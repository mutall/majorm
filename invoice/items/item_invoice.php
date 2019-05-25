<?php
namespace invoice;

//The invoice item class supports management of clients across time. This class 
//is a binary item where client is the driver and the invoice instance is the 
//storage entity. It is:- 
//a) the first item shown in an invoice report
//b) the item associated with the storage that drives reporting system
//shown in an invoice report
//c) the integrator of all derived storages, e.g., water consumption, charges, 
//etc. 
class item_invoice extends item_binary{
    //
    public function __construct($record) {
        //
            parent::__construct($record, "client", "invoice");
        //
        //Invoice is not used for calculating the closing alance; its use in
        //display report is purely aesthetic, 
        $this->aesthetic = true;
    }    
 
    //Returns the summary sql of an invoice item. The sql has only one user 
    //message field named value (as expected in all summaries). For aesthetic 
    //items, such as invoice, the value is selected to be any field expression
    //that does not have multiple values for each client. In the case of an,
    //invoice, its the ref
    function summary($parametrized = true) {
        //
        //Get the detailed sql, depending on whether this invoice is 
        //for posting or for repoerting
        $posted = $this->record->invoice->posted ? "report": "poster";
        //
        //The full name of the sql
        $fname = "detailed_$posted";
        //
        //Execute it and group by the client_name key
        return $this->chk(
            //    
            "select "
                //
                //The summary field, refr,  is called value; by default it is 
                //based on the amount column of the detailed sql
                . "invoice.ref as value, "
                //
                //The client is needed for linkin teh summary to other data
                . "invoice.client "
            //    
            . "from "
                //
                . "({$this->$fname($parametrized)}) as invoice "
            . "group by "
                //
                //This is needed for reporting        
                . "invoice.ref, "
                //
                //Just in case client names ar nort unique        
                . "invoice.client "        
        );
    }
    
    //Returns an sql for reporting current invoice. An invoice is a posted 
    //client.    
    function detailed_poster($parametrized=true) {
        //
        //The code of a client is either explicitly given or it is the same as 
        //the primaru key
        $client_code = "if(client.code is null, client.client, client.code)";  
        //
        //Formulate the detailed sql
        $sql = $this->chk(
            "select "
                //Key messages from an invoice:-
                //
                //The full name of (user) vendor
                ."user_vendor.name as vendor_name, "
                //
                //Full name of (user) client
                ."user_client.name as client_name, "
                //
                //A short client id used for bank transaction. 
                ."$client_code as code, " 
                //
                //The current date for this invoice.
                . "'{$this->record->invoice->timestamp}' as timestamp, "
                //
                //Design a reference code for the invoice -- if there is no 
                //client id
                . "concat($client_code, '-', '{$this->record->invoice->timestamp}')  as ref, "
                //
                //Add zone as a connection independent descriptor of a client
                //We can deduce the connection's zone from the the zones 
                //demarcation, geographically
                . "zone.name as zone_name, "
                //
                //The client id is needd for formulating invoice item summary
                ."client.client "
            ."from "
                //
                //The driver for this the client table
                ."client "
                //
                //Client is a user
                ."inner join user as user_client on "
                        . "client.user = user_client.user "
                //
                //Brinin in the vednor        
                . "inner join vendor on "
                    . "client.vendor = vendor.vendor "
                //
                //Vendor is another user
                ."inner join user as user_vendor on "
                        . "vendor.user = user_vendor.user "
                //
                //We need access to  the zone; it may not be avaiable
                ."left join zone on "
                    . "client.zone = zone.zone "        
            ."where "
                //
                //Add the parametrized client constraint, if necessary. 
                .($parametrized ? "client.client = :driver ": "true ")
                
        );
        //
        return $sql;
    }
    
    //Post the invoice, so that the current invoice option becomes available
    //to other binary items
    function post() {
        //
        $this->query(
            "insert into "
                . "invoice ("
                    //
                    //The message fields
                    ."ref, "
                    //
                    //We need to take charge of the insertion timestamp so 
                    //that teh current invoice does not become the last one during
                    //posrting
                    . "timestamp, "
                    //
                    //All storages for binary iems link to client
                    ."client, "
                    //
                    //Keeping track of the last invoice. Important for historical 
                    //computations
                    . "invoice_1 "
                .   ")"
                ."("
                . "select "
                        . "poster.ref, "
                        //
                        //Fix the timstamp, to allow us distingush the current
                        //invoice from the last one during posting
                        ."'{$this->record->invoice->timestamp}', "
                        //
                        . "poster.client, "
                        //
                        //Track the last invoice        
                        . "last_invoice.invoice "
                    . "from "
                        . "({$this->poster()}) as poster "
                        //
                        //Add the last invoice, if any
                        . "left join ({$this->record->invoice->last_invoice()}) as last_invoice on "
                            . "poster.client = last_invoice.client "
                . ")"
                . "on duplicate key update "
                    . "ref = values(ref), "
                    . "timestamp = '{$this->record->invoice->timestamp}'"
            );
        
    }

     //Unposting of invoices removes the last posted records except
     //those that are linked yo initial balances.
    function unpost() {
        //
        $this->query(
            "delete "
                //
                //We are all the invoices
                . "invoice.* "
            . "from "
                //
                ."invoice "
                
                //Bring in the last invoices
                ."inner join ({$this->record->invoice->last_invoice()}) as last_invoice on "
                . "last_invoice.invoice = invoice.invoice "
                //
                //Bring in the closing balaces associated with the last nvoicve
                . "inner join closing_balance on "
                    . "closing_balance.invoice = last_invoice.invoice "
            //
            //Exclude invoices linked to the initial balances            
            . "where "
                . "not (closing_balance.initial)"
        );       
    }
    
    
}


