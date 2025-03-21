<?php
require_once '../vendor/autoload.php';

// Twilio credentials
$sid = "ACb209fff76b6ed7af0c65d0099e8a6276";
$token = "dc72153ba59680664c2f080268854008";

// Create Twilio client
$twilio = new \Twilio\Rest\Client($sid, $token);

// Send message using the format provided
$message = $twilio->messages->create(
    "whatsapp:" . $phone_number, // to
    array(
        "from" => "whatsapp:+12514283965", // Your Twilio WhatsApp number
        "body" => "Your subscription is expired. Please renew your membership to continue enjoying our services."
    )
);
?>