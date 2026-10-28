<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\Product;
use App\Models\RecipeItem;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLog;
use Spatie\Permission\Models\Role;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users untuk tiap Role (Owner, Manager, Cashier)
        $owner = User::firstOrCreate(
            ['email' => 'owner@kayaraya.com'],
            ['name' => 'Budi Santoso (Owner)', 'password' => Hash::make('password')]
        );
        $owner->syncRoles(['Owner']);

        $manager = User::firstOrCreate(
            ['email' => 'manager@kayaraya.com'],
            ['name' => 'Siti Rahma (Manager)', 'password' => Hash::make('password')]
        );
        $manager->syncRoles(['Manager']);

        $cashier = User::firstOrCreate(
            ['email' => 'kasir@kayaraya.com'],
            ['name' => 'Rian Kasir', 'password' => Hash::make('password')]
        );
        $cashier->syncRoles(['Cashier']);

        // 2. Suppliers
        $supDairy = Supplier::firstOrCreate(
            ['name' => 'PT Sumber Susu Nusantara'],
            ['phone' => '081234567890', 'address' => 'Jl. Soekarno Hatta No. 45, Pekanbaru']
        );

        $supCoffee = Supplier::firstOrCreate(
            ['name' => 'CV Roastery Kopi Mandiri'],
            ['phone' => '081398765432', 'address' => 'Jl. Riau No. 12, Pekanbaru']
        );

        $supPackaging = Supplier::firstOrCreate(
            ['name' => 'Toko Kemasan Abadi'],
            ['phone' => '082155667788', 'address' => 'Jl. Tuanku Tambusai No. 88, Pekanbaru']
        );

        // 3. Raw Materials & Multi-Unit Conversion
        // Susu UHT: Beli per Karton (12 Liter / 12.000 ml)
        $matSusu = Material::firstOrCreate(
            ['name' => 'Susu UHT Plain'],
            [
                'category' => 'Dairy',
                'stock' => 24000, // 24.000 ml
                'purchase_unit' => 'Karton',
                'recipe_unit' => 'ml',
                'conversion_factor' => 12000,
                'reorder_point' => 5000,
            ]
        );

        // Biji Kopi Arabika: Beli per Kg (1.000 gram)
        $matKopi = Material::firstOrCreate(
            ['name' => 'Biji Kopi Arabika House Blend'],
            [
                'category' => 'Coffee Beans',
                'stock' => 5000, // 5.000 gram
                'purchase_unit' => 'Kg',
                'recipe_unit' => 'gram',
                'conversion_factor' => 1000,
                'reorder_point' => 1500,
            ]
        );

        // Gula Aren Cair: Beli per Jerigen (5.000 ml)
        $matAren = Material::firstOrCreate(
            ['name' => 'Sirup Gula Aren Asli'],
            [
                'category' => 'Syrup',
                'stock' => 10000, // 10.000 ml
                'purchase_unit' => 'Jerigen',
                'recipe_unit' => 'ml',
                'conversion_factor' => 5000,
                'reorder_point' => 2000,
            ]
        );

        // Cup Dingin 16oz: Beli per Dus (1.000 pcs) - Diset mendekati ROP untuk demo alert
        $matCup = Material::firstOrCreate(
            ['name' => 'Paper Cup Dingin 16oz + Tutup'],
            [
                'category' => 'Packaging',
                'stock' => 150, // Stok kritis di bawah ROP 200
                'purchase_unit' => 'Dus',
                'recipe_unit' => 'pcs',
                'conversion_factor' => 1000,
                'reorder_point' => 200,
            ]
        );

        // Sirup Karamel: Beli per Botol (750 ml)
        $matKaramel = Material::firstOrCreate(
            ['name' => 'Sirup Karamel Gourmet'],
            [
                'category' => 'Syrup',
                'stock' => 1500, // 1.500 ml
                'purchase_unit' => 'Botol',
                'recipe_unit' => 'ml',
                'conversion_factor' => 750,
                'reorder_point' => 500,
            ]
        );

        // 4. Products (Menu Kafe) & Bill of Materials (BOM)
        // Menu 1: Es Kopi Susu Kaya Raya
        $prodKopiSusu = Product::firstOrCreate(
            ['name' => 'Es Kopi Susu Kaya Raya'],
            [
                'category' => 'Coffee',
                'price' => 22000,
                'detail' => 'Signature kopi susu dengan gula aren pilihan khas Kedai Kopi Kaya Raya.'
            ]
        );
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matKopi->id], ['quantity' => 18]); // 18 gram kopi
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matSusu->id], ['quantity' => 120]); // 120 ml susu
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matAren->id], ['quantity' => 25]); // 25 ml aren
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matCup->id], ['quantity' => 1]); // 1 pcs cup

        // Menu 2: Caramel Latte Ice
        $prodCaramel = Product::firstOrCreate(
            ['name' => 'Caramel Latte Ice'],
            [
                'category' => 'Coffee',
                'price' => 26000,
                'detail' => 'Espresso berpadu susu segar dan gurihnya sirup karamel gourmet.'
            ]
        );
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matKopi->id], ['quantity' => 18]);
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matSusu->id], ['quantity' => 150]);
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matKaramel->id], ['quantity' => 20]);
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matCup->id], ['quantity' => 1]);

        // Menu 3: Americano Ice
        $prodAmericano = Product::firstOrCreate(
            ['name' => 'Americano Ice'],
            [
                'category' => 'Coffee',
                'price' => 18000,
                'detail' => 'Double shot espresso dengan air dingin segar.'
            ]
        );
        RecipeItem::firstOrCreate(['product_id' => $prodAmericano->id, 'material_id' => $matKopi->id], ['quantity' => 18]);
        RecipeItem::firstOrCreate(['product_id' => $prodAmericano->id, 'material_id' => $matCup->id], ['quantity' => 1]);

        // 5. Purchase Order (PO) Dummy (1 Completed, 1 Pending Approval)
        // PO Completed
        $poCompleted = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-20261028-0001'],
            [
                'supplier_id' => $supDairy->id,
                'user_id' => $manager->id,
                'total_cost' => 380000,
                'status' => 'completed',
                'notes' => 'Pengadaan rutin susu UHT mingguan.'
            ]
        );
        PurchaseDetail::firstOrCreate(
            ['purchase_id' => $poCompleted->id, 'material_id' => $matSusu->id],
            ['qty_purchase' => 2, 'unit_price' => 190000, 'subtotal' => 380000]
        );

        // PO Pending (Menunggu Approval Owner di Dashboard)
        $poPending = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-20261028-0002'],
            [
                'supplier_id' => $supPackaging->id,
                'user_id' => $manager->id,
                'total_cost' => 450000,
                'status' => 'pending',
                'notes' => 'Restock darurat cup dingin karena stok mendekati ROP.'
            ]
        );
        PurchaseDetail::firstOrCreate(
            ['purchase_id' => $poPending->id, 'material_id' => $matCup->id],
            ['qty_purchase' => 1, 'unit_price' => 450000, 'subtotal' => 450000]
        );

        // 6. Transaksi POS Dummy (Sales)
        $sale = Sale::firstOrCreate(
            ['invoice_number' => 'INV-202610280915-101'],
            [
                'user_id' => $cashier->id,
                'total_price' => 48000,
                'payment_amount' => 50000,
                'change_amount' => 2000,
                'payment_method' => 'cash',
            ]
        );
        SaleItem::firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $prodKopiSusu->id],
            ['quantity' => 1, 'unit_price' => 22000, 'subtotal' => 22000]
        );
        SaleItem::firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $prodCaramel->id],
            ['quantity' => 1, 'unit_price' => 26000, 'subtotal' => 26000]
        );

        // 7. Inventory Logs
        InventoryLog::firstOrCreate(
            ['reference' => 'Goods Receipt: PO-20261028-0001'],
            [
                'material_id' => $matSusu->id,
                'type' => 'in',
                'quantity' => 24000, // 2 karton x 12.000 ml
                'stock_after' => 24000,
            ]
        );
        InventoryLog::firstOrCreate(
            ['reference' => 'POS Sale #INV-202610280915-101 (Es Kopi Susu x1)'],
            [
                'material_id' => $matKopi->id,
                'type' => 'out',
                'quantity' => 18,
                'stock_after' => 4982,
            ]
        );
    }
}
