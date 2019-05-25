<?php
namespace invoice;

//This class supports management of chargeable, monthly services that are not 
//covered by rental, water or electricity charges. Identifying and delivering 
//these services should be a core business of a rental system to promote client
//satisfaction. The current services for Mutall include:-
//
//. security
//. gabbage collection.
//
//The following servics are in the planning stages:-
// 
//. IT consultations
//. Stand-by power supply
//
//The model to support these and future services comprises of the following 
//entities:-
//. service(name*, price, auto)
//. subscription(service*, client*, factor)
//. charge(servive*, period*, amount) 
//
//Notes:-
//(i) The cost of a service is calculated in 2 ways:- 
//
//a) charge = subscription.factor * service_type.price or
//b) charge = service_type.price
//
//depending on whether the servce is automatic or not.
//Formula a) is suplied if the service is subscribed. This method is used to levy
//non-standard charges to selected clients
//Formula be is used if a service is automatic and ahas not been subscribed.
//subscriibed.
//If a service is not automatic and has not been subscribed, e.g., water charges
//then other criteria must be used for calculating the charge. That becomes a new
//item that is driven by client, usses charge as the storage and uses water meter
//connection to pick out shich clents to charge.
//
//The subscription factor is estimated by a joint assessment when a client is
//registered, but can be altered during rent life when the service usage is 
//better estimated. This fact is used to adjust charges on after the 3rd year of 
//client occupancy
//
//Service is a binary item because it is pposting it involves 2 tables: the 
//water connection as the driver and the charge as the storage for the posted 
//records.
class item_service extends item_binary {
    //
    public function __construct($record) {
        //
        //The servce driver is the ater connectin
        parent::__construct($record,  "wconnection", "charge");
        //
        //Services are paid in advance, when rent is due
        $this->advance=true;
    }
    
    //Returns the all the unclasified charges associated with unstructured 
    //services. These charges are based on intuition and client/tenant agreement
    //In contrast water, electricity and rent are charges derived from some raw 
    //inputs, e.g., meter readings.
    function detailed_poster($parametrized=true){
        //
        //There are 2 ways of calcluating service charges
        //
        //a) when a client has subscribed to a service...
        $ca = "subscription.subscription is not null ";
        //
        //...the charge should be the subscribed amount
        $a ="subscription.amount ";
        //
        //b) when there is no subscription and the service is automatically 
        //charged...
        $cb = "subscription.subscription is null and service.auto "; 
        //
        //...then charge the same as the service price
        $b = "service.price ";
        //
        //When non of the conditions apply, the service charge is not applied
        $amount = "if($ca ,$a , if($cb, $b, null))";
        //
        return $this->chk(
            "SELECT "
                //Client Messages to report:- 
                    ."service.name, "
                    //
                    //Identify the water connection 
                    . "wconnection.meter_no, "
                    //
                    //The amount to be charged
                    ."$amount as amount, " 
                    //
                    //Keys needed for supporting this binary item
                    //
                    //used for calculating closing balances for the client
                    ."client.client,  "
                    //
                    //Charge is identified by 3 keys: service, wconnection and
                    //the current date
                    ."service.service, "
                    . "wconnection.wconnection "
            ."FROM "
                //
                //The driver for services is the water connection
                ."wconnection "
                //
                //Add the client required for a) summarising balances and b) 
                //linking teh services to the current invice
                . "inner join client on "
                    . "wconnection.client = client.client "
                //
                //Add support for deriving client messages; its a lose join -- 
                //thus bringing in all the services
                //
                ."join service "
                //
                //Add support for testing whether a servive is subscribed or not
                ."left join subscription on "
                    ."subscription.service = service.service "
                    . "and subscription.wconnection = wconnection.wconnection "
                //
                //Add charged to support the "charge service once per month" rule 
                //-- especially when there are multiple postings in a month
                . "left join ({$this->charged()}) as charged on "
                    . "charged.wconnection = wconnection.wconnection "
                    . "and charged.service = service.service " 
            ."where "
                //        
                //Apply the client parametrized constraint, if requested        
                . ($parametrized ? "client.client = :driver ": "true ")
                //
                //Exclude null service amounts
                . "and ($amount) is not null "
                //
                //Only active, i.e., not closed, connections are considered
                . "and wconnection.end_date is null "
                //
                //Enforce the "charge once per month" rule b applying the charge
                //to the services that have not yest been charged.        
                . "and charged.service is null"
                        
        );
    }
    
    //Posting of servivces follow the general procedure for auto-generatd items.
    function post(){
        //
        return $this->query(
           //
           //Auto generated records are always created for the storage table      
           "insert into "
                //
                //The storage table is used for holding the posted data. The
                //fields of interest are the user messages
                . "charge ("
                    //
                    //Specify the user messages
                    . "amount, "
                    //
                    //Specify the charge identification fields  
                    . "wconnection, service, invoice "
                . ") "
                //
                //The poster sql to identify the data to be posted
                . "(select "
                    //
                    //match the user messages 
                    . "poster.amount, "
                    //
                    //The service charge identifiers
                    . "poster.wconnection, "
                    . "poster.service, "
                    . "current_invoice.invoice "
                . "from "
                    //
                    //No parametrization and Duplicates will be taken care of
                    . "({$this->poster()}) as poster "
                    //
                    //We need the current invoice
                    . "inner join ({$this->current_invoice()}) as current_invoice on "
                        . "current_invoice.client = poster.client "
                . ") "
                //
                //Only non-identifiers feature in an on duplicate clause            
                . "on duplicate key update "
                    . "amount = values(amount) "
        );        
    }
    
    //Returns serivices that have already been charged for the current period, to
    //enforce the "charge once per month" rule when multiple postings are done
    //in a month.
    function charged() {
        //
        return $this->chk(
        "select "
            //    
            //The service in question
            ."service.service, "
            //
            //The water conennction is required a left join to this query to 
            //allow us determine if the connection has been charged or not
            . "charge.wconnection "
        . "from "
                //The charge table drives this process
                ."charge "
                //
                //Bring in the service in question
                ."inner join service on charge.service = service.service "
                //
                //Invoice supplies the charge's timestamp
                . "inner join invoice on charge.invoice = invoice.invoice "
         ."where "
            //
            //Match the current and invoice month
            ."month(invoice.timestamp) = month('{$this->record->invoice->timestamp}') "
            //    
            //Match the current and invoice year
            ."and year(invoice.timestamp) = year('{$this->record->invoice->timestamp}') "
            //
            //Exclude the current invoice -- to correct for the partial posting
            //problem
            . "and (not invoice.timestamp = '{$this->record->invoice->timestamp}')"
        );        
    }
        
}
