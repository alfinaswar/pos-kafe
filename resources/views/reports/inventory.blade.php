@extends('layouts.app')

@section('title', 'Laporan Mutasi Stok (Inventory Logs)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Inventory Logs</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Riwayat Mutasi Bahan Baku (Masuk / Keluar)</h3>
    </div>
    <div class="card-body">
        <table id="inventory-report-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Waktu</th>
                    <th>Bahan Baku</th>
                    <th>Tipe</th>
                    <th>Jumlah Mutasi</th>
                    <th>Sisa Stok Akhir</th>
                    <th>Referensi / Keterangan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function () {
            $('#inventory-report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('reports.inventory') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'date', name: 'created_at'},
                    {data: 'material_name', name: 'material.name'},
                    {data: 'type_badge', name: 'type'},
                    {data: 'quantity_display', name: 'quantity'},
                    {data: 'stock_after_display', name: 'stock_after'},
                    {data: 'reference', name: 'reference'}
                ]
            });
        });
    </script>
@endpush
