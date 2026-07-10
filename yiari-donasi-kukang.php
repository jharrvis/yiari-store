<?php
/**
 * Plugin Name: YIARI Donasi Kukang
 * Plugin URI: https://yiari.or.id/
 * Description: Plugin form donasi rehabilitasi kukang untuk Yayasan IAR Indonesia menggunakan Payment Gateway Midtrans dengan dukungan multi-bahasa (ID/EN) dan multi-mata uang (IDR/USD).
 * Version: 3.1.1
 * Author: Julian H - MCIMEDIA
 * Author URI: https://mcimedia.net
 * License: GPL v2 or later
 * Text Domain: yiari-donasi-kukang
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('YIARI_DONASI_KUKANG_VERSION', '3.1.1');
define('YIARI_DONASI_KUKANG_PATH', plugin_dir_path(__FILE__));
define('YIARI_DONASI_KUKANG_URL', plugin_dir_url(__FILE__));
define('YIARI_DONASI_KUKANG_BASENAME', plugin_basename(__FILE__));

// Autoload function for modular structure
spl_autoload_register(function($class_name) {
    // Only autoload classes that belong to this plugin
    if (strpos($class_name, 'YIARI_') !== 0) {
        return;
    }
    
    // Convert class name to file path
    $file_name = str_replace('_', '-', strtolower($class_name)) . '.php';
    $class_file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';

    // Check in modules directory
    $module_file = YIARI_DONASI_KUKANG_PATH . 'modules/' . $class_file_name;
    if (file_exists($module_file)) {
        require_once $module_file;
        return;
    }
    
    // Check in includes directory
    $include_file = YIARI_DONASI_KUKANG_PATH . 'includes/' . $class_file_name;
    if (file_exists($include_file)) {
        require_once $include_file;
        return;
    }
    
    // Check in admin directory
    $admin_file = YIARI_DONASI_KUKANG_PATH . 'admin/' . $class_file_name;
    if (file_exists($admin_file)) {
        require_once $admin_file;
        return;
    }

    // Check in public directory
    $public_file = YIARI_DONASI_KUKANG_PATH . 'public/' . $class_file_name;
    if (file_exists($public_file)) {
        require_once $public_file;
        return;
    }
});

// Include required core files
require_once YIARI_DONASI_KUKANG_PATH . 'includes/class-yiari-donasi-kukang-loader.php';
require_once YIARI_DONASI_KUKANG_PATH . 'includes/class-yiari-plugin-deactivator.php';

// Register activation/deactivation hooks
register_deactivation_hook(__FILE__, array('YIARI_Plugin_Deactivator', 'deactivate'));

// Initialize the plugin
function run_yiari_donasi_kukang() {
    $plugin = new YIARI_Donasi_Kukang_Loader();
    $plugin->run();
}

// Clear any conflicting cron jobs on initialization
add_action('init', 'yiari_clear_conflicting_crons', 1);
function yiari_clear_conflicting_crons() {
    // Clear the old problematic cron hook that causes the WordPress error
    $old_hook_timestamp = wp_next_scheduled('update_exchange_rates');
    if ($old_hook_timestamp) {
        wp_unschedule_event($old_hook_timestamp, 'update_exchange_rates');
    }
}

run_yiari_donasi_kukang();
?>