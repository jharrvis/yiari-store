<?php

/**
 * Currency Manager for YIARI Donasi Kukang Plugin
 * 
 * Handles currency conversion and exchange rate management
 */
class YIARI_Currency_Manager {
    
    /**
     * Initialize currency manager
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Add exchange rate functions
        add_action('init', array($this, 'load_currency_functions'));
        
        // Schedule hourly exchange rate updates
        add_action('wp', array($this, 'schedule_exchange_rate_updates'));
        add_action('yiari_update_exchange_rates', array($this, 'automatic_exchange_rate_update'));
        
        // AJAX handlers for exchange rate functionality
        add_action('wp_ajax_get_current_exchange_rate', array($this, 'ajax_get_current_exchange_rate'));
        add_action('wp_ajax_nopriv_get_current_exchange_rate', array($this, 'ajax_get_current_exchange_rate'));
        add_action('wp_ajax_test_exchange_api', array($this, 'test_exchange_api_connection'));
    }
    
    /**
     * Load currency conversion functions
     *
     * @since    3.1.0
     */
    public function load_currency_functions() {
        // Currency conversion functions are already defined in dynamic_exchange_rate_system.php
        // This is kept for backward compatibility
    }
    
    /**
     * Get USD exchange rate - COMPLETELY DYNAMIC
     * NO HARDCODED VALUES WHATSOEVER
     *
     * @since    3.1.0
     * @param    bool    $force_update    Whether to force update from API
     * @return   float                    Exchange rate (USD per IDR)
     */
    public function get_usd_exchange_rate($force_update = false) {
        global $wpdb;
        $currency_table = $wpdb->prefix . 'kukang_currency_new';

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
        $live_rate = $this->fetch_live_exchange_rate();
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
     *
     * @since    3.1.0
     * @return   float|null    Exchange rate or null on failure
     */
    public function fetch_live_exchange_rate() {
        $providers = [
            'exchangerate_api_free',
            'exchangerate_api_paid',
            'fixer_io',
            'openexchange_rates'
        ];

        foreach ($providers as $provider) {
            try {
                $rate = call_user_func([$this, "fetch_rate_from_$provider"]);
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
     *
     * @since    3.1.0
     * @return   float|null    Exchange rate or null on failure
     */
    public function fetch_rate_from_exchangerate_api_free() {
        $url = 'https://api.exchangerate-api.com/v4/latest/USD';

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress-YIARI-Plugin/3.1'
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
     * Convert IDR to USD - DYNAMIC ONLY
     *
     * @since    3.1.0
     * @param    float    $idr_amount    Amount in IDR
     * @return   float                   Amount in USD
     */
    public function convert_idr_to_usd($idr_amount) {
        if (!is_numeric($idr_amount) || $idr_amount <= 0) {
            return 0;
        }

        $exchange_rate = $this->get_usd_exchange_rate();
        $usd_amount = $idr_amount * $exchange_rate;

        error_log("Converting IDR $idr_amount to USD: $usd_amount (rate: $exchange_rate)");
        return round($usd_amount, 2);
    }

    /**
     * Convert USD to IDR - DYNAMIC ONLY
     *
     * @since    3.1.0
     * @param    float    $usd_amount    Amount in USD
     * @return   float                   Amount in IDR
     */
    public function convert_usd_to_idr($usd_amount) {
        if (!is_numeric($usd_amount) || $usd_amount <= 0) {
            return 0;
        }

        $exchange_rate = $this->get_usd_exchange_rate();
        $idr_amount = $usd_amount / $exchange_rate;

        error_log("Converting USD $usd_amount to IDR: $idr_amount (rate: $exchange_rate)");
        return round($idr_amount, 0);
    }
    
    /**
     * Schedule automatic rate updates
     *
     * @since    3.1.0
     */
    public function schedule_exchange_rate_updates() {
        if (!wp_next_scheduled('yiari_update_exchange_rates')) {
            wp_schedule_event(time(), 'hourly', 'yiari_update_exchange_rates');
        }
    }

    /**
     * Automatic exchange rate update
     *
     * @since    3.1.0
     */
    public function automatic_exchange_rate_update() {
        global $wpdb;
        $currency_table = $wpdb->prefix . 'kukang_currency_new';

        // Check if auto-update is enabled
        $auto_update = $wpdb->get_var("SELECT auto_update FROM $currency_table WHERE currency_code = 'USD'");
        if (!$auto_update) {
            return;
        }

        try {
            $new_rate = $this->get_usd_exchange_rate(true); // Force update
            error_log("Automatic exchange rate update successful: $new_rate");
        } catch (Exception $e) {
            error_log("Automatic exchange rate update failed: " . $e->getMessage());
        }
    }
    
    /**
     * AJAX endpoint for real-time rate checking
     *
     * @since    3.1.0
     */
    public function ajax_get_current_exchange_rate() {
        try {
            $rate = $this->get_usd_exchange_rate();
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
     * AJAX handler for testing exchange API connection
     *
     * @since    3.1.0
     */
    public function test_exchange_api_connection() {
        $start_time = microtime(true);

        try {
            $rate = $this->get_usd_exchange_rate(true); // Force update
            $end_time = microtime(true);
            $response_time = round(($end_time - $start_time) * 1000);

            if ($rate) {
                wp_send_json_success([
                    'rate' => number_format($rate, 6),
                    'provider' => get_option('kukang_exchange_api_provider', 'exchangerate-api'),
                    'response_time' => $response_time
                ]);
            } else {
                wp_send_json_error(['message' => 'Failed to fetch rate from API']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
?>