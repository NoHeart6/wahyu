<?php
session_start();
require_once 'config/database.php';

// Fungsi helper untuk menangani htmlspecialchars dengan aman
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Ambil data user
$db = new Database();
$users = $db->getDatabase()->users;
$user = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);

// Redirect jika user tidak ditemukan
if (!$user) {
    $_SESSION = array();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Inisialisasi nilai default jika null
$user->username = $user->username ?? '';
$user->nama = $user->nama ?? '';
$user->email = $user->email ?? '';
$user->alamat = $user->alamat ?? '';
$user->telepon = $user->telepon ?? '';

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($nama) || empty($email)) {
        $error = 'Nama dan email harus diisi!';
    } else {
        $updateData = [
            'nama' => $nama,
            'email' => $email,
            'alamat' => $alamat,
            'telepon' => $telepon
        ];
        
        // Update password jika diisi
        if (!empty($password) && !empty($new_password)) {
            if (password_verify($password, $user->password)) {
                $updateData['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            } else {
                $error = 'Password lama tidak sesuai!';
            }
        }
        
        if (empty($error)) {
            try {
                $users->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])],
                    ['$set' => $updateData]
                );
                $success = 'Profil berhasil diperbarui!';
                
                // Refresh data user
                $user = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan saat memperbarui profil.';
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
    <title>Profil Saya - Toko Sparepart Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-person"></i> <?php echo safe_html($user->nama); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-2" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Menu</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="profile.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-person"></i> Profil Saya
                        </a>
                        <a href="orders.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bag"></i> Riwayat Pesanan
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Profil</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo safe_html($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo safe_html($success); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo safe_html($user->username); ?>" readonly disabled>
                            </div>
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo safe_html($user->nama); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo safe_html($user->email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3"><?php echo safe_html($user->alamat); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="telepon" class="form-label">No. Telepon</label>
                                <input type="tel" class="form-control" id="telepon" name="telepon" value="<?php echo safe_html($user->telepon); ?>">
                            </div>
                            
                            <h5 class="mt-4 mb-3">Ganti Password</h5>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password Lama</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 