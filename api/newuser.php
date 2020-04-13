<?php
 require_once '../app.php';

$postdata = file_get_contents("php://input");

if (isset($postdata) && !empty($postdata)) {
	
	$request = json_decode($postdata);

	//sanitize
	$fName = ucwords(strtolower(trim($request->fName)));
	$lName = ucwords(strtolower(trim($request->lName)));
	$email = strtolower(trim($request->email));

	
	$response = $user->newUser($email, $firstname, $surname);
    echo json_encode($response);

}

exit();