<?php
include '../api_key.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'];

    $apiKey = OPENAI_API_KEY;

    $data = [
        'model' => 'gpt-3.5',
        'messages' => [
            ['role' => 'system', 'content' => 'Du er en som elsker krypto og vil prøve å få brukeren til å sjekke ut en av tjenestene til Robin Kopperud'],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.8,
        'max_tokens' => 150,
        'top_p' => 0.7,
        'frequency_penalty' => 1,
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
