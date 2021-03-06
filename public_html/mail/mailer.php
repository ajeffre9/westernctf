<?php
/**
 * Created by PhpStorm.
 * User: harrisonchow
 * Date: 11/11/15
 * Time: 12:23 AM
 */
$getPost = (array) json_decode(file_get_contents('php://input'));
require '../../vendor/autoload.php';

//$sendTo = "abcabc@test.com";
//$sendToName = "Harrison Chow";
$sendTo = $getPost["email"];
if ($sendTo == "") {
    echo json_encode(["result" => "failed", "message" => "The email is invalid."]);
    exit;
}
//if ($sendToName == "") $sendToName = "none";

//$sendFrom = "info@westerncyber.club";
$sendFrom = $getPost["from"];
if ($getPost["type"] == "from") {
    $sendFromName = $getPost["fullName"];
    $sendToName = "Western Cyber Security Club";
} else {
    $sendFromName = "Western Cyber Security Club";
    $sendToName = $getPost["fullName"];
}

$sendGridUsr = "app43353028@heroku.com";
$sendGridPassword = "khpoaec44366";
$sendGridApi = "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";

// Add user to contacts
$url = "https://api.sendgrid.com/v3/contactdb/recipients";
$data = new stdClass();
$data->email = $sendTo;
$data->first_name = $sendToName;
$data->last_name = '';

// Set email body and subject
if ($getPost["message"] == null || $getPost["message"] == undefined || $getPost["message"] == "")
    $emailBody = file_get_contents("emails/welcome-message.html");
else
    $emailBody = $getPost["message"];

if ($getPost["emailSubject"] == null || $getPost["emailSubject"] == undefined || $getPost["emailSubject"] == "")
    $emailSubject = "Welcome to Western Security Club";
else
    $emailSubject = $getPost["emailSubject"];

// Check for subscribe type
if ($getPost["type"] == "subscribe") {
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . $sendGridApi . "\r\n",
            'method' => 'POST',
            'content' => "[" . json_encode($data) . "]"
        )
    );
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

// Add user to subscription list
    if ($response != "") {
        $response = json_decode($response);
        if ($response->error_count == 1) {
            echo json_encode(["result" => "failed", "message" => "The email is invalid."]);
            exit;
        }
        if ($response->new_count == 0) {
            echo json_encode(["result" => "failed", "message" => "The email is already in the list."]);
            exit;
        }

        $usrId = $response->persisted_recipients;
        $usrId = $usrId[0];
        $listId = "17788";
        $url = "https://api.sendgrid.com/v3/contactdb/lists/" . $listId . "/recipients/" . $usrId;

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . $sendGridApi . "\r\n",
                'method' => 'POST'
            ),
        );
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
    } else {
        echo json_encode(["result" => "failed", "message" => "The server has encountered an error."]);
        exit;
    }

    $sendGridTemplateId = "658b13d5-b11e-4e86-b274-39a9b829ea87";

    $sendgrid = new SendGrid($sendGridApi);

    $message = new SendGrid\Email();
    $message
        ->addTo($sendTo, $sendToName)
        ->setFrom($sendFrom)
        ->setFromName($sendFromName)
        ->setSubject($emailSubject)
        ->setCategory("Subscription")
        ->setHtml($emailBody)
        ->setTemplateId($sendGridTemplateId);

    try {
        $sendgrid->send($message);
    } catch (SendGrid\Exception $e) {
        echo json_encode(["result" => "failed", "message" => "The email failed to send. Check console for dump.", "exception" => $e]);
        exit;
    }
    echo json_encode(["result" => "success", "message" => "The email has been sent."]);
} else {
    $sendgrid = new SendGrid($sendGridApi);

    $message = new SendGrid\Email();
    $message
        ->addTo($sendTo, $sendToName)
        ->setFrom($sendFrom)
        ->setFromName($sendFromName)
        ->setSubject($emailSubject)
        ->setCategory("Communication")
        ->setHtml($emailBody);

    try {
        $sendgrid->send($message);
    } catch (SendGrid\Exception $e) {
        echo json_encode(["result" => "failed", "message" => "The email failed to send. Check console for dump.", "exception" => json_encode($e)]);
        exit;
    }
    echo json_encode(["result" => "success", "message" => "The message has been sent."]);
}
?>