# Phase 0 Audit

Tanggal: 2026-07-10

## Backup
- File backup plugin: `/tmp/yiari_donasi_midtrans_backup_2026-07-10-160910.tar.gz`
- File backup tabel plugin: `/tmp/yiari_donasi_midtrans_db_backup_2026-07-10-160910.sql`

## WordPress Target
- Site: `https://staging.yiari.or.id`
- Database aktif: `yiari_staging`
- Prefix tabel: `MuuNS_`

## Tabel yang Ditemukan
Tabel aktif/legacy yang relevan untuk plugin ini:
- `MuuNS_kukang_currency_new`
- `MuuNS_kukang_currency_settings`
- `MuuNS_kukang_dolls`
- `MuuNS_kukang_dolls_new`
- `MuuNS_kukang_transactions`
- `MuuNS_kukang_transactions_new`
- `MuuNS_midtrans_donations`
- `MuuNS_midtrans_exchange_rates`
- `MuuNS_midtrans_transactions`
- `MuuNS_xendit_donations`
- `MuuNS_xendit_exchange_rates`
- `MuuNS_yiari_kukang_currency_settings`
- `MuuNS_yiari_kukang_dolls`
- `MuuNS_yiari_kukang_transactions`

## Temuan Penting
- Tabel yang paling aktif di kode saat ini adalah `kukang_dolls_new` dan `kukang_transactions_new`.
- Masih ada referensi campuran ke tabel lama `kukang_dolls` pada beberapa modul, terutama shipping dan English form.
- Option WordPress yang aktif terdeteksi:
  - `midtrans_server_key`
  - `midtrans_settings`
  - `biteship_settings`
- Tidak ditemukan option aktif untuk `xendit`, tetapi tabel `xendit_*` masih ada di database.

## Bentuk Data Saat Ini
- `MuuNS_kukang_dolls_new`: 5 produk, sudah punya `price_idr`, `price_usd`, `weight_grams`, dimensi, deskripsi, dan `image_url`.
- `MuuNS_kukang_transactions_new`: 192 baris, masih memakai kolom qty hardcoded:
  `regina_qty`, `jagger_qty`, `butros_qty`, `eid_qty`, `anoda_qty`.
- `MuuNS_kukang_transactions_new` juga menyimpan:
  shipping, payment, `tracking_number`, `language`, `currency`, `usd_amount`, `exchange_rate`.

## Distribusi Data Transaksi
- `transaction_status`:
  - `settlement`: 124
  - `pending`: 49
  - `expire`: 19
- `order_status`:
  - `processing`: 187
  - `delivered`: 4
  - `delivering`: 1
- `language`: seluruh data saat ini `id`
- `currency`: seluruh data saat ini `IDR`

## Implikasi Migrasi
- Model order saat ini belum normal karena item order disimpan sebagai kolom qty per produk.
- Migrasi ke `products`, `orders`, `order_items`, dan `shipments` wajib dilakukan.
- Xendit dapat dihapus dari kode baru, tetapi tabel lama sebaiknya tidak langsung di-drop sampai migrasi dan verifikasi selesai.
- Biteship masih aktif di admin dan shipping manager, sehingga penggantian ke KiriminAja perlu menyentuh settings, AJAX, admin UI, dan flow fulfillment.

## Prioritas Teknis Berikutnya
1. Buat schema baru yang generik.
2. Tambah migrator versi plugin untuk membuat tabel baru.
3. Buat script migrasi dari `kukang_dolls_new` ke `products`.
4. Buat script migrasi dari `kukang_transactions_new` ke `orders` dan `order_items`.
5. Pisahkan kode legacy yang masih membaca `kukang_dolls` dan `kukang_transactions`.
