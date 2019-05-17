<?php
namespace invoice;

//Modelling the database on a server
class dbase_majorm extends dbase {
    //
    public function __construct() {
        //
        $username = "mutallco";
        $password = "mutall_2015";
        //
        //Use the latest version 
        $dbname = "mutallco_majorm";
        //
        parent::__construct($username, $password, $dbname);
    }

}

//A report for old invoices
class report_majorm extends report{
    //
    //
    public function __construct() {
        //
        //Construct the parent.
        parent::__construct();
        }
    
    //The databse used by mutall project
    function get_dbase() {
        //
        return new dbase_majorm();
    }
    
    //Returns the mutall record
    function get_record(){
        return new record_majorm($this);
    }
}

//A poster, for genartaing ad managing new invoices
class poster_majorm extends poster{
    //
    public function __construct() {
        //
        //Construct the parent.
        parent::__construct();
        }
    
    //The databse used by mutall project
    function get_dbase() {
        //
        return new dbase_majorm();
    }
    
    //Returns the mutall record
    function get_record(){
        return new record_majorm($this);
    }
}

//Emailer
class emailer_majorm extends emailer{
    //
    function __construct() {
        parent::__construct();
        //
        //Add the cc's.
        $this->AddCC('njuguna27@gmail.com');
        $this->AddCC('peterkmuraya@gmail.com');
    }
    
}

//Model a invoice report to be printed a normally, i.e., on A4 paper
//using a color printer implying heavy use of CSS.
class layout_majorm extends layout_label {
    //
    function __construct() {
        parent::__construct();
    }
    
    //Show this underlying rcords data in on all the 5 sections of a labeled report
    function display_record() {
        //
        //Show the logo will all its supporting labels
        $this->show_record_logo();
        //
        //Use the invoice item to display the identification section of the
        //report
        echo "<section>";
        echo "<p class='name'>Client Identification</p>";
        //
        //Let $ds be the detailed statement of an invoice item
        $ds = $this->record->items['invoice']->statements['detailed'];
        //
        //Let $ll be the a label layout
        $ll = new layout_label();
        //
        //Display the invoice detailed in a label layout
        $ll->display_statement($ds);
        echo "</section>";
        //
        //Use all the remaining items to show the report summary debit/credit
        //table, including teh closing balance with its double lines.
        $this->show_record_summary();
        //
        //Use all the remaining items to show the report details of each
        //item laid out in a label format, where only one record is involed,
        $this->show_record_detailed();
        //
        //Show the announcements, i.e., notes
        $this->show_record_announcements();
    }
    
    //Show the company logo and address.
    function show_record_logo(){
        //
        //Output the Logo image plus associated text
        echo 
        "<section id='logo'>"
            . "<img "
                . "src='logo.jpg'"
                . "style='width:150px; height:100px'"
                . "/>"
            . "Mutall Investment Co. Ltd. <br/>"
            . "P.O. Box 374<br/>"
            . "Kiserian - 00206 <br/>"
            . "email: mutallcompany@gmail.com<br/>"
            . "contact:Wycliffe on 0727 203 769<br/><br/>"
            .date("jS M Y"). "<br/>"
        ."</section>";
        //
        //Show the type of this document: invoice or statement, as a complete 
        //reference
        //
        //Let $ref be the reference to ths document
        $ref = [];
        //
        //Test if there is any previus posting
        if ($this->record->try_ref($ref)){
            //
            //There is. Report it
            echo 
            "<div>"
            . "{$ref['type']}<p>REF: {$ref['code']}</p>"
            . "</div>";
        }else{
            //There is none.Report the situation
            echo 
            "<div>"
            . "No posted data found for this period"
            . "</div>";
        }
    }
    
    //Use all the items of a record, other than invoice, to show the report 
    //details of each item laid out in a label format, where only one record 
    //is involed,
    function show_record_summary() {
        //
        echo "<section>";
        echo "<p class='name'>Summary</p>";
        //
        //Print a table tag.
        echo "<table>";
        //
        //Step through all the items of a record and display each one of them.
        foreach ($this->record->items as $key => $item) {
            //
            //Exclude the invoice.
            if ($key !== 'invoice') {
                //
                //Get teh summary data from the item and show in the label forma.
                $this->show_record_summary_item($item);
            }
        }
        //
        //Close the table.
        echo "</table>";
        echo "</section>";
        //
    }
    
    //Display the summary data of the given item in a tabular layout
    function show_record_summary_item(item $item) {
        //
        //Condition for displaying a summary arecord are:-
        //a) the summary must exist
        //b) the amount is not null
        $valid = (count($item->statements['summary']->results)>0) 
            && (!is_null($item->statements['summary']->results[0][0]));  
        //
        //Only cases with data will be shown
        if ($valid){
            //
            //Get the data
            //
            //Let $x be the amount to display. Only one result is expected
            $x =  $item->statements['summary']->results[0][0];
            //
            //Format it as an amount, i/r., no decimal and a thousand separator.
            $amount = number_format($x);   
            //
            //Show the data
            //
            echo "<tr name='$item->name'>";
            //
            //Now show the data
            echo "<td>";
                echo $item->name;
            echo "</td>";
            //
            //The values should be right aligned
            echo "<td class='double'>";
                //
                //Format the amount to currency
                echo $amount;
            echo "</td>";
            //
            //Close tr.
            echo "</tr>";
        }
    }

    //Use all the remaining items to show the report details of each
    //item laid out in a label format, where only one record is involed,
    function show_record_detailed() {
        //
        echo "<section>";
        //
        echo "<name>Details</name>";
        //
        //Step through all the items of a record and display each one of them.
        foreach ($this->record->items as $key => $item) {
            //
            //Exclude the invoice.
            if ($key !== 'invoice') {
                //
                //Show the item's data -- depnding on the number of records and
                //fields
                $item->display($this); 
            }
        }
        echo "</section detailed>";
        
    }
    
    //Show announcements on a record; that depends on the source of the normal report
    function show_record_announcements(){
        //
        //We assume that the the open_record($record) funstion is called before
        //making reference to $this->record
        //
        //First, test if here is posted data; if none return
        if (!isset($this->record->items['rent']->statements['detailed']->results[0])){
           return;     
        }
        //
        //Output the announcements
        //
        echo "<section>";
        //
        //Let $r be the rental value of the earliest agreement
        $r = $this->record->items['rent']->statements['detailed']->results[0]['price'];
        //
        //Let $a be the arrears
        $a = $this->record->get_arrears();
        //
        //Let $f be 1 for monthly and 3 for quaterly clients
        $f = $this->record->result['quarterly'] ? 3: 1;
        //
        //Let x be the no. of rental amounts in the opening balance, taking $f
        //into account 
        $x = $a/($r * $f);
        //
        //An upto-date acccount; thanks
        if ($a<=0){
            echo
            "<p class='ok'>"
            . "This account is very upto-date. Thank you."
            . "</p>";
        }
        //A well maintained account with a little balance
        elseif ($x<1){
            echo 
            "<p class='ok'>"
            . "This account is well maintained. Please clear the outstanding balance to make it perfect."
            . "</p>";
        }
        //
        //Arrears less that 2 months old
        elseif ($x>=1 and $x<2){
            echo
            "<p class='warning'>"
            . "This account is ok, but you have arrears that is more than your rental amount. Please clear the outstanding balance."
            . "</p>";
        }
        //
        //Arrears more than 2 months old is distressful
        else{
            //Format the x
            $fx = number_format($a/$r);
            //
            //This is a distress situation
            echo 
            "<p class='distress'>"
            . "Your account is in arrears of Ksh "
            . number_format($a)
            . " which is close to $fx times the rental amount of Ksh $r. "
            . "Please regularise it. This is the last request before we start "
            . "instituting measures to recover the current outstanding balance of "
            . number_format($this->record->items['closing_balance']->statements['summary']->results[0][0])
            . ".</p>";
        }
        //
        //Get the client code for announcement purposes
        $code = $this->record->items['invoice']->statements['detailed']->results['0']['id'];
        //
        echo 
        "Make all cheques payable to Mutall Investments Co. Ltd<br/>"
        . "<br/>"
        . "Coop Bank-Kiserian<br/>"
        . "A/c No: 011 485 839 41800<br/>"
        . "OR<br/>"
        . "Kenya Commercial Bank-Kiserian<br/>"
        . "A/c No: 111 971 8260<br/>"
        . "<br/>"
        . "Please indicate your client code, <strong>$code</strong>, as the reference on "
                . "the banking slip<br/>";
        
        echo "</section>";
        
    }
    
}


//Modelling an invoice record
class record_majorm extends record{
    //
    function __construct(invoice $invoice){
        //
        //Call the parent constructor, passing this mutall record
        parent::__construct($invoice);
    }
    
    //Set the user defined items for mutall rental. 
    function get_udf_items(){
        //
        return [ 
            'water' => new item_water($this)
        ];
    }                

}

//
//This class supports management of the water resource for the MajorM data model.
class item_water extends item_binary{
    //
    public function __construct($record) {
        //
        //The driver is water connection and the auto-generated 
        //data from meter readings is stored in the water consumtion table
        parent::__construct($record, "wconnection", "wconsumption");
    }
    
    
    //Returns that water consumption to be charged to a client. This is a most
    //basic calculation for water vendors
    function detailed_poster($parametrized=true){
        //
        //Assuming previous value is not null.....
        $diff = "(curr.value - prev.value)";
        //
        //the difference might be null
        //        
        //Define quantity of water consumed, nullifing negative cases
        $qty = "if($diff<0, null, $diff)";
        //
        return $this->chk(
            "select "
                //The Client Messages
                //
                //The water meter being read
                ."wconnection.meter_no, "
                //
                //The most recent psoted is the previous date...
                ."prev.date as prev_date, "
                //
                //..and its associated value is the previous reading
                ."prev.value as prev_value, "
                //
                //The most recent unposted date is the current date...
                ."curr.date as curr_date, "
                //
                //and the current reading is the matching value
                ."curr.value as curr_value, "
                //
                //Consupmption is the positive differenece between readings. 
                //It is null if any of them is null
                ."$qty  as units, "
                //
                //The charge rate -- which is meter dependent. The borehole water 
                //has a different rate from vendor supplied one.
                ."vendor.price, "
                //
                //Compute the consumption
                ."vendor.price * $qty as amount, "
                //
                //What is the status of the meter, connected or disconnected
                //. "state.disconnected, "
                //
                //Keys
                //    
                //The client foreign key field , needed for :-
                //a) calculating closing balances by grouping
                //b) innerr joining the generated data for export
                . "wconnection.client, "
                //
                //Required for establishing connection to water consumption
                . "wconnection.wconnection "
            //    
            . "from "
                //
                //This process is driven by water connections
                ."wconnection "
                //
                //Bring in the venor-- who determines the water price
                . "inner join client on wconnection.client = client.client "
                . "inner join vendor on client.vendor = vendor.vendor "
                //
                //Extend the driver to allow calculations of water consumption
                //
                    //Add the previous reading in the current period
                    . "left join ({$this->prev_reading()}) as prev on "
                        . "prev.wconnection = wconnection.wconnection "
                    //
                    //Add the current reading in the current period
                    . " left join ({$this->curr_reading()}) as curr on "
                        . "curr.wconnection = wconnection.wconnection "
            //  
            //Add the various constraints            
            . "where "
                //        
                //Apply the client parametrized constraint, if requested        
                . ($parametrized ? "wconnection.client = :driver ": "true ")
        );
    }
    
    
    //Returns previous reading for a connection. 
    function prev_reading(){
        //
        return $this->chk(
            "select "
                //This is a water connection exension; so the connection primary 
                //key wil be needed
                ."wconnection.wconnection, "
                //
                //We need to report on the date and value of the water reading
                ."wreading.date, "
                ."wreading.value "
            //    
            . "from "
                //
                //The process is driven by water connection
                ."wconnection "
                //
                //The target join requires access to water reading date, so 
                //bring in wreading
                ."inner join wreading on "
                    . "wreading.wconnection = wconnection.wconnection "
                //
                //Get the previous date for the same water connection
                //This is the target join; it has a double join.
                . "inner join ({$this->prev_date()}) as date "
                    . "on date.wconnection = wconnection.wconnection "
                    . "and date.value = wreading.date"
        );
    }
    
    //Returns the date of the previous reading, whether the connection is old or 
    //new
    function prev_date(){
        //
        $sql = $this->chk(
            "select "
                //
                //Returns the date of an old client if valid, otherwise assume
                //the client is new.
                . "if(old_date.value is not null, old_date.value, new_date.value) as value, "
                //
                //Connection is neeed to support furtjer joins
                . "wconnection.wconnection "
            //    
            . "from "
                //
                //The process is driven by the water connection
                . "wconnection "
                //
                //Date for an existing, i.e., old,  connection
                . "left join ({$this->old_prev_date()}) as old_date on "
                    . "old_date.wconnection = wconnection.wconnection "
                //
                //Date for a new water connection
                . "left join ({$this->new_prev_date()}) as new_date on "
                    . "new_date.wconnection = wconnection.wconnection "
        );
        //
        return $sql;
        
    }
    
    //Returns the previous date of water readings for an old, i.e., exising 
    //connection. This means the water consumption of the last invoice
    function old_prev_date(){
        //
        $sql = $this->chk(
            "select "
                //
                //The current readinn of the last consumption
                . "wconsumption.curr_date as value, "
                
                //..of a given water connection. 
                ."wconnection.wconnection "
            //    
            ."from "
                //
                //The process is driven by the water connection
                . "wconnection "
                //
                //You need access (last) water consumption
                . "inner join wconsumption on "
                    . "wconsumption.wconnection=wconnection.wconnection "
                //
                //We need teh last invoice, to ensure the last water consumption
                . "inner join ({$this->record->invoice->last_invoice()}) as invoice on "
                    . "wconsumption.invoice = invoice.invoice "
        );
        //
        //
        return $sql;        
    }
    
    //Returns the previous date of a water readings for an new client, i.e., one
    //for which we have never had any posting. It is the oldest reading below
    //current timestamp
    function new_prev_date(){
        //
        $sql = $this->chk(
            "select "
                //
                //The desired most recent date comes from the water reading...
                . "min(wreading.date) as value, "
                //
                //...for a given water connection
                ."wconnection.wconnection "
            //    
            ."from "
                //
                //The process is driven by the water connection
                . "wconnection "
                //
                //You need access to the water reading
                . "inner join wreading on "
                    . "wreading.wconnection=wconnection.wconnection "
            //    
            ."where "
                //
                //Exclude future readings
               . ($this->record->invoice->has_future 
                        ? "wreading.date<'{$this->record->invoice->future_date}' "
                        :"true "
                   )
            //
            ."group by "
                //        
                //The grouping is by the driver, the water connection
                . "wconnection.wconnection "
        );
        //
        //
        return $sql;        
    }
    
    
    //Returns the sql for reporting on current reading. It is the highest dated
    //unposted reading below the cutoff for any water connectiion
    function curr_reading(){
        //
        return $this->chk(
            "select "
                //
                //We need to report on the date and value of the water reading
                ."wreading.date, "
                ."wreading.value, "
                //
                //This is a water connection extension; so the connection primary 
                //key wil be needed
                ."wconnection.wconnection, "
                //
                //The water reading primary key is needed for linking this 
                //time-variant quantity to the current invoice
                . "wreading.wreading "
                //
            . "from "
                //
                //The process is driven by water connection
                ."wconnection "
                //
                //The target join requires access to water reading date, so 
                //bring in wreading
                ."inner join wreading "
                    . "on wreading.wconnection=wconnection.wconnection "
                //
                //Get the highest posted date below cuoff for a water connection
                //This is the target join; it has a double join
                . "inner join ({$this->curr_date()}) as date on "
                    . "date.wconnection = wconnection.wconnection "
                    . "and date.value = wreading.date"
        );
    }
    
    //Returns the current dates of a water readings for each connection. Current
    //date is defined as the highest date for current readings per connection. 
    //A current reading is neither futuristic nor historical.
    function curr_date(){
        //
        $sql = $this->chk(
            "select "
                //We will be ates grouping by water connection. 
                ."wconnection.wconnection, "
                //
                //The desired date comes from the water reading; pick teh highest
                . "max(wreading.date) as value "
            ."from "
                //
                //The process is driven by the water connection
                . "wconnection "
                //
                //Bring in the water reading
                . "inner join wreading on "
                    . "wreading.wconnection=wconnection.wconnection "
                //
                //Support for filltering historical readings -- if any (because only 
                //current readings are considered)
                ."left join ({$this->record->invoice->last_invoice()}) as last_invoice on "
                    . "last_invoice.client = wconnection.client "
               
            ."where "
                //
                //Filter out future readings (that may be in the database). This
                //invoice's timestamp separates the current from the future       
                .($this->record->invoice->has_future 
                        ? "wreading.date<'{$this->record->invoice->future_date}' "
                        :"true "
                   )
                
                //
                //Filter out historical readings -- if applicable
                ."and if (last_invoice.invoice is null, "
                        . "true, "
                        . "wreading.timestamp>=last_invoice.timestamp "
                      .") "
                        
            ."group by "
                //        
                //The grouping is by the driver, i.e., the water connection
                . "wconnection.wconnection " 
        );
        //
        //
        return $sql;        
    }
    
    
    //Posting water, a binary item, involves creating new records in the 
    //storage, i.e., the water consumption table. Posting simply freezes the
    //formular:-
    //water.charge = water.consumption * vendor.price
    function post(){
        //
        $this->query(
        //
        //Create the water records to be posted...
        "insert into "
            //     
            . "wconsumption ("
                //
                //Specify the water message fields for communicating with the 
                //client
                ."prev_date, curr_date, curr_value, "
                ."prev_value, units, price, amount,"
                //
                //
                //Specify all the water storage identifiers.
                ."wconnection, invoice "
            . ")"
            //
            // Select from the oster and current invoice
            . "(select "
                // 
                //The poster fields to match the desired messages supply the data.
                //The comma will be ignored if there are no message datas 
                //. $this->messages->data
                ."prev_date, curr_date, curr_value, "
                ."prev_value, units, price, amount,"
                //
                //Water connection is one of the identifiers
                . "poster.wconnection, "
                //
                //We need teh curent invoice
                . "current_invoice.invoice "
            . "from "
                 //Get data come from this items's poster sql, with the following 
                 //conditions:-
                 //
                 //No parametrized client constraint is needed for posting 
                 //pusposes because database CRUD operations are designed to 
                 //work with multiple records.
                 . "({$this->poster()}) as poster "
                //
                //we need the current invoice
                ."inner join ({$this->current_invoice()}) as current_invoice on "
                    . "poster.client = current_invoice.client "
                 
            .") "     
        . "on duplicate key update "
            //
            //List all the fields, except the identifiers -- wconsumption and date             
            . "prev_date = values(prev_date), "
            . "curr_date = values(curr_date), "
            . "curr_value = values(curr_value), "
            . "prev_value=values(prev_value), "
            . "units = values(units), "
            . "price = values(price), "
            . "amount = values(amount)"
        );
    }
}


