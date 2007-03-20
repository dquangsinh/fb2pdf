<?php
require_once 'awscfg.php';
require_once 'db.php';
require_once 'utils.php';

global $secret;
global $dbServer, $dbName, $dbUser, $dbPassword;

$password = trim($_POST['pass']);
$email    = trim($_POST['email']);
$key      = trim($_POST['key']);
$status   = trim($_POST['status']);

error_log("FB2PDF INFO. Callback: password=$password, key=$key, status=$status, email=$email"); 

// check parameters
if (!$key)
{
    error_log("FB2PDF ERROR. Callback: Missing or wrong parameter key"); 
    send_response("400 Bad Request", "Missing or wrong parameter key");
    die;
}

if ($status != "r" and $status != "e")
{
    error_log("FB2PDF ERROR. Callback: Missing or wrong parameter status"); 
    send_response("400 Bad Request", "Missing or wrong parameter status");
    die;
}

// check password
if ($password != md5($secret . $key))
{
    error_log("FB2PDF ERROR. Callback: Incorrect password"); 
    send_response("400 Bad Request", "Incorrect password");
    die;
}

// update status in the DB
$db = new DB($dbServer, $dbName, $dbUser, $dbPassword);
if (!$db->updateBookStatus($key, $status))
    error_log("FB2PDF ERROR. Callback: Unable to update book status. Key=$key"); 


// send email to user
if ($email)
{
    $statusUrl = get_page_url("status.php?id=$key");
    
    $subject = "Your book";
    
    $message = "<html><body>";
    
    if ($status == "r")
        $message .= "Ваша книга была успешно сконвертированна.";
    else if ($status == "e")
        $message .= "При конвертации Вашей книги произошла ошибка.";
    
    $message .= "<br><a href=\"$statusUrl\">Посмотреть результат конвертации</a>";
    $message .= "</body></html>";

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n"; 
    $headers .= 'From: FB2PDF <noreply@codeminders.com>' . "\r\n";
    
    mail($email, $subject, $message, $headers);
}

send_response("200 OK", "");

function send_response($httpCode, $message)
{
    header("HTTP/1.0 $httpCode");
    header('Content-type: text/html');    
    if ($message)
        echo "<html><body>$message</body></html>";
}

?>