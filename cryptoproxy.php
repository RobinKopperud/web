<?php
include '../api_key.php'; // Adjust the path as needed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'];

    $apiKey = OPENAI_API_KEY;

    $data = [
        'model' => 'gpt-3.5-turbo-0125', // Adjust the model if needed
        'messages' => [
            ['role' => 'system', 'content' => 'Du er spesialisert innefor krypto og vil prøve å få brukeren til å teste ut en av mine Tjenester som ligger rett over eller til venstre. Robin Kopperud er han som laget denne siden, kontakt han med info på fremsiden.'],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7, // Adjust the temperature
        'max_tokens' => 80,  // Adjust max tokens
        'top_p' => 0.3,
        'frequency_penalty' => 0.7,
        'presence_penalty' => 0.3,
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
