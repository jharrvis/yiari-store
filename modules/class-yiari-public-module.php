<?php

/**
 * Public Module for YIARI Donasi Kukang Plugin
 * 
 * Handles public-facing functionality and front-end features
 */
class YIARI_Public_Module {
    
    /**
     * Initialize public module
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Enqueue public assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // AJAX handlers for public functionality
        add_action('wp_ajax_nopriv_get_biteship_cities', array($this, 'ajax_get_biteship_cities'));
        add_action('wp_ajax_get_biteship_cities', array($this, 'ajax_get_biteship_cities'));
        add_action('wp_ajax_nopriv_calculate_shipping_cost', array($this, 'ajax_calculate_shipping_cost'));
        add_action('wp_ajax_calculate_shipping_cost', array($this, 'ajax_calculate_shipping_cost'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'ajax_process_donation'));
        add_action('wp_ajax_process_donation', array($this, 'ajax_process_donation'));
        add_action('wp_ajax_nopriv_process_donation_en', array($this, 'ajax_process_donation_en'));
        add_action('wp_ajax_process_donation_en', array($this, 'ajax_process_donation_en'));
        add_action('wp_ajax_nopriv_get_current_exchange_rate', array($this, 'ajax_get_current_exchange_rate'));
        add_action('wp_ajax_get_current_exchange_rate', array($this, 'ajax_get_current_exchange_rate'));
        
        // Shortcodes
        add_shortcode('cek_donasi', array($this, 'render_donation_tracking_form'));
        
        // Load Midtrans Snap.js in header
        add_action('wp_head', array($this, 'load_midtrans_snap_js'));
    }
    
    /**
     * Enqueue public assets
     *
     * @since    3.1.0
     */
    public function enqueue_public_assets() {
        // Only load on pages with donation forms
        if (has_shortcode(get_post()->post_content, 'donasi_kukang') ||
            has_shortcode(get_post()->post_content, 'donasi_kukang_en') ||
            has_shortcode(get_post()->post_content, 'cek_donasi')) {

            // CSS
            wp_enqueue_style('donasi-kukang-public', YIARI_DONASI_KUKANG_URL . 'css/donasi-kukang-public.css', array(), YIARI_DONASI_KUKANG_VERSION);

            // JavaScript
            wp_enqueue_script('jquery');
            wp_enqueue_script('donasi-kukang-public', YIARI_DONASI_KUKANG_URL . 'js/donasi-kukang-public.js', array('jquery'), YIARI_DONASI_KUKANG_VERSION, true);

            // Localize script for AJAX
            wp_localize_script('donasi-kukang-public', 'yiari_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yiari_ajax_nonce')
            ));
        }

        // Always localize for shortcodes
        wp_localize_script('donasi-kukang-public', 'donasi_kukang_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('donasi_kukang_nonce')
        ));
    }
    
    /**
     * Load Midtrans Snap.js
     *
     * @since    3.1.0
     */
    public function load_midtrans_snap_js() {
        $payment_manager = new YIARI_Payment_Manager();
        $settings = $payment_manager->get_midtrans_settings();
        $environment = $settings['environment'] ?? 'sandbox';
        $client_key = ($environment === 'production') ? $settings['production_client_key'] : $settings['sandbox_client_key'];
        
        if (!empty($client_key)) {
            $snap_url = ($environment === 'production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
            echo '<script src="' . $snap_url . '" data-client-key="' . esc_attr($client_key) . '"></script>';
        }
    }
    
    /**
     * Render donation tracking form
     *
     * @since    3.1.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Form HTML
     */
    public function render_donation_tracking_form($atts) {
        // Implementation will be moved from the main file
        // This is a placeholder to maintain backward compatibility
        ob_start();
        ?>
        <div id="donor-tracking-system" style="max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
            <h3 style="text-align: center; color: #2c5530; margin-bottom: 20px;">🔍 Cek Status Donasi Anda</h3>
            <p style="text-align: center; margin-bottom: 20px; color: #666;">Masukkan Order ID Anda untuk melihat status donasi dan pengiriman</p>

            <form id="tracking-form" style="text-align: center;">
                <input type="text" id="order-id-input" placeholder="Contoh: KUKANG-20250923-ABC123"
                       style="width: 100%; max-width: 400px; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; margin-bottom: 15px;"
                       required>
                <br>
                <button type="submit" style="background: #2c5530; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;">Cek Status</button>
            </form>

            <div id="tracking-result" style="margin-top: 20px; display: none;"></div>
        </div>

        <script>
        document.getElementById('tracking-form').addEventListener('submit', function(e) {
            e.preventDefault();

            var orderId = document.getElementById('order-id-input').value.trim();
            var resultDiv = document.getElementById('tracking-result');

            if (!orderId) {
                alert('Silakan masukkan Order ID');
                return;
            }

            // Show loading
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><span style="color: #2c5530;">🔄 Mencari data donasi...</span></div>';

            // AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url("admin-ajax.php"); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        resultDiv.innerHTML = xhr.responseText;
                    } else {
                        resultDiv.innerHTML = '<div style="background: #ffe6e6; padding: 15px; border-radius: 5px; color: #d32f2f; text-align: center;">❌ Terjadi kesalahan. Silakan coba lagi.</div>';
                    }
                }
            };

            xhr.send('action=check_donor_order_status&order_id=' + encodeURIComponent(orderId));
        });
        </script>

        <style>
        #donor-tracking-system button:hover {
            background: #1e3d24 !important;
        }
        #donor-tracking-system input:focus {
            border-color: #2c5530 !important;
            outline: none;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler to get Biteship cities
     *
     * @since    3.1.0
     */
    public function ajax_get_biteship_cities() {
        // Implementation will be moved from the shipping manager
        // This is a placeholder to maintain backward compatibility
        $shipping_manager = new YIARI_Shipping_Manager();
        $shipping_manager->ajax_get_biteship_cities();
    }
    
    /**
     * AJAX handler to calculate shipping cost
     *
     * @since    3.1.0
     */
    public function ajax_calculate_shipping_cost() {
        // Implementation will be moved from the shipping manager
        // This is a placeholder to maintain backward compatibility
        $shipping_manager = new YIARI_Shipping_Manager();
        $shipping_manager->ajax_calculate_shipping_cost();
    }
    
    /**
     * AJAX handler to process donation
     *
     * @since    3.1.0
     */
    public function ajax_process_donation() {
        // Implementation will be moved from the form manager
        // This is a placeholder to maintain backward compatibility
        $form_manager = new YIARI_Form_Manager();
        $form_manager->ajax_process_donation();
    }
    
    /**
     * AJAX handler to process English donation
     *
     * @since    3.1.0
     */
    public function ajax_process_donation_en() {
        // Implementation will be moved from the form manager
        // This is a placeholder to maintain backward compatibility
        $form_manager = new YIARI_Form_Manager();
        $form_manager->ajax_process_donation_en();
    }
    
    /**
     * AJAX handler to get current exchange rate
     *
     * @since    3.1.0
     */
    public function ajax_get_current_exchange_rate() {
        // Implementation will be moved from the currency manager
        // This is a placeholder to maintain backward compatibility
        $currency_manager = new YIARI_Currency_Manager();
        $currency_manager->ajax_get_current_exchange_rate();
    }
}
?>