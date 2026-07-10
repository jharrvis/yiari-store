<?php

/**
 * Database Manager for YIARI Donasi Kukang Plugin
 * 
 * Handles all database operations and table creation
 */
class YIARI_Database_Manager {
    
    /**
     * Initialize database manager
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Register activation hook
        register_activation_hook(YIARI_DONASI_KUKANG_PATH . 'yiari-donasi-kukang.php', array($this, 'create_tables'));
        
        // Create tables on init
        add_action('init', array($this, 'create_tables'));
    }
    
    /**
     * Create required database tables
     *
     * @since    3.1.0
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create NEW transactions table (v3.1.1)
        $transactions_table = $wpdb->prefix . 'kukang_transactions_new';
        $sql_transactions = "CREATE TABLE IF NOT EXISTS $transactions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL,
            order_id varchar(100) NOT NULL UNIQUE,
            snap_token varchar(255) NOT NULL,
            gross_amount decimal(12,2) NOT NULL,
            subtotal decimal(12,2) NOT NULL,
            shipping_cost decimal(12,2) NOT NULL DEFAULT 0,
            total_weight int(11) NOT NULL DEFAULT 0,
            
            -- Customer Information
            customer_name varchar(150) NOT NULL,
            email varchar(150) NOT NULL,
            phone varchar(25) DEFAULT NULL,
            
            -- Shipping Address
            address text DEFAULT NULL,
            province varchar(100) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            postal_code varchar(15) DEFAULT NULL,
            
            -- Product Details (Kukang Dolls)
            regina_qty int(11) DEFAULT 0,
            jagger_qty int(11) DEFAULT 0,
            butros_qty int(11) DEFAULT 0,
            eid_qty int(11) DEFAULT 0,
            anoda_qty int(11) DEFAULT 0,
            total_items int(11) DEFAULT 0,
            
            -- Shipping Details
            courier varchar(50) DEFAULT NULL,
            service varchar(100) DEFAULT NULL,
            
            -- Payment Status
            payment_type varchar(50) DEFAULT NULL,
            transaction_status varchar(50) DEFAULT 'pending',
            fraud_status varchar(50) DEFAULT NULL,
            
            -- Timestamps
            transaction_time datetime DEFAULT NULL,
            settlement_time datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Additional Info
            bank varchar(50) DEFAULT NULL,
            va_number varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            
            -- Order Management (Added for version 3.0)
            order_status varchar(50) DEFAULT 'processing',
            tracking_number varchar(100) DEFAULT NULL,
            
            -- Multi-language and Currency Support (Added for version 3.1)
            language varchar(10) DEFAULT 'id',
            currency varchar(10) DEFAULT 'IDR',
            usd_amount decimal(12,2) DEFAULT NULL,
            exchange_rate decimal(10,4) DEFAULT NULL,
            
            PRIMARY KEY (id),
            UNIQUE KEY unique_order_id (order_id),
            INDEX idx_transaction_status (transaction_status),
            INDEX idx_customer_email (email),
            INDEX idx_created_at (created_at),
            INDEX idx_order_status (order_status),
            INDEX idx_language (language),
            INDEX idx_currency (currency)
        ) $charset_collate;";
        
        // Create NEW dolls table (v3.1.1)
        $dolls_table = $wpdb->prefix . 'kukang_dolls_new';
        $sql_dolls = "CREATE TABLE IF NOT EXISTS $dolls_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            price_idr int(11) NOT NULL DEFAULT 150000,
            price_usd decimal(10,2) NOT NULL DEFAULT 10.00,
            weight_grams int(11) NOT NULL DEFAULT 200,
            length_cm int(11) NOT NULL DEFAULT 20,
            width_cm int(11) NOT NULL DEFAULT 15,
            height_cm int(11) NOT NULL DEFAULT 10,
            description text DEFAULT NULL,
            image_url varchar(255) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_active (is_active),
            KEY idx_name (name)
        ) $charset_collate;";
        
        // Create NEW currency settings table (v3.1.1)
        $currency_table = $wpdb->prefix . 'kukang_currency_new';
        $sql_currency = "CREATE TABLE IF NOT EXISTS $currency_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            currency_code varchar(3) NOT NULL DEFAULT 'USD',
            usd_rate decimal(15,8) NOT NULL DEFAULT 0.000067,
            api_rate decimal(15,8) DEFAULT NULL,
            manual_rate decimal(15,8) DEFAULT NULL,
            auto_update tinyint(1) DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            last_api_update datetime DEFAULT NULL,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            UNIQUE KEY unique_currency_code (currency_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_transactions);
        dbDelta($sql_dolls);
        dbDelta($sql_currency);
        
        // Insert default dolls if table is empty
        $this->insert_default_dolls();
        
        // Insert default currency settings if table is empty
        $this->insert_default_currency_settings();
    }
    
    /**
     * Insert default dolls if table is empty
     *
     * @since    3.1.0
     */
    private function insert_default_dolls() {
        global $wpdb;

        $dolls_table = $wpdb->prefix . 'kukang_dolls_new';

        // Check if table exists and has data
        $dolls_count = $wpdb->get_var("SELECT COUNT(*) FROM $dolls_table");

        if ($dolls_count == 0) {
            $default_dolls = array(
                array(
                    'name' => 'Regina',
                    'price_idr' => 150000,
                    'price_usd' => 10.00,
                    'weight_grams' => 200,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 10,
                    'description' => 'Boneka kukang Regina yang lucu dan menggemaskan'
                ),
                array(
                    'name' => 'Jagger',
                    'price_idr' => 150000,
                    'price_usd' => 10.00,
                    'weight_grams' => 200,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 10,
                    'description' => 'Boneka kukang Jagger yang menggemaskan'
                ),
                array(
                    'name' => 'Butros',
                    'price_idr' => 150000,
                    'price_usd' => 10.00,
                    'weight_grams' => 200,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 10,
                    'description' => 'Boneka kukang Butros yang imut'
                ),
                array(
                    'name' => 'Eid',
                    'price_idr' => 150000,
                    'price_usd' => 10.00,
                    'weight_grams' => 200,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 10,
                    'description' => 'Boneka kukang Eid yang cantik'
                ),
                array(
                    'name' => 'Anoda',
                    'price_idr' => 150000,
                    'price_usd' => 10.00,
                    'weight_grams' => 200,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 10,
                    'description' => 'Boneka kukang Anoda yang adorable'
                )
            );

            foreach ($default_dolls as $doll) {
                $wpdb->insert($dolls_table, $doll);
            }
        }
    }
    
    /**
     * Insert default currency settings if table is empty
     *
     * @since    3.1.0
     */
    private function insert_default_currency_settings() {
        global $wpdb;

        $currency_table = $wpdb->prefix . 'kukang_currency_new';

        // Check if table exists and has data
        $currency_count = $wpdb->get_var("SELECT COUNT(*) FROM $currency_table WHERE currency_code = 'USD'");

        if ($currency_count == 0) {
            $wpdb->insert($currency_table, [
                'currency_code' => 'USD',
                'usd_rate' => 0.000067, // Default IDR to USD rate
                'api_rate' => null, // Will be fetched dynamically from API
                'manual_rate' => null,
                'auto_update' => 1,
                'is_active' => 1,
                'last_api_update' => null
            ]);
        }
    }
}
?>