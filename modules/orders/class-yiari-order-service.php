<?php

/**
 * Service for writing normalized orders and order items.
 */
class YIARI_Order_Service {

    /**
     * Upsert a normalized order from legacy checkout data.
     *
     * @param array $checkout_data
     * @param array $options
     * @return int|null
     */
    public function upsert_normalized_order($checkout_data, $options = array()) {
        global $wpdb;

        if (empty($checkout_data['order_id'])) {
            return null;
        }

        $orders_table = $wpdb->prefix . 'yiari_orders';
        $existing_order_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$orders_table} WHERE order_number = %s LIMIT 1",
                $checkout_data['order_id']
            )
        );

        $selected_items = $this->build_order_items($checkout_data);
        $self_item_count = 0;
        $total_weight = 0;

        foreach ($selected_items as $item) {
            if ($item['fulfillment_type'] === 'self_purchase') {
                $self_item_count += $item['qty'];
            }
            $total_weight += $item['weight_grams_snapshot'] * $item['qty'];
        }

        $payment_status = isset($options['payment_status'])
            ? $options['payment_status']
            : $this->map_legacy_payment_status($checkout_data['transaction_status'] ?? 'pending');

        $fulfillment_status = isset($options['fulfillment_status'])
            ? $options['fulfillment_status']
            : (!empty($checkout_data['tracking_number']) ? 'awb_created' : 'draft');

        $order_row = array(
            'order_number' => $checkout_data['order_id'],
            'legacy_order_id' => $checkout_data['order_id'],
            'donor_name' => $checkout_data['customer_name'] ?? '',
            'donor_email' => $checkout_data['email'] ?? '',
            'donor_phone' => $checkout_data['phone'] ?? '',
            'address' => $checkout_data['address'] ?? '',
            'province' => $checkout_data['province'] ?? '',
            'city' => $checkout_data['city_name'] ?? ($checkout_data['city'] ?? ''),
            'postal_code' => $checkout_data['postal_code'] ?? '',
            'language' => $checkout_data['language'] ?? 'id',
            'currency' => $checkout_data['currency'] ?? 'IDR',
            'subtotal_amount' => $checkout_data['subtotal'] ?? 0,
            'shipping_amount' => $checkout_data['shipping_cost'] ?? 0,
            'total_amount' => $checkout_data['gross_amount'] ?? 0,
            'total_weight_grams' => $total_weight,
            'self_book_count' => $self_item_count,
            'donation_book_count' => intval($checkout_data['donation_book_count'] ?? 0),
            'contains_donation_items' => empty($checkout_data['donation_book_count']) ? 0 : 1,
            'donation_motivation_code' => $checkout_data['donation_motivation_code'] ?? null,
            'donation_motivation_other' => $checkout_data['donation_motivation_other'] ?? null,
            'payment_gateway' => 'midtrans',
            'payment_reference' => $options['payment_reference'] ?? ($checkout_data['transaction_id'] ?? null),
            'payment_type' => $checkout_data['payment_type'] ?? null,
            'payment_status' => $payment_status,
            'fraud_status' => $checkout_data['fraud_status'] ?? null,
            'fulfillment_status' => $fulfillment_status,
            'notes' => $checkout_data['notes'] ?? null,
            'transaction_time' => $checkout_data['transaction_time'] ?? null,
            'settlement_time' => $checkout_data['settlement_time'] ?? null,
            'created_at' => $checkout_data['created_at'] ?? current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        $formats = array(
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%f', '%f', '%f', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s'
        );

        if ($existing_order_id) {
            $wpdb->update($orders_table, $order_row, array('id' => $existing_order_id), $formats, array('%d'));
            $order_id = (int) $existing_order_id;
        } else {
            $wpdb->insert($orders_table, $order_row, $formats);
            $order_id = (int) $wpdb->insert_id;
        }

        if (!$order_id) {
            return null;
        }

        $this->replace_order_items($order_id, $selected_items, $checkout_data['currency'] ?? 'IDR', $checkout_data);

        return $order_id;
    }

    /**
     * Synchronize normalized order payment and fulfillment state from a Midtrans callback.
     *
     * @param string $order_number
     * @param array  $callback_data
     * @return array
     */
    public function sync_midtrans_callback($order_number, $callback_data) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'yiari_orders';
        $status_logs_table = $wpdb->prefix . 'yiari_order_status_logs';
        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$orders_table} WHERE order_number = %s LIMIT 1",
                $order_number
            )
        );

        if (!$order) {
            return array('success' => false, 'reason' => 'order_not_found');
        }

        $new_payment_status = $this->map_legacy_payment_status($callback_data['transaction_status'] ?? 'pending');
        $new_fulfillment_status = $this->map_midtrans_to_fulfillment_status(
            $new_payment_status,
            $order->fulfillment_status
        );

        $update_data = array(
            'payment_reference' => $callback_data['transaction_id'] ?? $order->payment_reference,
            'payment_type' => $callback_data['payment_type'] ?? $order->payment_type,
            'payment_status' => $new_payment_status,
            'fraud_status' => $callback_data['fraud_status'] ?? $order->fraud_status,
            'fulfillment_status' => $new_fulfillment_status,
            'updated_at' => current_time('mysql'),
        );

        if ($new_payment_status === 'paid' && empty($order->settlement_time)) {
            $update_data['settlement_time'] = current_time('mysql');
        }

        $wpdb->update($orders_table, $update_data, array('id' => $order->id), null, array('%d'));

        if ($order->payment_status !== $new_payment_status) {
            $wpdb->insert(
                $status_logs_table,
                array(
                    'order_id' => $order->id,
                    'source' => 'midtrans_callback',
                    'status_type' => 'payment',
                    'previous_status' => $order->payment_status,
                    'new_status' => $new_payment_status,
                    'message' => 'Payment status synchronized from Midtrans callback',
                    'context_payload' => wp_json_encode($callback_data),
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }

        if ($order->fulfillment_status !== $new_fulfillment_status) {
            $wpdb->insert(
                $status_logs_table,
                array(
                    'order_id' => $order->id,
                    'source' => 'midtrans_callback',
                    'status_type' => 'fulfillment',
                    'previous_status' => $order->fulfillment_status,
                    'new_status' => $new_fulfillment_status,
                    'message' => 'Fulfillment status synchronized from Midtrans callback',
                    'context_payload' => wp_json_encode($callback_data),
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }

        return array(
            'success' => true,
            'order_id' => (int) $order->id,
            'payment_status' => $new_payment_status,
            'fulfillment_status' => $new_fulfillment_status,
        );
    }

    /**
     * Build normalized items from legacy qty form data.
     *
     * @param array $checkout_data
     * @return array
     */
    private function build_order_items($checkout_data) {
        $repository = new YIARI_Product_Repository();
        $selected_products = $repository->get_selected_products_from_form($checkout_data);
        $items = array();

        foreach ($selected_products as $selected) {
            $product = $selected['product'];
            $qty = $selected['qty'];
            $currency = $checkout_data['currency'] ?? 'IDR';
            $unit_price = ($currency === 'USD')
                ? floatval($product->price_usd ?? 0)
                : floatval($product->price_idr ?? 0);

            $items[] = array(
                'product_id' => isset($product->id) ? intval($product->id) : 0,
                'sku_snapshot' => $product->sku ?? ('LEGACY-' . strtoupper(sanitize_title($product->name))),
                'product_name_snapshot' => $product->name,
                'qty' => $qty,
                'unit_price_amount' => $unit_price,
                'line_total_amount' => $unit_price * $qty,
                'weight_grams_snapshot' => intval($product->weight_grams ?? 0),
                'fulfillment_type' => 'self_purchase',
                'requires_shipping' => 1,
                'donation_recipient_type' => null,
                'metadata' => wp_json_encode(array('qty_key' => $selected['qty_key'])),
            );
        }

        return $items;
    }

    /**
     * Replace all normalized order items for the target order.
     *
     * @param int   $order_id
     * @param array $items
     * @param string $currency
     * @param array $checkout_data
     */
    private function replace_order_items($order_id, $items, $currency, $checkout_data) {
        global $wpdb;

        $order_items_table = $wpdb->prefix . 'yiari_order_items';
        $wpdb->delete($order_items_table, array('order_id' => $order_id), array('%d'));

        foreach ($items as $item) {
            $wpdb->insert(
                $order_items_table,
                array(
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'sku_snapshot' => $item['sku_snapshot'],
                    'product_name_snapshot' => $item['product_name_snapshot'],
                    'qty' => $item['qty'],
                    'currency' => $currency,
                    'unit_price_amount' => $item['unit_price_amount'],
                    'line_total_amount' => $item['line_total_amount'],
                    'weight_grams_snapshot' => $item['weight_grams_snapshot'],
                    'fulfillment_type' => $item['fulfillment_type'],
                    'requires_shipping' => $item['requires_shipping'],
                    'donation_recipient_type' => $item['donation_recipient_type'],
                    'metadata' => $item['metadata'],
                    'created_at' => $checkout_data['created_at'] ?? current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ),
                array(
                    '%d', '%d', '%s', '%s', '%d', '%s', '%f', '%f', '%d', '%s', '%d', '%s', '%s', '%s', '%s'
                )
            );
        }
    }

    /**
     * Map legacy statuses into the normalized payment state.
     *
     * @param string $legacy_status
     * @return string
     */
    private function map_legacy_payment_status($legacy_status) {
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
     * Map payment state to normalized fulfillment state without regressing shipped orders.
     *
     * @param string $payment_status
     * @param string $current_fulfillment_status
     * @return string
     */
    private function map_midtrans_to_fulfillment_status($payment_status, $current_fulfillment_status) {
        $terminal_statuses = array('awb_created', 'shipped', 'delivered', 'returned');

        if (in_array($current_fulfillment_status, $terminal_statuses, true)) {
            return $current_fulfillment_status;
        }

        if ($payment_status === 'paid') {
            return 'paid';
        }

        if ($payment_status === 'canceled') {
            return 'canceled';
        }

        return 'draft';
    }
}
?>
