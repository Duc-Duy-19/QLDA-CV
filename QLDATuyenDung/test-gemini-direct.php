<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== TESTING GEMINI API ===\n\n";

$apiKey = config('services.gemini.api_key');

if (!$apiKey) {
    echo "❌ GEMINI_API_KEY not found in .env\n";
    echo "Please add: GEMINI_API_KEY=your_key_here\n";
    exit(1);
}

echo "✓ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

$testPrompt = "Tôi muốn học Backend Developer, nên bắt đầu từ đâu?";

echo "Testing with prompt: \"$testPrompt\"\n\n";

try {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    
    echo "Calling API...\n";
    
    $response = Http::withoutVerifying()
        ->timeout(30)
        ->post($url . '?key=' . $apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $testPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ]
        ]);

    echo "Status: " . $response->status() . "\n\n";

    if (!$response->successful()) {
        echo "❌ API ERROR!\n";
        echo "Response body:\n";
        echo $response->body() . "\n";
        exit(1);
    }

    $result = $response->json();
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $result['candidates'][0]['content']['parts'][0]['text'];
        echo "✅ SUCCESS!\n\n";
        echo "AI Response:\n";
        echo "===========\n";
        echo $text . "\n";
        echo "===========\n";
    } else {
        echo "❌ No text in response\n";
        echo "Full response:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

} catch (Exception $e) {
    echo "❌ EXCEPTION!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END TEST ===\n";
