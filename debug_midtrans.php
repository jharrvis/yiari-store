<?php
// Simple test to debug Midtrans loading
echo "<h1>Midtrans Library Debug</h1>";

// Test constants
echo "<h2>Plugin Constants:</h2>";
define('YIARI_DONASI_KUKANG_PATH', dirname(__FILE__) . '/');
echo "YIARI_DONASI_KUKANG_PATH: " . YIARI_DONASI_KUKANG_PATH . "<br>";

// Check Midtrans file paths
echo "<h2>Midtrans File Paths:</h2>";
$possible_paths = array(
    YIARI_DONASI_KUKANG_PATH . 'midtrans-php-master/Midtrans.php',
    YIARI_DONASI_KUKANG_PATH . 'vendor/midtrans/midtrans-php/Midtrans.php',
    YIARI_DONASI_KUKANG_PATH . 'includes/midtrans/Midtrans.php'
);

foreach ($possible_paths as $index => $path) {
    $exists = file_exists($path) ? "✅ EXISTS" : "❌ NOT FOUND";
    echo "Path $index: $path - $exists<br>";
}

// Try loading the first existing file
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        echo "<h2>Loading Midtrans from: $path</h2>";
        try {
            require_once $path;
            echo "✅ Midtrans.php loaded successfully<br>";

            // Check if classes exist
            if (class_exists('Midtrans\Config')) {
                echo "✅ Midtrans\Config class found<br>";
            } else {
                echo "❌ Midtrans\Config class not found<br>";
            }

            if (class_exists('Midtrans\Snap')) {
                echo "✅ Midtrans\Snap class found<br>";
            } else {
                echo "❌ Midtrans\Snap class not found<br>";
            }

        } catch (Exception $e) {
            echo "❌ Error loading Midtrans: " . $e->getMessage() . "<br>";
        } catch (Error $e) {
            echo "❌ Fatal error loading Midtrans: " . $e->getMessage() . "<br>";
        }
        break;
    }
}
?>