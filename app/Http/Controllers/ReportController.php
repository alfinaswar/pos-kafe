<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\InventoryLog;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report-access');
    }

    public function sales(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $sales = Sale::with('user')->latest();

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $sales->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }

            return DataTables::of($sales)
                ->addIndexColumn()
                ->addColumn('cashier', fn($r) => $r->user->name ?? '-')
                ->addColumn('total_formatted', fn($r) => 'Rp ' . number_format($r->total_price, 0, ',', '.'))
                ->addColumn('date', fn($r) => $r->created_at->format('d/m/Y H:i'))
                ->make(true);
        }

        return view('reports.sales');
    }

    public function inventory(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $logs = InventoryLog::with('material')->latest();

            return DataTables::of($logs)
                ->addIndexColumn()
                ->addColumn('material_name', fn($r) => $r->material->name ?? '-')
                ->addColumn('type_badge', function ($r) {
                    $color = $r->type === 'in' ? 'badge-success' : 'badge-danger';
                    return '<span class="badge ' . $color . '">' . strtoupper($r->type) . '</span>';
                })
                ->addColumn('quantity_display', function ($r) {
                    $sign = $r->type === 'in' ? '+' : '-';
                    return $sign . ' ' . number_format($r->quantity, 2) . ' ' . ($r->material->recipe_unit ?? '');
                })
                ->addColumn('stock_after_display', fn($r) => number_format($r->stock_after, 2) . ' ' . ($r->material->recipe_unit ?? ''))
                ->addColumn('date', fn($r) => $r->created_at->format('d/m/Y H:i'))
                ->rawColumns(['type_badge'])
                ->make(true);
        }

        return view('reports.inventory');
    }
}
