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

$error = null;
$success = null;

// Definisikan path upload
$uploadDir = __DIR__ . '/../uploads/';
// Buat folder jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Proses tambah produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        if (empty($_POST['nama']) || empty($_POST['harga']) || empty($_POST['stok'])) {
            $error = "Semua field harus diisi!";
        } else {
            // Data produk baru
            $productData = [
                'nama' => $_POST['nama'],
                'harga' => (int)$_POST['harga'],
                'stok' => (int)$_POST['stok'],
                'deskripsi' => $_POST['deskripsi'],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            // Upload gambar jika ada
            if (!empty($_FILES['gambar']['name'])) {
                // Buat nama file yang unik
                $fileExtension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $fileExtension;
                $targetFilePath = $uploadDir . $fileName;

                // Validasi file
                $allowTypes = array('jpg', 'png', 'jpeg');
                if (in_array(strtolower($fileExtension), $allowTypes)) {
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFilePath)) {
                        $productData['image'] = 'uploads/' . $fileName;
                    } else {
                        throw new Exception("Gagal mengupload gambar! Error: " . error_get_last()['message']);
                    }
                } else {
                    throw new Exception("Hanya file JPG, JPEG & PNG yang diperbolehkan!");
                }
            }

            $result = $collection->insertOne($productData);

            if ($result->getInsertedCount() > 0) {
                $_SESSION['success'] = "Produk berhasil ditambahkan!";
                header('Location: index.php');
                exit;
            } else {
                $error = "Gagal menambahkan produk!";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Selamat datang, <?php echo htmlspecialchars($_SESSION['user_nama']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-2" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Tambah Produk Baru</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>

                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="harga" name="harga" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="stok" class="form-label">Stok</label>
                                <input type="number" class="form-control" id="stok" name="stok" required>
                            </div>

                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="gambar" class="form-label">Gambar Produk</label>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                                <small class="text-muted">Format yang diperbolehkan: JPG, JPEG, PNG</small>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">Tambah Produk</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 