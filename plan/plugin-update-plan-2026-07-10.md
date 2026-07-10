# Plugin Update Plan

Tanggal: 2026-07-10

## Tujuan
Refactor plugin agar mendukung katalog merchandise universal, payment Midtrans-only, shipping via KiriminAja, workflow fulfillment otomatis setelah settlement, dan backup aman sebelum perubahan.

## Ruang Lingkup
1. Backup plugin lama dan siapkan rollback.
2. Ubah domain produk dari khusus boneka kukang menjadi produk/merchandise universal.
3. Hapus atau nonaktifkan semua gateway selain Midtrans.
4. Ganti integrasi shipping dari Biteship ke KiriminAja.
5. Ubah alur order menjadi:
   user order -> pending payment -> settlement Midtrans -> generate AWB KiriminAja -> email admin/user -> sertifikat.
6. Tambahkan migrasi data, pengujian regresi, dan rollout bertahap.

## Detail Plan

### 1. Backup & Rollback
- Backup folder plugin penuh sebelum perubahan.
- Export tabel plugin lama sebelum migrasi.
- Simpan mapping schema lama ke schema baru untuk rollback.

### 2. Refactor Produk Universal
- Ganti istilah domain `doll`, `kukang_dolls`, dan label sejenis menjadi `product`.
- Buat struktur data produk generik:
  `id`, `sku`, `name`, `slug`, `description`, `price_idr`, `price_usd`, `weight_gram`, `length`, `width`, `height`, `is_shippable`, `stock`, `status`, `image`, `sort_order`.
- Tambahkan manager produk di wp-admin:
  pencarian, filter aktif/nonaktif, stok, berat/dimensi, gambar, dan urutan tampil.

### 3. Payment Midtrans Only
- Audit dan bersihkan semua cabang payment selain Midtrans.
- Rapikan settings admin agar hanya konfigurasi Midtrans yang dipakai.
- Pastikan status internal sinkron dengan status notifikasi Midtrans.

### 4. Shipping KiriminAja
- Ganti seluruh shipping manager yang masih terkait Biteship.
- Integrasikan:
  coverage lookup, pricing, courier preference, create order/AWB, tracking, webhook, dan pickup/drop-off.
- Simpan `shipping_cost`, `courier`, dan `service_type` hasil pricing untuk dipakai saat create order.

### 5. Workflow Fulfillment
- Buat status internal:
  `draft`, `pending_payment`, `paid`, `shipment_requested`, `awb_created`, `shipped`, `delivered`, `canceled`, `returned`.
- Generate resi hanya setelah status Midtrans `settlement`.
- Simpan AWB, link tracking, payload request/response, dan log status pengiriman.

### 6. Email & Sertifikat
- Kirim email ke donor dan admin setelah AWB berhasil dibuat.
- Sertakan nomor resi, link tracking, ringkasan order, dan sertifikat.
- Sertifikat menggunakan template yang sudah ada, sistem hanya mengisi nama donatur dan metadata yang diperlukan.

### 7. Migrasi Data & Testing
- Siapkan migrasi ke tabel `products`, `orders`, `order_items`, `shipments`, `order_status_logs`, `notifications`, dan `certificate_files` bila diperlukan.
- Lakukan testing untuk:
  form ID/EN, IDR/USD, settlement Midtrans, generate AWB, webhook KiriminAja, email, sertifikat, dan admin export bila masih dipertahankan.

### 8. Rollout
- Implementasi di staging.
- UAT untuk checkout, settlement, AWB, tracking, dan email.
- Rollout ke production setelah backup final dan checklist valid.

## Referensi KiriminAja
- Overview: https://developer.kiriminaja.com/docs/introduction
- Pricing Express: https://developer.kiriminaja.com/docs/pricing/express
- Create Order Express: https://developer.kiriminaja.com/docs/order/express
- Tracking: https://developer.kiriminaja.com/docs/order/tracking
- Register Callback: https://developer.kiriminaja.com/docs/webhook/setup
- Webhook Payload Express: https://developer.kiriminaja.com/docs/webhook/event
- Pickup Schedule: https://developer.kiriminaja.com/docs/pickup/schedule
- Courier List: https://developer.kiriminaja.com/docs/others/courier-list
- Set Courier Preference: https://developer.kiriminaja.com/docs/others/set-preference

## Keputusan Sementara
- Export Excel ke KiriminAja tidak wajib jika integrasi API create AWB berjalan stabil.
- Export manual tetap bisa dipertahankan sebagai fallback operasional/admin, bukan alur utama.
