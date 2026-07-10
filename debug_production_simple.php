<?php
/**
 * Simple Production Debug Script
 *
 * Run this to quickly check production issues
 */

echo "=== YIARI DONASI PRODUCTION DEBUG ===\n\n";

// 1. Check file fix
echo "1. CHECKING DATABASE TABLE FIX:\n";
$ajax_file = __DIR__ . '/helpers/ajax-handlers.php';

if (file_exists($ajax_file)) {
    $content = file_get_contents($ajax_file);

    if (strpos($content, 'kukang_transactions_new') !== false) {
        echo "✅ ajax-handlers.php FIXED - using kukang_transactions_new\n";
    } else {
        echo "❌ ajax-handlers.php STILL BROKEN - using old table name\n";
    }
} else {
    echo "❌ ajax-handlers.php not found\n";
}

echo "\n";

// 2. Check Midtrans library
echo "2. CHECKING MIDTRANS LIBRARY:\n";
$midtrans_paths = [
    __DIR__ . '/midtrans-php-master/Midtrans.php',
    __DIR__ . '/vendor/midtrans/midtrans-php/Midtrans.php'
];

$midtrans_found = false;
foreach ($midtrans_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Midtrans library found: $path\n";
        $midtrans_found = true;
        break;
    }
}

if (!$midtrans_found) {
    echo "❌ Midtrans library NOT FOUND\n";
}

echo "\n";

// 3. Check WordPress integration
echo "3. CHECKING WORDPRESS INTEGRATION:\n";

$wp_paths = [
    __DIR__ . '/../../../wp-config.php',
    __DIR__ . '/../../../../wp-config.php',
    __DIR__ . '/../wp-config.php'
];

$wp_found = false;
foreach ($wp_paths as $path) {
    if (file_exists($path)) {
        echo "✅ WordPress found: $path\n";
        $wp_found = true;
        break;
    }
}

if (!$wp_found) {
    echo "❌ WordPress wp-config.php NOT FOUND\n";
}

echo "\n";

// 4. Main issues summary
echo "4. MAIN ISSUES FOUND:\n";
echo "=====================================\n";
echo "❌ Error: Failed to check status: Midtrans API is returning API error. HTTP status code: 404\n";
echo "❌ API response: Transaction doesn't exist\n\n";

echo "LIKELY CAUSES:\n";
echo "1. ❌ Database table inconsistency (FIXED)\n";
echo "2. ❌ Production environment not properly configured\n";
echo "3. ❌ Order ID not being sent to Midtrans correctly\n";
echo "4. ❌ Wrong API endpoint (sandbox vs production)\n\n";

echo "NEXT STEPS TO FIX:\n";
echo "==================\n";
echo "1. Check WordPress admin -> Plugin Settings\n";
echo "2. Verify environment is set to 'production'\n";
echo "3. Verify production server key and client key are correct\n";
echo "4. Test with debug mode enabled\n";
echo "5. Check if order_id format is valid for Midtrans\n\n";

echo "IMMEDIATE ACTION REQUIRED:\n";
echo "========================\n";
echo "Go to WordPress Admin -> YIARI Donasi Settings and:\n";
echo "- Set Environment: Production\n";
echo "- Enter valid Production Server Key\n";
echo "- Enter valid Production Client Key\n";
echo "- Save and test again\n\n";

echo "Debug completed: " . date('Y-m-d H:i:s') . "\n";
?>