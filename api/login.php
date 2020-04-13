<?php
 require_once '../app.php';
 
  function login($conn){
      
    $token = null;
    // $headers= apache_request_headers();
    // print_r($headers);

    $postdata = file_get_contents("php://input");

    if (isset($postdata) && !empty($postdata)) {
       $request = json_decode($postdata);

       //sanitize
       $username = $request->username;
       $password = $request->password;

       $queryLog = $conn->query("SELECT * FROM students WHERE email = '$username' LIMIT 1");

       $numRow = $queryLog->num_rows;

       if ($numRow > 0 ) {
       	   
       	   $row = $queryLog->fetch_assoc();

       	   if ($row['password'] == $password) {

          echo json_encode(
              array(
              	'status'  => 'OK',
                'message' => 'successful login',
                'token'   => md5($username . time()),
                'email'   => $username
          ));

          http_response_code(200);
       }
       else{
        // http_response_code(401);
        echo json_encode(
        	array(
        		'status'  => 'ERROR',
        		'message' => 'Login failed'
        	));
          }
       }  else{
       	   echo json_encode(
        	array(
        		'status'  => 'ERRORUSER',
        		'message' => 'User does not exist'
        	));
       }  
    }
      
  exit();
  }

   login($conn);