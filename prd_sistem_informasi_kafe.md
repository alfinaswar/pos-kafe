# Product Requirement Document (PRD)

## 1. Document Overview

- **Project Name:** Sistem Informasi Point of Sale (POS), Persediaan, dan Pembelian Supplier (SIP3-KAFE)
- **Study Case:** Kedai Kopi Kaya Raya (Jl. Tengku Zainal Abidin No. 1, Kota Pekanbaru)
- **Target Architecture:** Web-based Application (Laravel MVC / PHP 8.x, MySQL, Tailwind CSS / Bootstrap, Livewire / Alpine.js)
- **Document Status:** Complete Specification for AI Agephp nt & Software Developer Implementation

## 2. Project Goal & Objectives

Tujuan utama pengembangan sistem ini adalah membangun aplikasi berbasis web yang mengintegrasikan transaksi penjualan di kasir (POS), pemotongan stok bahan baku secara otomatis berdasarkan resep (_Bill of Materials_), konversi satuan otomatis antara unit pembelian supplier dan unit resep dapur, serta manajemen pengadaan/pembelian bahan baku dari supplier.

### Key Objectives:

1. **POS Integration & Automated Deduction:** Mencatat transaksi penjualan harian dan mengurangi stok bahan baku secara otomatis berdasarkan resep (_Bill of Materials_ / BOM).
2. **Multi-Unit Conversion Engine:** Menjembatani perbedaan antara satuan pembelian supplier (misal: Karton, Pack, Botol) dan satuan resep dapur (misal: ml, gram, pcs) secara otomatis.
3. **Inventory Control:** Menerapkan metode **FIFO (First In First Out)** untuk perputaran stok dan **ROP (Reorder Point)** untuk peringatan stok kritis.
4. **Supplier & Purchasing Management:** Mengelola master data supplier, penerbitan _Purchase Order_ (PO) digital dengan sistem otorisasi (_approval_), dan validasi barang masuk.
5. **Decision Support & Reporting:** Menyediakan _dashboard_ analitik dan laporan transaksional terpusat untuk _Owner_ dan _Manager_.

## 3. User Roles & Access Control (RBAC)

Sistem wajib menerapkan **Role-Based Access Control (RBAC)** dengan 3 tingkatan hak akses:

| Role                       | Access Scope                   | Key Permissions                                                                                                                                                      |
| :------------------------- | :----------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Owner (Pimpinan)**       | Full System Access             | View Executive Dashboard, Approve Purchase Order (PO), Access Financial & Inventory Reports, User Management.                                                        |
| **Manager / Admin Gudang** | Operational & Inventory Access | Manage Master Data (Materials, Categories, Recipes, Suppliers), Process Multi-Unit Conversions, Process Stock Opname, Create Purchase Order, Validate Goods Receipt. |
| **Cashier (Kasir)**        | POS Access Only                | Process Sales Transactions, Print Receipts, View Menu Availability, View Daily Shift Sales Summary.                                                                  |

## 4. Multi-Unit Conversion & Recipe (BOM) Architecture

### 4.1 Unit Conversion Logic (Beli vs Pakai)

Setiap bahan baku (_Raw Material_) wajib memiliki konfigurasi 3 parameter satuan:

1. `purchase_unit`: Satuan saat barang dibeli dari supplier (contoh: _Karton_, _Pack_, _Botol_, _Galon_).
2. `recipe_unit`: Satuan terkecil yang digunakan pada resep menu (contoh: _gram_, _ml_, _pcs_).
3. `conversion_factor`: Angka pengali yang menyatakan berapa jumlah satuan resep dalam 1 satuan beli.
    - _Contoh:_ Susu UHT dibeli dalam satuan `Karton` dengan isi 12.000 ml per karton. Maka `purchase_unit` = Karton, `recipe_unit` = ml, dan `conversion_factor` = 12000.

### 4.2 Bill of Materials (BOM) / Recipe Logic

Setiap menu/produk yang dijual di kasir (_Products_) harus dihubungkan dengan tabel relasi resep (_recipe_items_) yang merinci kebutuhan bahan baku per 1 porsi.

- _Contoh Produk:_ "Es Kopi Susu Kaya Raya"
    - Kopi Espresso: 18 gram
    - Susu UHT: 150 ml
    - Gula Aren: 20 ml
    - Cup Plastik: 1 pcs

### 4.3 Automated Stock Deduction via POS

Saat kasir melakukan transaksi penjualan _Qty_ produk, sistem wajib melakukan _Database Transaction_ (ACID):

1. Mengalikan jumlah produk terjual dengan kuantitas bahan di resep.
2. Mengurangi stok bahan baku di tabel `materials` menggunakan metode **FIFO** (berdasarkan tanggal kedatangan _Goods Receipt_ tertua).
3. Mengecek apakah sisa stok bahan baku berada di bawah nilai `reorder_point` (ROP). Jika iya, aktifkan status _warning_ di dashboard.

## 5. Core Modules & Functional Requirements

### 5.1 Authentication Module

- Login page dengan validasi enkripsi password (Bcrypt/Argon2).
- Middleware routing otomatis berdasarkan _role_ pengguna setelah berhasil login.

### 5.2 Master Data Module

- **Kategori Bahan & Produk:** Pengelompokan data inventaris dan menu kafe.
- **Manajemen Bahan Baku (_Materials_):** Input nama bahan, kategori, stok saat ini, stok minimum (ROP), `purchase_unit`, `recipe_unit`, dan `conversion_factor`.
- **Manajemen Supplier:** Input profil vendor (nama perusahaan, kontak, alamat, produk yang disuplai).
- **Manajemen Produk & Resep (_Products & BOM_):** Input menu makanan/minuman, harga jual, foto, serta form dinamis untuk memasukkan daftar bahan baku dan takarannya.

### 5.3 Inventory & Stock Control Module

- **Penerimaan Barang (_Goods Receipt_):** Saat barang datang dari supplier, admin memasukkan jumlah beli dalam `purchase_unit`. Sistem otomatis mengonversi ke `recipe_unit` dan menambah stok total gudang.
- **Stock Opname:** Fasilitas audit fisik untuk mencocokkan stok sistem dengan stok riil di gudang, mencatat selisih (_variance_).

### 5.4 Purchasing & Supplier Module

- **Notifikasi Stok Kritis (ROP):** Indikator visual di dashboard ketika bahan baku menyentuh batas minimum.
- **Purchase Order (PO):** Pembuatan draf surat pemesanan bahan baku ke supplier.
- **Approval PO:** _Owner_ dapat meninjau dan menyetujui (_approve_) atau menolak PO yang diajukan admin.

### 5.5 POS (Point of Sales) & Sales Module

- Antarmuka kasir yang interaktif (katalog menu berdasarkan kategori, keranjang belanja, kalkulasi kembalian).
- Tombol "Bayar" yang memicu transaksi kasir sekaligus pemotongan stok otomatis via BOM.
- Cetak struk penjualan termal (_thermal print layout_).

### 5.6 Reporting Module

- **Laporan Penjualan:** Rekap omzet harian/bulanan.
- **Laporan Mutasi Stok:** Riwayat keluar-masuk bahan baku secara transparan.
- **Laporan Pembelian:** Rekapitulasi biaya pengadaan bahan ke supplier.
- **Ekspor Data:** Fitur unduh laporan ke format PDF dan Excel.

## 6. Database Schema (MySQL Architecture)

AI Agent wajib membuat tabel relasional berikut di database MySQL:

1. **`users`**
    - `id` (PK, INT)
    - `name` (VARCHAR)
    - `username` (VARCHAR, UNIQUE)
    - `password` (VARCHAR)
    - `role` (ENUM: 'owner', 'manager', 'cashier')
    - `timestamps`

2. **`suppliers`**
    - `id` (PK, INT)
    - `name` (VARCHAR)
    - `phone` (VARCHAR)
    - `address` (TEXT)
    - `timestamps`

3. **`materials`** (Bahan Baku)
    - `id` (PK, INT)
    - `name` (VARCHAR)
    - `category` (VARCHAR)
    - `stock` (DECIMAL, menyimpan nilai dalam satuan _recipe_unit_)
    - `purchase_unit` (VARCHAR, misal: Karton, Pack)
    - `recipe_unit` (VARCHAR, misal: gram, ml, pcs)
    - `conversion_factor` (DECIMAL, pengali konversi)
    - `reorder_point` (DECIMAL, batas minimum stok)
    - `timestamps`

4. **`products`** (Menu Jualan)
    - `id` (PK, INT)
    - `name` (VARCHAR)
    - `category` (VARCHAR)
    - `price` (DECIMAL)
    - `image` (VARCHAR, nullable)
    - `timestamps`

5. **`recipe_items`** (BOM / Relasi Produk ke Bahan)
    - `id` (PK, INT)
    - `product_id` (FK to products)
    - `material_id` (FK to materials)
    - `quantity` (DECIMAL, takaran dalam satuan _recipe_unit_)
    - `timestamps`

6. **`purchases`** (Faktur Pembelian / PO)
    - `id` (PK, INT)
    - `supplier_id` (FK to suppliers)
    - `user_id` (FK to users)
    - `invoice_number` (VARCHAR)
    - `total_cost` (DECIMAL)
    - `status` (ENUM: 'pending', 'approved', 'completed')
    - `timestamps`

7. **`purchase_details`**
    - `id` (PK, INT)
    - `purchase_id` (FK to purchases)
    - `material_id` (FK to materials)
    - `qty_purchase` (DECIMAL, jumlah dalam satuan beli)
    - `subtotal` (DECIMAL)
    - `timestamps`

8. **`sales`** (Transaksi POS)
    - `id` (PK, INT)
    - `user_id` (FK to cashiers)
    - `total_price` (DECIMAL)
    - `payment_amount` (DECIMAL)
    - `change_amount` (DECIMAL)
    - `timestamps`

9. **`sale_items`**
    - `id` (PK, INT)
    - `sale_id` (FK to sales)
    - `product_id` (FK to products)
    - `quantity` (INT)
    - `subtotal` (DECIMAL)
    - `timestamps`

10. **`inventory_logs`** (Catatan Mutasi FIFO & Stok)
    - `id` (PK, INT)
    - `material_id` (FK to materials)
    - `type` (ENUM: 'in', 'out')
    - `quantity` (DECIMAL dalam satuan _recipe_unit_)
    - `reference` (VARCHAR, misal: 'POS Sale #12' atau 'Purchase #05')
    - `timestamps`

## 7. Acceptance Criteria for AI Agent

- **Laravel Framework:** Gunakan migration, Eloquent relationships, dan Form Request Validation.
- **Database Transactions:** Pada modul kasir (POS) dan penerimaan barang, pastikan menggunakan `DB::transaction()` untuk menjaga konsistensi stok.
- **Responsive UI:** Tampilan wajib rapi menggunakan Bootstrap (Admin LTE 3) agar nyaman diakses baik oleh kasir maupun pimpinan kafe.
