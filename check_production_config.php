<?php
/**
 * Production Configuration Checker & Fixer
 *
 * Script untuk mengecek dan memperbaiki konfigurasi production Midtrans
 */

// Load WordPress
$wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
if (file_exists($wp_config_path)) {
    require_once $wp_config_path;
} else {
    echo "WordPress not found. Please run from WordPress admin.\n";
    exit;
}

echo "<h1>🔍 Production Configuration Checker</h1>";

// 1. Check Midtrans settings
$settings = get_option('midtrans_settings', array());
$environment = $settings['environment'] ?? 'sandbox';

echo "<h2>Current Settings:</h2>";
echo "<ul>";
echo "<li><strong>Environment:</strong> " . $environment . "</li>";
echo "<li><strong>Sandbox Server Key:</strong> " . (empty($settings['sandbox_server_key']) ? "❌ Empty" : "✅ Set") . "</li>";
echo "<li><strong>Sandbox Client Key:</strong> " . (empty($settings['sandbox_client_key']) ? "❌ Empty" : "✅ Set") . "</li>";
echo "<li><strong>Production Server Key:</strong> " . (empty($settings['production_server_key']) ? "❌ Empty" : "✅ Set") . "</li>";
echo "<li><strong>Production Client Key:</strong> " . (empty($settings['production_client_key']) ? "❌ Empty" : "✅ Set") . "</li>";
echo "</ul>";

// 2. Test current configuration
if ($environment === 'production') {
    echo "<h2>🔴 Production Mode Active</h2>";

    if (empty($settings['production_server_key']) || empty($settings['production_client_key'])) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<strong>❌ ERROR:</strong> Production keys are not configured!<br>";
        echo "Plugin is set to production mode but production keys are missing.";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<strong>✅ Production keys are configured</strong>";
        echo "</div>";

        // Test API connectivity
        echo "<h3>Testing Production API...</h3>";
        $server_key = $settings['production_server_key'];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.midtrans.com/v2/charge",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . base64_encode($server_key . ":"),
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code === 400) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
            echo "<strong>✅ API Connection Successful</strong><br>";
            echo "HTTP 400 response indicates authentication is working (missing parameters expected for test)";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<strong>❌ API Connection Failed</strong><br>";
            echo "HTTP Code: $http_code<br>";
            echo "This might indicate invalid production keys.";
            echo "</div>";
        }
    }
} else {
    echo "<h2>🟡 Sandbox Mode Active</h2>";
    echo "<p>Plugin is currently in sandbox/testing mode.</p>";
}

// 3. Check database tables
echo "<h2>📊 Database Tables Status</h2>";

global $wpdb;
$tables_to_check = array(
    'kukang_transactions_new',
    'kukang_transactions',
    'kukang_dolls_new',
    'kukang_currency_new'
);

foreach ($tables_to_check as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;

    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
        echo "<li>✅ $full_table (Records: $count)</li>";
    } else {
        echo "<li>❌ $full_table (Not found)</li>";
    }
}

// 4. Check for common issues
echo "<h2>🔧 Common Issues Check</h2>";

// Check Midtrans library
$midtrans_paths = array(
    dirname(__FILE__) . '/midtrans-php-master/Midtrans.php',
    dirname(__FILE__) . '/vendor/midtrans/midtrans-php/Midtrans.php'
);

$midtrans_found = false;
foreach ($midtrans_paths as $path) {
    if (file_exists($path)) {
        echo "<li>✅ Midtrans library found: " . basename(dirname($path)) . "</li>";
        $midtrans_found = true;
        break;
    }
}

if (!$midtrans_found) {
    echo "<li>❌ Midtrans library not found</li>";
}

// Check WordPress error logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "<li>✅ WordPress debug mode enabled</li>";
} else {
    echo "<li>⚠️ WordPress debug mode disabled</li>";
}

// 5. Recommendations
echo "<h2>💡 Recommendations</h2>";

if ($environment === 'production' && !empty($settings['production_server_key'])) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Next Steps for Production:</h3>";
    echo "<ol>";
    echo "<li>Enable WordPress debug logging temporarily: <code>define('WP_DEBUG_LOG', true);</code></li>";
    echo "<li>Check <code>/wp-content/debug.log</code> for Midtrans errors</li>";
    echo "<li>Test with small amount first</li>";
    echo "<li>Verify webhook URL is set in Midtrans dashboard</li>";
    echo "<li>Check that Order ID format matches Midtrans requirements</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Production Setup Required:</h3>";
    echo "<ol>";
    echo "<li>Go to plugin settings</li>";
    echo "<li>Change environment to 'production'</li>";
    echo "<li>Enter valid production server key and client key</li>";
    echo "<li>Save settings</li>";
    echo "<li>Run this checker again</li>";
    echo "</ol>";
    echo "</div>";
}

// 6. Log recent transactions for debugging
echo "<h2>📝 Recent Transactions (for debugging)</h2>";

$recent_transactions = $wpdb->get_results("
    SELECT order_id, transaction_status, payment_type, gross_amount, created_at
    FROM {$wpdb->prefix}kukang_transactions_new
    ORDER BY created_at DESC
    LIMIT 5
");

if ($recent_transactions) {
    echo "<table style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th style='border: 1px solid #ddd; padding: 8px;'>Order ID</th><th style='border: 1px solid #ddd; padding: 8px;'>Status</th><th style='border: 1px solid #ddd; padding: 8px;'>Payment</th><th style='border: 1px solid #ddd; padding: 8px;'>Amount</th><th style='border: 1px solid #ddd; padding: 8px;'>Created</th></tr>";

    foreach ($recent_transactions as $txn) {
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . esc_html($txn->order_id) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . esc_html($txn->transaction_status) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . esc_html($txn->payment_type ?: '-') . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>Rp " . number_format($txn->gross_amount) . "</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . esc_html($txn->created_at) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No recent transactions found.</p>";
}

echo "<hr>";
echo "<p><strong>Report generated:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>