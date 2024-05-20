<?php
// Include the API key file
include '../../api_key.php';

// Get the base64 image string from POST request
$base64_image = $_POST['image'];

// Prepare the payload for the OpenAI API
$payload = [
    "model" => "gpt-4o",
    "messages" => [
        [
            "role" => "user",
            "content" => [
                [
                    "type" => "text",
                    "text" => "what is the license plate number?"
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
    "Authorization: " . "Bearer " . $api_key
];

// Use cURL to make the POST request to the OpenAI API
$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
if(curl_errno($ch)) {
    echo 'Request Error:' . curl_error($ch);
}
curl_close($ch);

echo $response;
?>
