@extends('layouts.app')

@section('title', 'Point of Sale (POS Kasir)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">POS</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .product-card {
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            border-radius: 8px;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .category-pill {
            cursor: pointer;
        }
        .cart-table th, .cart-table td {
            vertical-align: middle !important;
        }
    </style>
@endpush

@section('content')
<div class="row">
    <!-- Menu Grid -->
    <div class="col-md-7 col-lg-8">
        <!-- Category Filter -->
        <div class="mb-3 d-flex flex-wrap gap-2">
            <button class="btn btn-primary btn-sm category-btn active mr-1 mb-1" data-cat="all">All Menu</button>
            @foreach($categories as $cat)
                <button class="btn btn-outline-secondary btn-sm category-btn mr-1 mb-1" data-cat="{{ strtolower($cat) }}">{{ $cat }}</button>
            @endforeach
        </div>

        <!-- Product Cards -->
        <div class="row" id="product-container">
            @forelse($products as $prod)
                <div class="col-sm-6 col-md-4 col-lg-3 mb-3 product-item" data-category="{{ strtolower($prod->category ?? 'other') }}">
                    <div class="card h-100 product-card shadow-sm border-0" 
                         onclick="addToCart({{ $prod->id }}, '{{ addslashes($prod->name) }}', {{ $prod->price }})">
                        <div class="card-body p-3 d-flex flex-column justify-content-between text-center">
                            <div>
                                <span class="badge badge-light border text-muted mb-2">{{ $prod->category ?? 'Menu' }}</span>
                                <h6 class="font-weight-bold mb-1 text-dark">{{ $prod->name }}</h6>
                            </div>
                            <div class="mt-3">
                                <h6 class="text-success font-weight-bold mb-0">Rp {{ number_format($prod->price, 0, ',', '.') }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-mug-hot fa-3x mb-3"></i>
                    <p>No products found. Please add products first.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- POS Cart & Checkout -->
    <div class="col-md-5 col-lg-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-shopping-cart mr-1"></i> Current Order</h3>
                <div class="card-tools">
                    <button class="btn btn-outline-danger btn-xs" onclick="clearCart()"><i class="fas fa-trash"></i> Reset</button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive" style="max-height: 380px;">
                <table class="table table-sm cart-table mb-0" id="cart-table">
                    <thead class="bg-light">
                        <tr>
                            <th>Item</th>
                            <th style="width: 90px;" class="text-center">Qty</th>
                            <th style="width: 100px;" class="text-right">Total</th>
                            <th style="width: 30px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-body">
                        <!-- Cart Items -->
                    </tbody>
                </table>
                <div id="cart-empty" class="text-center py-5 text-muted">
                    <i class="fas fa-cash-register fa-2x mb-2 text-gray"></i>
                    <p class="mb-0 text-sm">Cart is empty. Select menu.</p>
                </div>
            </div>

            <!-- Summary & Payment -->
            <div class="card-footer bg-light p-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total:</span>
                    <h4 class="font-weight-bold text-primary mb-0" id="grand-total-display">Rp 0</h4>
                </div>

                <div class="form-group mb-2">
                    <label class="text-xs mb-1">Payment Amount (Bayar):</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                        <input type="number" id="payment-input" class="form-control font-weight-bold text-success" placeholder="0" min="0">
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-3 text-sm">
                    <span>Kembalian (Change):</span>
                    <strong id="change-display" class="text-secondary">Rp 0</strong>
                </div>

                <button type="button" class="btn btn-success btn-block btn-lg font-weight-bold" id="btn-checkout" onclick="processCheckout()" disabled>
                    <i class="fas fa-check-circle mr-1"></i> BAYAR & CETAK STRUK
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    let cart = [];

    // Filter Kategori
    $('.category-btn').click(function() {
        $('.category-btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');

        let cat = $(this).data('cat');
        if (cat === 'all') {
            $('.product-item').show();
        } else {
            $('.product-item').hide();
            $(`.product-item[data-category="${cat}"]`).show();
        }
    });

    function addToCart(id, name, price) {
        let existing = cart.find(i => i.product_id === id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({
                product_id: id,
                name: name,
                price: price,
                quantity: 1
            });
        }
        renderCart();
    }

    function updateQty(id, delta) {
        let item = cart.find(i => i.product_id === id);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                cart = cart.filter(i => i.product_id !== id);
            }
        }
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(i => i.product_id !== id);
        renderCart();
    }

    function clearCart() {
        cart = [];
        $('#payment-input').val('');
        renderCart();
    }

    function renderCart() {
        let tbody = $('#cart-body');
        tbody.empty();
        let total = 0;

        if (cart.length === 0) {
            $('#cart-empty').show();
            $('#btn-checkout').prop('disabled', true);
        } else {
            $('#cart-empty').hide();
            $('#btn-checkout').prop('disabled', false);

            cart.forEach(item => {
                let subtotal = item.quantity * item.price;
                total += subtotal;
                tbody.append(`
                    <tr>
                        <td class="font-weight-bold text-sm">${item.name}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-xs">
                                <button type="button" class="btn btn-outline-secondary btn-xs px-2" onclick="updateQty(${item.product_id}, -1)">-</button>
                                <span class="px-2 align-middle font-weight-bold">${item.quantity}</span>
                                <button type="button" class="btn btn-outline-secondary btn-xs px-2" onclick="updateQty(${item.product_id}, 1)">+</button>
                            </div>
                        </td>
                        <td class="text-right text-sm">Rp ${subtotal.toLocaleString('id-ID')}</td>
                        <td class="text-center">
                            <i class="fas fa-times text-danger cursor-pointer" onclick="removeFromCart(${item.product_id})"></i>
                        </td>
                    </tr>
                `);
            });
        }

        $('#grand-total-display').text('Rp ' + total.toLocaleString('id-ID'));
        calcChange(total);
    }

    $('#payment-input').on('input', function() {
        let total = cart.reduce((acc, i) => acc + (i.quantity * i.price), 0);
        calcChange(total);
    });

    function calcChange(total) {
        let pay = parseFloat($('#payment-input').val()) || 0;
        let change = pay - total;
        if (change >= 0) {
            $('#change-display').text('Rp ' + change.toLocaleString('id-ID')).removeClass('text-danger').addClass('text-success font-weight-bold');
        } else {
            $('#change-display').text('Kurang: Rp ' + Math.abs(change).toLocaleString('id-ID')).removeClass('text-success').addClass('text-danger');
        }
    }

    function processCheckout() {
        let total = cart.reduce((acc, i) => acc + (i.quantity * i.price), 0);
        let payment = parseFloat($('#payment-input').val()) || 0;

        if (payment < total) {
            Swal.fire({
                icon: 'warning',
                title: 'Uang Kurang!',
                text: 'Jumlah pembayaran kurang dari total tagihan.'
            });
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Pembayaran?',
            text: `Total: Rp ${total.toLocaleString('id-ID')} | Bayar: Rp ${payment.toLocaleString('id-ID')}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Proses Transaksi'
        }).then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: "{{ route('pos.store') }}",
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        items: cart,
                        payment_amount: payment,
                        payment_method: 'cash'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Transaksi Berhasil!',
                            text: `Kembalian: Rp ${response.change.toLocaleString('id-ID')}`,
                            confirmButtonText: 'Cetak Struk'
                        }).then(() => {
                            // Buka popup cetak struk termal
                            window.open("{{ url('/pos/receipt') }}/" + response.sale_id, '_blank', 'width=400,height=600');
                            clearCart();
                        });
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal memproses transaksi.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: msg
                        });
                    }
                });
            }
        });
    }
</script>
@endpush
