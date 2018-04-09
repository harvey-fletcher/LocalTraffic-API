<?php

	//Include the API settings
	include_once('api_settings.php');

	//This is the query to delete any existing tokens
	$query = "DELETE FROM application_tokens WHERE token='". $_GET['Token'] ."'";

	//Run the deletion query
	mysqli_query($conn, $query);

	//This is the query to insert the new token
	$query = "INSERT INTO application_tokens (`token`) VALUES ('". $_GET['Token'] ."')";

	//Run the insert query
	mysqli_query($conn, $query);


	//Done message
	$data = array("status"=>"200","info"=>"Processed Token");
	echo json_encode(array($data));
?>
