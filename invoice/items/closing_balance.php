<?php
namespace invoice;
//
//Closing balance is the sum of all non-aesthetic item amounts in the current
//period. Client and closing balances are the only aesthetic items known so far.
//
//Closing balance is a binary item because posting it involves 2 entiies:-
//a) client as the driver and 
//b) closing_balance as the storage table for the posted items
class item_closing_balance extends item_binary {
    //
    //Construct a closing balance using a parent record 
    public function __construct($record) {
        //
        parent::__construct($record, "client", "closing_balance");
        //
        //Closing balance is an aesthetic item; it is not used for calculating 
        //itself. In a report, it is simply aesthetic.
        $this->aesthetic = true;
    }
    
    //Returns the sql for computing the closing balance for the current period. 
    //It is calculated as the sum of all non-aesthetic item amounts. Invoice and 
    //closing balance items are not used; they are said to be known aesthect, i.e.,
    //their values in a report is purely for beauty.
    function detailed_poster($parametrized=true){
        //
        return $this->chk(
            "SELECT "
                //
                //The messages to communicate to the user
                //
                //The summary amount
                ."summary.amount, "
                //
                //Keys for supporting postage all based on the storrge entity
                //
                //The client field is needed for posting closing balances
                ."client.client "
            ."FROM "
                //The drivver for closing balance summation is the client
                ."client "
                //
                //Extend the client the summary for the closing balance. It is 
                //expected that every client will have a closing balance, and 
                //those that don't will be filtered out by the inner join 
                . "inner join ({$this->summation($parametrized)}) as summary on "
                    . "summary.client = client.client "
            . "where "
                //
                //Apply the client parametrized client constraint -- when displaying
                //generated data
                .($parametrized ? "client.client = :driver ": "true ")
        );
    }
    
    //Returns the Sql for computing the closing balance for this period, as the
    //sum of all non-aesthetic item amounts. Let nbitem be an non-aesthetic item
    function summation($parametrized=true){
        //
        return $this->chk(
            "SELECT "
                //
                //Sum all the amounts of the union of all non-aesthetic
                //items:- 
                ."sum(nbitem.amount) as amount, "
                //
                //The client field is needed the items detailed poster query.
                . "nbitem.client "
            ."FROM "
                //
                //Bring in all the non-aesthetic items
                . "({$this->union_of_na_items($parametrized)})as nbitem "
            . "Group by "
                  //
                . "nbitem.client"
        );
    }
   
    //Posting a closing balance simply adds tehe closing balance record for the
    //current period 
    function post(){
        //
        //Create the closing balance record, like any other bianry case
        $this->dbase->query(
            "insert into "
                //
                //Its the closing balance table we want to insert
                . "closing_balance(invoice, amount) "
                //
                //The data comes from the poster sql for this item
                . "("
                    . "select "
                        //
                        ."current_invoice.invoice, "
                        //
                        //The closing balance amount
                        ."poster.amount "
                    . "from "
                        //
                        //Get All the current closing balances -- no parametrized 
                        //client constraint
                        . "({$this->detailed_poster(false)}) as poster "
                        //
                        //The current invoice is needed for posting purposes. 
                        //This assumes that the invoice must be posted before 
                        //any storage item
                        . "inner join ({$this->current_invoice()}) as current_invoice on "
                           . "current_invoice.client = poster.client "
                . ") "
                //
                . "on duplicate key update "
                    //
                    //Only non-identifies are updated            
                    . "amount = values(amount)"
        );             
    }
    
    //Returns the sql that reports on all unionised items that are needed for 
    //computing closing balances, i.e., non-aesthetic items.
    function union_of_na_items($parametrized=true){
        //
        //There is no initial union operator; after the first union, the 
        //operatop will be set to 'union all'.
        $union_operator = "";
        //
        //Start with an empty sql statement
        $sql="";
        //
        //Visit each non-aesthetic item and add it to the uniting sql.
        //There must at least one item to cpnstruct the closing balance
        //
        foreach($this->record->items as $item){
            //
            //Only non-aesthetic cases are considered
            if (!$item->aesthetic){
                //
                //Build the union, checking the subquery as usual
                $sql = $sql . $union_operator . $this->chk(
                    " select "
                        //
                        //The amount to be summed -- taking care whether the item
                        //debits or credits teh client's balance. 
                        ."poster.amount, "
                        //
                        //The client to use for grouping. This implies all poster
                        //queries must report the client.
                        ."poster.client "
                    ."from "
                        //
                        //This query should run for all clients, NOT just a 
                        //specific one, i.e., we don't need the parametrized client
                        //constraint?????. In addition, closing balance calculation needs 
                        //all postable records -- not just those that have not
                        //been posted. So, the postage constraint is not needed
                        . "({$item->detailed_poster($parametrized, false)}) as poster "
                );
                //
                //Re-set the union operatr so that duplicate records are not ignored.
               $union_operator = " union all ";         
            }
        } 
        //
        //We check and return the complete union of all items
        return $this->chk($sql);
    }
    
     //Unposting of closing balances removes the last posted records except
     //those that are initially loaded. That is primary data -- not derived
    function unpost() {
        //
        $this->query(
            "delete "
                //
                //From the closing balance table
                . "closing_balance.* "
            . "from "
                //
                . "closing_balance "
                //
                //Bring in the last invoice; yes you are unposting the last
                //posted case closing balance. 
                . "inner join ({$this->record->invoice->last_invoice()}) as last_invoice on "
                    . "closing_balance.invoice = last_invoice.invoice "
            //
            //Exclude the initial balances            
            . "where "
                . "not (closing_balance.initial)"
        );       
    }
    
}

