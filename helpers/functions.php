<?php

/**
 * Helper functions for YIARI Donasi Kukang Plugin
 *
 * @since 3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get formatted price with currency symbol
 *
 * @param float  $price    Price amount
 * @param string $currency Currency code (IDR/USD)
 * @return string          Formatted price
 */
function yiari_format_price($price, $currency = 'IDR') {
    if ($currency === 'USD') {
        return '$' . number_format($price, 2);
    } else {
        return 'Rp ' . number_format($price, 0, ',', '.');
    }
}

/**
 * Get current exchange rate from database
 *
 * @return float Exchange rate
 */
function yiari_get_exchange_rate() {
    global $wpdb;
    $currency_table = $wpdb->prefix . 'kukang_currency_settings';

    $rate = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(manual_rate, api_rate) as rate FROM $currency_table WHERE currency_code = %s AND is_active = 1",
        'USD'
    ));

    return $rate ? floatval($rate) : 15000; // Default fallback rate
}

/**
 * Convert IDR to USD
 *
 * @param float $idr_amount IDR amount
 * @return float            USD amount
 */
function yiari_idr_to_usd($idr_amount) {
    $rate = yiari_get_exchange_rate();
    return $idr_amount / $rate;
}

/**
 * Convert USD to IDR
 *
 * @param float $usd_amount USD amount
 * @return float            IDR amount
 */
function yiari_usd_to_idr($usd_amount) {
    $rate = yiari_get_exchange_rate();
    return $usd_amount * $rate;
}

/**
 * Generate unique order ID
 *
 * @param string $prefix Order ID prefix
 * @return string        Unique order ID
 */
function yiari_generate_order_id($prefix = 'KUKANG') {
    $timestamp = date('Ymd-His');
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . '-' . $timestamp . '-' . $random;
}

/**
 * Sanitize phone number
 *
 * @param string $phone Phone number
 * @return string       Sanitized phone number
 */
function yiari_sanitize_phone($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Convert 08xx to 628xx
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }

    // Ensure it starts with 62
    if (substr($phone, 0, 2) !== '62') {
        $phone = '62' . $phone;
    }

    return $phone;
}

/**
 * Get translation text based on language
 *
 * @param string $text     Text key
 * @param string $language Language code (id/en)
 * @return string          Translated text
 */
function yiari_get_text($text, $language = 'id') {
    $translations = array(
        'id' => array(
            'required_field' => 'Field ini wajib diisi',
            'invalid_email' => 'Format email tidak valid',
            'invalid_phone' => 'Format nomor telepon tidak valid',
            'minimum_quantity' => 'Pilih minimal 1 boneka kukang',
            'processing_donation' => 'Sedang memproses donasi...',
            'payment_success' => 'Pembayaran berhasil',
            'payment_failed' => 'Pembayaran gagal'
        ),
        'en' => array(
            'required_field' => 'This field is required',
            'invalid_email' => 'Invalid email format',
            'invalid_phone' => 'Invalid phone number format',
            'minimum_quantity' => 'Select at least 1 kukang doll',
            'processing_donation' => 'Processing donation...',
            'payment_success' => 'Payment successful',
            'payment_failed' => 'Payment failed'
        )
    );

    return isset($translations[$language][$text]) ? $translations[$language][$text] : $text;
}

/**
 * Log error to WordPress debug log
 *
 * @param string $message Error message
 * @param string $context Context information
 */
function yiari_log_error($message, $context = '') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[YIARI Donasi Kukang] ' . $message;
        if ($context) {
            $log_message .= ' - Context: ' . $context;
        }
        error_log($log_message);
    }
}

/**
 * Check if Midtrans library is available
 *
 * @return bool
 */
function yiari_is_midtrans_available() {
    return class_exists('Midtrans\\Config');
}

/**
 * Check if PHPSpreadsheet is available
 *
 * @return bool
 */
function yiari_is_phpspreadsheet_available() {
    return defined('KUKANG_PHPSPREADSHEET_AVAILABLE') && KUKANG_PHPSPREADSHEET_AVAILABLE;
}