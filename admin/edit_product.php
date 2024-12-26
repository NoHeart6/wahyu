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
$product = null;

// Definisikan path upload
$uploadDir = __DIR__ . '/../uploads/';
// Buat folder jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Ambil data produk yang akan diedit
if (isset($_GET['id'])) {
    try {
        $productId = new MongoDB\BSON\ObjectId($_GET['id']);
        $product = $collection->findOne(['_id' => $productId]);
        
        if (!$product) {
            $_SESSION['error'] = "Produk tidak ditemukan!";
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

// Proses update produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productId = new MongoDB\BSON\ObjectId($_POST['id']);
        
        // Validasi input
        if (empty($_POST['nama']) || empty($_POST['harga']) || empty($_POST['stok'])) {
            $error = "Semua field harus diisi!";
        } else {
            // Update data produk
            $updateData = [
                '$set' => [
                    'nama' => $_POST['nama'],
                    'harga' => (int)$_POST['harga'],
                    'stok' => (int)$_POST['stok'],
                    'deskripsi' => $_POST['deskripsi'],
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]
            ];

            // Update gambar jika ada
            if (!empty($_FILES['gambar']['name'])) {
                // Buat nama file yang unik
                $fileExtension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $fileExtension;
                $targetFilePath = $uploadDir . $fileName;

                // Validasi file
                $allowTypes = array('jpg', 'png', 'jpeg');
                if (in_array(strtolower($fileExtension), $allowTypes)) {
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFilePath)) {
                        // Hapus gambar lama jika ada
                        if (!empty($product->image)) {
                            $oldImagePath = __DIR__ . '/../' . $product->image;
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        $updateData['$set']['image'] = 'uploads/' . $fileName;
                    } else {
                        throw new Exception("Gagal mengupload gambar! Error: " . error_get_last()['message']);
                    }
                } else {
                    throw new Exception("Hanya file JPG, JPEG & PNG yang diperbolehkan!");
                }
            }

            $result = $collection->updateOne(
                ['_id' => $productId],
                $updateData
            );

            if ($result->getModifiedCount() > 0 || !empty($_FILES['gambar']['name'])) {
                $_SESSION['success'] = "Produk berhasil diperbarui!";
                header('Location: index.php');
                exit;
            } else {
                $error = "Tidak ada perubahan data!";
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
    <title>Edit Produk - Admin Panel</title>
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
                        <h4 class="mb-0">Edit Produk</h4>
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
                            <input type="hidden" name="id" value="<?php echo $product->_id; ?>">
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Produk</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($product->nama); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="harga" name="harga" value="<?php echo $product->harga; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="stok" class="form-label">Stok</label>
                                <input type="number" class="form-control" id="stok" name="stok" value="<?php echo $product->stok; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo htmlspecialchars($product->deskripsi); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="gambar" class="form-label">Gambar Produk</label>
                                <?php if (!empty($product->image)): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($product->image); ?>" alt="Current Image" class="img-thumbnail" style="max-width: 200px; height: auto;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar. Format yang diperbolehkan: JPG, JPEG, PNG</small>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">Kembali</a>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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