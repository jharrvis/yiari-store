<?php

/**
 * Shipping Manager for YIARI Donasi Kukang Plugin
 * 
 * Handles shipping calculations and Biteship API integration
 */
class YIARI_Shipping_Manager {
    
    /**
     * Initialize shipping manager
     *
     * @since    3.1.0
     */
    public function initialize() {
        // AJAX handlers for shipping functionality
        add_action('wp_ajax_get_biteship_cities', array($this, 'ajax_get_biteship_cities'));
        add_action('wp_ajax_nopriv_get_biteship_cities', array($this, 'ajax_get_biteship_cities'));
        add_action('wp_ajax_calculate_shipping_cost', array($this, 'ajax_calculate_shipping_cost'));
        add_action('wp_ajax_nopriv_calculate_shipping_cost', array($this, 'ajax_calculate_shipping_cost'));
        
        // Test shipping API
        add_action('wp_ajax_test_biteship_api', array($this, 'test_biteship_api_connection'));
    }
    
    /**
     * Get Biteship API key
     *
     * @since    3.1.0
     * @return   string    API key
     */
    public function get_biteship_api_key() {
        $settings = get_option('biteship_settings');
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        
        // Fallback to hardcoded key if not set in database (for backward compatibility)
        if (empty($api_key)) {
            $api_key = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoieWlhcmkiLCJ1c2VySWQiOiI2OGNiNzhmZDI4ZjEzMjAwMTEwM2RjYmEiLCJpYXQiOjE3NTgxNjU1MDJ9.tq3GsQ9haExyGmE_ROiJozU-8nz9UzH8gY0vB5mPeaY';
            error_log('⚠️ Using fallback hardcoded Biteship API key. Please configure API key in admin settings.');
        }
        
        return $api_key;
    }
    
    /**
     * Get areas/cities from Biteship API
     *
     * @since    3.1.0
     * @param    string    $query    Search query
     * @return   array              Cities data
     */
    public function get_biteship_areas($query = '') {
        $api_key = $this->get_biteship_api_key();
        
        if (empty($api_key)) {
            return false;
        }

        $url = "https://api.biteship.com/v1/maps/areas";
        if (!empty($query)) {
            $url .= "?input=" . urlencode($query) . "&countries=ID";
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . $api_key
            ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            error_log('Biteship Areas cURL Error: ' . $err);
            return false;
        }

        $result = json_decode($response, true);
        error_log('Biteship Areas HTTP Code: ' . $http_code);
        error_log('Biteship Areas URL: ' . $url);
        error_log('Biteship Areas Response: ' . $response);

        if ($http_code !== 200) {
            error_log('Biteship Areas API Error - HTTP ' . $http_code . ': ' . $response);
            return false;
        }

        return $result;
    }
    
    /**
     * Get shipping rate using Biteship API
     *
     * @since    3.1.0
     * @param    string    $destination_area_id    Destination area ID
     * @param    int       $weight_grams           Weight in grams
     * @return   array                             Shipping rate data
     */
    public function get_biteship_shipping_rate($destination_area_id, $weight_grams = 1000) {
        $api_key = $this->get_biteship_api_key();
        
        if (empty($api_key)) {
            return false;
        }

        // Get Bogor area ID dynamically from Biteship API
        $origin_area_id = $this->get_bogor_area_id();
        if (!$origin_area_id) {
            error_log('Failed to get Bogor area ID from Biteship API');
            return false;
        }

        $data = array(
            "origin_area_id" => $origin_area_id,
            "destination_area_id" => $destination_area_id,
            "couriers" => "jne",
            "items" => array(
                array(
                    "name" => "Boneka Kukang",
                    "description" => "Boneka untuk adopsi kukang",
                    "weight" => (int)$weight_grams, // weight in grams
                    "length" => 20,
                    "width" => 15,
                    "height" => 10,
                    "value" => 150000
                )
            )
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.biteship.com/v1/rates/couriers",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . $api_key,
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            error_log('Biteship Rate cURL Error: ' . $err);
            return false;
        }

        $result = json_decode($response, true);
        error_log('Biteship Rate HTTP Code: ' . $http_code);
        error_log('Biteship Rate Request: ' . json_encode($data));
        error_log('Biteship Rate Response: ' . $response);

        if ($http_code !== 200) {
            // Check for specific balance error
            if ($result && isset($result['error']) &&
                strpos($result['error'], 'No sufficient balance') !== false) {
                error_log('Biteship Rate API Error - Insufficient Balance: ' . $response);
                return array('error' => 'insufficient_balance', 'message' => $result['error']);
            }

            error_log('Biteship Rate API Error - HTTP ' . $http_code . ': ' . $response);
            return false;
        }

        return $result;
    }
    
    /**
     * Get Bogor area ID from Biteship API
     *
     * @since    3.1.0
     * @return   string    Bogor area ID
     */
    public function get_bogor_area_id() {
        static $bogor_area_id = null;

        // Cache the result to avoid repeated API calls
        if ($bogor_area_id !== null) {
            return $bogor_area_id;
        }

        // Use the correct Tamansari, Bogor area ID with new format (includes postal code)
        $bogor_area_id = 'IDNP9IDNC74IDND6753IDZ16610'; // Tamansari, Bogor
        error_log("Using Tamansari Bogor area ID: " . $bogor_area_id);
        return $bogor_area_id;
    }

    /**
     * Get shipping cost from Biteship API - simplified wrapper for frontend
     *
     * @since    3.1.1
     */
    public function get_biteship_shipping_cost($destination_area_id, $weight_grams = 500) {
        error_log("Biteship shipping cost - destination: $destination_area_id, weight: $weight_grams grams");

        // Call API directly with weight in grams
        $result = $this->get_biteship_shipping_rate($destination_area_id, $weight_grams);

        if ($result && isset($result['pricing']) && is_array($result['pricing'])) {
            // First, try to find any JNE service
            foreach ($result['pricing'] as $pricing) {
                $courier_name = $pricing['courier_name'] ?? $pricing['company'] ?? '';
                $service_name = $pricing['courier_service_name'] ?? $pricing['type'] ?? 'Regular';

                if (strtolower($courier_name) === 'jne' || strpos(strtolower($service_name), 'jne') !== false) {
                    error_log("Biteship success - found JNE service: " . json_encode($pricing));
                    return array(
                        'cost' => intval($pricing['price']),
                        'service' => $service_name,
                        'description' => $pricing['description'] ?? ($courier_name . ' Service'),
                        'estimated_delivery_time' => $pricing['duration'] ?? '2-3 hari'
                    );
                }
            }

            // If no JNE found, use the cheapest option
            $cheapest = null;
            foreach ($result['pricing'] as $pricing) {
                if (!$cheapest || $pricing['price'] < $cheapest['price']) {
                    $cheapest = $pricing;
                }
            }

            if ($cheapest) {
                error_log("Biteship success - using cheapest option: " . json_encode($cheapest));
                $courier_name = $cheapest['courier_name'] ?? $cheapest['company'] ?? 'Regular';
                $service_name = $cheapest['courier_service_name'] ?? $cheapest['type'] ?? 'Regular';

                return array(
                    'cost' => intval($cheapest['price']),
                    'service' => $service_name,
                    'description' => $cheapest['description'] ?? ($courier_name . ' Service'),
                    'estimated_delivery_time' => $cheapest['duration'] ?? '2-3 hari'
                );
            }
        }

        error_log("Biteship failed or no pricing found: " . json_encode($result));
        return false;
    }

    /**
     * AJAX handler to get cities from Biteship API
     *
     * @since    3.1.0
     */
    public function ajax_get_biteship_cities() {
        try {
            $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

            error_log("City search request: '$query'");

            // Only use Biteship API - no hardcoded fallback
            if (empty($query)) {
                wp_send_json_error(array('message' => 'Query parameter is required for city search'));
                return;
            }

            $biteship_result = $this->get_biteship_areas($query);

            if ($biteship_result && isset($biteship_result['success']) && $biteship_result['success'] && isset($biteship_result['areas'])) {
                error_log("Biteship areas success: " . count($biteship_result['areas']) . " areas found");

                // Transform response format to match frontend expectations
                // Group by unique display names to avoid duplicates
                $unique_areas = array();
                $seen_names = array();

                foreach ($biteship_result['areas'] as $area) {
                    $city_name = $area['administrative_division_level_2_name'] ?? $area['name'];
                    $province = $area['administrative_division_level_1_name'];
                    $district = $area['administrative_division_level_3_name'] ?? '';
                    $type = ucfirst($area['administrative_division_level_2_type'] ?? 'kota');

                    // Create unique display name with district if available
                    $display_name = $type . ' ' . $city_name . ', ' . $province;
                    if (!empty($district) && $district !== $city_name) {
                        $display_name = $district . ', ' . $type . ' ' . $city_name . ', ' . $province;
                    }

                    // Extract postal code from name field (format: "Area, City, Province. 12345")
                    $postal_code = '';

                    // Try from dedicated postal_code field first
                    if (isset($area['postal_code']) && !empty($area['postal_code'])) {
                        $postal_code = (string)$area['postal_code'];
                    }
                    // Fallback to extracting from name field
                    elseif (isset($area['name']) && preg_match('/\.\\s*(\\d{5})$/', $area['name'], $matches)) {
                        $postal_code = $matches[1];
                    }

                    error_log("DEBUG BACKEND - Area: {$area['id']}, Name: {$area['name']}, Postal: $postal_code");

                    // Create unique key based on area name (including postal code for uniqueness)
                    $unique_key = $area['name'] ?? $display_name;

                    // Only add if we haven't seen this exact area
                    if (!in_array($unique_key, $seen_names)) {
                        $unique_areas[] = array(
                            'area_id' => $area['id'],
                            'city_name' => $city_name,
                            'province' => $province,
                            'type' => $type,
                            'district' => $district,
                            'full_name' => $area['name'],
                            'display_name' => $area['name'], // Use full name from API as display
                            'postal_code' => $postal_code
                        );
                        $seen_names[] = $unique_key;
                    }
                }

                $transformed_data = array(
                    'data' => $unique_areas
                );

                wp_send_json_success($transformed_data);
            } else {
                error_log("Biteship areas API failed: " . json_encode($biteship_result));
                wp_send_json_error(array(
                    'message' => 'City search service is currently unavailable. Please try again later.',
                    'details' => 'Biteship API error'
                ));
            }

        } catch (Exception $e) {
            error_log('City search error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'City search service error'));
        }
    }
    
    /**
     * AJAX handler to calculate shipping cost
     *
     * @since    3.1.0
     */
    public function ajax_calculate_shipping_cost() {
        try {
            // Use area ID method (preferred for Biteship)
            $destination_area_id = '';
            if (isset($_POST['destination_area_id'])) {
                $destination_area_id = sanitize_text_field($_POST['destination_area_id']);
            } elseif (isset($_POST['destination_city_id'])) {
                $destination_area_id = sanitize_text_field($_POST['destination_city_id']);
            }

            if (empty($destination_area_id) || !isset($_POST['weight'])) {
                error_log('Missing required parameters for shipping calculation');
                wp_send_json_error(array('message' => 'Missing required parameters'));
                return;
            }

            // DEBUG: Log what we received
            error_log("DEBUG - Received destination_area_id: '$destination_area_id'");
            error_log("DEBUG - Length: " . strlen($destination_area_id));
            error_log("DEBUG - All POST data: " . print_r($_POST, true));

            // Validate area ID format (must include postal code IDZ suffix)
            $is_valid_format = preg_match('/^IDNP\d+IDNC\d+IDND\d+IDZ\d+$/', $destination_area_id);

            if (!$is_valid_format) {
                error_log("DEBUG - Invalid area ID format: '$destination_area_id'");
                error_log("DEBUG - Expected format: IDNP[digits]IDNC[digits]IDND[digits]IDZ[digits]");

                // Check if it's old format that we can fix
                if (preg_match('/^IDNP\d+IDNC\d+IDND\d+$/', $destination_area_id)) {
                    error_log("DEBUG - This looks like old format without postal code");
                    wp_send_json_error(array('message' => 'Area ID format lama terdeteksi. Silakan pilih ulang kota dari pencarian.'));
                } else {
                    error_log("DEBUG - Completely invalid format");
                    wp_send_json_error(array('message' => 'Format area ID tidak valid. Silakan pilih kota dari hasil pencarian yang tersedia.'));
                }
                return;
            }

            error_log("DEBUG - Area ID format is valid!");

            $weight = intval($_POST['weight']);
            error_log("Area ID shipping calculation - destination: $destination_area_id, weight: $weight");

            // Minimum weight is 1000 grams (1kg)
            if ($weight < 1000) {
                $weight = 1000;
            }

            // Use simplified shipping cost calculation (matches referensi format)
            $shipping_result = $this->get_biteship_shipping_cost($destination_area_id, $weight);

            if ($shipping_result && isset($shipping_result['cost']) && $shipping_result['cost'] > 0) {
                $shipping_cost = intval($shipping_result['cost']);
                $service = $shipping_result['service'] ?? 'REG';
                $description = $shipping_result['description'] ?? 'Regular Service';

                error_log("✅ Shipping calculation SUCCESS - cost: $shipping_cost");

                wp_send_json(array(
                    'success' => true,
                    'data' => array(
                        'cost' => $shipping_cost,  // Frontend expects 'cost' key directly
                        'service' => $service,
                        'description' => $description,
                        'estimated_delivery_time' => $shipping_result['estimated_delivery_time'] ?? '2-3 hari'
                    )
                ));
            } else {
                error_log("❌ Shipping calculation FAILED - no valid cost returned");
                error_log("Result: " . json_encode($shipping_result));

                wp_send_json_error(array(
                    'message' => 'Shipping cost service is currently unavailable. Please try again later.',
                    'details' => 'No shipping rates available for selected destination'
                ));
            }

        } catch (Exception $e) {
            error_log('Shipping calculation error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Shipping calculation service error'));
        }
    }
    
    /**
     * AJAX handler to test Biteship API connection
     *
     * @since    3.1.0
     */
    public function test_biteship_api_connection() {
        $api_key = $this->get_biteship_api_key();

        $tests = array();

        // Test 1: Areas API
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.biteship.com/v1/maps/areas?input=jakarta&countries=ID",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . $api_key
            ),
        ));
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        $tests['areas'] = array(
            'url' => 'https://api.biteship.com/v1/maps/areas?input=jakarta&countries=ID',
            'http_code' => $http_code,
            'error' => $err,
            'response' => $response
        );

        // Test 2: Rates API
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.biteship.com/v1/rates/couriers",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array(
                "origin_area_id" => "ID152",
                "destination_area_id" => "ID154",
                "couriers" => "jne",
                "items" => array(array(
                    "name" => "Test Item",
                    "weight" => 1000,
                    "length" => 20,
                    "width" => 15,
                    "height" => 10,
                    "value" => 150000
                ))
            )),
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . $api_key,
                "Content-Type: application/json"
            ),
        ));
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        $tests['rates'] = array(
            'url' => 'https://api.biteship.com/v1/rates/couriers',
            'http_code' => $http_code,
            'error' => $err,
            'response' => $response
        );

        wp_send_json($tests);
    }
}
?>