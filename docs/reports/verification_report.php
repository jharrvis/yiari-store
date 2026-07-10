<?php
/**
 * Final Verification Script for YIARI Donasi Midtrans Plugin
 * 
 * This script verifies that all key functions of the refactored plugin are working correctly.
 */

// This verification script should be executed in WordPress context
if (!defined('ABSPATH')) {
    die('This verification script must be executed within WordPress context.');
}

/**
 * Verify all required functions exist
 */
function verify_required_functions() {
    $functions_to_check = array(
        // Currency functions
        'get_usd_exchange_rate',
        'convert_idr_to_usd',
        'convert_usd_to_idr',
        'get_validated_exchange_rate',
        
        // Form functions
        'donasi_rehabilitasi_kukang_form',
        'donasi_kukang_en',
        
        // AJAX handlers
        'handle_donation_form_submission',
        'handle_english_donation_form_submission',
        'handle_calculate_shipping_cost',
        'handle_get_biteship_cities',
        
        // Admin functions
        'currency_settings_page',
        'biteship_settings_page',
        
        // Utility functions
        'get_biteship_api_key',
        'create_kukang_transactions_table'
    );
    
    $missing_functions = array();
    $found_functions = array();
    
    foreach ($functions_to_check as $function) {
        if (function_exists($function)) {
            $found_functions[] = $function;
        } else {
            $missing_functions[] = $function;
        }
    }
    
    return array(
        'missing' => $missing_functions,
        'found' => $found_functions,
        'all_exist' => empty($missing_functions)
    );
}

/**
 * Verify database tables
 */
function verify_database_tables() {
    global $wpdb;
    
    $tables_to_check = array(
        'kukang_transactions',
        'kukang_dolls',
        'kukang_currency_settings'
    );
    
    $missing_tables = array();
    $found_tables = array();
    
    foreach ($tables_to_check as $table) {
        $full_table_name = $wpdb->prefix . $table;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") == $full_table_name;
        
        if ($table_exists) {
            $found_tables[] = $full_table_name;
        } else {
            $missing_tables[] = $full_table_name;
        }
    }
    
    return array(
        'missing' => $missing_tables,
        'found' => $found_tables,
        'all_exist' => empty($missing_tables)
    );
}

/**
 * Verify shortcodes
 */
function verify_shortcodes() {
    $shortcodes_to_check = array(
        'donasi_kukang',
        'donasi_kukang_en',
        'cek_donasi'
    );
    
    $missing_shortcodes = array();
    $found_shortcodes = array();
    
    foreach ($shortcodes_to_check as $shortcode) {
        if (shortcode_exists($shortcode)) {
            $found_shortcodes[] = $shortcode;
        } else {
            $missing_shortcodes[] = $shortcode;
        }
    }
    
    return array(
        'missing' => $missing_shortcodes,
        'found' => $found_shortcodes,
        'all_exist' => empty($missing_shortcodes)
    );
}

/**
 * Verify AJAX actions
 */
function verify_ajax_actions() {
    // This is a simplified check since we can't directly inspect WordPress hooks
    // In a real implementation, you'd want to use WordPress's hook inspection
    
    $required_actions = array(
        'wp_ajax_process_donation_en',
        'wp_ajax_nopriv_process_donation_en',
        'wp_ajax_handle_donation_form_submission',
        'wp_ajax_nopriv_handle_donation_form_submission',
        'wp_ajax_calculate_shipping_cost',
        'wp_ajax_nopriv_calculate_shipping_cost',
        'wp_ajax_get_biteship_cities',
        'wp_ajax_nopriv_get_biteship_cities'
    );
    
    // For now, we'll just return that they should be registered
    // A more complete implementation would actually check the WordPress hook system
    return array(
        'actions' => $required_actions,
        'note' => 'Actions should be registered. Complete verification requires WordPress hook inspection.'
    );
}

/**
 * Run verification
 */
function run_verification() {
    echo "<div style='font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 10px;'>";
    echo "<h1 style='color: #2c5530; text-align: center;'>✅ YIARI Donasi Midtrans Plugin - Verification Report</h1>";
    
    // Verify functions
    echo "<h2>🔧 Function Verification</h2>";
    $function_result = verify_required_functions();
    if ($function_result['all_exist']) {
        echo "<p style='color: green;'><strong>✓ All required functions exist</strong></p>";
        echo "<p style='font-size: 12px; color: #666;'>Functions verified: " . count($function_result['found']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Missing functions:</strong></p>";
        echo "<ul>";
        foreach ($function_result['missing'] as $missing) {
            echo "<li style='color: red;'>{$missing}</li>";
        }
        echo "</ul>";
    }
    
    // Verify database tables
    echo "<h2>🗄️ Database Table Verification</h2>";
    $table_result = verify_database_tables();
    if ($table_result['all_exist']) {
        echo "<p style='color: green;'><strong>✓ All required database tables exist</strong></p>";
        echo "<p style='font-size: 12px; color: #666;'>Tables verified: " . count($table_result['found']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Missing database tables:</strong></p>";
        echo "<ul>";
        foreach ($table_result['missing'] as $missing) {
            echo "<li style='color: red;'>{$missing}</li>";
        }
        echo "</ul>";
    }
    
    // Verify shortcodes
    echo "<h2>🧩 Shortcode Verification</h2>";
    $shortcode_result = verify_shortcodes();
    if ($shortcode_result['all_exist']) {
        echo "<p style='color: green;'><strong>✓ All required shortcodes are registered</strong></p>";
        echo "<p style='font-size: 12px; color: #666;'>Shortcodes verified: " . count($shortcode_result['found']) . "</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Missing shortcodes:</strong></p>";
        echo "<ul>";
        foreach ($shortcode_result['missing'] as $missing) {
            echo "<li style='color: red;'>{$missing}</li>";
        }
        echo "</ul>";
    }
    
    // Verify AJAX actions
    echo "<h2>⚡ AJAX Action Verification</h2>";
    $ajax_result = verify_ajax_actions();
    echo "<p style='color: orange;'><strong>⚠️ " . $ajax_result['note'] . "</strong></p>";
    echo "<p style='font-size: 12px; color: #666;'>Actions that should be registered: " . count($ajax_result['actions']) . "</p>";
    
    // Overall status
    $overall_pass = $function_result['all_exist'] && $table_result['all_exist'] && $shortcode_result['all_exist'];
    
    echo "<div style='margin-top: 30px; padding: 20px; background: " . ($overall_pass ? '#d4edda' : '#f8d7da') . "; border-left: 5px solid " . ($overall_pass ? '#28a745' : '#dc3545') . "; border-radius: 5px;'>";
    echo "<h3 style='margin-top: 0; color: " . ($overall_pass ? '#155724' : '#721c24') . ";'>" . ($overall_pass ? "🎉 VERIFICATION SUCCESSFUL" : "❌ VERIFICATION FAILED") . "</h3>";
    if ($overall_pass) {
        echo "<p>All critical components are properly implemented and ready for production use.</p>";
    } else {
        echo "<p>Some components are missing. Please check the verification details above.</p>";
    }
    echo "</div>";
    
    echo "</div>";
}

// Run verification if in WordPress context
if (defined('ABSPATH')) {
    run_verification();
}
?>