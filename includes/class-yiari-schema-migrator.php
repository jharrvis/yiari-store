<?php

/**
 * Schema migrator for the next-generation YIARI donation store data model.
 */
class YIARI_Schema_Migrator {

    /**
     * Version marker for schema migrations managed by this class.
     */
    const SCHEMA_VERSION = '2026-07-13-02';

    /**
     * Option key used to persist the installed schema version.
     */
    const OPTION_KEY = 'yiari_schema_version';

    /**
     * Run schema creation only when the stored version is outdated.
     */
    public function maybe_migrate() {
        $installed_version = get_option(self::OPTION_KEY, '');

        if ($installed_version === self::SCHEMA_VERSION) {
            return;
        }

        $this->create_tables();
        update_option(self::OPTION_KEY, self::SCHEMA_VERSION, false);
    }

    /**
     * Create the next-generation normalized tables.
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $products_table = $wpdb->prefix . 'yiari_products';
        $orders_table = $wpdb->prefix . 'yiari_orders';
        $order_items_table = $wpdb->prefix . 'yiari_order_items';
        $shipments_table = $wpdb->prefix . 'yiari_shipments';
        $status_logs_table = $wpdb->prefix . 'yiari_order_status_logs';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_products = "CREATE TABLE {$products_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            legacy_product_id BIGINT UNSIGNED NULL,
            sku VARCHAR(64) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            name VARCHAR(190) NOT NULL,
            product_type VARCHAR(32) NOT NULL DEFAULT 'physical',
            description LONGTEXT NULL,
            price_idr DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            price_usd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            weight_grams INT UNSIGNED NOT NULL DEFAULT 0,
            length_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            width_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            height_cm DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            is_shippable TINYINT(1) NOT NULL DEFAULT 1,
            stock_quantity INT NOT NULL DEFAULT 0,
            manage_stock TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(32) NOT NULL DEFAULT 'active',
            image_url VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY legacy_product_id (legacy_product_id),
            UNIQUE KEY sku (sku),
            UNIQUE KEY slug (slug),
            KEY product_type (product_type),
            KEY status (status),
            KEY sort_order (sort_order)
        ) {$charset_collate};";

        $sql_orders = "CREATE TABLE {$orders_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number VARCHAR(100) NOT NULL,
            legacy_order_id VARCHAR(100) NULL,
            donor_name VARCHAR(190) NOT NULL,
            donor_email VARCHAR(190) NOT NULL,
            donor_phone VARCHAR(50) NULL,
            address LONGTEXT NULL,
            province VARCHAR(100) NULL,
            city VARCHAR(100) NULL,
            postal_code VARCHAR(20) NULL,
            language VARCHAR(10) NOT NULL DEFAULT 'id',
            currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
            subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            shipping_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_weight_grams INT UNSIGNED NOT NULL DEFAULT 0,
            self_book_count INT UNSIGNED NOT NULL DEFAULT 0,
            donation_book_count INT UNSIGNED NOT NULL DEFAULT 0,
            contains_donation_items TINYINT(1) NOT NULL DEFAULT 0,
            donation_motivation_code VARCHAR(64) NULL,
            donation_motivation_other TEXT NULL,
            payment_gateway VARCHAR(32) NOT NULL DEFAULT 'midtrans',
            payment_reference VARCHAR(255) NULL,
            payment_type VARCHAR(50) NULL,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending_payment',
            fraud_status VARCHAR(50) NULL,
            fulfillment_status VARCHAR(50) NOT NULL DEFAULT 'draft',
            notes LONGTEXT NULL,
            transaction_time DATETIME NULL,
            settlement_time DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY donor_email (donor_email),
            KEY payment_status (payment_status),
            KEY fulfillment_status (fulfillment_status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_order_items = "CREATE TABLE {$order_items_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            sku_snapshot VARCHAR(64) NOT NULL,
            product_name_snapshot VARCHAR(190) NOT NULL,
            qty INT UNSIGNED NOT NULL DEFAULT 1,
            currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
            unit_price_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            line_total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            weight_grams_snapshot INT UNSIGNED NOT NULL DEFAULT 0,
            fulfillment_type VARCHAR(32) NOT NULL DEFAULT 'self_purchase',
            requires_shipping TINYINT(1) NOT NULL DEFAULT 1,
            donation_recipient_type VARCHAR(32) NULL DEFAULT NULL,
            metadata LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY fulfillment_type (fulfillment_type),
            KEY requires_shipping (requires_shipping)
        ) {$charset_collate};";

        $sql_shipments = "CREATE TABLE {$shipments_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(32) NOT NULL DEFAULT 'kiriminaja',
            courier_code VARCHAR(50) NULL,
            service_type VARCHAR(100) NULL,
            shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            total_weight_grams INT UNSIGNED NOT NULL DEFAULT 0,
            tracking_number VARCHAR(100) NULL,
            tracking_url VARCHAR(255) NULL,
            external_shipment_id VARCHAR(255) NULL,
            shipment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            request_payload LONGTEXT NULL,
            response_payload LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY provider (provider),
            KEY shipment_status (shipment_status),
            KEY tracking_number (tracking_number)
        ) {$charset_collate};";

        $sql_status_logs = "CREATE TABLE {$status_logs_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            source VARCHAR(50) NOT NULL DEFAULT 'system',
            status_type VARCHAR(32) NOT NULL,
            previous_status VARCHAR(50) NULL,
            new_status VARCHAR(50) NOT NULL,
            message TEXT NULL,
            context_payload LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY status_type (status_type),
            KEY new_status (new_status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql_products);
        dbDelta($sql_orders);
        dbDelta($sql_order_items);
        dbDelta($sql_shipments);
        dbDelta($sql_status_logs);
    }
}
?>
