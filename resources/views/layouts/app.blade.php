<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIP3-KAFE') }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('assets/dist/css/adminlte.min.css') }}">

    @stack('styles')
    @yield('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Top Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('home') }}" class="nav-link">Home</a>
            </li>
            @can('pos-access')
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('pos.index') }}" class="nav-link text-success font-weight-bold"><i class="fas fa-cash-register mr-1"></i> Buka Kasir (POS)</a>
                </li>
            @endcan
        </ul>

        <ul class="navbar-nav ml-auto">
            @guest
                @if (Route::has('login'))
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a></li>
                @endif
            @else
                <li class="nav-item dropdown">
                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle mr-1"></i> {{ Auth::user()->name }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <span class="dropdown-item dropdown-header">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</span>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt mr-2 text-danger"></i> {{ __('Logout') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>
            @endguest
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('home') }}" class="brand-link">
            <img src="{{ asset('assets/dist/img/AdminLTELogo.png') }}" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-bold">SIP3-KAFE</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{ asset('assets/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    @auth
                        <a href="#" class="d-block">{{ Auth::user()->name }}</a>
                        <span class="badge badge-success text-xs">{{ Auth::user()->roles->pluck('name')->first() ?? 'Staff' }}</span>
                    @else
                        <a href="#" class="d-block">Guest</a>
                    @endauth
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    @can('pos-access')
                    <li class="nav-item">
                        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }} bg-success">
                            <i class="nav-icon fas fa-cash-register"></i>
                            <p>Kasir (POS)</p>
                        </a>
                    </li>
                    @endcan

                    <!-- Master Data -->
                    @if(auth()->user()->can('material-list') || auth()->user()->can('product-list') || auth()->user()->can('supplier-list'))
                    <li class="nav-header">MASTER DATA & RESEP</li>

                    @can('material-list')
                    <li class="nav-item">
                        <a href="{{ route('materials.index') }}" class="nav-link {{ request()->routeIs('materials.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-boxes"></i>
                            <p>Bahan Baku & Unit</p>
                        </a>
                    </li>
                    @endcan

                    @can('product-list')
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-utensils"></i>
                            <p>Menu & Resep (BOM)</p>
                        </a>
                    </li>
                    @endcan

                    @can('supplier-list')
                    <li class="nav-item">
                        <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>Suppliers</p>
                        </a>
                    </li>
                    @endcan
                    @endif

                    <!-- Purchasing -->
                    @can('purchase-list')
                    <li class="nav-header">PENGADAAN & STOK</li>
                    <li class="nav-item">
                        <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Purchase Orders (PO)</p>
                        </a>
                    </li>
                    @endcan

                    <!-- Reports -->
                    @can('report-access')
                    <li class="nav-header">LAPORAN</li>
                    <li class="nav-item">
                        <a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Laporan Penjualan</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('reports.inventory') }}" class="nav-link {{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Mutasi Stok Gudang</p>
                        </a>
                    </li>
                    @endcan

                    <!-- System Users -->
                    @if(auth()->user()->can('user-list') || auth()->user()->can('role-list'))
                    <li class="nav-header">PENGATURAN SISTEM</li>
                    @can('user-list')
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Manage Users</p>
                        </a>
                    </li>
                    @endcan

                    @can('role-list')
                    <li class="nav-item">
                        <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Manage Roles</p>
                        </a>
                    </li>
                    @endcan
                    @endif
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 font-weight-bold">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; {{ date('Y') }} Kedai Kopi Kaya Raya.</strong> All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>SIP3-KAFE</b> v1.0.0
        </div>
    </footer>
</div>

<!-- Scripts -->
<script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/dist/js/adminlte.min.js') }}"></script>

@stack('scripts')
@yield('scripts')
</body>
</html>
