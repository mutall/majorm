<?php
namespace invoice;

//
//Require all item files. Note the referencing, relative to the current 
//directory which explains why the project and the library are organized the
//way they are
require_once "invoice/items/item.php";
require_once "invoice/items/adjustment.php";
require_once "invoice/items/closing_balance.php";
require_once "invoice/items/opening_balance.php";
require_once "invoice/items/payment.php";
require_once "invoice/items/services.php";
require_once "invoice/items/item_invoice.php";

//
//A layout is how the infomation on  page is presented. There are two different 
//kinds of layouts:-
//Tabular: Presenting the data in a table format.
//Label: Presenting the data in a label format.
abstract class layout {
    
    //Tabular layout should not show labels, as it is assumed that the labels 
    //are in the header. For labeled layout, they should.
    public $show_label;
    
    //Tags for describing a layout, modelled along a tabular layout
    public $table_tag;
    public $record_tag;
    public $field_tag;
    //
    //The following data may be set using open tags; see, e.g.,  
    //open_record($record)
    public $record;
    public $invoice;
    
    //
    function __construct($show_label, $table_tag, $record_tag, $field_tag) {
        
        $this->show_label = $show_label;
        
        $this->table_tag=$table_tag;
        $this->record_tag=$record_tag;
        $this->field_tag=$field_tag;
    }
    
    //
    //Displays the table tag (for a tabular lyout) and save the invoice
    //for further references
    function open_table(invoice $invoice){
        //
        //Save teh invoive for further references
        $this->invoice=$invoice;
        //
        //Open the table tag; the invoice can be used to supply data for setting
        //any attributes that are needed
        echo "<$this->table_tag>";
    }
    
    function close_table(){
        echo "</$this->table_tag>";
    }

    //
    //Display the open tag of this layout and save the record associated with this
    //layout for future use
    function open_record(record $record){
       //
        //Save the record so that we can make references within it
        $this->record=$record;
        //
        //Get the client code field assuming the driver_sql for reporting 
        //has retrieved it
        $code = $record->result['code'];
        //
        //Show the open record tag. In future we will use the record to supply
        //further attributes to support e.g., interaction with users.
        //attributes may be added
        echo "<$this->record_tag class='record' id='$code'>";
    }
    
    function close_record(){
        echo "</$this->record_tag>";
    }

    
    //Show the item display, e.g., <td> for a tabular layout. 
    function open_item(item $item){
        //
        //Set the class for this opening tag
        //
        //Use the $item to define condition for displaying money
        $double =
            //
            //Invoie is not a money item
            $item->name!=='Invoice'
            //
            //Onlly the summarised case is considered
            && $item->record->invoice->level=='summary';
        //
        //Ddefine the double class
        $class = $double ? "class='double'": "";    
        //
        //Output the open item tag; its <td> for tabular cases, field for label
        //layouts
        echo "<$this->field_tag $class>";
    }
    
    //Close the item tag is shown, e.g., </td>
    function close_item(){
        echo "</$this->field_tag>";
    }
    
    //
    abstract function display_header();

    //Display this layouts given record
    abstract function display_record();

    //Show the given statement using this layout; we assume that teh statemet
    //already has some result.
    abstract function display_statement(statement $statement);
    
}

//The tabular layout is characterized by a header
class layout_tabular extends layout{
    
    function __construct() {
        //
        //Tabular layout should not show the labels (only the data as it is assumed
        //that teh labels are at the header)
        parent::__construct(false, "table", "tr", "td");
    } 
    //
    //Show the head tag.
    function show_tag_head($open = true) {
    }

    //Show the header tags for a tabular layout
    function display_header() {
        //
        //Echo the thead tag.
        echo "<thead>";
        //
        //Print the colspan tiile row.
        echo "<tr>";
        //
        //Show the name of each item.
        foreach ($this->invoice->record->items as $item) {
            //
            //Print the item name as the table heads.
            echo "<th>$item->name</th>";
        }
        //
        //Close the header row.
        echo "</tr>";
        //
        //Close the thead tag.
        echo "</thead>";
    }
    
    
    //Display the current record of this layout's invoice in a tabular 
    //fashion. In this case, the display is a very simple table; in other cases, 
    //e.g., that of layout_mutall, it can be quite complex,  
    public function display_record() {
        //
        //Display each item of the underlying record
        foreach ($this->record->items as $item) {
            //
            //Output the item tag with all her atributes -- if any
            $this->open_item($item);
            //
            //Now use the invoice's layout to dislay the item. (we can access the
            //current invoice from an item.
            $item->display();
            //
            //Close the field tag
            $this->close_item();
        }
    }
    
    //Display the given statement (of an item) in a tabular fashion
    function display_statement(statement $statement) {
        //
        //Get the fields of the statement
        $fields = $statement->get_show_fields();
        //
        //Output the table tag.
        echo "<table class='statement'>";
        //
        //Output the table header.
        echo "<tr>";
        foreach ($fields as $fld) {
            //
            //Print the fields names.
            echo "<th>".ucfirst($fld->name)."</th>";
        }
        //
        //Close the header.
        echo "</tr>";
        //
        //Show the body
        foreach ($statement->results as $result) {
            //
            //Open the table row 
            echo "<tr>";
            //
            //Output the body of the table.
            foreach ($fields as $fld) {
                //
                //Get the value
                $value = $result[$fld->name];
                //
                //Format it.
                $fvalue = $fld->format($value); 
                //
                //Show it, using the field's CSS style. 
                echo "<td $fld->style>" .$fvalue . "</td>";
            }
            //
            //Close teh table row
            echo "</tr>";
        }
        //
        //Output the summary row
        //
        //Get the column span of the closing row
        $span = count($fields)-1;
        //
        //Only cases where the span is greater than 1 need be considered, i.e.,
        //single field columns do not need spanning
        $colspan = $span>1 ? "colspan=$span": "";
        //
        //Output the last row
        echo 
        "<tr>"
        . "<td $colspan>"
            . "Total"
        . "</td>"
        . "<td class='double'>"
            //    
            . $statement->item->statements['summary']->results[0][0]
        . "</td>"
        ."</tr>";        
        //
        //Close the table tag
        echo "</table>";
    }

    //
    //Shows the footer of the page. If any is requred.
    function display_footer($open = true) {
        //
        //Check for the status of the open varible.
        if ($open) {
            //
            //Open the footer tag.
            echo "<footer>";
        } else {
            //
            //Close the footer tag.
            echo "</footer>";
        }
    }

}

//The label layout uses puts a label before its value, for every field in a 
//record. It us a singleton class
class layout_label extends layout{
    
    function __construct() {
        //
        //A labeled layout should show her labels
        parent::__construct(true, "report", "div", "field");
    }
    
 
    //Label layouts have no header
    function display_header() {}
    
    //Show the given record in a label layout. Thisis an example
    //Opening balance   Date:2019-05-08     200
    //Water             Previous reading    34
    //                  Current reading     36
    //                  Consumption         2
    //                  Amount              240
    //Payment           Amount              300
    //etc                  
    //This method will be extended to support the thermal printer
    function display_record(){
        //
        //The general layout is that of a table. Open it.
        echo "<table>";
        //
        //The table will be 
        //
        //The data to be displayed will come from the dateiled or summary 
        //statement
        //
        //The body is drived from the record being displayed
        foreach($this->record->items as $item){
            //
            //Get the level of details, sumary or etailed
            $level = $this->record->invoice->level;
            //
            //Use teh requested statement to drive this dislay
            $statement = $item->statements[$level];
            //
            //Count the number of rows in the results; it will be used for 
            //spaning the item column
            $span = count($statement->results);
            //
            //Get the fields to be shown
            $fields = $statement->get_show_fields();
            //
            //Count the number of fields
            $nofields = count($fields);
            //
            //Loop through all the the result records. 
            foreach($statement->results as $row){
                //
                //Set the first row indicator to true
                $first_row = true;
                //
                //Loop through all the fields. We need to know when we are 
                //outputing teh fis field, so that we can row-wise span the item 
                //as needed
                foreach($fields as $field){
                    //
                    //Each field will be output in a row
                    echo "<tr>";
                    //
                    //
                    //Ouput the item name on conditon that it is the first field
                    if ($first_row){
                        //
                        //The row span depends on the count of fields in the 
                        //statement
                        echo "<td rowspan='$nofields'>$item->name</td>";
                    }
                    //
                    //Output the field name
                    echo "<td>$field->name</td>";
                    //
                    //Output the field value
                    echo "<td>{$row[$field->name]}</td>";
                    //
                    //Close the output row
                    echo "</tr>";    
                    //
                    //Reset the first row, as subsequent rows cannot be the first
                    //row
                    $first_row = false;
                }
            }
        }
        //
        //Close teh table
        echo "</table>";
    }
    
    //Display an items driver statement in a label format. We assume that the 
    //item's result is only one record. Multiple records are best shown in a 
    //tabular fashion
    function display_statement(statement $statement) {
        //
        //Get the item's only record to display
        $result = $statement->results[0];
        //
        $fields = $statement->get_show_fields();
        //
        $hidden = count($fields)==1 ? "hidden=true": "";
        //
        //Show the fields in a flexi display
        echo "<div class='fields'>";
        //
        //Step through all the fields of this items result 
        foreach ($fields as $field) {
            //
            //Get the field name
            $fname = $field->name;
            //
            //Get the field value
            $value = $result[$fname];
            //
            //Format the value
            $fvalue = $field->format($value);
            //
            echo 
            "<p class='field'>"
                //
                //Not the nonbreakng space after field name
                ."<span $hidden class='fname'>".ucfirst($fname).":&nbsp;</span>"
                ."<span $field->style class='fvalue'>"
                    //
                    //Show the formated value
                    . $fvalue
                 ."</span>"
            //Note the space after the <p> tag to invite a wordwrap point.        
            . "</p> ";
            
        }
        //
        //Close the flexi display
        echo "</div>";
        
    }
    
}

//The detailed layout is a combination of tabular and label layouts -- tabular
//for summaries and label for the details. It has no header, so it inheits the
//label layout
class layout_summary_detail extends layout_label {
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
        echo "<section>";
        echo "<p>Announcements</p><br/>";
        //
        //List all the active here. Active means that the current invoice 
        //timestamp is between the start and end date of the message
        //                  invoive.timestamp
        //----|-------------|-------------------|------------
        //     msg.start_date                  msg.end_date
        //     
        //Put the address of the vendor here
        //
        //Get the vendor name account code data from the current record, 
        //invoice item.
        $invoice = 
            $this-> record->items['invoice']->statements['detailed']->results[0];
        //
        $code = $invoice['code'];
        $vendor_name=$invoice['vendor_name'];
        //
        echo 
        "Make all cheques payable to $vendor_name<br/>"
        . "<br/>"
        ."Account number(s):-"        
        . "<br/>"
        . "<br/>"
        . "Please indicate your client code, <strong>$code</strong>, as the reference on "
                . "the banking slip<br/>";
        
        echo "</section>";
        
    }
    
}


    
//A layout suitable for the thermal printer. Strings are padded as required
class layout_thermal extends layout_label {
    //
    //Total width limit of the thermal printer
    const TOTAL_WIDTH = 32;
    
    const ITEM_WIDTH = 3;
    const FIELD_WIDTH = 13;
    //
    //The character to used for padding
    const PADCHAR=".";
    
    //Place for holding the text of a page
    public $page;
    //
    //Holder for all the pages of this layout
    public $pages;
        
    function __construct(){
        parent::__construct();
    }
    
    //
    //Displays the thermal "table" tag (for a tabular lyout) and save the invoice
    //for further references
    function open_table(invoice $invoice){
        //
        //Save the invoive for further references
        $this->invoice=$invoice;
        //
        //Initialize the thermal pages to an empty array
        $this->pages = [];
    }
  
    //
    //Closing the table of a therl output simply echos the json version
    function close_table(){
        //
        header("Content-Type:application/json");
        echo json_encode($this->pages);
        
    }

    //
    //Opening a thermal percord simply initialiss a page (text) collector
    function open_record(record $record){
       //
        //Save the record so that we can make references within it
        $this->record=$record;
        //
        $this->page = "";
    }
    
    //
    //Closing a record pushes the record page to teh invoice pages
    function close_record(){
        $this->pages[] = $this->page;
    }

    
    //Show the given record in a label layout. Thisis an example
    //Opening balance   Date:2019-05-08     200
    //Water             Previous reading    34
    //                  Current reading     36
    //                  Consumption         2
    //                  Amount              240
    //Payment           Amount              300
    //etc                  
    //This method will be extended to support the thermal printer
    function display_record(){
        //
        //The data to be displayed will come from the invoice level, assuming
        //that the record property has been set using the open tag.
        $level = $this->record->invoice->level;
        //
        //Visit all the items of the record and display each one of them
        foreach($this->record->items as $item){
                        
            //
            //Use the requested statement to drive this dislay
            $statement = $item->statements[$level];
            //
            //Display the statement
            $this->display_statement($statement);
        }
        
    }
    
    //Collect the output from the given statement fit for thermal printing
    function display_statement(statement $statement){
        //
        //Get the statement's fields to be displayed. Not all fields need 
        //to b displayed, e.g., primary and foreign keys.
        $fields = $statement->get_show_fields();
        //
        //Loop through all the pre-fetched result rows of the statement being 
        //displayed. 
        foreach($statement->results as $row){
            //
            //Indicate that this is the first row, as it needs to be output 
            //specially
            $first_row = true;
            //
            //Loop through all the fields. We need to know when we are 
            //outputing the first field, so that we can row-wise span the item 
            //as needed
            foreach($fields as $field){
                //
                //Ouput the item name on conditon that it is the first row
                if ($first_row){
                    //
                    //The first field in an item can occuppy as musch space as
                    //it wants. Prefix it with a new line spacing. Note the 
                    //double slash
                    $this->page .= "\\n".$statement->item->name."\\n";
                }else{
                    //All othere subsequent foeld o a sttaement must be fitted
                    //to teh desired space
                    //
                    //With space to the desired with
                    $this->page .= $this->fit(" ", self::ITEM_WIDTH, " ");
                }
                //
                //Fit the field name to the desired width
                $this->page .=  $this->fit($field->name, self::FIELD_WIDTH, ".");
                //
                //The data width must be truncated to $data_with characatrrs
                $data_width = self::TOTAL_WIDTH - (self::ITEM_WIDTH + self::FIELD_WIDTH);
                //
                //Output the value, truncating as necssary
                $this->page .= substr($row[$field->name], 0, $data_width);
                //
                //Close the output row
                $this->page .= "\\n";    
                //
                //Reset the first row, as subsequent rows cannot be the first
                //row
                $first_row = false;
            }
        }
}    
            
    
    
    //Fit teh given string to the given field width -- trimming or padding where 
    //where necessary
    function fit($str, $len, $separator){
        //
        //Get teh length of teh string
        $strlen = strlen($str);
        //
        //Decide whether to truncate teh string or padd id
        if ($strlen>$len){
            //
            //Trim the string by $x characters
            return substr($str, 0, $len);
        }
        //
        else{
            //
            return str_pad($str, $len, $separator, STR_PAD_RIGHT);
            
        }
    }
   
}

class dbase extends \PDO {

    //
    //The properties of this class are:-
    public $username;
    public $password = "";
    public $dbname;

    //The tables of this database 
    public $tables;
    //
    //The constructor method to be called each time an instance of this class in made.
    public function __construct($username, $password, $dbname) {
        //
        //Set the dsn properties.
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
        //
        //Set the dsn.
        $dsn = "mysql:host=localhost;dbname=$dbname";
        //
        //Parent constructor take a data source name.
        parent::__construct($dsn, $username, $password);
        //
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        //
        //Set the tables of this tabalase
        $this->tables = $this->get_tables();
    }
    
    //Use th informatio scemea to retirn the tables of this database
    function get_tables(){
        //
        //Get the table names
        $results = $this->query(
            "select "
                . "table_name "
            . "from "
                . "information_schema.tables "
            . "where "
                . "table_schema = '$this->dbname'"    
        );
        //
        //Cerat an empty list of tables
        $tables = [];
        //
        //Retrieve the results an insert the indexed tables
        while($result = $results->fetch()){
            //
            //Get the table name
            $tname = $result[0];        
            //
            $tables[$tname] = new table($tname);
        }
        //
        //return teh tables
        return $tables;
    }

    //
    //Returns a checked sql WHEN A QUERY HAS NO PARAMETERS!!!
    public function chk($sql) {
        //
        //A prepared pdo statment throws no exception even if it has errors
        $stmt = parent::prepare($sql);
        //
        //We have to exceited the query for pdo to throw exception if the
        //prepared statement has errors
        //
        try {
            //
            //This is the reason why theis version works only with queries
            //without paramaters
            $stmt->execute();
            //
            $stmt->closeCursor();
            //
        } catch (\Exception $ex) {
            //
            throw new \Exception($ex->getMessage());
        }
        //
        //Return the same sql as the input
        return $sql;
    }

    //
    //Returns a result from a sql stmt.
    public function query($sql) {
        //
        try {
            $result = parent::query($sql);
        } catch (\Exception $e) {
            throw $e;
        }
        //
        return $result;
    }

    //
    //Returns a result from a sql stmt.
    public function prepare($sql, $option = null) {
        //
        //
        //Check the sql
        $stmt = parent::prepare($sql);
        //
        //Check for errors
        if (!$stmt) {
            throw new \Exception($this->error . "<br/>$sql");
        }
        //
        //Return the perapered statement
        return $stmt;
    }

}

//Modellig the mutall_rental database on the development serve
class dbase_dev_mutall_rental extends dbase {

    public function __construct() {
        //
        $username = "mutallde";
        $password = "mutalldatamanagers";
        $dbname = "mutallde_rental";
        //
        parent::__construct($username, $password, $dbname);
    }

}


//Modelling eureka waters databse on the local server
class dbase_local_eureka_waters extends dbase {

    //
    public function __construct() {
        //
        $username = "mutallco";
        $password = "mutall2015";
        $dbname = "eureka_waters";
        //
        parent::__construct($username, $password, $dbname);
    }

}

//Modelling the tables of this datbase. For now, the clas does not have musch
//use, other tan helpint to identify primary and foreign key fieds
class table{
    //
    public $name;
    //
    function __construct($tname){
        $this->name = $tname;
    }
    
    //
    //Returns a condition to select posted table records disregarding those from
    //the current period, since when we begin posting, noting in the current 
    //period is assumed posted
    static function posted($tname){
        //
        //A reading is partially posted if:-
        $partially_posted = 
            //
            //There is a current invoice
            "invoice.invoice is not null "
            //
            //that is linked to the reading    
            . "and $tname.invoice = invoice.invoice ";    
        //
        //A table record is posted if:-
        $posted =
            //    
            //it is linked to an invoice
             "$tname.invoice is not null "
            //
            //...and not partially posted
            ."and not ($partially_posted)";
        //
        //Retrn the posted condtiopn; if if you want unposted, you simply 
        //negate this version
        return $posted;
    }

}

//
//These file are required to support the PHPMailer lass
require_once "../library/PHPMailer-master/src/PHPMailer.php";
require_once "../library/PHPMailer-master/src/Exception.php";
require_once "../library/PHPMailer-master/src/SMTP.php";

//The emailer class in the invoice namespace is an extension of the PHPMailer 
//version
class emailer extends \PHPMailer\PHPMailer\PHPMailer {
    //
    //
    function __construct() {
        //
        //Construct a PHPMailer and yes, throw exceptions
        parent::__construct(true);
        //
        //These are the initiaizations that extends the PHPMailer.
        //
        //Tell PHPMailer to use SMTP
        $this->isSMTP();
        //
        //Enable SMTP debugging
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $this->SMTPDebug = 0;
        //
        //Set the hostname of the mail server
        $this->Host = 'smtp.gmail.com';
        //
        // use
        // $mail->Host = gethostbyname('smtp.gmail.com');
        // if your network does not support SMTP over IPv6
        // 
        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $this->Port = 587;
        //
        //Set the encryption system to use - ssl (deprecated) or tls
        $this->SMTPSecure = 'tls';
        //
        //Whether to use SMTP authentication
        $this->SMTPAuth = true;
        //
        //Username to use for SMTP authentication - use full email address for gmail
        $this->Username = "mutallcompany@gmail.com";
        //
        //Password to use for SMTP authentication
        $this->Password = "mutall01";
        //
        //Set who the message is to be sent from. The system picks the name but ovverides
        //the username with the one specified avove; so, it dpes not matter what you
        //type but it has to be a valid email address
        $this->setFrom('mutallcompany@gmail.com', 'Mutall Investment Co. Ltd');
        //
        //Set an alternative reply-to address
        //$mail->addReplyTo('replyto@example.com', 'First Last');
        //
        //Set who the message is to be sent to. I should be able to see this email from
        //my inbox under sent emails
        //$this->addAddress('mutallcompany@gmail.com', 'Mutall');
        //
        //Set the subject line
        //$this->Subject = 'PHPMailer GMail SMTP test';
        //
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        //$this->msgHTML(file_get_contents('contents.html'), __DIR__);
    }
}

//A page is associated with a database.
abstract class page {
    //
    //A page has a Dbase associated with it.
    public $dbase;
    //
    //The constructor method. class by each instance.
    public function __construct() {
        //
        //Set the database that drives this page
        $this->dbase = $this->get_dbase();
    }
    
    //Returns the database to use for this page. Users must provde their own 
    //version of this method
    abstract function get_dbase();
    
    //Display any sql from teh unrelying database
    function display_sql($sql){
        //
        //Get theh query statement
        $statement = $this->dbase->query($sql);
        //
        //Get the number of fields in the query
        $nfields = $statement->columnCount();
        //
        echo "<table>"; 
        //
        //Output the header
        echo "<tr>";
        //
        for($i=0; $i<$nfields; $i++){
            //
            $column = $statement->getColumnMeta($i);
            //
            echo "<th>".$column['name']."</th>";
        }
        echo "</tr>";
        //
        //Output the body
        while($result = $statement->fetch()){
            //
            echo "<tr>";
            //
            for($i=0; $i<$nfields; $i++){
                echo "<td>".$result[$i]."</td>";
            }
            echo "</tr>";
            
        }
        echo "</table>"; 
    }
     
    //
    //Try bind arg, tries to bind the reference variable, $value, and a matching 
    //property of this page, to the same value as it has. If it has a null then we 
    //look for a value in the super global variables $_GET AND $_POST under the 
    //given name. It returns false if the value is not found.
    static function try_bind_arg(page $page, $name, &$value = null, $validate = FILTER_DEFAULT) {
        //
        //Checking of the value has been passed.if so we set it as the value 
        //of the criteria.
        if (!is_null($value)) {
            //
            //Set the named property of the given page to the value argument.
            $page->$name = $value;
            //    
        } elseif (!empty($_POST[$name])) {
            //
            //Set the named property of the given page to the post global variable
            $value = filter_var($_POST[$name], $validate);
            $page->$name = $value;
            //    
        } elseif (!empty($_GET[$name])) {
            //
            //Set the named property of the given page to the get global variable
            $value = filter_var($_GET[$name], $validate);
            $page->$name = $value;
            //
        } else {
            //
            //We Return a false.
            return false;
        }
        return TRUE;
    }

    //
    //The Bind Arg method throws exception when the argument cannot be set
    static function bind_arg(page $page, $name, &$value = null, $validate = FILTER_DEFAULT) {
        //
        //Test for the try bind arg is it returns a false we through a 
        //new exception.
        if (!page::try_bind_arg($page, $name, $value, $validate = FILTER_DEFAULT)) {
            //
            //Throw a new exception with a massege.
            throw new \Exception("No argument value passed for argument " . $name);
        }
    }
    
    //Execute the requested method from the requested class, or display the home
    //page. This method was evoked from teh given basedir, thus helping us to 
    //track where we came from
    static function index($basedir){
        //
        //Enable error reporting for all types
        error_reporting(E_ALL);
        //
        //Switch on the errors for this production version
        ini_set('display_error', '1');
        //
        //Trap any exceptions
        try{
            //
            //
            if(!empty($_REQUEST['class'])){
                //
                //Set the class name.
                $classname = $_REQUEST['class'];
            }else{
                //
                //Redirect to the home page
                header('location: home.php');
                exit();
            }
            //
            //Add the namespace to the method
            $namespaced_class = __NAMESPACE__."\\".$classname;
            //
            //Make a instance of the class.
            $class = new $namespaced_class();
            //
            //Pass the base directory to the class object; this is important for
            //accessing local resources. The email->MsgHTML uses this directoty to
            //resolve relative addresses
            $class->basedir = $basedir;
            //
            //Set the method fom the global post or get variables
            if (!empty($_REQUEST['method'])){
                //
                $method = $_REQUEST['method'];
            }else{
               //
               //There must be a method.
               throw new \Exception('Method not found');
            }
            //
            //Determine if styling using a css is required for the output or not
            if (!empty($_REQUEST['css'])){
                //
                //Get teh CSS file
                $cssfile = $_REQUEST['css'];
                //
                //Use the file for styling output
                ?>

                <!-- 
                The html section is needed to support CSS from an external file -->
                <html>
                    <head>

                        <!--Keep title short and unique, so that this page can be identified
                        even when there are many open cases-->
                        <title>Invoice</title>

                        <link 
                            id="invoice.css" 
                            rel="stylesheet" 
                            type="text/css" 
                            href='<?php echo $cssfile;?>'>

                    </head>

                    <body>
                        <?php
                        //Call the desired method
                        $class->$method();
                        ?>

                    </body>
                </html>
                <?php
            //
            //Styling is not required
            }else{
                //
                //Call the raw method.
                $class->$method();
            }
        }
        catch(Exception $ex){
            echo $ex->getMessage();
        }
    }

}

//
abstract class invoice extends page {
    //
    //An invoice can present data before or after it is posted. A poster 
    //invoice presents data before it is is posted; a report presents it after
    //posting
    public $posted;
    //
    //The following 2 fields are set during the display or emailing of an 
    //invoice.
    //
    //1. The level of detail to show for this invoic'es item --detailed or summary
    public  $level; 
    //
    //2. The layout of this invoice
    public $layout;
    //
    //Constants for converting credit and debit balances to amounts
    //
    //Credit adjustments decrease the client's balance
    const credit = -1;
    //
    //The opposite is true for debits
    const debit = 1;
    
    //An invoice is characterised by:-
    //- Whether we are reporting data before or after it is posted? This in turn 
    //helps us to pick which sql of an item to use, detailed_poster or detailed 
    //report. If before, then the $posted is set to false; otherwise it is set 
    //to true.
    //-A selection criteria which limits the size of the invoice
    function __construct($posted, $criteria=null) {
        //
        $this->posted = $posted;
        //
        //The criterial for filtering data is optional
        page::try_bind_arg($this, 'criteria', $criteria);
        //
        //Construct the parent before anything else for the purpose of 
        //using method and properties in the construction of the child.
        parent::__construct();
        //
        //Each invoice has a record; set it here using an abstract function 
        //because we cannot instantiate a record, which is abstract.
        $this->record = $this->get_record();
    }
    
    //Returns the last invoice in invoice in theh database; its the one with 
    //the highest that is not teh same as that of the current invoice
    function last_invoice(){
        //
        return $this->dbase->chk(
            "select "
                //
                //Show all the fields of the invoice
                . "invoice.* "
            . "from "
                . "invoice "
                //
                //Match this invoice to the one with the most recent date
                . "inner join ({$this->last_date()}) as last_date on "
                    ."last_date.client = invoice.client "
                    ."and last_date.timestamp = invoice.timestamp "
        );
    }
    
    //Returns the most recent invoice date. It is one with the highest 
    //time (excluding future date).
    function last_date(){
        //
        return $this->dbase->chk(
            "select "
                //
                //The most recet timestamp...
                . "max(invoice.timestamp) as timestamp, "
                //
                //...of a given client. Invoices are client (not 
                //connection) based
                ." invoice.client "
            . "from "
                . "invoice "
            . "where "
                //Be careful here!! This may just be the invoice you have 
                //inserted during a posting!!!!! Exclude it. We assume that there
                //are no invoices after this one
                . "not (invoice.timestamp='{$this->record->invoice->timestamp}') "
                //
                //Ignore future invoices. The invoice's date separates the
                //future from the present    
            
                //
            ."group by "
                . "invoice.client"            
        );
    }
    
    //Initialize this invoice with data from multiple sources, including
    //the arguements
    function initialize($layout=null, $level=null){
        //
        //Bind the arguments to invoice properties
        page::bind_arg($this, 'layout', $layout);
        page::bind_arg($this, 'level', $level);
        //
        //Change the layout name to the equivalent object
        //Let $obj be the desired layout object, complete with namespacing
        $obj = __NAMESPACE__."\\".$layout; 
        //
        $this->layout = new $obj();
        //
        //Prepare statements for all the items
        foreach($this->record->items as $item){
            //
            //Prepare detailed and summary statements of the item to provide 
            //data for a parametrized client (or invoice) display. 
            $item->prepare_statements();
        }
         //
        //Override the fact that prev_value and curr_value in the detailed statement
        //of water item are not money fields
        $this->record->items['water']->statements['detailed']->fields['prev_value']->is_money = false;
        $this->record->items['water']->statements['detailed']->fields['curr_value']->is_money = false;
        //
        //The units of water consumption is not (formated as) money
        $this->record->items['water']->statements['detailed']->fields['units']->is_money = false;
    }
    
    //Display a complete invoice, guided by the following arguments:-
    //- The general layout of the invoice, tabular or label
    //- The index (of the detail level) to the item statemenet to use for 
    //reporting, i.e., detailed, summary, gross, etc.
    function display($layout=null, $level=null) {//invoice
        //
        //Initialize this invoice with data from multiple sources, including the
        //given arguments
        $this->initialize($layout, $level);
        //
        //Retrieve the data that drives the display
        $results = $this->query();
        //
        //Now display the data, modelled along a tabular layout
        //
        //Open the main report tag, e.g., <table> for a tabular layout. For 
        //thermal print, this initializesa new property -- json 
        $this->layout->open_table($this);
        //
        //Show the header of this report; this is relevant for tabular layouts 
        //only.
        $this->layout->display_header();
        //
        //Step thorugh the driver table records and display each one of them.
        while ($result = $results->fetch()) {
            //
            //Populate this invoice's record with data to be displayed
            $this->record->populate($result);
            //
            //Display this invoice's record in the required (invoice) layout and 
            //item detail
            $this->record->display();
        }
        //
        //Close the main report tag, e.g., </table>. For a jsoon layout this
        //echos the json property
        $this->layout->close_table();
    }
    
    //Send this invoice as emails of to clients.
    function email(layout $layout=null, $level=null){
        //
        //Initialize this invoice with data from multiple sources, including the
        //given arguments
        $this->initialize($layout, $level);
        //
        //Create the emailer object, an extension of PHPMailer 
        $this->emailer = new emailer_mutall();
        //
        //Retrieve the data that drives the email list
        $results = $this->query();
        //
        //Loop through all the results, outputting each one of them as an email
        while ($result = $results->fetch()) {
            //
            //Populate the invoice record with the data to email
            $this->record->populate($result);
            //
            //Email the record
            $this->record->email();
        }
    }
    
    
    //Query this invoice
    function query(){
    //        
        //Retrieve the data that drives the display
        //
        //Get the sql that drives this invoice.
        $sql = $this->get_driver_sql();
        //
        //Query the database for results.
        $results = $this->dbase->query($sql);
        //
        return $results;
    }
   
    
    //Returns the structure of the record that is used for driving an invoice.
    //Different websites must implement their own versions of a record. For 
    //mutall needs electricity and water items; eureka_waters need water only. 
    //Sidai has a different structure, etc. Mutall has impleented the 
    //record_mutall locally.
    abstract function get_record();
   
    //Returns the sql that drives this invoice report
    abstract function get_driver_sql();//invoice
    
    //Returns the name of the css file for styling outputs from this invoice
    //such as the poster display or email
    //The file:-
    //- can be specfied be the user from the browser through a request
    //-is named invoice.css in the website directory
    function get_cssfile(){
        //
        if (!empty($_REQUEST['css'])){
            return $_REQUEST['css'];
        }else{
            return 'invoice.css';
        }
    }
}

//A record models a single row in an invoice page that is laid out in a
//tabular fashion. 
abstract class record {
    //
    //The invoice. Either poster or report will be the parent at any creation.
    public $invoice;
    //
    //A record has one or may items. The item is the specific report element.
    public $items = [];
    
    public function __construct(invoice $invoice) {
        //
        //Set the invoice.
        $this->invoice = $invoice;
        //
        //Defined the items in the order of display. Set the initial items, 
        //i.e., invoice and opening balances
        $this->items = [
            'invoice' => new item_invoice($this),
            'opening_balance' => new item_opening_balance($this)
            ];
        //
        //Set the user defined items
        foreach($this->get_udf_items($this) as $key=>$item){
            $this->items[$key]=$item;
        }
        //
        //Set the items needed for balance calculations
        foreach([
                    'services' => new item_service($this),
                    'payment' => new item_payment($this),
                    'adjustment' => new item_adjustment($this)
                ] as $key=>$item
                ){
            //
            $this->items[$key]=$item;
        }
        //
        //Insert closing balance as tahe lst item
        $this->items['closing_balance'] = new item_closing_balance($this);
    }
   
    //Set teh user defiend items
    abstract function get_udf_items();
    
    //Fill the record items with data
    function populate($result){
        //
        $this->result = $result;
        //
        //Set the primarykey of the table row for further use. The 
        //client/invoice parameter in the items sql is bounf from this key
        $this->invoice->primarykey = $result['primarykey'];
        //
        //Fill the record with data, item by item
        foreach($this->items as $item){
            //
            //Attend to both the detailed and summary statements of an item 
            foreach(['detailed', 'summary'] as $level){
                //
                //Get the mutall statetement
                $statement = $item->statements[$level];
                //
                //Excute the statement (with a bound primarykey)
                $statement->execute();
                //
                //Fetch all the statement's data 
                $results = $statement->stmt->fetchAll();
                //
                //Save the results internally for further refereces 
                $statement->results = $results;
            }        
        }
        //
        return;
    }
    
    //Returns the arrears amount for this record for use in preparing clients'
    //announcements
    function get_arrears(){
        //
        //The arrears amount is defiend as the sum of teh following summary 
        //item names
        $names = [
            'opening_balance',
            'payment',
            'credit',
            'debit'
        ];
        //
        //Start with no sum
        $sum=0;
        //
        //Sum all the named (arrears) items
        foreach($names as $name){
            //
            //Only available data is considered
            if (isset($this->items[$name]->statements['summary']->results[0][0])){
                //
                //Do teh acculumation
                $sum = $sum + $this->items[$name]->statements['summary']->results[0][0];
            }
        }
        //
        return $sum;
    }

    //
    //Returns the type of document and its reference number for reporting as 
    //as (a) an email subject and  (b) invoice header -- if there is posted 
    //data.
    function try_ref(&$ref){
        //
        //Test if there is a valid reference
        if (isset($this->items['rent']->statements['detailed']->results[0])){
            //
            //Get the current rent factor, 1, 3 or null, using the first agreement entry
            $factor = $this->items['rent']->statements['detailed']->results[0]['factor'];
            //
            $type = is_null($factor) ? "STATEMENT" : "INVOICE";
            //
            //Get the first recrord of the detailed satement of this record's invoice item 
            $rec = $this->items['invoice']->statements['detailed']->results[0];
            //
            //Return the two pieces of of data: document type and its reference number
            $ref['type'] = $type;
            $ref['code']= "{$rec['id']}-{$rec['year']}-{$rec['month']}";
            //
            return true;    
        }else{
            //There is no reference
            return false;
        }
        
    }
    
    //Display this record according to the underlying invoice's requests, e.g., 
    //level of detail, form layout, etc., all of which are specified during 
    //request for invoice display
    function display(){//record
        //
        //Get the underlying layout (from this record's invoce)
        $layout = $this->invoice->layout;
        //
        //Open the record (tag) plus all the attributes of the invoice. For 
        //thermal princ, this initializes a new json "record" of this record
        $layout->open_record($this);
        //
        //Display the record; this can be a simple tabular layout, label layout 
        //or a much more complex layout, e.g., layout_mutall. Note that a layout 
        //can display many types of objects -- henc the more descriptive name,
        //layout::dislay_record rather than simply layout::display(). 
        $layout->display_record();
        //
        //Close the record (tag) -- depending on the layout. For thermal print
        //layout simply pushes a json record to the json "table"
        $layout->close_record();
    }
    
    //Compile the complete email; then send it (using PhpMailer)
    function email(){
        //
        //Get the emailer
        $emailer = $this->invoice->emailer;
        //
        //Initialize the address list
        $emailer->ClearAddresses();
        //
        //The email is sent to the client's email address. This means that the 
        //record's driver data must return an email.
        $address = $this->result['email'];
        $emailer->addAddress($address);
        //
        //The subject is invoice/statement for the current period -- the same bit
        //that appears in the invoice header
        $ref = [];
        $emailer->Subject = $this->try_ref($ref) 
            ?  $ref['type']." ".$ref['code']
            :"No posted data found";
        //
        //The message comes from the display of the record. Collect the message
        //data
        //
        //Start output buffering
        ob_start();
        //
        //Display the invoice
        $this->display();
        //
        //Get the output and stop buffering
        $info = ob_get_clean();
        //
        //Compile the message as a complete HTML report
        $message = "
        <html>
            <head>
              <style>"
             //
             //The style comes from the mutall_rental.css. This is a shared
             //resource,   
            . file_get_contents($this->invoice->get_cssfile())
            //
            ."</style>
            </head>
            <body>
                $info
            </body>
        </html>
        ";
        //
        //Initialize the message body by converting the given html into plain text 
        //alternative body and referenced images to embedded versions. The 
        //base directory used for resolving relative paths to images is...
        $emailer->msgHTML($message, $this->invoice->basedir);
        //
        //
        //send the message, check for errors
        if (!$emailer->send()) {
            echo "Mailer Error: " . $emailer->ErrorInfo;
        } else {
            echo "$emailer->Subject sent to $address<br/>";
        }
    }
}

//A poster invoice is a client bill that uses new/current data, i.e., one that 
//has not been reporetd on before, a.k.a "posted". The driver table of a poster 
//invoice is the client table.
//Poster is partaiily defined because its users have to implement the following 
//methods:-
//a) get_dbase(), to point to the desired database; there is no default
//b) get_record(), to support customization to different application areas, e.g., 
//  payroll, rental, etc.
abstract class poster extends invoice {
    //
    //Indicates if the database has future data or not; if it has  we must 
    //filter it out from the current bill. The alternative would be to physically
    //oot it out and put it back when it becoemes current. This flag allows us 
    //to roll back time .
    public $has_future;
    //
    //The future cuotff date. All current data must be dated before the future.
    //This boolean field is used for excluding future data from current invoice
    //computatations without having to remove such data from the database 
    public $future_date;
    //
    //The invoice timestanp allows us distinguish current_invoice() from 
    //last_invoice() DURING POSTING when a partialy completed invoice 
    //is available. This is critical; without it closing balances, among other
    //calculaions are impposible to compute. 
    //It is also used for identifying an invoice.
    public $timestamp;
    //
    public function __construct($future_date=null, $timestamp=null) {
        //
        //The filter for excluding future data is optional
        $this->has_future = 
            page::try_bind_arg($this, 'future_date', $future_date) ? true:false;
        //
        //The invoice's timestamp is optional. This feature is provided so that
        //users can post invoices ahead of time, For instance, you may want to
        //release next month's invoices today -- the 25th of may -- which may
        //be the practice with some organizations. It is also important for 
        //testing purposes
        if (!(page::try_bind_arg($this, 'timestamp', $timestamp))){
            //
            //The default is the current time stamp
            $this->timestamp = date('Y-m-d H:i:s'); 
        }
        //
        //A poster invoice presents data that has not been posted. 
        //This is important for selecting the driver sql 
        parent::__construct(false);
    }
    
    //Returns the sql that drives the poster invoice. It is based  on 
    //clients for whom there is a water connection
    function get_driver_sql() {//poster
        //
        //Consider only non terminated water connections.Te invoice timestamp is
        //the current date marker
        $terminated = 
            "(wconnection.end_date is not null "
            . "and wconnection.end_date<'{$this->record->invoice->timestamp}')";
        //
        //The filtering criteria
        $criteria = isset($this->criteria) ? "and $this->criteria": "";
        //
        return $this->dbase->chk(
            //
            //Clients with multiple mtere are returned only once    
            "select distinct "
                //
                //The driver expects a primary key field
                . "client.client as primarykey, "
                //
                //Needed for attributing record tagss to implement Camilus 
                //search engine
                . "client.code "
            //    
            . "from "
                . "client "
                //
                //Filter to see only those clients with water connections 
                . "inner join wconnection "
                    . "on wconnection.client = client.client "
                //
                //To allow access to the details of a client, bring in the user
                ."inner join user on client.user = user.user " 
            //
            . "where "
                //
                //Only active water connections are considered
                . "not $terminated "
                //
                //Add the filtering criteria
                ."$criteria "
            //
            //The ordering s important for Vamilus navigation method    
            . "order by user.name"     
        );
    }
    
    
    //Post the current invoice's items to the database. We require to wrap
    //this process as a transation for 2 reasons:-
    //a) the results of poster queries get affected by what is partially posted, 
    //so that the result that is posted may be very different from what was 
    //available before the posting.
    //b) if any posting fails, you want to roll back the trasaction
    //
    //This transaction business is not the solution to the problemn cited in (a)
    //So, I have discontinued it -- and introdudes a controlled time stamp for
    //invoives
    function post() {
        //
        //Aler the user if you need to unpost future invoces, relative
        //to the current one????
        //
        //Post all the items in the natural order
        foreach ($this->record->items as $item) {
            //
            //Post this item
            $item->post();
        }
        //
        //Done
        echo "Ok";
    }

    //Undo the postings of this invoice. For binary items, this means also clearing
    //all postings beyund the current date, to make the psoting consistent. This
    //is why users need to confirm the request as it may invoile undoing a lot
    //of posted work
    function unpost() {
        //
        //Reverse the order in which items are posted, so that invoice is 
        //unposted last, because storage items should be deleted before the 
        //invoice since they are all linked to invoice
        $items = array_reverse($this->record->items);
        //
        //Unpost all the reversed items
        foreach ($items as $item) {
            //
            $item->unpost();
        }
        //
        //Also consider removing all the binary items posted after this
        //invoices timespamp. 
        //
        //Indicate when everything has run as expected
        echo "Ok";
    }

    
}

//A report invoice is a type of invoice that reports posted items.
//The driver table for this is the invoice table.
abstract class report extends invoice {

    //
    //The properties of  report are:-
    //The criteria for section of the reporting data.
    public $criteria;

    //
    //The constructor's critaria and order by clauses are optional.
    function __construct() {
        //
        //A poster invoice presents data that has been posted. 
        //This is important for selecting the driver sql 
        parent::__construct(true);
    }
    
    //Returns an sql, based on invoice,  that drives reports. A driver is typified
    //by a primary key that is used as a parameter in display queries
    function get_driver_sql() {//report
        //
        return $this->dbase->chk(
            "select "
                //
                //The key that is unque to everu driver is commonly known as 
                //primary key
                . "invoice.invoice as primarykey, "
                //
                //The client name is required for labelling display records to 
                //support Camilus work
                . "client.code "
            . "from "
                //
                //The table driving this process 
                . "invoice "
                //
                //The following joins are added to support search criteria (used)
                //in the where clause
                . "inner join client on invoice.client = client.client "
                . "inner join vendor on client.vendor = vendor.vendor "
            //
            //Add the where clause -- if necssary    
            .(isset($this->criteria) ? "where $this->criteria ": "") 
            .(isset($this->order_by) ? "order by $this->order_by": "")
        );
    }

}

//
abstract class report_statement extends report {
    //
    //The constructor method.
    function __construct() {
        //
        //A statement presents data that has been posted
        parent::__construct(true);
    }

}

//Reports a summarised version of the normal report in a tabular form. Users 
//implement thier own version by defining the get_record() abstract method
abstract class report_schedule extends report {
    //
    function __construct() {
        //
        parent::__construct();
    }

}

abstract class report_sms extends report {

    //
    //The constructor method.
    function __construct() {
        //
        //Construct the parent.
        parent::__construct();
    }

}

//Modelling a custom statement for driving an invoice
class statement{
    //
    //The results of executing this sttaement, as a dually indexed array. This 
    //is populated during display or emailing of an invoice
    public $results;
    //
    //The PDO::PDOstatement that is used for carrying out PDO functions. It is 
    //needed for pulating a record with fetched data.
    public $stmt;
    //
    function __construct(item $item, $sql){
        //
        $this->item = $item;
        //
        //Retrieve the item's database
        $dbase = $item->record->invoice->dbase;
        //
        //Prepare the statement
        $this->stmt = $dbase->prepare($sql);
        //
        //Collects the fields, as stdclasses, from executing this items sql
        $this->fields = $this->get_mutall_fields();
        //
        //Bind the driver parameer to the primary key field
        if (!$this->stmt->bindParam(":driver", $this->record->invoice->primarykey)){
           throw new \Exception($this->stmt->errorInfo()[2]);
        }
    }
    
    //Returns standard class fields for this item by  executing its sql
    function get_mutall_fields() {
        //
        //Excute the stmt to obtain the field count. Use a dummy driver
        $this->stmt->execute([':driver' => 'dummy']);
        //
        //Init the field collection.
        $fields = [];
        //
        //Get the number of column in the PDO statement.
        $column_count = $this->stmt->columnCount();
        //
        //Loop through the columns of the result.
        foreach (range(0, $column_count - 1) as $index) {
            //
            $column = $this->stmt->getColumnMeta($index);
            //
            //Get teh field name
            $fname = $column['name'];
            //
            //Get the field type
            $type = $column['native_type'];
            //
            //Get the length of the field.
            $len = $column['len'];
            //
            //Create a mutall field
            $mutall_field = new field($this, $fname, $type, $len);
            //
            //Use the indexed arrey to hold the mutall field
            $fields[$fname] = $mutall_field;
        }
        //
        //Return the field.
        return $fields;
    }

    
    //Display this statement (for tabular or label layouts) depending on whether 
    //its result yields a single record, multiple records or none. Single 
    //records are shown in a labeled fashion; multiple records as a table. 
    //table
    function display(){//statement    
        //
        //Count the number of records in this statement's results
        $norecs = count($this->results);
        //
        //If the results is empty, do nothing
        if ($norecs==0){
            //
            //Display nothing
            return;
        }
        //If there is only one record, then use then... 
        elseif ($norecs == 1){
            //
            //...use the label format to display the result...
            $local_layout = new layout_label();
        }else{
            //
            //..otherwise (for multiple records) use the tabular layout
            $local_layout = new layout_tabular();
        }
        //
        //Open the item as a block with 1% top magin (see the invoice.css) 
        echo "<div class='item'>";
        //
        //Output the item's name as a label -- if the global layout is indeed
        //label. Global means the layout specifeid on the invoice
        $global_layout = $this->item->record->invoice->layout;
        //
        if ($global_layout->show_label){
            //
            //Show the item name as a block with color green (see invoice.css)
            echo "<p class='name'>"
            . $this->item->name
            . "</p>";
        }
        //
        //Use the layout to show the results of the selected statement
        $local_layout->display_statement($this);
        //
        //Close the item tag
        echo "</div>";
    }
    
    //
    //Executing the statement
    function execute(){
        //
        //Get the primary key of client or invoice driver
        $primarykey = $this->item->record->invoice->primarykey;
        //
        //Execute the statement
        $result = $this->stmt->execute([":driver"=>$primarykey]);
        //
        //Test for errors
        if (!$result){
            throw new \Exception($this->stmt->errorInfo()[2]);
        }
    }
    
    //Returns all the fields to display, i.e., thoses not marked as hidden.
    //Primary and foreign key fields are marked as hidden
    function get_show_fields(){
        //
        return  array_filter($this->fields, function($field){
            //
            //Filter out the no-show cases.
            return $field->is_shown;
        });
    }
    
    
}

//A field is a key value pair. A name and a Value.
class field {
    //
    //The properties of a field are.
    //The field name
    public $name;
    //
    //The current field value.
    public $value;
    //
    //The parent item. The item is field's home.
    public $item;
    //
    //A field has a data type, e.g., string, real or boolean
    public $data_type;
    
    //The data type constants
    const int  = "LONGLONG";
    const str = "VAR_STRING";
    const double = "DOUBLE";
    const date = "DATE";
    
    //Set this to true if the field is to be displayed. 
    public $is_shown;
    
    //By default all fields of double type are considerd as money fields. 
    //The user can ovrride this fact when this is not the case
    public $is_money;
    
    //The constructor method parameters are obtained a query field metadata.
    function __construct($stmt, $name, $data_type, $len) {
        //
        $this->name = $name;
        $this->data_type = $data_type;
        $this->length = $len;
        $this->stmt = $stmt;
        //
        //By default all fields will be displayed. Primary and foreign key 
        //fields will not be shown.
        $this->is_shown = $this->get_is_shown();
        //
        $this->style = $this->get_style();
        //
        //By default all fields of double type are considerd as money fields. 
        //The usr can ovrride this fact when this is not the case
        $this->is_money = $data_type==self::double ? true: false;
    }
    
    //Retuens the style to show for a field
    function get_style(){
        //
        //By default, there is no styling
        $style = "";
        //
        //Doubles are right aligned
        if ($this->data_type ===self::double){
            //
            $style = "class = 'double'";    
        }
        //
        return $style;
        
    }
    
    //Returns true if the field is to be shonw; false otherwise. Attributes are
    //shown; primary and foreign key fields are not.
    function get_is_shown(){
        //
        //Get the containing database
        $dbase = $this->stmt->item->record->invoice->dbase;
        //
        //If teh field name matce sthe name of an exusting table, then, by 
        //mutall naming rule, it is not an attribute.
        if (key_exists($this->name, $dbase->tables)){
            //
            //The key is either primary or foreign. Do not show
            return false;
            
        }//
        //Remove teh posted field
        elseif($this->name=='posted'){
            return false;
        }
        else{
            //The key is an attribute. Show it
            return true;
        }
    }
    
    //Format the given value for this field
    function format($value){
        //
        //Start with the original (unformated) value
        $fvalue = $value;
        //
        //Null values are not formated
        if (empty($value)){
            //do nothing
        }
        //
        //Format all moneys no decimal point and a comma thousand 
        //separator.
        elseif($this->is_money){
            $fvalue = number_format($fvalue, 0, ".", ",");
        }
        //
        //Other real numbers, e.g., water meter readings are formated with 2 
        //places of decimal and no comma separator
        elseif($this->data_type == self::double ){
            $fvalue = number_format($fvalue, 2, ".", "");
        }
        //
        return $fvalue;
    }
}
