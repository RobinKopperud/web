<?php
include '../api_key.php'; // Adjust the path if necessary

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'];
    $systemMessage = isset($input['system_message']) ? $input['system_message'] : 'You are a helpful assistant.';
    $model = isset($input['model']) ? $input['model'] : 'gpt-4';
    $temperature = isset($input['temperature']) ? $input['temperature'] : 1;
    $max_tokens = isset($input['max_tokens']) ? $input['max_tokens'] : 256;

    $apiKey = OPENAI_API_KEY;

    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => $temperature,
        'max_tokens' => $max_tokens,
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
