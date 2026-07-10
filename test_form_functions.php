<?php
/*
 * Test File untuk Form Donasi Kukang
 * Gunakan ini untuk test shortcode tanpa perlu WordPress full
 */

// Load WordPress
$wp_load_paths = [
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../wp-load.php',
    dirname(__FILE__) . '/../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('WordPress tidak ditemukan. Sesuaikan path wp-load.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Form Donasi Kukang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .test-header {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
        }

        .test-section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
        }

        .debug-info {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin-top: 10px;
            font-size: 12px;
            color: #ccc;
        }
    </style>
</head>
<body>
    <div class="test-header">
        <h1>🧪 Test Form Donasi Kukang</h1>
        <p>Plugin Version: <?php echo defined('YIARI_DONASI_KUKANG_VERSION') ? YIARI_DONASI_KUKANG_VERSION : 'Unknown'; ?></p>
        <p>WordPress Version: <?php echo get_bloginfo('version'); ?></p>
        <p>Test Time: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="test-section">
        <h2>📋 Plugin Status Check</h2>
        <?php
        echo "<div class='debug-info'>";
        echo "✅ WordPress loaded: " . (function_exists('wp_loaded') ? 'Yes' : 'Yes (function check passed)') . "<br>";
        echo "✅ Plugin constants defined: " . (defined('YIARI_DONASI_KUKANG_VERSION') ? 'Yes' : 'No') . "<br>";
        echo "✅ Shortcode registered: " . (shortcode_exists('donasi_kukang') ? 'Yes' : 'No') . "<br>";
        echo "✅ Admin AJAX URL: " . admin_url('admin-ajax.php') . "<br>";

        // Check if classes are loaded
        $classes = ['YIARI_Form_Manager', 'YIARI_Shipping_Manager', 'YIARI_Payment_Manager', 'YIARI_Database_Manager'];
        foreach ($classes as $class) {
            echo "✅ Class $class: " . (class_exists($class) ? 'Loaded' : 'Not Found') . "<br>";
        }

        // Check database tables
        global $wpdb;
        $tables = ['kukang_dolls_new', 'kukang_transactions_new', 'kukang_currency_new'];
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
            echo "✅ Table $full_table: " . ($exists ? 'Exists' : 'Not Found') . "<br>";
        }
        echo "</div>";
        ?>
    </div>

    <div class="test-section">
        <h2>🎯 Indonesian Form Test</h2>
        <p>Testing shortcode: <code>[donasi_kukang]</code></p>

        <?php
        try {
            echo do_shortcode('[donasi_kukang]');
        } catch (Exception $e) {
            echo "<div style='background: #ff6b6b; padding: 15px; border-radius: 5px; color: white;'>";
            echo "❌ Error loading shortcode: " . $e->getMessage();
            echo "</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🔧 JavaScript Console Tests</h2>
        <p>Open browser Developer Tools (F12) → Console untuk melihat test JavaScript:</p>
        <div class="debug-info">
            Test functions yang tersedia:
            <br>• changeQty(dollName, change) - Test quantity controls
            <br>• searchCities(query) - Test city search
            <br>• calculateShippingCost(city) - Test shipping calculation
            <br>• validateDonationForm() - Test form validation
        </div>
    </div>

    <div class="test-section">
        <h2>🚀 Manual Test Steps</h2>
        <ol style="color: white;">
            <li><strong>Test Quantity Controls:</strong> Klik tombol +/- pada boneka kukang</li>
            <li><strong>Test City Search:</strong> Ketik "jakarta" di field Kota/Kabupaten</li>
            <li><strong>Test Shipping Calc:</strong> Pilih kota dari dropdown, lihat ongkir terupdate</li>
            <li><strong>Test Form Submission:</strong> Isi semua field dan klik "Proses Donasi"</li>
            <li><strong>Check Console:</strong> Lihat log di browser console untuk debug info</li>
        </ol>
    </div>

    <script>
        console.log('🧪 Form Test Page Loaded');
        console.log('📋 Available functions for testing:');
        console.log('• changeQty(dollName, change) - Test quantity controls');
        console.log('• searchCities(query) - Test city search');
        console.log('• calculateShippingCost(city) - Test shipping calculation');
        console.log('• validateDonationForm() - Test form validation');

        // Test if functions are available
        setTimeout(() => {
            console.log('🔍 Function availability check:');
            console.log('• changeQty:', typeof changeQty !== 'undefined' ? '✅ Available' : '❌ Not found');
            console.log('• searchCities:', typeof searchCities !== 'undefined' ? '✅ Available' : '❌ Not found');
            console.log('• debouncedSearchCities:', typeof debouncedSearchCities !== 'undefined' ? '✅ Available' : '❌ Not found');
            console.log('• validateDonationForm:', typeof validateDonationForm !== 'undefined' ? '✅ Available' : '❌ Not found');
        }, 2000);
    </script>
</body>
</html>