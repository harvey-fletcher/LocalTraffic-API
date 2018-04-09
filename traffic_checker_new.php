<?php

	//API_settings
	include_once('api_settings_v2.php');

	//Frequently used functions
	include_once('global_functions.php');

	$data = array();
	$items = array();
	$output = array();

	//This is the XML with the traffic information
	$inputXML = shell_exec('curl http://m.highways.gov.uk/feeds/rss/UnplannedEvents.xml');

	//We want to split that XML into useable arrays
	$TrafficData = xml_parse_into_struct(xml_parser_create(), $inputXML, $vals, $index);

	//Erase the first 17 tags as they do not contain useful traffic data
	foreach(range(0,17) as $erase){
		unset($vals[$erase]);
	}

	//Split the useable traffic data up
	$TrafficData = array_chunk(array_values($vals), 20);

	//The last tag in that array just closes the RSS feed and is not needed
	array_pop($TrafficData);

	//For each element, we need the following information
	foreach($TrafficData as $Event){
		//A blank array to put this traffic event into
		$TrafficEvent = array();

		//We need the following sections
		$TrafficEvent["event_id"] = $Event[5]["value"]; //The unique ID of the event
		$TrafficEvent["category"] = $Event[3]["value"]; //The category
		$TrafficEvent["description"] = $Event[8]["value"]; //A description of the event
		$TrafficEvent["road"] = $Event[10]["value"]; //The name of the road
		$TrafficEvent["county"] = $Event[12]["value"]; //The county
		
		//Put that into the main data array so it gets returned
		array_push($data, $TrafficEvent);
	}

	//Clear the TrafficData array so we can populate it with only the values we want
	$TrafficData = array();

	//Filter the traffic so we only get events in Hampshire and Surrey
	foreach($data as $item){
		if(($item["county"] == "Hampshire")){ //|| ($item["county"] == "Surrey")){
			array_push($TrafficData, json_encode($item, JSON_FORCE_OBJECT));
		}
	}

	//Get the tokens to send to.
	$tokens = GetAllTokens($conn);

	//This is what we will send in the FCM request
        $CURLdata = '{"data":{"traffic_information":' . json_encode($TrafficData) . '},"registration_ids":['. $tokens  .']}';

	//If we're doing a blanksend (testing)
	if(isset($_GET['blanksend'])){
		$CURLdata = '{"data":{"traffic_information":[]},"registration_ids":['. $tokens .']}';
	}

        //Build the curl request command WITH the data in it
        $command = "curl -X POST --Header 'Authorization: key=". $notifications_key  ."' --Header 'Content-Type: application/json' -d '" . $CURLdata . "' 'http://fcm.googleapis.com/fcm/send'";

        //Execute the curl request $command and store it as an array
	$SendMessageResults = json_decode(shell_exec($command), 1);

	//Pretty print the data we have sent if the user has requested it in pretty format
        if(isset($_GET['pretty'])){
		$SendMessageData = json_decode($CURLdata, 1);
		$SendMessageTo = json_decode($CURLdata, 1)['registration_ids'];

		//Merge the registration IDs into the result array so we can see on an individual basis which users are active
		$CurrentID = 0;
	
		foreach($SendMessageTo as $RegistrationID){
			$SendMessageResults['results'][$CurrentID]['registration_id'] = $RegistrationID;
			$CurrentID++;
		}

		echo '<h1>This is the data we are sending out</h1>';
                echo '<pre>' . print_r($SendMessageData, 1) . '</pre>';
		echo '<h1>This is the result of the bulk message send</h1>';
                echo '<pre>' . print_r($SendMessageResults, 1) . '</pre>';
        }

?>
