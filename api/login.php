<?php
 require_once '../app.php';
 
    $postdata = file_get_contents("php://input");

    if (isset($postdata) && !empty($postdata)) {
       $request = json_decode($postdata);
        
       $token = null;
       
       //sanitize
       $username = $request->username;
       $password = $request->password;


        $response = $user->login($username, $password);
        echo json_encode($response);

       exit();
    }