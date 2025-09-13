<?php
if (!defined('ABSPATH')) {
    exit;
}

// Function to send push notifications (Example using Firebase)
function dq_send_push_notification($title, $message, $token) {
    $url = 'https://fcm.googleapis.com/fcm/send';
    $apiKey = 'YOUR_FIREBASE_SERVER_KEY';

    $fields = [
        'to' => $token,
        'notification' => [
            'title' => $title,
            'body'  => $message,
        ]
    ];

    wp_remote_post($url, [
        'body'    => json_encode($fields),
        'headers' => [
            'Authorization' => 'key=' . $apiKey,
            'Content-Type'  => 'application/json'
        ]
    ]);
}
