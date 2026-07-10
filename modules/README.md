# Modules

Struktur modul lama saat ini masih berada langsung di folder `modules/`.
Refactor berikutnya akan memindahkan tanggung jawab ke subfolder domain agar plugin lebih bersih dan mudah diuji.

## Target Structure
- `legacy/`: modul lama yang masih dipertahankan sementara selama masa transisi.
- `catalog/`: produk, katalog, pricing snapshot, dan admin CRUD produk.
- `orders/`: order service, order repository, item snapshot, dan status log.
- `payments/`: Midtrans-only payment integration dan callback handling.
- `shipping/`: KiriminAja pricing, shipment creation, tracking, dan webhook.
- `notifications/`: email, sertifikat, dan notifikasi operasional.

## Migration Rule
- Jangan tambah fitur baru ke modul legacy bila fitur itu bagian dari arsitektur baru.
- Perubahan bugfix kecil boleh tetap masuk modul legacy selama belum dimigrasikan.
- Fitur baru untuk katalog, order, payment, shipping, dan notifikasi harus diarahkan ke struktur domain baru.
