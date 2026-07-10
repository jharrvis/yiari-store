<?php

/**
 * AJAX handlers for YIARI Donasi Kukang Plugin
 *
 * @since 3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler to check donor order status
 */
function yiari_ajax_check_donor_order_status() {
    global $wpdb;

    $order_id = sanitize_text_field($_POST['order_id'] ?? '');

    if (empty($order_id)) {
        wp_send_json_error(array('message' => 'Order ID tidak boleh kosong'));
    }

    $transactions_table = $wpdb->prefix . 'kukang_transactions_new';

    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $transactions_table WHERE order_id = %s",
        $order_id
    ));

    if (!$transaction) {
        echo '<div style="background: #ffe6e6; padding: 15px; border-radius: 5px; color: #d32f2f; text-align: center;">❌ Order ID tidak ditemukan. Pastikan Order ID Anda benar.</div>';
        wp_die();
    }

    // Get currency and format amount
    $currency = $transaction->currency ?? 'IDR';
    $amount = $currency === 'USD' ? $transaction->usd_amount : $transaction->gross_amount;
    $formatted_amount = yiari_format_price($amount, $currency);

    // Status mapping
    $status_map = array(
        'pending' => array('color' => '#ff9800', 'icon' => '⏳', 'text' => 'Menunggu Pembayaran'),
        'settlement' => array('color' => '#4caf50', 'icon' => '✅', 'text' => 'Pembayaran Berhasil'),
        'capture' => array('color' => '#4caf50', 'icon' => '✅', 'text' => 'Pembayaran Berhasil'),
        'deny' => array('color' => '#f44336', 'icon' => '❌', 'text' => 'Pembayaran Ditolak'),
        'cancel' => array('color' => '#f44336', 'icon' => '❌', 'text' => 'Pembayaran Dibatalkan'),
        'expire' => array('color' => '#f44336', 'icon' => '⏰', 'text' => 'Pembayaran Kadaluarsa'),
        'failure' => array('color' => '#f44336', 'icon' => '❌', 'text' => 'Pembayaran Gagal')
    );

    $payment_status = $status_map[$transaction->transaction_status] ?? array('color' => '#757575', 'icon' => '❓', 'text' => 'Status Tidak Diketahui');

    // Order status mapping
    $order_status_map = array(
        'processing' => array('color' => '#ff9800', 'icon' => '📦', 'text' => 'Sedang Diproses'),
        'packed' => array('color' => '#2196f3', 'icon' => '📋', 'text' => 'Dikemas'),
        'shipped' => array('color' => '#9c27b0', 'icon' => '🚚', 'text' => 'Dikirim'),
        'delivered' => array('color' => '#4caf50', 'icon' => '🏠', 'text' => 'Terkirim'),
        'cancelled' => array('color' => '#f44336', 'icon' => '❌', 'text' => 'Dibatalkan')
    );

    $order_status = $order_status_map[$transaction->order_status] ?? array('color' => '#757575', 'icon' => '❓', 'text' => 'Status Tidak Diketahui');

    // Build items list
    $items = array();
    if ($transaction->regina_qty > 0) $items[] = "Regina ({$transaction->regina_qty}x)";
    if ($transaction->jagger_qty > 0) $items[] = "Jagger ({$transaction->jagger_qty}x)";
    if ($transaction->butros_qty > 0) $items[] = "Butros ({$transaction->butros_qty}x)";
    if ($transaction->eid_qty > 0) $items[] = "Eid ({$transaction->eid_qty}x)";
    if ($transaction->anoda_qty > 0) $items[] = "Anoda ({$transaction->anoda_qty}x)";
    $items_text = implode(', ', $items);

    // Display result
    echo '<div style="background: #f0f8f0; border: 2px solid #4caf50; border-radius: 8px; padding: 20px; margin-top: 20px;">';
    echo '<h4 style="color: #2c5530; margin-top: 0; text-align: center;">📋 Detail Donasi Anda</h4>';
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">';

    echo '<div>';
    echo '<strong>Order ID:</strong><br>' . esc_html($transaction->order_id) . '<br><br>';
    echo '<strong>Nama:</strong><br>' . esc_html($transaction->customer_name) . '<br><br>';
    echo '<strong>Email:</strong><br>' . esc_html($transaction->email) . '<br><br>';
    echo '<strong>Total Items:</strong><br>' . $transaction->total_items . ' boneka<br><br>';
    echo '</div>';

    echo '<div>';
    echo '<strong>Total Pembayaran:</strong><br>' . $formatted_amount . '<br><br>';
    echo '<strong>Metode Pembayaran:</strong><br>' . esc_html($transaction->payment_type ?: 'Belum dipilih') . '<br><br>';
    echo '<strong>Tanggal Transaksi:</strong><br>' . date('d/m/Y H:i', strtotime($transaction->created_at)) . '<br><br>';
    if ($transaction->tracking_number) {
        echo '<strong>Nomor Resi:</strong><br>' . esc_html($transaction->tracking_number) . '<br><br>';
    }
    echo '</div>';

    echo '</div>';

    if (!empty($items_text)) {
        echo '<p><strong>Boneka Kukang:</strong><br>' . $items_text . '</p>';
    }

    // Status indicators
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">';
    echo '<div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid ' . $payment_status['color'] . ';">';
    echo '<div style="font-size: 24px; margin-bottom: 5px;">' . $payment_status['icon'] . '</div>';
    echo '<div style="font-weight: bold; color: ' . $payment_status['color'] . ';">Status Pembayaran</div>';
    echo '<div style="color: ' . $payment_status['color'] . ';">' . $payment_status['text'] . '</div>';
    echo '</div>';

    echo '<div style="text-align: center; padding: 15px; background: white; border-radius: 8px; border: 2px solid ' . $order_status['color'] . ';">';
    echo '<div style="font-size: 24px; margin-bottom: 5px;">' . $order_status['icon'] . '</div>';
    echo '<div style="font-weight: bold; color: ' . $order_status['color'] . ';">Status Pesanan</div>';
    echo '<div style="color: ' . $order_status['color'] . ';">' . $order_status['text'] . '</div>';
    echo '</div>';
    echo '</div>';

    if ($transaction->notes) {
        echo '<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">';
        echo '<strong>Catatan:</strong><br>' . esc_html($transaction->notes);
        echo '</div>';
    }

    echo '</div>';

    wp_die();
}
add_action('wp_ajax_nopriv_check_donor_order_status', 'yiari_ajax_check_donor_order_status');
add_action('wp_ajax_check_donor_order_status', 'yiari_ajax_check_donor_order_status');

/**
 * AJAX handler for basic form validation
 */
function yiari_ajax_validate_form() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'donasi_kukang_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }

    $errors = array();

    // Validate email
    $email = sanitize_email($_POST['email'] ?? '');
    if (empty($email) || !is_email($email)) {
        $errors['email'] = 'Format email tidak valid';
    }

    // Validate phone
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = 'Nomor telepon harus diisi';
    }

    // Validate name
    $name = sanitize_text_field($_POST['customer_name'] ?? '');
    if (empty($name) || strlen($name) < 3) {
        $errors['customer_name'] = 'Nama minimal 3 karakter';
    }

    if (!empty($errors)) {
        wp_send_json_error(array('errors' => $errors));
    }

    wp_send_json_success(array('message' => 'Validasi berhasil'));
}
add_action('wp_ajax_nopriv_validate_form', 'yiari_ajax_validate_form');
add_action('wp_ajax_validate_form', 'yiari_ajax_validate_form');

/**
 * AJAX handler to get doll prices
 */
function yiari_ajax_get_doll_prices() {
    global $wpdb;

    $dolls_table = $wpdb->prefix . 'kukang_dolls';
    $dolls = $wpdb->get_results("SELECT name, price FROM $dolls_table WHERE is_active = 1 ORDER BY id ASC");

    $prices = array();
    foreach ($dolls as $doll) {
        $prices[strtolower($doll->name)] = intval($doll->price);
    }

    wp_send_json_success($prices);
}
add_action('wp_ajax_nopriv_get_doll_prices', 'yiari_ajax_get_doll_prices');
add_action('wp_ajax_get_doll_prices', 'yiari_ajax_get_doll_prices');