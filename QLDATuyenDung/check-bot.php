<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== CHECKING AI BOT USER ===\n\n";

// Check if bot user exists
$botUser = User::where('email', 'ai-advisor@webcv.com')->first();

if ($botUser) {
    echo "✓ Bot user EXISTS!\n";
    echo "  - ID: {$botUser->id}\n";
    echo "  - Name: {$botUser->name}\n";
    echo "  - Email: {$botUser->email}\n";
    echo "  - Role: {$botUser->role}\n";
    echo "\n";
} else {
    echo "✗ Bot user NOT FOUND!\n";
    echo "  Run migration: php artisan migrate\n";
    echo "\n";
    
    // Try to create it manually
    echo "Attempting to create bot user...\n";
    try {
        $bot = User::create([
            'name' => 'AI Career Advisor',
            'email' => 'ai-advisor@webcv.com',
            'password' => bcrypt('not-used'),
            'role' => 'bot',
            'email_verified_at' => now(),
        ]);
        echo "✓ Bot user created successfully! ID: {$bot->id}\n";
    } catch (Exception $e) {
        echo "✗ Failed to create bot: {$e->getMessage()}\n";
    }
}

echo "\n=== END CHECK ===\n";
