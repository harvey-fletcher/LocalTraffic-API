<?php

    //API_settings
    include_once('api_settings_v2.php');

    //Frequently used functions
    include_once('global_functions.php');

    //Go to Highways agency and get a list of all the events
//    $data = doCurl('http://m.highways.gov.uk/feeds/rss/AllEvents.xml');
    $data = doCurl('http://m.highways.gov.uk/feeds/rss/UnplannedEvents.xml');

    //Split those events up into an array so that we can work with them.
    xml_parse_into_struct(xml_parser_create(), $data, $Events);

    //There are 18 tags prior to the first event.
    foreach(range(0,17) as $index){
        unset($Events[$index]);
    }

    //There are 2 un-necessary tags after the last event
    array_pop($Events);
    array_pop($Events);

    //There are 20 tags per event
    $Temp = array_chunk($Events, 20);
    $Events = array();

    //Only take roads for Hampshire or Surrey
    foreach($Temp as $Event){
        $COUNTY = $Event[12]["value"];

        if($COUNTY == "Surrey" || $COUNTY == "Hampshire"){
            array_push($Events, $Event);
        }
    }

    //We only pass in certain values of the traffic information, this goes into an array, $output
    $output = array();

    //Load the specific values into the array
    foreach($Events as $Event){
         $data = array();

         $data["event_id"] = $Event[5]["value"];
         $data["category"] = $Event[3]["value"];
         $data["description"] = $Event[8]["value"];
         $data["road"] = $Event[10]["value"];
         $data["county"] = $Event[12]["value"];

         array_push($output, $data);
    }

    //Get the tokens to send to.
    $tokens = GetAllTokens($conn);

    //This is what we will send in the FCM request
    $CURLdata = '{"data":{"traffic_information":' . json_encode($output) . '},"registration_ids":['. $tokens  .']}';

    //If we're doing a blanksend (testing)
    if(isset($_GET['blanksend'])){
  	$CURLdata = '{"data":{"traffic_information":[]},"registration_ids":['. $tokens .']}';
    }

    //Execute the curl request $command and store it as an array
    if(isset($_GET['pretty'])){
        echo '<pre>' . print_r(json_decode($CURLdata), 1) . '</pre>';
        echo '<pre>' . print_r(json_decode(sendTraffic($CURLdata)), 1) . '</pre>';
    } else {
        sendTraffic($CURLdata);
    }

?>
