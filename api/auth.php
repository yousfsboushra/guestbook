<?php
error_reporting(E_ALL);
session_start();
require('helper.php');

$api = (!empty($_POST['api']))? $_POST['api'] : '';
$username = (!empty($_POST['username']))? $_POST['username'] : '';
$password = (!empty($_POST['password']))? $_POST['password'] : '';

$error = 1;
$response = '';
if(isvalidUsername($username)){
    if(isvalidPassword($password)){
        $connection = connectToDatabase();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // hash password
        if($api == "signup"){
            $userAdded = signup($connection, $username, $hashedPassword);
            if($userAdded){
                $error = 0;
                $response = "User added successfully";
            }else{
                $error = 1;
                $response = "Something went wrong, try again";
            }
        }else if($api == "login"){
            $user = login($connection, $username, $password);
            if($user){
                $error = 0;
                $response = $user;
            }else{
                $error = 1;
                $response = "Wrong username or password";
            }
        }
        mysqli_close($connection);
    }else{
        $error = 1;
        $response = 'Invalid password';
    }
}else{
    $error = 1;
    $response = 'Invalid username';
}

// Format result as json and return it
$result = new stdClass();
$result->error = $error;
$result->response = $response;
print json_encode($result);
die();

function login($connection, $username, $password){
    $user = findUserByUsername($connection, $username);
    if(isset($user->password)){
        if(password_verify($password, $user->password)){
            $token = bin2hex(random_bytes(100));
            $sqlQuery = sprintf('update users set token = "%s" where `username` = "%s"', $token, $username);
            if($result = mysqli_query($connection, $sqlQuery)){
                unset($user->password);
                unset($user->created_at);
                $user->token = $token;
                return $user;
            }
        }
    }
    return false;
}

function signup($connection, $username, $hashedPassword){
    $sqlQuery = sprintf('INSERT INTO users (`username`, `password`) VALUES ("%s", "%s")', $username, $hashedPassword);
    print $sqlQuery;
    if($result = mysqli_query($connection, $sqlQuery)){
        return $result;
    }
    return false;
}
?>