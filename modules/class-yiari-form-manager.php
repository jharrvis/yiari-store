<?php

/**
 * Form Manager for YIARI Donasi Kukang Plugin
 * 
 * Handles donation form rendering and processing
 */
class YIARI_Form_Manager {
    
    /**
     * Initialize form manager
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Register shortcodes
        add_shortcode('donasi_kukang', array($this, 'render_indonesian_form'));
        add_shortcode('donasi_kukang_en', array($this, 'render_english_form'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_form_assets'));
        
        // Load Midtrans Snap.js
        add_action('wp_head', array($this, 'load_midtrans_snap_js'));
    }
    
    /**
     * Enqueue form assets
     *
     * @since    3.1.0
     */
    public function enqueue_form_assets() {
        // CSS
        wp_enqueue_style('donasi-kukang-public', YIARI_DONASI_KUKANG_URL . 'css/donasi-kukang-public.css', array(), YIARI_DONASI_KUKANG_VERSION);

        // JavaScript - Temporarily disabled to avoid conflicts with embedded JS
        // wp_enqueue_script('donasi-kukang-public', YIARI_DONASI_KUKANG_URL . 'js/donasi-kukang-public.js', array('jquery'), YIARI_DONASI_KUKANG_VERSION, true);

        // Localize for AJAX calls directly to jQuery
        wp_localize_script('jquery', 'yiari_ajax', array(
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
     * Render Indonesian donation form
     *
     * @since    3.1.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Form HTML
     */
    public function render_indonesian_form($atts) {
        // Implementation will be moved from the main file
        // This is a placeholder to maintain backward compatibility
        return $this->get_indonesian_form_html();
    }
    
    /**
     * Render English donation form
     *
     * @since    3.1.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Form HTML
     */
    public function render_english_form($atts) {
        // Implementation will be moved from the main file
        // This is a placeholder to maintain backward compatibility
        return $this->get_english_form_html();
    }
    
    /**
     * Get Indonesian form HTML
     *
     * @since    3.1.0
     * @return   string    Form HTML
     */
    private function get_indonesian_form_html() {
        // Ensure legacy tables still exist while the new schema becomes primary.
        $database_manager = new YIARI_Database_Manager();
        $database_manager->create_tables();

        $product_repository = new YIARI_Product_Repository();
        $dolls = $product_repository->get_active_products();

        ob_start();
        ?>
        <div class="donasi-kukang-container" style="
            width: 100%;
            max-width: 100%;
            padding: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: #333;
            line-height: 1.6;
        ">
            <div class="form-header" style="text-align: center; margin-bottom: 40px;">
                <h2 style="color: white; margin: 0 0 10px 0; font-size: 28px; font-weight: 300; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Informasi Donatur</h2>
                <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Lengkapi data berikut untuk proses adopsi boneka kukang</p>
            </div>

            <form id="donasiKukangForm" class="donasi-form" style="max-width: 800px; margin: 0 auto;">
                <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #333;">Nama Lengkap</label>
                        <input type="text" name="customer_name" required style="
                            width: 100%;
                            padding: 12px 16px;
                            border: 2px solid #e0e0e0;
                            border-radius: 8px;
                            font-size: 15px;
                            background: #fff;
                            box-sizing: border-box;
                            transition: border-color 0.3s ease;
                        " onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                    </div>

                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #333;">Email</label>
                        <input type="email" name="email" required style="
                            width: 100%;
                            padding: 12px 16px;
                            border: 2px solid #e0e0e0;
                            border-radius: 8px;
                            font-size: 15px;
                            background: #fff;
                            box-sizing: border-box;
                            transition: border-color 0.3s ease;
                        " onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #333;">No. Telepon</label>
                        <input type="tel" name="phone" required style="
                            width: 100%;
                            padding: 12px 16px;
                            border: 2px solid #e0e0e0;
                            border-radius: 8px;
                            font-size: 15px;
                            background: #fff;
                            box-sizing: border-box;
                            transition: border-color 0.3s ease;
                        " onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                    </div>

                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #333;">Kode Pos</label>
                        <input type="text" name="postal_code" required style="
                            width: 100%;
                            padding: 12px 16px;
                            border: 2px solid #e0e0e0;
                            border-radius: 8px;
                            font-size: 15px;
                            background: #fff;
                            box-sizing: border-box;
                            transition: border-color 0.3s ease;
                        " onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #333;">Alamat Lengkap</label>
                    <textarea name="address" required style="
                        width: 100%;
                        padding: 12px 16px;
                        border: 2px solid #e0e0e0;
                        border-radius: 8px;
                        font-size: 15px;
                        background: #fff;
                        box-sizing: border-box;
                        transition: border-color 0.3s ease;
                        min-height: 80px;
                        resize: vertical;
                        font-family: inherit;
                    " onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'"></textarea>
                </div>

                <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px; position: relative;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: #333;">Kota/Kabupaten</label>
                        <input type="text"
                       id="citySearch"
                       placeholder="Ketik minimal 4 karakter untuk mencari kota..."
                       autocomplete="off"
                       style="
                    width: 100%;
                    padding: 12px 16px;
                    border: 2px solid #e0e0e0;
                    border-radius: 8px;
                    font-size: 15px;
                    background: #fff;
                    box-sizing: border-box;
                    transition: border-color 0.3s ease;
                " onfocus="this.style.borderColor='#3498db'; showSearchResults()" onblur="setTimeout(() => hideSearchResults(), 300)" oninput="debouncedSearchCities(this.value)" onkeydown="handleSearchKeydown(event)">

                <div id="citySearchResults" style="
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: white;
                    border: 1px solid #e0e0e0;
                    border-top: none;
                    border-radius: 0 0 8px 8px;
                    max-height: 200px;
                    overflow-y: auto;
                    z-index: 1000;
                    display: none;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                "></div>

                <input type="hidden" name="city_id" value="" required>
                <input type="hidden" name="city_name" value="">
                <input type="hidden" name="province" value="">
                    </div>

                    <input type="hidden" name="courier" value="jne">
                    <input type="hidden" name="courier_service" value="REG">
                    <input type="hidden" name="shipping_cost" value="0">
                </div>

                <div class="kukang-dolls-section" style="margin-bottom: 30px;">
                    <h3 style="text-align: center; color: black; font-size: 24px; font-weight: 300; margin-bottom: 20px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Pilih Boneka Kukang</h3>

                    <div class="dolls-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px; max-width: 700px; margin: 0 auto;">
                        <?php foreach ($dolls as $doll): ?>
                        <div class="doll-card" style="
                            text-align: center;
                            padding: 20px;
                            border: 2px solid #f0f0f0;
                            border-radius: 12px;
                            background: #fff;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.borderColor='#3498db'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.borderColor='#f0f0f0'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="font-weight: 600; margin-bottom: 8px; font-size: 16px; color: #2c3e50;"><?php echo esc_html($doll->name); ?></div>
                            <div style="font-size: 14px; color: #7f8c8d; margin-bottom: 15px;">Rp <?php echo number_format(isset($doll->price_idr) ? $doll->price_idr : $doll->price, 0, ',', '.'); ?></div>

                            <div class="qty-control" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <button type="button" onclick="changeQty('<?php echo strtolower($doll->name); ?>', -1)" style="
                                    width: 32px; height: 32px;
                                    border-radius: 6px;
                                    background: #ecf0f1;
                                    border: none;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 16px;
                                    color: #2c3e50;
                                    transition: background-color 0.2s ease;
                                " onmouseover="this.style.background='#d0d7dd'" onmouseout="this.style.background='#ecf0f1'">−</button>

                                <span class="qty-display" style="
                                    background: #3498db;
                                    color: white;
                                    min-width: 36px;
                                    height: 32px;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    border-radius: 6px;
                                    font-weight: 600;
                                    font-size: 15px;
                                ">0</span>

                                <button type="button" onclick="changeQty('<?php echo strtolower($doll->name); ?>', 1)" style="
                                    width: 32px; height: 32px;
                                    border-radius: 6px;
                                    background: #ecf0f1;
                                    border: none;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 16px;
                                    color: #2c3e50;
                                    transition: background-color 0.2s ease;
                                " onmouseover="this.style.background='#d0d7dd'" onmouseout="this.style.background='#ecf0f1'">+</button>

                                <input type="hidden" name="<?php echo strtolower($doll->name); ?>_qty" value="0" class="qty-input">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="order-flow-section" style="background: rgba(255,255,255,0.95); padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 16px 0; color: #2c3e50;">Pilihan Pemesanan</h3>
                    <label style="display:block; margin-bottom:10px;">
                        <input type="radio" name="order_flow_type" value="self_only" checked>
                        Beli produk untuk diri sendiri
                    </label>
                    <label style="display:block; margin-bottom:10px;">
                        <input type="radio" name="order_flow_type" value="self_plus_donation">
                        Beli 1 produk untuk diri sendiri dan 1 produk untuk didonasikan melalui YIARI
                    </label>
                </div>

                <div class="motivation-section" style="background: rgba(255,255,255,0.95); padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 16px 0; color: #2c3e50;">Motivasi Donasi</h3>
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Apa alasan utama Anda berdonasi?</label>
                    <select name="donation_motivation_code" id="donation_motivation_code" style="width:100%; padding:12px 16px; border:2px solid #e0e0e0; border-radius:8px; font-size:15px; background:#fff; box-sizing:border-box;">
                        <option value="">Pilih salah satu</option>
                        <option value="ingin_mendukung_misi_yiari">Ingin mendukung misi YIARI</option>
                        <option value="tertarik_pada_produk_edukasi">Tertarik pada produk atau buku edukasi</option>
                        <option value="ingin_berdonasi_produk">Ingin berdonasi produk melalui YIARI</option>
                        <option value="rekomendasi_teman_atau_komunitas">Rekomendasi teman atau komunitas</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                    <div id="donation_motivation_other_wrap" style="display:none; margin-top:12px;">
                        <input type="text" name="donation_motivation_other" id="donation_motivation_other" placeholder="Silakan isi alasan Anda" style="width:100%; padding:12px 16px; border:2px solid #e0e0e0; border-radius:8px; font-size:15px; background:#fff; box-sizing:border-box;">
                    </div>
                </div>

                <div class="summary-section" style="
                    background: rgba(255,255,255,0.95);
                    padding: 30px;
                    border-radius: 12px;
                    margin-bottom: 30px;
                    border: 1px solid rgba(255,255,255,0.3);
                    backdrop-filter: blur(10px);
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span style="font-size: 16px; color: #495057;">Subtotal Produk:</span>
                        <span id="subtotalAmount" style="font-size: 16px; font-weight: 600; color: #2c3e50;">Rp 0</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span style="font-size: 16px; color: #495057;">Ongkos Kirim (JNE REG):</span>
                        <div style="text-align: right;">
                            <span id="shippingAmount" style="font-size: 16px; font-weight: 600; color: #2c3e50;"><em>Pilih kota dulu</em></span>
                            <div id="ongkirStatus" style="font-size: 12px; color: #6c757d; margin-top: 2px;"></div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span style="font-size: 14px; color: #6c757d;">Total Berat:</span>
                        <span id="totalWeight" style="font-size: 14px; color: #6c757d;">0 gram</span>
                    </div>
                    <hr style="border: none; border-top: 2px solid #dee2e6; margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 20px; font-weight: 600; color: #2c3e50;">Total Pembayaran:</span>
                        <span id="totalAmount" style="font-size: 24px; font-weight: 700; color: #e74c3c;">Rp 0</span>
                    </div>
                </div>

                <div class="submit-section" style="text-align: center;">
                    <button type="submit" style="
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border: none;
                        padding: 16px 48px;
                        border-radius: 8px;
                        font-size: 18px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        min-width: 200px;
                        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                    " onmouseover="this.style.background='linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'" onmouseout="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        💝 Proses Donasi Sekarang
                    </button>
                </div>
            </form>
        </div>

        <script type="text/javascript">
        // Global functions that need to be available immediately
        function changeQty(dollName, change) {
            const qtyInput = document.querySelector(`input[name="${dollName}_qty"]`);
            const qtyDisplay = qtyInput.parentElement.querySelector('.qty-display');
            let currentQty = parseInt(qtyInput.value) || 0;

            currentQty = Math.max(0, currentQty + change);

            qtyInput.value = currentQty;
            qtyDisplay.textContent = currentQty;

            const newTotalWeight = calculateTotalWeight();
            console.log(`📊 Quantity changed - ${dollName}: ${currentQty}`);
            console.log(`⚖️ New total weight: ${newTotalWeight}g`);

            // Update subtotal immediately (before shipping calculation)
            updateSubtotalOnly();

            // Reload shipping options if city is selected (using debounced calculation)
            const cityIdInput = document.querySelector('input[name="city_id"]');
            if (cityIdInput && cityIdInput.value) {
                console.log(`🔄 Quantity changed, recalculating shipping for city: ${cityIdInput.value}`);

                // Find the selected city from global storage
                if (window.selectedCity) {
                    console.log(`🗺️ Using stored city:`, window.selectedCity);
                    debouncedShippingCalculation(window.selectedCity);
                } else {
                    console.log(`⚠️ Selected city not found`);
                    updateTotal(); // Fallback: update total without shipping recalculation
                }
            } else {
                console.log(`⚠️ No city selected for shipping recalculation`);
                updateTotal(); // Update total without shipping cost change
            }
        }

        function updateSubtotalOnly() {
            let subtotal = 0;
            let totalWeight = 0;
            let firstSelectedPrice = 0;

            // Calculate subtotal and weight
            <?php foreach ($dolls as $doll): ?>
            const <?php echo strtolower($doll->name); ?>_qty = parseInt(document.querySelector('input[name="<?php echo strtolower($doll->name); ?>_qty"]').value) || 0;
            subtotal += <?php echo strtolower($doll->name); ?>_qty * <?php echo intval(isset($doll->price_idr) ? $doll->price_idr : $doll->price); ?>;
            totalWeight += <?php echo strtolower($doll->name); ?>_qty * <?php echo intval($doll->weight_grams); ?>;
            if (!firstSelectedPrice && <?php echo strtolower($doll->name); ?>_qty > 0) {
                firstSelectedPrice = <?php echo intval(isset($doll->price_idr) ? $doll->price_idr : $doll->price); ?>;
            }
            <?php endforeach; ?>

            const donationFlow = document.querySelector('input[name="order_flow_type"]:checked');
            if (donationFlow && donationFlow.value === 'self_plus_donation' && firstSelectedPrice > 0) {
                subtotal += firstSelectedPrice;
            }

            document.getElementById('subtotalAmount').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('totalWeight').textContent = totalWeight + ' gram';

            console.log(`💰 Subtotal updated: Rp ${subtotal.toLocaleString('id-ID')}`);
            console.log(`⚖️ Weight updated: ${totalWeight}g`);
        }

        function calculateTotalWeight() {
            let totalWeight = 0;
            <?php foreach ($dolls as $doll): ?>
            const <?php echo strtolower($doll->name); ?>_qty = parseInt(document.querySelector('input[name="<?php echo strtolower($doll->name); ?>_qty"]').value) || 0;
            totalWeight += <?php echo strtolower($doll->name); ?>_qty * <?php echo intval($doll->weight_grams); ?>;
            <?php endforeach; ?>
            return totalWeight;
        }

        function updateTotal() {
            const subtotalAmount = parseInt(document.getElementById('subtotalAmount').textContent.replace(/[^0-9]/g, '') || 0);
            const shippingCost = parseInt(document.querySelector('input[name="shipping_cost"]').value) || 0;
            const total = subtotalAmount + shippingCost;

            document.getElementById('totalAmount').textContent = 'Rp ' + total.toLocaleString('id-ID');
            console.log(`💰 Total updated: Rp ${total.toLocaleString('id-ID')} (Subtotal: ${subtotalAmount} + Shipping: ${shippingCost})`);
        }

        // City search functions
        var searchTimeout;
        function debouncedSearchCities(query) {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            searchTimeout = setTimeout(() => {
                searchCities(query);
            }, 300);
        }

        // Live search functions
        function searchCities(query) {
            const resultsDiv = document.getElementById('citySearchResults');

            // Clear previous results
            resultsDiv.innerHTML = '';

            // Only search if query is at least 3 characters
            if (query.length < 3) {
                resultsDiv.style.display = 'none';
                return;
            }

            // Show loading state
            const loadingItem = document.createElement('div');
            loadingItem.style.cssText = 'padding: 10px 15px; color: #666; font-style: italic;';
            loadingItem.textContent = 'Mencari kota...';
            resultsDiv.appendChild(loadingItem);
            resultsDiv.style.display = 'block';

            // Search using Biteship API
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_biteship_cities&query=${encodeURIComponent(query)}`
            })
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML = ''; // Clear loading

                if (data.success && data.data && data.data.data && data.data.data.length > 0) {
                    const cities = data.data.data;
                    cities.slice(0, 10).forEach(city => { // Show max 10 results
                        displayCityResult(city, resultsDiv);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    const noResultItem = document.createElement('div');
                    noResultItem.style.cssText = 'padding: 10px 15px; color: #666; font-style: italic;';
                    noResultItem.textContent = 'Tidak ada kota yang ditemukan';
                    resultsDiv.appendChild(noResultItem);
                    resultsDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('City search API error:', error);
                resultsDiv.innerHTML = '';
                const errorItem = document.createElement('div');
                errorItem.style.cssText = 'padding: 10px 15px; color: #e74c3c; font-style: italic;';
                errorItem.textContent = 'Error mencari kota. Coba lagi.';
                resultsDiv.appendChild(errorItem);
                resultsDiv.style.display = 'block';
            });
        }

        function displayCityResult(city, resultsDiv) {
            const resultItem = document.createElement('div');
            resultItem.style.cssText = 'padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background-color 0.2s;';

            // Debug logging
            console.log('🏙️ Displaying city result:', {
                area_id: city.area_id,
                display_name: city.display_name,
                postal_code: city.postal_code,
                full_name: city.full_name
            });

            // Use the full name from API as display (format: "Sidorejo, Salatiga, Jawa Tengah. 50711")
            const displayText = city.display_name || city.full_name || (city.type + ' ' + city.city_name + ', ' + city.province);

            resultItem.innerHTML = `
                <div style="font-size: 14px; line-height: 1.4;">
                    <span style="font-weight: 500; color: #2c3e50;">${displayText}</span>
                </div>
            `;

            resultItem.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });

            resultItem.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'white';
            });

            resultItem.addEventListener('mousedown', function(e) {
                e.preventDefault();
            });

            resultItem.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                selectCity(city);
            });

            resultsDiv.appendChild(resultItem);
        }

        function selectCity(city) {
            // DEBUG: Log the selected city
            console.log('🏙️ Selected city object:', city);
            console.log('🆔 area_id:', city.area_id);
            console.log('📮 postal_code:', city.postal_code);

            // Update hidden fields - use area_id as the primary identifier
            // IMPORTANT: Combine with postal code for new Biteship format
            const baseAreaId = city.area_id || city.city_id;
            const postalCode = city.postal_code || '';
            const finalAreaId = postalCode ? `${baseAreaId}IDZ${postalCode}` : baseAreaId;

            console.log('🎯 Base area_id:', baseAreaId);
            console.log('📮 Postal code:', postalCode);
            console.log('🆔 Final area_id (with postal):', finalAreaId);

            // Store globally selected city with updated area_id
            window.selectedCity = {
                ...city,
                area_id: finalAreaId,  // Use the combined area_id with postal code
                city_id: finalAreaId   // Keep for backward compatibility
            };

            // Update search input with the display name from API
            document.getElementById('citySearch').value = city.display_name || city.full_name;

            document.querySelector('input[name="city_id"]').value = finalAreaId;
            document.querySelector('input[name="city_name"]').value = city.display_name || city.full_name;
            document.querySelector('input[name="province"]').value = city.province;

            hideSearchResults();

            // Trigger shipping calculation
            console.log('🚛 Triggering shipping calculation for selected city');
            debouncedShippingCalculation(window.selectedCity);
        }

        function showSearchResults() {
            var resultsDiv = document.getElementById('citySearchResults');
            if (resultsDiv && resultsDiv.innerHTML.trim() !== '') {
                resultsDiv.style.display = 'block';
            }
        }

        function hideSearchResults() {
            var resultsDiv = document.getElementById('citySearchResults');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }
        }

        function handleSearchKeydown(event) {
            var resultsDiv = document.getElementById('citySearchResults');
            if (!resultsDiv || resultsDiv.style.display === 'none') return;

            var options = resultsDiv.querySelectorAll('.city-option');
            var selected = resultsDiv.querySelector('.city-option.selected');
            var selectedIndex = Array.from(options).indexOf(selected);

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (selected) selected.classList.remove('selected');
                var nextIndex = selectedIndex < options.length - 1 ? selectedIndex + 1 : 0;
                options[nextIndex].classList.add('selected');
                options[nextIndex].style.backgroundColor = '#e3f2fd';
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (selected) selected.classList.remove('selected');
                var prevIndex = selectedIndex > 0 ? selectedIndex - 1 : options.length - 1;
                options[prevIndex].classList.add('selected');
                options[prevIndex].style.backgroundColor = '#e3f2fd';
            } else if (event.key === 'Enter' && selected) {
                event.preventDefault();
                selected.click();
            } else if (event.key === 'Escape') {
                hideSearchResults();
            }
        }

        // Shipping calculation functions
        var shippingTimeout;
        function debouncedShippingCalculation(city) {
            if (shippingTimeout) {
                clearTimeout(shippingTimeout);
            }
            shippingTimeout = setTimeout(() => {
                calculateShippingCost(city);
            }, 500);
        }

        function calculateShippingCost(city) {
            console.log('🚛 Calculating shipping cost for:', city);

            const totalWeight = calculateTotalWeight();
            if (totalWeight <= 0) {
                console.log('⚠️ No items selected, skipping shipping calculation');
                return;
            }

            // Show loading state
            document.getElementById('shippingAmount').textContent = 'Menghitung...';
            document.getElementById('ongkirStatus').textContent = 'Menghitung ongkos kirim...';

            // Calculate shipping using AJAX
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=calculate_shipping_cost&destination_area_id=${encodeURIComponent(city.area_id)}&weight=${totalWeight}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('🚛 Shipping calculation response:', data);

                if (data.success && data.data && data.data.cost) {
                    const shippingCost = parseInt(data.data.cost) || 0;
                    const etd = data.data.estimated_delivery_time || '2-3 hari';

                    document.getElementById('shippingAmount').textContent = 'Rp ' + shippingCost.toLocaleString('id-ID');
                    document.getElementById('ongkirStatus').innerHTML = `
                        <span style="color: #27ae60;">✅ Berhasil</span><br>
                        <small>${data.data.service} - ${etd}</small>
                    `;

                    // Update hidden field
                    document.querySelector('input[name="shipping_cost"]').value = shippingCost;

                    // Update total
                    updateTotal();

                    console.log('✅ Shipping cost calculated:', shippingCost);
                } else {
                    document.getElementById('shippingAmount').textContent = 'Error';
                    document.getElementById('ongkirStatus').innerHTML = '<span style="color: #e74c3c;">❌ Gagal menghitung ongkir</span>';
                    console.error('❌ Shipping calculation failed:', data);
                }
            })
            .catch(error => {
                console.error('❌ Shipping calculation error:', error);
                document.getElementById('shippingAmount').textContent = 'Error';
                document.getElementById('ongkirStatus').innerHTML = '<span style="color: #e74c3c;">❌ Error koneksi</span>';
            });
        }

        // Form submission handler
        document.getElementById('donasiKukangForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate form before submission
            if (!validateDonationForm()) {
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Memproses...';

            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'process_donation');

            // Submit form
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('🎯 Form submission response:', data);

                if (data.success && data.data.snap_token) {
                    console.log('✅ Payment processing successful, opening Midtrans Snap');

                    // Open Midtrans Snap payment
                    if (typeof snap !== 'undefined') {
                        snap.pay(data.data.snap_token, {
                            onSuccess: function(result) {
                                console.log('✅ Payment successful:', result);
                                alert('🎉 Pembayaran berhasil! Terima kasih atas donasi Anda.');
                                // Optionally reload page or redirect
                                window.location.reload();
                            },
                            onPending: function(result) {
                                console.log('⏳ Payment pending:', result);
                                alert('⏳ Pembayaran tertunda. Silakan selesaikan pembayaran Anda.');
                            },
                            onError: function(result) {
                                console.error('❌ Payment error:', result);
                                alert('❌ Pembayaran gagal. Silakan coba lagi.');
                            },
                            onClose: function() {
                                console.log('💨 Payment popup closed');
                                // User closed the popup without completing payment
                            }
                        });
                    } else {
                        console.error('❌ Midtrans Snap library not loaded');
                        alert('❌ Error: Sistem pembayaran tidak tersedia');
                    }
                } else {
                    console.error('❌ Form submission failed:', data);
                    let errorMessage = 'Terjadi kesalahan saat memproses donasi.';

                    if (data.data && data.data.message) {
                        errorMessage = data.data.message;
                    } else if (data.data && data.data.errors) {
                        errorMessage = data.data.errors.join('\\n');
                    }

                    alert('❌ ' + errorMessage);
                }
            })
            .catch(error => {
                console.error('❌ Form submission error:', error);
                alert('❌ Terjadi kesalahan jaringan. Silakan coba lagi.');
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });

        function validateDonationForm() {
            const errors = [];

            // Check required fields
            const requiredFields = [
                { name: 'customer_name', label: 'Nama Lengkap' },
                { name: 'email', label: 'Email' },
                { name: 'phone', label: 'Nomor Telepon' },
                { name: 'address', label: 'Alamat' },
                { name: 'city_name', label: 'Kota/Kabupaten' },
                { name: 'postal_code', label: 'Kode Pos' }
            ];

            requiredFields.forEach(field => {
                const input = document.querySelector(`input[name="${field.name}"], textarea[name="${field.name}"]`);
                if (!input || !input.value.trim()) {
                    errors.push(`${field.label} harus diisi`);
                }
            });

            const motivationInput = document.getElementById('donation_motivation_code');
            if (!motivationInput || !motivationInput.value.trim()) {
                errors.push('Motivasi donasi harus dipilih');
            }

            const motivationOtherInput = document.getElementById('donation_motivation_other');
            if (motivationInput && motivationInput.value === 'lainnya' && (!motivationOtherInput || !motivationOtherInput.value.trim())) {
                errors.push('Alasan donasi lainnya harus diisi');
            }

            // Check if any items selected
            let hasItems = false;
            <?php foreach ($dolls as $doll): ?>
            const <?php echo strtolower($doll->name); ?>Qty = parseInt(document.querySelector('input[name="<?php echo strtolower($doll->name); ?>_qty"]').value) || 0;
            if (<?php echo strtolower($doll->name); ?>Qty > 0) hasItems = true;
            <?php endforeach; ?>

            if (!hasItems) {
                errors.push('Pilih minimal 1 boneka kukang untuk diadopsi');
            }

            // Check shipping cost
            const shippingCost = parseInt(document.querySelector('input[name="shipping_cost"]').value) || 0;
            if (shippingCost <= 0) {
                errors.push('Ongkos kirim belum dihitung. Pilih kota tujuan terlebih dahulu.');
            }

            if (errors.length > 0) {
                alert('❌ Form belum lengkap:\\n\\n' + errors.join('\\n'));
                return false;
            }

            return true;
        }

        function toggleDonationMotivationOther() {
            const motivation = document.getElementById('donation_motivation_code');
            const otherWrap = document.getElementById('donation_motivation_other_wrap');
            const otherInput = document.getElementById('donation_motivation_other');

            if (!motivation || !otherWrap || !otherInput) {
                return;
            }

            if (motivation.value === 'lainnya') {
                otherWrap.style.display = 'block';
            } else {
                otherWrap.style.display = 'none';
                otherInput.value = '';
            }
        }

        // Initialize calculations on page load
        updateSubtotalOnly();
        updateTotal();
        document.getElementById('donation_motivation_code').addEventListener('change', toggleDonationMotivationOther);
        document.querySelectorAll('input[name="order_flow_type"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                updateSubtotalOnly();
                updateTotal();
            });
        });
        toggleDonationMotivationOther();
        </script>

        <?php $this->add_form_javascript($dolls); ?>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get English form HTML
     *
     * @since    3.1.0
     * @return   string    Form HTML
     */
    private function get_english_form_html() {
        global $wpdb;

        // Get active dolls with prices
        $dolls = $wpdb->get_results("
            SELECT name, price_idr as price, price_usd, description, is_active
            FROM {$wpdb->prefix}kukang_dolls_new
            WHERE is_active = 1
            ORDER BY id ASC
        ");

        // Fallback if no dolls or missing USD prices
        if (!$dolls) {
            $dolls = array(
                (object) array('name' => 'Regina', 'price' => 150000, 'price_usd' => 10.00, 'description' => '', 'is_active' => 1),
                (object) array('name' => 'Jagger', 'price' => 150000, 'price_usd' => 10.00, 'description' => '', 'is_active' => 1),
                (object) array('name' => 'Butros', 'price' => 150000, 'price_usd' => 10.00, 'description' => '', 'is_active' => 1),
                (object) array('name' => 'Eid', 'price' => 150000, 'price_usd' => 10.00, 'description' => '', 'is_active' => 1),
                (object) array('name' => 'Anoda', 'price' => 150000, 'price_usd' => 10.00, 'description' => '', 'is_active' => 1)
            );
        }

        // Get current USD rate
        $currency_settings = $wpdb->get_row("SELECT usd_rate FROM {$wpdb->prefix}kukang_currency_new WHERE currency_code = 'USD'");
        $usd_rate = $currency_settings ? $currency_settings->usd_rate : 0.000067;

        ob_start();
        ?>
        <div class="donasi-kukang-container" style="width: 100%; max-width: 100%; padding: 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; color: #333; line-height: 1.6;">
            <div style="text-align: center; margin-bottom: 40px;">
                <h2 style="color: white; margin: 0 0 10px 0; font-size: 28px; font-weight: 300; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">🐒 Adopt a Slow Loris</h2>
                <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Complete the following information for slow loris doll adoption process</p>
            </div>

            <form id="donasiKukangFormEn" class="donasi-form" style="background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 20px; backdrop-filter: blur(10px);">
                <!-- Doll Selection Grid -->
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; font-size: 20px; color: #2c3e50; text-align: center;">🎯 Select Slow Loris to Adopt</h3>
                    <div class="dolls-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px; max-width: 700px; margin: 0 auto;">
                        <?php foreach ($dolls as $doll):
                            $doll_name = strtolower($doll->name);
                            $usd_price = isset($doll->price_usd) ? $doll->price_usd : ($doll->price * $usd_rate);
                        ?>
                        <div class="doll-card" style="text-align: center; padding: 20px; border: 2px solid #f0f0f0; border-radius: 12px; background: #fff; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#3498db'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.borderColor='#f0f0f0'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="font-size: 48px; margin-bottom: 10px;">🐒</div>
                            <div style="font-weight: bold; font-size: 14px; color: #2c3e50; margin-bottom: 5px;"><?php echo esc_html(ucfirst($doll->name)); ?></div>
                            <div style="font-weight: bold; color: #3498db; font-size: 16px; margin-bottom: 10px;">$<?php echo number_format($usd_price, 2); ?></div>
                            <div style="font-size: 11px; color: #7f8c8d; margin-bottom: 10px; line-height: 1.2;">≈ Rp <?php echo number_format($doll->price, 0, ',', '.'); ?></div>

                            <!-- Quantity Controls -->
                            <div class="qty-control" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 15px;">
                                <button type="button" onclick="changeQtyEn('<?php echo $doll_name; ?>', -1)" style="width: 32px; height: 32px; border-radius: 6px; background: #ecf0f1; border: none; font-weight: 600; cursor: pointer; font-size: 16px; color: #2c3e50; transition: background-color 0.2s ease;" onmouseover="this.style.background='#d0d7dd'" onmouseout="this.style.background='#ecf0f1'">-</button>
                                <div class="qty-display" style="background: #3498db; color: white; min-width: 36px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; font-weight: 600; font-size: 15px;" id="<?php echo $doll_name; ?>_qty_display_en">0</div>
                                <button type="button" onclick="changeQtyEn('<?php echo $doll_name; ?>', 1)" style="width: 32px; height: 32px; border-radius: 6px; background: #ecf0f1; border: none; font-weight: 600; cursor: pointer; font-size: 16px; color: #2c3e50; transition: background-color 0.2s ease;" onmouseover="this.style.background='#d0d7dd'" onmouseout="this.style.background='#ecf0f1'">+</button>
                            </div>
                            <input type="hidden" class="qty-input" name="<?php echo $doll_name; ?>_qty" id="<?php echo $doll_name; ?>_qty_en" value="0" data-price="<?php echo $usd_price; ?>" data-name="<?php echo esc_attr($doll->name); ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Customer Information -->
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; font-size: 20px; color: #2c3e50; text-align: center;">👤 Personal Information</h3>
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">Full Name *</label>
                            <input type="text" name="customer_name" required style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background: #fff; transition: border-color 0.3s ease;" placeholder="Enter your full name" onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">Email Address *</label>
                            <input type="email" name="email" required style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background: #fff; transition: border-color 0.3s ease;" placeholder="your.email@example.com" onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                        </div>
                    </div>
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">Phone Number *</label>
                            <input type="tel" name="phone" required style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background: #fff; transition: border-color 0.3s ease;" placeholder="+62812345678" onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px; font-size: 20px; color: #2c3e50; text-align: center;">🏠 Shipping Address</h3>
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 250px; position: relative;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">City/Regency *</label>
                            <input type="text" id="citySearchEn" placeholder="Type your city name..." autocomplete="off" style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background: #fff; transition: border-color 0.3s ease;" onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                            <div id="citySearchResultsEn" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; z-index: 1000; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"></div>
                            <input type="hidden" name="city_id" id="city_id_en">
                            <input type="hidden" name="city_name" id="city_name_en">
                            <input type="hidden" name="province" id="province_en">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">Postal Code *</label>
                            <input type="text" name="postal_code" required maxlength="5" style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background: #fff; transition: border-color 0.3s ease;" placeholder="12345" onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'">
                        </div>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">Complete Address *</label>
                        <textarea name="address" required style="width: 100%; min-height: 80px; resize: vertical; font-family: inherit; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; background: #fff; transition: border-color 0.3s ease;" placeholder="Street address, building number, apartment/unit details, etc." onfocus="this.style.borderColor='#3498db'" onblur="this.style.borderColor='#e0e0e0'"></textarea>
                    </div>
                </div>

                <div style="background: rgba(255,255,255,0.95); padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 16px 0; color: #2c3e50;">Order Preference</h3>
                    <label style="display:block; margin-bottom:10px;">
                        <input type="radio" name="order_flow_type" value="self_only" checked>
                        Buy the product for myself
                    </label>
                    <label style="display:block; margin-bottom:10px;">
                        <input type="radio" name="order_flow_type" value="self_plus_donation">
                        Buy 1 product for myself and 1 product to donate through YIARI
                    </label>
                </div>

                <div style="background: rgba(255,255,255,0.95); padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 16px 0; color: #2c3e50;">Donation Motivation</h3>
                    <label style="display:block; margin-bottom:8px; font-weight:500;">What is your main reason for donating?</label>
                    <select name="donation_motivation_code" id="donation_motivation_code_en" style="width:100%; padding:12px 16px; border:2px solid #e0e0e0; border-radius:8px; font-size:15px; background:#fff; box-sizing:border-box;">
                        <option value="">Please choose</option>
                        <option value="ingin_mendukung_misi_yiari">I want to support YIARI's mission</option>
                        <option value="tertarik_pada_produk_edukasi">I am interested in the educational product or book</option>
                        <option value="ingin_berdonasi_produk">I want to donate a product through YIARI</option>
                        <option value="rekomendasi_teman_atau_komunitas">Recommendation from a friend or community</option>
                        <option value="lainnya">Other</option>
                    </select>
                    <div id="donation_motivation_other_wrap_en" style="display:none; margin-top:12px;">
                        <input type="text" name="donation_motivation_other" id="donation_motivation_other_en" placeholder="Please tell us your reason" style="width:100%; padding:12px 16px; border:2px solid #e0e0e0; border-radius:8px; font-size:15px; background:#fff; box-sizing:border-box;">
                    </div>
                </div>

                <!-- Hidden fields for shipping calculation -->
                <input type="hidden" name="shipping_cost" id="shipping_cost_en" value="0">
                <input type="hidden" name="currency" value="USD">
                <input type="hidden" name="exchange_rate" value="<?php echo $usd_rate; ?>">

                <!-- Submit Button -->
                <div class="submit-section" style="text-align: center;">
                    <button type="submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 16px 48px; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; min-width: 200px; text-shadow: 0 1px 2px rgba(0,0,0,0.2);" onmouseover="this.style.background='linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'" onmouseout="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        🎁 Process Donation Now
                    </button>
                </div>
            </form>

            <!-- Summary Section -->
            <div class="summary-section" style="background: rgba(255,255,255,0.95); padding: 30px; border-radius: 12px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                <h3 style="margin-bottom: 20px; font-size: 20px; color: #2c3e50; text-align: center;">📋 Donation Summary</h3>

                <div class="summary-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 8px;">
                    <span style="font-weight: 600;">Subtotal:</span>
                    <span id="subtotalAmountEn" style="font-weight: bold; color: #3498db; font-size: 18px;">$0.00</span>
                </div>

                <div class="summary-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 8px;">
                    <span style="font-weight: 600;">Total Weight:</span>
                    <span id="totalWeightEn" style="color: #666;">0 gram</span>
                </div>

                <div class="summary-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 1px solid #e0e0e0;">
                    <span style="font-weight: 600;">Shipping Cost:</span>
                    <span id="shippingAmountEn" style="color: #e67e22; font-weight: 600;">Select city first</span>
                </div>

                <div class="summary-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 8px; border-top: 2px solid #3498db; padding-top: 15px;">
                    <span style="font-size: 20px; font-weight: bold;">TOTAL DONATION:</span>
                    <span id="totalAmountEn" style="font-size: 24px; font-weight: bold; color: #2c3e50;">$0.00</span>
                </div>

                <div id="ongkirStatusEn" style="text-align: center; margin-top: 15px; font-size: 14px; color: #7f8c8d;">
                    Please select dolls and enter city to calculate shipping cost
                </div>
            </div>
        </div>

        <script>
        // Global variables for English form
        var subtotalEn = 0;
        var shippingCostEn = 0;
        var dollPricesEn = {};
        var selectedCityEn = null;

        // Extract doll prices for English form
        <?php foreach ($dolls as $doll): ?>
        dollPricesEn['<?php echo strtolower($doll->name); ?>'] = <?php echo isset($doll->price_usd) ? $doll->price_usd : ($doll->price * $usd_rate); ?>;
        <?php endforeach; ?>

        // Global functions for English form - matching Indonesian form functions exactly
        function changeQtyEn(dollName, change) {
            var qtyInput = document.getElementById(dollName + '_qty_en');
            var qtyDisplay = document.getElementById(dollName + '_qty_display_en');
            var hiddenInput = document.querySelector('input[name="' + dollName + '_qty"]');

            var currentQty = parseInt(qtyInput.value) || 0;
            var newQty = Math.max(0, currentQty + change);

            qtyInput.value = newQty;
            qtyDisplay.textContent = newQty;
            if (hiddenInput) hiddenInput.value = newQty;

            calculateSubtotalEn();
        }

        function calculateSubtotalEn() {
            subtotalEn = 0;
            for (var dollName in dollPricesEn) {
                var qty = parseInt(document.getElementById(dollName + '_qty_en').value) || 0;
                subtotalEn += qty * dollPricesEn[dollName];
            }
            document.getElementById('subtotalAmountEn').textContent = '$' + subtotalEn.toFixed(2);
            calculateTotalEn();
            calculateTotalWeightEn();
        }

        function calculateTotalEn() {
            var total = subtotalEn + shippingCostEn;
            document.getElementById('totalAmountEn').textContent = '$' + total.toFixed(2);
        }

        function calculateTotalWeightEn() {
            var totalWeight = 0;
            for (var dollName in dollPricesEn) {
                var qty = parseInt(document.getElementById(dollName + '_qty_en').value) || 0;
                totalWeight += qty * 200; // 200g per doll
            }
            document.getElementById('totalWeightEn').textContent = totalWeight + ' gram';
            return totalWeight;
        }

        // City search functionality for English form
        var searchTimeoutEn;
        function debouncedSearchCitiesEn(query) {
            if (searchTimeoutEn) {
                clearTimeout(searchTimeoutEn);
            }
            searchTimeoutEn = setTimeout(function() {
                searchCitiesEn(query);
            }, 300);
        }

        function searchCitiesEn(query) {
            if (query.length < 3) {
                document.getElementById('citySearchResultsEn').style.display = 'none';
                return;
            }

            var resultsDiv = document.getElementById('citySearchResultsEn');
            resultsDiv.innerHTML = '<div style="padding: 10px 15px; color: #666; font-style: italic;">Searching cities...</div>';
            resultsDiv.style.display = 'block';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_biteship_cities&query=' + encodeURIComponent(query)
            })
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML = '';

                if (data.success && data.data && data.data.data && data.data.data.length > 0) {
                    var cities = data.data.data;
                    cities.slice(0, 10).forEach(city => {
                        var resultItem = document.createElement('div');
                        resultItem.style.cssText = 'padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background-color 0.2s;';
                        resultItem.textContent = city.display_name || city.full_name;

                        resultItem.addEventListener('mouseenter', function() {
                            this.style.backgroundColor = '#f8f9fa';
                        });
                        resultItem.addEventListener('mouseleave', function() {
                            this.style.backgroundColor = 'white';
                        });
                        resultItem.addEventListener('click', function() {
                            selectCityEn(city);
                        });

                        resultsDiv.appendChild(resultItem);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="padding: 10px 15px; color: #666; font-style: italic;">No cities found</div>';
                    resultsDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('City search error:', error);
                resultsDiv.innerHTML = '<div style="padding: 10px 15px; color: #e74c3c; font-style: italic;">Error searching cities. Please try again.</div>';
            });
        }

        function selectCityEn(city) {
            var baseAreaId = city.area_id || city.city_id;
            var postalCode = city.postal_code || '';
            var finalAreaId = postalCode ? baseAreaId + 'IDZ' + postalCode : baseAreaId;

            selectedCityEn = {
                ...city,
                area_id: finalAreaId,
                city_id: finalAreaId
            };

            document.getElementById('citySearchEn').value = city.display_name || city.full_name;
            document.getElementById('city_id_en').value = finalAreaId;
            document.getElementById('city_name_en').value = city.display_name || city.full_name;
            document.getElementById('province_en').value = city.province || '';
            document.getElementById('citySearchResultsEn').style.display = 'none';

            debouncedShippingCalculationEn(selectedCityEn);
        }

        // Shipping calculation for English form
        var shippingTimeoutEn;
        function debouncedShippingCalculationEn(city) {
            if (shippingTimeoutEn) {
                clearTimeout(shippingTimeoutEn);
            }
            shippingTimeoutEn = setTimeout(() => {
                calculateShippingCostEn(city);
            }, 500);
        }

        function calculateShippingCostEn(city) {
            var totalWeight = calculateTotalWeightEn();
            if (totalWeight <= 0) {
                console.log('No items selected, skipping shipping calculation');
                return;
            }

            document.getElementById('shippingAmountEn').textContent = 'Calculating...';
            document.getElementById('ongkirStatusEn').textContent = 'Calculating shipping cost...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=calculate_shipping_cost&destination_area_id=' + encodeURIComponent(city.area_id) + '&weight=' + totalWeight
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.cost) {
                    var costIDR = parseInt(data.data.cost);
                    var costUSD = costIDR * <?php echo $usd_rate; ?>;
                    shippingCostEn = costUSD;

                    document.getElementById('shippingAmountEn').textContent = '$' + costUSD.toFixed(2);
                    document.getElementById('shipping_cost_en').value = costUSD.toFixed(2);
                    document.getElementById('ongkirStatusEn').textContent = 'Shipping cost calculated for ' + (city.display_name || city.full_name);

                    calculateTotalEn();
                } else {
                    shippingCostEn = 0;
                    document.getElementById('shippingAmountEn').textContent = 'Error';
                    document.getElementById('ongkirStatusEn').textContent = 'Error calculating shipping cost. Please try selecting city again.';
                }
            })
            .catch(error => {
                console.error('Shipping calculation error:', error);
                shippingCostEn = 0;
                document.getElementById('shippingAmountEn').textContent = 'Error';
                document.getElementById('ongkirStatusEn').textContent = 'Error calculating shipping cost. Please check your connection.';
            });
        }

        // Form validation and submission for English form
        function validateDonationFormEn() {
            var errors = [];

            // Check required fields
            var requiredFields = {
                'customer_name': 'Full name',
                'email': 'Email address',
                'phone': 'Phone number',
                'address': 'Complete address',
                'city_name': 'City',
                'postal_code': 'Postal code'
            };

            for (var field in requiredFields) {
                var input = document.querySelector('input[name="' + field + '"]') || document.querySelector('textarea[name="' + field + '"]');
                if (!input || !input.value.trim()) {
                    errors.push(requiredFields[field] + ' is required');
                }
            }

            // Check if any dolls selected
            var hasDolls = false;
            for (var dollName in dollPricesEn) {
                var qty = parseInt(document.getElementById(dollName + '_qty_en').value) || 0;
                if (qty > 0) {
                    hasDolls = true;
                    break;
                }
            }

            if (!hasDolls) {
                errors.push('Please select at least 1 slow loris doll to adopt');
            }

            if (errors.length > 0) {
                alert('Errors:\\n' + errors.join('\\n'));
                return false;
            }

            return true;
        }

        // Event listeners for English form
        document.addEventListener('DOMContentLoaded', function() {
            // City search event listener
            var citySearchInput = document.getElementById('citySearchEn');
            if (citySearchInput) {
                citySearchInput.addEventListener('input', function() {
                    var query = this.value;
                    if (query.length >= 3) {
                        debouncedSearchCitiesEn(query);
                    } else {
                        document.getElementById('citySearchResultsEn').style.display = 'none';
                    }
                });
            }

            // Postal code change listener
            var postalCodeInput = document.querySelector('input[name="postal_code"]');
            if (postalCodeInput) {
                postalCodeInput.addEventListener('blur', function() {
                    if (this.value.length >= 5 && selectedCityEn) {
                        debouncedShippingCalculationEn(selectedCityEn);
                    }
                });
            }

            // Form submission
            var formEn = document.getElementById('donasiKukangFormEn');
            if (formEn) {
                formEn.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (validateDonationFormEn()) {
                        var submitBtn = this.querySelector('button[type="submit"]');
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Processing...';

                        var formData = new FormData(this);
                        formData.append('action', 'process_donation');
                        formData.append('language', 'en');

                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.snap_token) {
                                window.snap.pay(data.data.snap_token, {
                                    onSuccess: function(result) {
                                        alert('Payment successful!');
                                        console.log(result);
                                    },
                                    onPending: function(result) {
                                        alert('Payment pending. Please complete your payment.');
                                        console.log(result);
                                    },
                                    onError: function(result) {
                                        alert('Payment failed!');
                                        console.log(result);
                                    },
                                    onClose: function() {
                                        console.log('Customer closed the popup without finishing the payment');
                                    }
                                });
                            } else {
                                alert('Error: ' + (data.data ? data.data.message : 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            alert('Network error occurred. Please try again.');
                            console.error(error);
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = '🎁 Process Donation Now';
                        });
                    }
                });
            }

            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#citySearchEn, #citySearchResultsEn')) {
                    document.getElementById('citySearchResultsEn').style.display = 'none';
                }
            });

            // Initialize calculations
            calculateSubtotalEn();
            calculateTotalWeightEn();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Process form submission
     *
     * @since    3.1.0
     * @param    array    $form_data    Form data
     * @param    string   $language     Language (id/en)
     * @return   array                  Processing result
     */
    public function process_form_submission($form_data, $language = 'id') {
        global $wpdb;
        
        // Validate required fields
        $required_fields = array(
            'customer_name' => 'Nama Lengkap',
            'email' => 'Email',
            'phone' => 'Telepon',
            'address' => 'Alamat',
            'city_name' => 'Kota',
            'postal_code' => 'Kode Pos'
        );
        
        $errors = array();
        foreach ($required_fields as $field => $label) {
            if (empty($form_data[$field])) {
                $errors[] = "$label harus diisi";
            }
        }

        if (empty($form_data['donation_motivation_code'])) {
            $errors[] = "Motivasi donasi harus dipilih";
        }

        if (($form_data['donation_motivation_code'] ?? '') === 'lainnya' && empty($form_data['donation_motivation_other'])) {
            $errors[] = "Alasan donasi lainnya harus diisi";
        }

        // Check if any dolls selected
        $has_dolls = false;
        $product_repository = new YIARI_Product_Repository();
        $dolls = $product_repository->get_active_products();
        foreach ($dolls as $doll) {
            $doll_name = strtolower($doll->name);
            $qty = isset($form_data[$doll_name . '_qty']) ? intval($form_data[$doll_name . '_qty']) : 0;
            if ($qty > 0) {
                $has_dolls = true;
                break;
            }
        }
        
        if (!$has_dolls) {
            $errors[] = "Pilih minimal 1 boneka kukang untuk diadopsi";
        }
        
        if (!empty($errors)) {
            return array(
                'success' => false,
                'errors' => $errors
            );
        }
        
        // Process the donation based on language
        if ($language === 'en') {
            return $this->process_english_donation($form_data);
        } else {
            return $this->process_indonesian_donation($form_data);
        }
    }
    
    /**
     * AJAX handler to process Indonesian donation
     *
     * @since    3.1.0
     */
    public function ajax_process_donation() {
        error_log("=== AJAX PROCESS DONATION START ===");

        // FORM VALIDATION - Semua field harus lengkap
        $required_fields = [
            'customer_name' => 'Nama Lengkap',
            'phone' => 'Nomor Telepon',
            'email' => 'Email',
            'address' => 'Alamat Lengkap',
            'city_name' => 'Kota/Kabupaten',
            'postal_code' => 'Kode Pos',
            'shipping_cost' => 'Ongkos Kirim'
        ];

        $validation_errors = [];

        // Debug each field validation
        foreach ($required_fields as $field => $label) {
            $value = $_POST[$field] ?? '';
            $trimmed_value = trim($value);
            error_log("Validating field '$field' ($label): value='$value', trimmed='$trimmed_value', empty=" . (empty($trimmed_value) ? 'yes' : 'no'));

            if (empty($trimmed_value)) {
                $validation_errors[] = "$label harus diisi";
                error_log("❌ Field '$field' failed validation: empty value");
            } else {
                error_log("✅ Field '$field' passed validation");
            }
        }

        if (empty(trim($_POST['donation_motivation_code'] ?? ''))) {
            $validation_errors[] = "Motivasi donasi harus dipilih";
        }

        if (trim($_POST['donation_motivation_code'] ?? '') === 'lainnya' && empty(trim($_POST['donation_motivation_other'] ?? ''))) {
            $validation_errors[] = "Alasan donasi lainnya harus diisi";
        }

        // Email format validation
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Format email tidak valid";
        }

        // Phone number validation (Indonesian format)
        if (!empty($_POST['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $validation_errors[] = "Nomor telepon harus 10-15 digit";
            }
        }

        // Check if any items selected (quantity > 0)
        $has_items = false;
        $item_quantities = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, '_qty') !== false) {
                $qty = intval($value);
                $item_quantities[$key] = $qty;
                error_log("Item quantity check: $key = $qty");
                if ($qty > 0) {
                    $has_items = true;
                }
            }
        }

        error_log("Items validation: has_items=" . ($has_items ? 'yes' : 'no') . ", quantities=" . json_encode($item_quantities));

        if (!$has_items) {
            $validation_errors[] = "Pilih minimal 1 boneka kukang untuk diadopsi";
            error_log("❌ Items validation failed: no items selected");
        } else {
            error_log("✅ Items validation passed");
        }

        // Shipping cost validation
        $shipping_cost = intval($_POST['shipping_cost'] ?? 0);
        error_log("Shipping cost validation: raw_value='" . ($_POST['shipping_cost'] ?? 'NOT_SET') . "', parsed_int=$shipping_cost");

        if ($shipping_cost <= 0) {
            $validation_errors[] = "Ongkos kirim harus dihitung terlebih dahulu. Pilih kota tujuan pengiriman.";
            error_log("❌ Shipping cost validation failed: cost is $shipping_cost");
        } else {
            error_log("✅ Shipping cost validation passed: $shipping_cost");
        }

        // If validation errors, return them
        if (!empty($validation_errors)) {
            error_log("❌ Form validation FAILED with " . count($validation_errors) . " errors:");
            foreach ($validation_errors as $i => $error) {
                error_log("  Error " . ($i + 1) . ": $error");
            }
            error_log("Returning JSON error response to frontend");

            wp_send_json_error([
                'message' => 'Form tidak lengkap atau tidak valid:',
                'errors' => $validation_errors
            ]);
            return;
        }

        error_log("✅ ALL FORM VALIDATION PASSED - Proceeding to payment");

        // Process payment with Midtrans
        try {
            // Prepare donation data
            $donation_data = array(
                'customer_name' => sanitize_text_field($_POST['customer_name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'address' => sanitize_textarea_field($_POST['address']),
                'city_name' => sanitize_text_field($_POST['city_name']),
                'postal_code' => sanitize_text_field($_POST['postal_code']),
                'courier' => 'jne',
                'courier_service' => 'REG',
                'shipping_cost' => intval($_POST['shipping_cost']),
                'language' => 'id',
                'currency' => 'IDR',
                'order_flow_type' => sanitize_text_field($_POST['order_flow_type'] ?? 'self_only'),
                'donation_motivation_code' => sanitize_text_field($_POST['donation_motivation_code'] ?? ''),
                'donation_motivation_other' => sanitize_text_field($_POST['donation_motivation_other'] ?? '')
            );

            $donation_data['donation_book_count'] = ($donation_data['order_flow_type'] === 'self_plus_donation') ? 1 : 0;

            // Add product quantities and calculate totals
            $product_repository = new YIARI_Product_Repository();
            $dolls = $product_repository->get_active_products();

            $total_items = 0;
            $subtotal = 0;
            $first_selected_price = 0;

            foreach ($dolls as $doll) {
                $doll_name = strtolower($doll->name);
                $qty_key = $doll_name . '_qty';
                if (isset($_POST[$qty_key])) {
                    $quantity = intval($_POST[$qty_key]);
                    $donation_data[$qty_key] = $quantity;
                    $total_items += $quantity;
                    $unit_price = floatval($doll->price_idr ?? 0);
                    $subtotal += $quantity * $unit_price;
                    if (!$first_selected_price && $quantity > 0) {
                        $first_selected_price = $unit_price;
                    }
                }
            }

            if (($donation_data['order_flow_type'] ?? 'self_only') === 'self_plus_donation' && $first_selected_price > 0) {
                $subtotal += $first_selected_price;
            }

            $donation_data['total_items'] = $total_items;
            $donation_data['subtotal'] = $subtotal;
            $donation_data['gross_amount'] = $subtotal + $donation_data['shipping_cost'];

            // Generate unique order ID
            $donation_data['order_id'] = 'KUKANG-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));

            // Add environment setting
            $payment_manager = new YIARI_Payment_Manager();
            $settings = $payment_manager->get_midtrans_settings();
            $donation_data['environment'] = $settings['environment'] ?? 'sandbox';
            error_log("Added environment to donation_data: " . $donation_data['environment']);

            // Process payment
            error_log("=== PAYMENT PROCESSING START ===");

            error_log("Calling process_donation_payment with data: " . json_encode($donation_data));
            $payment_result = $payment_manager->process_donation_payment($donation_data);

            error_log("Payment result: " . json_encode($payment_result));

            if ($payment_result && $payment_result['success']) {
                // Save to database
                error_log("Payment successful, saving to database...");
                $this->save_transaction_to_database($donation_data, $payment_result);
                wp_send_json_success($payment_result);
            } else {
                error_log("Payment processing failed: " . json_encode($payment_result));
                wp_send_json_error(array('message' => 'Payment processing failed', 'details' => $payment_result));
            }

        } catch (Exception $e) {
            error_log('Donation processing error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'Internal server error: ' . $e->getMessage()));
        } catch (Error $e) {
            error_log('Fatal error in donation processing: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'Fatal error: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX handler to process English donation
     *
     * @since    3.1.0
     */
    public function ajax_process_donation_en() {
        try {
            // Verify nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yiari_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Security verification failed'));
                return;
            }

            // Sanitize form data
            $form_data = array(
                'customer_name' => sanitize_text_field($_POST['customer_name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'address' => sanitize_textarea_field($_POST['address']),
                'city_name' => sanitize_text_field($_POST['city_name']),
                'city_id' => sanitize_text_field($_POST['city_id']),
                'postal_code' => sanitize_text_field($_POST['postal_code']),
                'currency' => 'USD',
                'exchange_rate' => floatval($_POST['exchange_rate']),
                'order_flow_type' => sanitize_text_field($_POST['order_flow_type'] ?? 'self_only'),
                'donation_motivation_code' => sanitize_text_field($_POST['donation_motivation_code'] ?? ''),
                'donation_motivation_other' => sanitize_text_field($_POST['donation_motivation_other'] ?? '')
            );

            $form_data['donation_book_count'] = ($form_data['order_flow_type'] === 'self_plus_donation') ? 1 : 0;

            // Get doll quantities
            global $wpdb;
            $dolls = $wpdb->get_results("SELECT name, price_usd FROM {$wpdb->prefix}kukang_dolls_new WHERE is_active = 1");
            foreach ($dolls as $doll) {
                $doll_key = strtolower($doll->name) . '_qty';
                $form_data[$doll_key] = isset($_POST[$doll_key]) ? intval($_POST[$doll_key]) : 0;
            }

            // Process donation
            $result = $this->process_english_donation($form_data);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }

        } catch (Exception $e) {
            error_log('English donation processing error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'An error occurred while processing donation'));
        }
    }

    /**
     * Process Indonesian donation
     *
     * @since    3.1.0
     * @param    array    $form_data    Form data
     * @return   array                  Processing result
     */
    private function process_indonesian_donation($form_data) {
        global $wpdb;

        // Validate required fields
        $required_fields = array(
            'customer_name' => 'Nama Lengkap',
            'email' => 'Email',
            'phone' => 'Telepon',
            'address' => 'Alamat',
            'city_name' => 'Kota',
            'postal_code' => 'Kode Pos'
        );

        $errors = array();
        foreach ($required_fields as $field => $label) {
            if (empty($form_data[$field])) {
                $errors[] = "$label harus diisi";
            }
        }

        // Check if any dolls selected
        $subtotal = 0;
        $total_weight = 0;
        $product_repository = new YIARI_Product_Repository();
        $dolls = $product_repository->get_active_products();
        $selected_dolls = array();
        $first_selected_price = 0;

        foreach ($dolls as $doll) {
            $doll_key = strtolower($doll->name) . '_qty';
            $qty = isset($form_data[$doll_key]) ? intval($form_data[$doll_key]) : 0;

            if ($qty > 0) {
                $selected_dolls[strtolower($doll->name)] = $qty;
                $unit_price = floatval($doll->price_idr ?? 0);
                $subtotal += $qty * $unit_price;
                $total_weight += $qty * intval($doll->weight_grams ?? 200);
                if (!$first_selected_price) {
                    $first_selected_price = $unit_price;
                }
            }
        }

        if (($form_data['order_flow_type'] ?? 'self_only') === 'self_plus_donation' && $first_selected_price > 0) {
            $subtotal += $first_selected_price;
        }

        if (empty($selected_dolls)) {
            $errors[] = "Pilih minimal 1 boneka kukang untuk diadopsi";
        }

        if (!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        // Calculate shipping cost using Biteship
        $shipping_manager = new YIARI_Shipping_Manager();
        $shipping_cost = 0;

        if (!empty($form_data['city_id'])) {
            $shipping_result = $shipping_manager->get_biteship_shipping_rate($form_data['city_id'], $total_weight);
            if ($shipping_result && isset($shipping_result['pricing']['value'])) {
                $shipping_cost = intval($shipping_result['pricing']['value']);
            }
        }

        $total_amount = $subtotal + $shipping_cost;

        // Generate order ID
        $order_id = 'KUKANG-' . date('YmdHis') . '-' . wp_rand(1000, 9999);

        // Save to database
        $transaction_data = array(
            'order_id' => $order_id,
            'customer_name' => $form_data['customer_name'],
            'email' => $form_data['email'],
            'phone' => $form_data['phone'],
            'address' => $form_data['address'],
            'city' => $form_data['city_name'],
            'postal_code' => $form_data['postal_code'],
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping_cost,
            'gross_amount' => $total_amount,
            'currency' => 'IDR',
            'donation_book_count' => intval($form_data['donation_book_count'] ?? 0),
            'donation_motivation_code' => $form_data['donation_motivation_code'] ?? '',
            'donation_motivation_other' => $form_data['donation_motivation_other'] ?? '',
            'transaction_status' => 'pending',
            'created_at' => current_time('mysql')
        );

        // Add doll quantities
        foreach ($selected_dolls as $doll_name => $qty) {
            $transaction_data[$doll_name . '_qty'] = $qty;
        }

        $inserted = $wpdb->insert($wpdb->prefix . 'kukang_transactions_new', $transaction_data);

        if (!$inserted) {
            return array('success' => false, 'message' => 'Gagal menyimpan data transaksi');
        }

        $order_service = new YIARI_Order_Service();
        $order_service->upsert_normalized_order($transaction_data, array(
            'payment_status' => 'pending_payment',
            'fulfillment_status' => 'draft',
        ));

        // Prepare payment data
        $payment_data = array(
            'order_id' => $order_id,
            'gross_amount' => $total_amount,
            'customer_name' => $form_data['customer_name'],
            'email' => $form_data['email'],
            'phone' => $form_data['phone'],
            'address' => $form_data['address'],
            'city_name' => $form_data['city_name'],
            'postal_code' => $form_data['postal_code'],
            'shipping_cost' => $shipping_cost,
            'currency' => 'IDR',
            'donation_book_count' => intval($form_data['donation_book_count'] ?? 0),
            'donation_motivation_code' => $form_data['donation_motivation_code'] ?? '',
            'donation_motivation_other' => $form_data['donation_motivation_other'] ?? ''
        );

        // Add doll data to payment
        foreach ($selected_dolls as $doll_name => $qty) {
            $payment_data[$doll_name . '_qty'] = $qty;
        }

        // Add environment setting
        $payment_manager = new YIARI_Payment_Manager();
        $settings = $payment_manager->get_midtrans_settings();
        $payment_data['environment'] = $settings['environment'] ?? 'sandbox';
        error_log("Added environment to payment_data (English form): " . $payment_data['environment']);

        // Process payment with Midtrans
        $payment_result = $payment_manager->process_donation_payment($payment_data);

        if ($payment_result['success']) {
            // Trigger email notification
            do_action('yiari_transaction_created', $payment_data, $payment_result['snap_token']);

            return array(
                'success' => true,
                'data' => array(
                    'snap_token' => $payment_result['snap_token'],
                    'order_id' => $order_id
                )
            );
        } else {
            return array('success' => false, 'message' => 'Gagal memproses pembayaran: ' . $payment_result['error']);
        }
    }
    
    /**
     * Process English donation
     *
     * @since    3.1.0
     * @param    array    $form_data    Form data
     * @return   array                  Processing result
     */
    private function process_english_donation($form_data) {
        global $wpdb;

        // Validate required fields
        $required_fields = array(
            'customer_name' => 'Full Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'city_name' => 'City',
            'postal_code' => 'Postal Code'
        );

        $errors = array();
        foreach ($required_fields as $field => $label) {
            if (empty($form_data[$field])) {
                $errors[] = "$label is required";
            }
        }

        if (empty($form_data['donation_motivation_code'])) {
            $errors[] = "Donation motivation is required";
        }

        if (($form_data['donation_motivation_code'] ?? '') === 'lainnya' && empty($form_data['donation_motivation_other'])) {
            $errors[] = "Please fill in the other donation reason";
        }

        // Check if any dolls selected
        $subtotal_usd = 0;
        $total_weight = 0;
        $product_repository = new YIARI_Product_Repository();
        $dolls = $product_repository->get_active_products();
        $selected_dolls = array();
        $first_selected_price_usd = 0;

        foreach ($dolls as $doll) {
            $doll_key = strtolower($doll->name) . '_qty';
            $qty = isset($form_data[$doll_key]) ? intval($form_data[$doll_key]) : 0;

            if ($qty > 0) {
                $selected_dolls[strtolower($doll->name)] = $qty;
                $unit_price_usd = floatval($doll->price_usd ?? 0);
                $subtotal_usd += $qty * $unit_price_usd;
                $total_weight += $qty * intval($doll->weight_grams ?? 200);
                if (!$first_selected_price_usd) {
                    $first_selected_price_usd = $unit_price_usd;
                }
            }
        }

        if (($form_data['order_flow_type'] ?? 'self_only') === 'self_plus_donation' && $first_selected_price_usd > 0) {
            $subtotal_usd += $first_selected_price_usd;
        }

        if (empty($selected_dolls)) {
            $errors[] = "Please select at least one slow loris to adopt";
        }

        if (!empty($errors)) {
            return array('success' => false, 'errors' => $errors);
        }

        // Calculate shipping cost using Biteship (in IDR, then convert to USD)
        $shipping_manager = new YIARI_Shipping_Manager();
        $shipping_cost_idr = 0;
        $shipping_cost_usd = 0;

        if (!empty($form_data['city_id'])) {
            $shipping_result = $shipping_manager->get_biteship_shipping_rate($form_data['city_id'], $total_weight);
            if ($shipping_result && isset($shipping_result['pricing']['value'])) {
                $shipping_cost_idr = intval($shipping_result['pricing']['value']);
                $shipping_cost_usd = $shipping_cost_idr * $form_data['exchange_rate'];
            }
        }

        $total_amount_usd = $subtotal_usd + $shipping_cost_usd;
        $total_amount_idr = $total_amount_usd / $form_data['exchange_rate']; // Convert back to IDR for Midtrans

        // Generate order ID
        $order_id = 'KUKANG-EN-' . date('YmdHis') . '-' . wp_rand(1000, 9999);

        // Save to database (amounts in IDR for consistency)
        $transaction_data = array(
            'order_id' => $order_id,
            'customer_name' => $form_data['customer_name'],
            'email' => $form_data['email'],
            'phone' => $form_data['phone'],
            'address' => $form_data['address'],
            'city' => $form_data['city_name'],
            'postal_code' => $form_data['postal_code'],
            'subtotal' => intval($subtotal_usd / $form_data['exchange_rate']), // Convert to IDR
            'shipping_cost' => $shipping_cost_idr,
            'gross_amount' => intval($total_amount_idr),
            'currency' => 'USD',
            'exchange_rate' => $form_data['exchange_rate'],
            'usd_amount' => $total_amount_usd,
            'donation_book_count' => intval($form_data['donation_book_count'] ?? 0),
            'donation_motivation_code' => $form_data['donation_motivation_code'] ?? '',
            'donation_motivation_other' => $form_data['donation_motivation_other'] ?? '',
            'transaction_status' => 'pending',
            'created_at' => current_time('mysql')
        );

        // Add doll quantities
        foreach ($selected_dolls as $doll_name => $qty) {
            $transaction_data[$doll_name . '_qty'] = $qty;
        }

        $inserted = $wpdb->insert($wpdb->prefix . 'kukang_transactions_new', $transaction_data);

        if (!$inserted) {
            return array('success' => false, 'message' => 'Failed to save transaction data');
        }

        $order_service = new YIARI_Order_Service();
        $order_service->upsert_normalized_order($transaction_data, array(
            'payment_status' => 'pending_payment',
            'fulfillment_status' => 'draft',
        ));

        // Prepare payment data for Midtrans (in IDR)
        $payment_data = array(
            'order_id' => $order_id,
            'gross_amount' => intval($total_amount_idr),
            'customer_name' => $form_data['customer_name'],
            'email' => $form_data['email'],
            'phone' => $form_data['phone'],
            'address' => $form_data['address'],
            'city_name' => $form_data['city_name'],
            'postal_code' => $form_data['postal_code'],
            'shipping_cost' => $shipping_cost_idr,
            'currency' => 'USD', // Flag for payment processing
            'donation_book_count' => intval($form_data['donation_book_count'] ?? 0),
            'donation_motivation_code' => $form_data['donation_motivation_code'] ?? '',
            'donation_motivation_other' => $form_data['donation_motivation_other'] ?? ''
        );

        // Add doll data to payment
        foreach ($selected_dolls as $doll_name => $qty) {
            $payment_data[$doll_name . '_qty'] = $qty;
        }

        // Add environment setting
        $payment_manager = new YIARI_Payment_Manager();
        $settings = $payment_manager->get_midtrans_settings();
        $payment_data['environment'] = $settings['environment'] ?? 'sandbox';
        error_log("Added environment to payment_data (USD form): " . $payment_data['environment']);

        // Process payment with Midtrans
        $payment_result = $payment_manager->process_donation_payment($payment_data);

        if ($payment_result['success']) {
            // Trigger email notification
            do_action('yiari_transaction_created', $payment_data, $payment_result['snap_token']);

            return array(
                'success' => true,
                'data' => array(
                    'snap_token' => $payment_result['snap_token'],
                    'order_id' => $order_id
                )
            );
        } else {
            return array('success' => false, 'message' => 'Failed to process payment: ' . $payment_result['error']);
        }
    }

    /**
     * Add JavaScript functionality for English form
     *
     * @since    3.1.0
     * @param    array    $dolls       Available dolls
     * @param    float    $usd_rate    USD exchange rate
     * @return   string               JavaScript code
     */
    private function add_english_form_javascript($dolls, $usd_rate) {
        ob_start();
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize variables for English form
            var shipping_cost_usd = 0;
            var subtotal_usd = 0;

            // Doll prices in USD
            var doll_prices_usd = {
                <?php foreach ($dolls as $doll):
                    $usd_price = isset($doll->price_usd) ? $doll->price_usd : ($doll->price * $usd_rate);
                ?>
                '<?php echo esc_js(strtolower($doll->name)); ?>': <?php echo $usd_price; ?>,
                <?php endforeach; ?>
            };

            // Update quantity and calculate total
            function updateQuantityEn(dollName, change) {
                var qtyInput = $('#' + dollName + '_qty_en');
                var hiddenInput = $('input[name="' + dollName + '_qty"]');
                var currentQty = parseInt(qtyInput.val()) || 0;
                var newQty = Math.max(0, currentQty + change);

                qtyInput.val(newQty);
                hiddenInput.val(newQty);
                calculateSubtotalEn();

                // Update display
                if (newQty > 0) {
                    $('#' + dollName + '_summary_en').show();
                    $('#' + dollName + '_summary_qty_en').text(newQty);
                    $('#' + dollName + '_summary_price_en').text(formatUSD(newQty * doll_prices_usd[dollName]));
                } else {
                    $('#' + dollName + '_summary_en').hide();
                }
            }

            // Calculate subtotal in USD
            function calculateSubtotalEn() {
                subtotal_usd = 0;
                var itemsSummary = '';
                var hasItems = false;
                var firstSelectedPrice = 0;

                $.each(doll_prices_usd, function(dollName, price) {
                    var qty = parseInt($('#' + dollName + '_qty_en').val()) || 0;
                    if (qty > 0) {
                        subtotal_usd += qty * price;
                        itemsSummary += '<div>' + dollName.charAt(0).toUpperCase() + dollName.slice(1) + ' × ' + qty + ' = ' + formatUSD(qty * price) + '</div>';
                        hasItems = true;
                        if (!firstSelectedPrice) {
                            firstSelectedPrice = price;
                        }
                    }
                });

                if ($('input[name="order_flow_type"]:checked').val() === 'self_plus_donation' && firstSelectedPrice > 0) {
                    subtotal_usd += firstSelectedPrice;
                    itemsSummary += '<div>Donation copy × 1 = ' + formatUSD(firstSelectedPrice) + '</div>';
                    hasItems = true;
                }

                if (hasItems) {
                    $('#items_summary_en').html(itemsSummary);
                } else {
                    $('#items_summary_en').html('<span style="color: #666; font-style: italic;">Select slow lorises above to see summary</span>');
                }

                $('#subtotal_display_en').text(formatUSD(subtotal_usd));
                calculateTotalEn();
            }

            // Calculate total including shipping
            function calculateTotalEn() {
                var total = subtotal_usd + shipping_cost_usd;
                $('#total_display_en').text(formatUSD(total));
            }

            // Format USD currency
            function formatUSD(amount) {
                return '$' + amount.toFixed(2);
            }

            // Quantity button handlers
            $('.qty-btn').on('click', function() {
                var dollName = $(this).data('doll');
                var change = $(this).hasClass('plus') ? 1 : -1;
                updateQuantityEn(dollName, change);
            });

            // Manual quantity input handler
            $('.qty-input').on('change', function() {
                var dollName = $(this).attr('id').replace('_qty_en', '');
                var qty = Math.max(0, parseInt($(this).val()) || 0);
                var hiddenInput = $('input[name="' + dollName + '_qty"]');

                $(this).val(qty);
                hiddenInput.val(qty);

                if (qty > 0) {
                    $('#' + dollName + '_summary_en').show();
                    $('#' + dollName + '_summary_qty_en').text(qty);
                    $('#' + dollName + '_summary_price_en').text(formatUSD(qty * doll_prices_usd[dollName]));
                } else {
                    $('#' + dollName + '_summary_en').hide();
                }

                calculateSubtotalEn();
            });

            // City search autocomplete for English form
            $('#city_name_en').on('input', function() {
                var query = $(this).val();
                if (query.length >= 2) {
                    $.ajax({
                        url: yiari_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_biteship_cities',
                            query: query,
                            nonce: yiari_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                var cities = response.data;
                                var suggestions = '';
                                $.each(cities, function(index, city) {
                                    suggestions += '<div class="city-suggestion" data-city-id="' + city.id + '" data-city-name="' + city.name + '" style="padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;">' + city.name + '</div>';
                                });
                                $('#city_suggestions_en').html(suggestions).show();
                            }
                        }
                    });
                } else {
                    $('#city_suggestions_en').hide();
                }
            });

            // City selection handler for English form
            $(document).on('click', '#city_suggestions_en .city-suggestion', function() {
                var cityName = $(this).data('city-name');
                var cityId = $(this).data('city-id');

                $('#city_name_en').val(cityName);
                $('#city_id_en').val(cityId);
                $('#city_suggestions_en').hide();

                // Calculate shipping cost
                calculateShippingCostEn();
            });

            // Calculate shipping cost in USD
            function calculateShippingCostEn() {
                var cityId = $('#city_id_en').val();
                var postalCode = $('#postal_code_en').val();

                if (cityId && postalCode && subtotal_usd > 0) {
                    $.ajax({
                        url: yiari_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'calculate_shipping_cost',
                            city_id: cityId,
                            postal_code: postalCode,
                            weight: calculateTotalWeightEn(),
                            nonce: yiari_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                var shipping_cost_idr = response.data.cost;
                                shipping_cost_usd = shipping_cost_idr * <?php echo $usd_rate; ?>;
                                $('#shipping_display_en').text(formatUSD(shipping_cost_usd));
                                calculateTotalEn();
                            }
                        }
                    });
                }
            }

            // Calculate total weight based on selected dolls
            function calculateTotalWeightEn() {
                var totalWeight = 0;
                $.each(doll_prices_usd, function(dollName, price) {
                    var qty = parseInt($('#' + dollName + '_qty_en').val()) || 0;
                    totalWeight += qty * 200; // Assume 200g per doll
                });
                return totalWeight;
            }

            // Postal code change handler
            $('#postal_code_en').on('change', function() {
                if ($(this).val().length >= 5) {
                    calculateShippingCostEn();
                }
            });

            // Form submission handler for English form
            $('#donasi-kukang-form-en').on('submit', function(e) {
                e.preventDefault();

                // Validate form
                var errors = [];

                // Check required fields
                if (!$('#customer_name_en').val().trim()) errors.push('Full name is required');
                if (!$('#email_en').val().trim()) errors.push('Email is required');
                if (!$('#phone_en').val().trim()) errors.push('Phone number is required');
                if (!$('#address_en').val().trim()) errors.push('Address is required');
                if (!$('#city_name_en').val().trim()) errors.push('City must be selected');
                if (!$('#postal_code_en').val().trim()) errors.push('Postal code is required');
                if (!$('#donation_motivation_code_en').val().trim()) errors.push('Donation motivation is required');
                if ($('#donation_motivation_code_en').val() === 'lainnya' && !$('#donation_motivation_other_en').val().trim()) errors.push('Please fill in the other donation reason');

                // Check if any dolls selected
                var hasDolls = false;
                $.each(doll_prices_usd, function(dollName, price) {
                    var qty = parseInt($('#' + dollName + '_qty_en').val()) || 0;
                    if (qty > 0) {
                        hasDolls = true;
                        return false;
                    }
                });

                if (!hasDolls) {
                    errors.push('Please select at least one slow loris to adopt');
                }

                if (errors.length > 0) {
                    alert('Error:\n' + errors.join('\n'));
                    return false;
                }

                // Show loading
                $('#submit-btn-en').prop('disabled', true).text('Processing...');

                // Submit form
                $.ajax({
                    url: yiari_ajax.ajax_url,
                    type: 'POST',
                    data: $(this).serialize() + '&action=process_donation_en&nonce=' + yiari_ajax.nonce,
                    success: function(response) {
                        if (response.success) {
                            // Redirect to payment page
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                alert('Donation processed successfully!');
                                location.reload();
                            }
                        } else {
                            alert('Error: ' + (response.data.message || 'An error occurred'));
                            $('#submit-btn-en').prop('disabled', false).text('🎁 Donate Now');
                        }
                    },
                    error: function() {
                        alert('Network error occurred. Please try again.');
                        $('#submit-btn-en').prop('disabled', false).text('🎁 Donate Now');
                    }
                });
            });

            // Initialize calculations
            calculateSubtotalEn();
            $('#donation_motivation_code_en').on('change', function() {
                if ($(this).val() === 'lainnya') {
                    $('#donation_motivation_other_wrap_en').show();
                } else {
                    $('#donation_motivation_other_wrap_en').hide();
                    $('#donation_motivation_other_en').val('');
                }
            }).trigger('change');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Add JavaScript functionality for Indonesian form
     *
     * @since    3.1.0
     * @param    array    $dolls    Available dolls
     * @return   string            JavaScript code
     */
    private function add_form_javascript($dolls) {
        ob_start();
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize variables
            var shipping_cost = 0;
            var subtotal = 0;

            // Doll prices
            var doll_prices = {
                <?php foreach ($dolls as $doll): ?>
                '<?php echo esc_js(strtolower($doll->name)); ?>': <?php echo intval($doll->price_idr); ?>,
                <?php endforeach; ?>
            };

            // Update quantity and calculate total
            function updateQuantity(dollName, change) {
                var qtyInput = $('#' + dollName + '_qty');
                var currentQty = parseInt(qtyInput.val()) || 0;
                var newQty = Math.max(0, currentQty + change);

                qtyInput.val(newQty);
                calculateSubtotal();

                // Update display
                if (newQty > 0) {
                    $('#' + dollName + '_summary').show();
                    $('#' + dollName + '_summary_qty').text(newQty);
                    $('#' + dollName + '_summary_price').text(formatIDR(newQty * doll_prices[dollName]));
                } else {
                    $('#' + dollName + '_summary').hide();
                }
            }

            // Calculate subtotal
            function calculateSubtotal() {
                subtotal = 0;
                var firstSelectedPrice = 0;
                $.each(doll_prices, function(dollName, price) {
                    var qty = parseInt($('#' + dollName + '_qty').val()) || 0;
                    subtotal += qty * price;
                    if (!firstSelectedPrice && qty > 0) {
                        firstSelectedPrice = price;
                    }
                });

                if ($('input[name="order_flow_type"]:checked').val() === 'self_plus_donation' && firstSelectedPrice > 0) {
                    subtotal += firstSelectedPrice;
                }

                $('#subtotal_display').text(formatIDR(subtotal));
                calculateTotal();
            }

            // Calculate total including shipping
            function calculateTotal() {
                var total = subtotal + shipping_cost;
                $('#total_display').text(formatIDR(total));
            }

            // Format IDR currency
            function formatIDR(amount) {
                return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            // Quantity button handlers
            $('.qty-btn').on('click', function() {
                var dollName = $(this).data('doll');
                var change = $(this).hasClass('plus') ? 1 : -1;
                updateQuantity(dollName, change);
            });

            $('input[name="order_flow_type"]').on('change', function() {
                calculateSubtotal();
            });

            // Manual quantity input handler
            $('.qty-input').on('change', function() {
                var dollName = $(this).attr('id').replace('_qty', '');
                var qty = Math.max(0, parseInt($(this).val()) || 0);
                $(this).val(qty);

                if (qty > 0) {
                    $('#' + dollName + '_summary').show();
                    $('#' + dollName + '_summary_qty').text(qty);
                    $('#' + dollName + '_summary_price').text(formatIDR(qty * doll_prices[dollName]));
                } else {
                    $('#' + dollName + '_summary').hide();
                }

                calculateSubtotal();
            });

            // City search autocomplete
            $('#city_name').on('input', function() {
                var query = $(this).val();
                if (query.length >= 2) {
                    $.ajax({
                        url: yiari_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'get_biteship_cities',
                            query: query,
                            nonce: yiari_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                var cities = response.data;
                                var suggestions = '';
                                $.each(cities, function(index, city) {
                                    suggestions += '<div class="city-suggestion" data-city-id="' + city.id + '" data-city-name="' + city.name + '">' + city.name + '</div>';
                                });
                                $('#city_suggestions').html(suggestions).show();
                            }
                        }
                    });
                } else {
                    $('#city_suggestions').hide();
                }
            });

            // City selection handler
            $(document).on('click', '.city-suggestion', function() {
                var cityName = $(this).data('city-name');
                var cityId = $(this).data('city-id');

                $('#city_name').val(cityName);
                $('#city_id').val(cityId);
                $('#city_suggestions').hide();

                // Calculate shipping cost
                calculateShippingCost();
            });

            // Calculate shipping cost
            function calculateShippingCost() {
                var cityId = $('#city_id').val();
                var postalCode = $('#postal_code').val();

                if (cityId && postalCode && subtotal > 0) {
                    $.ajax({
                        url: yiari_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'calculate_shipping_cost',
                            city_id: cityId,
                            postal_code: postalCode,
                            weight: calculateTotalWeight(),
                            nonce: yiari_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                shipping_cost = response.data.cost;
                                $('#shipping_display').text(formatIDR(shipping_cost));
                                calculateTotal();
                            }
                        }
                    });
                }
            }

            // Calculate total weight based on selected dolls
            function calculateTotalWeight() {
                var totalWeight = 0;
                $.each(doll_prices, function(dollName, price) {
                    var qty = parseInt($('#' + dollName + '_qty').val()) || 0;
                    totalWeight += qty * 200; // Assume 200g per doll
                });
                return totalWeight;
            }

            // Postal code change handler
            $('#postal_code').on('change', function() {
                if ($(this).val().length >= 5) {
                    calculateShippingCost();
                }
            });

            // Form submission handler
            $('#donasi-kukang-form').on('submit', function(e) {
                e.preventDefault();

                // Validate form
                var errors = [];

                // Check required fields
                if (!$('#customer_name').val().trim()) errors.push('Nama lengkap harus diisi');
                if (!$('#email').val().trim()) errors.push('Email harus diisi');
                if (!$('#phone').val().trim()) errors.push('Nomor telepon harus diisi');
                if (!$('#address').val().trim()) errors.push('Alamat harus diisi');
                if (!$('#city_name').val().trim()) errors.push('Kota harus dipilih');
                if (!$('#postal_code').val().trim()) errors.push('Kode pos harus diisi');

                // Check if any dolls selected
                var hasDolls = false;
                $.each(doll_prices, function(dollName, price) {
                    var qty = parseInt($('#' + dollName + '_qty').val()) || 0;
                    if (qty > 0) {
                        hasDolls = true;
                        return false;
                    }
                });

                if (!hasDolls) {
                    errors.push('Pilih minimal 1 boneka kukang untuk diadopsi');
                }

                if (errors.length > 0) {
                    alert('Kesalahan:\n' + errors.join('\n'));
                    return false;
                }

                // Show loading
                $('#submit-btn').prop('disabled', true).text('Memproses...');

                // Submit form
                $.ajax({
                    url: yiari_ajax.ajax_url,
                    type: 'POST',
                    data: $(this).serialize() + '&action=process_donation&nonce=' + yiari_ajax.nonce,
                    success: function(response) {
                        if (response.success) {
                            // Redirect to payment page
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                alert('Donasi berhasil diproses!');
                                location.reload();
                            }
                        } else {
                            alert('Error: ' + (response.data.message || 'Terjadi kesalahan'));
                            $('#submit-btn').prop('disabled', false).text('Donasi Sekarang');
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
                        $('#submit-btn').prop('disabled', false).text('Donasi Sekarang');
                    }
                });
            });

            // Initialize calculations
            calculateSubtotal();
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Save transaction to database (compatibility method)
     *
     * @since    3.1.0
     * @param    array    $donation_data     Donation data
     * @param    array    $payment_result    Payment result from Midtrans
     */
    public function save_transaction_to_database($donation_data, $payment_result) {
        global $wpdb;

        error_log("=== SAVING TRANSACTION TO DATABASE ===");
        error_log("Donation data: " . json_encode($donation_data));
        error_log("Payment result: " . json_encode($payment_result));

        // Prepare transaction data for database
        $transaction_data = array(
            'order_id' => $donation_data['order_id'],
            'snap_token' => $payment_result['snap_token'],
            'customer_name' => $donation_data['customer_name'],
            'email' => $donation_data['email'],
            'phone' => isset($donation_data['phone']) ? $donation_data['phone'] : '',
            'address' => isset($donation_data['address']) ? $donation_data['address'] : '',
            'city' => isset($donation_data['city_name']) ? $donation_data['city_name'] : '',
            'postal_code' => isset($donation_data['postal_code']) ? $donation_data['postal_code'] : '',
            'gross_amount' => $donation_data['gross_amount'],
            'subtotal' => isset($donation_data['subtotal']) ? $donation_data['subtotal'] : $donation_data['gross_amount'],
            'shipping_cost' => isset($donation_data['shipping_cost']) ? $donation_data['shipping_cost'] : 0,
            'currency' => isset($donation_data['currency']) ? $donation_data['currency'] : 'IDR',
            'donation_book_count' => intval($donation_data['donation_book_count'] ?? 0),
            'donation_motivation_code' => $donation_data['donation_motivation_code'] ?? '',
            'donation_motivation_other' => $donation_data['donation_motivation_other'] ?? '',
            'transaction_status' => 'pending',
            'created_at' => current_time('mysql')
        );

        // Add doll quantities if present
        $doll_names = array('regina', 'jagger', 'butros', 'eid', 'anoda');
        foreach ($doll_names as $doll_name) {
            $qty_key = $doll_name . '_qty';
            if (isset($donation_data[$qty_key])) {
                $transaction_data[$qty_key] = intval($donation_data[$qty_key]);
            }
        }

        // Add exchange rate for USD transactions
        if (isset($donation_data['exchange_rate'])) {
            $transaction_data['exchange_rate'] = $donation_data['exchange_rate'];
        }
        if (isset($donation_data['usd_amount'])) {
            $transaction_data['usd_amount'] = $donation_data['usd_amount'];
        }

        // Insert into database
        $table_name = $wpdb->prefix . 'kukang_transactions_new';
        $result = $wpdb->insert($table_name, $transaction_data);

        if ($result === false) {
            error_log("❌ Failed to save transaction to database: " . $wpdb->last_error);
            throw new Exception('Failed to save transaction to database: ' . $wpdb->last_error);
        } else {
            error_log("✅ Transaction saved successfully with ID: " . $wpdb->insert_id);
        }

        $order_service = new YIARI_Order_Service();
        $order_service->upsert_normalized_order($transaction_data, array(
            'payment_status' => 'pending_payment',
            'fulfillment_status' => 'draft',
            'payment_reference' => $payment_result['snap_token'] ?? null,
        ));
    }
}
?>
