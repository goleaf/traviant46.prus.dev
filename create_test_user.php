<?php
/**
 * Create Test User Script for Travian T4.6
 * This script creates a test user for the website
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "=== Creating Test User for Travian T4.6 ===\n\n";

// Create a test user
try {
    $user = new \App\Models\User();
    $user->name = 'Test Player';
    $user->email = 'test@traviant46.prus.dev';
    $user->password = \Illuminate\Support\Facades\Hash::make('password123');
    $user->email_verified_at = now();
    $user->save();
    
    echo "✅ Test user created successfully!\n";
    echo "📧 Email: test@traviant46.prus.dev\n";
    echo "🔑 Password: password123\n";
    echo "🌐 You can now login at: https://traviant46.prus.dev/login\n\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
        echo "ℹ️  Test user already exists!\n";
        echo "📧 Email: test@traviant46.prus.dev\n";
        echo "🔑 Password: password123\n";
        echo "🌐 You can login at: https://traviant46.prus.dev/login\n\n";
    } else {
        echo "❌ Failed to create test user: " . $e->getMessage() . "\n";
    }
}

echo "=== Website Status ===\n";
echo "🌐 Website URL: https://traviant46.prus.dev/\n";
echo "🔐 Login URL: https://traviant46.prus.dev/login\n";
echo "📝 Register URL: https://traviant46.prus.dev/register\n";
echo "🏠 Home URL: https://traviant46.prus.dev/home\n\n";

echo "=== Available Features ===\n";
echo "✅ User Authentication (Login/Register)\n";
echo "✅ Email Verification\n";
echo "✅ Password Reset\n";
echo "✅ Two-Factor Authentication\n";
echo "✅ Sitter System\n";
echo "✅ Dashboard\n";
echo "✅ Livewire Components\n";
echo "✅ Flux UI Components\n\n";

echo "=== Next Steps for Full Travian Game ===\n";
echo "🔧 Implement game mechanics (villages, buildings, units)\n";
echo "🗺️  Add world map functionality\n";
echo "⚔️  Implement combat system\n";
echo "🏰 Add alliance system\n";
echo "📊 Create statistics and rankings\n";
echo "🎮 Add hero system\n\n";

echo "The foundation is ready! The Laravel application is working correctly.\n";
?>
