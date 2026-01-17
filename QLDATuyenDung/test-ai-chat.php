<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\AiCareerAdvisorService;
use App\Services\GeminiService;

echo "=== TEST AI CAREER ADVISOR ===\n\n";

// 1. Check AI Bot user exists
echo "1. Checking AI Bot user...\n";
$aiBot = User::where('email', 'ai-advisor@webcv.com')->first();

if ($aiBot) {
    echo "   ✓ AI Bot found: {$aiBot->name} (ID: {$aiBot->id})\n";
    echo "   ✓ Role: {$aiBot->role}\n\n";
} else {
    echo "   ✗ AI Bot not found!\n\n";
    exit(1);
}

// 2. Test AI Career Advisor Service
echo "2. Testing AI Career Advisor Service...\n";

$geminiService = new GeminiService();
$aiService = new AiCareerAdvisorService($geminiService);

$testMessage = "Tôi muốn chuyển sang làm Backend Developer, tôi nên học gì?";
echo "   Question: {$testMessage}\n\n";

try {
    $response = $aiService->getCareerAdvice($testMessage, [], [
        'name' => 'Test User',
        'current_position' => 'Frontend Developer',
        'experience_years' => 2
    ]);

    if ($response) {
        echo "   ✓ AI Response:\n";
        echo "   " . str_repeat("=", 60) . "\n";
        echo "   " . $response['response'] . "\n";
        echo "   " . str_repeat("=", 60) . "\n";
        echo "   Topic detected: {$response['topic']}\n\n";
        echo "   ✓ Test PASSED!\n";
    } else {
        echo "   ✗ AI Service returned null\n";
        echo "   Check API key and network connection\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
}

echo "\n=== TEST COMPLETED ===\n";
