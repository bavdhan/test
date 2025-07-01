<?php
$verify_token = "whatsappToken123"; // Must match your Meta App webhook config
$accessToken = "EAAPhtFb1frUBO4TpnqltYaBRBag3x8LiEP5fQ7WrtWstUoeDZABNjtwwsZAuazBx4CRO0QkO80ZBcOLoZB6wyxZCjrVU9c2hMwecsz0RDZCWf3tot1iZBCw5LBkbqijwsUHy1geYIrBh72jfgGoIGzv1kCQ9iIVTFIkZAnp0YaKDnMugZCFyPqdrVGDckZBQXLC0UHbYjCQaHkgpXjDWqzlEMKelZCwcC19sOvXcuinS6nEtai0RQZDZD"; // Replace
$phoneNumberId = "692052867327707"; // Replace

// Step 1: Webhook verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $verify_token) {
        echo $challenge;
        exit;
    } else {
        http_response_code(403);
        echo "Invalid token or mode";
        exit;
    }
}

// Step 2: Handle incoming messages
$input = file_get_contents("php://input");
file_put_contents("webhook_log.txt", $input . PHP_EOL, FILE_APPEND); // Log for debug

$data = json_decode($input, true);

if (
    isset($data['entry'][0]['changes'][0]['value']['messages'][0]['from']) &&
    isset($data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'])
) {
    $from = $data['entry'][0]['changes'][0]['value']['messages'][0]['from'];
    $msg = $data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];

    // Save message to file
    $entry = date('Y-m-d H:i:s') . " | $from: $msg" . PHP_EOL;
    file_put_contents("chat_store.txt", $entry, FILE_APPEND);

    // Auto-reply to the user
    $replyText = "Hi! You said: $msg";

    $payload = [
        "messaging_product" => "whatsapp",
        "to" => $from,
        "type" => "text",
        "text" => ["body" => $replyText]
    ];

    $ch = curl_init("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
}

http_response_code(200);
echo "EVENT_RECEIVED";
