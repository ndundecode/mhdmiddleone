<?php
 require_once '../app.php';

$postdata = file_get_contents("php://input");

if (isset($postdata) && !empty($postdata)) {
	
	$request = json_decode($postdata);

	//sanitize
	$fName = ucwords(strtolower(trim($request->fName)));
	$lName = ucwords(strtolower(trim($request->lName)));
	$email = strtolower(trim($request->email));

	//store
	$conn->query("INSERT INTO `students`(`fName`, `lName`, `email`)
					 VALUES ('$fName', '$lName','$email')");
}

exit();