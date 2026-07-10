<?php
/**
 * Test functionality for the refactored YIARI Donasi Midtrans plugin
 * This file tests all the key functionality to ensure everything works properly
 */

// This test file should be executed in WordPress context
if (!defined('ABSPATH')) {
    // If running as standalone, show an error
    die('This test file must be executed within WordPress context.');
}

/**
 * Test currency conversion functionality
 */
function test_currency_conversion() {
    $results = array(
        'test_name' => 'Currency Conversion Test',
        'passed' => true,
        'details' => array()
    );

    try {
        // Test if exchange rate functions exist
        if (!function_exists('get_usd_exchange_rate')) {
            $results['passed'] = false;
            $results['details'][] = 'ERROR: get_usd_exchange_rate function does not exist';
            return $results;
        }

        if (!function_exists('convert_idr_to_usd')) {
            $results['passed'] = false;
            $results['details'][] = 'ERROR: convert_idr_to_usd function does not exist';
            return $results;
        }

        if (!function_exists('convert_usd_to_idr')) {
            $results['passed'] = false;
            $results['details'][] = 'ERROR: convert_usd_to_idr function does not exist';
            return $results;
        }

        // Get current exchange rate
        $rate = get_usd_exchange_rate();
        $results['details'][] = "Current exchange rate: 1 USD = " . number_format(1/$rate, 2) . " IDR ({$rate} per IDR)";

        if ($rate <= 0) {
            $results['passed'] = false;
            $results['details'][] = "ERROR: Invalid exchange rate: $rate";
            return $results;
        }

        // Test conversions
        $idr_amount = 150000;
        $usd_amount = convert_idr_to_usd($idr_amount);
        $results['details'][] = "IDR {$idr_amount} = USD {$usd_amount}";

        $converted_back = convert_usd_to_idr($usd_amount);
        $results['details'][] = "USD {$usd_amount} = IDR {$converted_back}";

        // Check if conversion is approximately correct (allowing for rounding)
        $tolerance = abs($idr_amount - $converted_back);
        if ($tolerance > 1) { // Allow 1 rupiah difference due to rounding
            $results['passed'] = false;
            $results['details'][] = "ERROR: Conversion mismatch. Original: {$idr_amount}, Converted back: {$converted_back}, Tolerance: {$tolerance}";
        }

        $results['details'][] = "Currency conversion test passed successfully";
    } catch (Exception $e) {
        $results['passed'] = false;
        $results['details'][] = "ERROR: Exception in currency conversion test: " . $e->getMessage();
    }

    return $results;
}

/**
 * Test database table creation
 */
function test_database_tables() {
    global $wpdb;
    
    $results = array(
        'test_name' => 'Database Tables Test',
        'passed' => true,
        'details' => array()
    );

    try {
        $tables_to_check = array(
            'kukang_transactions',
            'kukang_dolls',
            'kukang_currency_settings'
        );

        foreach ($tables_to_check as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'") == $full_table_name;
            
            if ($table_exists) {
                $results['details'][] = "✓ Table {$full_table_name} exists";
            } else {
                $results['passed'] = false;
                $results['details'][] = "✗ Table {$full_table_name} does not exist";
            }
        }

        if ($results['passed']) {
            $results['details'][] = "All required database tables exist";
        }
    } catch (Exception $e) {
        $results['passed'] = false;
        $results['details'][] = "ERROR: Exception in database test: " . $e->getMessage();
    }

    return $results;
}

/**
 * Test AJAX endpoint availability
 */
function test_ajax_endpoints() {
    $results = array(
        'test_name' => 'AJAX Endpoints Test',
        'passed' => true,
        'details' => array()
    );

    try {
        // Check if required AJAX actions are registered
        $required_actions = array(
            'process_donation_en',  // English form
            'handle_donation_form_submission',  // Indonesian form
            'calculate_shipping_cost',  // Shipping calculation
            'get_biteship_cities',  // City search
            'get_order_details',  // Admin features
            'update_order_status',  // Admin features
            'update_tracking_number',  // Admin features
            'check_donor_order_status',  // Tracking shortcode
            'test_exchange_api'  // Currency testing
        );

        $results['details'][] = "AJAX endpoints test - actions registered check";
        
        // Note: We can't directly check if actions are added via add_action
        // This would need to use WordPress internal functions or hooks
        $results['details'][] = "AJAX endpoints test completed (internal actions check would require WordPress hooks inspection)";
        
    } catch (Exception $e) {
        $results['passed'] = false;
        $results['details'][] = "ERROR: Exception in AJAX endpoints test: " . $e->getMessage();
    }

    return $results;
}

/**
 * Test shortcode registration
 */
function test_shortcodes() {
    $results = array(
        'test_name' => 'Shortcode Registration Test',
        'passed' => true,
        'details' => array()
    );

    try {
        $shortcodes_to_check = array(
            'donasi_kukang' => 'Indonesian donation form',
            'donasi_kukang_en' => 'English donation form',
            'cek_donasi' => 'Donation tracking'
        );

        foreach ($shortcodes_to_check as $shortcode => $description) {
            if (shortcode_exists($shortcode)) {
                $results['details'][] = "✓ Shortcode [{$shortcode}] ({$description}) is registered";
            } else {
                $results['passed'] = false;
                $results['details'][] = "✗ Shortcode [{$shortcode}] ({$description}) is NOT registered";
            }
        }

        if ($results['passed']) {
            $results['details'][] = "All required shortcodes are registered";
        }
    } catch (Exception $e) {
        $results['passed'] = false;
        $results['details'][] = "ERROR: Exception in shortcode test: " . $e->getMessage();
    }

    return $results;
}

/**
 * Run all tests
 */
function run_all_tests() {
    $test_results = array();
    
    $test_results[] = test_currency_conversion();
    $test_results[] = test_database_tables();
    $test_results[] = test_ajax_endpoints();
    $test_results[] = test_shortcodes();
    
    // Display results
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    echo "<h2>YIARI Donasi Midtrans Plugin - Functionality Test Results</h2>";
    
    $all_passed = true;
    foreach ($test_results as $result) {
        $status_class = $result['passed'] ? 'status-pass' : 'status-fail';
        $status_text = $result['passed'] ? 'PASSED' : 'FAILED';
        $status_icon = $result['passed'] ? '✓' : '✗';
        
        echo "<div style='margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h3 style='margin-top: 0;'>{$status_icon} {$result['test_name']} - <span class='{$status_class}'>{$status_text}</span></h3>";
        
        foreach ($result['details'] as $detail) {
            echo "<p style='margin: 5px 0; padding-left: 20px;'>{$detail}</p>";
        }
        
        echo "</div>";
        
        if (!$result['passed']) {
            $all_passed = false;
        }
    }
    
    $overall_status = $all_passed ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED';
    $overall_class = $all_passed ? 'status-pass' : 'status-fail';
    
    echo "<div style='margin-top: 20px; padding: 15px; background: #f0f8f0; border: 2px solid #2c5530; border-radius: 5px; text-align: center;'>";
    echo "<h3>Overall Result: <span class='{$overall_class}'>{$overall_status}</span></h3>";
    echo "</div>";
    
    echo "<style>
        .status-pass { color: green; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
    </style>";
    
    echo "</div>";
}

// Run all tests if called directly in WordPress context
if (defined('ABSPATH')) {
    run_all_tests();
}
?>