<?php
/**
 * Fix Production Issues for YIARI Donasi Kukang Plugin
 *
 * This script fixes the main issues preventing production transactions:
 * 1. Database table inconsistency
 * 2. Production configuration validation
 * 3. Midtrans environment setup verification
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For standalone execution during debugging
    define('ABSPATH', dirname(__FILE__) . '/');

    // Load WordPress if available
    $wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once $wp_config_path;
    } else {
        echo "<h1>⚠️ WordPress not found. Please run this from WordPress admin or fix the path.</h1>";
        echo "<p>Current directory: " . dirname(__FILE__) . "</p>";
        echo "<p>Looking for wp-config.php at: $wp_config_path</p>";
        exit;
    }
}

echo "<h1>🔧 YIARI Plugin Production Fix</h1>";
echo "<div style='background: #f0f8f0; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>Analisis Masalah Production:</h2>";
echo "<ul>";
echo "<li>❌ Transaksi tidak tercatat di Midtrans dashboard</li>";
echo "<li>❌ Error 404 'Transaction doesn't exist' saat cek status</li>";
echo "<li>❌ Inkonsistensi nama tabel database</li>";
echo "<li>❌ Konfigurasi production environment</li>";
echo "</ul>";
echo "</div>";

// 1. Fix database table inconsistency
echo "<h2>1. 🗃️ Memperbaiki Inkonsistensi Database</h2>";

global $wpdb;

// Check if new table exists
$new_table = $wpdb->prefix . 'kukang_transactions_new';
$old_table = $wpdb->prefix . 'kukang_transactions';

$new_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$new_table'") == $new_table;
$old_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$old_table'") == $old_table;

echo "<p><strong>Status Tabel:</strong></p>";
echo "<ul>";
echo "<li>$new_table: " . ($new_table_exists ? "✅ Ada" : "❌ Tidak ada") . "</li>";
echo "<li>$old_table: " . ($old_table_exists ? "✅ Ada" : "❌ Tidak ada") . "</li>";
echo "</ul>";

// 2. Check Midtrans configuration
echo "<h2>2. 🔐 Validasi Konfigurasi Midtrans</h2>";

$midtrans_settings = get_option('midtrans_settings', array());
$environment = $midtrans_settings['environment'] ?? 'sandbox';

echo "<p><strong>Environment Aktif:</strong> " . ucfirst($environment) . "</p>";

if ($environment === 'production') {
    $server_key = $midtrans_settings['production_server_key'] ?? '';
    $client_key = $midtrans_settings['production_client_key'] ?? '';

    echo "<p><strong>Production Keys:</strong></p>";
    echo "<ul>";
    echo "<li>Server Key: " . (empty($server_key) ? "❌ Tidak ada" : "✅ Ada (" . substr($server_key, 0, 8) . "...)") . "</li>";
    echo "<li>Client Key: " . (empty($client_key) ? "❌ Tidak ada" : "✅ Ada (" . substr($client_key, 0, 8) . "...)") . "</li>";
    echo "</ul>";
} else {
    echo "<p>⚠️ Plugin masih dalam mode sandbox</p>";
}

// 3. Test Midtrans connectivity
echo "<h2>3. 🌐 Test Koneksi Midtrans</h2>";

if ($environment === 'production' && !empty($midtrans_settings['production_server_key'])) {
    $server_key = $midtrans_settings['production_server_key'];
    $api_url = "https://api.midtrans.com/v2/charge";

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
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

    echo "<p><strong>Test API Production:</strong></p>";
    echo "<ul>";
    echo "<li>HTTP Code: $http_code " . ($http_code === 400 ? "✅ (Auth berhasil)" : "❌") . "</li>";
    echo "<li>URL: $api_url</li>";
    if ($curl_error) {
        echo "<li>Error: $curl_error</li>";
    }
    echo "</ul>";
} else {
    echo "<p>⚠️ Tidak dapat test production - keys tidak lengkap atau masih sandbox mode</p>";
}

// 4. Recommended fixes
echo "<h2>4. 🔧 Rekomendasi Perbaikan</h2>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107;'>";
echo "<h3>Langkah-langkah Perbaikan:</h3>";
echo "<ol>";
echo "<li><strong>Fix Inkonsistensi Database:</strong>";
echo "<ul>";
echo "<li>Update file <code>helpers/ajax-handlers.php</code> line 26</li>";
echo "<li>Ganti <code>kukang_transactions</code> menjadi <code>kukang_transactions_new</code></li>";
echo "</ul></li>";

echo "<li><strong>Verifikasi Production Settings:</strong>";
echo "<ul>";
echo "<li>Pastikan production server key dan client key sudah diisi</li>";
echo "<li>Verifikasi environment sudah diset ke 'production'</li>";
echo "<li>Test koneksi API production</li>";
echo "</ul></li>";

echo "<li><strong>Debug Order ID Format:</strong>";
echo "<ul>";
echo "<li>Pastikan format order_id sesuai dengan yang diharapkan Midtrans</li>";
echo "<li>Cek apakah order_id yang di-generate unique</li>";
echo "</ul></li>";

echo "<li><strong>Enable Error Logging:</strong>";
echo "<ul>";
echo "<li>Aktifkan WordPress debug logging</li>";
echo "<li>Monitor error_log untuk detail error Midtrans</li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

// 5. Quick fix for ajax-handlers.php
echo "<h2>5. 🚀 Quick Fix</h2>";

$ajax_file = dirname(__FILE__) . '/helpers/ajax-handlers.php';
if (file_exists($ajax_file)) {
    $content = file_get_contents($ajax_file);

    if (strpos($content, "kukang_transactions_new") === false && strpos($content, "kukang_transactions") !== false) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 5px solid #dc3545;'>";
        echo "<p><strong>⚠️ Ditemukan Masalah:</strong></p>";
        echo "<p>File <code>helpers/ajax-handlers.php</code> masih menggunakan tabel lama <code>kukang_transactions</code></p>";
        echo "<p><strong>Perbaikan diperlukan:</strong> Ganti dengan <code>kukang_transactions_new</code></p>";
        echo "</div>";

        // Show the line that needs fixing
        $lines = explode("\n", $content);
        foreach ($lines as $num => $line) {
            if (strpos($line, 'kukang_transactions') !== false && strpos($line, 'kukang_transactions_new') === false) {
                echo "<p><strong>Line " . ($num + 1) . ":</strong> <code>" . htmlspecialchars(trim($line)) . "</code></p>";
            }
        }
    } else {
        echo "<p>✅ File ajax-handlers.php sudah menggunakan tabel yang benar</p>";
    }
} else {
    echo "<p>❌ File ajax-handlers.php tidak ditemukan</p>";
}

echo "<hr>";
echo "<h2>📋 Kesimpulan</h2>";
echo "<p>Untuk mengatasi masalah production:</p>";
echo "<ol>";
echo "<li>Fix nama tabel di ajax-handlers.php</li>";
echo "<li>Pastikan production keys terisi dengan benar</li>";
echo "<li>Verifikasi environment setting = 'production'</li>";
echo "<li>Test ulang dengan transaksi kecil</li>";
echo "</ol>";

echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<p><strong>💡 Tips:</strong> Setelah perbaikan, test dengan nominal kecil terlebih dahulu untuk memastikan transaksi berhasil tercatat di dashboard Midtrans production.</p>";
echo "</div>";
?>