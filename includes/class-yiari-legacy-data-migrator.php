<?php

/**
 * Migrates legacy kukang tables into the normalized YIARI schema.
 */
class YIARI_Legacy_Data_Migrator {

    /**
     * Version marker for legacy data migration.
     */
    const DATA_MIGRATION_VERSION = '2026-07-13-01';

    /**
     * Option key used to persist the legacy migration version.
     */
    const OPTION_KEY = 'yiari_legacy_data_migration_version';

    /**
     * Run legacy data migration only once per migration version.
     */
    public function maybe_migrate() {
        $installed_version = get_option(self::OPTION_KEY, '');

        if ($installed_version === self::DATA_MIGRATION_VERSION) {
            return;
        }

        $this->migrate_products();
        $this->migrate_orders();

        update_option(self::OPTION_KEY, self::DATA_MIGRATION_VERSION, false);
    }

    /**
     * Copy legacy product data into the normalized product catalog.
     */
    private function migrate_products() {
        global $wpdb;

        $legacy_products_table = $wpdb->prefix . 'kukang_dolls_new';
        $products_table = $wpdb->prefix . 'yiari_products';
        $legacy_products = $wpdb->get_results("SELECT * FROM {$legacy_products_table} ORDER BY id ASC");

        foreach ($legacy_products as $legacy_product) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$products_table} WHERE legacy_product_id = %d LIMIT 1",
                    $legacy_product->id
                )
            );

            if ($existing_id) {
                continue;
            }

            $product_slug = sanitize_title($legacy_product->name);
            $product_sku = 'LEGACY-' . strtoupper($product_slug);

            $wpdb->insert(
                $products_table,
                array(
                    'legacy_product_id' => (int) $legacy_product->id,
                    'sku' => $product_sku,
                    'slug' => $product_slug,
                    'name' => $legacy_product->name,
                    'product_type' => 'physical',
                    'description' => $legacy_product->description,
                    'price_idr' => $legacy_product->price_idr,
                    'price_usd' => $legacy_product->price_usd,
                    'weight_grams' => $legacy_product->weight_grams,
                    'length_cm' => $legacy_product->length_cm,
                    'width_cm' => $legacy_product->width_cm,
                    'height_cm' => $legacy_product->height_cm,
                    'is_shippable' => 1,
                    'stock_quantity' => 0,
                    'manage_stock' => 0,
                    'status' => ((int) $legacy_product->is_active === 1) ? 'active' : 'inactive',
                    'image_url' => $legacy_product->image_url,
                    'sort_order' => (int) $legacy_product->id,
                    'created_at' => $legacy_product->created_at ?: current_time('mysql'),
                    'updated_at' => $legacy_product->updated_at ?: current_time('mysql'),
                ),
                array(
                    '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%f', '%f', '%f', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s'
                )
            );
        }
    }

    /**
     * Copy legacy orders, order items, shipment rows, and status logs.
     */
    private function migrate_orders() {
        global $wpdb;

        $legacy_orders_table = $wpdb->prefix . 'kukang_transactions_new';
        $orders_table = $wpdb->prefix . 'yiari_orders';
        $legacy_orders = $wpdb->get_results("SELECT * FROM {$legacy_orders_table} ORDER BY id ASC");

        foreach ($legacy_orders as $legacy_order) {
            $existing_order_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$orders_table} WHERE order_number = %s LIMIT 1",
                    $legacy_order->order_id
                )
            );

            if ($existing_order_id) {
                continue;
            }

            $self_book_count = $this->get_legacy_self_item_count($legacy_order);
            $payment_status = $this->map_legacy_payment_status($legacy_order->transaction_status);
            $fulfillment_status = $this->map_legacy_fulfillment_status($legacy_order->order_status, $legacy_order->tracking_number);

            $wpdb->insert(
                $orders_table,
                array(
                    'order_number' => $legacy_order->order_id,
                    'legacy_order_id' => $legacy_order->order_id,
                    'donor_name' => $legacy_order->customer_name,
                    'donor_email' => $legacy_order->email,
                    'donor_phone' => $legacy_order->phone,
                    'address' => $legacy_order->address,
                    'province' => $legacy_order->province,
                    'city' => $legacy_order->city,
                    'postal_code' => $legacy_order->postal_code,
                    'language' => $legacy_order->language ?: 'id',
                    'currency' => $legacy_order->currency ?: 'IDR',
                    'subtotal_amount' => $legacy_order->subtotal,
                    'shipping_amount' => $legacy_order->shipping_cost,
                    'total_amount' => $legacy_order->gross_amount,
                    'total_weight_grams' => (int) $legacy_order->total_weight,
                    'self_book_count' => $self_book_count,
                    'donation_book_count' => 0,
                    'contains_donation_items' => 0,
                    'payment_gateway' => 'midtrans',
                    'payment_reference' => $legacy_order->transaction_id,
                    'payment_type' => $legacy_order->payment_type,
                    'payment_status' => $payment_status,
                    'fraud_status' => $legacy_order->fraud_status,
                    'fulfillment_status' => $fulfillment_status,
                    'notes' => $legacy_order->notes,
                    'transaction_time' => $legacy_order->transaction_time,
                    'settlement_time' => $legacy_order->settlement_time,
                    'created_at' => $legacy_order->created_at ?: current_time('mysql'),
                    'updated_at' => $legacy_order->updated_at ?: current_time('mysql'),
                ),
                array(
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
                )
            );

            $new_order_id = (int) $wpdb->insert_id;

            if (!$new_order_id) {
                continue;
            }

            $this->migrate_order_items($new_order_id, $legacy_order);
            $this->migrate_shipment($new_order_id, $legacy_order);
            $this->insert_status_logs($new_order_id, $legacy_order, $payment_status, $fulfillment_status);
        }
    }

    /**
     * Convert fixed legacy qty columns into normalized order items.
     */
    private function migrate_order_items($order_id, $legacy_order) {
        global $wpdb;

        $order_items_table = $wpdb->prefix . 'yiari_order_items';
        $products_table = $wpdb->prefix . 'yiari_products';
        $legacy_item_map = array(
            'regina_qty' => 'Regina',
            'jagger_qty' => 'Jagger',
            'butros_qty' => 'Butros',
            'eid_qty' => 'Eid',
            'anoda_qty' => 'Anoda',
        );

        foreach ($legacy_item_map as $qty_field => $product_name) {
            $qty = (int) $legacy_order->{$qty_field};

            if ($qty <= 0) {
                continue;
            }

            $product = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$products_table} WHERE name = %s LIMIT 1",
                    $product_name
                )
            );

            if (!$product) {
                continue;
            }

            $unit_price = ($legacy_order->currency === 'USD')
                ? (float) $product->price_usd
                : (float) $product->price_idr;

            $wpdb->insert(
                $order_items_table,
                array(
                    'order_id' => $order_id,
                    'product_id' => (int) $product->id,
                    'sku_snapshot' => $product->sku,
                    'product_name_snapshot' => $product->name,
                    'qty' => $qty,
                    'currency' => $legacy_order->currency ?: 'IDR',
                    'unit_price_amount' => $unit_price,
                    'line_total_amount' => $unit_price * $qty,
                    'weight_grams_snapshot' => (int) $product->weight_grams,
                    'fulfillment_type' => 'self_purchase',
                    'requires_shipping' => 1,
                    'donation_recipient_type' => null,
                    'metadata' => wp_json_encode(array('legacy_qty_field' => $qty_field)),
                    'created_at' => $legacy_order->created_at ?: current_time('mysql'),
                    'updated_at' => $legacy_order->updated_at ?: current_time('mysql'),
                ),
                array(
                    '%d', '%d', '%s', '%s', '%d', '%s', '%f', '%f', '%d', '%s', '%d', '%s', '%s', '%s'
                )
            );
        }
    }

    /**
     * Create a shipment record when legacy shipment data exists.
     */
    private function migrate_shipment($order_id, $legacy_order) {
        global $wpdb;

        if (empty($legacy_order->courier) && empty($legacy_order->tracking_number)) {
            return;
        }

        $shipments_table = $wpdb->prefix . 'yiari_shipments';

        $wpdb->insert(
            $shipments_table,
            array(
                'order_id' => $order_id,
                'provider' => 'legacy',
                'courier_code' => $legacy_order->courier,
                'service_type' => $legacy_order->service,
                'shipping_cost' => $legacy_order->shipping_cost,
                'total_weight_grams' => (int) $legacy_order->total_weight,
                'tracking_number' => $legacy_order->tracking_number,
                'tracking_url' => null,
                'external_shipment_id' => null,
                'shipment_status' => $this->map_legacy_shipment_status($legacy_order->order_status, $legacy_order->tracking_number),
                'request_payload' => null,
                'response_payload' => null,
                'created_at' => $legacy_order->created_at ?: current_time('mysql'),
                'updated_at' => $legacy_order->updated_at ?: current_time('mysql'),
            ),
            array(
                '%d', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
    }

    /**
     * Add baseline status logs to support audit trails for migrated orders.
     */
    private function insert_status_logs($order_id, $legacy_order, $payment_status, $fulfillment_status) {
        global $wpdb;

        $status_logs_table = $wpdb->prefix . 'yiari_order_status_logs';
        $logs = array(
            array(
                'order_id' => $order_id,
                'source' => 'legacy_migration',
                'status_type' => 'payment',
                'previous_status' => null,
                'new_status' => $payment_status,
                'message' => 'Migrated payment status from kukang_transactions_new',
                'context_payload' => wp_json_encode(array(
                    'legacy_transaction_status' => $legacy_order->transaction_status,
                    'legacy_transaction_id' => $legacy_order->transaction_id,
                )),
                'created_at' => $legacy_order->updated_at ?: current_time('mysql'),
            ),
            array(
                'order_id' => $order_id,
                'source' => 'legacy_migration',
                'status_type' => 'fulfillment',
                'previous_status' => null,
                'new_status' => $fulfillment_status,
                'message' => 'Migrated fulfillment status from kukang_transactions_new',
                'context_payload' => wp_json_encode(array(
                    'legacy_order_status' => $legacy_order->order_status,
                    'legacy_tracking_number' => $legacy_order->tracking_number,
                )),
                'created_at' => $legacy_order->updated_at ?: current_time('mysql'),
            ),
        );

        foreach ($logs as $log) {
            $wpdb->insert(
                $status_logs_table,
                $log,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Sum all legacy physical item quantities.
     */
    private function get_legacy_self_item_count($legacy_order) {
        return (int) $legacy_order->regina_qty
            + (int) $legacy_order->jagger_qty
            + (int) $legacy_order->butros_qty
            + (int) $legacy_order->eid_qty
            + (int) $legacy_order->anoda_qty;
    }

    /**
     * Map old transaction statuses into the new payment state machine.
     */
    private function map_legacy_payment_status($legacy_status) {
        $legacy_status = (string) $legacy_status;

        if ($legacy_status === 'settlement') {
            return 'paid';
        }

        if ($legacy_status === 'pending') {
            return 'pending_payment';
        }

        if (in_array($legacy_status, array('expire', 'cancel', 'deny'), true)) {
            return 'canceled';
        }

        return 'pending_payment';
    }

    /**
     * Map old order status values into normalized fulfillment states.
     */
    private function map_legacy_fulfillment_status($legacy_status, $tracking_number) {
        $legacy_status = (string) $legacy_status;

        if ($legacy_status === 'delivered') {
            return 'delivered';
        }

        if ($legacy_status === 'delivering') {
            return 'shipped';
        }

        if (!empty($tracking_number)) {
            return 'awb_created';
        }

        return 'paid';
    }

    /**
     * Map legacy shipment values into normalized shipment states.
     */
    private function map_legacy_shipment_status($legacy_status, $tracking_number) {
        $legacy_status = (string) $legacy_status;

        if ($legacy_status === 'delivered') {
            return 'delivered';
        }

        if ($legacy_status === 'delivering') {
            return 'in_transit';
        }

        if (!empty($tracking_number)) {
            return 'awb_created';
        }

        return 'pending';
    }
}
?>
