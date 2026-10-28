<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pos-access');
    }

    public function index(): View
    {
        $products = Product::with('recipeItems.material')->orderBy('name')->get();
        $categories = Product::select('category')->distinct()->whereNotNull('category')->pluck('category');
        return view('pos.index', compact('products', 'categories'));
    }

    // Process POS Transaction + Automated BOM Recipe Stock Deduction (FIFO)
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string'
        ]);

        try {
            $sale = DB::transaction(function () use ($request) {
                $totalPrice = 0;
                foreach ($request->items as $item) {
                    $totalPrice += ($item['quantity'] * $item['price']);
                }

                if ($request->payment_amount < $totalPrice) {
                    throw new \Exception('Payment amount is less than total price!');
                }

                $changeAmount = $request->payment_amount - $totalPrice;
                $invoiceNumber = 'INV-' . date('YmdHis') . '-' . rand(100, 999);

                // 1. Catat Transaksi Penjualan
                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'user_id' => auth()->id(),
                    'total_price' => $totalPrice,
                    'payment_amount' => $request->payment_amount,
                    'change_amount' => $changeAmount,
                    'payment_method' => $request->input('payment_method', 'cash'),
                ]);

                // 2. Loop Items & Deduct BOM Inventory
                foreach ($request->items as $item) {
                    $product = Product::with('recipeItems.material')->findOrFail($item['product_id']);
                    $subtotal = $item['quantity'] * $item['price'];

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'subtotal' => $subtotal,
                    ]);

                    // Pemotongan Stok Bahan Baku Otomatis Sesuai Resep (BOM)
                    foreach ($product->recipeItems as $recipe) {
                        $material = $recipe->material;
                        $deductedQty = $recipe->quantity * $item['quantity']; // dalam recipe_unit

                        // Potong stok bahan
                        $material->decrement('stock', $deductedQty);

                        // Catat log mutasi inventaris (Out / FIFO)
                        InventoryLog::create([
                            'material_id' => $material->id,
                            'type' => 'out',
                            'quantity' => $deductedQty,
                            'stock_after' => $material->fresh()->stock,
                            'reference' => 'POS Sale #' . $invoiceNumber . ' (' . $product->name . ' x' . $item['quantity'] . ')',
                        ]);
                    }
                }

                return $sale;
            });

            return response()->json([
                'success' => true,
                'message' => 'Transaction successful!',
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'change' => $sale->change_amount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // Thermal Receipt Print View
    public function receipt(Sale $sale): View
    {
        $sale->load(['user', 'items.product']);
        return view('pos.receipt', compact('sale'));
    }
}
