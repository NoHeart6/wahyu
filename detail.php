<?php
session_start();
require_once 'config/database.php';

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Inisialisasi koneksi database
$db = new Database();
$collection = $db->getDatabase()->sparepart;

// Ambil ID produk dari parameter URL
$id = isset($_GET['id']) ? new MongoDB\BSON\ObjectId($_GET['id']) : null;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Ambil detail sparepart
$sparepart = $collection->findOne(['_id' => $id]);

if (!$sparepart) {
    header('Location: index.php');
    exit;
}

// Ambil produk terkait dengan kategori yang sama
$related_products = $collection->find([
    'kategori' => $sparepart->kategori,
    '_id' => ['$ne' => $id]
], ['limit' => 4]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sparepart->nama; ?> - Toko Sparepart Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .navbar {
            background-color: #1a237e !important;
        }
        .product-title {
            color: #1a237e;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .product-price {
            font-size: 2rem;
            color: #d32f2f;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .product-category {
            background-color: #1a237e;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .product-stock {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        .product-description {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .product-image {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .product-features {
            background-color: #e8eaf6;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .feature-item {
            margin-bottom: 10px;
        }
        .feature-icon {
            color: #1a237e;
            margin-right: 10px;
        }
        .related-products {
            background-color: #f5f5f5;
            padding: 40px 0;
            margin-top: 3rem;
        }
        .related-product-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .related-product-card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background-color: #1a237e;
            border-color: #1a237e;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #0d1757;
            border-color: #0d1757;
        }
        .quantity-input {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
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
                    <?php if (isset($_SESSION['user_logged_in'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['user_nama']); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light ms-2" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-light ms-2" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="register.php">Daftar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo $sparepart->gambar; ?>" class="img-fluid product-image" alt="<?php echo $sparepart->nama; ?>" onerror="this.onerror=null; this.src='https://dummyimage.com/600x400/000/fff&text=No+Image';">
            </div>
            <div class="col-md-6">
                <h1 class="product-title"><?php echo $sparepart->nama; ?></h1>
                <div class="product-category">
                    <i class="bi bi-tag"></i> <?php echo $sparepart->kategori; ?>
                </div>
                <p class="product-price">Rp <?php echo number_format($sparepart->harga, 0, ',', '.'); ?></p>
                <p class="product-stock">
                    <i class="bi bi-box-seam"></i> Stok: 
                    <span class="badge bg-<?php echo $sparepart->stok > 0 ? 'success' : 'danger'; ?>">
                        <?php echo $sparepart->stok > 0 ? $sparepart->stok . ' unit' : 'Habis'; ?>
                    </span>
                </p>
                
                <div class="product-description">
                    <h4><i class="bi bi-info-circle"></i> Deskripsi Produk:</h4>
                    <p><?php echo $sparepart->deskripsi; ?></p>
                </div>

                <div class="product-features">
                    <h4 class="mb-3">Keunggulan Produk:</h4>
                    <div class="feature-item">
                        <i class="bi bi-check-circle feature-icon"></i> Kualitas Original
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-check-circle feature-icon"></i> Garansi Resmi
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-check-circle feature-icon"></i> Pengiriman Cepat
                    </div>
                </div>

                <?php if (isset($_SESSION['user_logged_in'])): ?>
                    <?php if ($sparepart->stok > 0): ?>
                        <form action="cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $sparepart->_id; ?>">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Jumlah Pembelian</label>
                                <div class="input-group quantity-input">
                                    <button type="button" class="btn btn-outline-secondary" onclick="decrementQuantity()">-</button>
                                    <input type="number" class="form-control text-center" id="quantity" name="quantity" value="1" min="1" max="<?php echo $sparepart->stok; ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="incrementQuantity()">+</button>
                                </div>
                            </div>
                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">
                                <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Maaf, stok sedang kosong.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Silakan <a href="login.php">login</a> atau <a href="register.php">daftar</a> untuk melakukan pembelian.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="related-products">
        <div class="container">
            <h3 class="mb-4">Produk Terkait</h3>
            <div class="row">
                <?php foreach ($related_products as $related): ?>
                <div class="col-md-3 mb-4">
                    <div class="card related-product-card h-100">
                        <img src="<?php echo $related->gambar; ?>" class="card-img-top" alt="<?php echo $related->nama; ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $related->nama; ?></h5>
                            <p class="card-text text-primary fw-bold">Rp <?php echo number_format($related->harga, 0, ',', '.'); ?></p>
                            <a href="detail.php?id=<?php echo $related->_id; ?>" class="btn btn-outline-primary w-100">Lihat Detail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function incrementQuantity() {
            var input = document.getElementById('quantity');
            var max = parseInt(input.getAttribute('max'));
            var value = parseInt(input.value);
            if (value < max) {
                input.value = value + 1;
            }
        }

        function decrementQuantity() {
            var input = document.getElementById('quantity');
            var value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
            }
        }
    </script>
</body>
</html> 