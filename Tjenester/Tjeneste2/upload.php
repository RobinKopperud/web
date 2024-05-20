<?php
require_once '../../api_key.php'; // Adjust the path as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($_FILES['image']['name']);

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            // Call OpenAI API to extract license plate number
            $imagePath = realpath($uploadFile);
            $imageData = base64_encode(file_get_contents($imagePath));

            $apiUrl = 'https://api.openai.com/v1/images/generations';
            $data = [
                'model' => 'dalle-2',
                'prompt' => 'Extract license plate number from the image',
                'image' => $imageData
            ];

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . OPENAI_API_KEY,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                echo json_encode(['error' => 'Request Error: ' . curl_error($ch)]);
                curl_close($ch);
                exit;
            }

            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpStatus != 200) {
                echo json_encode(['error' => 'API Request Failed. Status Code: ' . $httpStatus]);
                curl_close($ch);
                exit;
            }

            $result = json_decode($response, true);
            curl_close($ch);

            if (isset($result['choices'][0]['text'])) {
                echo json_encode(['plate_number' => trim($result['choices'][0]['text'])]);
            } else {
                echo json_encode(['error' => 'Failed to extract license plate number.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move uploaded file.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded or upload error.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
