<?php

/**
 * Payment Manager for YIARI Donasi Kukang Plugin
 * 
 * Handles Midtrans payment gateway integration
 */
class YIARI_Payment_Manager {
    
    /**
     * Initialize payment manager
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Load Midtrans library
        $this->load_midtrans_library();
        
        // AJAX handlers disabled to prevent conflicts - handled by YIARI_Public_Module instead
        // add_action('wp_ajax_process_donation', array($this, 'ajax_process_donation'));
        // add_action('wp_ajax_nopriv_process_donation', array($this, 'ajax_process_donation'));
        // add_action('wp_ajax_process_donation_en', array($this, 'ajax_process_donation_en'));
        // add_action('wp_ajax_nopriv_process_donation_en', array($this, 'ajax_process_donation_en'));
        
        // Midtrans notification handlers
        add_action('wp_ajax_midtrans_notification', array($this, 'handle_midtrans_notification'));
        add_action('wp_ajax_nopriv_midtrans_notification', array($this, 'handle_midtrans_notification'));
        
        // Test payment gateway
        add_action('wp_ajax_test_midtrans_gateway', array($this, 'test_midtrans_gateway_connection'));
    }
    
    /**
     * Load Midtrans library
     *
     * @since    3.1.0
     */
    public function load_midtrans_library() {
        error_log("=== LOADING MIDTRANS LIBRARY ===");

        // Try multiple possible paths for Midtrans library
        $possible_paths = array(
            YIARI_DONASI_KUKANG_PATH . 'midtrans-php-master/Midtrans.php',
            YIARI_DONASI_KUKANG_PATH . 'vendor/midtrans/midtrans-php/Midtrans.php',
            YIARI_DONASI_KUKANG_PATH . 'includes/midtrans/Midtrans.php'
        );

        foreach ($possible_paths as $index => $path) {
            $exists = file_exists($path) ? "✅ EXISTS" : "❌ NOT FOUND";
            error_log("Path $index: $path - $exists");
        }

        $midtrans_loaded = false;
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                error_log("Attempting to load: $path");
                try {
                    require_once $path;
                    $midtrans_loaded = true;
                    error_log("✅ Successfully loaded: $path");
                    break;
                } catch (Exception $e) {
                    error_log("❌ Error loading $path: " . $e->getMessage());
                } catch (Error $e) {
                    error_log("❌ Fatal error loading $path: " . $e->getMessage());
                }
            }
        }

        if (!$midtrans_loaded) {
            // Check if classes are already loaded via autoloader
            if (!class_exists('Midtrans\Config') || !class_exists('Midtrans\Snap')) {
                error_log('Midtrans library not found in any expected location');
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning"><p>⚠️ Midtrans PHP library not found. Payment features may not work properly. Please install the Midtrans PHP library.</p></div>';
                });
                return false;
            }
        }

        return true;
    }
    
    /**
     * Get Midtrans settings
     *
     * @since    3.1.0
     * @return   array    Midtrans settings
     */
    public function get_midtrans_settings() {
        return get_option('midtrans_settings', array(
            'environment' => 'sandbox',
            'sandbox_server_key' => '',
            'sandbox_client_key' => '',
            'production_server_key' => '',
            'production_client_key' => ''
        ));
    }
    
    /**
     * Configure Midtrans environment
     *
     * @since    3.1.0
     * @param    string    $environment    Environment (sandbox/production)
     */
    public function configure_midtrans_environment($environment = 'sandbox') {
        error_log("=== CONFIGURING MIDTRANS ENVIRONMENT: $environment ===");

        // Ensure Midtrans library is loaded
        error_log("Loading Midtrans library...");
        if (!$this->load_midtrans_library()) {
            error_log("❌ Midtrans library loading failed");
            throw new Exception('Midtrans library not available');
        }
        error_log("✅ Midtrans library loaded successfully");

        // Check if Midtrans classes exist
        error_log("Checking for Midtrans classes...");
        if (!class_exists('Midtrans\Config')) {
            error_log("❌ Midtrans\Config class not found");
            throw new Exception('Midtrans Config class not found');
        }
        error_log("✅ Midtrans\Config class found");

        $settings = $this->get_midtrans_settings();

        // Set server key based on environment
        if ($environment === 'production') {
            \Midtrans\Config::$serverKey = $settings['production_server_key'];
            \Midtrans\Config::$isProduction = true;
        } else {
            \Midtrans\Config::$serverKey = $settings['sandbox_server_key'];
            \Midtrans\Config::$isProduction = false;
        }

        // Set common configuration
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }
    
    /**
     * Process donation payment
     *
     * @since    3.1.0
     * @param    array    $donation_data    Donation data
     * @return   array                     Payment response
     */
    public function process_donation_payment($donation_data) {
        try {
            error_log("=== PAYMENT MANAGER: process_donation_payment START ===");
            error_log("Input data: " . json_encode($donation_data));

            // Configure Midtrans environment
            $environment = isset($donation_data['environment']) ? $donation_data['environment'] : 'sandbox';

            // If environment not in data, get from settings
            if (!isset($donation_data['environment'])) {
                $settings = $this->get_midtrans_settings();
                $environment = $settings['environment'] ?? 'sandbox';
                error_log("Environment not in donation data, using from settings: " . $environment);
            }
            error_log("Configuring Midtrans environment: " . $environment);
            $this->configure_midtrans_environment($environment);
            error_log("Midtrans environment configured successfully");
        
        // Prepare transaction details
        $transaction_details = array(
            'order_id' => $donation_data['order_id'],
            'gross_amount' => intval($donation_data['gross_amount'])
        );
        
        // Prepare customer details
        $customer_details = array(
            'first_name' => $donation_data['customer_name'],
            'email' => $donation_data['email'],
            'phone' => $donation_data['phone'],
            'billing_address' => array(
                'first_name' => $donation_data['customer_name'],
                'email' => $donation_data['email'],
                'phone' => $donation_data['phone'],
                'address' => $donation_data['address'],
                'city' => $donation_data['city_name'],
                'postal_code' => $donation_data['postal_code'],
                'country_code' => 'IDN'
            ),
            'shipping_address' => array(
                'first_name' => $donation_data['customer_name'],
                'email' => $donation_data['email'],
                'phone' => $donation_data['phone'],
                'address' => $donation_data['address'],
                'city' => $donation_data['city_name'],
                'postal_code' => $donation_data['postal_code'],
                'country_code' => 'IDN'
            )
        );
        
        // Prepare item details from the normalized-table-first catalog.
        $item_details = array();
        $product_repository = new YIARI_Product_Repository();
        $dolls = $product_repository->get_active_products();
        $first_selected_item = null;

        foreach ($dolls as $doll) {
            $doll_name = strtolower($doll->name);
            $qty = isset($donation_data[$doll_name . '_qty']) ? intval($donation_data[$doll_name . '_qty']) : 0;

            if ($qty > 0) {
                $price = (isset($donation_data['currency']) && $donation_data['currency'] === 'USD')
                    ? floatval($doll->price_usd ?? 0)
                    : floatval($doll->price_idr ?? 0);
                $price = intval($price);
                // For USD transactions, convert to IDR for Midtrans
                if (isset($donation_data['currency']) && $donation_data['currency'] === 'USD' && isset($donation_data['exchange_rate'])) {
                    $price = intval($price / $donation_data['exchange_rate']);
                }

                if (null === $first_selected_item) {
                    $first_selected_item = array(
                        'id' => $doll_name . '_donation',
                        'price' => $price,
                        'name' => $doll->name . ' Donation Copy',
                    );
                }

                $item_details[] = array(
                    'id' => $doll_name,
                    'price' => $price,
                    'quantity' => $qty,
                    'name' => $doll->name
                );
            }
        }

        $donation_count = intval($donation_data['donation_item_count'] ?? ($donation_data['donation_book_count'] ?? 0));
        if ($donation_count > 0 && null !== $first_selected_item) {
            $item_details[] = array(
                'id' => $first_selected_item['id'],
                'price' => $first_selected_item['price'],
                'quantity' => $donation_count,
                'name' => $first_selected_item['name']
            );
        }
        
        // Add shipping cost
        if (intval($donation_data['shipping_cost']) > 0) {
            $item_details[] = array(
                'id' => 'shipping',
                'price' => intval($donation_data['shipping_cost']),
                'quantity' => 1,
                'name' => 'Shipping Cost (JNE REG)'
            );
        }
        
        // Prepare transaction parameters
        $transaction_params = array(
            'transaction_details' => $transaction_details,
            'customer_details' => $customer_details,
            'item_details' => $item_details
        );
        
        // Add payment method restrictions for USD transactions
        if (isset($donation_data['currency']) && $donation_data['currency'] === 'USD') {
            $transaction_params['enabled_payments'] = array('credit_card');
            $transaction_params['credit_card'] = array(
                'secure' => true,
                'channel' => 'migs',
                'bank' => 'bca',
                'installment' => array(
                    'required' => false
                )
            );
        }
        
        try {
            // Get Snap token
            $snap_token = \Midtrans\Snap::getSnapToken($transaction_params);
            
            return array(
                'success' => true,
                'snap_token' => $snap_token,
                'order_id' => $donation_data['order_id']
            );
            
        } catch (Exception $e) {
            error_log('Midtrans Snap token generation failed: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }

        } catch (Exception $e) {
            error_log('=== PAYMENT MANAGER: EXCEPTION ===');
            error_log('Error message: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return array(
                'success' => false,
                'error' => 'Payment processing error: ' . $e->getMessage()
            );
        } catch (Error $e) {
            error_log('=== PAYMENT MANAGER: FATAL ERROR ===');
            error_log('Error message: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return array(
                'success' => false,
                'error' => 'Fatal error in payment processing: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * AJAX handler to process donation
     *
     * @since    3.1.0
     */
    public function ajax_process_donation() {
        // This will be implemented in the form manager
        // For now, we'll keep the existing implementation
        $this->handle_indonesian_donation();
    }
    
    /**
     * AJAX handler to process English donation
     *
     * @since    3.1.0
     */
    public function ajax_process_donation_en() {
        // This will be implemented in the form manager
        // For now, we'll keep the existing implementation
        $this->handle_english_donation();
    }
    
    /**
     * Handle Indonesian donation (placeholder)
     *
     * @since    3.1.0
     */
    private function handle_indonesian_donation() {
        // Implementation will be moved from the main file
        // This is a placeholder to maintain backward compatibility
    }
    
    /**
     * Handle English donation (placeholder)
     *
     * @since    3.1.0
     */
    private function handle_english_donation() {
        // Implementation will be moved from the main file
        // This is a placeholder to maintain backward compatibility
    }
    
    /**
     * Handle Midtrans notification
     *
     * @since    3.1.0
     */
    public function handle_midtrans_notification() {
        error_log("=== MIDTRANS WEBHOOK NOTIFICATION RECEIVED ===");

        try {
            // Load Midtrans library and configure environment
            $this->load_midtrans_library();

            // Get settings to determine environment
            $settings = $this->get_midtrans_settings();
            $environment = $settings['environment'] ?? 'sandbox';

            error_log("Configuring Midtrans webhook for environment: $environment");
            $this->configure_midtrans_environment($environment);

            // Create notification object
            $notif = new \Midtrans\Notification();

            $transaction_status = $notif->transaction_status;
            $order_id = $notif->order_id;
            $fraud_status = $notif->fraud_status ?? '';
            $gross_amount = $notif->gross_amount;
            $transaction_id = $notif->transaction_id;

            error_log("Webhook notification details:");
            error_log("- Order ID: $order_id");
            error_log("- Transaction ID: $transaction_id");
            error_log("- Status: $transaction_status");
            error_log("- Fraud Status: $fraud_status");
            error_log("- Amount: $gross_amount");

            global $wpdb;
            $table_name = $wpdb->prefix . 'kukang_transactions_new';

            // Verify transaction exists in our database
            $existing_transaction = $wpdb->get_row($wpdb->prepare(
                "SELECT id, transaction_status, order_status FROM $table_name WHERE order_id = %s",
                $order_id
            ));

            if (!$existing_transaction) {
                error_log("❌ Webhook: Transaction not found in database for order: $order_id");
                http_response_code(404);
                echo "TRANSACTION_NOT_FOUND";
                wp_die();
            }

            // Prepare update data
            $update_data = array(
                'transaction_id' => $transaction_id,
                'transaction_status' => $transaction_status,
                'fraud_status' => $fraud_status,
                'payment_type' => isset($notif->payment_type) ? $notif->payment_type : '',
                'updated_at' => current_time('mysql')
            );

            // Add settlement time if transaction is settled
            if ($transaction_status == 'settlement' || ($transaction_status == 'capture' && $fraud_status == 'accept')) {
                $update_data['settlement_time'] = current_time('mysql');

                // Automatically update order status from pending to processing when payment is settled
                if (in_array(strtolower($existing_transaction->order_status ?: 'pending'), ['pending', ''])) {
                    $update_data['order_status'] = 'processing';
                    error_log("✅ Order status automatically updated to processing (payment settled)");
                }

                error_log("✅ Transaction settled, adding settlement time");
            }

            // Add bank info if available
            if (isset($notif->bank)) {
                $update_data['bank'] = $notif->bank;
                error_log("Bank info added: " . $notif->bank);
            }

            // Add VA number if available
            if (isset($notif->va_numbers) && is_array($notif->va_numbers) && count($notif->va_numbers) > 0) {
                $update_data['va_number'] = $notif->va_numbers[0]->va_number;
                error_log("VA number added: " . $notif->va_numbers[0]->va_number);
            }

            // Add additional payment details if available
            if (isset($notif->biller_code)) {
                $update_data['notes'] = "Biller Code: " . $notif->biller_code;
            }

            if (isset($notif->bill_key)) {
                $current_notes = $update_data['notes'] ?? '';
                $update_data['notes'] = $current_notes . (!empty($current_notes) ? '; ' : '') . "Bill Key: " . $notif->bill_key;
            }

            // Log the update data
            error_log("Updating database with data: " . json_encode($update_data));

            // Update database
            $updated = $wpdb->update(
                $table_name,
                $update_data,
                array('order_id' => $order_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'), // Format specifiers
                array('%s') // Where format
            );

            if ($updated !== false) {
                error_log("✅ Webhook: Transaction status updated successfully");
                error_log("- Order ID: $order_id");
                error_log("- Old Status: " . $existing_transaction->transaction_status);
                error_log("- New Status: $transaction_status");
                error_log("- Rows affected: $updated");

                $order_service = new YIARI_Order_Service();
                $sync_result = $order_service->sync_midtrans_callback($order_id, array(
                    'transaction_id' => $transaction_id,
                    'transaction_status' => $transaction_status,
                    'fraud_status' => $fraud_status,
                    'payment_type' => isset($notif->payment_type) ? $notif->payment_type : '',
                ));
                error_log("Normalized order sync result: " . wp_json_encode($sync_result));

                http_response_code(200);
                echo "OK";
            } else {
                error_log("❌ Webhook: Failed to update transaction status for order: $order_id");
                error_log("Database error: " . $wpdb->last_error);
                http_response_code(500);
                echo "DATABASE_UPDATE_FAILED";
            }

        } catch (Exception $e) {
            error_log("❌ Webhook exception: " . $e->getMessage());
            error_log("Exception trace: " . $e->getTraceAsString());
            http_response_code(400);
            echo "ERROR: " . $e->getMessage();
        } catch (Error $e) {
            error_log("❌ Webhook fatal error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo "FATAL_ERROR: " . $e->getMessage();
        }

        wp_die(); // Important for AJAX requests
    }
    
    /**
     * Test Midtrans gateway connection
     *
     * @since    3.1.0
     */
    public function test_midtrans_gateway_connection() {
        $settings = $this->get_midtrans_settings();
        $environment = $settings['environment'] ?? 'sandbox';
        
        $server_key = ($environment === 'production') ? $settings['production_server_key'] : $settings['sandbox_server_key'];
        $client_key = ($environment === 'production') ? $settings['production_client_key'] : $settings['sandbox_client_key'];
        
        if (empty($server_key)) {
            wp_send_json_error(array('message' => 'Server key not configured'));
            return;
        }
        
        // Test server key validity
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => ($environment === 'production') ? "https://api.midtrans.com/v2/charge" : "https://api.sandbox.midtrans.com/v2/charge",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . base64_encode($server_key . ":"),
                "Content-Type: application/json",
                "Accept: application/json"
            ),
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);
        
        $valid = ($http_code === 400); // 400 means authentication succeeded but missing parameters
        
        wp_send_json_success(array(
            'environment' => $environment,
            'server_key_valid' => $valid,
            'client_key_present' => !empty($client_key),
            'http_code' => $http_code,
            'response' => $valid ? 'Authentication successful' : ($err ?: 'Authentication failed')
        ));
    }
}
?>
