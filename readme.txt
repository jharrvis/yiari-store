=== YIARI Donasi Kukang ===
Contributors: Julian H - MCIMEDIA
Donate link: https://yiari.or.id/
Tags: donation, midtrans, payment gateway, kukang, wildlife, rehabilitation
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 3.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin form donasi rehabilitasi kukang untuk Yayasan IAR Indonesia menggunakan Payment Gateway Midtrans dengan dukungan multi-bahasa (ID/EN) dan multi-mata uang (IDR/USD).

== Description ==

YIARI Donasi Kukang adalah plugin WordPress yang memungkinkan organisasi konservasi untuk menerima donasi online untuk program rehabilitasi kukang. Plugin ini dikembangkan khusus untuk Yayasan IAR Indonesia dengan fitur-fitur canggih.

**Fitur Utama:**
* 🌍 **Multi-bahasa**: Dukungan bahasa Indonesia dan Inggris
* 💰 **Multi-mata uang**: IDR dan USD dengan konversi real-time
* 💳 **Payment Gateway**: Integrasi Midtrans untuk berbagai metode pembayaran
* 🚚 **Shipping**: Integrasi Biteship untuk kalkulasi ongkos kirim
* 📊 **Admin Dashboard**: Panel admin lengkap untuk manajemen donasi
* 📱 **Responsive**: Desain mobile-friendly
* 🔒 **Secure**: Enkripsi dan validasi keamanan tingkat tinggi

**Metode Pembayaran:**
* Transfer Bank (BCA, BNI, BRI, Mandiri)
* E-Wallet (GoPay, OVO, DANA, ShopeePay)
* Virtual Account
* Credit Card (Visa, MasterCard)
* Convenience Store (Alfamart, Indomaret)

**Fitur Admin:**
* Dashboard real-time
* Laporan transaksi
* Manajemen boneka kukang
* Pengaturan mata uang
* Export data ke Excel
* Tracking order

== Installation ==

1. Upload plugin ke direktori `/wp-content/plugins/`
2. Aktifkan plugin melalui menu 'Plugins' di WordPress
3. Konfigurasi Midtrans API keys di menu 'Donasi Midtrans > Midtrans Settings'
4. Atur Biteship API key untuk shipping (opsional)
5. Gunakan shortcode `[donasi_kukang]` untuk form Indonesia atau `[donasi_kukang_en]` untuk form Inggris

== Frequently Asked Questions ==

= Apakah plugin ini gratis? =

Ya, plugin ini gratis untuk digunakan. Namun Anda perlu akun Midtrans untuk payment gateway.

= Apakah mendukung berbagai mata uang? =

Ya, plugin mendukung IDR dan USD dengan konversi real-time menggunakan API exchange rate.

= Bagaimana cara menampilkan form donasi? =

Gunakan shortcode:
* `[donasi_kukang]` - Form bahasa Indonesia
* `[donasi_kukang_en]` - Form bahasa Inggris
* `[cek_donasi]` - Form tracking donasi

= Apakah ada biaya transaksi? =

Plugin tidak mengenakan biaya. Biaya transaksi sesuai dengan ketentuan Midtrans.

== Screenshots ==

1. Form donasi dengan pilihan boneka kukang
2. Dashboard admin dengan statistik real-time
3. Halaman pengaturan Midtrans
4. Form tracking donasi untuk donor
5. Laporan transaksi dengan export Excel

== Changelog ==

= 3.1.1 - 2025-09-26 =
**CRITICAL BUG FIXES**
* Fixed fatal error during plugin activation due to missing helper files
* Resolved WordPress cron job conflicts causing "Undefined array key" errors
* Fixed autoloader issues in modular architecture
* Added proper error handling for missing dependencies
* Enhanced plugin stability and reliability

**New Features:**
* Added comprehensive helper functions for currency and validation
* Implemented proper AJAX handlers for better user experience
* Created plugin deactivation cleanup mechanism
* Enhanced error logging for debugging

**Technical Improvements:**
* Renamed cron hook to prevent conflicts with other plugins
* Improved plugin loader with better error handling
* Added file existence checks to prevent fatal errors
* Enhanced modular structure with proper dependency management

= 3.1.0 =
* Multi-language support (Indonesian/English)
* Multi-currency support (IDR/USD)
* Dynamic exchange rate system with API integration
* Modular plugin architecture
* Enhanced admin interface
* Biteship shipping integration
* Improved payment processing with Midtrans

== Upgrade Notice ==

= 3.1.1 =
CRITICAL UPDATE: Fixes fatal errors and WordPress cron conflicts. Please backup before updating and deactivate/reactivate plugin after update.

== Support ==

Untuk dukungan teknis, silakan hubungi:
* Email: support@mcimedia.net
* Website: https://mcimedia.net
* Plugin URI: https://yiari.or.id/

== Privacy Policy ==

Plugin ini mengumpulkan data donor (nama, email, alamat) untuk keperluan processing donasi dan pengiriman. Data disimpan dengan aman dan tidak dibagikan kepada pihak ketiga tanpa persetujuan.

== Third Party Services ==

Plugin ini menggunakan layanan pihak ketiga:
* **Midtrans**: Payment gateway (https://midtrans.com)
* **Biteship**: Shipping cost calculation (https://biteship.com)
* **Exchange Rate API**: Currency conversion (https://exchangerate-api.com)

== Development ==

Plugin ini dikembangkan dengan struktur modular untuk kemudahan maintenance dan pengembangan. Developer dapat extend functionality melalui hooks dan filters yang tersedia.

**Requirements:**
* WordPress 5.0+
* PHP 7.4+
* MySQL 5.6+
* cURL extension
* JSON extension