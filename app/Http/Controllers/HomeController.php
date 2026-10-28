<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        // 1. Total Penjualan Hari ini
        $todaySales = Sale::whereDate('created_at', today())->sum('total_price');
        $todayOrders = Sale::whereDate('created_at', today())->count();

        // 2. Total Menu Aktif
        $totalProducts = Product::count();

        // 3. Stok Bahan Kritis (ROP Warning)
        $lowStockMaterials = Material::whereRaw('stock <= reorder_point')->get();

        // 4. Pending Purchase Orders (Menunggu Approval Owner)
        $pendingPurchases = Purchase::where('status', 'pending')->with('supplier')->get();

        return view('home', compact(
            'todaySales',
            'todayOrders',
            'totalProducts',
            'lowStockMaterials',
            'pendingPurchases'
        ));
    }
}
