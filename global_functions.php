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
?>
