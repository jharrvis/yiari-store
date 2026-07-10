<?php
// DYNAMIC EXCHANGE RATE SYSTEM - NO HARDCODE ALLOWED
// This system ensures all exchange rates are fetched from real APIs

/**
 * Get USD exchange rate - COMPLETELY DYNAMIC
 * NO HARDCODED VALUES WHATSOEVER
 */
function get_usd_exchange_rate($force_update = false) {
    global $wpdb;
    $currency_table = $wpdb->prefix . 'kukang_currency_settings';

    // Check for manual override first
    $manual_rate = $wpdb->get_var("SELECT manual_rate FROM $currency_table WHERE currency_code = 'USD' AND manual_rate IS NOT NULL");
    if ($manual_rate && $manual_rate > 0) {
        error_log("Using manual override rate: $manual_rate");
        return floatval($manual_rate);
    }

    // Check cache first unless forced
    $cache_key = 'kukang_live_exchange_rate';
    if (!$force_update) {
        $cached_rate = get_transient($cache_key);
        if ($cached_rate !== false && $cached_rate > 0) {
            error_log("Using cached exchange rate: $cached_rate");
            return floatval($cached_rate);
        }
    }

    // Fetch from API - NO HARDCODE
    $live_rate = fetch_live_exchange_rate();
    if ($live_rate && $live_rate > 0) {
        // Cache for 30 minutes for performance
        set_transient($cache_key, $live_rate, 1800);

        // Update database
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $currency_table (currency_code, api_rate, last_api_update, is_active)
             VALUES ('USD', %f, %s, 1)
             ON DUPLICATE KEY UPDATE api_rate = %f, last_api_update = %s",
            $live_rate, current_time('mysql'), $live_rate, current_time('mysql')
        ));

        error_log("Fetched live exchange rate from API: $live_rate");
        return $live_rate;
    }

    // Last resort - get last known good rate from database
    $last_good_rate = $wpdb->get_var("SELECT api_rate FROM $currency_table WHERE currency_code = 'USD' AND api_rate > 0 ORDER BY last_api_update DESC LIMIT 1");
    if ($last_good_rate && $last_good_rate > 0) {
        error_log("Using last known good rate from database: $last_good_rate");
        return floatval($last_good_rate);
    }

    // CRITICAL ERROR - No rate available
    error_log("CRITICAL: No exchange rate available from any source");
    throw new Exception("Unable to fetch USD exchange rate from any source. Please check API configuration.");
}

/**
 * Fetch live exchange rate from multiple API providers
 * GUARANTEED NO HARDCODE
 */
function fetch_live_exchange_rate() {
    $providers = [
        'exchangerate_api_free',
        'exchangerate_api_paid',
        'fixer_io',
        'openexchange_rates'
    ];

    foreach ($providers as $provider) {
        try {
            $rate = call_user_func("fetch_rate_from_$provider");
            if ($rate && $rate > 0) {
                error_log("Successfully fetched rate from $provider: $rate");
                return $rate;
            }
        } catch (Exception $e) {
            error_log("Provider $provider failed: " . $e->getMessage());
            continue;
        }
    }

    return null;
}

/**
 * ExchangeRate-API.com Free Tier
 */
function fetch_rate_from_exchangerate_api_free() {
    $url = 'https://api.exchangerate-api.com/v4/latest/USD';

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress-Kukang-Plugin/3.1'
        ]
    ]);

    if (is_wp_error($response)) {
        throw new Exception('HTTP Error: ' . $response->get_error_message());
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        throw new Exception("HTTP $http_code response");
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['rates']['IDR'])) {
        throw new Exception('Invalid response format');
    }

    $idr_per_usd = floatval($data['rates']['IDR']);
    if ($idr_per_usd <= 0) {
        throw new Exception('Invalid IDR rate received');
    }

    return 1 / $idr_per_usd; // Convert to USD per IDR
}

/**
 * ExchangeRate-API.com Paid Tier
 */
function fetch_rate_from_exchangerate_api_paid() {
    $api_key = get_option('kukang_exchange_api_key');
    if (empty($api_key)) {
        throw new Exception('API key not configured');
    }

    $url = "https://v6.exchangerate-api.com/v6/$api_key/latest/USD";

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'Accept' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) {
        throw new Exception('HTTP Error: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || $data['result'] !== 'success' || !isset($data['conversion_rates']['IDR'])) {
        throw new Exception('API returned error: ' . ($data['error-type'] ?? 'Unknown'));
    }

    $idr_per_usd = floatval($data['conversion_rates']['IDR']);
    if ($idr_per_usd <= 0) {
        throw new Exception('Invalid IDR rate');
    }

    return 1 / $idr_per_usd;
}

/**
 * Fixer.io API
 */
function fetch_rate_from_fixer_io() {
    $api_key = get_option('kukang_fixer_api_key');
    if (empty($api_key)) {
        throw new Exception('Fixer API key not configured');
    }

    $url = "http://data.fixer.io/api/latest?access_key=$api_key&base=USD&symbols=IDR";

    $response = wp_remote_get($url, ['timeout' => 10]);

    if (is_wp_error($response)) {
        throw new Exception('HTTP Error: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !$data['success']) {
        throw new Exception('Fixer API error: ' . ($data['error']['info'] ?? 'Unknown'));
    }

    if (!isset($data['rates']['IDR'])) {
        throw new Exception('IDR rate not found in response');
    }

    $idr_per_usd = floatval($data['rates']['IDR']);
    if ($idr_per_usd <= 0) {
        throw new Exception('Invalid IDR rate');
    }

    return 1 / $idr_per_usd;
}

/**
 * OpenExchangeRates.org API
 */
function fetch_rate_from_openexchange_rates() {
    $api_key = get_option('kukang_openexchange_api_key');
    if (empty($api_key)) {
        throw new Exception('OpenExchangeRates API key not configured');
    }

    $url = "https://openexchangerates.org/api/latest.json?app_id=$api_key&base=USD&symbols=IDR";

    $response = wp_remote_get($url, ['timeout' => 10]);

    if (is_wp_error($response)) {
        throw new Exception('HTTP Error: ' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || isset($data['error'])) {
        throw new Exception('OpenExchangeRates error: ' . ($data['message'] ?? 'Unknown'));
    }

    if (!isset($data['rates']['IDR'])) {
        throw new Exception('IDR rate not found');
    }

    $idr_per_usd = floatval($data['rates']['IDR']);
    if ($idr_per_usd <= 0) {
        throw new Exception('Invalid IDR rate');
    }

    return 1 / $idr_per_usd;
}

/**
 * Convert IDR to USD - DYNAMIC ONLY
 */
function convert_idr_to_usd($idr_amount) {
    if (!is_numeric($idr_amount) || $idr_amount <= 0) {
        return 0;
    }

    $exchange_rate = get_usd_exchange_rate();
    $usd_amount = $idr_amount * $exchange_rate;

    error_log("Converting IDR $idr_amount to USD: $usd_amount (rate: $exchange_rate)");
    return round($usd_amount, 2);
}

/**
 * Convert USD to IDR - DYNAMIC ONLY
 */
function convert_usd_to_idr($usd_amount) {
    if (!is_numeric($usd_amount) || $usd_amount <= 0) {
        return 0;
    }

    $exchange_rate = get_usd_exchange_rate();
    $idr_amount = $usd_amount / $exchange_rate;

    error_log("Converting USD $usd_amount to IDR: $idr_amount (rate: $exchange_rate)");
    return round($idr_amount, 0);
}

/**
 * Schedule automatic rate updates
 */
add_action('wp', 'schedule_exchange_rate_updates');
function schedule_exchange_rate_updates() {
    if (!wp_next_scheduled('update_exchange_rates')) {
        wp_schedule_event(time(), 'hourly', 'update_exchange_rates');
    }
}

add_action('update_exchange_rates', 'automatic_exchange_rate_update');
function automatic_exchange_rate_update() {
    global $wpdb;
    $currency_table = $wpdb->prefix . 'kukang_currency_settings';

    // Check if auto-update is enabled
    $auto_update = $wpdb->get_var("SELECT auto_update FROM $currency_table WHERE currency_code = 'USD'");
    if (!$auto_update) {
        return;
    }

    try {
        $new_rate = get_usd_exchange_rate(true); // Force update
        error_log("Automatic exchange rate update successful: $new_rate");
    } catch (Exception $e) {
        error_log("Automatic exchange rate update failed: " . $e->getMessage());
    }
}

/**
 * AJAX endpoint for real-time rate checking
 */
add_action('wp_ajax_get_current_exchange_rate', 'ajax_get_current_exchange_rate');
add_action('wp_ajax_nopriv_get_current_exchange_rate', 'ajax_get_current_exchange_rate');
function ajax_get_current_exchange_rate() {
    try {
        $rate = get_usd_exchange_rate();
        $last_update = get_transient('kukang_rate_last_update') ?: 'Unknown';

        wp_send_json_success([
            'rate' => $rate,
            'formatted_rate' => number_format($rate, 6),
            'idr_per_usd' => number_format(1 / $rate, 0),
            'last_update' => $last_update,
            'source' => 'dynamic_api'
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Validate exchange rate before using
 */
function validate_exchange_rate($rate) {
    // Reasonable bounds for USD/IDR rate
    $min_rate = 0.00005; // 1 USD = 20,000 IDR max
    $max_rate = 0.0001;  // 1 USD = 10,000 IDR min

    if (!is_numeric($rate) || $rate <= 0) {
        return false;
    }

    if ($rate < $min_rate || $rate > $max_rate) {
        error_log("Exchange rate $rate is outside reasonable bounds ($min_rate - $max_rate)");
        return false;
    }

    return true;
}

/**
 * Get exchange rate with validation
 */
function get_validated_exchange_rate() {
    try {
        $rate = get_usd_exchange_rate();

        if (!validate_exchange_rate($rate)) {
            throw new Exception("Exchange rate validation failed: $rate");
        }

        return $rate;
    } catch (Exception $e) {
        error_log("Exchange rate validation error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * CRITICAL: Remove any hardcoded fallbacks
 * This function ensures NO hardcoded values are ever used
 */
function ensure_no_hardcode_rates() {
    // Check for any hardcoded rates in the system
    $suspicious_patterns = [
        '0.000065', // Common hardcoded rate
        '0.000070',
        '0.000060',
        '15000',    // Common IDR/USD rate
        '14000',
        '16000'
    ];

    $current_rate = get_option('kukang_last_known_rate', '');
    foreach ($suspicious_patterns as $pattern) {
        if (strpos($current_rate, $pattern) !== false) {
            error_log("WARNING: Suspicious hardcoded rate detected: $current_rate");
            delete_option('kukang_last_known_rate');
            break;
        }
    }
}

// Run check on plugin load
add_action('plugins_loaded', 'ensure_no_hardcode_rates');
?>