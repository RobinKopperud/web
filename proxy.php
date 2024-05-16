<?php
// Include the API key file from the parent directory
include '../api_key.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'];

    $apiKey = OPENAI_API_KEY;

    $data = [
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'Du elsker AI og vil prøve å få brukeren til å sjekke ut min AI musikk på fransk for å vise hvor bra det funker'],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 1,
        'max_tokens' => 100,
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(['error' => curl_error($ch)]);
    } else {
        echo $response;
    }
    curl_close($ch);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
