<?php
session_start();
require_once 'config/database.php';

// Inisialisasi koneksi database
$db = new Database();
$collection = $db->getDatabase()->sparepart;

// Filter berdasarkan kategori jika ada
$filter = [];
if (isset($_GET['kategori']) && $_GET['kategori'] !== 'Semua') {
    $filter['kategori'] = $_GET['kategori'];
}

// Ambil data sparepart dengan filter
$spareparts = $collection->find($filter);

// Ambil daftar kategori unik
$categories = $collection->distinct('kategori');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bengkel & Sparepart Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1558981806-ec527fa84c39?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 2rem;
        }
        .navbar {
            background-color: #1a237e !important;
        }
        .card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #1a237e;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .footer {
            background-color: #1a237e;
            color: white;
            padding: 40px 0;
            margin-top: 50px;
        }
        .btn-primary {
            background-color: #1a237e;
            border-color: #1a237e;
        }
        .btn-primary:hover {
            background-color: #0d1757;
            border-color: #0d1757;
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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart"></i> Keranjang
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_logged_in'])): ?>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/index.php">Dashboard Admin</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="profile.php">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['user_nama']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
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

    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Bengkel & Sparepart Motor Terpercaya</h1>
            <p class="lead">Temukan berbagai sparepart berkualitas untuk motor Anda</p>
            <div class="mt-4">
                <a href="#products" class="btn btn-light btn-lg me-2">Lihat Produk</a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">Hubungi Kami</a>
            </div>
        </div>
    </div>

    <div class="container" id="products">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="fw-bold">Daftar Sparepart</h2>
            </div>
            <div class="col-md-6">
                <form class="d-flex" action="search.php" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Cari sparepart..." aria-label="Search">
                    <button class="btn btn-primary" type="submit">Cari</button>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?kategori=Semua" class="btn btn-outline-primary <?php echo (!isset($_GET['kategori']) || $_GET['kategori'] === 'Semua') ? 'active' : ''; ?>">Semua</a>
                    <?php foreach ($categories as $category): ?>
                    <a href="?kategori=<?php echo urlencode($category); ?>" 
                       class="btn btn-outline-primary <?php echo (isset($_GET['kategori']) && $_GET['kategori'] === $category) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($spareparts as $sparepart): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="position-relative">
                        <?php if (!empty($sparepart->gambar)): ?>
                            <img src="<?php echo $sparepart->gambar; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo $sparepart->nama; ?>"
                                 onerror="this.onerror=null; this.src='https://dummyimage.com/600x400/000/fff&text=No+Image';">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-image text-muted" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        <span class="category-badge"><?php echo htmlspecialchars($sparepart->kategori); ?></span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($sparepart->nama); ?></h5>
                        <p class="card-text text-primary fw-bold">Rp <?php echo number_format($sparepart->harga, 0, ',', '.'); ?></p>
                        <p class="card-text"><small class="text-muted">Stok: <?php echo $sparepart->stok; ?></small></p>
                        <div class="d-grid gap-2">
                            <a href="detail.php?id=<?php echo $sparepart->_id; ?>" class="btn btn-primary">Detail</a>
                            <?php if (isset($_SESSION['user_logged_in'])): ?>
                                <form action="cart.php" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $sparepart->_id; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="bi bi-cart-plus"></i> Login untuk Membeli
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold">Tentang Kami</h5>
                    <p>Bengkel dan toko sparepart motor terpercaya dengan pengalaman lebih dari 10 tahun melayani pelanggan.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold">Kontak</h5>
                    <p><i class="bi bi-geo-alt"></i> Jl. Motor No. 123, Kota</p>
                    <p><i class="bi bi-telephone"></i> +62 123 4567 890</p>
                    <p><i class="bi bi-envelope"></i> info@bengkelmotor.com</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold">Jam Operasional</h5>
                    <p>Senin - Sabtu: 08:00 - 17:00</p>
                    <p>Minggu: 09:00 - 15:00</p>
                </div>
            </div>
            <hr class="border-light">
            <div class="text-center">
                <p class="mb-0">&copy; 2023 Bengkel & Sparepart Motor. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 