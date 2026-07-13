# Technical Implementation Plan

Tanggal: 2026-07-10

## Keputusan Arsitektur
Pendekatan yang disarankan adalah **rebuild terarah di dalam plugin yang sama**, bukan patch bertahap di kode lama dan bukan juga plugin baru terpisah.

Alasannya:
- struktur saat ini masih sangat spesifik ke domain `kukang` dan `doll`
- flow payment, shipping, dan data model perlu diganti cukup dalam
- tetap memakai plugin yang sama mengurangi risiko migrasi aktivasi, shortcode, dan deployment WordPress
- kita bisa membersihkan modul sambil menjaga backward compatibility di fase transisi

Pendekatan praktis:
- pertahankan entry plugin `yiari-donasi-kukang.php` di awal
- buat layer baru yang generik dan pisahkan modul lama sebagai legacy
- migrasikan traffic dan data ke modul baru, lalu hapus legacy setelah stabil

## Target Struktur Folder
- `yiari-donasi-kukang.php`: bootstrap sementara
- `includes/`: loader, service container ringan, config, migrator
- `modules/legacy/`: salinan modul lama yang masih dipakai sementara
- `modules/catalog/`: product manager, product repository, product admin UI
- `modules/orders/`: order service, order repository, order status log
- `modules/payments/`: Midtrans gateway, callback handler, payment status mapper
- `modules/shipping/`: KiriminAja client, rate service, shipment service, webhook handler
- `modules/notifications/`: email service, certificate generator
- `helpers/`: helper generik yang tetap kecil dan stateless
- `plan/` dan `docs/`: dokumentasi internal

## Database Refactor
Tabel baru yang disarankan:
- `wp_yiari_products`
- `wp_yiari_orders`
- `wp_yiari_order_items`
- `wp_yiari_shipments`
- `wp_yiari_order_status_logs`
- `wp_yiari_certificates`
- `wp_yiari_plugin_settings` bila ingin settings terstruktur

Data penting:
- product: sku, nama, harga, stok, berat, dimensi, status, gambar
- order: nomor order, donor, alamat, currency, subtotal, shipping, total, payment status, fulfillment status, motivation data
- order item: product_id, sku_snapshot, qty, price_snapshot, weight_snapshot, fulfillment_type
- shipment: courier, service_type, shipping_cost, order_id, awb, tracking_url, request/response log

Tambahan field yang disarankan:
- `orders`:
  - `donation_item_count`
  - `self_item_count`
  - `order_flow_type`
  - `contains_donation_items`
  - `donation_motivation_code`
  - `donation_motivation_other`
- `order_items`:
  - `fulfillment_type` dengan nilai `self_purchase` atau `donation_purchase`
  - `requires_shipping`
  - `donation_recipient_type` untuk saat ini default `yiari`

## Mapping File Saat Ini ke Implementasi Baru
- `modules/class-yiari-database-manager.php`
  dipecah menjadi migrator schema + repository setup
- `modules/class-yiari-form-manager.php`
  dipecah menjadi form renderer + order creator
- `modules/class-yiari-payment-manager.php`
  dipersempit menjadi Midtrans-only adapter
- `modules/class-yiari-shipping-manager.php`
  ditulis ulang penuh untuk KiriminAja
- `modules/class-yiari-email-manager.php`
  diperluas untuk email settlement, AWB, dan sertifikat
- `modules/class-yiari-admin-module.php`
  dipecah menjadi admin products, admin orders, admin settings, admin reports
- `helpers/ajax-handlers.php`
  dipisah per domain: catalog, checkout, shipping, admin

## Tahapan Implementasi

### Phase 0. Safety
- backup folder plugin
- export tabel lama
- catat shortcode lama yang harus tetap hidup

### Phase 1. New Data Model
- tambahkan migrator versi plugin
- buat tabel baru
- buat script migrasi dari data `kukang` lama ke `products`

### Phase 2. Product Catalog
- buat repository dan admin CRUD produk berbasis `yiari_products`
- ubah front-end agar membaca item dari katalog, bukan hardcoded boneka
- untuk produk buku, pertahankan satu produk utama dan biarkan checkout yang membentuk dua jenis item order

### Phase 3. Orders
- buat order aggregate baru
- simpan item order secara snapshot, bukan bergantung ke harga produk saat ini
- implementasikan dua jalur item order:
  - `self_purchase`: dibayar donor, dikirim ke donor
  - `donation_purchase`: dibayar donor, tidak dikirim ke donor, dicatat sebagai donasi buku melalui YIARI
- shipping calculator hanya menghitung item dengan `requires_shipping = 1`
- order summary harus memisahkan:
  - subtotal buku untuk diri sendiri
  - subtotal buku donasi
  - total shipping
  - total keseluruhan
- tambahkan validasi form untuk pertanyaan motivasi:
  - pilihan ganda wajib diisi
  - field teks `other` wajib jika pilihan adalah `lainnya`
- simpan motivasi di level order, bukan order item, karena motivasi terkait donor/order secara keseluruhan

### Phase 4. Midtrans
- bersihkan gateway lain
- standardisasi callback dan state transition
- generate shipment hanya saat `settlement`

### Phase 5. KiriminAja
- implement API client dengan endpoint:
  pricing, courier list, create order express, tracking, callback registration
- simpan courier/service/shipping_cost hasil pilihan user
- buat webhook endpoint publik untuk `processed_packages`, `shipped_packages`, `finished_packages`, `returned_packages`

### Phase 6. Notification & Certificate
- generate sertifikat dari template
- kirim email ke donor dan admin setelah AWB tersedia
- jika ada `donation_purchase`, sertifikat mengambil total buku donasi dan identitas donor dari order
- email admin perlu memuat ringkasan:
  - jumlah buku dikirim ke donor
  - jumlah buku didonasikan melalui YIARI
  - apakah order butuh shipment atau hanya donasi

### Phase 7. Legacy Cleanup
- pindahkan kode lama ke `modules/legacy/`
- hapus referensi `doll`, `kukang_dolls`, dan Biteship setelah stabil

## Fallback & Operasional
- export Excel tetap opsional sebagai fallback admin, bukan primary flow
- tracking tetap punya fallback polling bila webhook KiriminAja terlambat/gagal
- semua request API penting wajib dilog dengan masking secret
- laporan admin harus punya agregasi terpisah antara `self_purchase` dan `donation_purchase`
- laporan admin perlu bisa menampilkan breakdown motivasi donasi berdasarkan `donation_motivation_code`

## Data/Access yang Dibutuhkan
- Midtrans sandbox + production keys
- KiriminAja sandbox + production API keys
- alamat origin/pengirim lengkap
- preferensi pickup atau drop-off
- template sertifikat final
- format email donor dan admin
- keputusan apakah export Excel dipertahankan sebagai fallback

## Definisi Selesai
- produk sudah generik
- checkout membaca katalog baru
- checkout buku bisa mencatat item untuk diri sendiri dan item donasi dalam satu order
- checkout menangkap motivasi donasi dan menyimpan opsi `lainnya` bila diisi
- settlement Midtrans memicu shipment KiriminAja
- AWB, tracking, email, dan sertifikat berjalan otomatis
- data lama termigrasi
- admin bisa mengelola produk dan order tanpa istilah lama yang spesifik
