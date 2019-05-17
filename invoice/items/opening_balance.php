<?php
namespace invoice;
//
//This class supports management of the opening balances. It is a unary item
//because it does not produce new records -- unlike closing balances
class item_opening_balance extends item_unary {
    //
    public function __construct($record) {
        //
        //The driver of the opening balance is theh client
        parent::__construct($record, "client");
    }
    
    //Returns the sql for reporting opening balances for the current date. There 
    //will be as many opening balances as there are last closing balances. The last
    //closung balances are the opening balances of th last invoice.
    function detailed_poster($parametrized=true){
        //
        return $this->chk(
            "select "
                //
                //Client messages
                //
                //Reference date for the balance ia teh same as the invove timestamp
                ."'{$this->record->invoice->timestamp}' as date, "
                //
                //The actual amount
                ."closing_balance.amount, "
                 //
                //The client is needed for calculating closing balance.
                ."closing_balance.client "
            ."from "
                //
                //Bring in the last closing balancee
                ."({$this->last_closing_balance()}) as closing_balance "
            //     
            ."where "
                //Add the client parametrized constraint
                .($parametrized ? "closing_balance.client =:driver ": "true ")
            );
    }
    
    //The detailed report sql of an opening balance item is the closing balance
    //of the previous invoice
    function detailed_report($parametrized=true){
        //
        return $this->chk(
            "select "
                //
                //Select all the fields of the (previous) closing balance 
                . "closing_balance.amount "    
            ."from "
                //
                //Bring in the invoice allluded to by the incoming parameter
                ."invoice "
                //
                //Bring in the previous invoice. If there is none, the query 
                //returns nothing
                ."inner join invoice as invoice_1 on "
                    ."invoice.invoice_1 = invoice_1.invoice "
                //
                //Bring in the previous balance
                . "inner join closing_balance on "
                    . "closing_balance.invoice = invoice_1.invoice "
            . "where "
                //Apply the given paramater -- if necessary
                .($parametrized ? "invoice.invoice =:driver ": "true ")
     
        );           
    }
    
    
    function summarised_report($parametrized = true) {
        //
        //Collect, client by client, the amounts used for constructin the 
        //opening balances.
        $storage = $this->chk(
            $this->get_local_sql('balance_initial',$parametrized)
            .' union all '    
            .$this->get_local_sql('closing_balance',$parametrized)
        );        
        //
        //Do the opening balance summary
        return $this->chk(
            //    
            "select "
                //Select all (*) the fields from the storage. We will have to figure
                //out how to throw out primary and foreign key fields from the
                //user reports
                . "storage.client, "
                . "sum(storage.amount) as amount "
            . "from "
            //
            //The storage table drives the reporting. Use alias to avoid 'Not 
            //unique table alia' error
                . "($storage) as storage "
                
        ."group by storage.client"        
        );
    }

    //Returns the client and amount componennts for summarising purposes.
    function get_local_sql($storage,$parametrized = true) {
        //
        return $this->chk(
            //    
            "select "
                //Select all (*) the fields from the storage. We will have to figure
                //out how to throw out primary and foreign key fields from the
                //user reports
                . "invoice.client, "
                . "$storage.amount "
            . "from "
            //
            //The storage table drives the reporting. Use alias to avoid 'Not 
            //unique table alia' error
                . "$storage "
                //
                //We need access to teh client, for grouping purposes
                . "inner join invoice on invoice.client = $storage.invoice "
            . "where "
            //
            //Add the invoice constraint, if needed.
            . ($parametrized ? "$storage.invoice =:driver " : "true ") 
                
        );
    }
    
    //The last closung balances are the opening balances of the last invoice.
    function last_closing_balance(){
        return $this->chk(
             "select "
                //The fields to export are:-
                . "closing_balance.amount, "
                . "last_invoice.client "
            . "from "
                //
                //The last invoice drives this provess
                . "({$this->record->invoice->last_invoice()}) as last_invoice "
                //
                //Its opening balance is the last closing balance
                . "inner join closing_balance on "
                    . "closing_balance.invoice = last_invoice.invoice"   
        );        
    }
    
}


