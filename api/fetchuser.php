<?php
 require_once '../app.php';
// RETRIEVE ALL STUDENTS IN THE DATABASE

   $sql = "SELECT * FROM students";
   $students = array();
   if ($result = $conn->query($sql)) {
       
       while ($row = $result->fetch_assoc()) {
           
           $students[] = $row;
       }

       echo json_encode($students);
   }else{
       http_response_code(404);
   }
   exit();