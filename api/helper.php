<?php
error_reporting(E_ALL);
require('config.php');

function isvalidUsername($username){
    $valid = preg_match('/([a-z]+[0-9a-z_\-\.]+)/', $username, $matches); // Check if username is valid
    
    // Return true if username is valid and it's length is more than 3
    if(strlen($username) >= 4 && isset($matches[0]) && $matches[0] == $username){
        return true;
    }
    return false;
}

function isvalidPassword($password){
    $uppercase = preg_match('/[A-Z]/', $password); // Check for uppercase letter
    $lowercase = preg_match('/[a-z]/', $password); // Check for lowercase letter
    $number    = preg_match('/[0-9]/', $password); // Check for number
    $specialChars = preg_match('/[^\w]/', $password); // Check for special charachters
    
    // return true if all types exist and length is more than 5
    if(strlen($password) >= 6 && $uppercase && $lowercase && $number && $specialChars) {
        return true;
    }
    return false;
}

function isValidToken($token){
    $valid = preg_match('/([0-9a-z]+)/', $token, $matches); // Check if token is valid
    if(isset($matches[0]) && $matches[0] == $token){
        return true;
    }
    return false;
}

function connectToDatabase(){
    // Create connection
    $connection = mysqli_connect($_SERVER['dbServerName'], $_SERVER['dbUsername'], $_SERVER['dbPassword'], $_SERVER['dbName']);
    // Check connection
    if (!$connection) {
        return false;
    }
    return $connection;
}

function findUserBytoken($connection, $token){
    if(isValidToken($token)){
        $sqlQuery = sprintf('select * from users where `token` = "%s"', $token);
        if($result = mysqli_query($connection, $sqlQuery)){
            while ($row = mysqli_fetch_object($result)){
                return $row;
            }
        }
    }
    return false;
}
function findUserById($connection, $userId){
    if(is_numeric($userId)){
        $sqlQuery = sprintf('select * from users where `id` = %d', $userId);
        if($result = mysqli_query($connection, $sqlQuery)){
            while ($row = mysqli_fetch_object($result)){
                return $row;
            }
        }
    }
    return false;
}
function findUserByUsername($connection, $username){
    if(isvalidUsername($username)){
        $sqlQuery = sprintf('select * from users where `username` = "%s"', $username);
        if($result = mysqli_query($connection, $sqlQuery)){
            while ($row = mysqli_fetch_object($result)){
                return $row;
            }
        }
    }
    return false;
}

function listUsers($connection, $token = ""){
    $users = array();
    $sqlQuery = sprintf('select * from users');
    if(!empty($token) && isValidToken($token)){
        $sqlQuery = sprintf('select * from users where `token` <> "%s"', $token);
    }
    if($result = mysqli_query($connection, $sqlQuery)){
        while ($row = mysqli_fetch_object($result)){
            $users[$row->id] = $row->username;
        }
        return $users;
    }
    return false;
}
?>