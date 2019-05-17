<?php
namespace invoice;

//The item class is designed to support posting, unposting, reporting and 
//editing of rental charges. The data to post must be derived from some entity
//in the data model, called a driver. To post means saving the data to to some
//other entity or entoties, called storages, or modifying the existing one. 
//When the driver and the storage are the same, then posting invoves only one 
//table. Such an item is referrerd to as unary; binary when one driver and one storage
//are need, and unclassified if more than 1 storage is involged, e.g., 
//opening_balance
abstract class item {
    //
    //The record that is the parent structure of this item. This allows 
    //access to data for this item's sibblings and ancestors
    public $record;
    //
    //The entity that drives the generation of data for this item
    public $driver;
    //
    //Name of this item, used in headers and other situations where a valid
    //name is required -- hence respect for naming rules, e.g. all lower case
    //and underbar for separating name components
    public $name;
    //
    //Indicates if the item takes part in the computing of closing balance or 
    //not. For instance, the client is simply a left margin marker; the closing
    //balance item, usually appearing as a right margin of a tabular invoice, 
    //cannot be used for calculating itself -- so these are aesthetic items. 
    public $aesthetic;
    //
    //Statements for this item; currently 2 are expected--detailed and summary
    public $statements=[];
    //
    //An item is charaterized by its parent records and driver entity. 
    //For instance, the water item is driven by water connection, 
    //rent by agreement, invoice by client, etc.
    public function __construct(record $record, $driver) {
        //
        //Set the parent record of the item, so that we can access sibblings and
        //ancestral data.
        $this->record = $record;
        //
        //Driver is the starting point of the posting process
        $this->driver = $driver;
        //
        //The name of the item is used in report headings. It is derived from this
        //item's class name, after stripping off the item_ prefix and capitalizing
        //the first letter.
        //
        //Get the non-namespaced class of the item
        $reflect = new \ReflectionClass($this);
        $name = $reflect->getShortName();
        //
        $this->name = ucfirst(str_replace("item_", "", $name));
        //
        //By default, every item is is needed for computing closing balances.
        //Those not used as such ar edscribed as aesthetic cases because the used
        //to appear on the exrime right and left of the inoice dsplay. Now we
        //know that theu can appear anywhere. Invoice andclosing balance were
        //initially tthe only known cases. Then came auto and manual balances 
        //which are used for calculating opening balances
        $this->aesthetic = false;
        //
        //Define a shortcut for the item's databse.
        $this->dbase = $this->record->invoice->dbase;
        //
        //All payments and expences are in arrears, except rent and services 
        //which are paid in advance
        $this->advance = false;
    }
    
    //Prepare the statements of this item, one for detailed reporting, the other
    //for summaries -- depending on whether the underlying invoice is for posted
    //or unposted data
    function prepare_statements(){
        //
        //Let $suffix be the suffix of the function name, detailed_poster() or 
        //detailed_report, that returns the sql for reporting on posted
        //or unposted data.
        $suffix = $this->record->invoice->posted ? "report": "poster";
        //
        //Prepare the detailed satement
        //
        //Formulate the name of the sql to execute to retrieve the details
        //(or summaries) of the item, e.g., detailed_poster, summary_report,
        //e.t.c.
        $fname = "detailed_".$suffix;
        //
        //Get the sql, ensuring that it is parametrized (with a :driver) -- 
        //hence the 'true' setting
        $sql =   $this->$fname(true);
        //
        //Set the detailed statement using the parameterized sql
        $this->statements["detailed"] = new statement($this, $sql);
        //
        //Now prepare the summary statement with parametrizatio on.
        $this->statements["summary"] = new statement($this, $this->summary(true));
    }
    
    //
    //Returns the cuoff period of this item. 
    //By default, rent and service charges
    //are charged in advance, so their cutoff period is $n. Expense are from 
    //previous period, so thoer cuoff period is $n-1.  Closing 
    //balances is associated with the next period, i.e., $n+1
    function cutoff($n=0){
        //
        return $this->record->invoice->cutoff($n);
    }
    
    //Display the data of an item -- depending on the undelying invoice's layout 
    //specification 
    function display(){//item
        //
        //Get the level of detail to show for this item
        $level = $this->record->invoice->level;
        //
        //Let $s be the statement to work on
        $s = $this->statements[$level];
        //
        //Display the statement
        $s->display();
    }

    //Returns the sql for the reporting items using the storage tables
    //The key difference between this and detailed_poster is that the
    //former is driven by the invoice table; the latter by the client table.
    abstract function detailed_report($parametrized = true);

    //Returns the summary sql depending on the detailed one
    function summary($parametrized = true) {
        //
        //Get the detailed sql
        $posted = $this->record->invoice->posted ? "report": "poster";
        //
        $fname = "detailed_$posted";
        //
        return $this->chk(
            //    
            "select "
                //
                //The summary field is called value; by default it is base on 
                //the amount column of the detailed sql
                . "sum(detailed.amount) as value "
            . "from "
                //
                //Ensure that the posstage constarint is ommited; i.e., there 
                //is none
                . "({$this->$fname($parametrized, false)}) as detailed "
        );
    }

    //Shortcut for $this->query. The design is to ensure that the the reporet
    //erroe is as close as possible to the query that raised it
    function query($sql) {
        //
        //Check the sql
        try {
            //
            $stmt = $this->record->invoice->dbase->query($sql);
        } catch (\Exception $ex) {
            //
            //Re-throw the exception
            throw $ex;
        }
        //
        //Return the sql statement
        return $stmt;
    }

    //Shortcut for $this->chk is...
    function chk($sql) {
        //
        //Check the sql
        $stmt = $this->record->invoice->dbase->prepare($sql);
        //
        try {
            //
            //Bind the client parameter to some test value
            $stmt->execute([':driver' => 'test']);
            //
            //The statement is no longer useful
            $stmt->closeCursor();
            //
        } catch (\Exception $ex) {
            //
            //Re-throw the exception
            throw $ex;
        }
        //
        //Return the same sql as the input
        return $sql;
    }
    
    //Returns an sql that identifies the current invoice. This query is 
    //repeatedly used for posting items. A call to this method is valid only 
    //after the current invoice record has been inserted. 
    function current_invoice() {
        //
        return $this->chk(
            "select "
                //
                //Select all the fields of an invoice
                . "invoice.* "
            . "from "
                . "invoice "
            //    
            //Filter by current timestamp;
            ."where "
                . "invoice.timestamp ='{$this->record->invoice->timestamp}' "   
       );                
    }

    
    //Write the necessary changes to the database to effect a posting. Posting 
    //does noting for unary items; for binary items it:-
    //- Saves a storage record for the current date
    abstract function post();

        
    //Returns the sql for a detailed poster of this item. This sql is used for
    //displaying the items:-
    //a) as a well formated report to be dislayed, printed or emailed
    //b) as a query for posting or other further processing.
    //When used as (a) the client parametrization is need; as in (b) it is not 
    //needed.
    abstract function detailed_poster($parametrized = true);
    
    //Returns the sql for posting this item. This depends on 2 other sql's:-
    //a) the poster's driver and   
    //b)the detailed poster.
    //This function ensures that only records returned by the driver are posted
    function poster(){
        //
        //Get the sql used for driving the poster display
        $driver = $this->record->invoice->get_driver_sql();
        //
        //Get the sql for deriving the data to be displayed. As we will use this
        //sql for CRUD operations we don't need the client parametrized constraint
        $detailed = $this->detailed_poster(false);
        //
        //Now formulate the poster sql, noting that the client in the $detailed 
        //should be matched to the primary key field of the $driver
        //client is the primary key
        $sql = $this->chk(
            "select "
                //All fields of the detailed poster sql
                . "detailed.* "
            . "from "
                //Take teh sql that is driving teh display into account -- which
                //is why we need a poster
                . "($driver) as driver "
                //
                //Bring in the detailed driver now
                . "inner join ($detailed) as detailed on "
                    . "driver.primarykey = detailed.client"    
        );
        //
        //Ruturn the sql
        return $sql;
    }
    
    
}


//This class models manually generated items, e.g., opening balance, payment 
//and adjustment. These items:-
//a) always drive the poster sql
//b) are posted by simply establishing a link to the invoice
//c) are unposted by nullifying the link
//d) must be dated, so that only cases below cuoff are considered
//e) the driver and the storage tables are the same.
//Manuually generated items are unposted by simply delinking them fro the invoice
//used to post them
//This class needs 1 posting operand, the driver, which  also serves as the storage
abstract class item_unary extends item_binary {

    //
    public function __construct(record $record, $driver) {
        //
        //The storage of unary item is the same as its driver
        parent::__construct($record, $driver, $driver);
    }

    //This method report all unary items in teh same way because of the 
    //following facts:-
    //a) every unary item has a corresponding driver table
    //b) the driver records must are between the curent and last invoice
    //b) all the posted fields of the item can be accessed through a wild card 
    //operator
    function detailed_report($parametrized = true) {
        //
        //Let $item be the driver of the unary item being considered
        $item = $this->driver;
        //
        return $this->chk(
            //    
            "select "
                //Select all (*) the storage fields from the item. We will 
                //throw out primary and foreign key fields from the user reports
                . "$item.* "
            . "from "
                //
                //Bring in the table that drives the reporting
                . "$item "
                //
                //Bring in the invoice, as alluded to by the parameter. There is 
                //no direct link between the item and the invoice. The link will
                //established using the timestamps of the invoice and the item 
                . "join invoice "
                //
                //Bring in the previous invoice; there may be none
                . "left join invoice as prev_invoice on "
                    . "invoice.invoice_1 = prev_invoice.invoice "
            . "where "
                //
                //Add the invoice constraint, if needed.
                . ($parametrized ? "invoice.invoice =:driver " : "true ")
                //
                //Ensure the driver item is between the opening and cosing timestamps
                //
                //The item must be earlier than the timestamp of the invoice        
                . "and $item.timestamp < invoice.timestamp "
                //
                //If applicable, the item must be stamped later that the previous 
                //invoice        
                . "and if ("
                    ."prev_invoice.invoice is not null, " 
                    ."$item.timestamp >= prev_invoice.timestamp, "
                    ."true"
                    .")"
        );
    }

    //Posting does nothing for unary items
    function post(){}
    
    //Unposting of unary items does nothing; this is part of the motivation for 
    //adopting the new approach
    function unpost() {}

}

//A binary item is an one where the driver and storage are 2 differnt entities
//This class supports management of data that is automatically generated. Such 
//data can be wiped out without fear, as it can be re-created through posting.
abstract class item_binary extends item {

    //
    //A binary item has one storage entity where the charges are posted
    public $storage;

    //
    public function __construct(record $record, $driver, $storage) {
        //
        $this->storage = $storage;
        //
        //By default, every item is used for calculating the closing balances.
        //Such items are know as aesthetic non-items; the only known cases of aesthetic
        //items are invoice and closing balance. The use of aesthetic items in a 
        //report is simply aesthetic.
        $this->aesthetic = false;
        //
        parent::__construct($record, $driver);
    }

    //Returns the sql that is used for managing data to be posted in the current
    //period.  It is used in 2 ways:-
    //
    //(a) to display the data just before it is posted
    //(b) to implement the actual posting, thus changing the database by 
    //automatically creating new storage records or updating manually generated
    //cases.
    //
    //In case (a) the sql is executed for a very specific client, so, it contains
    //a client parameter so that we can program the record to be output the way 
    //we would wish. The parameter is not needed in case (b) since we use
    //the inbuilt features of the sql language to create, delete or update many 
    //database records at once.
    //
    //In addition, for display purposes, we do not wish to see posted data. 
    //
    //To support these 2 constraits, the method takes 2 boolean arguments
    //- $parametrized - to return a parametrized sql that constrains the result to 
    //  a specific client or invoice
    //- $postage - to select all postable records or just the unpostd ones
    function detailed_poster($parametrized = true) {
        //
        return $this->chk(
        "select "
            //
            //List the message data fields needed for communicating to the user 
            //monthly through an invoice report, e.g., 
            //agreement.amount * driver.factor as amount.
            . $this->messages->data
            //
            //The client foreign key field , needed for :-
            //a) calculating closing balances by grouping
            //b) inner joining the generated data to current invoice 
            //  for posting purposes. 
            //c) left joining to support compound items, e.g.. opeing 
            //balances
            . "$this->driver.client, "
            //
            //Extra identifiers of the storage beyond client and period.
            //In driving the following keys, the user has to :- 
            //(a)be aware of which identfiers are in the messages 
            //(b)exclude the client field.
            //All storage identifiers, excluding period, are used for 
            //formulating the on expression use for testing if generated 
            //records are posted or not
            . "$this->key_storage_identifiers "
        . " from "
            //
            //The table (virtual, e.g. power, or real) that drives this sql
            . "$this->driver as driver, "
            //
            //Add support for modifyng (either rowwise or columnwise) that 
            //the appropriate data needed for deriving the user messages 
            //are correct. E.g.:-
            //- a join to the initial balance with the most recent unposted 
            //  date, 
            //- joins to the water connenction with the current and previous 
            //  water readings, etc.     
            . "$this->driver_modidifiers "
            //
            //Add support for the postage constraint by extending the driver
            //with the posted stored items. This subquery is driven by the 
            //storage items for the current period. This is important for
            //for autogeneated items
            . "left join ({$this->posted_items()}) as storage on"
            //
            //Fields for joining the driver to the postage constraint.
            //These are all the identification fields of the storage
            //table, excluding the period. It has the general format:-
            //
        //storage.f1 = driver.f1 
            //and storage.f2 = driver.f2 ... 
            //and storage.fk = driver.fk
            //
        //for the k identification fields. Be guided by the key 
            //storage identifies formulated by the key_storage_identifiers
            //during field selection
            . "on $this->on_storage_identifiers "
            //
            //Add extra joins for supporting the messages and constraints, 
            //if any. E.g., in the water item, we require a wmeter join to
            //access the serial number message.
            . "$this->message_joins "
        . "where "
            //Apply the client parametrized constraint, if requested        
            . ($parametrized ? "driver.client = :driver " : "true ")
            //
            //Add the constraint for excluding future scenarios, e.g.,
            //ebill.date<='{$this->cutoff()}'    
            . "$this->currency_constraints"
        );
    }
    
    //Returns sql for reporting a binary item. Its storage entoty drives the sql
    //
    //This method will work for all binary items because of the following facts:-
    //a) Every binary item has a storage table linked to the current invoice
    //b) all the posted fields of any item can be accessed through a wild card 
    //operator on the storage entity
    function detailed_report($parametrized = true) {
        //
        return $this->chk(
            //    
            "select "
                //Select all (*) the storage fields from the storage. We throw 
                //out primary and foreign key fields from the user reports
                . "storage.* "
            . "from "
                //
                //The storage table drives the reporting process.
                . "$this->storage  as storage "
            . "where "
                //
                //Add the invoice constraint, if needed.
                . ($parametrized ? "storage.invoice =:driver " : "true ")
        );
    }

    
    
    //Unposting of a binary item simply removes its storage records from the
    //database. Which ones? The last posted ones.
    function unpost() {
        //
        //For debugging; this variable allows us to inpect the delete sql 
        //statement before exceuting it -- when debugging
        $sql = null;
        //
        //This general routine is used by many items. By trapping any errors, we
        //can custmose the error message to tells us what item we were 
        //unposting when it occured.
        try{
            //
            //Now delete this item's storage records for the current data
            //
            //But first, formulate the query, so that we can inspect in the next
            //step -- if needed
            $sql ="delete "
                    //
                    //The storage table to delete records from
                    . "$this->storage.* "
                . "from "
                    //
                    . "$this->storage "
                    //
                    //Bring in the last invoice. (The current one cannot be used
                    //for that purpose as its timestamp is indeterminate)
                    . "inner join ({$this->record->invoice->last_invoice()}) as last_invoice on "
                        . "$this->storage.invoice = last_invoice.invoice ";
                   
            //You may inspect, at this point, the sql before executing. Do the
            //execution from first principles (rather than call $this->query) so 
            //that the exception can be re-thrown        
            $this->record->invoice->dbase->query($sql);
        }
        catch(\Exception $ex){
            //
            //Add the item's name to the exception message and re-throw
            throw new \Exception("Item=$this->name. {$ex->getMessage()}");
        }
    }

}
