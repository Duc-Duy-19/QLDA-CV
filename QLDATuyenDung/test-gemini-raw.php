<?php

// Test Gemini API trực tiếp
$apiKey = 'AIzaSyBhXdbGvuHCTwPUPj487tYHBt3aYPVYWuM';
$url = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Tôi muốn học AI và Machine Learning, nên bắt đầu từ đâu?']
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.8,
        'maxOutputTokens' => 1024
    ]
];

$ch = curl_init($url . '?key=' . $apiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

echo "=== TESTING GEMINI API ===\n\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "URL: $url\n\n";
echo "Sending request...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($error) {
    echo "CURL Error: $error\n";
    exit(1);
}

if ($httpCode != 200) {
    echo "❌ API ERROR!\n";
    echo "Response:\n";
    echo $response . "\n";
    exit(1);
}

$result = json_decode($response, true);

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $text = $result['candidates'][0]['content']['parts'][0]['text'];
    echo "✅ SUCCESS! Gemini is working!\n\n";
    echo "AI Response:\n";
    echo "====================\n";
    echo $text . "\n";
    echo "====================\n\n";
    echo "This proves Gemini API is working. The issue is in the Laravel service.\n";
} else {
    echo "❌ Unexpected response format\n";
    echo "Full response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== END TEST ===\n";
