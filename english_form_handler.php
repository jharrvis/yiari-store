<?php
/**
 * English form handler functions have been moved to the main plugin file
 * This file is deprecated but kept for compatibility
 */

/**
 * Function to check if Midtrans supports credit card only restriction
 * This is specifically for USD transactions
 */
function midtrans_supports_credit_card_only() {
    // Check if Midtrans library is available
    if (!class_exists('\Midtrans\Snap')) {
        return false;
    }

    // Check if server key is configured
    $server_key = get_option('midtrans_server_key', '');
    if (empty($server_key)) {
        return false;
    }

    return true;
}

/**
 * Get available payment methods for English form (USD)
 * Returns only credit card for USD transactions
 */
function get_english_form_payment_methods() {
    return [
        'credit_card' => [
            'name' => 'Credit Card',
            'description' => 'Visa, Mastercard, JCB',
            'enabled' => true
        ]
    ];
}

/**
 * Validate USD transaction constraints
 */
function validate_usd_transaction($transaction_data) {
    $errors = [];

    // Check if currency is USD
    if ($transaction_data['currency'] !== 'USD') {
        $errors[] = 'Invalid currency for English form';
    }

    // Check if payment method is restricted to credit card
    if (!isset($transaction_data['payment_restriction']) || $transaction_data['payment_restriction'] !== 'credit_card_only') {
        $errors[] = 'USD transactions must use credit card only';
    }

    // Check minimum amount (example: $5 minimum)
    if (floatval($transaction_data['usd_amount']) < 5.00) {
        $errors[] = 'Minimum order amount is $5.00 USD';
    }

    // Check if shipping is to Indonesia only
    if (empty($transaction_data['city_id'])) {
        $errors[] = 'Shipping city must be selected (Indonesia only)';
    }

    return $errors;
}

/**
 * Log English form transaction for audit
 */
function log_english_form_transaction($order_id, $transaction_data) {
    error_log("=== USD TRANSACTION LOG ===");
    error_log("Order ID: " . $order_id);
    error_log("Currency: USD");
    error_log("USD Amount: " . $transaction_data['usd_amount']);
    error_log("Exchange Rate: " . $transaction_data['exchange_rate']);
    error_log("IDR Equivalent: " . $transaction_data['idr_total']);
    error_log("Payment Method: Credit Card Only");
    error_log("Customer: " . $transaction_data['customer_name']);
    error_log("Email: " . $transaction_data['email']);
    error_log("Shipping: " . $transaction_data['city_name'] . ", " . $transaction_data['province']);
    error_log("=== END USD TRANSACTION LOG ===");
}
?>