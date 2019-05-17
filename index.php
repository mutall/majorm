<?php
//
//Use the try/catch block for the production version
//try
//{
    //
    //The page class, needed to lauch the index() function below, is defined in 
    //the following shared library. Note how it is placed relative to this project
    require_once "invoice/classes.php";
    //
    //The index will will evoke methods, some of which are in the shared library, 
    //others are locally defined in this project. To the resolve locally defined 
    //ones, we need access to the local classes.
    require_once "majorm.php";
    //
    //Now call the index process from the invoice namespace. Pass the home 
    //directory to the page
    invoice\page::index(__DIR__);
                    
//
//Use the try/catch for te production version
//}catch(\Exception $ex){
//   die($ex->getTraceAsString()); 
//}