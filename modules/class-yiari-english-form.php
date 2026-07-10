<?php

/**
 * English Form Handler for YIARI Donasi Kukang Plugin
 * 
 * This class handles the English donation form which matches the Indonesian form
 * but uses USD currency from API
 */
class YIARI_English_Form {
    
    /**
     * Initialize English form
     *
     * @since    3.1.0
     */
    public function initialize() {
        // Register shortcode
        add_shortcode('donasi_kukang_en', array($this, 'render_form'));
        
        // AJAX handlers
        add_action('wp_ajax_process_donation_en', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_process_donation_en', array($this, 'handle_form_submission'));
    }
    
    /**
     * Render English donation form
     *
     * @since    3.1.0
     * @return   string    Form HTML
     */
    public function render_form() {
        global $wpdb;
        $kukang_table = $wpdb->prefix . 'kukang_dolls';

        // Try to get dolls with new columns, fallback to basic columns if needed
        $dolls = $wpdb->get_results("SELECT name, price, weight_grams, length_cm, width_cm, height_cm, description FROM $kukang_table WHERE is_active = 1 ORDER BY id ASC");

        // If that failed (missing columns), try basic query
        if (!$dolls || $wpdb->last_error) {
            error_log("Kukang dolls query with new columns failed: " . $wpdb->last_error);
            error_log("Falling back to basic query...");
            $dolls = $wpdb->get_results("SELECT name, price FROM $kukang_table ORDER BY id ASC");

            // Add default values for missing properties
            if ($dolls) {
                foreach ($dolls as $doll) {
                    $doll->weight_grams = 150;
                    $doll->length_cm = 20;
                    $doll->width_cm = 15;
                    $doll->height_cm = 10;
                    $doll->description = '';
                }
            }
        }

        // If still no dolls, there's a bigger problem
        if (!$dolls) {
            error_log("No dolls found in database! Creating default data...");

            // Try to insert default data
            $default_dolls = array(
                array('name' => 'Regina', 'price' => 150000),
                array('name' => 'Jagger', 'price' => 150000),
                array('name' => 'Butros', 'price' => 150000),
                array('name' => 'Eid', 'price' => 150000),
                array('name' => 'Anoda', 'price' => 150000),
            );

            foreach ($default_dolls as $doll) {
                $wpdb->insert($kukang_table, $doll);
            }

            // Try query again
            $dolls = $wpdb->get_results("SELECT name, price FROM $kukang_table ORDER BY id ASC");
            if ($dolls) {
                foreach ($dolls as $doll) {
                    $doll->weight_grams = 150;
                    $doll->length_cm = 20;
                    $doll->width_cm = 15;
                    $doll->height_cm = 10;
                    $doll->description = '';
                }
            }
        }
        
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
        <h2 style="color: white; margin: 0 0 10px 0; font-size: 28px; font-weight: 300; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Donor Information</h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 16px; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Complete the following data for slow loris doll adoption process</p>
    </div>
    
    <form id="donasiKukangFormEn" class="donasi-form" style="max-width: 800px; margin: 0 auto;">
        <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 250px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Full Name</label>
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
                <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Email</label>
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
                <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Phone Number</label>
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
                <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Postal Code</label>
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
            <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">Complete Address</label>
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
                <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: white; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">City/District</label>
                <input type="text" 
                       id="citySearchEn" 
                       placeholder="Type at least 4 characters to search for city..." 
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
                " onfocus="this.style.borderColor='#3498db'; showSearchResultsEn()" onblur="setTimeout(() => hideSearchResultsEn(), 300)" oninput="debouncedSearchCitiesEn(this.value)" onkeydown="handleSearchKeydownEn(event)">
                
                <div id="citySearchResultsEn" style="
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
            <h3 style="text-align: center; color: white; font-size: 24px; font-weight: 300; margin-bottom: 20px; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Choose Slow Loris Dolls</h3>
            
            <div class="dolls-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px; max-width: 700px; margin: 0 auto;">
                <?php foreach ($dolls as $doll):
                    $usd_price = convert_idr_to_usd($doll->price);
                ?>
                <div class="doll-card" style="
                    text-align: center; 
                    padding: 20px;
                    border: 2px solid #f0f0f0;
                    border-radius: 12px;
                    background: #fff;
                    transition: all 0.3s ease;
                " onmouseover="this.style.borderColor='#3498db'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.borderColor='#f0f0f0'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <div style="font-weight: 600; margin-bottom: 8px; font-size: 16px; color: #2c3e50;"><?php echo esc_html($doll->name); ?></div>
                    <div style="font-size: 14px; color: #7f8c8d; margin-bottom: 15px;">$<?php echo number_format($usd_price, 2); ?></div>
                    
                    <div class="qty-control" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <button type="button" onclick="changeQtyEn('<?php echo strtolower($doll->name); ?>', -1)" style="
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
                        
                        <span class="qty-display-en" style="
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
                        
                        <button type="button" onclick="changeQtyEn('<?php echo strtolower($doll->name); ?>', 1)" style="
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
                        
                        <input type="hidden" name="<?php echo strtolower($doll->name); ?>_qty" value="0" class="qty-input-en">
                    </div>
                </div>
                <?php endforeach; ?>
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
                <span style="font-size: 16px; color: #495057;">Product Subtotal:</span>
                <span id="subtotalAmountEn" style="font-size: 16px; font-weight: 600; color: #2c3e50;">$0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span style="font-size: 16px; color: #495057;">Shipping Cost (JNE REG):</span>
                <span id="shippingAmountEn" style="font-size: 16px; font-weight: 600; color: #2c3e50;"><em>Select city first</em></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span style="font-size: 14px; color: #6c757d;">Total Weight:</span>
                <span id="totalWeightEn" style="font-size: 14px; color: #6c757d;">0 grams</span>
            </div>
            <hr style="border: none; border-top: 2px solid #dee2e6; margin: 15px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 20px; font-weight: 600; color: #2c3e50;">Total Payment:</span>
                <span id="totalAmountEn" style="font-size: 24px; font-weight: 700; color: #e74c3c;">$0.00</span>
            </div>
            <div style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                <small style="color: #0d47a1;">💳 Payment Method: Credit Card Only for USD Transactions</small>
            </div>
        </div>
        
        <div class="submit-section" style="text-align: center;">
            <button type="submit" style="
                background: linear-gradient(135deg, #2c5530 0%, #3d7c47 100%); 
                color: white; 
                border: none; 
                padding: 16px 48px; 
                border-radius: 8px; 
                font-size: 18px; 
                font-weight: 600; 
                cursor: pointer;
                transition: all 0.3s ease;
                min-width: 200px;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 25px rgba(44, 85, 48, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">ADOPT NOW</button>
        </div>
    </form>
    
    <script>
    const dollPricesEn = {
        <?php foreach ($dolls as $doll): 
            $usd_price = convert_idr_to_usd($doll->price);
        ?>
        '<?php echo strtolower($doll->name); ?>': <?php echo $usd_price; ?>,
        <?php endforeach; ?>
    };

    const dollWeightsEn = {
        <?php foreach ($dolls as $doll): ?>
        '<?php echo strtolower($doll->name); ?>': <?php echo intval($doll->weight_grams ?? 150); ?>,
        <?php endforeach; ?>
    };

    const dollSpecsEn = {
        <?php foreach ($dolls as $doll): ?>
        '<?php echo strtolower($doll->name); ?>': {
            name: '<?php echo esc_js($doll->name); ?>',
            price: <?php echo $doll->price; ?>,
            weight: <?php echo intval($doll->weight_grams ?? 150); ?>,
            length: <?php echo intval($doll->length_cm ?? 20); ?>,
            width: <?php echo intval($doll->width_cm ?? 15); ?>,
            height: <?php echo intval($doll->height_cm ?? 10); ?>,
            description: '<?php echo esc_js($doll->description ?? ''); ?>'
        },
        <?php endforeach; ?>
    };

    let citiesDataEn = [];
    let shippingServicesEn = [];
    let searchTimeoutEn = null;
    let selectedCityEn = null;
    const exchangeRateEn = <?php echo get_usd_exchange_rate(); ?>;
    
    // Load cities on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadCitiesEn();
    });
    
    function loadCitiesEn() {
        // Skip initial load since API requires query parameter
        addFallbackCitiesEn();
        return;

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_biteship_cities&query=jakarta'
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);

            let cities = null;

            // Try different response formats for backward compatibility
            if (data.success && data.data && Array.isArray(data.data)) {
                // Biteship/fallback format: {success: true, data: [...]}\n
                cities = data.data;
            } else if (data.success && data.data && data.data.data) {
                // Nested data format
                cities = data.data.data;
            } else if (data.success === false) {
                console.error('API Error:', data.data ? data.data.message : 'Unknown error');
            }

            if (cities && cities.length > 0) {
                citiesDataEn = cities.map(city => {
                    // Combine area_id with postal_code for new Biteship format
                    const baseAreaId = city.area_id || city.id;
                    const postalCode = city.postal_code || '';
                    const fullAreaId = postalCode ? `${baseAreaId}IDZ${postalCode}` : baseAreaId;

                    return {
                        area_id: fullAreaId, // New format with postal code
                        city_id: fullAreaId, // Keep for backward compatibility
                        city_name: city.city_name || city.name,
                        province: city.province || city.province_name,
                        type: city.type || 'City',
                        zone: city.zone || 1,
                        postal_code: postalCode,
                        display_name: city.display_name || ''
                    };
                });

                console.log('Found', citiesDataEn.length, 'cities from API');
            } else {
                console.error('No cities found in response from Biteship API');
                // Don't use fallback cities - force user to try different search
                document.getElementById('citySearchResultsEn').innerHTML = '<div style="color: red; padding: 10px;">City not found. Try a different search term.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading cities:', error);
            // Don't use fallback cities - show error message
            document.getElementById('citySearchResultsEn').innerHTML = '<div style="color: red; padding: 10px;">City search service is currently unavailable. Please try again later.</div>';
        });
    }
    
    function addFallbackCitiesEn() {
        console.log('Adding fallback cities');
        const fallbackCities = [
            // DKI Jakarta
            {area_id: '10001', city_name: 'Jakarta Selatan', province: 'DKI Jakarta', type: 'City', zone: 1, postal_code: '12100'},
            {area_id: '10002', city_name: 'Jakarta Pusat', province: 'DKI Jakarta', type: 'City', zone: 1, postal_code: '10000'},
            {area_id: '10003', city_name: 'Jakarta Utara', province: 'DKI Jakarta', type: 'City', zone: 1},
            {area_id: '10004', city_name: 'Jakarta Timur', province: 'DKI Jakarta', type: 'City', zone: 1},
            {area_id: '10005', city_name: 'Jakarta Barat', province: 'DKI Jakarta', type: 'City', zone: 1},

            // Jawa Barat
            {area_id: '10740', city_name: 'Bogor', province: 'Jawa Barat', type: 'City', zone: 1},
            {area_id: '11001', city_name: 'Bekasi', province: 'Jawa Barat', type: 'City', zone: 1},
            {area_id: '11101', city_name: 'Depok', province: 'Jawa Barat', type: 'City', zone: 1},
            {area_id: '10101', city_name: 'Bandung', province: 'Jawa Barat', type: 'City', zone: 1},
            {area_id: '24', city_name: 'Bandung Barat', province: 'Jawa Barat', type: 'Regency', zone: 1},
            {area_id: '32', city_name: 'Cimahi', province: 'Jawa Barat', type: 'City', zone: 1},
            {area_id: '78', city_name: 'Cirebon', province: 'Jawa Barat', type: 'City', zone: 2},
            {area_id: '79', city_name: 'Cirebon', province: 'Jawa Barat', type: 'Regency', zone: 2},
            {area_id: '430', city_name: 'Sukabumi', province: 'Jawa Barat', type: 'City', zone: 2},
            {area_id: '431', city_name: 'Sukabumi', province: 'Jawa Barat', type: 'Regency', zone: 2},
            {area_id: '446', city_name: 'Tasikmalaya', province: 'Jawa Barat', type: 'City', zone: 2},
            {area_id: '447', city_name: 'Tasikmalaya', province: 'Jawa Barat', type: 'Regency', zone: 2},
            {area_id: '22', city_name: 'Garut', province: 'Jawa Barat', type: 'Regency', zone: 2},
            {area_id: '455', city_name: 'Subang', province: 'Jawa Barat', type: 'Regency', zone: 2},
            {area_id: '456', city_name: 'Sumedang', province: 'Jawa Barat', type: 'Regency', zone: 2},
            {area_id: '21', city_name: 'Karawang', province: 'Jawa Barat', type: 'Regency', zone: 2},
            
            // Banten
            {area_id: '448', city_name: 'Tangerang', province: 'Banten', type: 'City', zone: 1},
            {area_id: '449', city_name: 'Tangerang Selatan', province: 'Banten', type: 'City', zone: 1},
            {area_id: '402', city_name: 'Serang', province: 'Banten', type: 'City', zone: 2},
            {area_id: '403', city_name: 'Serang', province: 'Banten', type: 'Regency', zone: 2},
            {area_id: '34', city_name: 'Cilegon', province: 'Banten', type: 'City', zone: 2},
            {area_id: '271', city_name: 'Pandeglang', province: 'Banten', type: 'Regency', zone: 2},
            {area_id: '221', city_name: 'Lebak', province: 'Banten', type: 'Regency', zone: 2},
            
            // Jawa Tengah
            {area_id: '419', city_name: 'Semarang', province: 'Jawa Tengah', type: 'City', zone: 2, postal_code: '50000'},
            {area_id: '420', city_name: 'Semarang', province: 'Jawa Tengah', type: 'Regency', zone: 2, postal_code: '50100'},
            {area_id: '406', city_name: 'Solo (Surakarta)', province: 'Jawa Tengah', type: 'City', zone: 2},
            {area_id: '428', city_name: 'Sukoharjo', province: 'Jawa Tengah', type: 'Regency', zone: 2},
            {area_id: '429', city_name: 'Sragen', province: 'Jawa Tengah', type: 'Regency', zone: 2},
            {area_id: '149', city_name: 'Boyolali', province: 'Jawa Tengah', type: 'Regency', zone: 2},
            {area_id: '206', city_name: 'Klaten', province: 'Jawa Tengah', type: 'Regency', zone: 2},
            {area_id: '450', city_name: 'Tegal', province: 'Jawa Tengah', type: 'City', zone: 3},
            {area_id: '451', city_name: 'Tegal', province: 'Jawa Tengah', type: 'Regency', zone: 3},
            {area_id: '273', city_name: 'Pati', province: 'Jawa Tengah', type: 'Regency', zone: 3},
            {area_id: '188', city_name: 'Jepara', province: 'Jawa Tengah', type: 'Regency', zone: 3},
            {area_id: '398', city_name: 'Salatiga', province: 'Jawa Tengah', type: 'City', zone: 2},
            {area_id: '210', city_name: 'Kudus', province: 'Jawa Tengah', type: 'Regency', zone: 3},
            {area_id: '244', city_name: 'Magelang', province: 'Jawa Tengah', type: 'City', zone: 2},
            {area_id: '243', city_name: 'Magelang', province: 'Jawa Tengah', type: 'Regency', zone: 2},
            
            // DI Yogyakarta
            {area_id: '497', city_name: 'Yogyakarta', province: 'DI Yogyakarta', type: 'City', zone: 2},
            {area_id: '25', city_name: 'Bantul', province: 'DI Yogyakarta', type: 'Regency', zone: 2},
            {area_id: '419', city_name: 'Sleman', province: 'DI Yogyakarta', type: 'Regency', zone: 2},
            {area_id: '218', city_name: 'Kulon Progo', province: 'DI Yogyakarta', type: 'Regency', zone: 2},
            {area_id: '116', city_name: 'Gunung Kidul', province: 'DI Yogyakarta', type: 'Regency', zone: 2},
            
            // Jawa Timur
            {area_id: '444', city_name: 'Surabaya', province: 'Jawa Timur', type: 'City', zone: 3},
            {area_id: '245', city_name: 'Malang', province: 'Jawa Timur', type: 'City', zone: 3},
            {area_id: '246', city_name: 'Malang', province: 'Jawa Timur', type: 'Regency', zone: 3},
            {area_id: '149', city_name: 'Batu', province: 'Jawa Timur', type: 'City', zone: 3},
            {area_id: '199', city_name: 'Kediri', province: 'Jawa Timur', type: 'City', zone: 3},
            {area_id: '200', city_name: 'Kediri', province: 'Jawa Timur', type: 'Regency', zone: 3},
            {area_id: '39', city_name: 'Blitar', province: 'Jawa Timur', type: 'City', zone: 3},
            {area_id: '40', city_name: 'Blitar', province: 'Jawa Timur', type: 'Regency', zone: 3},
            {area_id: '252', city_name: 'Mojokerto', province: 'Jawa Timur', type: 'City', zone: 3},
            {area_id: '268', city_name: 'Pasuruan', province: 'Jawa Timur', type: 'City', zone: 3},
            {area_id: '269', city_name: 'Pasuruan', province: 'Jawa Timur', type: 'Regency', zone: 3},
            {area_id: '186', city_name: 'Jember', province: 'Jawa Timur', type: 'Regency', zone: 3},
            
            // Sumatera Utara
            {area_id: '255', city_name: 'Medan', province: 'Sumatera Utara', type: 'City', zone: 4},
            {area_id: '141', city_name: 'Binjai', province: 'Sumatera Utara', type: 'City', zone: 4},
            {area_id: '106', city_name: 'Deli Serdang', province: 'Sumatera Utara', type: 'Regency', zone: 4},
            {area_id: '208', city_name: 'Karo', province: 'Sumatera Utara', type: 'Regency', zone: 4},
            {area_id: '275', city_name: 'Pematang Siantar', province: 'Sumatera Utara', type: 'City', zone: 4},
            {area_id: '409', city_name: 'Sibolga', province: 'Sumatera Utara', type: 'City', zone: 4},
            
            // Sumatera Barat
            {area_id: '270', city_name: 'Padang', province: 'Sumatera Barat', type: 'City', zone: 4},
            {area_id: '51', city_name: 'Bukittinggi', province: 'Sumatera Barat', type: 'City', zone: 4},
            {area_id: '1', city_name: 'Agam', province: 'Sumatera Barat', type: 'Regency', zone: 4},
            {area_id: '371', city_name: 'Padang Panjang', province: 'Sumatera Barat', type: 'City', zone: 4},
            
            // Riau
            {area_id: '272', city_name: 'Pekanbaru', province: 'Riau', type: 'City', zone: 4},
            {area_id: '112', city_name: 'Dumai', province: 'Riau', type: 'City', zone: 4},
            {area_id: '19', city_name: 'Bengkalis', province: 'Riau', type: 'Regency', zone: 4},
            
            // Kepulauan Riau
            {area_id: '18', city_name: 'Batam', province: 'Kepulauan Riau', type: 'City', zone: 4},
            {area_id: '455', city_name: 'Tanjung Pinang', province: 'Kepulauan Riau', type: 'City', zone: 4},
            
            // Sumatera Selatan
            {area_id: '369', city_name: 'Palembang', province: 'Sumatera Selatan', type: 'City', zone: 4},
            {area_id: '325', city_name: 'Prabumulih', province: 'Sumatera Selatan', type: 'City', zone: 4},
            {area_id: '253', city_name: 'Lubuk Linggau', province: 'Sumatera Selatan', type: 'City', zone: 4},
            
            // Lampung
            {area_id: '35', city_name: 'Bandar Lampung', province: 'Lampung', type: 'City', zone: 3},
            {area_id: '256', city_name: 'Metro', province: 'Lampung', type: 'City', zone: 3},
            {area_id: '217', city_name: 'Lampung Selatan', province: 'Lampung', type: 'Regency', zone: 3},
            
            // Bengkulu
            {area_id: '17', city_name: 'Bengkulu', province: 'Bengkulu', type: 'City', zone: 4},
            
            // Jambi
            {area_id: '182', city_name: 'Jambi', province: 'Jambi', type: 'City', zone: 4},
            
            // Bali
            {area_id: '61', city_name: 'Denpasar', province: 'Bali', type: 'City', zone: 3},
            {area_id: '12', city_name: 'Badung', province: 'Bali', type: 'Regency', zone: 3},
            {area_id: '115', city_name: 'Gianyar', province: 'Bali', type: 'Regency', zone: 3},
            {area_id: '447', city_name: 'Tabanan', province: 'Bali', type: 'Regency', zone: 3},
            
            // Nusa Tenggara Barat
            {area_id: '250', city_name: 'Mataram', province: 'Nusa Tenggara Barat', type: 'City', zone: 4},
            {area_id: '38', city_name: 'Bima', province: 'Nusa Tenggara Barat', type: 'City', zone: 5},
            {area_id: '225', city_name: 'Lombok Barat', province: 'Nusa Tenggara Barat', type: 'Regency', zone: 4},
            
            // Nusa Tenggara Timur
            {area_id: '89', city_name: 'Kupang', province: 'Nusa Tenggara Timur', type: 'City', zone: 5},
            
            // Kalimantan Barat
            {area_id: '328', city_name: 'Pontianak', province: 'Kalimantan Barat', type: 'City', zone: 4},
            {area_id: '415', city_name: 'Singkawang', province: 'Kalimantan Barat', type: 'City', zone: 4},
            
            // Kalimantan Tengah
            {area_id: '270', city_name: 'Palangka Raya', province: 'Kalimantan Tengah', type: 'City', zone: 5},
            
            // Kalimantan Selatan
            {area_id: '16', city_name: 'Banjarmasin', province: 'Kalimantan Selatan', type: 'City', zone: 4},
            {area_id: '15', city_name: 'Banjarbaru', province: 'Kalimantan Selatan', type: 'City', zone: 4},
            
            // Kalimantan Timur
            {area_id: '15', city_name: 'Balikpapan', province: 'Kalimantan Timur', type: 'City', zone: 4},
            {area_id: '393', city_name: 'Samarinda', province: 'Kalimantan Timur', type: 'City', zone: 4},
            {area_id: '47', city_name: 'Bontang', province: 'Kalimantan Timur', type: 'City', zone: 5},
            
            // Kalimantan Utara
            {area_id: '452', city_name: 'Tarakan', province: 'Kalimantan Utara', type: 'City', zone: 5},
            
            // Sulawesi Utara
            {area_id: '252', city_name: 'Manado', province: 'Sulawesi Utara', type: 'City', zone: 5},
            {area_id: '38', city_name: 'Bitung', province: 'Sulawesi Utara', type: 'City', zone: 5},
            {area_id: '454', city_name: 'Tomohon', province: 'Sulawesi Utara', type: 'City', zone: 5},
            
            // Sulawesi Selatan
            {area_id: '242', city_name: 'Makassar', province: 'Sulawesi Selatan', type: 'City', zone: 4},
            {area_id: '372', city_name: 'Parepare', province: 'Sulawesi Selatan', type: 'City', zone: 5},
            {area_id: '118', city_name: 'Gowa', province: 'Sulawesi Selatan', type: 'Regency', zone: 4},
            {area_id: '243', city_name: 'Maros', province: 'Sulawesi Selatan', type: 'Regency', zone: 4},
            
            // Sulawesi Tenggara
            {area_id: '205', city_name: 'Kendari', province: 'Sulawesi Tenggara', type: 'City', zone: 5},
            {area_id: '14', city_name: 'Bau-Bau', province: 'Sulawesi Tenggara', type: 'City', zone: 6},
            
            // Gorontalo
            {area_id: '114', city_name: 'Gorontalo', province: 'Gorontalo', type: 'City', zone: 5},
            
            // Sulawesi Barat
            {area_id: '239', city_name: 'Mamuju', province: 'Sulawesi Barat', type: 'Regency', zone: 6},
            
            // Maluku
            {area_id: '3', city_name: 'Ambon', province: 'Maluku', type: 'City', zone: 6},
            
            // Maluku Utara
            {area_id: '454', city_name: 'Ternate', province: 'Maluku Utara', type: 'City', zone: 6},
            {area_id: '455', city_name: 'Tidore Kepulauan', province: 'Maluku Utara', type: 'City', zone: 6},
            
            // Papua Barat
            {area_id: '251', city_name: 'Manokwari', province: 'Papua Barat', type: 'Regency', zone: 7},
            {area_id: '425', city_name: 'Sorong', province: 'Papua Barat', type: 'City', zone: 7},
            
            // Papua
            {area_id: '185', city_name: 'Jayapura', province: 'Papua', type: 'City', zone: 7},
            {area_id: '258', city_name: 'Merauke', province: 'Papua', type: 'Regency', zone: 7}
        ];
        
        // Store fallback cities in the global citiesDataEn
        citiesDataEn = fallbackCities;
        
        // API maintenance notice removed
        
        console.log('Loaded', citiesDataEn.length, 'fallback cities');
    }
    
    // Debounced search to prevent too many API calls
    function debouncedSearchCitiesEn(query) {
        if (searchTimeoutEn) {
            clearTimeout(searchTimeoutEn);
        }
        searchTimeoutEn = setTimeout(() => {
            searchCitiesEn(query);
        }, 300);
    }

    // Live search functions
    function searchCitiesEn(query) {
        const resultsDiv = document.getElementById('citySearchResultsEn');

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
        loadingItem.textContent = 'Searching cities...';
        resultsDiv.appendChild(loadingItem);
        resultsDiv.style.display = 'block';

        // Search using Biteship API
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_biteship_cities&query=' + encodeURIComponent(query)
        })
        .then(response => response.json())
        .then(data => {
            resultsDiv.innerHTML = ''; // Clear loading

            if (data.success && data.data && data.data.data && data.data.data.length > 0) {
                const cities = data.data.data;
                cities.slice(0, 10).forEach(city => { // Show max 10 results
                    displayCityResultEn(city, resultsDiv);
                });
                resultsDiv.style.display = 'block';
            } else {
                // Fallback to local search
                searchCitiesLocalEn(query, resultsDiv);
            }
        })
        .catch(error => {
            console.error('City search API error:', error);
            // Fallback to local search
            searchCitiesLocalEn(query, resultsDiv);
        });
    }

    function searchCitiesLocalEn(query, resultsDiv) {
        resultsDiv.innerHTML = ''; // Clear previous results

        // Filter cities based on query
        const filteredCities = citiesDataEn.filter(city => {
            const cityName = city.city_name.toLowerCase();
            const province = city.province.toLowerCase();
            const type = city.type.toLowerCase();
            const searchQuery = query.toLowerCase();

            return cityName.includes(searchQuery) ||
                   province.includes(searchQuery) ||
                   (type + ' ' + cityName).includes(searchQuery);
        });
        
        // Show results
        if (filteredCities.length > 0) {
            filteredCities.slice(0, 10).forEach(city => { // Show max 10 results
                displayCityResultEn(city, resultsDiv);
            });
            
            resultsDiv.style.display = 'block';
        } else {
            const noResultItem = document.createElement('div');
            noResultItem.style.cssText = 'padding: 10px 15px; color: #666; font-style: italic;';
            noResultItem.textContent = 'No cities found';
            resultsDiv.appendChild(noResultItem);
            resultsDiv.style.display = 'block';
        }
    }

    function displayCityResultEn(city, resultsDiv) {
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
            selectCityEn(city);
        });

        resultsDiv.appendChild(resultItem);
    }

    function selectCityEn(city) {
        // DEBUG: Log the selected city
        console.log('🏙️ Selected city object:', city);
        console.log('🆔 area_id:', city.area_id);
        console.log('🆔 city_id:', city.city_id);
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
        window.selectedCityEn = {
            ...city,
            area_id: finalAreaId,  // Use the combined area_id with postal code
            city_id: finalAreaId   // Keep for backward compatibility
        };

        // Update search input with the display name from API
        document.getElementById('citySearchEn').value = city.display_name || city.full_name;

        document.querySelector('input[name="city_id"]').value = finalAreaId;
        document.querySelector('input[name="city_name"]').value = city.city_name;
        document.querySelector('input[name="province"]').value = city.province;

        // Auto-fill postal code if available
        if (city.postal_code) {
            document.querySelector('input[name="postal_code"]').value = city.postal_code;
        }

        console.log('🎯 City selected:', {
            area_id: city.area_id,
            display_name: city.display_name,
            postal_code: city.postal_code
        });

        // Hide search results immediately
        setTimeout(() => {
            hideSearchResultsEn();
        }, 100);

        console.log('🚚 About to load shipping options for city change...');

        // Load shipping options for selected city
        loadShippingOptionsEn(city);

        // Also trigger immediate shipping recalculation to ensure it updates
        setTimeout(() => {
            console.log('🔄 Double-checking shipping calculation after city selection...');
            if (window.selectedCityEn) {
                const currentWeight = calculateTotalWeightEn();
                console.log('⚖️ Current weight for recalculation:', currentWeight + 'g');

                if (currentWeight > 0 || true) { // Always recalculate even with 0 items (minimum 1kg)
                    console.log('🔄 Triggering shipping recalculation due to city change');
                    loadShippingOptionsEn(window.selectedCityEn);
                }
            }
        }, 200);
    }
    
    function showSearchResultsEn() {
        const query = document.getElementById('citySearchEn').value;
        if (query.length >= 4) {
            document.getElementById('citySearchResultsEn').style.display = 'block';
        }
    }
    
    function hideSearchResultsEn() {
        document.getElementById('citySearchResultsEn').style.display = 'none';
    }

    function handleSearchKeydownEn(event) {
        const resultsDiv = document.getElementById('citySearchResultsEn');
        const items = resultsDiv.querySelectorAll('div');

        if (event.key === 'Escape') {
            hideSearchResultsEn();
            event.target.blur();
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const activeItem = resultsDiv.querySelector('.active');
            if (activeItem) {
                activeItem.click();
            }
        }
    }
    
    function loadShippingOptionsEn(selectedCity) {
        if (!selectedCity) return;

        // Use the area_id which should already include postal code (from selectCityEn function)
        const cityId = selectedCity.area_id || selectedCity.city_id;
        console.log('🏙️ loadShippingOptionsEn - selectedCity:', selectedCity);
        console.log('🆔 loadShippingOptionsEn - cityId extracted:', cityId);
        console.log('🔍 selectedCity.area_id:', selectedCity.area_id);
        console.log('🔍 selectedCity.city_id:', selectedCity.city_id);

        if (!cityId) {
            console.error('❌ No cityId found in selectedCity');
            return;
        }

        // Calculate total weight
        const totalWeight = calculateTotalWeightEn();

        // Show loading message
        document.getElementById('shippingAmountEn').innerHTML = '<em>Calculating shipping cost...</em>';

        // Get shipping cost using Biteship API with cache busting
        const timestamp = Date.now();
        console.log(`🚀 Loading shipping for ${totalWeight}g to ${cityId} (timestamp: ${timestamp})`);

        // Check area ID format before sending
        const hasPostalCode = cityId.includes('IDZ');
        console.log('📮 Area ID has postal code (IDZ):', hasPostalCode);

        if (!hasPostalCode) {
            console.warn('⚠️ WARNING: Area ID missing postal code suffix (IDZ)');
        }

        // Use area_id method for Biteship API
        console.log(`🗺️ Using area ID method: ${cityId}`);
        const requestBody = `action=calculate_shipping_cost&destination_area_id=${cityId}&weight=${totalWeight}&timestamp=${timestamp}`;
        console.log('📤 Request body:', requestBody);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
        .then(response => response.json())
        .then(data => {
            console.log(`💬 API Response for ${totalWeight}g:`, data);
            console.log('🔍 Response structure check:');
            console.log('  data.success:', data.success);
            console.log('  data.data:', data.data);
            console.log('  data.data.pricing:', data.data ? data.data.pricing : 'N/A');
            console.log('  data.data.pricing.value:', data.data && data.data.pricing ? data.data.pricing.value : 'N/A');

            if (data.success && data.data && data.data.pricing) {
                // Biteship response format
                const shippingCost = parseInt(data.data.pricing.value) || 0;
                const service = data.data.service || 'REG';
                const description = data.data.description || 'Regular Service';

                // Convert shipping cost to USD using exchange rate
                const shippingCostUSD = shippingCost * exchangeRateEn;

                console.log('✅ Response validation passed');
                console.log('💰 Parsed shipping cost:', shippingCost, '(IDR)');
                console.log('💰 Parsed shipping cost (USD):', shippingCostUSD, '(USD)');
                console.log('🚚 Service:', service);
                console.log('📝 Description:', description);

                document.querySelector('input[name="shipping_cost"]').value = shippingCost; // Keep in IDR for Midtrans
                document.querySelector('input[name="courier_service"]').value = service;
                document.getElementById('shippingAmountEn').innerHTML =
                    `<strong>$${shippingCostUSD.toFixed(2)}</strong><br><small>${description}</small>`;
                updateTotalEn();
            } else {
                console.error('❌ Response validation FAILED!');
                console.error('  data.success:', data.success);
                console.error('  data.data exists:', !!data.data);
                console.error('  data.data.pricing exists:', !!(data.data && data.data.pricing));
                console.error('  Full response:', data);
                console.error('No shipping cost data received from Biteship API');
                document.getElementById('shippingAmountEn').innerHTML = '<strong style="color: red;">Shipping service unavailable</strong><br><small>Please try again later</small>';
                document.querySelector('input[name="shipping_cost"]').value = 0;
                document.querySelector('input[name="courier_service"]').value = '';
                updateTotalEn();
            }
        })
        .catch(error => {
            console.error('❌ Error calculating shipping cost:', error);
            console.error('🌐 Network error or API failure for city:', selectedCity);

            // Try one more time after a brief delay
            setTimeout(() => {
                console.log('🔄 Retrying shipping calculation after error...');
                loadShippingOptionsEn(selectedCity);
            }, 2000);

            document.getElementById('shippingAmountEn').innerHTML = '<strong style="color: orange;">Retrying...</strong><br><small>Re-calculating shipping</small>';

            // Don't set shipping cost to 0 immediately, keep current value
            // document.querySelector('input[name="shipping_cost"]').value = 0;
            // document.querySelector('input[name="courier_service"]').value = '';
            updateTotalEn();
        });
    }
    
    // Shipping calculation debounce timeout
    let shippingCalculationTimeoutEn = null;

    function calculateTotalWeightEn() {
        let totalWeight = 0;
        Object.keys(dollSpecsEn).forEach(dollName => {
            const qty = parseInt(document.querySelector(`input[name="${dollName}_qty"]`).value) || 0;
            const weight = dollSpecsEn[dollName].weight; // Use dynamic weight from database
            totalWeight += qty * weight;
        });

        return Math.max(totalWeight, 1000); // Minimum 1kg
    }

    function debouncedShippingCalculationEn(selectedCity) {
        if (shippingCalculationTimeoutEn) {
            clearTimeout(shippingCalculationTimeoutEn);
        }

        // Show loading indicator immediately
        const shippingElement = document.getElementById('shippingAmountEn');
        if (shippingElement) {
            shippingElement.innerHTML = '<em>Calculating shipping cost...</em>';
        }

        shippingCalculationTimeoutEn = setTimeout(() => {
            loadShippingOptionsEn(selectedCity);
        }, 300); // 300ms debounce
    }
    
    function calculateZoneBasedShippingEn(zone, weightInKg) {
        // Base shipping rates per zone (in Rupiah per kg)
        const zoneRates = {
            1: 8000,   // Jakarta, Bogor, Bekasi, Depok, Tangerang (Jabodetabek)
            2: 12000,  // Jawa Barat, Banten, Jawa Tengah, DI Yogyakarta (sekitar Jawa)
            3: 15000,  // Jawa Timur, Bali, Lampung (Jawa Timur & sekitarnya)
            4: 20000,  // Sumatra, Kalimantan Selatan, Sulawesi Selatan (pulau besar)
            5: 25000,  // Kalimantan lain, Sulawesi lain, NTB (pulau sedang)
            6: 35000,  // Maluku, Sulawesi Barat (pulau jauh)
            7: 50000   // Papua (pulau terjauh)
        };
        
        const baseRate = zoneRates[zone] || zoneRates[1];
        const totalCost = baseRate * weightInKg;
        
        // Minimum shipping cost based on zone
        const minimumCosts = {
            1: 10000, 2: 15000, 3: 18000, 4: 25000, 5: 30000, 6: 40000, 7: 60000
        };
        
        return Math.max(totalCost, minimumCosts[zone] || minimumCosts[1]);
    }
    
    function changeQtyEn(dollName, change) {
        const qtyInput = document.querySelector(`input[name="${dollName}_qty"]`);
        const qtyDisplay = qtyInput.parentElement.querySelector('.qty-display-en');
        let currentQty = parseInt(qtyInput.value) || 0;

        currentQty = Math.max(0, currentQty + change);

        qtyInput.value = currentQty;
        qtyDisplay.textContent = currentQty;

        const newTotalWeight = calculateTotalWeightEn();
        console.log(`📊 Quantity changed - ${dollName}: ${currentQty}`);
        console.log(`⚖️ New total weight: ${newTotalWeight}g`);

        // Update subtotal immediately (before shipping calculation)
        updateSubtotalOnlyEn();

        // Reload shipping options if city is selected (using debounced calculation)
        const cityIdInput = document.querySelector('input[name="city_id"]');
        if (cityIdInput && cityIdInput.value) {
            console.log(`🔄 Quantity changed, recalculating shipping for city: ${cityIdInput.value}`);

            // Find the selected city from citiesDataEn
            const selectedCity = citiesDataEn.find(city => (city.city_id === cityIdInput.value || city.area_id === cityIdInput.value));
            if (selectedCity) {
                console.log(`🏴 Found selected city:`, selectedCity);
                debouncedShippingCalculationEn(selectedCity);
            } else {
                // Try to find by area_id from global storage
                if (window.selectedCityEn) {
                    console.log(`🗺️ Using stored city:`, window.selectedCityEn);
                    debouncedShippingCalculationEn(window.selectedCityEn);
                } else {
                    console.log(`⚠️ Selected city not found in citiesData`);
                    updateTotalEn(); // Fallback: update total without shipping recalculation
                }
            }
        } else {
            console.log(`⚠️ No city selected for shipping recalculation`);
            updateTotalEn(); // Update total without shipping cost change
        }
    }

    function updateSubtotalOnlyEn() {
        let subtotal = 0;
        let totalWeight = 0;

        Object.keys(dollSpecsEn).forEach(dollName => {
            const qty = parseInt(document.querySelector(`input[name="${dollName}_qty"]`).value) || 0;
            // Convert IDR price to USD
            const usdPrice = dollSpecsEn[dollName].price * exchangeRateEn;
            subtotal += qty * usdPrice;
            totalWeight += qty * dollSpecsEn[dollName].weight; // Use dynamic weight
        });

        const displayWeight = Math.max(totalWeight, 1000);

        // Update subtotal and weight display immediately
        document.getElementById('subtotalAmountEn').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('totalWeightEn').textContent = displayWeight + ' grams';
    }
    
    function updateTotalEn() {
        let subtotal = 0;
        let totalWeight = 0;

        Object.keys(dollSpecsEn).forEach(dollName => {
            const qty = parseInt(document.querySelector(`input[name="${dollName}_qty"]`).value) || 0;
            // Convert IDR price to USD
            const usdPrice = dollSpecsEn[dollName].price * exchangeRateEn;
            subtotal += qty * usdPrice;
            totalWeight += qty * dollSpecsEn[dollName].weight; // Use dynamic weight
        });

        // Get shipping cost in IDR from hidden field, convert to USD
        const shippingCostIdr = parseFloat(document.querySelector('input[name="shipping_cost"]').value) || 0;
        const shippingCostUsd = shippingCostIdr * exchangeRateEn;
        const total = subtotal + shippingCostUsd;
        const displayWeight = Math.max(totalWeight, 1000);

        document.getElementById('subtotalAmountEn').textContent = '$' + subtotal.toFixed(2);

        // DON'T overwrite shippingAmount if it has rich HTML content from loadShippingOptions
        const shippingElement = document.getElementById('shippingAmountEn');
        const currentShippingHTML = shippingElement.innerHTML;

        // Only update if it's just a plain text or needs updating due to cost change
        if (!currentShippingHTML.includes('<strong>') && !currentShippingHTML.includes('<small>')) {
            // Simple text content, safe to update
            shippingElement.textContent = '$' + shippingCostUsd.toFixed(2);
        } else {
            // Rich HTML content from shipping calculation, preserve the formatting but update cost
            const courierService = document.querySelector('input[name="courier_service"]').value || 'REG';
            const description = currentShippingHTML.includes('Regular Service') ? 'Regular Service' : courierService;
            shippingElement.innerHTML = `<strong>$${shippingCostUsd.toFixed(2)}</strong><br><small>${description}</small>`;
        }

        document.getElementById('totalAmountEn').textContent = '$' + total.toFixed(2);
        document.getElementById('totalWeightEn').textContent = displayWeight + ' grams';
    }

    // Form submission handler - inline to ensure it loads
    function handleFormSubmitEn(event) {
        event.preventDefault();

        console.log('🚀 English form submission started');

        // Validate that at least one doll is selected
        let hasSelection = false;
        const form = document.getElementById('donasiKukangFormEn');
        const qtyInputs = form.querySelectorAll('input[name$="_qty"]');

        for (let input of qtyInputs) {
            if (parseInt(input.value) > 0) {
                hasSelection = true;
                break;
            }
        }

        if (!hasSelection) {
            alert('Please select at least 1 slow loris doll to adopt.');
            return false;
        }

        // Prepare form data
        const formData = new FormData(form);

        // Add language and currency info
        formData.append('language', 'en');
        formData.append('currency', 'USD');
        formData.append('exchange_rate', exchangeRateEn);

        // DEBUG: Log form data being sent
        console.log('=== ENGLISH FORM SUBMISSION DEBUG ===');
        console.log('Form data being sent:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }

        // Add action to form data
        formData.append('action', 'process_donation_en');

        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');

        xhr.onload = function() {
            if (xhr.status === 200) {
                const responseText = xhr.responseText.trim();
                console.log('Server response:', responseText);

                // Try to parse as JSON first (for error responses)
                try {
                    const jsonResponse = JSON.parse(responseText);

                    if (jsonResponse.success === false) {
                        // Handle validation errors
                        console.error('Form validation failed:', jsonResponse);

                        let errorMessage = jsonResponse.data.message || 'Form not valid';
                        if (jsonResponse.data.errors && Array.isArray(jsonResponse.data.errors)) {
                            errorMessage += '\n\n' + jsonResponse.data.errors.map(error => '• ' + error).join('\n');
                        }

                        alert(errorMessage);
                        return;
                    }

                    // If JSON parsing succeeds but it's not an error, expect success response
                    if (jsonResponse.success && jsonResponse.data && jsonResponse.data.snap_token) {
                        console.log('Valid Snap Token received from English form:', jsonResponse.data.snap_token.substring(0, 20) + '...');

                        // Open Midtrans Snap
                        snap.pay(jsonResponse.data.snap_token, {
                            onSuccess: function(result) {
                                alert('Thank you! Your donation was successful.');
                                location.reload();
                            },
                            onPending: function(result) {
                                alert('Payment is being processed.');
                                location.reload();
                            },
                            onError: function(result) {
                                alert('There was an error in payment.');
                            },
                            onClose: function() {
                                console.log('Payment popup closed');
                            }
                        });
                    } else {
                        alert('There was an error. Response format invalid. Please try again.');
                        console.error('Invalid response received:', jsonResponse);
                    }

                } catch (e) {
                    // Response is not JSON, treat as error
                    alert('There was an error in server response. Please try again.');
                    console.error('Invalid response received:', e, responseText);
                }
            } else {
                alert('There was a server error. Please try again.');
                console.error('XHR Error:', xhr.status, xhr.responseText);
            }
        };

        xhr.onerror = function() {
            alert('There was a network error. Please try again.');
            console.error('XHR Network Error');
        };

        // Send the request
        xhr.send(formData);

        return false; // Prevent default form submission
    }

    // Bind form submission handler using addEventListener for better reliability
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('donasiKukangFormEn');
        if (form) {
            console.log('🔧 Binding English form submission handler via addEventListener');

            // Add multiple layers of prevention
            form.addEventListener('submit', function(event) {
                console.log('🚀 English form submit event triggered via addEventListener');
                event.preventDefault();
                event.stopPropagation();

                // Call the main handler
                try {
                    handleFormSubmitEn(event);
                } catch (error) {
                    console.error('❌ Error in handleFormSubmitEn:', error);
                }

                return false;
            }, true); // Use capture phase

            // Also bind to the submit button directly for extra safety
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.addEventListener('click', function(event) {
                    console.log('🔘 Submit button clicked');
                    event.preventDefault();
                    event.stopPropagation();

                    // Directly trigger the form submission handler
                    try {
                        handleFormSubmitEn(event);
                    } catch (error) {
                        console.error('❌ Error in handleFormSubmitEn from button:', error);
                    }

                    return false;
                });
            }
        } else {
            console.error('❌ English form not found for binding submission handler');
        }

        // Log if any JavaScript errors occur
        window.addEventListener('error', function(event) {
            console.error('❌ JavaScript Error:', event.error);
        });
    });
    </script>
</div>
<?php
    return ob_get_clean();
    }
}