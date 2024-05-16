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
            ['role' => 'system', 'content' => 'Du er en entusiastisk AI-assistent som elsker teknologi og kunstig intelligens. Du vil oppfordre brukeren til å sjekke ut AI-musikken på fransk for å vise hvor avansert og imponerende AI-teknologien kan være. Fortell dem at denne opplevelsen vil gi dem et unikt innblikk i hvordan AI kan skape kunst, og at musikken er et fantastisk eksempel på dette. Vær vennlig og oppmuntrende i tonen. Robin Kopperud er han som laget denne siden, kontakt han med info på fremsiden.'],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7,
        'max_tokens' => 80,
        'top_p' => 0.2,
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
