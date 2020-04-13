<?php 
 require_once "db_connect.php";
class User extends Database{
    
    public function __construct(){

        parent::__construct();
    }

/**---------------------------------------------------------------------------------------- */
    public function sec_session_start() {
      $session_name = 'mysession';   // Set a custom session name
      $secure = true;
      // This stops JavaScript being able to access the session id.
      $httponly = true;
      // Forces sessions to only use cookies.
      if (ini_set('session.use_only_cookies', 1) === FALSE) {
          // header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
          return $response = array(
            'status' => 'ERROR',
            'code' => 401,
            'message' => 'Could not initiate a safe session'
          );
          exit();
      }
      // Gets current cookies params.
      $cookieParams = session_get_cookie_params();
      session_set_cookie_params($cookieParams["lifetime"],
          $cookieParams["path"], 
          $cookieParams["domain"], 
          $secure,
          $httponly);
      // Sets the session name to the one set above.
      session_name($session_name);
      session_start();            // Start the PHP session 
      session_regenerate_id(true);    // regenerated the session, delete the old one. 
  }
  /********************************************************************************** */
   // LOGIN FUNCTION
   public function login($email, $password) {
    // Using prepared statements means that SQL injection is not possible. 
    if ($stmt = $this->conn->prepare("
            SELECT user_id, user_email, user_password, role_type, user_fname, user_lname, user_security_id
            FROM users
            WHERE user_email = ?
            LIMIT 1")) {
            $stmt->bind_param('s', $email);  // Bind "$email" to parameter.
            $stmt->execute();    // Execute the prepared query.
            $stmt->store_result();
 
        // get variables from result.
        $stmt->bind_result($user_id, $user_email, $db_password, $role_type, $firstname, $lastname, $security);
        $stmt->fetch();
 
        if ($stmt->num_rows == 1) {
            // If the user exists we check if the account is locked
            // from too many login attempts 
 
            if ($this->checkbrute($user_id) == true) {
                // Account is locked 
                // Send an email to user saying their account is locked
                // return false;
                return $response = array(
                  'status' => 'ERROR',
                  'code' => 401,
                  'message' => 'Your account is locked'
                );
            } else {
                // Check if the password in the database matches
                // the password the user submitted. We are using
                // the password_verify function to avoid timing attacks.
                if (password_verify($password, $db_password)) {
                    // Password is correct!

                    // Get the user-agent string of the user.
                    $user_browser = $_SERVER['HTTP_USER_AGENT'];
                    // XSS protection as we might print this value

                    // $user_id = preg_replace("/[^0-9]+/", "", $user_id);
                    $_SESSION['user_id'] = $user_id;
                    // XSS protection as we might print this value

                    // $username = preg_replace("/[^a-zA-Z0-9_\-]+/",  "", $username);
                    $_SESSION['username'] = $user_email;
                    $_SESSION['login_string'] = hash('sha512',  $db_password . $user_browser . time());
                    // Login successful.

                    /* --------------------------------*/
                    /*INSERT IN USER HISTORY*/
                    $message = $this->sanitize("has logged in the system at ");
                    $this->insert_logQ($_SESSION['user_id'], $message);
                    /*UPDATE USER LAST LOGIN TIME HE IS PERFORMING*/
                    $lastlogin = date("Y-m-d H:i:s");
                    //$lastlogin = date("Y-m-d h:m:s");
                    $pre_stmt = $this->conn->prepare("UPDATE users SET user_last_login = ? WHERE user_email = ?");
                    $pre_stmt->bind_param("ss", $lastlogin, $email);
                    $result = $pre_stmt->execute() or die($this->conn->error);

                    /*--------------------------------*/

                    // rsend back response to the front end;
                    return $response = array(
                      'status'    => 'OK',
                      'code'      => 200,
                      'message'   => 'User succefully logged in',
                      'firstname' => $firstname,
                      'lastname'  => $lastname,
                      'email'     => $_SESSION['username'],
                      'token'     => $_SESSION['login_string'],
                      'userId'    => $_SESSION['user_id'],
                      'roleType'  => $role_type,
                      'securityId'=> $security
                    );
                    /* -------------------------------- */
                } else {
                    // Password is not correct
                    // We record this attempt in the database
                    $now = time();
                    $this->conn->query("INSERT INTO login_attempts(user_id, time)
                                    VALUES ('$user_id', '$now')");
                    // return false;
                    return $response = array(
                      'status' => 'ERROR',
                      'code' => 401,
                      'message' => 'User failed to loggin'
                    );
                }
            }
        } else {
            // No user exists.
            // return false;
            return $response = array(
              'status' => 'ERROR',
              'code' => 401,
              'message' => 'User does not exist'
            );
        }
    }
}
/********************************************************************************************** */
 //check login

public function login_check() {
  // Check if all session variables are set 
  if (isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['login_string'])) {

       $user_id = $_SESSION['user_id'];
       $login_string = $_SESSION['login_string'];
       $username = $_SESSION['username'];

      // Get the user-agent string of the user.
      $user_browser = $_SERVER['HTTP_USER_AGENT'];

      if ($stmt = $this->conn->prepare("SELECT user_password 
                                        FROM users 
                                        WHERE user_id = ? LIMIT 1")) {
          // Bind "$user_id" to parameter. 
          $stmt->bind_param('s', $user_id);
          $stmt->execute();   // Execute the prepared query.
          $stmt->store_result();

          if ($stmt->num_rows == 1) {
              // If the user exists get variables from result.
              $stmt->bind_result($password);
              $stmt->fetch();
              $login_check = hash('sha512', $password . $user_browser);

              if (hash_equals($login_check, $login_string) ){
                  // Logged In!!!! 
                  return $response = array(
                    'status' => 'OK',
                    'code' => 200,
                    'message' => 'User succefully logged in',
                    'username' => $username
                    
                  );
                  // return true;
              } else {
                  // Not logged in 
                  return $response = array(
                    'status' => 'ERROR',
                    'code' => 401,
                    'message' => 'Not logged in'
                  );
                  // return false;
              }
          } else {
              // Not logged in
              return $response = array(
                'status' => 'ERROR',
                'code' => 401,
                'message' => 'Not logged in'
              );
              // return false;
          }
      } else {
          // Not logged in 
          return $response = array(
            'status' => 'ERROR',
            'code' => 401,
            'message' => 'Not logged in'
          );
          // return false;
      }
  } else {
      // Not logged in 
      return $response = array(
        'status' => 'ERROR',
        'code' => 401,
        'message' => 'Not logged in'
      );
      // return false;
  }
}
/************************************************************************************************ */
 // CHECKBRUT FUNCTION
public function checkbrute($user_id) {
  // Get timestamp of current time 
  $now = time();

  // All login attempts are counted from the past 2 hours. 
  $valid_attempts = $now - (2 * 60 * 60);

  if ($stmt = $this->conn->prepare("SELECT time 
                           FROM login_attempts 
                           WHERE user_id = ? 
                          AND time > '$valid_attempts'")) {
      $stmt->bind_param('s', $user_id);

      // Execute the prepared query. 
      $stmt->execute();
      $stmt->store_result();

      // If there have been more than 5 failed logins 
      if ($stmt->num_rows >= 5) {
          return true;
      } else {
          return false;
      }
  }
}
/*************************************************************************************************/
/*CHECK IF THE USER IS ALREADY REGISTERED OR NOT*/

private function email_exists($email, $table_name, $column_email){

    $pre_stmt = $this->conn->prepare("SELECT * FROM $table_name WHERE $column_email = ?");
    $pre_stmt->bind_param("s", $email);
    $pre_stmt->execute() or die($this->conn->error);
    $result = $pre_stmt->get_result();

    if($result->num_rows > 0)
      return 1;
    else
      return 0;

}//end email_exists method
/*------------------------------------------------------------------------------------------------------------------*/

public function create_user_account($email, $column_email, $table_name, $user_data){
    //prepare statement is used here to protect from SQL attack

    if($this->email_exists($email, $table_name, $column_email)){
    
        return $response = array(
          'status' => 'ERROREMAIL',
          'code' => 401,
          'message' => 'Email already exists'
        );
    }
    else {  
        
      // echo '<pre>';
      //   var_dump($data); die();
            /*INSERT IN USER HISTORY*/
            // $message = $this->sanitize("has added a user in the system at ");
            
           if($this->insertQ($table_name, $user_data)){

             /* --------------------------------*/
                /*INSERT IN USER HISTORY*/
               // $message = $this->sanitize("created an account in the system at ");
               //  $this->insert_logQ($_SESSION['user_id'], $message);
               //  /*UPDATE USER LAST LOGIN TIME HE IS PERFORMING*/
               //  $lastlogin = date("Y-m-d H:i:s");

               //  $pre_stmt = $this->conn->prepare("UPDATE users SET user_last_login = ? WHERE user_email = ?");
               //  $pre_stmt->bind_param("ss", $lastlogin, $email);
               //  $result = $pre_stmt->execute() or die($this->conn->error);

              /*--------------------------------*/
                return $response = array(
                  'status' => 'OK',
                  'code' => 200,
                  'message' => 'User succefully created',
                  'userEmail' => $email
                );
           }else{
                 return $response = array(
                   'status' => 'ERROR',
                  'code' => 401,
                  'message' => 'User creation failed'
                );
                 
              }
    }
    }//end create_user_account
/*------------------------------------------------------------------------------------------------------------------*/
private function insertQ($table_name, $data){

    $insertQUser = "INSERT INTO $table_name(".implode(",", array_keys($data)).")";
    $insertQUser .= "VALUES(". "'" . implode("','", array_values($data)) . "')";
    return $resultUser = $this->conn->query($insertQUser) or die($this->conn->error);
     

  }//end insertQ
/**************************************************************************************************** */
public function generateUserID($fname, $lname, $dob, $roleType){
// public function generateUserID($fname, $lname, $dob, $priv){

    // $security_id = "";
    $edit_date = date("ymd", strtotime($dob));
    $edit_date = $this->generateDob($dob);

    $initial  = substr($lname, 0,1);
    $initial .= substr($fname, 0,1);
    $initial  = strtoupper($initial);

      switch ($roleType) {

        case 'SAD':
          $security_id = $initial.'S'.$edit_date;
          break;
        case 'GAD':
          $security_id = $initial.'G'.$edit_date;
          break;
      case 'LAD':
          $security_id = $initial.'L'.$edit_date;
        break;
      case 'NAD':
          $security_id = $initial.'N'.$edit_date;
      break;
        default: 'wrong role input';
          break;
      }

    return $security_id;
}
/*************************************************************************************************** */
/*GENERATE UNIQUE SECURITY CODE*/


public function checkkeys($randStr){
// function checkkeys($data, $randStr, $table_name, $check_column_name){
  
	$sql = $this->conn->query("SELECT * FROM users");
  
  $keyExists = false;
	while($row = $sql->fetch_assoc()){

		if ($row["user_security_id"] == $randStr) {

			$keyExists = true;
			break;
		}else{
			$keyExists = false;
		}

	}
	return $keyExists;
}

/*-----------------------------------------------------------------*/

public function generateDob($dob){
// function generatekey($conn, $table_name, $check_column_name){

	$keyLength = 8 ;
    // $str = "1234567890";
    $str = date("Ymd", strtotime($dob));
	$randStr = substr(str_shuffle($str), 0, $keyLength);

	$checkkeys = $this->checkkeys($randStr);

	while($checkkeys == true){

		$randStr = substr(str_shuffle($str), 0, $keyLength);
		$checkkeys = $this->checkkeys($randStr);

	}

	return $randStr;
}
/*END GENERATE*/
/************************************************************************************************************************ */

   /*GENERATE RANDOM PASSWORD FOR USER LOGIN FOR THE FIRST TIME*/


   function checkPwdKeys($randPwd){

	$sql = $this->conn->query("SELECT * FROM users");

	while($row = $sql->fetch_assoc()){

		if ($row["user_password"] == $randPwd) {

			$keyExists = true;

			break;

		}else{

			$keyExists = false;
		}

	}

	return $keyExists;
}
/*----------------------------------------------------------------*/
function createRandomPwd(){

	$length = 8;
	$chars = "abcdefghijklmnopqrstuvwxz!@$&?0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$randPwd = substr(str_shuffle($chars), 0 , $length);

    //call function to check if the string already exist in the database

	$checkkeys = $this->checkPwdKeys($randPwd);
    while($checkkeys == true){

		$randPwd = substr(str_shuffle($chars), 0 , $length);
		$checkkeys = $this->checkPwdKeys($randPwd);

	}
	return $randPwd;
}
/********************************************************************************************************************** */
     /*SANITIZING STRING NEVER TRUST USERS*/

function sanitize($string_value){

  $string_value = get_magic_quotes_gpc() ? stripslashes($string_value) : $string_value;
  $string_value = function_exists("mysqli_real_escape_string") ? $this->conn->real_escape_string($string_value) : $this->conn->escape_string($string_value);

  return $string_value;
}


/********************************************************************************************************************** */
//send email function 24/11/19

public function sendEmail($email, $userID, $defPwd, $fname){
    
    $to = $email;
    $id = $userID;
    $pwd = $defPwd;
    $subject = "MIAAM LOGIN DETAILS";
    $link = "https://coffeecousinsmhd.co.za/ccadmin/login.php";
    $message = 'Hi '.$fname.', kindly use the following link: ' . $link . ' to access the admin panel.' . "\r\n" .
    'Your login ddetails are as follow' . "\r\n" . ' Coffee Cousins ID : ' . $id . ' and Password: ' . $pwd .  "\r\n" .
    'Please change your password after first login.' . "\r\n" . 'Regards' . "\r\n" . 'Coffee Cousins Management';
    $headers = 'From: no-reply@coffeecousins' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    $message = wordwrap($message, 70);
    
    
    
    if(mail($to, $subject, $message, $headers)){
        return "SUCCESS";
    }else{
        return "FAIL";
    }
}
/******************************************************************************************************* */
/*GENERATE UNIQUE PRODRUCT CODE*/


public function checkkeyId($randStr, $table_name, $key_column){
  $keyExists = false;    

   	$sql = $this->conn->query("SELECT * FROM $table_name");
	    while($row = $sql->fetch_assoc()){
		    if ($row[$key_column] == $randStr){
			      $keyExists = true;
			      break;
		    }else if ($row[$key_column] != $randStr || $row[$key_column] == ''){
		      	$keyExists = false;
	    	}
	}
	      return $keyExists;
}

/**--------------------------------------------------------------- */

public function generatekey($prefix, $table_name, $key_column){

	$keyLength = 16 ;
	$str = uniqid();
	$randStr = $prefix . substr(str_shuffle($str), 0, $keyLength);

	  $checkkeys = $this->checkkeyId($randStr, $table_name, $key_column);

	while($checkkeys == true){
		$randStr = $prefix . substr(str_shuffle($str), 0, $keyLength);
		$checkkeys = $this->checkkeyId($randStr, $table_name, $key_column);
	}

	return $randStr;
}
/*END GENERATE*/
/******************************************************************************************************** */
}//end User class

  $user = new User();
  