<?php

class Database{
    
    public $conn;
/********************************************************************** */
    public function __construct(){

        require_once "constant.php";

        $this->conn = new mysqli(HOST, USER, PASSWORD, DATABASE);

        if($this->conn)
         return $this->conn;
        else
         return "DATABASE CONNECTION FAILED";
    }
/*********************************************************************** */
}//END CLASS


 $db = new Database();

if(!$db->conn->connect_error)
  echo "connected";
else die($db->conn->connect_error);

 // AppUrl = 'http://eglrdc.org/mhdmiddleone/api/'; // live link