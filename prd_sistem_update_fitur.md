# Product Requirement Document (PRD)

## Sistem Informasi Point of Sale, Persediaan, dan Pembelian Supplier (SIP3-KAFE) - Commercial Edition

## 1. Document Overview & Commercial Strategy

- **Project Name:** Sistem Informasi Point of Sale, Persediaan, dan Pembelian Supplier (SIP3-KAFE)
- **Study Case:** Kedai Kopi Kaya Raya (Jl. Tengku Zainal Abidin No. 1, Kota Pekanbaru)
- **Target Architecture:** Web-based Application (Laravel MVC / PHP 8.x, MySQL, Tailwind CSS / Bootstrap, Livewire / Alpine.js)
- **Target Business Model:** B2B SaaS (Software as a Service) / Multi-Outlet Ready Enterprise System
- **Document Status:** Complete Specification for AI Agent & Software Developer Implementation (v2.0)

---

## 2. Project Goal & Objectives

Tujuan utama pengembangan sistem ini adalah membangun aplikasi berbasis web komersial yang mengintegrasikan transaksi penjualan di kasir (POS), pemotongan stok bahan baku secara otomatis berbasis **FIFO Batch**, konversi satuan otomatis, manajemen pengadaan/pembelian supplier, serta kontrol keuangan kasir yang ketat untuk mencegah kecurangan (_fraud_).

### Key Objectives:

1. **POS Integration & Automated FIFO Deduction:** Mencatat transaksi penjualan harian dan mengurangi stok bahan baku secara akurat berdasarkan batch kedatangan tertua via _Bill of Materials_ (BOM).
2. **Multi-Unit Conversion Engine:** Menjembatani perbedaan satuan pembelian supplier (Karton, Pack, Botol) dan satuan resep dapur (gram, ml, pcs) secara otomatis.
3. **Inventory & Batch Tracking:** Menerapkan metode FIFO berbasis lot kedatangan, tanggal kedaluwarsa (_expiry date_), dan peringatan stok kritis (_Reorder Point / ROP_).
4. **Cashier Shift & Financial Security:** Manajemen buka/tutup shift kasir (_Cash Reconciliation_) dan pencatatan kas operasional harian (_Petty Cash_).
5. **Supplier & Purchasing Management:** Mengelola master data supplier, penerbitan _Purchase Order_ (PO) digital dengan sistem otorisasi (_approval_), dan validasi barang masuk.
6. **Executive Dashboard & Reporting:** Menyediakan analitik omzet, laba kotor, mutasi stok, dan laporan keuangan shift secara terpusat untuk _Owner_.

---

## 3. User Roles & Access Control (RBAC)

Sistem wajib menerapkan **Role-Based Access Control (RBAC)** dengan 3 tingkatan hak akses utama:

| Role                       | Access Scope                      | Key Permissions                                                                                                                                                                                |
| :------------------------- | :-------------------------------- | :--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Owner (Pimpinan)**       | Full System Access (Multi-Outlet) | View Executive Dashboard, Approve Purchase Order (PO), Access Financial, Shift Reconciliation & Inventory Reports, User Management.                                                            |
| **Manager / Admin Gudang** | Operational & Inventory Access    | Manage Master Data (Materials, Categories, Recipes, Suppliers), Process Multi-Unit Conversions, Process Stock Opname, Create Purchase Order, Validate Goods Receipt, Monitor Material Batches. |
| **Cashier (Kasir)**        | POS Access Only (Per Shift)       | Open/Close Cashier Shift, Process Sales Transactions, Input Petty Cash, Print Receipts, View Menu Availability, View Daily Shift Sales Summary.                                                |

---

## 4. Multi-Unit Conversion & Recipe (BOM) Architecture

### 4.1 Unit Conversion Logic (Beli vs Pakai)

Setiap bahan baku (_Raw Material_) wajib memiliki konfigurasi 3 parameter satuan:

1. `purchase_unit`: Satuan saat barang dibeli dari supplier (contoh: _Karton_, _Pack_, _Botol_, _Galon_).
2. `recipe_unit`: Satuan terkecil yang digunakan pada resep menu (contoh: _gram_, _ml_, _pcs_).
3. `conversion_factor`: Angka pengali yang menyatakan berapa jumlah satuan resep dalam 1 satuan beli (Contoh: Susu UHT dibeli dalam Karton isi 12.000 ml. Maka `purchase_unit` = Karton, `recipe_unit` = ml, dan `conversion_factor` = 12000).

### 4.2 Bill of Materials (BOM) & FIFO Batch Deduction

Setiap menu yang dijual di kasir (_Products_) dihubungkan dengan tabel relasi resep (`recipe_items`). Saat transaksi POS terjadi:

1. Sistem menjalankan `DB::transaction()` dengan `lockForUpdate()` untuk mencegah _race condition_.
2. Sistem mengurangi stok bahan baku di tabel `material_batches` berdasarkan urutan tanggal kedatangan tertua (`remaining_qty > 0`) secara FIFO.
3. Jika stok batch pertama habis, sistem otomatis menggeser pengurangan ke batch berikutnya hingga kuantitas resep terpenuhi.

---

## 5. Core Modules & Functional Requirements

### 5.1 Authentication & Shift Management Module

- Login page dengan validasi enkripsi password (Bcrypt/Argon2).
- **Manajemen Shift Kasir:** Kasir wajib memasukkan Modal Awal (_Starting Cash_) saat login sebelum mengakses POS. Saat tutup shift (_Close Cashier_), kasir memasukkan uang fisik aktual untuk dibandingkan dengan ekspektasi sistem (Modal Awal + Tunai - Petty Cash).

### 5.2 Master Data Module

- **Outlets:** Manajemen cabang kafe untuk mendukung skalabilitas multi-outlet.
- **Kategori & Bahan Baku (_Materials_):** Input data bahan, ROP, konversi satuan, dan pemantauan stok batch.
- **Supplier & Produk/Resep (_BOM_):** Input profil vendor dan form dinamis komposisi bahan per porsi menu.

### 5.3 Inventory & Stock Control Module

- **Goods Receipt (Pembelian):** Barang masuk otomatis menghasilkan batch baru di `material_batches` lengkap dengan harga beli dan tanggal kedaluwarsa.
- **Stock Opname & Waste Log:** Audit fisik gudang dan pencatatan bahan rusak/basi.

### 5.4 Purchasing & Supplier Module

- **Notifikasi ROP & Purchase Order (PO):** Indikator visual stok kritis dan pengajuan draf PO ke Owner untuk proses _Approval_.

### 5.5 POS & Sales Module

- Antarmuka kasir interaktif, keranjang belanja, kalkulasi kembalian, pencatatan _Petty Cash_ (kas kecil operasional), dan cetak struk thermal.

### 5.6 Reporting & Audit Trail

- Laporan Penjualan, Mutasi Stok FIFO, Rekapitulasi Shift Kasir, dan `activity_logs` untuk memantau aktivitas sensitif. Ekspor ke PDF/Excel.

---

## 6. Database Schema (MySQL Architecture v2.0)

1. **`users`**: `id`, `name`, `username`, `password`, `role` (enum: 'owner', 'manager', 'cashier'), `timestamps`
2. **`outlets`**: `id`, `name`, `address`, `phone`, `timestamps`
3. **`suppliers`**: `id`, `name`, `phone`, `address`, `timestamps`
4. **`materials`**: `id`, `name`, `category`, `stock`, `purchase_unit`, `recipe_unit`, `conversion_factor`, `reorder_point`, `timestamps`
5. **`products`**: `id`, `name`, `category`, `price`, `image`, `timestamps`
6. **`recipe_items`**: `id`, `product_id` (FK), `material_id` (FK), `quantity`, `timestamps`
7. **`purchases`**: `id`, `supplier_id` (FK), `user_id` (FK), `invoice_number`, `total_cost`, `status` (enum: 'pending', 'approved', 'completed'), `timestamps`
8. **`purchase_details`**: `id`, `purchase_id` (FK), `material_id` (FK), `qty_purchase`, `subtotal`, `timestamps`
9. **`material_batches`**: `id`, `material_id` (FK), `purchase_id` (FK, nullable), `initial_qty`, `remaining_qty`, `purchase_price`, `expired_date`, `timestamps`
10. **`sales`**: `id`, `user_id` (FK), `shift_id` (FK), `total_price`, `payment_amount`, `change_amount`, `timestamps`
11. **`sale_items`**: `id`, `sale_id` (FK), `product_id` (FK), `quantity`, `subtotal`, `timestamps`
12. **`cashier_shifts`**: `id`, `user_id` (FK), `outlet_id` (FK), `start_cash`, `end_cash`, `total_sales_cash`, `total_sales_non_cash`, `expected_cash`, `actual_cash`, `variance`, `status` (enum: 'open', 'closed'), `closed_at`, `timestamps`
13. **`petty_cash`**: `id`, `user_id` (FK), `shift_id` (FK), `type` (enum: 'in', 'out'), `amount`, `description`, `timestamps`
14. **`inventory_logs`**: `id`, `material_id` (FK), `type` (enum: 'in', 'out'), `quantity`, `reference`, `timestamps`
15. **`activity_logs`**: `id`, `user_id` (FK), `action`, `description`, `ip_address`, `timestamps`

---

## 7. Acceptance Criteria for Implementation

- **Laravel Framework & Best Practices:** Gunakan Migration, Eloquent ORM relationships, Form Request Validation, dan Middleware RBAC.
- **Strict Concurrency Control:** Wajib menggunakan `DB::transaction()` dan `lockForUpdate()` pada modul POS dan pemotongan stok FIFO batch untuk mencegah _race condition_.
- **Production Readiness:** Antarmuka responsif, aman dari celah XSS/CSRF, serta memiliki audit trail yang lengkap untuk seluruh aktivitas krusial kafe.
