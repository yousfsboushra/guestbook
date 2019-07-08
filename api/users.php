<?php
error_reporting(E_ALL);
session_start();
require('helper.php');

$api = (!empty($_POST['api']))? $_POST['api'] : '';
$token = (!empty($_POST['token']))? $_POST['token'] : '';

$error = 1;
$response = '';
$connection = connectToDatabase();
if($api == "list"){
    if($user = findUserBytoken($connection, $token)){ // check if the user token is valid
        $error = 0;
        $response = listUsers($connection, $token);
    }else{
        $error = 1;
        $response = 'Invalid token';
    }
}else{
    $error = 1;
    $response = 'Invalid call';
}
mysqli_close($connection);

// Format result as json and return it
$result = new stdClass();
$result->error = $error;
$result->response = $response;
print json_encode($result);
die();

?>