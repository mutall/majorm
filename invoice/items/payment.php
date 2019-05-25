<?php
namespace invoice;

//
//This class supports management of payments received from a client. 
class item_payment extends item_unary{
    //
    public function __construct($record) {
        //
        parent::__construct($record, "payment");
    }
    
    //Returns an sql that retuens received payments during teh current period
    function detailed_poster($parametrized=true){
        //
        return $this->chk(
            "select "
                //
                //The key payment messages to be communicated to the client 
                //through the monthly invoice report are:-
                //
                //The acutal date when the amount was paid
                ."payment.date, "
                //
                //The receipt reference number, i.e., cheque no, mpesa, etc.
                ."payment.ref, "
                //
                //The amount paid; it is a credit amount -- so we expect the 
                //usee to enter it as a negative amount. This can be enforced
                //by a suitable user interface
                ."payment.amount, "
                //
                //Primary and foreign keys
                //
                //The primary key needed for summarizing bclosing balancee
                ."payment.client "
            //
            //The remaider of the sql the similar for all unary items
            ."FROM "
                //The driver for payments
                ."payment "
                //
                //Add a left join to last opening balance, relative to the referece 
                //timestamp -- as the last invoice might not be available
                . "left join ({$this->record->invoice->last_invoice()}) as last_invoice "
                    . "on last_invoice.client = payment.client "
            ."where "
                //
                //Specify the client parameter if necessary. Every poster sql 
                //must be constrained to a specific client when in display mode.
                //The constraint is removed when exporting data (as a batch) 
                //using an sql        
                .($parametrized ? "payment.client=:driver ": "true ")
                //
                //Current adjustments are between the current date and the 
                //last invoice -- if any
                //
                //Ignore all future adjustments
                . "and ".($this->record->invoice->has_future 
                        ? "payment.date<'{$this->record->invoice->future_date}' "
                        :"true "
                    )
                //
                //Ignore, if any, posted adjustments
                . "and if("
                        . "last_invoice.invoice is null, "
                        . "true, "
                        . "payment.timestamp>=last_invoice.timestamp) "
                
        );
    }
    
    
}
