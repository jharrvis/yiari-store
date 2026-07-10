<?php

/**
 * Email Manager for YIARI Donasi Kukang Plugin
 *
 * Handles email notifications for transactions and order updates
 */
class YIARI_Email_Manager {

    /**
     * Initialize email manager
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Hook into transaction creation and status updates
        add_action('yiari_transaction_created', array($this, 'send_transaction_confirmation'), 10, 2);
        add_action('yiari_order_status_updated', array($this, 'send_status_update_notification'), 10, 3);
    }

    /**
     * Send transaction confirmation email with payment link
     *
     * @since    3.1.0
     * @param    array    $transaction_data    Transaction data
     * @param    string   $snap_token         Midtrans Snap token
     */
    public function send_transaction_confirmation($transaction_data, $snap_token) {
        error_log("=== SENDING TRANSACTION CONFIRMATION EMAIL ===");
        error_log("Order ID: " . $transaction_data['order_id']);
        error_log("Email: " . $transaction_data['email']);

        $to = $transaction_data['email'];
        $subject = 'Order Confirmation - ' . $transaction_data['order_id'] . ' - YIARI Foundation';

        // Get Midtrans settings for payment URL
        $payment_manager = new YIARI_Payment_Manager();
        $settings = $payment_manager->get_midtrans_settings();
        $environment = $settings['environment'] ?? 'sandbox';

        // Create payment URL
        $payment_url = $this->get_payment_url($snap_token, $environment);

        // Generate email content
        $message = $this->generate_transaction_confirmation_email($transaction_data, $payment_url);

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: YIARI Foundation <noreply@yiari.or.id>',
            'Reply-To: info@yiari.or.id'
        );

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            error_log("✅ Transaction confirmation email sent successfully to: $to");
        } else {
            error_log("❌ Failed to send transaction confirmation email to: $to");
        }

        return $sent;
    }

    /**
     * Send order status update notification
     *
     * @since    3.1.0
     * @param    object   $transaction        Transaction object
     * @param    string   $new_status         New order status
     * @param    string   $tracking_number    Tracking number (optional)
     */
    public function send_status_update_notification($transaction, $new_status, $tracking_number = '') {
        error_log("=== SENDING STATUS UPDATE EMAIL ===");
        error_log("Order ID: " . $transaction->order_id);
        error_log("New Status: $new_status");
        error_log("Tracking: $tracking_number");

        $to = $transaction->email;
        $subject = 'Order Update - ' . $transaction->order_id . ' - YIARI Foundation';

        // Generate email content based on status
        switch ($new_status) {
            case 'processing':
                $message = $this->generate_processing_email($transaction);
                break;
            case 'delivering':
                $message = $this->generate_delivering_email($transaction, $tracking_number);
                break;
            case 'delivered':
                $message = $this->generate_delivered_email($transaction);
                break;
            default:
                $message = $this->generate_general_status_email($transaction, $new_status);
        }

        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: YIARI Foundation <noreply@yiari.or.id>',
            'Reply-To: info@yiari.or.id'
        );

        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            error_log("✅ Status update email sent successfully to: $to");
        } else {
            error_log("❌ Failed to send status update email to: $to");
        }

        return $sent;
    }

    /**
     * Get payment URL for Snap token
     *
     * @since    3.1.0
     * @param    string   $snap_token     Snap token
     * @param    string   $environment    Environment (sandbox/production)
     * @return   string                   Payment URL
     */
    private function get_payment_url($snap_token, $environment = 'sandbox') {
        if ($environment === 'production') {
            return "https://app.midtrans.com/snap/v2/vtweb/$snap_token";
        } else {
            return "https://app.sandbox.midtrans.com/snap/v2/vtweb/$snap_token";
        }
    }

    /**
     * Generate transaction confirmation email
     *
     * @since    3.1.0
     * @param    array    $transaction_data    Transaction data
     * @param    string   $payment_url         Payment URL
     * @return   string                        Email HTML content
     */
    private function generate_transaction_confirmation_email($transaction_data, $payment_url) {
        global $wpdb;

        // Get doll details
        $dolls_table = $wpdb->prefix . 'kukang_dolls_new';
        $dolls = $wpdb->get_results("SELECT name, price_idr, price_usd FROM $dolls_table WHERE is_active = 1");

        $use_usd = ($transaction_data['currency'] ?? 'IDR') === 'USD';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .order-summary { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .payment-button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .payment-button:hover { background: #218838; }
                .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .items-table th { background: #f1f1f1; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .important { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🐒 YIARI Foundation</h1>
                    <h2>Order Confirmation</h2>
                    <p>Thank you for supporting slow loris conservation!</p>
                </div>

                <div class="content">
                    <div class="important">
                        <strong>⏰ Important:</strong> Please complete your payment within 24 hours to secure your order.
                    </div>

                    <div class="order-summary">
                        <h3>📋 Order Details</h3>
                        <p><strong>Order Number:</strong> <?php echo esc_html($transaction_data['order_id']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                        <p><strong>Customer:</strong> <?php echo esc_html($transaction_data['customer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo esc_html($transaction_data['email']); ?></p>
                    </div>

                    <div class="order-summary">
                        <h3>🐒 Items Ordered</h3>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $subtotal = 0;
                                foreach ($dolls as $doll) {
                                    $doll_name = strtolower($doll->name);
                                    $qty_key = $doll_name . '_qty';
                                    $qty = isset($transaction_data[$qty_key]) ? intval($transaction_data[$qty_key]) : 0;

                                    if ($qty > 0) {
                                        $unit_price = $use_usd ? $doll->price_usd : $doll->price_idr;
                                        $total = $qty * $unit_price;
                                        $subtotal += $total;

                                        echo '<tr>';
                                        echo '<td>' . esc_html($doll->name) . ' Slow Loris Doll</td>';
                                        echo '<td>' . $qty . '</td>';
                                        echo '<td>' . ($use_usd ? '$' . number_format($unit_price, 2) : 'Rp ' . number_format($unit_price, 0, ',', '.')) . '</td>';
                                        echo '<td>' . ($use_usd ? '$' . number_format($total, 2) : 'Rp ' . number_format($total, 0, ',', '.')) . '</td>';
                                        echo '</tr>';
                                    }
                                }

                                // Shipping
                                $shipping_cost = intval($transaction_data['shipping_cost'] ?? 0);
                                if ($shipping_cost > 0) {
                                    echo '<tr>';
                                    echo '<td>Shipping (JNE REG)</td>';
                                    echo '<td>1</td>';
                                    echo '<td>' . ($use_usd ? '$' . number_format($shipping_cost * 0.000067, 2) : 'Rp ' . number_format($shipping_cost, 0, ',', '.')) . '</td>';
                                    echo '<td>' . ($use_usd ? '$' . number_format($shipping_cost * 0.000067, 2) : 'Rp ' . number_format($shipping_cost, 0, ',', '.')) . '</td>';
                                    echo '</tr>';
                                }

                                // Total
                                $total_amount = isset($transaction_data['gross_amount']) ? intval($transaction_data['gross_amount']) : ($subtotal + $shipping_cost);
                                echo '<tr style="font-weight: bold; background: #f1f1f1;">';
                                echo '<td colspan="3">TOTAL</td>';
                                echo '<td>' . ($use_usd ? '$' . number_format($total_amount * 0.000067, 2) : 'Rp ' . number_format($total_amount, 0, ',', '.')) . '</td>';
                                echo '</tr>';
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="text-align: center;">
                        <h3>💳 Complete Your Payment</h3>
                        <p>Click the button below to proceed with secure payment via Midtrans:</p>
                        <a href="<?php echo esc_url($payment_url); ?>" class="payment-button">PAY NOW</a>
                        <p><small>Secure payment powered by Midtrans</small></p>
                    </div>

                    <?php if (isset($transaction_data['address'])): ?>
                    <div class="order-summary">
                        <h3>📦 Shipping Address</h3>
                        <p><?php echo esc_html($transaction_data['customer_name']); ?></p>
                        <p><?php echo esc_html($transaction_data['address']); ?></p>
                        <p><?php echo esc_html($transaction_data['city_name'] ?? ''); ?>, <?php echo esc_html($transaction_data['postal_code'] ?? ''); ?></p>
                        <p><?php echo esc_html($transaction_data['phone'] ?? ''); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="footer">
                    <p>🌱 This symbolic adoption helps protect slow lorises in the wild.</p>
                    <p>YIARI Foundation - Slow Loris Conservation</p>
                    <p>Email: info@yiari.or.id | Website: www.yiari.or.id</p>
                    <p><em>If you have any questions, please contact our support team.</em></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate processing status email
     *
     * @since    3.1.0
     * @param    object   $transaction    Transaction object
     * @return   string                   Email HTML content
     */
    private function generate_processing_email($transaction) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .status-update { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🐒 YIARI Foundation</h1>
                    <h2>Payment Confirmed!</h2>
                </div>

                <div class="content">
                    <div class="status-update">
                        <h3>✅ Order Status: Processing</h3>
                        <p><strong>Order #:</strong> <?php echo esc_html($transaction->order_id); ?></p>
                        <p>Thank you! Your payment has been successfully received.</p>
                        <p>We are now preparing your slow loris adoption package for shipment.</p>
                    </div>

                    <p>Dear <?php echo esc_html($transaction->customer_name); ?>,</p>

                    <p>Great news! Your payment for the slow loris adoption has been confirmed and your order is now being processed.</p>

                    <p><strong>What happens next?</strong></p>
                    <ul>
                        <li>📦 We will carefully prepare your adoption package</li>
                        <li>🚚 Your order will be shipped via JNE REG</li>
                        <li>📧 You'll receive tracking information once shipped</li>
                        <li>🏠 Delivery typically takes 2-3 business days</li>
                    </ul>

                    <p>Your support helps us continue our vital work protecting slow lorises in their natural habitat. Thank you for making a difference!</p>
                </div>

                <div class="footer">
                    <p>🌱 Together we're protecting slow lorises for future generations.</p>
                    <p>YIARI Foundation - Slow Loris Conservation</p>
                    <p>Email: info@yiari.or.id | Website: www.yiari.or.id</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate delivering status email with tracking
     *
     * @since    3.1.0
     * @param    object   $transaction        Transaction object
     * @param    string   $tracking_number    Tracking number
     * @return   string                       Email HTML content
     */
    private function generate_delivering_email($transaction, $tracking_number) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #9b59b6; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .tracking-info { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center; border: 2px solid #9b59b6; }
                .tracking-number { font-size: 18px; font-weight: bold; color: #9b59b6; background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .button { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🐒 YIARI Foundation</h1>
                    <h2>Your Order is On The Way! 🚚</h2>
                </div>

                <div class="content">
                    <div class="tracking-info">
                        <h3>📋 Tracking Information</h3>
                        <p><strong>Order #:</strong> <?php echo esc_html($transaction->order_id); ?></p>
                        <p><strong>Status:</strong> In Transit</p>
                        <div class="tracking-number">
                            Tracking Number: <?php echo esc_html($tracking_number); ?>
                        </div>
                        <a href="https://www.jne.co.id/id/tracking/trace" target="_blank" class="button">Track Your Package</a>
                    </div>

                    <p>Dear <?php echo esc_html($transaction->customer_name); ?>,</p>

                    <p>Exciting news! Your slow loris adoption package has been shipped and is now on its way to you.</p>

                    <p><strong>Shipping Details:</strong></p>
                    <ul>
                        <li>🚚 <strong>Courier:</strong> JNE Regular Service</li>
                        <li>📋 <strong>Tracking Number:</strong> <?php echo esc_html($tracking_number); ?></li>
                        <li>📦 <strong>Delivery Address:</strong><br>
                            <?php echo esc_html($transaction->customer_name); ?><br>
                            <?php echo esc_html($transaction->address ?: ''); ?><br>
                            <?php echo esc_html($transaction->city ?: ''); ?>, <?php echo esc_html($transaction->postal_code ?: ''); ?>
                        </li>
                        <li>⏰ <strong>Estimated Delivery:</strong> 2-3 business days</li>
                    </ul>

                    <p><strong>How to track your package:</strong></p>
                    <ol>
                        <li>Visit <a href="https://www.jne.co.id/id/tracking/trace" target="_blank">JNE Tracking Website</a></li>
                        <li>Enter your tracking number: <strong><?php echo esc_html($tracking_number); ?></strong></li>
                        <li>View real-time delivery updates</li>
                    </ol>

                    <p>Your adoption package includes your symbolic slow loris doll and information about the conservation work your support enables.</p>

                    <p>Thank you for your commitment to slow loris conservation! 🌱</p>
                </div>

                <div class="footer">
                    <p>🌱 Your support is making a real difference for slow lorises in the wild.</p>
                    <p>YIARI Foundation - Slow Loris Conservation</p>
                    <p>Email: info@yiari.or.id | Website: www.yiari.or.id</p>
                    <p><em>Questions about your delivery? Contact us anytime.</em></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate delivered status email
     *
     * @since    3.1.0
     * @param    object   $transaction    Transaction object
     * @return   string                   Email HTML content
     */
    private function generate_delivered_email($transaction) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #27ae60; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .delivery-confirmation { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center; border: 2px solid #27ae60; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🐒 YIARI Foundation</h1>
                    <h2>Package Delivered! 🎉</h2>
                </div>

                <div class="content">
                    <div class="delivery-confirmation">
                        <h3>✅ Delivery Confirmed</h3>
                        <p><strong>Order #:</strong> <?php echo esc_html($transaction->order_id); ?></p>
                        <p>Your slow loris adoption package has been successfully delivered!</p>
                    </div>

                    <p>Dear <?php echo esc_html($transaction->customer_name); ?>,</p>

                    <p>Congratulations! Your slow loris adoption package has been delivered to your address.</p>

                    <p>We hope you love your symbolic slow loris doll and the information about the important conservation work you're supporting.</p>

                    <p><strong>Your Impact:</strong></p>
                    <ul>
                        <li>🌳 Supporting habitat protection</li>
                        <li>🔬 Funding important research</li>
                        <li>📚 Supporting education programs</li>
                        <li>🏥 Helping rescue and rehabilitation efforts</li>
                    </ul>

                    <p>Thank you for being a slow loris conservation hero! Your support directly contributes to protecting these amazing primates for future generations.</p>

                    <p>Stay connected with us for updates on the conservation work you're supporting!</p>
                </div>

                <div class="footer">
                    <p>🌱 Because of supporters like you, slow lorises have a brighter future.</p>
                    <p>YIARI Foundation - Slow Loris Conservation</p>
                    <p>Email: info@yiari.or.id | Website: www.yiari.or.id</p>
                    <p><em>Follow our work on social media to see your impact in action!</em></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate general status update email
     *
     * @since    3.1.0
     * @param    object   $transaction    Transaction object
     * @param    string   $status         New status
     * @return   string                   Email HTML content
     */
    private function generate_general_status_email($transaction, $status) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .status-update { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🐒 YIARI Foundation</h1>
                    <h2>Order Status Update</h2>
                </div>

                <div class="content">
                    <div class="status-update">
                        <h3>📋 Status Update</h3>
                        <p><strong>Order #:</strong> <?php echo esc_html($transaction->order_id); ?></p>
                        <p><strong>New Status:</strong> <?php echo esc_html(ucfirst($status)); ?></p>
                    </div>

                    <p>Dear <?php echo esc_html($transaction->customer_name); ?>,</p>

                    <p>This is to inform you that your order status has been updated.</p>

                    <p>If you have any questions about your order, please don't hesitate to contact us.</p>

                    <p>Thank you for supporting slow loris conservation!</p>
                </div>

                <div class="footer">
                    <p>🌱 Your support helps protect slow lorises in the wild.</p>
                    <p>YIARI Foundation - Slow Loris Conservation</p>
                    <p>Email: info@yiari.or.id | Website: www.yiari.or.id</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
?>