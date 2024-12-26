<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cek apakah keranjang kosong
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Inisialisasi koneksi database
$db = new Database();
$users = $db->getDatabase()->users;
$orders = $db->getDatabase()->orders;
$spareparts = $db->getDatabase()->sparepart;

// Validasi dan bersihkan keranjang dari produk yang tidak valid
$invalid_items = [];
foreach ($_SESSION['cart'] as $key => $item) {
    try {
        $product = $spareparts->findOne(['_id' => new MongoDB\BSON\ObjectId($item['id'])]);
        if (!$product) {
            $invalid_items[] = $item['nama'];
            unset($_SESSION['cart'][$key]);
        }
    } catch (Exception $e) {
        $invalid_items[] = $item['nama'];
        unset($_SESSION['cart'][$key]);
    }
}

// Reindex array keranjang
$_SESSION['cart'] = array_values($_SESSION['cart']);

// Jika ada item yang tidak valid, tampilkan pesan
if (!empty($invalid_items)) {
    $_SESSION['error_message'] = 'Beberapa produk telah dihapus dari keranjang karena tidak tersedia: ' . implode(', ', $invalid_items);
    header('Location: cart.php');
    exit;
}

// Cek lagi apakah keranjang kosong setelah validasi
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Ambil data user
$user = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);

$error = '';
$success = '';

// Tambahkan array untuk informasi rekening bank
$bank_accounts = [
    'bri' => [
        'nama' => 'Bank BRI',
        'no_rek' => '1234-5678-9012-3456',
        'atas_nama' => 'Toko Sparepart Motor'
    ],
    'bni' => [
        'nama' => 'Bank BNI',
        'no_rek' => '0123-4567-8901',
        'atas_nama' => 'Toko Sparepart Motor'
    ],
    'bca' => [
        'nama' => 'Bank BCA',
        'no_rek' => '8901-2345-6789',
        'atas_nama' => 'Toko Sparepart Motor'
    ]
];

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alamat_pengiriman = trim($_POST['alamat_pengiriman'] ?? '');
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? '';
    
    if (empty($alamat_pengiriman) || empty($metode_pembayaran)) {
        $error = 'Semua field harus diisi!';
    } else {
        // Hitung total dan persiapkan items
        $total = 0;
        $items = [];
        $stockError = false;
        
        foreach ($_SESSION['cart'] as $item) {
            // Cek stok
            $product = $spareparts->findOne(['_id' => new MongoDB\BSON\ObjectId($item['id'])]);
            
            // Debug informasi
            if (!$product) {
                $error = 'Produk dengan ID ' . $item['id'] . ' tidak ditemukan.';
                $stockError = true;
                break;
            }
            
            if ($product->stok < $item['quantity']) {
                $error = 'Stok tidak mencukupi untuk produk ' . $item['nama'] . '. Stok tersedia: ' . $product->stok . ', Jumlah yang diminta: ' . $item['quantity'];
                $stockError = true;
                break;
            }
            
            $subtotal = $item['harga'] * $item['quantity'];
            $total += $subtotal;
            
            $items[] = [
                'product_id' => $item['id'],
                'nama' => $item['nama'],
                'harga' => $item['harga'],
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal
            ];
        }
        
        if (!$stockError) {
            try {
                // Buat order baru
                $order = [
                    'user_id' => $_SESSION['user_id'],
                    'items' => $items,
                    'total' => $total,
                    'shipping_address' => $alamat_pengiriman,
                    'payment_method' => $metode_pembayaran,
                    'status' => 'pending',
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $result = $orders->insertOne($order);
                
                if ($result->getInsertedCount()) {
                    // Update stok produk
                    foreach ($items as $item) {
                        $spareparts->updateOne(
                            ['_id' => new MongoDB\BSON\ObjectId($item['product_id'])],
                            ['$inc' => ['stok' => -$item['quantity']]]
                        );
                    }
                    
                    // Kosongkan keranjang
                    unset($_SESSION['cart']);
                    $_SESSION['cart'] = [];
                    
                    $success = 'Pesanan berhasil dibuat!';
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan saat memproses pesanan.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Toko Sparepart Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .navbar-brand {
            font-weight: 600;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.05);
            padding: 1.25rem;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }
        .card-header h5 {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        .form-control, .form-select {
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }
        .btn-primary {
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            background-color: #4a90e2;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #357abd;
            transform: translateY(-1px);
        }
        .bank-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .bank-info p {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .alert-info {
            background-color: #e1f0ff;
            border-color: #b8daff;
            color: #004085;
        }
        .order-summary-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .order-summary-item:last-child {
            border-bottom: none;
        }
        .total-amount {
            font-size: 1.25rem;
            color: #2c3e50;
            padding-top: 1rem;
            border-top: 2px solid #e2e8f0;
        }
        .product-name {
            color: #2c3e50;
            font-weight: 500;
        }
        .product-quantity {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .product-price {
            color: #2c3e50;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Toko Sparepart Motor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart"></i> Keranjang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($user->nama); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-2" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-cart-check me-2"></i>Checkout</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                            <br>
                            <a href="orders.php" class="alert-link">Lihat pesanan saya</a>
                        </div>
                        <?php else: ?>
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="nama" class="form-label">
                                    <i class="bi bi-person me-2"></i>Nama Penerima
                                </label>
                                <input type="text" class="form-control" id="nama" value="<?php echo htmlspecialchars($user->nama); ?>" readonly>
                            </div>
                            <div class="mb-4">
                                <label for="alamat_pengiriman" class="form-label">
                                    <i class="bi bi-geo-alt me-2"></i>Alamat Pengiriman
                                </label>
                                <textarea class="form-control" id="alamat_pengiriman" name="alamat_pengiriman" rows="3" required><?php echo htmlspecialchars($user->alamat ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label for="metode_pembayaran" class="form-label">
                                    <i class="bi bi-credit-card me-2"></i>Metode Pembayaran
                                </label>
                                <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required onchange="toggleBankDetails(this.value)">
                                    <option value="">Pilih metode pembayaran...</option>
                                    <option value="bri">Transfer Bank BRI</option>
                                    <option value="bni">Transfer Bank BNI</option>
                                    <option value="bca">Transfer Bank BCA</option>
                                    <option value="cod">Cash on Delivery (COD)</option>
                                </select>
                            </div>

                            <div id="bank_details" class="mb-4" style="display: none;">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="bi bi-bank me-2"></i>Informasi Rekening Bank
                                        </h6>
                                        <?php foreach ($bank_accounts as $code => $bank): ?>
                                        <div class="bank-info" id="bank_<?php echo $code; ?>" style="display: none;">
                                            <p class="mb-2"><strong><?php echo $bank['nama']; ?></strong></p>
                                            <p class="mb-2">No. Rekening: <strong class="text-primary"><?php echo $bank['no_rek']; ?></strong></p>
                                            <p class="mb-0">Atas Nama: <strong><?php echo $bank['atas_nama']; ?></strong></p>
                                        </div>
                                        <?php endforeach; ?>
                                        <div class="alert alert-info mt-3 mb-0">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <small>
                                                Silakan transfer sesuai dengan total pembayaran ke rekening di atas.
                                                Pesanan akan diproses setelah pembayaran dikonfirmasi.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check2-circle me-2"></i>Buat Pesanan
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Ringkasan Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $total = 0;
                        foreach ($_SESSION['cart'] as $item):
                            $subtotal = $item['harga'] * $item['quantity'];
                            $total += $subtotal;
                        ?>
                        <div class="order-summary-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="product-name"><?php echo $item['nama']; ?></div>
                                    <div class="product-quantity"><?php echo $item['quantity']; ?>x @ Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></div>
                                </div>
                                <div class="product-price">
                                    Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="total-amount d-flex justify-content-between align-items-center">
                            <strong>Total Pembayaran:</strong>
                            <strong class="text-primary">Rp <?php echo number_format($total, 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleBankDetails(method) {
        const bankDetails = document.getElementById('bank_details');
        const allBankInfo = document.querySelectorAll('.bank-info');
        
        // Sembunyikan semua detail bank
        allBankInfo.forEach(info => info.style.display = 'none');
        
        if (method && method !== 'cod') {
            // Tampilkan container detail bank
            bankDetails.style.display = 'block';
            // Tampilkan detail bank yang dipilih
            document.getElementById('bank_' + method).style.display = 'block';
        } else {
            // Sembunyikan container detail bank
            bankDetails.style.display = 'none';
        }
    }
    </script>
</body>
</html> 