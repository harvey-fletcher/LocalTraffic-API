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
        if(!isset($Event[12]))die();

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

         //Get the current time
         $nowTime = strtotime(date('Y-m-d') . 'T' . date('h:i:s+01:00'));
         $overallStart = strtotime($Event[17]["value"]);

         //If the event start is in the future, set isFuture to true
         if($overallStart > $nowTime){
             $data["isFuture"] = true;
         } else {
             $data["isFuture"] = false;
         }

         //Because sending all the future planned events makes the array WAAAAAAAAAAAAYYYYYY too big for FCM, we only want to send events which will occur within the next 1 day, and result in road closure
         $futureDateFilter = strtotime(date('Y-m-d h:i:s', strtotime("+1 Day")));

         //We only want to apply the additional date filters if the event is in the future, else we send it anyway
         if($data["isFuture"]){
             //Start the event by default to send
             $sendEvent = true;

             //If the event starts after the FDF, then ignore it.
             if($overallStart > $futureDateFilter){
                 $sendEvent = false;
             }

             //We only want to display roads closed
             if(strpos($data["category"], "closure") === false){
                 $sendEvent = false;
             }

             //Attach the extra fields
             $data["overallStart"] = gmdate("d/m/y H:i", $overallStart);

             //Attach the event if it is not to be ignored.
             if($sendEvent){
                 array_push($output, $data);
             }
         } else {
             array_push($output, $data);
         }
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
