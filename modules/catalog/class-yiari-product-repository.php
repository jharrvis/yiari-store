<?php

/**
 * Product repository with normalized-table-first reads and legacy fallback.
 */
class YIARI_Product_Repository {

    /**
     * Get the normalized products table name.
     *
     * @return string
     */
    private function get_products_table() {
        global $wpdb;

        return $wpdb->prefix . 'yiari_products';
    }

    /**
     * Get all normalized products for admin management.
     *
     * @return array
     */
    public function get_all_products() {
        global $wpdb;

        $products_table = $this->get_products_table();
        return $wpdb->get_results("SELECT * FROM {$products_table} ORDER BY sort_order ASC, id ASC");
    }

    /**
     * Get one normalized product by primary key.
     *
     * @param int $product_id Product ID.
     * @return object|null
     */
    public function get_product_by_id($product_id) {
        global $wpdb;

        $products_table = $this->get_products_table();
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$products_table} WHERE id = %d LIMIT 1", intval($product_id))
        );
    }

    /**
     * Get active products for storefront rendering and order building.
     *
     * @return array
     */
    public function get_active_products() {
        global $wpdb;

        $products_table = $this->get_products_table();
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

    /**
     * Create or update a normalized product.
     *
     * @param array $product_data Sanitized product data.
     * @param int   $product_id   Optional existing product ID.
     * @return array
     */
    public function save_product($product_data, $product_id = 0) {
        global $wpdb;

        $products_table = $this->get_products_table();
        $product_id = intval($product_id);
        $existing_product = $product_id > 0 ? $this->get_product_by_id($product_id) : null;

        $name = sanitize_text_field($product_data['name'] ?? '');
        if ('' === $name) {
            return array(
                'success' => false,
                'message' => 'Nama produk wajib diisi.',
            );
        }

        $base_slug = sanitize_title($product_data['slug'] ?? $name);
        if ('' === $base_slug) {
            $base_slug = 'product-' . time();
        }

        $slug = $this->get_unique_slug($base_slug, $product_id);

        $base_sku = sanitize_text_field($product_data['sku'] ?? '');
        if ('' === $base_sku) {
            $base_sku = 'YIARI-' . strtoupper(str_replace('-', '-', $base_slug));
        }

        $sku = $this->get_unique_sku($base_sku, $product_id);
        $price_idr = floatval($product_data['price_idr'] ?? 0);
        $price_usd = floatval($product_data['price_usd'] ?? 0);

        if ($price_usd <= 0 && $price_idr > 0) {
            $price_usd = round($price_idr * 0.000067, 2);
        }

        $row = array(
            'sku' => $sku,
            'slug' => $slug,
            'name' => $name,
            'product_type' => sanitize_text_field($product_data['product_type'] ?? 'physical'),
            'description' => sanitize_textarea_field($product_data['description'] ?? ''),
            'price_idr' => $price_idr,
            'price_usd' => $price_usd,
            'weight_grams' => intval($product_data['weight_grams'] ?? 0),
            'length_cm' => floatval($product_data['length_cm'] ?? 0),
            'width_cm' => floatval($product_data['width_cm'] ?? 0),
            'height_cm' => floatval($product_data['height_cm'] ?? 0),
            'is_shippable' => !empty($product_data['is_shippable']) ? 1 : 0,
            'stock_quantity' => intval($product_data['stock_quantity'] ?? 0),
            'manage_stock' => !empty($product_data['manage_stock']) ? 1 : 0,
            'status' => sanitize_text_field($product_data['status'] ?? 'active'),
            'image_url' => esc_url_raw($product_data['image_url'] ?? ''),
            'sort_order' => intval($product_data['sort_order'] ?? 0),
            'updated_at' => current_time('mysql'),
        );

        $formats = array(
            '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%f', '%f', '%f', '%d', '%d', '%d', '%s', '%s', '%d', '%s'
        );

        if ($existing_product) {
            $result = $wpdb->update($products_table, $row, array('id' => $product_id), $formats, array('%d'));
        } else {
            $row = array_merge(
                array(
                    'legacy_product_id' => null,
                ),
                $row,
                array(
                    'created_at' => current_time('mysql'),
                )
            );
            $result = $wpdb->insert(
                $products_table,
                $row,
                array_merge(array('%d'), $formats, array('%s'))
            );
            $product_id = intval($wpdb->insert_id);
        }

        if (false === $result) {
            return array(
                'success' => false,
                'message' => $wpdb->last_error ?: 'Gagal menyimpan produk.',
            );
        }

        return array(
            'success' => true,
            'product_id' => $product_id,
            'message' => $existing_product ? 'Produk berhasil diperbarui.' : 'Produk berhasil ditambahkan.',
        );
    }

    /**
     * Delete a normalized product.
     *
     * @param int $product_id Product ID.
     * @return array
     */
    public function delete_product($product_id) {
        global $wpdb;

        $products_table = $this->get_products_table();
        $result = $wpdb->delete($products_table, array('id' => intval($product_id)), array('%d'));

        if (false === $result) {
            return array(
                'success' => false,
                'message' => $wpdb->last_error ?: 'Gagal menghapus produk.',
            );
        }

        return array(
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        );
    }

    /**
     * Build a unique slug for the normalized catalog.
     *
     * @param string $base_slug   Desired base slug.
     * @param int    $product_id  Existing product ID when editing.
     * @return string
     */
    private function get_unique_slug($base_slug, $product_id = 0) {
        global $wpdb;

        $products_table = $this->get_products_table();
        $base_slug = $base_slug ?: 'product';
        $slug = $base_slug;
        $suffix = 2;

        while ($this->value_exists('slug', $slug, $product_id, $products_table)) {
            $slug = $base_slug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Build a unique SKU for the normalized catalog.
     *
     * @param string $base_sku    Desired base SKU.
     * @param int    $product_id  Existing product ID when editing.
     * @return string
     */
    private function get_unique_sku($base_sku, $product_id = 0) {
        global $wpdb;

        $products_table = $this->get_products_table();
        $base_sku = strtoupper($base_sku ?: 'YIARI-PRODUCT');
        $sku = $base_sku;
        $suffix = 2;

        while ($this->value_exists('sku', $sku, $product_id, $products_table)) {
            $sku = $base_sku . '-' . $suffix;
            $suffix++;
        }

        return $sku;
    }

    /**
     * Check whether a unique value already exists in the products table.
     *
     * @param string $column      Column name.
     * @param string $value       Candidate value.
     * @param int    $product_id  Existing product ID when editing.
     * @param string $table_name  Products table name.
     * @return bool
     */
    private function value_exists($column, $value, $product_id, $table_name) {
        global $wpdb;

        if ($product_id > 0) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE {$column} = %s AND id != %d LIMIT 1",
                    $value,
                    $product_id
                )
            );
        } else {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE {$column} = %s LIMIT 1",
                    $value
                )
            );
        }

        return !empty($existing_id);
    }
}
?>
