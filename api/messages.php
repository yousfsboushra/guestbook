<?php
error_reporting(E_ALL);
session_start();
require('helper.php');

$token = (!empty($_POST['token']))? $_POST['token'] : '';
$recipient_id = (!empty($_POST['recipient_id']))? $_POST['recipient_id'] : '';
$message = (!empty($_POST['message']))? $_POST['message'] : '';
$reply_to = (!empty($_POST['reply_to']))? $_POST['reply_to'] : '0';
$message_id = (!empty($_POST['message_id']))? $_POST['message_id'] : '';
$api = (!empty($_POST['api']))? $_POST['api'] : '';

$error = 1;
$response = '';
if(in_array($api, array("create", "update", "delete", "reply", "list"))){
    if(isValidMessage($message, $api)){ // check if the message is valid
        $connection = connectToDatabase();
        if($user = findUserBytoken($connection, $token)){ // check if the user token is valid
            if(isValidRecipient($connection, $recipient_id, $api)){
                if($api == "list"){
                    $error = 0;
                    $response = listMyMessages($connection, $user);
                }else if($api == "create"){
                    if($messageObject = createMessage($connection, $message, $recipient_id, $user)){
                        $error = 0;
                        $response = 'Message has been sent';
                    }else{
                        $error = 1;
                        $response = 'Something went wrong';
                    }
                }else if($api == "reply"){
                    if($replyObject = isValidMessageId($connection, $reply_to)){
                        if(isValidReply($replyObject, $recipient_id, $user)){
                            if($messageObject = createMessage($connection, $message, $recipient_id, $user, $reply_to)){
                                $error = 0;
                                $response = 'Reply has been sent';
                            }else{
                                $error = 1;
                                $response = 'Something went wrong';
                            }
                        }else{
                            $error = 1;
                            $response = 'Something went wrong';
                        }
                    }else{
                        $error = 1;
                        $response = 'Message not found';
                    }
                }else if($api == "update"){
                    if($messageObject = isValidMessageId($connection, $message_id)){
                        if($response = updateMessage($connection, $messageObject, $message, $recipient_id, $user, $message_id)){
                            $error = 0;
                            $response = 'Message has been updated';
                        }else{
                            $error = 1;
                            $response = 'Something went wrong';
                        }
                    }else{
                        $error = 1;
                        $response = 'Message not found';
                    }
                }else if($api == "delete"){
                    if($messageObject = isValidMessageId($connection, $message_id)){
                        if($response = deleteMessage($connection, $messageObject, $user)){
                            $error = 0;
                            $response = 'Message has been deleted';
                        }else{
                            $error = 1;
                            $response = 'Something went wrong';
                        }
                    }else{
                        $error = 1;
                        $response = 'Message not found';
                    }
                }
            }else{
                $error = 1;
                $response = 'Invalid recipient';
            }
        }else{
            $error = 1;
            $response = 'Invalid user token';
        }
    }else{
        $error = 1;
        $response = 'Invalid message';
    }
}else{
    $error = 1;
    $response = 'Invalid call';
}


// Format result as json and return it
$result = new stdClass();
$result->error = $error;
$result->response = $response;
print json_encode($result);
die();

//Need security check
function isValidMessage($message, $api){
    if($api == "delete" || $api == "list"){
        return true;
    }
    if(!empty($message)){
        return true;
    }
    return false;
}

function isValidRecipient($connection, $recipient_id, $api){
    if($api == "delete" || $api == "list"){
        return true;
    }
    if($recipient = findUserById($connection, $recipient_id)){
        return $recipient;
    }
    return false;
}

function isValidMessageId($connection, $message_id){
    if($message_id == "0"){
        return true;
    }
    if(is_numeric($message_id)){
        $sqlQuery = sprintf('select * from messages where `id` = %d', $message_id);   
        if($result = mysqli_query($connection, $sqlQuery)){
            while ($row = mysqli_fetch_object($result)){
                return $row;
            }
        }
    }
    return false;
}

function isValidReply($replyObject, $recipient_id, $user){
    if(isset($replyObject->to_user_id) && is_numeric($replyObject->to_user_id) && 
    isset($replyObject->from_user_id) && is_numeric($replyObject->from_user_id) && 
    isset($user->id) && is_numeric($user->id) && is_numeric($recipient_id) &&
    $replyObject->to_user_id  == $user->id && $replyObject->from_user_id == $recipient_id){
        return true;
    }
    return false;
}

function listMyMessages($connection, $user){
    $messages = array();
    if(is_numeric($user->id)){
        $userId = $user->id;
        $sqlQuery = sprintf('select * from messages where `from_user_id` = %d or `to_user_id` = %d', $userId, $userId);
        if($result = mysqli_query($connection, $sqlQuery)){
            $users = listUsers($connection);
            while ($row = mysqli_fetch_object($result)){
                $contactId = ($row->from_user_id == $userId)? $row->to_user_id : $row->from_user_id;
                if(!isset($messages[$contactId])){
                    $messages[$contactId] = array(
                        'name' => $users[$contactId],
                        'messages' => array()
                    );
                }
                $messages[$contactId]['messages'][$row->id] = array(
                    'message' => htmlspecialchars($row->message),
                    'reply_to' => (is_numeric($row->reply_to))? $row->reply_to : "" ,
                    'from' => $row->from_user_id,
                    'to' => $row->to_user_id,
                    'replies' => array()
                );
            }
        }
    }
    return $messages;
}

function deleteMessage($connection, $messageObject, $user){
    if(isset($messageObject->id) && is_numeric($messageObject->id) && 
    isset($messageObject->from_user_id) && is_numeric($messageObject->from_user_id) && 
    isset($user->id) && is_numeric($user->id) && $messageObject->from_user_id == $user->id){
        $sqlQuery = sprintf('delete from messages where id = %d', $messageObject->id);
        if($result = mysqli_query($connection, $sqlQuery)){
            return $result;
        }
    }
    return false;
}

function updateMessage($connection, $messageObject, $message, $recipient_id, $user, $message_id){
    if(isValidMessage($message, 'update') && is_numeric($recipient_id) &&
    isset($messageObject->id) && is_numeric($messageObject->id) &&
    isset($messageObject->from_user_id) && is_numeric($messageObject->from_user_id) && 
    isset($user->id) && is_numeric($user->id) && $messageObject->from_user_id == $user->id){
        $message = mysqli_real_escape_string($connection, $message);
        $sqlQuery = sprintf('update messages set message = "%s", to_user_id = %d where id = %d', $message, $recipient_id, $messageObject->id);
        if($result = mysqli_query($connection, $sqlQuery)){
            return $result;
        }
    }
    return false;
}

function createMessage($connection, $message, $recipient_id, $user, $reply_to = "NULL"){
    if(isValidMessage($message, 'update') && is_numeric($recipient_id) &&
    isset($user->id) && is_numeric($user->id)){
        $message = mysqli_real_escape_string($connection, $message);
        $sqlQuery = sprintf('INSERT INTO messages (`from_user_id`, `to_user_id`, `message`, `reply_to`) VALUES (%d, %d, "%s", NULL)', $user->id, $recipient_id, $message);
        if(is_numeric($reply_to)){
            $sqlQuery = sprintf('INSERT INTO messages (`from_user_id`, `to_user_id`, `message`, `reply_to`) VALUES (%d, %d, "%s", %d)', $user->id, $recipient_id, $message, $reply_to);
        }
        if($result = mysqli_query($connection, $sqlQuery)){
            return $result;
        }
    }
    return false;
}
?>