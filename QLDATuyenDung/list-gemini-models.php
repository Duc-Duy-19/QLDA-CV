<?php

// List all available Gemini models
$apiKey = 'AIzaSyBhXdbGvuHCTwPUPj487tYHBt3aYPVYWuM';
$url = 'https://generativelanguage.googleapis.com/v1/models';

$ch = curl_init($url . '?key=' . $apiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

echo "=== LISTING AVAILABLE GEMINI MODELS ===\n\n";
echo "Fetching models...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    echo "❌ Error fetching models (HTTP $httpCode)\n";
    echo $response . "\n";
    exit(1);
}

$result = json_decode($response, true);

if (isset($result['models'])) {
    echo "✅ Available Models:\n\n";
    foreach ($result['models'] as $model) {
        $name = $model['name'] ?? 'unknown';
        $displayName = $model['displayName'] ?? '';
        $supported = $model['supportedGenerationMethods'] ?? [];
        
        // Extract just the model ID
        $modelId = str_replace('models/', '', $name);
        
        echo "Model: $modelId\n";
        if ($displayName) echo "  Display Name: $displayName\n";
        if (!empty($supported)) echo "  Supports: " . implode(', ', $supported) . "\n";
        echo "\n";
    }
    
    echo "\nTo use a model, copy the Model ID (e.g., 'gemini-pro')\n";
    echo "and use URL: https://generativelanguage.googleapis.com/v1beta/models/MODEL_ID:generateContent\n";
} else {
    echo "❌ Unexpected response format\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=== END ===\n";
