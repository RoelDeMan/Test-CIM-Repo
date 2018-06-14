<?php
if(isset($_COOKIE["PHPSESSID"])){
	session_id($_COOKIE["PHPSESSID"]);
}else{
	session_id(str_replace('.', '', $_SERVER['REMOTE_ADDR']) . generateRandomString());
}
session_start();

function generateRandomString($length = 20) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}


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

if(isset($_POST["newCookie"])){
	setcookie("PHPSESSID", str_replace('.', '', $_SERVER['REMOTE_ADDR']) . generateRandomString(), time()+3600, "", "roeldeman.nl/kvh");
}

//Get current user
if(isset($_POST["getCurrentUser"])){
    echo $_SESSION["email"];
}

if(isset($_POST['getUserInfo'])){
  $email = $_SESSION["email"];
    $query = "SELECT name, insertion, lastName, email FROM Users WHERE email = '$email'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Log uit
if(isset($_POST["logOut"])){
    session_destroy();
    setcookie("PHPSESSID", str_replace('.', '', $_SERVER['REMOTE_ADDR']) . generateRandomString(), time()+3600, "", "kwestievanhouding.nl");
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

//Set user authorized to see the page
if(isset($_POST["setCorrectSession"])){
      $_SESSION["correct"] = 'true';
      $isCorrect = $_SESSION["correct"];
      echo $isCorrect;
      return $isCorrect;
}

//Check if user is admin
if(isset($_POST["checkUserIsAdmin"])){
      $query = "SELECT admin FROM Users WHERE email = '".$_SESSION["email"]."'";
      $result = getQueryResult($dbConnection, $query);
      return makeJSONofResults($result);
}

//Get all users for the administrator
if(isset($_POST["getAllUsersForAdmin"])){
      $query = "SELECT name, insertion, lastName, email, authorized, recieverEmail FROM Users ORDER BY name ASC";
      $result = getQueryResult($dbConnection, $query);
      return makeJSONofResults($result);
}

//Set new authorize value at current email
if(isset($_POST["setAuthorizeStatus"])){
    $email = $_POST["email"];
    $authorized = $_POST["authorized"];
    $query = "UPDATE Users SET authorized = '$authorized' WHERE email = '$email'";
    $result = getQueryResult($dbConnection, $query);
}


//send once an email to the selected user when enable the authorize status
if(isset($_POST["recieveAuthorizedMail"])){
  $email = $_POST["email"];
  $emailFake = 'rjmdeman@gmail.com';
  $name = $_POST["name"];
  $url = $_POST["url"];
    $query = "UPDATE Users SET recieverEmail = 1 WHERE email = '$email'";
    $result = getQueryResult($dbConnection, $query);
      $headers = 'From: Samen in zicht <admin@kwestievanhouding.nl>' . "\r\n";
      $headers .='X-Mailer: PHP/' . phpversion();
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
  $message = " Hallo $name, <br><br> U heeft een account aangemaakt op de 'Kwestie van houding' website. <br>De administrator heeft uw toegang gegeven zodat u kan inloggen. <br><br> U kunt inloggen via: $url <br> Succes! <br><br> Met vriendelijk groet, <br> Administrator - Samen in zicht";
  mail($email, "Toegang - Samen in zicht", $message, $headers);
}


//Check if user exist and send Mail
if(isset($_POST["userExistAndSendMail"])){
    $email = $_POST["email"];
    $url = $_POST["url"];
    $query1 = "SELECT email FROM Users WHERE email = '$email'";
    $result1 = getQueryResult($dbConnection, $query1);
    $resultEmail = " ";
    $emptyString = " ";
    if ($result1->num_rows > 0) {
        while($row = $result1->fetch_assoc()) {
            $resultEmail = $row["email"];
        }
    }
    if (is_null($resultEmail) || empty($resultEmail) || !isset($resultEmail) || $resultEmail === $emptyString) {
        echo "false";
    }else{
          $headers = 'From: Samen in zicht <admin@kwestievanhouding.nl>' . "\r\n";
          $headers .='X-Mailer: PHP/' . phpversion();
          $headers .= "MIME-Version: 1.0\r\n";
          $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $generate = rand(1000,9999);
        $query2 = "UPDATE Users SET passwordCode = ".$generate." WHERE email = '$resultEmail'";
        $result2 = getQueryResult($dbConnection, $query2);
        $message = " Hallo, <br><br> Hierbij ontvangt u de code: $generate. <br> Op $url kunt u de code invoeren om toegang te krijgen om een nieuw wachtwoord te kunnen maken. <br><br><br> Met vriendelijk groet, <br> Administrator - Samen in zicht";
        mail($email, "Nieuw wachtwoord - Samen in zicht", $message, $headers);
        echo $resultEmail;
      }
}

if(isset($_POST["getCorrectPassCode"])){
  $email = $_POST["email"];
  $password = $_POST["newPassword"];
  $passCode = $_POST["passwordCode"];
  $query = "SELECT name, insertion, lastName, email FROM Users WHERE email = '".$email."' AND passwordCode = ".$passCode."";
  $result1 = getQueryResult($dbConnection, $query);
  $resultEmailCode = " ";
  if ($result1->num_rows > 0) {
      while($row = $result1->fetch_assoc()) {
          $resultEmailCode = $row["email"];
      }
  }
  if ($resultEmailCode === $email){
    $hashed_stamp = time();
    $hashed_password = hash('sha256', $password.'_'.$email.'+'.$hashed_stamp);
    $query2 = "UPDATE Users SET password = '$hashed_password', stamp = $hashed_stamp, passwordCode = null WHERE email = '$resultEmailCode'";
    $result2 = getQueryResult($dbConnection, $query2);
    echo 'true';
  }else{
    echo 'false';
  }
}


//Changing current password
if(isset($_POST["changePassword"])){
  $email = $_SESSION["email"];
  $currentPassword = $_POST["password"];
  $newPassword = $_POST["newPassword"];
  $query1 = "SELECT password, stamp FROM Users WHERE email = '".$email."'";
  $result1 = getQueryResult($dbConnection, $query1);
  $hashedPassword =  " ";
  $currentStamp = " ";
  if ($result1->num_rows > 0){
      while($row = $result1->fetch_assoc()) {
          $hashedPassword = $row["password"];
          $currentStamp = $row["stamp"];
      }
  }
  $hashed = hash('sha256', $currentPassword.'_'.$email.'+'.$currentStamp);
  if($hashed == $hashedPassword) {
    $hashedNewStamp = time();
    $hashedNewPassword = hash('sha256', $newPassword.'_'.$email.'+'.$hashedNewStamp);
        $query2 = "UPDATE Users SET password = '$hashedNewPassword', stamp = $hashedNewStamp WHERE email = '$email'";
        $result2 = getQueryResult($dbConnection, $query2);
      echo 'true';
  }else {
      echo 'false';
  }
}


//Sign up and add user
if(isset($_POST["addUser"])){
  $password = $_POST["password"];
  $email = $_POST["email"];
  $name = $_POST['name'];
  $insertion = $_POST["insertion"];
  $lastName = $_POST["lastName"];
  $url = $_POST["url"];
  $authorized_value = 0;
  $hashed_stamp = time();
  $hashed_password = hash('sha256', $password.'_'.$email.'+'.$hashed_stamp);
  $query1 = "SELECT email FROM Users where email = '$email'";
  $result1 = getQueryResult($dbConnection, $query1);
  $existEmail = " ";
  if ($result1->num_rows > 0) {
      while($row = $result1->fetch_assoc()) {
          $existEmail = $row["email"];
      }
  }
  if ($existEmail == $email){
    echo 'false';
  }else{
    $query = "INSERT INTO Users (email, password, name, lastName, insertion, stamp, authorized) VALUES ('".$email."','".$hashed_password."','".$name."','".$lastName."','".$insertion."','".$hashed_stamp."','".$authorized_value."')";
    $result = getQueryResult($dbConnection, $query);
    sendMailForActivation($name, $insertion, $lastName, $email, $url);
    echo 'true';
  }
}

//Sending a mail to the develop team
function sendMailForActivation($name, $insertion, $lastName, $email, $url){
//    $emailReciever = 'marieke.degreef@han.nl';
//	$emailRecieverSecond = 'annica.brummel@han.nl';
//	$emailRecieverRoel1 = 'rjmdeman@gmail.com';
//	$emailRecieverSecondRoel2 = 'roel.deman@han.nl';
    if (is_null($insertion) || empty($insertion) || !isset($insertion)) {
      $fullName = $name.' '.$lastName;
    }else {
      $fullName = $name.' '.$insertion.' '.$lastName;
    }
          $headers = 'From: Samen in zicht <admin@kwestievanhouding.nl>' . "\r\n";
      $headers .='X-Mailer: PHP/' . phpversion();
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
      $message = "Hallo, <br><br> '$fullName' wil graag een account aanmaken. <br><br> Ga naar het beheerpaneel via $url om het account/emailadres van $email te activeren. <br><br><br> Met vriendelijke groet, <br> Kwestie van houding";
      mail($emailReciever, "$fullName wil zich graag bij Samen in zicht aanmelden", $message, $headers);
	  mail($emailRecieverSecond, "$fullName wil zich graag bij Samen in zicht aanmelden", $message, $headers);
	  mail($emailRecieverRoel1, "$fullName wil zich graag bij Samen in zicht aanmelden", $message, $headers);
	  mail($emailRecieverSecondRoel2, "$fullName wil zich graag bij Samen in zicht aanmelden", $message, $headers);
}

//Get all users
if(isset($_POST["getUsers"])){
  $query = "SELECT * FROM Users";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get all videos
if(isset($_POST["getVideos"])){
  $query = "SELECT * FROM Videos WHERE deleted = 0";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get all categories
if(isset($_POST["getCategories"])){
  $query = "SELECT * FROM Categories";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get all cases
if(isset($_POST["getCases"])){
  $query = "SELECT * FROM Cases";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get all video's from one specific user
if(isset($_POST["getUserVideos"])){
	//echo session_id();
    $query = "SELECT v.id, v.videoUrl, v.title, v.description, v.email, v.caseId, c.name AS casename, count(vm.id) AS markerCount, (SELECT COUNT(*) FROM Questions qq INNER JOIN MarkerQuestions mqq ON qq.id = mqq.questionId INNER JOIN VideoMarkers vmm ON vmm.id = mqq.markerId  WHERE v.id = vmm.videoId) AS totalMarkers,
    (SELECT COUNT(*) FROM Questions q
    WHERE q.id NOT IN (
    SELECT mq.questionId
    FROM MarkerQuestions mq
    INNER JOIN VideoMarkers vm ON mq.markerId = vm.id WHERE v.id = vm.videoId
    GROUP BY vm.videoId
    )) AS openQuestionCount
    FROM Videos v INNER JOIN Cases c ON v.caseId = c.id LEFT OUTER JOIN VideoMarkers vm ON vm.videoId = v.id WHERE v.deleted = 0 AND v.email = '".$_SESSION["email"]."' GROUP BY v.id ORDER BY c.name, v.title ASC
    ";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get all video's from one specific user
if(isset($_POST["getSharedUserVideos"])){
    $query = "SELECT v.id, v.videoUrl, v.title, v.description, v.caseId, v.email, u.name, u.insertion, u.lastName, v.timestamp, count(vm.id) AS markerCount, (SELECT COUNT(*) FROM Questions qq INNER JOIN MarkerQuestions mqq ON qq.id = mqq.questionId INNER JOIN VideoMarkers vmm ON vmm.id = mqq.markerId  WHERE v.id = vmm.videoId) AS totalMarkers,
    (SELECT COUNT(*) FROM Questions q
    WHERE q.id NOT IN (
    SELECT mq.questionId
    FROM MarkerQuestions mq
    INNER JOIN VideoMarkers vm ON mq.markerId = vm.id WHERE v.id = vm.videoId
    GROUP BY vm.videoId
    )) AS openQuestionCount
    FROM Videos v LEFT OUTER JOIN VideoMarkers vm ON vm.videoId = v.id INNER JOIN Users u ON v.email = u.email
                WHERE v.deleted = 0 AND v.email IN
                (SELECT usv.ownerEmail FROM UserSharedVideos usv WHERE usv.viewerEmail = '".$_SESSION["email"]."')
                GROUP BY v.id
                ORDER BY v.email, v.timestamp DESC
    ";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get stats for questions on a video
if(isset($_POST["getVideoQuestionStats"])){
  $query = "SELECT q.id, q.question, q.category, COUNT(mq.questionId) as count
  FROM Questions q LEFT JOIN MarkerQuestions mq ON q.id = mq.questionId LEFT JOIN VideoMarkers vm ON mq.markerId = vm.id
  WHERE vm.videoId = ".$_POST["videoId"]."
  GROUP BY q.category
  UNION
  SELECT q.id, q.question, q.category, 0 FROM Questions q
  WHERE q.id NOT IN (SELECT q.id FROM Questions q LEFT JOIN MarkerQuestions mq ON q.id = mq.questionId LEFT JOIN VideoMarkers vm ON mq.markerId = vm.id
  WHERE vm.videoId = ".$_POST["videoId"]."
  GROUP BY q.category)
  ORDER BY id";
  $result = getQueryResult($dbConnection, $query);
  return makeJSONofResults($result);
}

//Get all video's from one specific user
if(isset($_POST["getSharedUserVideosCount"])){
    $query = "SELECT v.id, v.videoUrl, v.title, v.description, v.caseId, v.email, u.name, u.insertion, u.lastName, count(vm.id) AS markerCount,
    (SELECT COUNT(*) FROM Questions q
    WHERE q.id NOT IN (
    SELECT mq.questionId
    FROM MarkerQuestions mq
    INNER JOIN VideoMarkers vm ON mq.markerId = vm.id WHERE v.id = vm.videoId
    GROUP BY vm.videoId
    )) AS openQuestionCount
    FROM Videos v LEFT OUTER JOIN VideoMarkers vm ON vm.videoId = v.id INNER JOIN Users u ON v.email = u.email
                WHERE v.deleted = 0 AND v.timestamp > u.lastCheck AND v.email IN
                (SELECT usv.ownerEmail FROM UserSharedVideos usv WHERE usv.viewerEmail = '".$_SESSION["email"]."')
                GROUP BY v.id
                ORDER BY v.email, v.timestamp DESC
    ";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get last check on new shared videos
if(isset($_POST["getUserLastCheck"])){
  $query = "SELECT u.lastCheck FROM Users u WHERE u.email = '".$_SESSION["email"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get last check on new shared videos
if(isset($_POST["updateUserLastCheck"])){
  $query = "UPDATE Users SET lastCheck  = '".$_POST["timestamp"]."' WHERE email = '".$_SESSION["email"]."'";
    $result = getQueryResult($dbConnection, $query);
}

//Get video by id
if(isset($_POST["getVideoById"])){
  $query = "SELECT v.id, v.videoUrl, v.title, v.description, v.email, v.caseId, c.name AS casename FROM Videos v INNER JOIN Cases c ON v.caseId = c.id WHERE v.deleted = 0 AND v.id = '".$_SESSION["videoId"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Set video id
if(isset($_POST["setVideoId"])){
    $_SESSION["videoId"] = $_POST["videoId"];
}

//Get questions for a video
if(isset($_POST["getVideoQuestions"])){
    $query = "SELECT * FROM Questions";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get marker by id
if(isset($_POST["getMarkerById"])){
  $query = "SELECT vm.id, vm.time, vm.description, vm.date FROM VideoMarkers vm WHERE vm.id = '".$_POST["markerId"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get marker with additional and correct question
if(isset($_POST["getMarkerQuestions"])){
    $query = "SELECT q.id, q.question, q.categoryX, q.categoryY, q.infoId, q.category, mq.markerId FROM Questions q INNER JOIN MarkerQuestions mq ON q.id = mq.questionId WHERE mq.markerId = '".$_POST["markerId"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get all markers for one specific video
if(isset($_POST["getVideoMarkers"])){
  $query = "SELECT * FROM VideoMarkers vm WHERE videoId = '".$_SESSION["videoId"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Add new marker to video
if(isset($_POST["addMarkerToVideo"])){
  $query1 = "INSERT INTO VideoMarkers (videoId, time, description, date) VALUES ('".$_POST["videoId"]."','".$_POST["time"]."','".mysqli_real_escape_string($dbConnection, $_POST['description'])."','".$_POST["date"]."')";
    $result1 = getQueryResult($dbConnection, $query1);

    $questions = explode("-", $_POST["questions"]);
    for($i = 0; $i < count($questions); $i++){
        $query2 = "INSERT INTO MarkerQuestions (markerId, questionId) VALUES ((SELECT vm.id FROM VideoMarkers vm
            WHERE videoId = '".$_POST["videoId"]."' AND time = '".$_POST["time"]."' AND description = '".mysqli_real_escape_string($dbConnection, $_POST['description'])."' AND date = '".$_POST["date"]."'),
            '".$questions[$i]."')";
        $result2 = getQueryResult($dbConnection, $query2);
    }
}

//Delete marker from video
if(isset($_POST["deleteMarkerFromVideo"])){
  $query = "DELETE FROM VideoMarkers WHERE id = '".$_POST["markerId"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Edit marker
if(isset($_POST["editMarker"])){
  $query = "UPDATE VideoMarkers SET description = '".mysqli_real_escape_string($dbConnection, $_POST['description'])."' WHERE id = '".$_POST["markerId"]."'";
    $result = getQueryResult($dbConnection, $query);
}

//Comment on a marker
if(isset($_POST["commentOnMarker"])){
  $query = "INSERT INTO MarkerComments (markerQuestionId, message, date, email) VALUES ('".$_POST["markerQuestionId"]."','".mysqli_real_escape_string($dbConnection, $_POST['message'])."','".$_POST["date"]."','".$_SESSION["email"]."')";
  $result = getQueryResult($dbConnection, $query);

  $query2 = "SELECT u.name, u.insertion, u.lastName FROM Users u WHERE u.email = '".$_SESSION["email"]."'";
  $result2 = getQueryResult($dbConnection, $query2);

  $viewerName =  "";
    if ($result2->num_rows > 0){
        while($row = $result2->fetch_assoc()) {
        $insertion = $row["insertion"];
        if (is_null($insertion) || empty($insertion) || !isset($insertion)) {
            $viewerName = $row["name"] . ' ' . $row["lastName"];
        }else {
            $viewerName = $row["name"] . ' ' . $row["insertion"] . ' ' . $row["lastName"];
        }
        }
    }

  $query3 = "SELECT v.email, v.title, u.name, u.insertion, u.lastName FROM Videos v INNER JOIN Users u ON u.email = v.email
        INNER JOIN VideoMarkers vm ON vm.videoId = v.id INNER JOIN MarkerQuestions mq ON mq.markerId = vm.id
        INNER JOIN MarkerComments mc ON mc.markerQuestionId = mq.id
        WHERE mc.markerQuestionId = '".$_POST["markerQuestionId"]."'
        GROUP BY mc.markerQuestionId";
  $result3 = getQueryResult($dbConnection, $query3);

  $userName =  "";
  $videoTitle = "";
  $email = "";
    if ($result3->num_rows > 0){
        while($row = $result3->fetch_assoc()) {
        $insertion2 = $row["insertion"];
        if (is_null($insertion2) || empty($insertion2) || !isset($insertion2)) {
            $userName = $row["name"] . ' ' . $row["lastName"];
        }else {
            $userName = $row["name"] . ' ' . $row["insertion"] . ' ' . $row["lastName"];
        }
      $email = $row["email"];
      $videoTitle = $row["title"];
        }
    }

  if($userName != $viewerName){
    $headers = 'From: Samen in zicht <admin@kwestievanhouding.nl>' . "\r\n";
    $headers .='X-Mailer: PHP/' . phpversion();
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $message = " Hallo '$userName', <br><br>'$viewerName' heeft een nieuwe reactie geplaatst bij uw video met de titel '$videoTitle'.<br> U kunt de video bekijken op www.kwestievanhouding.nl <br><br><br> Met vriendelijk groet, <br> Administrator - Samen in zicht";
    mail($email, "Reactie - Samen in zicht", $message, $headers);
  }
  return makeJSONofResults($result);
}

//Get the replies from a simgle marker
if(isset($_POST["getMarkerComments"])){
    $query = "SELECT mc.id, mq.id, mc.message, mc.date, mc.email, u.name, u.lastName, u.insertion, mq.markerId, mq.questionId FROM MarkerComments mc
              INNER JOIN MarkerQuestions mq ON mc.markerQuestionId = mq.id INNER JOIN Users u ON u.email = mc.email
              WHERE mq.questionId = '".$_POST["markerQuestionId"]."' AND mq.markerId = '".$_POST["markerId"]."' ORDER BY mc.date DESC";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get the reference for a marker question and it's comments
if(isset($_POST["getMarkerCommentQuestionLink"])){
    $query = "SELECT * FROM MarkerQuestions mq
              WHERE mq.questionId = '".$_POST["markerQuestionId"]."' AND mq.markerId = '".$_POST["markerId"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Get all users that video's are NOT with
if(isset($_POST["getMyShares"])){
    $query = "SELECT usv.viewerEmail, u.name, u.insertion, u.lastName FROM UserSharedVideos usv INNER JOIN Users u ON usv.viewerEmail = u.email WHERE usv.ownerEmail = '".$_SESSION["email"]."'";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Add user to network
if(isset($_POST["addSharedUser"])){
    $query = "INSERT INTO UserSharedVideos (ownerEmail, viewerEmail) VALUES ('".$_SESSION["email"]."','".$_POST["viewerEmail"]."')";
    $result = getQueryResult($dbConnection, $query);

  $query2 = "SELECT u.name, u.insertion, u.lastName FROM Users u WHERE u.email = '".$_POST["viewerEmail"]."'";
  $result2 = getQueryResult($dbConnection, $query2);

  $viewerName =  "";
    if ($result2->num_rows > 0){
        while($row = $result2->fetch_assoc()) {
        $insertion = $row["insertion"];
        if (is_null($insertion) || empty($insertion) || !isset($insertion)) {
            $viewerName = $row["name"] . ' ' . $row["lastName"];
        }else {
            $viewerName = $row["name"] . ' ' . $row["insertion"] . ' ' . $row["lastName"];
        }
        }
    }

  $query3 = "SELECT u.name, u.insertion, u.lastName FROM Users u WHERE u.email = '".$_SESSION["email"]."'";
  $result3 = getQueryResult($dbConnection, $query3);
    $userName =  "";
    if ($result3->num_rows > 0){
      while($row = $result3->fetch_assoc()) {
      $insertion = $row["insertion"];
      if (is_null($insertion) || empty($insertion) || !isset($insertion)) {
            $userName = $row["name"] . ' ' . $row["lastName"];
        }else {
            $userName = $row["name"] . ' ' . $row["insertion"] . ' ' . $row["lastName"];
        }
    }
  }

  $email = $_POST["viewerEmail"];
  $headers = 'From: Samen in zicht <admin@kwestievanhouding.nl>' . "\r\n";
  $headers .='X-Mailer: PHP/' . phpversion();
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
  $message = " Hallo '$viewerName', <br><br>U bent aan mijn netwerk toegevoegd.<br> U kunt mijn video's bekijken op www.kwestievanhouding.nl <br><br><br> Met vriendelijk groet, <br> '$userName' - Samen in zicht";
  mail($email, "Netwerk - Samen in zicht", $message, $headers);
}

//Remove user from network
if(isset($_POST["deleteSharedUser"])){
    $query = "DELETE FROM UserSharedVideos WHERE ownerEmail = '".$_SESSION["email"]."' AND viewerEmail = '".$_POST["viewerEmail"]."'";
    $result = getQueryResult($dbConnection, $query);
}

//Get all users that video's are shared with
if(isset($_POST["getNotShared"])){
    $query = "SELECT u.email, u.name, u.insertion, u.lastName FROM Users u WHERE u.email  != '".$_SESSION["email"]."' AND u.email NOT IN
                (SELECT usv.viewerEmail FROM UserSharedVideos usv WHERE usv.ownerEmail = '".$_SESSION["email"]."')";
    $result = getQueryResult($dbConnection, $query);
    return makeJSONofResults($result);
}

//Remove video from user
if(isset($_POST["RemoveVideoFromUser"])){
  $query = "DELETE FROM UserVideos WHERE email = '".$_POST["email"]."' AND videoId = '".$_POST["videoId"]."'";
    $result = getQueryResult($dbConnection, $query);
}

//Add new video
if(isset($_POST["addVideo"])){
  $fullPath = getVideoDataInsert();

  $query1 = "SELECT * FROM Cases WHERE name = '".$_POST["videoCaseId"]."'";
  $result1 = getQueryResult($dbConnection, $query1);

  if ($result1->num_rows == 0) {
      $query2 = "INSERT INTO Cases (name) VALUES ('".$_POST["videoCaseId"]."')";
      $result2 = getQueryResult($dbConnection, $query2);
  }

  $query3 = "INSERT INTO Videos (videoUrl, title, description, email, caseId, timestamp) VALUES ('".$fullPath."','".mysqli_real_escape_string($dbConnection, $_POST['videoTitle'])."','".mysqli_real_escape_string($dbConnection, $_POST['videoDescription'])."','".$_SESSION["email"]."', (SELECT c.id FROM Cases c WHERE c.name = '".$_POST["videoCaseId"]."'), '".$_POST["timestamp"]."'
  )";
  $result3 = getQueryResult($dbConnection, $query3);

  $queryC = "DELETE FROM Videos WHERE deleted = 0 AND timestamp = 0";
  $resultC = getQueryResult($dbConnection, $queryC);

  $queryCC = "DELETE FROM Cases WHERE name = ''";
  $resultCC = getQueryResult($dbConnection, $queryCC);

  $query4 = "SELECT v.id FROM Videos v WHERE deleted = 0 AND videoUrl = '".$fullPath."' AND title = '".mysqli_real_escape_string($dbConnection, $_POST['videoTitle'])."' AND email = '".$_SESSION["email"]."' AND
    description = '".mysqli_real_escape_string($dbConnection, $_POST['videoDescription'])."' AND caseId =  (SELECT c.id FROM Cases c WHERE c.name = '".$_POST["videoCaseId"]."') ";
  $result4 = getQueryResult($dbConnection, $query4);
  return makeJSONofResults($result4);
}

//Add new video
if(isset($_POST["addVideoTest"])){
// $ftp_server = "ftp.kwestievanhouding.nl";
// $ftp_user_name = "kvhadmin";
// $ftp_user_pass = "L@ctoraat1!020";
// $ftp_directory = 'httpdocs/videos'; // leave blank
// $ftp_source_file_name = $_FILES['newVideo']['tmp_name'];
// $ftp_dest_file_name = "testvideo.mp4";
// if( ftp_file( $ftp_server, $ftp_user_name, $ftp_user_pass, $ftp_source_file_name, $ftp_directory, $ftp_dest_file_name) ){
// 	$temp_file = tempnam(sys_get_temp_dir(), 'Tux');
// 	echo $temp_file;
//   echo "Success: FTP'd data\n";
// } else {
//   echo "Error: Could not FTP data.\n";
// }

}

function ftp_file( $ftpservername, $ftpusername, $ftppassword, $ftpsourcefile, $ftpdirectory, $ftpdestinationfile )
{
  $conn_id = ftp_connect($ftpservername);
  if ( $conn_id == false )
  {
    echo "FTP open connection failed to $ftpservername \n" ;
    return false;
  }
  $login_result = ftp_login($conn_id, $ftpusername, $ftppassword);
  if ((!$conn_id) || (!$login_result)) {
    echo "FTP connection has failed!\n";
    echo "Attempted to connect to " . $ftpservername . " for user " . $ftpusername . "\n";
    return false;
  } else {
    echo "Connected to " . $ftpservername . ", for user " . $ftpusername . "\n";
  }
  if ( strlen( $ftpdirectory ) > 0 )
  {
    if (ftp_chdir($conn_id, $ftpdirectory )) {
      echo "Current directory is now: " . ftp_pwd($conn_id) . "\n";
    } else {
      echo "Couldn't change directory on $ftpservername\n";
      return false;
    }
  }
  ftp_pasv ( $conn_id, true ) ;
  $upload = ftp_put( $conn_id, $ftpdestinationfile, $ftpsourcefile, FTP_ASCII );
  echo '--- Source:' . $ftpsourcefile . '--- (Als deze source leeg is betekent dit dat de file niet in de tmp map (standaard upload locatie) is gekomen)';
  echo '--- Destination:' . $ftpdestinationfile . '---';
  if (!$upload) {
    echo "$ftpservername: FTP upload has failed!\n";
    return false;
  } else {
    echo "Uploaded " . $ftpsourcefile . " to " . $ftpservername . " as " . $ftpdestinationfile . "\n";
  }
  ftp_close($conn_id);
  return true;
}

//Check video rules
if(isset($_POST["checkVideoRules"])){
  // if($_FILES["newVideo"]["type"] == "video/mp4"){
  //   if($_FILES["newVideo"]["size"] < 26214400){
  //     echo "true";
  //   }else{
  //     echo "De video is te groot";
  //   }
  // }else{
  //     echo "De video is geen MP4";
  // }
}


//Delete existing video
if(isset($_POST["deleteVideo"])){
  $query = "UPDATE Videos SET deleted = 1 WHERE id = '".$_POST['videoId']."'";
    $result = getQueryResult($dbConnection, $query);
}

//Get the video that was uploaded
function getVideoDataInsert(){
    $fullPath = '';
    if($_FILES['newVideo']['name']){
        $newDirectory = 'videos';
        $newFileName = $_FILES['newVideo']['name'];
        $fullPath = $newDirectory . '/' . generateRandomString() . time() . '-' . $newFileName;
        uploadImage($fullPath);
    }
    return $fullPath;
}

//Get upload directory
function getDirectory(){
    $splitPart = explode("/", getcwd());
    $lastPart = array_pop($splitPart);
    $baselink = implode("/", $splitPart);
    return $baselink;
}

//Upload image for the suggestion
function uploadImage($fullPath){
   move_uploaded_file($_FILES['newVideo']['tmp_name'], getDirectory() . '/' . $fullPath);
}

//Execute query's
function getQueryResult($con, $query){
  $result = mysqli_query($con, $query);
  if (!$result) {
    die('Invalid query: ' . mysqli_error() . $query);
  }
  return $result;
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
