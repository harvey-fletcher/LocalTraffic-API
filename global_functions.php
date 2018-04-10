<?php
	//This function will connect to the database and get all registered application instances
	function GetAllTokens($conn){
		//This is the string we will put the tokens in
		$tokens = "";

		//Get all the tokens from the database
		$query = "SELECT token FROM application_tokens";
		$result = mysqli_query($conn, $query);

		//How many tokens are there?
		$token_count = mysqli_num_rows($result);
		
		//Start building the array from row 0
		$current_token = 0;

		//Loop through all the rows
		while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
			//Add to tokens
			$tokens.= '"' . $row['token'] . '"';

			//If this row has another row after it, add a suffix of a comma.
			if($current_token + 1 < $token_count){
				$tokens.= ',';
			}

			//Increment to the next row;
			$current_token++;
		}

		//Return the string of tokens
		return $tokens;
	}

    //This is the function used to go and get XML from pages.
    function doCurl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        return curl_exec($ch);
    }

    function sendTraffic($data){
        $ch = curl_init();

        $Parameters = array(
                "Authorization: key=" . $GLOBALS['notifications_key'],
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            );

        curl_setopt($ch, CURLOPT_URL, "http://fcm.googleapis.com/fcm/send");
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $Parameters);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch); 
        $err = curl_error($ch);
        curl_close($ch);

        if($err){
            die();
        }

        return $response;
    }
