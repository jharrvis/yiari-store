<?php

/**
 * Product repository with normalized-table-first reads and legacy fallback.
 */
class YIARI_Product_Repository {

    /**
     * Get active products for storefront rendering and order building.
     *
     * @return array
     */
    public function get_active_products() {
        global $wpdb;

        $products_table = $wpdb->prefix . 'yiari_products';
        $products = $wpdb->get_results(
            "SELECT * FROM {$products_table} WHERE status = 'active' ORDER BY sort_order ASC, id ASC"
        );

        if (!empty($products)) {
            return $products;
        }

        $legacy_table = $wpdb->prefix . 'kukang_dolls_new';
        return $wpdb->get_results(
            "SELECT
                id,
                name,
                description,
                price_idr,
                price_usd,
                weight_grams,
                length_cm,
                width_cm,
                height_cm,
                image_url,
                is_active
            FROM {$legacy_table}
            WHERE is_active = 1
            ORDER BY id ASC"
        );
    }

    /**
     * Get product names keyed by qty field format used in legacy forms.
     *
     * @return array
     */
    public function get_legacy_quantity_field_map() {
        $map = array();

        foreach ($this->get_active_products() as $product) {
            $map[strtolower($product->name) . '_qty'] = $product;
        }

        return $map;
    }

    /**
     * Get orderable products present in form data.
     *
     * @param array $form_data
     * @return array
     */
    public function get_selected_products_from_form($form_data) {
        $selected = array();

        foreach ($this->get_legacy_quantity_field_map() as $qty_key => $product) {
            $qty = isset($form_data[$qty_key]) ? intval($form_data[$qty_key]) : 0;

            if ($qty > 0) {
                $selected[] = array(
                    'product' => $product,
                    'qty' => $qty,
                    'qty_key' => $qty_key,
                );
            }
        }

        return $selected;
    }
}
?>
