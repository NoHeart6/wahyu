# Aplikasi Toko Sparepart Motor

Aplikasi web sederhana untuk toko sparepart motor menggunakan PHP dan MongoDB.

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MongoDB 4.4 atau lebih tinggi
- MongoDB PHP Driver
- Composer

## Instalasi

1. Install MongoDB di komputer Anda
2. Install PHP MongoDB Driver
3. Clone repository ini
4. Jalankan `composer install` untuk menginstall dependensi
5. Jalankan migrasi database dengan perintah:
   ```
   php migrations/create_spareparts.php
   ```
6. Jalankan server PHP dengan perintah:
   ```
   php -S localhost:8000
   ```
7. Buka browser dan akses `http://localhost:8000`

## Fitur

- Menampilkan daftar sparepart
- Detail produk
- Keranjang belanja
- Checkout (dalam pengembangan)

## Struktur Database

Koleksi: `sparepart`

Struktur dokumen:
```json
{
    "nama": "Nama Produk",
    "harga": 100000,
    "stok": 50,
    "deskripsi": "Deskripsi produk",
    "gambar": "path/to/image.jpg"
}
``` 