<?php
// Include the API key file
include '../../../api_key.php';
$apiKey = OPENAI_API_KEY; // Ensure this matches your variable name in the included file

// Get the base64 image string from POST request
$base64_image = $_POST['image'] ?? null;

if (!$base64_image) {
    echo json_encode(['error' => 'No image data provided']);
    exit;
}

// Prepare the payload for the OpenAI API
$payload = [
    "model" => "gpt-4o",
    "messages" => [
        [
            "role" => "user",
            "content" => [
                [
                    "type" => "text",
                    "text" => "whats the license plate number? answer strictly only with the license plate number"
                ],
                [
                    "type" => "image_url",
                    "image_url" => [
                        "url" => "data:image/jpeg;base64,$base64_image"
                    ]
                ]
            ]
        ]
    ],
    "max_tokens" => 300
];

$headers = [
    "Content-Type: application/json",
    "Authorization: " . "Bearer " . $apiKey
];

// Use cURL to make the POST request to the OpenAI API
$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['error' => 'Request Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

if ($httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
    echo json_encode(['error' => 'API request failed with response code ' . $httpcode]);
    curl_close($ch);
    exit;
}

curl_close($ch);

echo $response;
?>
