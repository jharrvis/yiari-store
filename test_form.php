<?php
// Simple test to check if the form shortcode works
// Load WordPress
$wp_load = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load)) {
    require_once $wp_load;
} else {
    echo "WordPress not found. Please adjust the path to wp-load.php";
    exit;
}

echo "<h1>Testing Donasi Kukang Form</h1>";

// Test Indonesian form shortcode
echo "<h2>Indonesian Form Test:</h2>";
echo do_shortcode('[donasi_kukang]');

// Test English form shortcode
echo "<h2>English Form Test:</h2>";
echo do_shortcode('[donasi_kukang_en]');

echo "<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}
</style>";
?>