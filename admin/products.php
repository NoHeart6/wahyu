<?php
session_start();

// Cek apakah sudah login dan role adalah admin
if (!isset($_SESSION['user_logged_in']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

// Inisialisasi koneksi database
$db = new Database();
$collection = $db->getDatabase()->sparepart;

// Handle delete product
if (isset($_GET['delete'])) {
    try {
        $productId = new MongoDB\BSON\ObjectId($_GET['delete']);
        
        // Cek apakah produk ada sebelum dihapus
        $product = $collection->findOne(['_id' => $productId]);
        if ($product) {
            $result = $collection->deleteOne(['_id' => $productId]);
            if ($result->getDeletedCount() > 0) {
                $_SESSION['success'] = "Produk berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus produk!";
            }
        } else {
            $_SESSION['error'] = "Produk tidak ditemukan!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: index.php');
    exit;
}

// Ambil semua data sparepart
try {
    $spareparts = $collection->find([]);
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    $spareparts = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="products.php">
                                Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="orders.php">
                                Pesanan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="logout.php">
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1>Kelola Produk</h1>
                    <a href="add_product.php" class="btn btn-primary">Tambah Produk Baru</a>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Produk berhasil dihapus!
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spareparts as $product): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $product->image; ?>" alt="<?php echo $product->name; ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?php echo $product->name; ?></td>
                                    <td>Rp <?php echo number_format($product->price, 0, ',', '.'); ?></td>
                                    <td><?php echo $product->stock; ?></td>
                                    <td>
                                        <a href="edit_product.php?id=<?php echo $product->_id; ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <a href="products.php?delete=<?php echo $product->_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 