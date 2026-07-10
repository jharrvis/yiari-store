<?php

/**
 * Admin Module for YIARI Donasi Kukang Plugin
 * 
 * Handles admin area functionality and settings pages
 */
class YIARI_Admin_Module {
    
    /**
     * Initialize admin module
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers for admin functionality
        add_action('wp_ajax_test_biteship_api', array($this, 'ajax_test_biteship_api'));
        add_action('wp_ajax_test_midtrans_gateway', array($this, 'ajax_test_midtrans_gateway'));
        add_action('wp_ajax_test_exchange_api', array($this, 'ajax_test_exchange_api'));
        add_action('wp_ajax_clear_kukang_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_refresh_exchange_rate', array($this, 'ajax_refresh_exchange_rate'));
        add_action('wp_ajax_test_exchange_rate_api', array($this, 'ajax_test_exchange_rate_api'));
        add_action('wp_ajax_update_exchange_rate_now', array($this, 'ajax_update_exchange_rate_now'));
        add_action('wp_ajax_save_manual_exchange_rate', array($this, 'ajax_save_manual_exchange_rate'));

        // Biteship testing AJAX
        add_action('wp_ajax_test_biteship_api_connection', array($this, 'ajax_test_biteship_api_connection'));
        add_action('wp_ajax_test_shipping_calculation', array($this, 'ajax_test_shipping_calculation'));

        // Midtrans testing AJAX
        add_action('wp_ajax_test_midtrans_connection', array($this, 'ajax_test_midtrans_connection'));
        add_action('wp_ajax_test_midtrans_payment', array($this, 'ajax_test_midtrans_payment'));

        // Transaction management AJAX
        add_action('wp_ajax_check_transaction_status', array($this, 'ajax_check_transaction_status'));
        add_action('wp_ajax_get_transaction_details', array($this, 'ajax_get_transaction_details'));
        add_action('wp_ajax_update_order_status', array($this, 'ajax_update_order_status'));
        add_action('wp_ajax_download_invoice', array($this, 'ajax_download_invoice'));
        add_action('wp_ajax_download_packing_slip', array($this, 'ajax_download_packing_slip'));
    }
    
    /**
     * Add admin menu
     *
     * @since    3.1.0
     */
    public function add_admin_menu() {
        add_menu_page(
            'Donasi Midtrans',
            'Donasi Midtrans',
            'manage_options',
            'donasi-midtrans',
            array($this, 'render_admin_dashboard'),
            'dashicons-heart',
            80
        );
        
        add_submenu_page(
            'donasi-midtrans',
            'Daftar Donasi',
            'Daftar Donasi',
            'manage_options',
            'donasi-list',
            array($this, 'render_donation_list')
        );
        
        add_submenu_page(
            'donasi-midtrans',
            'Kelola Boneka Kukang',
            'Boneka Kukang',
            'manage_options',
            'kukang-dolls',
            array($this, 'render_dolls_management')
        );
        
        add_submenu_page(
            'donasi-midtrans',
            'Pengaturan Biteship',
            'Biteship Settings',
            'manage_options',
            'biteship-settings',
            array($this, 'render_biteship_settings')
        );
        
        add_submenu_page(
            'donasi-midtrans',
            'Currency Settings',
            'Currency & Exchange Rate',
            'manage_options',
            'currency-settings',
            array($this, 'render_currency_settings')
        );
        
        add_submenu_page(
            'donasi-midtrans',
            'Pengaturan Midtrans',
            'Midtrans Settings',
            'manage_options',
            'midtrans-settings',
            array($this, 'render_midtrans_settings')
        );
    }
    
    /**
     * Render admin dashboard
     *
     * @since    3.1.0
     */
    public function render_admin_dashboard() {
        // Implementation will be moved from the main file
        // This is a placeholder to maintain backward compatibility
        echo '<div class="wrap"><h1>Dashboard Donasi Midtrans</h1><p>Halaman dashboard akan dimuat di sini.</p></div>';
    }
    
    /**
     * Render donation list
     *
     * @since    3.1.0
     */
    public function render_donation_list() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle export request
        if (isset($_POST['export'])) {
            $this->handle_export_request();
            return;
        }

        // Handle check status request
        if (isset($_POST['check_status'])) {
            $this->handle_check_status_request();
            return;
        }

        $this->display_donation_list_page();
    }

    /**
     * Handle export request
     *
     * @since    3.1.0
     */
    private function handle_export_request() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kukang_transactions_new';

        // Build WHERE clause
        $where_conditions = array();

        $search_term = isset($_POST['export_search']) ? sanitize_text_field($_POST['export_search']) : '';
        if (!empty($search_term)) {
            $where_conditions[] = $wpdb->prepare("CONCAT(order_id, customer_name, email, phone, address, city, province) LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        }

        $bulan = isset($_POST['export_bulan']) ? sanitize_text_field($_POST['export_bulan']) : '';
        if (!empty($bulan)) {
            $where_conditions[] = $wpdb->prepare("MONTH(created_at) = %s", $bulan);
        }

        $tahun = isset($_POST['export_tahun']) ? sanitize_text_field($_POST['export_tahun']) : '';
        if (!empty($tahun)) {
            $where_conditions[] = $wpdb->prepare("YEAR(created_at) = %s", $tahun);
        }

        $status = isset($_POST['export_status']) ? sanitize_text_field($_POST['export_status']) : '';
        if (!empty($status)) {
            $where_conditions[] = $wpdb->prepare("transaction_status = %s", $status);
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // Get filtered data for export
        $export_query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC";
        $donasi_data = $wpdb->get_results($export_query, ARRAY_A);

        if ($donasi_data && count($donasi_data) > 0) {
            $this->export_to_csv($donasi_data);
            exit;
        } else {
            wp_die('Tidak ada data untuk diekspor dengan filter yang dipilih.');
        }
    }

    /**
     * Export data to CSV
     *
     * @since    3.1.0
     * @param    array    $data    Data to export
     */
    private function export_to_csv($data) {
        $filename = 'donasi_kukang_' . date('Y-m-d_H-i') . '.csv';

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $output = fopen('php://output', 'w');

        // Header row
        $headers = [
            'Order ID', 'Tanggal', 'Customer', 'Email', 'Phone', 'Alamat',
            'Kota', 'Provinsi', 'Kode Pos', 'Payment Status', 'Payment Method',
            'Bank', 'Total Amount', 'Shipping Cost', 'Regina Qty', 'Jagger Qty',
            'Butros Qty', 'Eid Qty', 'Anoda Qty', 'Total Items', 'Weight',
            'Order Status', 'Tracking Number'
        ];

        // Output BOM for UTF-8
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);

        // Data rows
        foreach ($data as $row) {
            $export_row = [
                $row['order_id'] ?? '',
                isset($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '',
                $row['customer_name'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['address'] ?? '',
                $row['city'] ?? '',
                $row['province'] ?? '',
                $row['postal_code'] ?? '',
                $row['transaction_status'] ?? '',
                $row['payment_type'] ?? '',
                $row['bank'] ?? '',
                isset($row['gross_amount']) ? 'Rp ' . number_format($row['gross_amount'], 0, ',', '.') : '',
                isset($row['shipping_cost']) ? 'Rp ' . number_format($row['shipping_cost'], 0, ',', '.') : '',
                $row['regina_qty'] ?? 0,
                $row['jagger_qty'] ?? 0,
                $row['butros_qty'] ?? 0,
                $row['eid_qty'] ?? 0,
                $row['anoda_qty'] ?? 0,
                $row['total_items'] ?? 0,
                isset($row['total_weight']) ? $row['total_weight'] . 'g' : '',
                $row['order_status'] ?? 'processing',
                $row['tracking_number'] ?? ''
            ];
            fputcsv($output, $export_row);
        }

        fclose($output);
    }

    /**
     * Display donation list page
     *
     * @since    3.1.0
     */
    private function display_donation_list_page() {
        ?>
        <div class="wrap">
            <h2>Daftar Donasi</h2>

            <!-- Combined Filter Form -->
            <div class="filter-container" style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <form method="get" class="filter-form">
                    <input type="hidden" name="page" value="donasi-list">

                    <div class="filter-row" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
                        <!-- Search Filter -->
                        <div class="filter-item">
                            <label for="search" style="display: block; font-weight: 600; margin-bottom: 3px;">🔍 Pencarian:</label>
                            <input type="text" name="search" id="search" placeholder="Order ID, nama, email..." value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>" style="width: 200px; padding: 5px;">
                        </div>

                        <!-- Month Filter -->
                        <div class="filter-item">
                            <label for="bulan" style="display: block; font-weight: 600; margin-bottom: 3px;">📅 Bulan:</label>
                            <select name="bulan" id="bulan" style="padding: 5px; width: 120px;">
                                <option value="">Semua Bulan</option>
                                <?php
                                $selected_month = isset($_GET['bulan']) ? $_GET['bulan'] : '';
                                $months = array(
                                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                );
                                foreach ($months as $key => $value) {
                                    echo '<option value="' . $key . '"' . ($selected_month == $key ? ' selected' : '') . '>' . $value . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="filter-item">
                            <label for="tahun" style="display: block; font-weight: 600; margin-bottom: 3px;">📆 Tahun:</label>
                            <select name="tahun" id="tahun" style="padding: 5px; width: 100px;">
                                <option value="">Semua Tahun</option>
                                <?php
                                $selected_year = isset($_GET['tahun']) ? $_GET['tahun'] : '';
                                $current_year = date('Y');
                                for ($year = $current_year; $year >= 2020; $year--) {
                                    echo '<option value="' . $year . '"' . ($selected_year == $year ? ' selected' : '') . '>' . $year . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="filter-item">
                            <label for="status" style="display: block; font-weight: 600; margin-bottom: 3px;">💳 Status:</label>
                            <select name="status" id="status" style="padding: 5px; width: 120px;">
                                <option value="">Semua Status</option>
                                <option value="settlement"<?php echo isset($_GET['status']) && $_GET['status'] === 'settlement' ? ' selected' : ''; ?>>✅ Settlement</option>
                                <option value="capture"<?php echo isset($_GET['status']) && $_GET['status'] === 'capture' ? ' selected' : ''; ?>>✅ Capture</option>
                                <option value="pending"<?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? ' selected' : ''; ?>>⏳ Pending</option>
                                <option value="failure"<?php echo isset($_GET['status']) && $_GET['status'] === 'failure' ? ' selected' : ''; ?>>❌ Failure</option>
                                <option value="deny"<?php echo isset($_GET['status']) && $_GET['status'] === 'deny' ? ' selected' : ''; ?>>❌ Deny</option>
                                <option value="cancel"<?php echo isset($_GET['status']) && $_GET['status'] === 'cancel' ? ' selected' : ''; ?>>🚫 Cancel</option>
                                <option value="expire"<?php echo isset($_GET['status']) && $_GET['status'] === 'expire' ? ' selected' : ''; ?>>⏰ Expire</option>
                                <option value="refund"<?php echo isset($_GET['status']) && $_GET['status'] === 'refund' ? ' selected' : ''; ?>>💰 Refund</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="filter-actions" style="display: flex; gap: 5px;">
                            <input type="submit" class="button button-primary" value="🔍 Filter" style="height: 32px;">
                            <a href="<?php echo admin_url('admin.php?page=donasi-list'); ?>" class="button" style="height: 32px; line-height: 30px; text-decoration: none;">🔄 Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <?php $this->display_donation_table(); ?>
        </div>
        <?php
    }

    /**
     * Display donation table with data
     *
     * @since    3.1.0
     */
    private function display_donation_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kukang_transactions_new';

        // Ensure table exists
        $database_manager = new YIARI_Database_Manager();
        $database_manager->create_tables();

        // Build WHERE clause with proper sanitization
        $where_conditions = array();

        $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        if (!empty($search_term)) {
            $where_conditions[] = $wpdb->prepare("CONCAT(order_id, customer_name, email, phone, address, city, province) LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        }

        $bulan = isset($_GET['bulan']) ? sanitize_text_field($_GET['bulan']) : '';
        if (!empty($bulan)) {
            $where_conditions[] = $wpdb->prepare("MONTH(created_at) = %s", $bulan);
        }

        $tahun = isset($_GET['tahun']) ? sanitize_text_field($_GET['tahun']) : '';
        if (!empty($tahun)) {
            $where_conditions[] = $wpdb->prepare("YEAR(created_at) = %s", $tahun);
        }

        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        if (!empty($status)) {
            $where_conditions[] = $wpdb->prepare("transaction_status = %s", $status);
        }

        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // Get filtered data
        $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC";
        $donasi_data = $wpdb->get_results($query, ARRAY_A);

        if ($donasi_data && count($donasi_data) > 0) {
            // Export form with current filter parameters
            echo '<div class="export-section" style="margin-bottom: 15px;">';
            echo '<form method="post" style="display: inline-block; margin-right: 10px;">';

            // Pass current filter parameters to export
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                echo '<input type="hidden" name="export_search" value="' . esc_attr($_GET['search']) . '">';
            }
            if (isset($_GET['bulan']) && !empty($_GET['bulan'])) {
                echo '<input type="hidden" name="export_bulan" value="' . esc_attr($_GET['bulan']) . '">';
            }
            if (isset($_GET['tahun']) && !empty($_GET['tahun'])) {
                echo '<input type="hidden" name="export_tahun" value="' . esc_attr($_GET['tahun']) . '">';
            }
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                echo '<input type="hidden" name="export_status" value="' . esc_attr($_GET['status']) . '">';
            }

            echo '<input type="submit" class="button button-primary" name="export" value="📊 Export to Excel" style="background: #00a32a; border-color: #00a32a;">';
            echo '</form>';

            // Show record count
            echo '<span style="color: #666; font-style: italic;">Menampilkan ' . count($donasi_data) . ' donasi</span>';
            echo '</div>';

            // Display table
            $this->render_donations_table($donasi_data);
        } else {
            echo '<div class="notice notice-warning inline"><p>Tidak ada data donasi ditemukan.</p></div>';
        }
    }

    /**
     * Render donations table
     *
     * @since    3.1.0
     * @param    array    $donasi_data    Donation data
     */
    private function render_donations_table($donasi_data) {
        // Responsive table with better layout
        echo '<div class="donasi-table-container">';
        echo '<table class="wp-list-table widefat striped donasi-table">';
        echo '<thead><tr>';
        echo '<th class="col-order-info">Order Info</th>';
        echo '<th class="col-customer">Customer & Items</th>';
        echo '<th class="col-payment">Payment & Status</th>';
        echo '<th class="col-shipping">Shipping & Tracking</th>';
        echo '<th class="col-actions">Actions</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($donasi_data as $donasi) {
            echo '<tr class="donasi-row">';

            // Column 1: Order Info (Order ID + Date)
            echo '<td class="col-order-info">';
            echo '<div class="order-id-block">';
            echo '<strong style="font-size: 13px; color: #2271b1;">' . esc_html($donasi['order_id']) . '</strong><br>';
            echo '<small style="color: #666;">' . date('d/m/Y H:i', strtotime($donasi['created_at'])) . '</small>';
            if ($donasi['settlement_time']) {
                echo '<br><small style="color: green;">✅ Paid: ' . date('d/m H:i', strtotime($donasi['settlement_time'])) . '</small>';
            }
            echo '</div>';
            echo '</td>';

            // Column 2: Customer & Items
            echo '<td class="col-customer">';
            echo '<div class="customer-block">';
            echo '<strong>' . esc_html($donasi['customer_name']) . '</strong><br>';
            echo '<small>📧 ' . esc_html($donasi['email']) . '</small><br>';
            echo '<small>📱 ' . esc_html($donasi['phone'] ?: '-') . '</small>';
            echo '</div>';

            // Items summary
            echo '<div class="items-block" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f0f0f0;">';
            $dolls = array();
            if ($donasi['regina_qty'] > 0) $dolls[] = 'Regina: ' . intval($donasi['regina_qty']);
            if ($donasi['jagger_qty'] > 0) $dolls[] = 'Jagger: ' . intval($donasi['jagger_qty']);
            if ($donasi['butros_qty'] > 0) $dolls[] = 'Butros: ' . intval($donasi['butros_qty']);
            if ($donasi['eid_qty'] > 0) $dolls[] = 'Eid: ' . intval($donasi['eid_qty']);
            if ($donasi['anoda_qty'] > 0) $dolls[] = 'Anoda: ' . intval($donasi['anoda_qty']);

            if (count($dolls) > 0) {
                echo '<small style="color: #666;">🐒 ' . implode(', ', $dolls) . '</small><br>';
                echo '<small><strong>Total: ' . intval($donasi['total_items']) . ' pcs (' . intval($donasi['total_weight']) . 'g)</strong></small>';
            } else {
                echo '<small style="color: #999; font-style: italic;">No items data</small>';
            }
            echo '</div>';
            echo '</td>';

            // Column 3: Payment & Status
            echo '<td class="col-payment">';
            $status = $donasi['transaction_status'];
            $status_icon = '';
            $status_color = '';
            switch (strtolower($status)) {
                case 'settlement':
                case 'capture':
                    $status_icon = '✅';
                    $status_color = '#27ae60';
                    break;
                case 'pending':
                    $status_icon = '⏳';
                    $status_color = '#f39c12';
                    break;
                case 'deny':
                case 'failure':
                    $status_icon = '❌';
                    $status_color = '#e74c3c';
                    break;
                case 'cancel':
                    $status_icon = '🚫';
                    $status_color = '#e74c3c';
                    break;
                case 'expire':
                    $status_icon = '⏰';
                    $status_color = '#95a5a6';
                    break;
                default:
                    $status_icon = '❓';
                    $status_color = '#95a5a6';
            }

            echo '<div class="payment-status-block">';
            echo '<div class="status-badge" style="display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; color: white; background-color: ' . $status_color . ';">';
            echo $status_icon . ' ' . strtoupper($status);
            echo '</div><br>';

            // Amount
            $currency = $donasi['currency'] ?? 'IDR';
            if ($currency === 'USD' && isset($donasi['usd_amount'])) {
                echo '<strong style="color: #2271b1;">$' . number_format($donasi['usd_amount'], 2) . '</strong><br>';
                echo '<small style="color: #666;">≈ Rp ' . number_format($donasi['gross_amount'], 0, ',', '.') . '</small>';
            } else {
                echo '<strong style="color: #2271b1;">Rp ' . number_format($donasi['gross_amount'], 0, ',', '.') . '</strong>';
            }

            if ($donasi['payment_type']) {
                echo '<br><small style="color: #666;">via ' . strtoupper($donasi['payment_type']);
                if ($donasi['bank']) {
                    echo ' (' . strtoupper($donasi['bank']) . ')';
                }
                echo '</small>';
            }
            echo '</div>';
            echo '</td>';

            // Column 4: Shipping & Tracking
            echo '<td class="col-shipping">';
            echo '<div class="shipping-block">';
            if ($donasi['address']) {
                echo '<small>📍 ' . esc_html($donasi['city'] ?: 'No city') . ', ' . esc_html($donasi['province'] ?: 'No province') . '</small><br>';
                echo '<small>Shipping: Rp ' . number_format($donasi['shipping_cost'] ?: 0, 0, ',', '.') . '</small>';
            }

            // Order status
            $order_status = $donasi['order_status'] ?? 'processing';
            $order_status_icon = '';
            $order_status_color = '';
            switch (strtolower($order_status)) {
                case 'processing':
                    $order_status_icon = '📦';
                    $order_status_color = '#f39c12';
                    break;
                case 'packed':
                    $order_status_icon = '📋';
                    $order_status_color = '#3498db';
                    break;
                case 'shipped':
                    $order_status_icon = '🚚';
                    $order_status_color = '#9b59b6';
                    break;
                case 'delivered':
                    $order_status_icon = '🏠';
                    $order_status_color = '#27ae60';
                    break;
                default:
                    $order_status_icon = '❓';
                    $order_status_color = '#95a5a6';
            }

            echo '<br><div class="order-status-badge" style="display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; color: white; background-color: ' . $order_status_color . ';">';
            echo $order_status_icon . ' ' . strtoupper($order_status);
            echo '</div>';

            if ($donasi['tracking_number']) {
                echo '<br><small style="color: #2271b1; font-weight: bold;">📋 ' . esc_html($donasi['tracking_number']) . '</small>';
            }
            echo '</div>';
            echo '</td>';

            // Column 5: Actions
            echo '<td class="col-actions">';
            echo '<div class="action-buttons" style="display: flex; gap: 5px; flex-direction: column;">';

            // Check status button
            echo '<button class="button button-small check-status-btn" data-order-id="' . esc_attr($donasi['order_id']) . '" style="font-size: 11px; height: 24px; line-height: 22px;">🔄 Check Status</button>';

            // View details modal button
            echo '<button class="button button-small view-details-btn" data-transaction-id="' . esc_attr($donasi['id']) . '" style="font-size: 11px; height: 24px; line-height: 22px;">👁️ Details</button>';

            // Order status update button (only for paid transactions)
            $payment_status = strtolower($donasi['transaction_status']);
            if (in_array($payment_status, ['settlement', 'capture'])) {
                $current_order_status = $donasi['order_status'] ?? 'processing';
                if ($current_order_status === 'processing') {
                    echo '<button class="button button-small update-status-btn" data-transaction-id="' . esc_attr($donasi['id']) . '" data-current-status="' . esc_attr($current_order_status) . '" style="font-size: 11px; height: 24px; line-height: 22px; background: #2271b1; color: white;">📦 Update Status</button>';
                } elseif ($current_order_status === 'delivering') {
                    echo '<button class="button button-small update-status-btn" data-transaction-id="' . esc_attr($donasi['id']) . '" data-current-status="' . esc_attr($current_order_status) . '" style="font-size: 11px; height: 24px; line-height: 22px; background: #9b59b6; color: white;">🚚 Track Order</button>';
                }
            }

            // PDF download buttons (only for settled transactions)
            if (in_array($payment_status, ['settlement', 'capture'])) {
                echo '<button class="button button-small download-invoice-btn" data-transaction-id="' . esc_attr($donasi['id']) . '" style="font-size: 11px; height: 24px; line-height: 22px; background: #27ae60; color: white;">📄 Invoice</button>';
                echo '<button class="button button-small download-packing-btn" data-transaction-id="' . esc_attr($donasi['id']) . '" style="font-size: 11px; height: 24px; line-height: 22px; background: #e67e22; color: white;">📋 Packing Slip</button>';
            }

            echo '</div>';
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        // Add transaction details modal
        echo '<div id="transaction-details-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">';
        echo '  <div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 800px; max-height: 80%; overflow-y: auto;">';
        echo '    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">';
        echo '      <h2 style="margin: 0;">📄 Transaction Details</h2>';
        echo '      <span id="modal-close" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>';
        echo '    </div>';
        echo '    <div id="modal-content-loading" style="text-align: center; padding: 40px; color: #666;">🔄 Loading...</div>';
        echo '    <div id="modal-content-details" style="display: none;"></div>';
        echo '  </div>';
        echo '</div>';

        // Add order status update modal
        echo '<div id="update-status-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">';
        echo '  <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border-radius: 5px; width: 60%; max-width: 500px;">';
        echo '    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">';
        echo '      <h2 style="margin: 0;">📦 Update Order Status</h2>';
        echo '      <span id="status-modal-close" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>';
        echo '    </div>';
        echo '    <div id="status-modal-content">';
        echo '      <div style="margin-bottom: 15px;">';
        echo '        <label for="new-order-status" style="display: block; font-weight: bold; margin-bottom: 5px;">New Status:</label>';
        echo '        <select id="new-order-status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">';
        echo '          <option value="processing">📦 Processing</option>';
        echo '          <option value="delivering">🚚 Delivering</option>';
        echo '          <option value="delivered">🏠 Delivered</option>';
        echo '        </select>';
        echo '      </div>';
        echo '      <div id="tracking-number-section" style="margin-bottom: 15px; display: none;">';
        echo '        <label for="tracking-number" style="display: block; font-weight: bold; margin-bottom: 5px;">Tracking Number:</label>';
        echo '        <input type="text" id="tracking-number" placeholder="Enter tracking number..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">';
        echo '      </div>';
        echo '      <div style="margin-bottom: 15px;">';
        echo '        <label for="status-notes" style="display: block; font-weight: bold; margin-bottom: 5px;">Notes (Optional):</label>';
        echo '        <textarea id="status-notes" placeholder="Add any notes..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; height: 60px; resize: vertical;"></textarea>';
        echo '      </div>';
        echo '      <div style="text-align: right;">';
        echo '        <button id="cancel-status-update" class="button" style="margin-right: 10px;">Cancel</button>';
        echo '        <button id="save-status-update" class="button button-primary">Update Status</button>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';

        // Add JavaScript functionality
        echo '<script>
        jQuery(document).ready(function($) {
            // Modal functionality
            $(".view-details-btn").click(function() {
                var transactionId = $(this).data("transaction-id");
                $("#transaction-details-modal").show();
                $("#modal-content-loading").show();
                $("#modal-content-details").hide();

                // Load transaction details via AJAX
                $.post(ajaxurl, {
                    action: "get_transaction_details",
                    transaction_id: transactionId,
                    nonce: "' . wp_create_nonce('admin_nonce') . '"
                }, function(response) {
                    $("#modal-content-loading").hide();
                    if (response.success) {
                        $("#modal-content-details").html(response.data.html).show();
                    } else {
                        $("#modal-content-details").html("<p style=\"color: red;\">❌ Error loading details: " + response.data.message + "</p>").show();
                    }
                }).fail(function() {
                    $("#modal-content-loading").hide();
                    $("#modal-content-details").html("<p style=\"color: red;\">❌ Network error occurred</p>").show();
                });
            });

            // Check status functionality
            $(".check-status-btn").click(function() {
                var orderId = $(this).data("order-id");
                var btn = $(this);
                var originalText = btn.text();

                btn.prop("disabled", true).text("🔄 Checking...");

                $.post(ajaxurl, {
                    action: "check_transaction_status",
                    order_id: orderId,
                    nonce: "' . wp_create_nonce('admin_nonce') . '"
                }, function(response) {
                    if (response.success) {
                        alert("✅ Status updated successfully!\\n\\nStatus: " + response.data.status + "\\nMessage: " + response.data.message);
                        location.reload(); // Refresh page to show updated status
                    } else {
                        alert("❌ Error: " + response.data.message);
                    }
                }).fail(function() {
                    alert("❌ Network error occurred");
                }).always(function() {
                    btn.prop("disabled", false).text(originalText);
                });
            });

            // Close modal functionality
            $("#modal-close, #transaction-details-modal").click(function(e) {
                if (e.target === this) {
                    $("#transaction-details-modal").hide();
                }
            });

            // Status update modal functionality
            $(".update-status-btn").click(function() {
                var transactionId = $(this).data("transaction-id");
                var currentStatus = $(this).data("current-status");

                $("#update-status-modal").data("transaction-id", transactionId);
                $("#new-order-status").val(currentStatus);
                $("#update-status-modal").show();

                // Show/hide tracking number section based on status
                if (currentStatus === "delivering") {
                    $("#tracking-number-section").show();
                } else {
                    $("#tracking-number-section").hide();
                }
            });

            // Status dropdown change handler
            $("#new-order-status").change(function() {
                if ($(this).val() === "delivering") {
                    $("#tracking-number-section").show();
                } else {
                    $("#tracking-number-section").hide();
                }
            });

            // Close status modal
            $("#status-modal-close, #cancel-status-update, #update-status-modal").click(function(e) {
                if (e.target === this) {
                    $("#update-status-modal").hide();
                    $("#tracking-number").val("");
                    $("#status-notes").val("");
                }
            });

            // Save status update
            $("#save-status-update").click(function() {
                var transactionId = $("#update-status-modal").data("transaction-id");
                var newStatus = $("#new-order-status").val();
                var trackingNumber = $("#tracking-number").val();
                var notes = $("#status-notes").val();
                var btn = $(this);

                // Validation
                if (newStatus === "delivering" && !trackingNumber.trim()) {
                    alert("Please enter a tracking number for delivering status.");
                    return;
                }

                btn.prop("disabled", true).text("Updating...");

                $.post(ajaxurl, {
                    action: "update_order_status",
                    transaction_id: transactionId,
                    new_status: newStatus,
                    tracking_number: trackingNumber,
                    notes: notes,
                    nonce: "' . wp_create_nonce('admin_nonce') . '"
                }, function(response) {
                    if (response.success) {
                        alert("✅ Order status updated successfully!");
                        location.reload();
                    } else {
                        alert("❌ Error: " + response.data.message);
                    }
                }).fail(function() {
                    alert("❌ Network error occurred");
                }).always(function() {
                    btn.prop("disabled", false).text("Update Status");
                });
            });

            // PDF download functionality
            $(".download-invoice-btn").click(function() {
                var transactionId = $(this).data("transaction-id");
                window.open(ajaxurl + "?action=download_invoice&transaction_id=" + transactionId + "&nonce=' . wp_create_nonce('admin_nonce') . '", "_blank");
            });

            $(".download-packing-btn").click(function() {
                var transactionId = $(this).data("transaction-id");
                window.open(ajaxurl + "?action=download_packing_slip&transaction_id=" + transactionId + "&nonce=' . wp_create_nonce('admin_nonce') . '", "_blank");
            });
        });
        </script>';

        // Add some basic styling
        echo '<style>
        .donasi-table-container {
            overflow-x: auto;
        }
        .donasi-table th,
        .donasi-table td {
            vertical-align: top;
            padding: 8px 10px;
        }
        .donasi-table th {
            background: #f1f1f1;
            font-weight: 600;
        }
        .col-order-info { width: 15%; }
        .col-customer { width: 25%; }
        .col-payment { width: 20%; }
        .col-shipping { width: 20%; }
        .col-actions { width: 15%; }
        .order-id-block, .customer-block, .items-block, .payment-status-block, .shipping-block {
            margin-bottom: 5px;
        }
        .action-buttons .button {
            width: 100%;
            margin-bottom: 2px;
        }
        @media (max-width: 768px) {
            .donasi-table th,
            .donasi-table td {
                font-size: 12px;
                padding: 6px 8px;
            }
        }
        </style>';
    }
    
    /**
     * Handle doll CRUD operations
     *
     * @since    3.1.0
     */
    private function handle_doll_operations() {
        global $wpdb;

        // Verify nonce
        if (!isset($_POST['doll_nonce']) || !wp_verify_nonce($_POST['doll_nonce'], 'doll_management')) {
            wp_die('Security check failed');
        }

        $action = sanitize_text_field($_POST['action']);
        $table_name = $wpdb->prefix . 'kukang_dolls_new';

        switch ($action) {
            case 'add':
                $data = array(
                    'name' => sanitize_text_field($_POST['doll_name']),
                    'price_idr' => intval($_POST['price_idr']),
                    'price_usd' => floatval($_POST['price_usd']),
                    'weight_grams' => intval($_POST['weight_grams']),
                    'length_cm' => intval($_POST['length_cm']),
                    'width_cm' => intval($_POST['width_cm']),
                    'height_cm' => intval($_POST['height_cm']),
                    'description' => sanitize_textarea_field($_POST['description']),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                );

                // Auto-calculate USD price if not provided
                if (empty($data['price_usd']) && $data['price_idr'] > 0) {
                    $currency_settings = $wpdb->get_row("SELECT usd_rate FROM {$wpdb->prefix}kukang_currency_new WHERE currency_code = 'USD'");
                    $usd_rate = $currency_settings ? $currency_settings->usd_rate : 0.000067;
                    $data['price_usd'] = $data['price_idr'] * $usd_rate;
                }

                $result = $wpdb->insert($table_name, $data);

                if ($result !== false) {
                    echo '<div class="notice notice-success is-dismissible"><p>Boneka berhasil ditambahkan!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Gagal menambahkan boneka: ' . $wpdb->last_error . '</p></div>';
                }
                break;

            case 'edit':
                $doll_id = intval($_POST['doll_id']);
                $data = array(
                    'name' => sanitize_text_field($_POST['doll_name']),
                    'price_idr' => intval($_POST['price_idr']),
                    'price_usd' => floatval($_POST['price_usd']),
                    'weight_grams' => intval($_POST['weight_grams']),
                    'length_cm' => intval($_POST['length_cm']),
                    'width_cm' => intval($_POST['width_cm']),
                    'height_cm' => intval($_POST['height_cm']),
                    'description' => sanitize_textarea_field($_POST['description']),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                );

                // Auto-calculate USD price if not provided
                if (empty($data['price_usd']) && $data['price_idr'] > 0) {
                    $currency_settings = $wpdb->get_row("SELECT usd_rate FROM {$wpdb->prefix}kukang_currency_new WHERE currency_code = 'USD'");
                    $usd_rate = $currency_settings ? $currency_settings->usd_rate : 0.000067;
                    $data['price_usd'] = $data['price_idr'] * $usd_rate;
                }

                $result = $wpdb->update($table_name, $data, array('id' => $doll_id));

                if ($result !== false) {
                    echo '<div class="notice notice-success is-dismissible"><p>Boneka berhasil diperbarui!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Gagal memperbarui boneka: ' . $wpdb->last_error . '</p></div>';
                }
                break;

            case 'delete':
                $doll_id = intval($_POST['doll_id']);
                $result = $wpdb->delete($table_name, array('id' => $doll_id));

                if ($result !== false) {
                    echo '<div class="notice notice-success is-dismissible"><p>Boneka berhasil dihapus!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Gagal menghapus boneka: ' . $wpdb->last_error . '</p></div>';
                }
                break;
        }
    }

    /**
     * Handle Biteship settings update
     *
     * @since    3.1.1
     */
    private function handle_biteship_settings_update() {
        // Verify nonce
        if (!isset($_POST['biteship_nonce']) || !wp_verify_nonce($_POST['biteship_nonce'], 'biteship_settings')) {
            wp_die('Security check failed');
        }

        $biteship_settings = array(
            'api_key' => sanitize_text_field($_POST['api_key']),
            'environment' => sanitize_text_field($_POST['environment']),
            'origin_area_id' => sanitize_text_field($_POST['origin_area_id'])
        );

        $updated = update_option('biteship_settings', $biteship_settings);

        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Biteship settings saved successfully!</p></div>';
        } else {
            echo '<div class="notice notice-info is-dismissible"><p>No changes made to Biteship settings.</p></div>';
        }
    }

    /**
     * Handle Midtrans settings update
     *
     * @since    3.1.1
     */
    private function handle_midtrans_settings_update() {
        // Verify nonce
        if (!isset($_POST['midtrans_nonce']) || !wp_verify_nonce($_POST['midtrans_nonce'], 'midtrans_settings')) {
            wp_die('Security check failed');
        }

        $midtrans_settings = array(
            'environment' => sanitize_text_field($_POST['environment']),
            'sandbox_server_key' => sanitize_text_field($_POST['sandbox_server_key']),
            'sandbox_client_key' => sanitize_text_field($_POST['sandbox_client_key']),
            'production_server_key' => sanitize_text_field($_POST['production_server_key']),
            'production_client_key' => sanitize_text_field($_POST['production_client_key'])
        );

        $updated = update_option('midtrans_settings', $midtrans_settings);

        if ($updated) {
            echo '<div class="notice notice-success is-dismissible"><p>Midtrans settings saved successfully!</p></div>';
        } else {
            echo '<div class="notice notice-info is-dismissible"><p>No changes made to Midtrans settings.</p></div>';
        }
    }

    /**
     * Handle currency settings update
     *
     * @since    3.1.1
     */
    private function handle_currency_settings_update() {
        global $wpdb;

        // Verify nonce
        if (!isset($_POST['currency_nonce']) || !wp_verify_nonce($_POST['currency_nonce'], 'currency_settings')) {
            wp_die('Security check failed');
        }

        $auto_update = isset($_POST['auto_update']) ? 1 : 0;
        $manual_rate = !empty($_POST['manual_rate']) ? floatval($_POST['manual_rate']) : null;

        // Update currency settings
        $updated = $wpdb->update(
            $wpdb->prefix . 'kukang_currency_new',
            array(
                'auto_update' => $auto_update,
                'manual_rate' => $manual_rate
            ),
            array('currency_code' => 'USD')
        );

        if ($updated !== false) {
            // If manual rate is set, also update the active USD rate
            if ($manual_rate && $manual_rate > 0) {
                $wpdb->update(
                    $wpdb->prefix . 'kukang_currency_new',
                    array('usd_rate' => $manual_rate),
                    array('currency_code' => 'USD')
                );
            }

            echo '<div class="notice notice-success is-dismissible"><p>Currency settings updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to update currency settings: ' . $wpdb->last_error . '</p></div>';
        }
    }

    /**
     * Render dolls management
     *
     * @since    3.1.0
     */
    public function render_dolls_management() {
        global $wpdb;

        // Handle form submissions
        if (isset($_POST['action'])) {
            $this->handle_doll_operations();
        }

        // Get all dolls
        $dolls = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kukang_dolls_new ORDER BY id ASC");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🐒 Kelola Boneka Kukang</h1>
            <a href="#" id="add-new-doll" class="page-title-action">Tambah Boneka Baru</a>
            <hr class="wp-header-end">

            <!-- Add/Edit Form -->
            <div id="doll-form" style="display: none; background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #c3c4c7; border-radius: 4px;">
                <h2 id="form-title">Tambah Boneka Baru</h2>
                <form method="post" id="doll-management-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="doll_id" id="doll_id" value="">
                    <?php wp_nonce_field('doll_management', 'doll_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="doll_name">Nama Boneka</label></th>
                            <td><input type="text" id="doll_name" name="doll_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="price_idr">Harga (IDR)</label></th>
                            <td>
                                <input type="number" id="price_idr" name="price_idr" class="regular-text" required min="0">
                                <p class="description">Harga dalam Rupiah Indonesia</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="price_usd">Harga (USD)</label></th>
                            <td>
                                <input type="number" id="price_usd" name="price_usd" class="regular-text" step="0.01" min="0">
                                <p class="description">Harga dalam US Dollar (akan dihitung otomatis jika kosong)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="weight_grams">Berat (gram)</label></th>
                            <td>
                                <input type="number" id="weight_grams" name="weight_grams" class="regular-text" min="0" value="200">
                                <p class="description">Berat untuk perhitungan ongkos kirim</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dimensions">Dimensi (cm)</label></th>
                            <td>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="number" id="length_cm" name="length_cm" placeholder="Panjang" style="width: 80px;" min="0" value="20">
                                    ×
                                    <input type="number" id="width_cm" name="width_cm" placeholder="Lebar" style="width: 80px;" min="0" value="15">
                                    ×
                                    <input type="number" id="height_cm" name="height_cm" placeholder="Tinggi" style="width: 80px;" min="0" value="10">
                                </div>
                                <p class="description">Panjang × Lebar × Tinggi dalam centimeter</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="description">Deskripsi</label></th>
                            <td>
                                <textarea id="description" name="description" class="large-text" rows="4" placeholder="Deskripsi boneka kukang..."></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="is_active">Status</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    Aktif (tampil di form donasi)
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button-primary" id="save-doll">Simpan Boneka</button>
                        <button type="button" class="button" id="cancel-form">Batal</button>
                    </p>
                </form>
            </div>

            <!-- Dolls Table -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <p>Kelola boneka kukang yang tersedia untuk adopsi</p>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 50px;">ID</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Harga IDR</th>
                        <th scope="col">Harga USD</th>
                        <th scope="col">Berat</th>
                        <th scope="col">Dimensi</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dolls): ?>
                        <?php foreach ($dolls as $doll): ?>
                        <tr>
                            <td><?php echo intval($doll->id); ?></td>
                            <td><strong><?php echo esc_html($doll->name); ?></strong></td>
                            <td>Rp <?php echo number_format($doll->price_idr, 0, ',', '.'); ?></td>
                            <td>$<?php echo number_format($doll->price_usd, 2); ?></td>
                            <td><?php echo intval($doll->weight_grams); ?>g</td>
                            <td><?php echo intval($doll->length_cm); ?> × <?php echo intval($doll->width_cm); ?> × <?php echo intval($doll->height_cm); ?> cm</td>
                            <td>
                                <?php if ($doll->is_active): ?>
                                    <span style="color: #00a32a;">● Aktif</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">● Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button edit-doll"
                                        data-id="<?php echo $doll->id; ?>"
                                        data-name="<?php echo esc_attr($doll->name); ?>"
                                        data-price-idr="<?php echo $doll->price_idr; ?>"
                                        data-price-usd="<?php echo $doll->price_usd; ?>"
                                        data-weight="<?php echo $doll->weight_grams; ?>"
                                        data-length="<?php echo $doll->length_cm; ?>"
                                        data-width="<?php echo $doll->width_cm; ?>"
                                        data-height="<?php echo $doll->height_cm; ?>"
                                        data-description="<?php echo esc_attr($doll->description); ?>"
                                        data-active="<?php echo $doll->is_active; ?>">
                                    Edit
                                </button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus boneka <?php echo esc_js($doll->name); ?>?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="doll_id" value="<?php echo $doll->id; ?>">
                                    <?php wp_nonce_field('doll_management', 'doll_nonce'); ?>
                                    <button type="submit" class="button" style="color: #d63638;">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <p>Belum ada boneka kukang yang tersedia.</p>
                                <p><button class="button-primary" onclick="document.getElementById('add-new-doll').click();">Tambah Boneka Pertama</button></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show add form
            $('#add-new-doll').click(function(e) {
                e.preventDefault();
                $('#form-title').text('Tambah Boneka Baru');
                $('#doll-management-form')[0].reset();
                $('input[name="action"]').val('add');
                $('#doll_id').val('');
                $('#is_active').prop('checked', true);
                $('#doll-form').slideDown();
            });

            // Show edit form
            $('.edit-doll').click(function() {
                var data = $(this).data();
                $('#form-title').text('Edit Boneka: ' + data.name);
                $('#doll_id').val(data.id);
                $('#doll_name').val(data.name);
                $('#price_idr').val(data.priceIdr);
                $('#price_usd').val(data.priceUsd);
                $('#weight_grams').val(data.weight);
                $('#length_cm').val(data.length);
                $('#width_cm').val(data.width);
                $('#height_cm').val(data.height);
                $('#description').val(data.description);
                $('#is_active').prop('checked', data.active == '1');
                $('input[name="action"]').val('edit');
                $('#doll-form').slideDown();

                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#doll-form').offset().top - 50
                }, 500);
            });

            // Cancel form
            $('#cancel-form').click(function() {
                $('#doll-form').slideUp();
            });

            // Auto-calculate USD price when IDR changes
            $('#price_idr').on('input', function() {
                var idr = parseFloat($(this).val());
                if (idr && !$('#price_usd').val()) {
                    // Use approximate rate if USD field is empty
                    var usd = (idr * 0.000067).toFixed(2);
                    $('#price_usd').val(usd);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render Biteship settings
     *
     * @since    3.1.0
     */
    public function render_biteship_settings() {
        // Handle form submissions
        if (isset($_POST['action']) && $_POST['action'] === 'update_biteship') {
            $this->handle_biteship_settings_update();
        }

        // Get current settings
        $biteship_settings = get_option('biteship_settings', array(
            'api_key' => '',
            'environment' => 'production',
            'origin_area_id' => 'IDNP9IDNC74IDND6753IDZ16610' // Tamansari, Bogor
        ));

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">🚛 Biteship Shipping Settings</h1>
            <hr class="wp-header-end">

            <div style="max-width: 800px;">
                <form method="post" action="">
                    <input type="hidden" name="action" value="update_biteship">
                    <?php wp_nonce_field('biteship_settings', 'biteship_nonce'); ?>

                    <!-- API Configuration -->
                    <div class="card" style="margin: 20px 0;">
                        <h2 class="title">🔑 API Configuration</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">API Key</th>
                                <td>
                                    <input type="text" name="api_key" value="<?php echo esc_attr($biteship_settings['api_key']); ?>" class="regular-text" placeholder="biteship_live.your_api_key_here">
                                    <p class="description">
                                        Your Biteship API key. Get it from <a href="https://app.biteship.com/api" target="_blank">Biteship Dashboard</a><br>
                                        <strong>Current:</strong> <?php echo $biteship_settings['api_key'] ? '***' . substr($biteship_settings['api_key'], -8) : 'Not set'; ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Environment</th>
                                <td>
                                    <select name="environment">
                                        <option value="production" <?php selected($biteship_settings['environment'], 'production'); ?>>Production</option>
                                        <option value="sandbox" <?php selected($biteship_settings['environment'], 'sandbox'); ?>>Sandbox</option>
                                    </select>
                                    <p class="description">Use Production for live transactions, Sandbox for testing.</p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Origin Settings -->
                    <div class="card">
                        <h2 class="title">📍 Origin Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Origin Area ID</th>
                                <td>
                                    <input type="text" name="origin_area_id" value="<?php echo esc_attr($biteship_settings['origin_area_id']); ?>" class="regular-text" readonly>
                                    <p class="description">
                                        <strong>Tamansari, Bogor</strong> - This is where packages will be shipped from.<br>
                                        Contact support if you need to change the origin location.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- API Testing -->
                    <div class="card">
                        <h2 class="title">🧪 API Testing</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Test Connection</th>
                                <td>
                                    <button type="button" id="test-biteship-api" class="button">Test API Connection</button>
                                    <button type="button" id="test-shipping-calculation" class="button">Test Shipping Calculation</button>
                                    <div id="biteship-test-result" style="margin-top: 10px;"></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button('Save Biteship Settings', 'primary', 'submit', false); ?>
                </form>

                <!-- Service Information -->
                <div class="card" style="margin-top: 30px;">
                    <h2 class="title">📦 Shipping Service Information</h2>
                    <div style="padding: 15px;">
                        <h4>Available Services:</h4>
                        <ul>
                            <li><strong>JNE REG:</strong> Regular service (2-3 days)</li>
                            <li><strong>JNE YES:</strong> Express service (1-2 days)</li>
                            <li><strong>JNE OKE:</strong> Economy service (3-5 days)</li>
                        </ul>
                        <p><strong>Note:</strong> Currently using JNE REG as default service for cost calculation.</p>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Test API connection
            $('#test-biteship-api').click(function() {
                var btn = $(this);
                var resultDiv = $('#biteship-test-result');

                btn.prop('disabled', true).text('Testing...');
                resultDiv.html('<div style="color: #666;">Testing Biteship API connection...</div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_biteship_api_connection',
                        nonce: '<?php echo wp_create_nonce('test_biteship_api'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: #00a32a;">✅ Biteship API connection successful!</div>');
                        } else {
                            resultDiv.html('<div style="color: #d63638;">❌ API connection failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: #d63638;">❌ AJAX request failed</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Test API Connection');
                    }
                });
            });

            // Test shipping calculation
            $('#test-shipping-calculation').click(function() {
                var btn = $(this);
                var resultDiv = $('#biteship-test-result');

                btn.prop('disabled', true).text('Testing...');
                resultDiv.html('<div style="color: #666;">Testing shipping calculation to Jakarta...</div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_shipping_calculation',
                        destination: 'jakarta',
                        weight: 1000,
                        nonce: '<?php echo wp_create_nonce('test_shipping_calc'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: #00a32a;">✅ Shipping calculation successful! Cost: Rp ' + response.data.cost.toLocaleString() + '</div>');
                        } else {
                            resultDiv.html('<div style="color: #d63638;">❌ Shipping calculation failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: #d63638;">❌ AJAX request failed</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Test Shipping Calculation');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render currency settings
     *
     * @since    3.1.0
     */
    public function render_currency_settings() {
        global $wpdb;

        // Handle form submissions
        if (isset($_POST['action']) && $_POST['action'] === 'update_currency') {
            $this->handle_currency_settings_update();
        }

        // Get current settings
        $currency_settings = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kukang_currency_new WHERE currency_code = 'USD'");
        if (!$currency_settings) {
            // Create default if not exists
            $wpdb->insert($wpdb->prefix . 'kukang_currency_new', array(
                'currency_code' => 'USD',
                'usd_rate' => 0.000067,
                'auto_update' => 1,
                'is_active' => 1
            ));
            $currency_settings = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kukang_currency_new WHERE currency_code = 'USD'");
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">💰 Currency & Exchange Rate Settings</h1>
            <hr class="wp-header-end">

            <div style="max-width: 800px;">
                <form method="post" action="">
                    <input type="hidden" name="action" value="update_currency">
                    <?php wp_nonce_field('currency_settings', 'currency_nonce'); ?>

                    <!-- Current Rate Display -->
                    <div class="card" style="margin: 20px 0;">
                        <h2 class="title">💱 Current Exchange Rate</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">USD Rate (1 IDR to USD)</th>
                                <td>
                                    <strong style="font-size: 18px; color: #2196f3;">
                                        $<?php echo number_format($currency_settings->usd_rate, 8); ?>
                                    </strong>
                                    <p class="description">
                                        Current rate: Rp 1 = $<?php echo number_format($currency_settings->usd_rate, 8); ?>
                                        <br>Example: Rp 150,000 = $<?php echo number_format(150000 * $currency_settings->usd_rate, 2); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Last Updated</th>
                                <td>
                                    <?php if ($currency_settings->last_api_update): ?>
                                        <span style="color: #00a32a;">
                                            <?php echo date('d/m/Y H:i:s', strtotime($currency_settings->last_api_update)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #666;">Never updated from API</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Rate Management -->
                    <div class="card">
                        <h2 class="title">⚙️ Rate Management</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Auto Update</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_update" value="1" <?php checked($currency_settings->auto_update, 1); ?>>
                                        Enable automatic rate updates from API
                                    </label>
                                    <p class="description">When enabled, exchange rates will be updated automatically using external API.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Manual Rate Override</th>
                                <td>
                                    <input type="number" name="manual_rate" value="<?php echo $currency_settings->manual_rate; ?>" step="0.00000001" min="0" class="regular-text">
                                    <p class="description">
                                        Set a fixed exchange rate (leave empty to use API rate).
                                        <br><strong>Format:</strong> 0.00006700 (for Rp 1 = $0.000067)
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- API Rate Testing -->
                    <div class="card">
                        <h2 class="title">🌐 API Rate Testing</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Test API Connection</th>
                                <td>
                                    <button type="button" id="test-exchange-api" class="button">Test API Connection</button>
                                    <button type="button" id="update-rate-now" class="button button-primary">Update Rate Now</button>
                                    <div id="api-test-result" style="margin-top: 10px;"></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button('Save Currency Settings', 'primary', 'submit', false); ?>
                </form>

                <!-- Exchange Rate History -->
                <?php
                $rate_history = $wpdb->get_results("
                    SELECT usd_rate, last_api_update, last_updated
                    FROM {$wpdb->prefix}kukang_currency_new
                    WHERE last_api_update IS NOT NULL
                    ORDER BY last_updated DESC
                    LIMIT 10
                ");

                if ($rate_history): ?>
                <div class="card" style="margin-top: 30px;">
                    <h2 class="title">📊 Recent Rate History</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>USD Rate</th>
                                <th>IDR Value</th>
                                <th>Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $prev_rate = null;
                            foreach ($rate_history as $history):
                                $change = $prev_rate ? (($history->usd_rate - $prev_rate) / $prev_rate) * 100 : 0;
                                $change_class = $change > 0 ? 'style="color: #00a32a;"' : ($change < 0 ? 'style="color: #d63638;"' : '');
                                $change_symbol = $change > 0 ? '↗' : ($change < 0 ? '↘' : '→');
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($history->last_updated)); ?></td>
                                <td>$<?php echo number_format($history->usd_rate, 8); ?></td>
                                <td>Rp <?php echo number_format(1 / $history->usd_rate, 0, ',', '.'); ?></td>
                                <td <?php echo $change_class; ?>>
                                    <?php if ($prev_rate): ?>
                                        <?php echo $change_symbol; ?> <?php echo number_format(abs($change), 2); ?>%
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            $prev_rate = $history->usd_rate;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Test API connection
            $('#test-exchange-api').click(function() {
                var btn = $(this);
                var resultDiv = $('#api-test-result');

                btn.prop('disabled', true).text('Testing...');
                resultDiv.html('<div style="color: #666;">Testing API connection...</div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_exchange_rate_api',
                        nonce: '<?php echo wp_create_nonce('test_exchange_api'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: #00a32a;">✅ API connection successful! Current rate: $' + response.data.rate + '</div>');
                        } else {
                            resultDiv.html('<div style="color: #d63638;">❌ API connection failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: #d63638;">❌ AJAX request failed</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Test API Connection');
                    }
                });
            });

            // Update rate now
            $('#update-rate-now').click(function() {
                var btn = $(this);
                var resultDiv = $('#api-test-result');

                if (!confirm('Update exchange rate from API now?')) {
                    return;
                }

                btn.prop('disabled', true).text('Updating...');
                resultDiv.html('<div style="color: #666;">Updating exchange rate...</div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_exchange_rate_now',
                        nonce: '<?php echo wp_create_nonce('update_exchange_rate'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: #00a32a;">✅ Exchange rate updated successfully!</div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            resultDiv.html('<div style="color: #d63638;">❌ Update failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: #d63638;">❌ AJAX request failed</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Update Rate Now');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render Midtrans settings
     *
     * @since    3.1.0
     */
    public function render_midtrans_settings() {
        // Handle form submissions
        if (isset($_POST['action']) && $_POST['action'] === 'update_midtrans') {
            $this->handle_midtrans_settings_update();
        }

        // Get current settings
        $midtrans_settings = get_option('midtrans_settings', array(
            'environment' => 'sandbox',
            'sandbox_server_key' => '',
            'sandbox_client_key' => '',
            'production_server_key' => '',
            'production_client_key' => ''
        ));

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">💳 Midtrans Payment Gateway Settings</h1>
            <hr class="wp-header-end">

            <div style="max-width: 800px;">
                <form method="post" action="">
                    <input type="hidden" name="action" value="update_midtrans">
                    <?php wp_nonce_field('midtrans_settings', 'midtrans_nonce'); ?>

                    <!-- Environment Selection -->
                    <div class="card" style="margin: 20px 0;">
                        <h2 class="title">🌍 Environment</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Current Environment</th>
                                <td>
                                    <select name="environment" id="midtrans-environment">
                                        <option value="sandbox" <?php selected($midtrans_settings['environment'], 'sandbox'); ?>>Sandbox (Testing)</option>
                                        <option value="production" <?php selected($midtrans_settings['environment'], 'production'); ?>>Production (Live)</option>
                                    </select>
                                    <p class="description">
                                        <strong>Sandbox:</strong> For testing payments with fake money<br>
                                        <strong>Production:</strong> For real transactions with real money
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Sandbox Settings -->
                    <div class="card sandbox-settings" style="<?php echo $midtrans_settings['environment'] === 'production' ? 'opacity: 0.5;' : ''; ?>">
                        <h2 class="title">🧪 Sandbox Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Sandbox Server Key</th>
                                <td>
                                    <input type="password" name="sandbox_server_key" value="<?php echo esc_attr($midtrans_settings['sandbox_server_key']); ?>" class="regular-text" placeholder="SB-Mid-server-...">
                                    <button type="button" class="button toggle-password" data-target="sandbox_server_key">Show</button>
                                    <p class="description">
                                        Server key for sandbox environment. Get it from <a href="https://dashboard.sandbox.midtrans.com/settings/config_info" target="_blank">Midtrans Sandbox Dashboard</a><br>
                                        <strong>Current:</strong> <?php echo $midtrans_settings['sandbox_server_key'] ? '***' . substr($midtrans_settings['sandbox_server_key'], -8) : 'Not set'; ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Sandbox Client Key</th>
                                <td>
                                    <input type="text" name="sandbox_client_key" value="<?php echo esc_attr($midtrans_settings['sandbox_client_key']); ?>" class="regular-text" placeholder="SB-Mid-client-...">
                                    <p class="description">
                                        Client key for sandbox environment (used in frontend JavaScript)<br>
                                        <strong>Current:</strong> <?php echo $midtrans_settings['sandbox_client_key'] ? '***' . substr($midtrans_settings['sandbox_client_key'], -8) : 'Not set'; ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Production Settings -->
                    <div class="card production-settings" style="<?php echo $midtrans_settings['environment'] === 'sandbox' ? 'opacity: 0.5;' : ''; ?>">
                        <h2 class="title">🏭 Production Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Production Server Key</th>
                                <td>
                                    <input type="password" name="production_server_key" value="<?php echo esc_attr($midtrans_settings['production_server_key']); ?>" class="regular-text" placeholder="Mid-server-...">
                                    <button type="button" class="button toggle-password" data-target="production_server_key">Show</button>
                                    <p class="description">
                                        Server key for production environment. Get it from <a href="https://dashboard.midtrans.com/settings/config_info" target="_blank">Midtrans Dashboard</a><br>
                                        <strong>Current:</strong> <?php echo $midtrans_settings['production_server_key'] ? '***' . substr($midtrans_settings['production_server_key'], -8) : 'Not set'; ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Production Client Key</th>
                                <td>
                                    <input type="text" name="production_client_key" value="<?php echo esc_attr($midtrans_settings['production_client_key']); ?>" class="regular-text" placeholder="Mid-client-...">
                                    <p class="description">
                                        Client key for production environment (used in frontend JavaScript)<br>
                                        <strong>Current:</strong> <?php echo $midtrans_settings['production_client_key'] ? '***' . substr($midtrans_settings['production_client_key'], -8) : 'Not set'; ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Gateway Testing -->
                    <div class="card">
                        <h2 class="title">🧪 Gateway Testing</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">Test Connection</th>
                                <td>
                                    <button type="button" id="test-midtrans-connection" class="button">Test Connection</button>
                                    <button type="button" id="test-midtrans-payment" class="button">Test Payment Creation</button>
                                    <div id="midtrans-test-result" style="margin-top: 10px;"></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button('Save Midtrans Settings', 'primary', 'submit', false); ?>
                </form>

                <!-- Payment Methods Information -->
                <div class="card" style="margin-top: 30px;">
                    <h2 class="title">💰 Supported Payment Methods</h2>
                    <div style="padding: 15px;">
                        <h4>Indonesian Rupiah (IDR):</h4>
                        <ul>
                            <li>Bank Transfer (Virtual Account): BCA, BNI, BRI, Mandiri, Permata</li>
                            <li>E-Wallets: GoPay, Dana, OVO, ShopeePay</li>
                            <li>Credit/Debit Cards: Visa, Mastercard, JCB</li>
                            <li>Over-the-Counter: Indomaret, Alfamart</li>
                        </ul>

                        <h4>US Dollar (USD):</h4>
                        <ul>
                            <li>Credit Cards only: Visa, Mastercard, JCB, American Express</li>
                        </ul>
                    </div>
                </div>

                <!-- Webhook Information -->
                <div class="card" style="margin-top: 20px; background: #fff3cd; border-left: 4px solid #ffc107;">
                    <h2 class="title">🔗 Webhook Configuration</h2>
                    <div style="padding: 15px;">
                        <p><strong>Webhook URL (for Midtrans dashboard):</strong></p>
                        <code style="background: #f1f1f1; padding: 8px; display: block; margin: 10px 0;">
                            <?php echo admin_url('admin-ajax.php?action=midtrans_notification'); ?>
                        </code>
                        <p class="description">
                            Copy this URL and add it to your Midtrans dashboard under Settings → Configuration → Payment Notification URL
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle password visibility
            $('.toggle-password').click(function() {
                var target = $(this).data('target');
                var input = $('input[name="' + target + '"]');
                var btn = $(this);

                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    btn.text('Hide');
                } else {
                    input.attr('type', 'password');
                    btn.text('Show');
                }
            });

            // Environment change handler
            $('#midtrans-environment').change(function() {
                var env = $(this).val();
                if (env === 'production') {
                    $('.sandbox-settings').css('opacity', '0.5');
                    $('.production-settings').css('opacity', '1');
                } else {
                    $('.sandbox-settings').css('opacity', '1');
                    $('.production-settings').css('opacity', '0.5');
                }
            });

            // Test connection
            $('#test-midtrans-connection').click(function() {
                var btn = $(this);
                var resultDiv = $('#midtrans-test-result');

                btn.prop('disabled', true).text('Testing...');
                resultDiv.html('<div style="color: #666;">Testing Midtrans connection...</div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_midtrans_connection',
                        nonce: '<?php echo wp_create_nonce('test_midtrans_connection'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: #00a32a;">✅ Midtrans connection successful!</div>');
                        } else {
                            resultDiv.html('<div style="color: #d63638;">❌ Connection failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: #d63638;">❌ AJAX request failed</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Test Connection');
                    }
                });
            });

            // Test payment creation
            $('#test-midtrans-payment').click(function() {
                var btn = $(this);
                var resultDiv = $('#midtrans-test-result');

                btn.prop('disabled', true).text('Testing...');
                resultDiv.html('<div style="color: #666;">Testing payment creation...</div>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_midtrans_payment',
                        nonce: '<?php echo wp_create_nonce('test_midtrans_payment'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html('<div style="color: #00a32a;">✅ Payment creation test successful! Snap token generated.</div>');
                        } else {
                            resultDiv.html('<div style="color: #d63638;">❌ Payment creation failed: ' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        resultDiv.html('<div style="color: #d63638;">❌ AJAX request failed</div>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Test Payment Creation');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Register settings
     *
     * @since    3.1.0
     */
    public function register_settings() {
        // Register Biteship settings
        register_setting('biteship_settings', 'biteship_settings');
        
        // Register Midtrans settings
        register_setting('midtrans_settings', 'midtrans_settings');
        
        // Register currency settings
        register_setting('currency_settings', 'currency_settings');
    }
    
    /**
     * Enqueue admin assets
     *
     * @since    3.1.0
     * @param    string    $hook    Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'donasi-midtrans') !== false || strpos($hook, 'currency-settings') !== false) {
            wp_enqueue_style('donasi-kukang-admin', YIARI_DONASI_KUKANG_URL . 'css/admin.css', array(), YIARI_DONASI_KUKANG_VERSION);
            wp_enqueue_script('donasi-kukang-admin', YIARI_DONASI_KUKANG_URL . 'js/admin.js', array('jquery'), YIARI_DONASI_KUKANG_VERSION, true);
            
            // Localize script
            wp_localize_script('donasi-kukang-admin', 'donasi_kukang_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('donasi_kukang_admin_nonce')
            ));
        }
    }
    
    /**
     * AJAX handler to test Biteship API
     *
     * @since    3.1.0
     */
    public function ajax_test_biteship_api() {
        // Implementation will be moved from the shipping manager
        // This is a placeholder to maintain backward compatibility
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }
    
    /**
     * AJAX handler to test Midtrans gateway
     *
     * @since    3.1.0
     */
    public function ajax_test_midtrans_gateway() {
        // Implementation will be moved from the payment manager
        // This is a placeholder to maintain backward compatibility
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }
    
    /**
     * AJAX handler to test exchange API
     *
     * @since    3.1.0
     */
    public function ajax_test_exchange_api() {
        // Implementation will be moved from the currency manager
        // This is a placeholder to maintain backward compatibility
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }
    
    /**
     * AJAX handler to clear cache
     *
     * @since    3.1.0
     */
    public function ajax_clear_cache() {
        // Implementation will be moved from the main file
        // This is a placeholder to maintain backward compatibility
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }
    
    /**
     * AJAX handler to refresh exchange rate
     *
     * @since    3.1.0
     */
    public function ajax_refresh_exchange_rate() {
        // Implementation will be moved from the currency manager
        // This is a placeholder to maintain backward compatibility
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }
    
    /**
     * AJAX handler to test exchange rate API
     *
     * @since    3.1.1
     */
    public function ajax_test_exchange_rate_api() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'test_exchange_api')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        try {
            $currency_manager = new YIARI_Currency_Manager();
            $rate = $currency_manager->get_usd_exchange_rate(true); // Force update from API

            wp_send_json_success(array(
                'rate' => number_format($rate, 8),
                'idr_equivalent' => number_format(1 / $rate, 0, ',', '.'),
                'message' => 'API connection successful',
                'timestamp' => current_time('mysql')
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'timestamp' => current_time('mysql')
            ));
        }
    }

    /**
     * AJAX handler to update exchange rate now
     *
     * @since    3.1.1
     */
    public function ajax_update_exchange_rate_now() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'update_exchange_rate')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        try {
            global $wpdb;
            $currency_manager = new YIARI_Currency_Manager();

            // Force update from API
            $new_rate = $currency_manager->get_usd_exchange_rate(true);

            // Update the main currency table
            $updated = $wpdb->update(
                $wpdb->prefix . 'kukang_currency_new',
                array(
                    'usd_rate' => $new_rate,
                    'api_rate' => $new_rate,
                    'last_api_update' => current_time('mysql')
                ),
                array('currency_code' => 'USD')
            );

            if ($updated !== false) {
                wp_send_json_success(array(
                    'rate' => number_format($new_rate, 8),
                    'idr_equivalent' => number_format(1 / $new_rate, 0, ',', '.'),
                    'message' => 'Exchange rate updated successfully',
                    'timestamp' => current_time('mysql')
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to update database'));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'timestamp' => current_time('mysql')
            ));
        }
    }

    /**
     * AJAX handler to test Biteship API connection
     *
     * @since    3.1.1
     */
    public function ajax_test_biteship_api_connection() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'test_biteship_api')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        try {
            $shipping_manager = new YIARI_Shipping_Manager();

            // Test basic areas API call
            $areas_result = $shipping_manager->get_biteship_areas('jakarta');

            if ($areas_result && isset($areas_result['success']) && $areas_result['success']) {
                wp_send_json_success(array(
                    'message' => 'Biteship API connection successful',
                    'areas_found' => count($areas_result['areas']),
                    'api_status' => 'Working'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Biteship API connection failed - Invalid response',
                    'details' => 'Unable to fetch areas from API'
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Biteship API error: ' . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler to test shipping calculation
     *
     * @since    3.1.1
     */
    public function ajax_test_shipping_calculation() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'test_shipping_calc')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        try {
            $shipping_manager = new YIARI_Shipping_Manager();

            // Test with Jakarta area ID (common destination)
            $jakarta_area_id = 'IDNP6IDNC153IDND2274IDZ10110'; // Jakarta Pusat
            $weight = intval($_POST['weight']) ?: 1000;

            $shipping_result = $shipping_manager->get_biteship_shipping_rate($jakarta_area_id, $weight);

            if ($shipping_result && isset($shipping_result['pricing']['value'])) {
                wp_send_json_success(array(
                    'message' => 'Shipping calculation successful',
                    'cost' => intval($shipping_result['pricing']['value']),
                    'service' => $shipping_result['service'] ?? 'REG',
                    'etd' => $shipping_result['pricing']['duration'] ?? '2-3 days'
                ));
            } else if (isset($shipping_result['error']) && $shipping_result['error'] === 'insufficient_balance') {
                wp_send_json_error(array(
                    'message' => 'API connected but insufficient balance in Biteship account'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Shipping calculation failed - No rates returned'
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Shipping calculation error: ' . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler to test Midtrans connection
     *
     * @since    3.1.1
     */
    public function ajax_test_midtrans_connection() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'test_midtrans_connection')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        try {
            // Get Midtrans settings directly without requiring the Payment Manager
            $settings = get_option('midtrans_settings', array(
                'environment' => 'sandbox',
                'sandbox_server_key' => '',
                'production_server_key' => ''
            ));

            $environment = $settings['environment'] ?? 'sandbox';
            $server_key = ($environment === 'production') ? $settings['production_server_key'] : $settings['sandbox_server_key'];

            if (empty($server_key)) {
                wp_send_json_error(array('message' => 'Server key not configured for ' . $environment . ' environment'));
                return;
            }

            // Test API connection
            $api_url = ($environment === 'production') ?
                'https://api.midtrans.com/v2/charge' :
                'https://api.sandbox.midtrans.com/v2/charge';

            $response = wp_remote_get($api_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($server_key . ':'),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                )
            ));

            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'Connection failed: ' . $response->get_error_message()));
                return;
            }

            $http_code = wp_remote_retrieve_response_code($response);

            // 400 is expected for empty request - means auth is working
            if ($http_code === 400) {
                wp_send_json_success(array(
                    'message' => 'Midtrans connection successful',
                    'environment' => $environment,
                    'api_status' => 'Authentication working'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Unexpected response from Midtrans API',
                    'http_code' => $http_code
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Midtrans connection error: ' . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler to test Midtrans payment creation
     *
     * @since    3.1.1
     */
    public function ajax_test_midtrans_payment() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'test_midtrans_payment')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        try {
            // Check if Midtrans library is available first
            $midtrans_available = false;
            $possible_paths = array(
                YIARI_DONASI_KUKANG_PATH . 'midtrans-php-master/Midtrans.php',
                YIARI_DONASI_KUKANG_PATH . 'vendor/midtrans/midtrans-php/Midtrans.php'
            );

            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    $midtrans_available = true;
                    break;
                }
            }

            if (!$midtrans_available && (!class_exists('Midtrans\Config') || !class_exists('Midtrans\Snap'))) {
                wp_send_json_error(array(
                    'message' => 'Midtrans PHP library not installed. Please install the library to test payment creation.',
                    'instructions' => 'Download from https://github.com/Midtrans/midtrans-php and place in plugin directory'
                ));
                return;
            }

            // Get settings
            $settings = get_option('midtrans_settings', array());
            $environment = $settings['environment'] ?? 'sandbox';
            $server_key = ($environment === 'production') ? $settings['production_server_key'] : $settings['sandbox_server_key'];

            if (empty($server_key)) {
                wp_send_json_error(array('message' => 'Server key not configured for ' . $environment . ' environment'));
                return;
            }

            // Try to use Payment Manager if available
            try {
                $payment_manager = new YIARI_Payment_Manager();

                // Create test payment data
                $test_data = array(
                    'order_id' => 'TEST-' . date('YmdHis') . '-' . wp_rand(1000, 9999),
                    'gross_amount' => 150000,
                    'customer_name' => 'Test Customer',
                    'email' => 'test@example.com',
                    'phone' => '081234567890',
                    'address' => 'Test Address',
                    'city_name' => 'Jakarta',
                    'postal_code' => '10110',
                    'shipping_cost' => 15000,
                    'currency' => 'IDR',
                    'regina_qty' => 1
                );

                $payment_result = $payment_manager->process_donation_payment($test_data);

                if ($payment_result['success'] && !empty($payment_result['snap_token'])) {
                    wp_send_json_success(array(
                        'message' => 'Payment creation successful',
                        'snap_token' => substr($payment_result['snap_token'], 0, 20) . '...',
                        'order_id' => $test_data['order_id']
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => 'Payment creation failed: ' . ($payment_result['error'] ?? 'Unknown error')
                    ));
                }

            } catch (Exception $e) {
                wp_send_json_error(array(
                    'message' => 'Payment creation test failed: ' . $e->getMessage()
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Payment creation error: ' . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler to save manual exchange rate
     *
     * @since    3.1.0
     */
    public function ajax_save_manual_exchange_rate() {
        // Implementation will be moved from the currency manager
        // This is a placeholder to maintain backward compatibility
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }

    /**
     * AJAX handler to check transaction status from Midtrans
     *
     * @since    3.1.0
     */
    public function ajax_check_transaction_status() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'admin_nonce')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $order_id = sanitize_text_field($_POST['order_id']);

        try {
            // Load Midtrans library and check status
            $payment_manager = new YIARI_Payment_Manager();
            $payment_manager->load_midtrans_library();

            // Get current settings
            $settings = $payment_manager->get_midtrans_settings();
            $environment = $settings['environment'] ?? 'sandbox';

            // Configure Midtrans
            $payment_manager->configure_midtrans_environment($environment);

            // Get transaction status from Midtrans
            $status = \Midtrans\Transaction::status($order_id);

            // Update database with new status
            global $wpdb;
            $table_name = $wpdb->prefix . 'kukang_transactions_new';

            $update_data = array(
                'transaction_status' => $status->transaction_status,
                'fraud_status' => isset($status->fraud_status) ? $status->fraud_status : null,
                'payment_type' => isset($status->payment_type) ? $status->payment_type : null,
            );

            // Add settlement time if transaction is settled
            if ($status->transaction_status == 'settlement' || ($status->transaction_status == 'capture' && $status->fraud_status == 'accept')) {
                $update_data['settlement_time'] = current_time('mysql');
            }

            // Add bank info if available
            if (isset($status->bank)) {
                $update_data['bank'] = $status->bank;
            }

            $updated = $wpdb->update(
                $table_name,
                $update_data,
                array('order_id' => $order_id),
                array('%s', '%s', '%s'),
                array('%s')
            );

            if ($updated !== false) {
                wp_send_json_success(array(
                    'status' => $status->transaction_status,
                    'message' => 'Transaction status updated successfully',
                    'details' => $status
                ));
            } else {
                wp_send_json_error(array('message' => 'Failed to update database: ' . $wpdb->last_error));
            }

        } catch (Exception $e) {
            error_log('Check status error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Failed to check status: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX handler to get transaction details for modal
     *
     * @since    3.1.0
     */
    public function ajax_get_transaction_details() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'admin_nonce')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $transaction_id = intval($_POST['transaction_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'kukang_transactions_new';

        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $transaction_id
        ), ARRAY_A);

        if (!$transaction) {
            wp_send_json_error(array('message' => 'Transaction not found'));
            return;
        }

        // Generate detailed HTML for modal
        ob_start();
        $this->render_transaction_details_modal($transaction);
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Render transaction details for modal
     *
     * @since    3.1.0
     * @param    array    $transaction    Transaction data
     */
    private function render_transaction_details_modal($transaction) {
        ?>
        <div class="transaction-details">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="detail-section">
                    <h3 style="margin: 0 0 10px 0; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 5px;">📋 Order Information</h3>
                    <p><strong>Order ID:</strong> <?php echo esc_html($transaction['order_id']); ?></p>
                    <p><strong>Transaction ID:</strong> <?php echo esc_html($transaction['transaction_id'] ?: '-'); ?></p>
                    <p><strong>Created:</strong> <?php echo date('d/m/Y H:i:s', strtotime($transaction['created_at'])); ?></p>
                    <?php if ($transaction['settlement_time']): ?>
                    <p><strong>Settled:</strong> <?php echo date('d/m/Y H:i:s', strtotime($transaction['settlement_time'])); ?></p>
                    <?php endif; ?>
                    <p><strong>Language:</strong> <?php echo strtoupper($transaction['language'] ?: 'ID'); ?></p>
                    <p><strong>Currency:</strong> <?php echo esc_html($transaction['currency'] ?: 'IDR'); ?></p>
                </div>

                <div class="detail-section">
                    <h3 style="margin: 0 0 10px 0; color: #27ae60; border-bottom: 2px solid #27ae60; padding-bottom: 5px;">👤 Customer Information</h3>
                    <p><strong>Name:</strong> <?php echo esc_html($transaction['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html($transaction['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo esc_html($transaction['phone'] ?: '-'); ?></p>
                    <p><strong>Address:</strong> <?php echo esc_html($transaction['address'] ?: '-'); ?></p>
                    <p><strong>City:</strong> <?php echo esc_html($transaction['city'] ?: '-'); ?></p>
                    <p><strong>Province:</strong> <?php echo esc_html($transaction['province'] ?: '-'); ?></p>
                    <p><strong>Postal Code:</strong> <?php echo esc_html($transaction['postal_code'] ?: '-'); ?></p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="detail-section">
                    <h3 style="margin: 0 0 10px 0; color: #8e44ad; border-bottom: 2px solid #8e44ad; padding-bottom: 5px;">🐒 Items Ordered</h3>
                    <?php
                    $dolls = array();
                    if ($transaction['regina_qty'] > 0) $dolls[] = 'Regina: ' . intval($transaction['regina_qty']) . ' pcs';
                    if ($transaction['jagger_qty'] > 0) $dolls[] = 'Jagger: ' . intval($transaction['jagger_qty']) . ' pcs';
                    if ($transaction['butros_qty'] > 0) $dolls[] = 'Butros: ' . intval($transaction['butros_qty']) . ' pcs';
                    if ($transaction['eid_qty'] > 0) $dolls[] = 'Eid: ' . intval($transaction['eid_qty']) . ' pcs';
                    if ($transaction['anoda_qty'] > 0) $dolls[] = 'Anoda: ' . intval($transaction['anoda_qty']) . ' pcs';

                    if (count($dolls) > 0) {
                        foreach ($dolls as $doll) {
                            echo '<p>• ' . esc_html($doll) . '</p>';
                        }
                    } else {
                        echo '<p style="color: #999; font-style: italic;">No items data</p>';
                    }
                    ?>
                    <p><strong>Total Items:</strong> <?php echo intval($transaction['total_items']); ?> pcs</p>
                    <p><strong>Total Weight:</strong> <?php echo intval($transaction['total_weight']); ?> grams</p>
                </div>

                <div class="detail-section">
                    <h3 style="margin: 0 0 10px 0; color: #e67e22; border-bottom: 2px solid #e67e22; padding-bottom: 5px;">💰 Payment Information</h3>
                    <p><strong>Subtotal:</strong> Rp <?php echo number_format($transaction['subtotal'], 0, ',', '.'); ?></p>
                    <p><strong>Shipping Cost:</strong> Rp <?php echo number_format($transaction['shipping_cost'], 0, ',', '.'); ?></p>
                    <p><strong>Total Amount:</strong> <span style="color: #27ae60; font-size: 18px; font-weight: bold;">Rp <?php echo number_format($transaction['gross_amount'], 0, ',', '.'); ?></span></p>

                    <?php if ($transaction['currency'] === 'USD' && $transaction['usd_amount']): ?>
                    <p><strong>USD Amount:</strong> <span style="color: #2980b9; font-size: 16px; font-weight: bold;">$<?php echo number_format($transaction['usd_amount'], 2); ?></span></p>
                    <p><strong>Exchange Rate:</strong> <?php echo number_format($transaction['exchange_rate'], 6); ?></p>
                    <?php endif; ?>

                    <p><strong>Payment Status:</strong>
                        <?php
                        $status = $transaction['transaction_status'];
                        $status_color = '';
                        switch (strtolower($status)) {
                            case 'settlement':
                            case 'capture':
                                $status_color = '#27ae60';
                                break;
                            case 'pending':
                                $status_color = '#f39c12';
                                break;
                            case 'deny':
                            case 'failure':
                                $status_color = '#e74c3c';
                                break;
                            default:
                                $status_color = '#95a5a6';
                        }
                        ?>
                        <span style="color: <?php echo $status_color; ?>; font-weight: bold;"><?php echo strtoupper($status); ?></span>
                    </p>

                    <?php if ($transaction['payment_type']): ?>
                    <p><strong>Payment Method:</strong> <?php echo strtoupper($transaction['payment_type']); ?></p>
                    <?php endif; ?>

                    <?php if ($transaction['bank']): ?>
                    <p><strong>Bank:</strong> <?php echo strtoupper($transaction['bank']); ?></p>
                    <?php endif; ?>

                    <?php if ($transaction['va_number']): ?>
                    <p><strong>VA Number:</strong> <?php echo esc_html($transaction['va_number']); ?></p>
                    <?php endif; ?>

                    <?php if ($transaction['fraud_status']): ?>
                    <p><strong>Fraud Status:</strong> <?php echo strtoupper($transaction['fraud_status']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-section">
                <h3 style="margin: 0 0 10px 0; color: #34495e; border-bottom: 2px solid #34495e; padding-bottom: 5px;">🚚 Shipping & Order Status</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <p><strong>Order Status:</strong>
                            <?php
                            $order_status = $transaction['order_status'] ?? 'processing';
                            $order_color = '';
                            switch (strtolower($order_status)) {
                                case 'processing':
                                    $order_color = '#f39c12';
                                    break;
                                case 'packed':
                                    $order_color = '#3498db';
                                    break;
                                case 'shipped':
                                    $order_color = '#9b59b6';
                                    break;
                                case 'delivered':
                                    $order_color = '#27ae60';
                                    break;
                                default:
                                    $order_color = '#95a5a6';
                            }
                            ?>
                            <span style="color: <?php echo $order_color; ?>; font-weight: bold;"><?php echo strtoupper($order_status); ?></span>
                        </p>

                        <?php if ($transaction['courier']): ?>
                        <p><strong>Courier:</strong> <?php echo strtoupper($transaction['courier']); ?></p>
                        <?php endif; ?>

                        <?php if ($transaction['service']): ?>
                        <p><strong>Service:</strong> <?php echo esc_html($transaction['service']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($transaction['tracking_number']): ?>
                        <p><strong>Tracking Number:</strong> <span style="color: #2980b9; font-weight: bold;"><?php echo esc_html($transaction['tracking_number']); ?></span></p>
                        <?php endif; ?>

                        <?php if ($transaction['notes']): ?>
                        <p><strong>Notes:</strong> <?php echo esc_html($transaction['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($transaction['snap_token']): ?>
            <div class="detail-section" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="margin: 0 0 10px 0; color: #6c757d;">🔧 Technical Information</h4>
                <p><strong>Snap Token:</strong> <code style="background: #e9ecef; padding: 2px 4px; border-radius: 3px; font-size: 12px;"><?php echo esc_html(substr($transaction['snap_token'], 0, 50)) . '...'; ?></code></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler to update order status
     *
     * @since    3.1.0
     */
    public function ajax_update_order_status() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'admin_nonce')) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $transaction_id = intval($_POST['transaction_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $tracking_number = sanitize_text_field($_POST['tracking_number']);
        $notes = sanitize_textarea_field($_POST['notes']);

        // Validate status
        $valid_statuses = array('pending', 'processing', 'delivering', 'delivered');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(array('message' => 'Invalid status'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'kukang_transactions_new';

        // Get current transaction
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $transaction_id
        ));

        if (!$transaction) {
            wp_send_json_error(array('message' => 'Transaction not found'));
            return;
        }

        // Prepare update data
        $update_data = array(
            'order_status' => $new_status,
            'updated_at' => current_time('mysql')
        );

        // Add tracking number if provided
        if (!empty($tracking_number)) {
            $update_data['tracking_number'] = $tracking_number;
        }

        // Add notes if provided
        if (!empty($notes)) {
            $current_notes = $transaction->notes ?: '';
            $status_note = '[' . date('Y-m-d H:i:s') . '] Status changed to ' . $new_status . ': ' . $notes;
            $update_data['notes'] = !empty($current_notes) ? $current_notes . '\n\n' . $status_note : $status_note;
        }

        // Update database
        $updated = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $transaction_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($updated !== false) {
            error_log("✅ Order status updated: Transaction ID $transaction_id -> $new_status");

            // Trigger email notification for status updates
            if ($new_status === 'delivering' && !empty($tracking_number)) {
                // Get updated transaction data
                $updated_transaction = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $transaction_id
                ));

                if ($updated_transaction) {
                    error_log("📧 Triggering email for delivering status with tracking: " . $tracking_number);
                    do_action('yiari_order_status_updated', $updated_transaction, $new_status, $tracking_number);
                }
            }

            wp_send_json_success(array(
                'message' => 'Order status updated successfully',
                'new_status' => $new_status,
                'tracking_number' => $tracking_number
            ));
        } else {
            error_log("❌ Failed to update order status: " . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Failed to update order status: ' . $wpdb->last_error));
        }
    }

    /**
     * AJAX handler to download invoice PDF
     *
     * @since    3.1.0
     */
    public function ajax_download_invoice() {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'admin_nonce')) {
            wp_die('Security verification failed');
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $transaction_id = intval($_GET['transaction_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'kukang_transactions_new';

        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $transaction_id
        ));

        if (!$transaction) {
            wp_die('Transaction not found');
        }

        // Generate PDF invoice
        $this->generate_invoice_pdf($transaction);
    }

    /**
     * AJAX handler to download packing slip PDF
     *
     * @since    3.1.0
     */
    public function ajax_download_packing_slip() {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'admin_nonce')) {
            wp_die('Security verification failed');
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $transaction_id = intval($_GET['transaction_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'kukang_transactions_new';

        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $transaction_id
        ));

        if (!$transaction) {
            wp_die('Transaction not found');
        }

        // Generate PDF packing slip
        $this->generate_packing_slip_pdf($transaction);
    }

    /**
     * Generate invoice PDF
     *
     * @since    3.1.0
     * @param    object    $transaction    Transaction data
     */
    private function generate_invoice_pdf($transaction) {
        // Check if we can use mPDF or similar
        require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem.php';

        // For now, generate HTML-based PDF using browser print
        $this->generate_html_invoice($transaction);
    }

    /**
     * Generate packing slip PDF
     *
     * @since    3.1.0
     * @param    object    $transaction    Transaction data
     */
    private function generate_packing_slip_pdf($transaction) {
        // For now, generate HTML-based PDF using browser print
        $this->generate_html_packing_slip($transaction);
    }

    /**
     * Generate HTML invoice for printing/PDF
     *
     * @since    3.1.0
     * @param    object    $transaction    Transaction data
     */
    private function generate_html_invoice($transaction) {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper headers for PDF download
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="invoice-' . $transaction->order_id . '.html"');

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Invoice - <?php echo esc_html($transaction->order_id); ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .company-info { text-align: center; margin-bottom: 20px; }
                .invoice-details { display: flex; justify-content: space-between; margin-bottom: 30px; }
                .invoice-details > div { width: 48%; }
                .customer-info, .invoice-info { background: #f9f9f9; padding: 15px; border-radius: 5px; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .items-table th { background: #f1f1f1; font-weight: bold; }
                .totals { float: right; width: 300px; margin-top: 20px; }
                .totals table { width: 100%; border-collapse: collapse; }
                .totals td { padding: 5px 10px; border-bottom: 1px solid #eee; }
                .total-final { font-weight: bold; font-size: 14px; background: #f1f1f1; }
                .footer { margin-top: 40px; text-align: center; color: #666; font-size: 10px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>INVOICE</h1>
                <div class="company-info">
                    <h2>YIARI Foundation</h2>
                    <p>Slow Loris Conservation Organization</p>
                    <p>Email: info@yiari.or.id | Website: www.yiari.or.id</p>
                </div>
            </div>

            <div class="invoice-details">
                <div class="customer-info">
                    <h3>Bill To:</h3>
                    <p><strong><?php echo esc_html($transaction->customer_name); ?></strong></p>
                    <p><?php echo esc_html($transaction->email); ?></p>
                    <p><?php echo esc_html($transaction->phone ?: '-'); ?></p>
                    <p><?php echo esc_html($transaction->address ?: '-'); ?></p>
                    <p><?php echo esc_html($transaction->city ?: '-'); ?>, <?php echo esc_html($transaction->postal_code ?: '-'); ?></p>
                </div>

                <div class="invoice-info">
                    <h3>Invoice Details:</h3>
                    <p><strong>Invoice #:</strong> <?php echo esc_html($transaction->order_id); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($transaction->created_at)); ?></p>
                    <p><strong>Payment Status:</strong> <?php echo strtoupper($transaction->transaction_status); ?></p>
                    <?php if ($transaction->settlement_time): ?>
                    <p><strong>Paid Date:</strong> <?php echo date('d/m/Y H:i', strtotime($transaction->settlement_time)); ?></p>
                    <?php endif; ?>
                    <p><strong>Currency:</strong> <?php echo esc_html($transaction->currency ?: 'IDR'); ?></p>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get doll prices safely
                    global $wpdb;
                    $dolls_table = $wpdb->prefix . 'kukang_dolls_new';

                    try {
                        $dolls = $wpdb->get_results($wpdb->prepare("SELECT name, price_idr, price_usd FROM {$dolls_table} WHERE is_active = %d", 1));

                        $total_items = 0;
                        $use_usd = ($transaction->currency === 'USD');

                        if ($dolls) {
                            foreach ($dolls as $doll) {
                                $doll_name = strtolower($doll->name);
                                $qty_field = $doll_name . '_qty';
                                $qty = isset($transaction->$qty_field) ? intval($transaction->$qty_field) : 0;

                                if ($qty > 0) {
                                    $unit_price = $use_usd ? floatval($doll->price_usd) : intval($doll->price_idr);
                                    $total = $qty * $unit_price;
                                    $total_items += $qty;

                                    echo '<tr>';
                                    echo '<td>' . esc_html($doll->name) . ' Slow Loris Adoption Doll</td>';
                                    echo '<td style="text-align: center;">' . $qty . '</td>';
                                    echo '<td style="text-align: right;">' . ($use_usd ? '$' . number_format($unit_price, 2) : 'Rp ' . number_format($unit_price, 0, ',', '.')) . '</td>';
                                    echo '<td style="text-align: right;">' . ($use_usd ? '$' . number_format($total, 2) : 'Rp ' . number_format($total, 0, ',', '.')) . '</td>';
                                    echo '</tr>';
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Invoice generation error: ' . $e->getMessage());
                        echo '<tr><td colspan="4">Error loading items</td></tr>';
                    }

                    // Shipping
                    if ($transaction->shipping_cost > 0) {
                        $shipping_amount = $use_usd ? ($transaction->shipping_cost * ($transaction->exchange_rate ?? 0.000067)) : $transaction->shipping_cost;
                        echo '<tr>';
                        echo '<td>Shipping (JNE REG)</td>';
                        echo '<td style="text-align: center;">1</td>';
                        echo '<td style="text-align: right;">' . ($use_usd ? '$' . number_format($shipping_amount, 2) : 'Rp ' . number_format($transaction->shipping_cost, 0, ',', '.')) . '</td>';
                        echo '<td style="text-align: right;">' . ($use_usd ? '$' . number_format($shipping_amount, 2) : 'Rp ' . number_format($transaction->shipping_cost, 0, ',', '.')) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div class="totals">
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td style="text-align: right;">
                            <?php
                            if ($use_usd && isset($transaction->usd_amount)) {
                                echo '$' . number_format($transaction->usd_amount - ($transaction->shipping_cost * ($transaction->exchange_rate ?? 0.000067)), 2);
                            } else {
                                echo 'Rp ' . number_format($transaction->subtotal, 0, ',', '.');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Shipping:</td>
                        <td style="text-align: right;">
                            <?php
                            if ($use_usd) {
                                echo '$' . number_format($transaction->shipping_cost * ($transaction->exchange_rate ?? 0.000067), 2);
                            } else {
                                echo 'Rp ' . number_format($transaction->shipping_cost, 0, ',', '.');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr class="total-final">
                        <td><strong>Total:</strong></td>
                        <td style="text-align: right;">
                            <strong>
                            <?php
                            if ($use_usd && isset($transaction->usd_amount)) {
                                echo '$' . number_format($transaction->usd_amount, 2);
                            } else {
                                echo 'Rp ' . number_format($transaction->gross_amount, 0, ',', '.');
                            }
                            ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="clear: both;"></div>

            <div class="footer">
                <p>Thank you for supporting slow loris conservation!</p>
                <p>This invoice was generated on <?php echo date('d/m/Y H:i:s'); ?></p>
                <p><em>Note: This adoption is symbolic and contributes to slow loris conservation efforts.</em></p>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Generate HTML packing slip for printing/PDF
     *
     * @since    3.1.0
     * @param    object    $transaction    Transaction data
     */
    private function generate_html_packing_slip($transaction) {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper headers
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="packing-slip-' . $transaction->order_id . '.html"');

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Packing Slip - <?php echo esc_html($transaction->order_id); ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                .company-info { text-align: center; margin-bottom: 20px; }
                .order-details { display: flex; justify-content: space-between; margin-bottom: 30px; }
                .order-details > div { width: 48%; }
                .shipping-info, .order-info { background: #f9f9f9; padding: 15px; border-radius: 5px; }
                .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                .items-table th { background: #f1f1f1; font-weight: bold; }
                .notes { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-top: 20px; }
                .footer { margin-top: 40px; text-align: center; color: #666; font-size: 10px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>PACKING SLIP</h1>
                <div class="company-info">
                    <h2>YIARI Foundation</h2>
                    <p>Slow Loris Conservation Organization</p>
                </div>
            </div>

            <div class="order-details">
                <div class="shipping-info">
                    <h3>🚚 Ship To:</h3>
                    <p><strong><?php echo esc_html($transaction->customer_name); ?></strong></p>
                    <p><?php echo esc_html($transaction->phone ?: '-'); ?></p>
                    <p><?php echo esc_html($transaction->address ?: '-'); ?></p>
                    <p><?php echo esc_html($transaction->city ?: '-'); ?>, <?php echo esc_html($transaction->postal_code ?: '-'); ?></p>
                    <?php if ($transaction->tracking_number): ?>
                    <p><strong>📋 Tracking #:</strong> <?php echo esc_html($transaction->tracking_number); ?></p>
                    <?php endif; ?>
                </div>

                <div class="order-info">
                    <h3>📦 Order Details:</h3>
                    <p><strong>Order #:</strong> <?php echo esc_html($transaction->order_id); ?></p>
                    <p><strong>Order Date:</strong> <?php echo date('d/m/Y', strtotime($transaction->created_at)); ?></p>
                    <p><strong>Status:</strong> <?php echo strtoupper($transaction->order_status ?: 'processing'); ?></p>
                    <p><strong>Weight:</strong> <?php echo intval($transaction->total_weight ?: 0); ?> grams</p>
                    <p><strong>Items:</strong> <?php echo intval($transaction->total_items ?: 0); ?> pcs</p>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th style="text-align: center;">Quantity</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get doll data safely
                    global $wpdb;
                    $dolls_table = $wpdb->prefix . 'kukang_dolls_new';

                    try {
                        $dolls = $wpdb->get_results($wpdb->prepare("SELECT name, description FROM {$dolls_table} WHERE is_active = %d", 1));

                        if ($dolls) {
                            foreach ($dolls as $doll) {
                                $doll_name = strtolower($doll->name);
                                $qty_field = $doll_name . '_qty';
                                $qty = isset($transaction->$qty_field) ? intval($transaction->$qty_field) : 0;

                                if ($qty > 0) {
                                    echo '<tr>';
                                    echo '<td><strong>' . esc_html($doll->name) . ' Slow Loris Doll</strong><br><small>' . esc_html($doll->description ?: 'Adoption doll for conservation support') . '</small></td>';
                                    echo '<td style="text-align: center; font-size: 16px; font-weight: bold;">' . $qty . '</td>';
                                    echo '<td style="border-left: 3px solid #ddd; background: #f9f9f9;"></td>';
                                    echo '</tr>';
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Packing slip generation error: ' . $e->getMessage());
                        echo '<tr><td colspan="3">Error loading items</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <?php if ($transaction->notes): ?>
            <div class="notes">
                <h4>📝 Special Instructions:</h4>
                <p><?php echo nl2br(esc_html($transaction->notes)); ?></p>
            </div>
            <?php endif; ?>

            <div class="footer">
                <p>Please verify all items before shipping. Thank you for supporting slow loris conservation!</p>
                <p>Packing slip generated on <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
?>