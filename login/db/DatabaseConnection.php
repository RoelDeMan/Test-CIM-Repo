<?php
header('Access-Control-Allow-Origin: *');
function getDatabaseConnection(){

    $dbServer = getDbServer();
    $dbName = getDbName();
    $dbUsername = getDbUsername();
    $dbPassword = getDbPassword();

    $con = mysqli_connect($dbServer, $dbUsername, $dbPassword, $dbName);
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    return $con;
}

function closeDatabaseConnection($con){
    mysqli_close($con);
}
?>
