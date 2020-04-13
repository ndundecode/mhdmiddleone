<?php 
 require_once "db_connect.php";

class User extends Database {
    
    public function __construct(){

        parent::__construct();
    }

   public function login($email, $password) {
      

       $queryLog = $this->conn->query("SELECT * FROM students WHERE email = '$username' LIMIT 1");

       $numRow = $queryLog->num_rows;

       if ($numRow > 0 ) {
           
           $row = $queryLog->fetch_assoc();

           if ($row['password'] == $password) {

          return (
              array(
                'status'  => 'OK',
                'message' => 'successful login',
                'token'   => md5($username . time()),
                'email'   => $username
          ));

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
/*----------------------------------------------------------------------*/

public function newUser($email, $firstname, $surname){
       
       //store
  if (!empty($email, $firstname, $surname)) {

   $conn->query("INSERT INTO `students`(`fName`, `lName`, `email`)
           VALUES ('$firstname', '$surname','$email')");

      return array(
                    'status' => 'OK',
                    'code' => 200,
                    'message' => 'User succefully created',
                    'userEmail' => $email
                );
  }else{

      return array(
                    'status' => 'ERROR',
                    'code' => 401,
                    'message' => 'User creation failed'
                  );
  }
  
}
/******************************************************************************************************** */
}//end User class

  