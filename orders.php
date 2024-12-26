<?php
session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Ambil data user dan pesanan
$db = new Database();
$users = $db->getDatabase()->users;
$orders = $db->getDatabase()->orders;

$user = $users->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])]);
$userOrders = $orders->find(
    ['user_id' => $_SESSION['user_id']],
    ['sort' => ['created_at' => -1]] // Urutkan dari yang terbaru
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Toko Sparepart Motor</title>
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

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Menu</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person"></i> Profil Saya
                        </a>
                        <a href="orders.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-bag"></i> Riwayat Pesanan
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Riwayat Pesanan</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($userOrders as $order): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Order #<?php echo substr($order->_id, -8); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $order->created_at->toDateTime()->format('d/m/Y H:i'); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php 
                                            echo match($order->status) {
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'shipped' => 'primary',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($order->status); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Harga</th>
                                                <th>Jumlah</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order->items as $item): ?>
                                            <tr>
                                                <td><?php echo $item->nama; ?></td>
                                                <td>Rp <?php echo number_format($item->harga, 0, ',', '.'); ?></td>
                                                <td><?php echo $item->quantity; ?></td>
                                                <td>Rp <?php echo number_format($item->harga * $item->quantity, 0, ',', '.'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Total Pembayaran:</strong></td>
                                                <td><strong>Rp <?php echo number_format($order->total, 0, ',', '.'); ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <div class="mt-3">
                                    <h6>Alamat Pengiriman:</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order->shipping_address)); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 