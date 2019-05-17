<?php
namespace invoice;

//This class supports management of adhoc, rather than routine, 
//adjustments that need to be made to account for unusual circumstances that 
//directly affect the closing balance of a client. 
class item_adjustment extends item_unary {
    //
    public function __construct($record) {
        //
        parent::__construct($record, "adjustment");
    }
    
    //Returns the sql used for displaying and posting adjustments that credit
    //or debit a client's invoice account for the current period
    function detailed_poster($parametrized=true){
        //     
        return $this->chk(
            "select "
                //Client is required for formulating closing balances
                ."adjustment.client, "
                //
                //The following fields are desired for user viewing
                //
                //The date of adjustment; it must be between the invoice reference 
                //timestamp and the last opening balance
                ."adjustment.date, "
                //
                //The reason why the adjustment was done; there must be one as 
                //it is used as an identifier
                ."adjustment.reason, "
                //
                //Crediting decreases the clients balance; debiting increases it
                //You have to decide which is which -- depnding on the circumstances
                ."adjustment.amount "
            //
            //The remaider of the sql the similar for all unary items
            ."from "
                //The driver of this (unary) item is ajustment
                ."adjustment "
                //
                //Add a left join to last opening balance, relative to the referece 
                //timestamp -- as the last invoice might not be available
                . "left join ({$this->record->invoice->last_invoice()}) as last_invoice "
                    . "on last_invoice.client = adjustment.client "
            ."where "
                //
                //Specify the client parameter if necessary. Every poster sql 
                //must be constrained to a specific client when in display mode.
                //The constraint is removed when exporting data (as a batch) 
                //using an sql        
                .($parametrized ? "adjustment.client=:driver ": "true ")
                //
                //Current adjustments are between the current date and the 
                //last invoice -- if any
                //
                //Ignore all future adjustments
                . ($this->record->invoice->has_future 
                        ?"and adjustment.date<'{$this->record->invoice->future_date}' "
                        :"and true "
                   )
                //
                //Ignore, if any, posted (or historical) adjustments
                . "and if("
                        . "last_invoice.invoice is null, "
                        . "true, "
                        . "adjustment.timestamp>=last_invoice.timestamp) "
                 
        );
    }
}
