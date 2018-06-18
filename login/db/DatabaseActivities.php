<?php

include 'Constants.php';
include 'DatabaseConnection.php';

$dbConnection = getDatabaseConnection();

//Authenticate user after login
if(isset($_POST["authenticateUser"])){
    $password = $_POST["password"];
    $email = $_POST["email"];
    $query = "SELECT * from Users where email = '".$email."'";
    $result = getQueryResult($dbConnection, $query);
    $hashed_password = "";
    $hashed_stamp = "";
      if ($result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              $hashed_password = $row["password"];
              $hashed_stamp = $row["stamp"];
              $authorized_value = $row["authorized"];
          }
      }
        $hashed = hash('sha256', $password.'_'.$email.'+'.$hashed_stamp);
        if($hashed == $hashed_password) {
          if($authorized_value == 0){
            echo 'unAuthorized';
          }else if($authorized_value == 1){
            $_SESSION["email"] = $email;
            echo 'true';
          }
        }else {
            echo 'false';
        }
}


//Check if user is already logged in
if(isset($_POST["checkLoggedIn"])){
    $isLoggedIn = 'false';
    if(isset($_SESSION["email"])){
      if(isset($_SESSION["correct"])){
        $isLoggedIn = 'true';
        //unset($_SESSION["correct"]);
      }else{
        $isLoggedIn = 'false';
      }
    }
    echo $isLoggedIn;
    //echo $_SESSION["correct"];
    return $isLoggedIn;
}


//Make JSON of SQL result to send back to Javascript
function makeJSONofResults($result){
  $output = [];
  while ($e = mysqli_fetch_assoc($result)) {
    $output[] = $e;
  }

  mysqli_free_result($result);
  print json_encode($output);
}
?>
