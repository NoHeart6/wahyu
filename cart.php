<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['error_message'] = 'Silakan login terlebih dahulu untuk mengakses keranjang belanja.';
    header('Location: login.php');
    exit;
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Inisialisasi koneksi database
$db = new Database();
$spareparts = $db->getDatabase()->sparepart;

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);

    if (empty($product_id)) {
        $_SESSION['error_message'] = 'ID Produk tidak valid.';
    } else {
        try {
            $product = $spareparts->findOne(['_id' => new MongoDB\BSON\ObjectId($product_id)]);
            
            if ($product) {
                // Cek apakah produk sudah ada di keranjang
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] === $product_id) {
                        // Update quantity jika masih dalam batas stok
                        $new_quantity = $item['quantity'] + $quantity;
                        if ($new_quantity <= $product->stok) {
                            $item['quantity'] = $new_quantity;
                        }
                        $found = true;
                        break;
                    }
                }
                unset($item);

                // Jika produk belum ada di keranjang dan quantity valid
                if (!$found && $quantity <= $product->stok) {
                    $_SESSION['cart'][] = [
                        'id' => $product_id,
                        'nama' => $product->nama,
                        'harga' => $product->harga,
                        'quantity' => $quantity,
                        'stok' => $product->stok
                    ];
                }

                $_SESSION['success_message'] = 'Produk berhasil ditambahkan ke keranjang!';
            } else {
                $_SESSION['error_message'] = 'Produk tidak ditemukan.';
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Gagal menambahkan produk ke keranjang: ' . $e->getMessage();
        }
    }
}

// Tampilkan pesan sukses jika ada
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

// Tampilkan pesan error jika ada
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Toko Sparepart Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-quantity {
            padding: 0.25rem 0.5rem;
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
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_logged_in'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person"></i> Profil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="orders.php">
                                <i class="bi bi-box"></i> Pesanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-light" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Keranjang Belanja</h2>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="alert alert-info">
                Keranjang belanja Anda kosong. 
                <a href="index.php" class="alert-link">Kembali berbelanja</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $index => $item):
                        $subtotal = $item['harga'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                    <div class="cart-item">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1"><?php echo htmlspecialchars($item['nama']); ?></h5>
                                <p class="text-muted mb-0">
                                    Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?>
                                    <?php if (isset($item['stok'])): ?>
                                        <span class="ms-2">(Stok: <?php echo $item['stok']; ?>)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-3">
                                <div class="quantity-control">
                                    <form method="post" action="update_cart.php" class="d-inline">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="action" value="decrease">
                                        <button type="submit" class="btn btn-outline-secondary btn-quantity" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                                    </form>
                                    <span class="mx-2"><?php echo $item['quantity']; ?></span>
                                    <form method="post" action="update_cart.php" class="d-inline">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <input type="hidden" name="action" value="increase">
                                        <button type="submit" class="btn btn-outline-secondary btn-quantity" <?php echo isset($item['stok']) && $item['quantity'] >= $item['stok'] ? 'disabled' : ''; ?>>+</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <strong>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="col-md-1 text-end">
                                <form method="post" action="update_cart.php">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <h4>Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></h4>
                        <div>
                            <a href="index.php" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left"></i> Lanjut Belanja
                            </a>
                            <a href="checkout.php" class="btn btn-primary">
                                <i class="bi bi-cart-check"></i> Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 