<?php
// Fixed Currency Settings Page with proper save functionality
function currency_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $currency_table = $wpdb->prefix . 'kukang_currency_settings';

    // Handle form submissions with nonce verification
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['currency_nonce'], 'currency_settings_action')) {

        // Update API Key
        if (isset($_POST['update_api_key'])) {
            $api_key = sanitize_text_field($_POST['exchange_api_key']);
            update_option('kukang_exchange_api_key', $api_key);

            $api_provider = sanitize_text_field($_POST['api_provider']);
            update_option('kukang_exchange_api_provider', $api_provider);

            echo '<div class="notice notice-success"><p>API settings saved successfully!</p></div>';
        }

        // Update Exchange Rate Settings
        if (isset($_POST['update_rate'])) {
            $manual_rate = floatval($_POST['manual_rate']);
            $auto_update = isset($_POST['auto_update']) ? 1 : 0;

            // Update or insert currency settings
            $existing = $wpdb->get_row("SELECT * FROM $currency_table WHERE currency_code = 'USD'");

            $data = [
                'currency_code' => 'USD',
                'manual_rate' => $manual_rate > 0 ? $manual_rate : null,
                'auto_update' => $auto_update,
                'last_updated' => current_time('mysql'),
                'is_active' => 1
            ];

            if ($existing) {
                $wpdb->update($currency_table, $data, ['currency_code' => 'USD']);
            } else {
                $wpdb->insert($currency_table, $data);
            }

            if ($wpdb->last_error) {
                echo '<div class="notice notice-error"><p>Database error: ' . $wpdb->last_error . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>Exchange rate settings saved successfully!</p></div>';
            }
        }

        // Force refresh exchange rate from API
        if (isset($_POST['refresh_rate'])) {
            $new_rate = fetch_exchange_rate_from_api(true); // Force update
            if ($new_rate) {
                echo '<div class="notice notice-success"><p>Exchange rate refreshed: $' . number_format($new_rate, 6) . ' per IDR</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to fetch exchange rate from API</p></div>';
            }
        }
    }

    // Get current settings
    $current_rate = get_usd_exchange_rate();
    $api_key = get_option('kukang_exchange_api_key', '');
    $api_provider = get_option('kukang_exchange_api_provider', 'exchangerate-api');
    $currency_data = $wpdb->get_row("SELECT * FROM $currency_table WHERE currency_code = 'USD'");

    ?>
    <div class="wrap">
        <h1>💱 Currency & Exchange Rate Settings</h1>

        <!-- Current Rate Display -->
        <div class="card">
            <h2>📊 Current Exchange Rate</h2>
            <div style="font-size: 24px; font-weight: bold; color: #2271b1; margin: 20px 0;">
                $<?php echo number_format($current_rate, 6); ?> USD per 1 IDR
            </div>
            <div style="font-size: 16px; color: #666;">
                1 USD = Rp <?php echo number_format(1 / $current_rate, 0); ?> IDR
            </div>
            <?php if ($currency_data): ?>
                <p><small>Last updated: <?php echo $currency_data->last_updated; ?></small></p>
            <?php endif; ?>
        </div>

        <!-- API Configuration -->
        <div class="card">
            <h2>🔧 API Configuration</h2>
            <form method="post" action="">
                <?php wp_nonce_field('currency_settings_action', 'currency_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Exchange Rate API Provider</th>
                        <td>
                            <select name="api_provider" required>
                                <option value="exchangerate-api" <?php selected($api_provider, 'exchangerate-api'); ?>>
                                    ExchangeRate-API.com (FREE)
                                </option>
                                <option value="fixer" <?php selected($api_provider, 'fixer'); ?>>
                                    Fixer.io
                                </option>
                                <option value="openexchange" <?php selected($api_provider, 'openexchange'); ?>>
                                    OpenExchangeRates.org
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text"
                                   name="exchange_api_key"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   class="regular-text"
                                   placeholder="Enter your API key (optional for some providers)">
                            <p class="description">
                                Some providers offer free tier without API key.
                                For higher limits, register for API key.
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="update_api_key" class="button-primary" value="Save API Settings">
                </p>
            </form>
        </div>

        <!-- Exchange Rate Management -->
        <div class="card">
            <h2>⚙️ Exchange Rate Management</h2>
            <form method="post" action="">
                <?php wp_nonce_field('currency_settings_action', 'currency_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Auto Update from API</th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="auto_update"
                                       value="1"
                                       <?php checked($currency_data->auto_update ?? 1, 1); ?>>
                                Automatically fetch latest rates from API
                            </label>
                            <p class="description">When enabled, rates are updated automatically every hour</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Manual Override Rate</th>
                        <td>
                            <input type="number"
                                   name="manual_rate"
                                   value="<?php echo $currency_data->manual_rate ?? ''; ?>"
                                   step="0.000001"
                                   min="0"
                                   placeholder="<?php echo $current_rate; ?>"
                                   class="regular-text">
                            <p class="description">
                                Leave empty to use API rate. Set value to override with manual rate.
                                <br>Current API rate: $<?php echo number_format($current_rate, 6); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="update_rate" class="button-primary" value="Update Rate Settings">
                    <input type="submit" name="refresh_rate" class="button-secondary" value="Force Refresh from API" style="margin-left: 10px;">
                </p>
            </form>
        </div>

        <!-- Currency Converter Tool -->
        <div class="card">
            <h2>🔄 Currency Converter</h2>
            <div style="display: flex; gap: 20px; align-items: center; margin: 20px 0;">
                <div>
                    <label>IDR Amount:</label><br>
                    <input type="number" id="idr_amount" placeholder="150000" style="width: 150px;">
                </div>
                <div style="padding: 10px 0;">↔️</div>
                <div>
                    <label>USD Amount:</label><br>
                    <input type="number" id="usd_amount" placeholder="15.00" step="0.01" style="width: 150px;">
                </div>
            </div>
            <button type="button" onclick="convertCurrency()" class="button">Convert</button>
        </div>

        <!-- Test API Connection -->
        <div class="card">
            <h2>🧪 Test API Connection</h2>
            <button type="button" onclick="testAPIConnection()" class="button">Test Connection</button>
            <div id="api-test-result" style="margin-top: 10px;"></div>
        </div>

    </div>

    <script>
    function convertCurrency() {
        const rate = <?php echo $current_rate; ?>;
        const idrInput = document.getElementById('idr_amount');
        const usdInput = document.getElementById('usd_amount');

        if (idrInput.value) {
            usdInput.value = (parseFloat(idrInput.value) * rate).toFixed(2);
        } else if (usdInput.value) {
            idrInput.value = Math.round(parseFloat(usdInput.value) / rate);
        }
    }

    function testAPIConnection() {
        const resultDiv = document.getElementById('api-test-result');
        resultDiv.innerHTML = 'Testing API connection...';

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_exchange_api'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `<div class="notice notice-success inline"><p>
                    ✅ API Connection Successful<br>
                    Rate: $${data.data.rate}<br>
                    Provider: ${data.data.provider}<br>
                    Response time: ${data.data.response_time}ms
                </p></div>`;
            } else {
                resultDiv.innerHTML = `<div class="notice notice-error inline"><p>
                    ❌ API Connection Failed: ${data.data.message}
                </p></div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="notice notice-error inline"><p>
                ❌ Network Error: ${error.message}
            </p></div>`;
        });
    }
    </script>

    <style>
    .card {
        background: #fff;
        border: 1px solid #c3c4c7;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin: 20px 0;
        padding: 20px;
    }
    .notice.inline {
        display: block;
        margin: 5px 0 2px;
        padding: 5px 12px;
    }
    </style>
    <?php
}

// AJAX handler for testing API connection
add_action('wp_ajax_test_exchange_api', 'test_exchange_api_connection');
function test_exchange_api_connection() {
    $start_time = microtime(true);

    try {
        $rate = fetch_exchange_rate_from_api(true);
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

// Enhanced exchange rate fetching with multiple API providers
function fetch_exchange_rate_from_api($force_refresh = false) {
    $cache_key = 'kukang_usd_exchange_rate';
    $cache_duration = 3600; // 1 hour

    // Check cache first unless force refresh
    if (!$force_refresh) {
        $cached_rate = get_transient($cache_key);
        if ($cached_rate !== false) {
            return floatval($cached_rate);
        }
    }

    $api_provider = get_option('kukang_exchange_api_provider', 'exchangerate-api');
    $api_key = get_option('kukang_exchange_api_key', '');

    $rate = null;

    switch ($api_provider) {
        case 'exchangerate-api':
            $rate = fetch_from_exchangerate_api($api_key);
            break;

        case 'fixer':
            $rate = fetch_from_fixer_api($api_key);
            break;

        case 'openexchange':
            $rate = fetch_from_openexchange_api($api_key);
            break;

        default:
            // Try all providers as fallback
            $rate = fetch_from_exchangerate_api($api_key) ?:
                   fetch_from_fixer_api($api_key) ?:
                   fetch_from_openexchange_api($api_key);
    }

    if ($rate && $rate > 0) {
        // Cache the successful result
        set_transient($cache_key, $rate, $cache_duration);

        // Update database
        global $wpdb;
        $currency_table = $wpdb->prefix . 'kukang_currency_settings';
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $currency_table (currency_code, api_rate, last_api_update)
             VALUES ('USD', %f, %s)
             ON DUPLICATE KEY UPDATE api_rate = %f, last_api_update = %s",
            $rate, current_time('mysql'), $rate, current_time('mysql')
        ));

        error_log("Exchange rate updated successfully: $rate");
        return $rate;
    }

    // Fallback to last known rate
    $currency_table = $wpdb->prefix . 'kukang_currency_settings';
    $last_rate = $wpdb->get_var("SELECT api_rate FROM $currency_table WHERE currency_code = 'USD'");

    if ($last_rate) {
        error_log("Using last known exchange rate: $last_rate");
        return floatval($last_rate);
    }

    // Ultimate fallback
    error_log("All exchange rate sources failed, using fallback rate");
    return 0.000065; // Approximate rate as last resort
}

// ExchangeRate-API.com implementation
function fetch_from_exchangerate_api($api_key = '') {
    try {
        // Free tier endpoint
        $url = 'https://api.exchangerate-api.com/v4/latest/USD';

        // Paid tier with API key
        if (!empty($api_key)) {
            $url = "https://v6.exchangerate-api.com/v6/$api_key/latest/USD";
        }

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress-Kukang-Plugin/3.1'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception('HTTP Error: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['rates']['IDR'])) {
            throw new Exception('Invalid API response format');
        }

        $idr_per_usd = $data['rates']['IDR'];
        return 1 / $idr_per_usd; // Convert to USD per IDR

    } catch (Exception $e) {
        error_log("ExchangeRate-API error: " . $e->getMessage());
        return null;
    }
}

// Fixer.io implementation
function fetch_from_fixer_api($api_key) {
    if (empty($api_key)) {
        return null; // Fixer requires API key
    }

    try {
        $url = "http://data.fixer.io/api/latest?access_key=$api_key&base=USD&symbols=IDR";

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception('HTTP Error: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !$data['success'] || !isset($data['rates']['IDR'])) {
            throw new Exception('Invalid API response: ' . ($data['error']['info'] ?? 'Unknown error'));
        }

        $idr_per_usd = $data['rates']['IDR'];
        return 1 / $idr_per_usd;

    } catch (Exception $e) {
        error_log("Fixer.io API error: " . $e->getMessage());
        return null;
    }
}

// OpenExchangeRates implementation
function fetch_from_openexchange_api($api_key) {
    if (empty($api_key)) {
        return null; // OpenExchange requires API key
    }

    try {
        $url = "https://openexchangerates.org/api/latest.json?app_id=$api_key&base=USD&symbols=IDR";

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception('HTTP Error: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || isset($data['error']) || !isset($data['rates']['IDR'])) {
            throw new Exception('Invalid API response: ' . ($data['message'] ?? 'Unknown error'));
        }

        $idr_per_usd = $data['rates']['IDR'];
        return 1 / $idr_per_usd;

    } catch (Exception $e) {
        error_log("OpenExchangeRates API error: " . $e->getMessage());
        return null;
    }
}
?>