<?php
/**
 * Website Test Script for Travian T4.6
 * This script tests if the Laravel application is working correctly
 */

echo "=== Travian T4.6 Website Test ===\n\n";

// Test 1: Check if Laravel is properly loaded
echo "1. Testing Laravel Application Bootstrap...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "   ✅ Laravel application loaded successfully\n";
} catch (Exception $e) {
    echo "   ❌ Failed to load Laravel application: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check database connection
echo "\n2. Testing Database Connection...\n";
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✅ Database connected successfully\n";
    echo "   📊 Found " . count($tables) . " tables: " . implode(', ', array_slice($tables, 0, 5)) . (count($tables) > 5 ? '...' : '') . "\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 3: Check environment configuration
echo "\n3. Testing Environment Configuration...\n";
if (file_exists(__DIR__ . '/.env')) {
    echo "   ✅ .env file exists\n";
    $env = file_get_contents(__DIR__ . '/.env');
    if (strpos($env, 'APP_KEY=') !== false && strpos($env, 'APP_KEY=') < strpos($env, "\n")) {
        $key = trim(substr($env, strpos($env, 'APP_KEY=') + 8, 32));
        if (strlen($key) > 20) {
            echo "   ✅ Application key is set\n";
        } else {
            echo "   ⚠️  Application key might be empty\n";
        }
    }
    if (strpos($env, 'APP_URL=https://traviant46.prus.dev') !== false) {
        echo "   ✅ Correct APP_URL configured\n";
    } else {
        echo "   ⚠️  APP_URL might not be set correctly\n";
    }
} else {
    echo "   ❌ .env file not found\n";
}

// Test 4: Check if routes are working
echo "\n4. Testing Route Configuration...\n";
try {
    $router = $app->make('router');
    $routes = $router->getRoutes();
    echo "   ✅ Router loaded successfully\n";
    echo "   🛣️  Found " . count($routes) . " routes\n";
    
    // List some key routes
    foreach ($routes as $route) {
        $uri = $route->uri();
        $methods = implode('|', $route->methods());
        if (in_array($uri, ['/', '/home', '/login', '/register'])) {
            echo "      - $methods $uri\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Route testing failed: " . $e->getMessage() . "\n";
}

// Test 5: Check public directory
echo "\n5. Testing Public Directory...\n";
if (file_exists(__DIR__ . '/public/index.php')) {
    echo "   ✅ public/index.php exists\n";
} else {
    echo "   ❌ public/index.php not found\n";
}

if (is_readable(__DIR__ . '/public/index.php')) {
    echo "   ✅ public/index.php is readable\n";
} else {
    echo "   ❌ public/index.php is not readable\n";
}

// Test 6: Check storage permissions
echo "\n6. Testing Storage Permissions...\n";
$storageDirs = ['storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views'];
foreach ($storageDirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        if (is_writable(__DIR__ . '/' . $dir)) {
            echo "   ✅ $dir is writable\n";
        } else {
            echo "   ⚠️  $dir is not writable\n";
        }
    } else {
        echo "   ❌ $dir does not exist\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "🌐 Your website should be accessible at: https://traviant46.prus.dev/\n";
echo "📝 If you see any ❌ errors above, please fix them before accessing the website.\n";
echo "🔧 For a fully functional Travian game, you'll need to implement the game logic components.\n\n";
?>
