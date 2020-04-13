<?php
 require_once '../app.php';

/*********************************************************************************/
 // delete record

 if (isset($_GET['delete'])) {
 	 
 	 $id = $_GET['delete'];

     $conn->query("DELETE FROM `students` WHERE sId = '$id'");

     exit();
 }

/*********************************************************************************/
 /*Fetch user update*/
 if (isset($_GET['getStudentUpdate'])) {
 	 
 	 $id = $_GET['getStudentUpdate'];

     $sql = $conn->query("SELECT * FROM students WHERE sId = '$id' LIMIT 1");
     $result = $sql->fetch_assoc();

     echo json_encode($result);

     exit();
 }
 /********************************************************************************/
 //update
 if (isset($_GET['update'])) {
 	 
 	 $id = (int)$_GET['update'];

     $postdata = file_get_contents("php://input");
     $request = json_decode($postdata);

	//sanitize
	$fName = ucwords(strtolower(trim($request->fName)));
	$lName = ucwords(strtolower(trim($request->lName)));
	$email = strtolower(trim($request->email));

 	 $conn->query("UPDATE `students`
 	 			   SET `fName`='$fName',`lName`='$lName',`email`='$email'
 	 			   WHERE `sId`='$id'");

 	 exit();
 }

 /****************************************************/
