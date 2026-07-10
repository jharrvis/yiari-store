<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YIARI Production Configuration Checker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        h1, h2 { color: #333; }
        .code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 YIARI Production Configuration Checker</h1>

        <?php
        // Try to load WordPress
        $wp_loaded = false;
        $possible_wp_paths = [
            __DIR__ . '/../../../wp-config.php',
            __DIR__ . '/../../../../wp-config.php',
            __DIR__ . '/../wp-config.php',
            __DIR__ . '/../../wp-config.php'
        ];

        foreach ($possible_wp_paths as $wp_path) {
            if (file_exists($wp_path)) {
                try {
                    require_once $wp_path;
                    $wp_loaded = true;
                    echo "<div class='success'>✅ WordPress loaded from: " . htmlspecialchars($wp_path) . "</div>";
                    break;
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Error loading WordPress: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }

        if (!$wp_loaded) {
            echo "<div class='error'>❌ WordPress not found. Tried paths:</div>";
            echo "<ul>";
            foreach ($possible_wp_paths as $path) {
                echo "<li>" . htmlspecialchars($path) . " - " . (file_exists($path) ? "exists" : "not found") . "</li>";
            }
            echo "</ul>";
            echo "<div class='warning'>⚠️ Please run this script from a location where WordPress can be loaded, or copy it to your WordPress root directory.</div>";
            echo "</div></body></html>";
            exit;
        }
        ?>

        <h2>📊 Current Configuration Status</h2>

        <?php
        // Get Midtrans settings
        $settings = get_option('midtrans_settings', array());
        $environment = $settings['environment'] ?? 'sandbox';

        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
        echo "<tr><td>Environment</td><td><span class='code'>" . htmlspecialchars($environment) . "</span></td><td>" .
             ($environment === 'production' ? "<span class='status-ok'>✅ Production</span>" : "<span class='status-warning'>⚠️ Sandbox</span>") . "</td></tr>";

        $sandbox_server = !empty($settings['sandbox_server_key']);
        $sandbox_client = !empty($settings['sandbox_client_key']);
        $prod_server = !empty($settings['production_server_key']);
        $prod_client = !empty($settings['production_client_key']);

        echo "<tr><td>Sandbox Server Key</td><td>" . ($sandbox_server ? "Set (hidden)" : "Not set") . "</td><td>" .
             ($sandbox_server ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "</td></tr>";
        echo "<tr><td>Sandbox Client Key</td><td>" . ($sandbox_client ? "Set (hidden)" : "Not set") . "</td><td>" .
             ($sandbox_client ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "</td></tr>";
        echo "<tr><td>Production Server Key</td><td>" . ($prod_server ? "Set (hidden)" : "Not set") . "</td><td>" .
             ($prod_server ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "</td></tr>";
        echo "<tr><td>Production Client Key</td><td>" . ($prod_client ? "Set (hidden)" : "Not set") . "</td><td>" .
             ($prod_client ? "<span class='status-ok'>✅</span>" : "<span class='status-error'>❌</span>") . "</td></tr>";
        echo "</table>";
        ?>

        <h2>🔧 Configuration Issues & Fixes</h2>

        <?php
        $issues = [];
        $fixes = [];

        // Check environment vs keys mismatch
        if ($environment === 'production' && (!$prod_server || !$prod_client)) {
            $issues[] = "Production mode enabled but production keys not configured";
            $fixes[] = "Set production server key and client key in plugin settings";
        }

        if ($environment === 'sandbox' && (!$sandbox_server || !$sandbox_client)) {
            $issues[] = "Sandbox mode enabled but sandbox keys not configured";
            $fixes[] = "Set sandbox server key and client key in plugin settings";
        }

        // Test API connectivity if production
        if ($environment === 'production' && $prod_server) {
            echo "<h3>🌐 Testing Production API Connectivity</h3>";

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
            $curl_error = curl_error($curl);
            curl_close($curl);

            echo "<table>";
            echo "<tr><th>Test</th><th>Result</th><th>Status</th></tr>";
            echo "<tr><td>API Endpoint</td><td>https://api.midtrans.com/v2/charge</td><td><span class='status-ok'>✅</span></td></tr>";
            echo "<tr><td>HTTP Response</td><td>$http_code</td><td>" .
                 ($http_code === 400 ? "<span class='status-ok'>✅ Auth OK</span>" : "<span class='status-error'>❌ Auth Failed</span>") . "</td></tr>";

            if ($curl_error) {
                echo "<tr><td>CURL Error</td><td>" . htmlspecialchars($curl_error) . "</td><td><span class='status-error'>❌</span></td></tr>";
                $issues[] = "CURL connection error: $curl_error";
            }
            echo "</table>";

            if ($http_code !== 400) {
                $issues[] = "Production API authentication failed (HTTP $http_code)";
                $fixes[] = "Verify production server key is correct and active";
            }
        }

        // Check database tables
        echo "<h3>📊 Database Tables Status</h3>";
        global $wpdb;
        $tables_to_check = [
            'kukang_transactions_new' => 'Main transactions table',
            'kukang_dolls_new' => 'Products/dolls table',
            'kukang_currency_new' => 'Currency settings table'
        ];

        echo "<table>";
        echo "<tr><th>Table</th><th>Description</th><th>Records</th><th>Status</th></tr>";

        foreach ($tables_to_check as $table => $description) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;

            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
                echo "<tr><td><span class='code'>$full_table</span></td><td>$description</td><td>$count</td><td><span class='status-ok'>✅</span></td></tr>";
            } else {
                echo "<tr><td><span class='code'>$full_table</span></td><td>$description</td><td>-</td><td><span class='status-error'>❌</span></td></tr>";
                $issues[] = "Database table $full_table not found";
                $fixes[] = "Run plugin activation or database creation script";
            }
        }
        echo "</table>";

        // Display issues and fixes
        if (!empty($issues)) {
            echo "<h3>❌ Issues Found</h3>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>" . htmlspecialchars($issue) . "</li>";
            }
            echo "</ul>";

            echo "<h3>🔧 Recommended Fixes</h3>";
            echo "<ol>";
            foreach ($fixes as $fix) {
                echo "<li>" . htmlspecialchars($fix) . "</li>";
            }
            echo "</ol>";
        } else {
            echo "<div class='success'>✅ No major configuration issues found!</div>";
        }

        // Recent transactions for debugging
        echo "<h3>📝 Recent Transactions (Last 5)</h3>";
        $recent_transactions = $wpdb->get_results("
            SELECT order_id, transaction_status, payment_type, gross_amount, created_at, transaction_id
            FROM {$wpdb->prefix}kukang_transactions_new
            ORDER BY created_at DESC
            LIMIT 5
        ");

        if ($recent_transactions) {
            echo "<table>";
            echo "<tr><th>Order ID</th><th>Transaction ID</th><th>Status</th><th>Payment Type</th><th>Amount</th><th>Created</th></tr>";
            foreach ($recent_transactions as $txn) {
                $status_color = in_array($txn->transaction_status, ['settlement', 'capture']) ? 'status-ok' :
                               ($txn->transaction_status === 'pending' ? 'status-warning' : 'status-error');
                echo "<tr>";
                echo "<td><span class='code'>" . htmlspecialchars($txn->order_id) . "</span></td>";
                echo "<td><span class='code'>" . htmlspecialchars($txn->transaction_id ?: '-') . "</span></td>";
                echo "<td><span class='$status_color'>" . htmlspecialchars($txn->transaction_status) . "</span></td>";
                echo "<td>" . htmlspecialchars($txn->payment_type ?: 'Not set') . "</td>";
                echo "<td>Rp " . number_format($txn->gross_amount) . "</td>";
                echo "<td>" . htmlspecialchars($txn->created_at) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>ℹ️ No recent transactions found.</div>";
        }
        ?>

        <h2>🚀 Next Steps</h2>
        <div class="info">
            <h3>After fixing the script URL issue:</h3>
            <ol>
                <li>Clear any caching (browser, WordPress cache, CDN)</li>
                <li>Check page source - script should now point to <span class="code">app.midtrans.com</span> (not sandbox)</li>
                <li>Test a small transaction (Rp 10,000)</li>
                <li>Monitor WordPress debug.log for errors</li>
                <li>Check Midtrans production dashboard for transaction</li>
            </ol>
        </div>

        <hr>
        <p><small>Report generated: <?php echo date('Y-m-d H:i:s'); ?> | Environment: <?php echo htmlspecialchars($environment); ?></small></p>
    </div>
</body>
</html>