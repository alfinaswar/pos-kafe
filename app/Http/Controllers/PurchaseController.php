<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:purchase-list|purchase-create|purchase-approve|purchase-receive', ['only' => ['index', 'show']]);
        $this->middleware('permission:purchase-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:purchase-approve', ['only' => ['approve', 'reject']]);
        $this->middleware('permission:purchase-receive', ['only' => ['receive']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $purchases = Purchase::with(['supplier', 'user'])->latest();
            return DataTables::of($purchases)
                ->addIndexColumn()
                ->addColumn('total_cost_formatted', function ($row) {
                    return 'Rp ' . number_format($row->total_cost, 0, ',', '.');
                })
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'pending' => 'badge-warning',
                        'approved' => 'badge-primary',
                        'completed' => 'badge-success',
                        'rejected' => 'badge-danger'
                    ];
                    $badgeClass = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $badgeClass . '">' . strtoupper($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $showBtn = '<a href="'.route('purchases.show', $row->id).'" class="btn btn-info btn-sm mr-1" title="Detail PO"><i class="fas fa-eye"></i></a>';
                    return '<div class="text-center">' . $showBtn . '</div>';
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('purchases.index');
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::orderBy('name')->get();
        return view('purchases.create', compact('suppliers', 'materials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'materials' => 'required|array|min:1',
            'materials.*.id' => 'required|exists:materials,id',
            'materials.*.qty' => 'required|numeric|min:0.01',
            'materials.*.price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $invoiceNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $totalCost = 0;

            foreach ($request->materials as $item) {
                $totalCost += ($item['qty'] * $item['price']);
            }

            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'user_id' => auth()->id(),
                'invoice_number' => $invoiceNumber,
                'total_cost' => $totalCost,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            foreach ($request->materials as $item) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'material_id' => $item['id'],
                    'qty_purchase' => $item['qty'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['qty'] * $item['price'],
                ]);
            }
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase Order (PO) draft created successfully.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'user', 'details.material']);
        return view('purchases.show', compact('purchase'));
    }

    // Owner Approval
    public function approve(Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'pending') {
            return back()->with('error', 'Only pending PO can be approved.');
        }

        $purchase->update(['status' => 'approved']);

        return redirect()->route('purchases.show', $purchase->id)
            ->with('success', 'Purchase Order approved successfully.');
    }

    public function reject(Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'pending') {
            return back()->with('error', 'Only pending PO can be rejected.');
        }

        $purchase->update(['status' => 'rejected']);

        return redirect()->route('purchases.show', $purchase->id)
            ->with('success', 'Purchase Order has been rejected.');
    }

    // Goods Receipt (Penerimaan Barang & Konversi Satuan Otomatis ke Recipe Unit)
    public function receive(Purchase $purchase): RedirectResponse
    {
        if ($purchase->status !== 'approved') {
            return back()->with('error', 'Only approved PO can be received into inventory.');
        }

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->details as $detail) {
                $material = $detail->material;
                // Konversi unit beli ke unit resep (misal: 1 karton x 12.000 ml)
                $addedRecipeQty = $detail->qty_purchase * $material->conversion_factor;

                $material->increment('stock', $addedRecipeQty);

                // Catat log mutasi stok masuk
                InventoryLog::create([
                    'material_id' => $material->id,
                    'type' => 'in',
                    'quantity' => $addedRecipeQty,
                    'stock_after' => $material->fresh()->stock,
                    'reference' => 'Goods Receipt: ' . $purchase->invoice_number,
                ]);
            }

            $purchase->update(['status' => 'completed']);
        });

        return redirect()->route('purchases.show', $purchase->id)
            ->with('success', 'Goods successfully received! Inventory stock updated via multi-unit conversion.');
    }
}
