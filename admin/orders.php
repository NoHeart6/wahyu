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
$orders = $db->getDatabase()->orders;
$users = $db->getDatabase()->users;

// Jika request AJAX untuk mendapatkan data realtime
if(isset($_GET['action']) && $_GET['action'] === 'get_orders') {
    try {
        // Convert user_id string to ObjectId
        $allOrders = $orders->aggregate([
            [
                '$lookup' => [
                    'from' => 'users',
                    'let' => ['userId' => ['$toObjectId' => '$user_id']],
                    'pipeline' => [
                        [
                            '$match' => [
                                '$expr' => [
                                    '$eq' => ['$_id', '$$userId']
                                ]
                            ]
                        ]
                    ],
                    'as' => 'user'
                ]
            ],
            [
                '$unwind' => '$user'
            ],
            [
                '$sort' => ['created_at' => -1]
            ]
        ])->toArray();

        $response = [];
        foreach($allOrders as $order) {
            $response[] = [
                'id' => (string)$order->_id,
                'order_number' => substr((string)$order->_id, -6),
                'date' => $order->created_at->toDateTime()->format('d/m/Y H:i'),
                'customer_name' => $order->user->nama,
                'total' => number_format($order->total, 0, ',', '.'),
                'status' => $order->status
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Update status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    try {
        $orderId = new MongoDB\BSON\ObjectId($_POST['order_id']);
        $newStatus = $_POST['status'];
        
        // Update status pesanan
        $orders->updateOne(
            ['_id' => $orderId],
            ['$set' => [
                'status' => $newStatus,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]]
        );
        
        $success = 'Status pesanan berhasil diperbarui!';
    } catch (Exception $e) {
        $error = 'Gagal memperbarui status pesanan: ' . $e->getMessage();
    }
}

// Array untuk label status
$statusLabels = [
    'pending' => ['label' => 'Menunggu Pembayaran', 'class' => 'warning'],
    'paid' => ['label' => 'Sudah Dibayar', 'class' => 'info'],
    'processing' => ['label' => 'Diproses', 'class' => 'primary'],
    'shipped' => ['label' => 'Dikirim', 'class' => 'info'],
    'delivered' => ['label' => 'Selesai', 'class' => 'success'],
    'cancelled' => ['label' => 'Dibatalkan', 'class' => 'danger']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background-color: #f5f6fa; }
        .navbar {
            background-color: #2c3e50;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .navbar-brand, .nav-link {
            color: #fff !important;
        }
        .nav-link:hover {
            color: rgba(255,255,255,0.8) !important;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,.05);
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
            border-radius: 30px;
        }
        #orderTableBody tr {
            transition: all 0.3s ease;
        }
        #orderTableBody tr:hover {
            background-color: #f8f9fa;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop me-2"></i>Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-box-seam me-1"></i>Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">
                            <i class="bi bi-cart-check me-1"></i>Pesanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people me-1"></i>Users
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_nama']); ?>
                        </span>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchOrder" placeholder="Cari pesanan...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus">
                            <option value="">Semua Status</option>
                            <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="filterDate">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" id="refreshOrders">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ID Pesanan</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="orderTableBody">
                            <!-- Data akan diisi oleh JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk memuat data pesanan
        function loadOrders() {
            const tableBody = document.getElementById('orderTableBody');
            const spinner = document.getElementById('loadingSpinner');
            
            spinner.style.display = 'block';
            
            fetch('orders.php?action=get_orders')
                .then(response => response.json())
                .then(orders => {
                    spinner.style.display = 'none';
                    tableBody.innerHTML = '';
                    
                    orders.forEach(order => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="ps-3">
                                <strong>#${order.order_number}</strong>
                            </td>
                            <td>
                                <i class="bi bi-calendar3 me-1"></i>
                                ${order.date}
                            </td>
                            <td>
                                <i class="bi bi-person me-1"></i>
                                ${order.customer_name}
                            </td>
                            <td>
                                <strong>Rp ${order.total}</strong>
                            </td>
                            <td>
                                <span class="badge bg-${getStatusClass(order.status)}">
                                    ${getStatusLabel(order.status)}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-gear me-1"></i>Aksi
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="order_id" value="${order.id}">
                                                <input type="hidden" name="status" value="paid">
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="bi bi-check-circle me-2"></i>Konfirmasi Pembayaran
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="order_id" value="${order.id}">
                                                <input type="hidden" name="status" value="processing">
                                                <button type="submit" class="dropdown-item text-info">
                                                    <i class="bi bi-box-seam me-2"></i>Proses Pesanan
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="order_id" value="${order.id}">
                                                <input type="hidden" name="status" value="shipped">
                                                <button type="submit" class="dropdown-item text-primary">
                                                    <i class="bi bi-truck me-2"></i>Kirim Pesanan
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="order_id" value="${order.id}">
                                                <input type="hidden" name="status" value="delivered">
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="bi bi-check2-circle me-2"></i>Selesaikan Pesanan
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="order_id" value="${order.id}">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-x-circle me-2"></i>Batalkan Pesanan
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    spinner.style.display = 'none';
                });
        }

        // Fungsi helper untuk mendapatkan label status
        function getStatusLabel(status) {
            const labels = {
                'pending': 'Menunggu Pembayaran',
                'paid': 'Sudah Dibayar',
                'processing': 'Diproses',
                'shipped': 'Dikirim',
                'delivered': 'Selesai',
                'cancelled': 'Dibatalkan'
            };
            return labels[status] || status;
        }

        // Fungsi helper untuk mendapatkan kelas status
        function getStatusClass(status) {
            const classes = {
                'pending': 'warning',
                'paid': 'info',
                'processing': 'primary',
                'shipped': 'info',
                'delivered': 'success',
                'cancelled': 'danger'
            };
            return classes[status] || 'secondary';
        }

        // Event listener untuk refresh manual
        document.getElementById('refreshOrders').addEventListener('click', loadOrders);

        // Event listener untuk pencarian
        document.getElementById('searchOrder').addEventListener('keyup', function(e) {
            const searchText = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#orderTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Event listener untuk filter status
        document.getElementById('filterStatus').addEventListener('change', function(e) {
            const status = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#orderTableBody tr');
            
            rows.forEach(row => {
                if (!status) {
                    row.style.display = '';
                    return;
                }
                
                const statusBadge = row.querySelector('.badge');
                const rowStatus = statusBadge.textContent.toLowerCase();
                row.style.display = rowStatus.includes(status) ? '' : 'none';
            });
        });

        // Event listener untuk filter tanggal
        document.getElementById('filterDate').addEventListener('change', function(e) {
            const selectedDate = new Date(e.target.value);
            const rows = document.querySelectorAll('#orderTableBody tr');
            
            rows.forEach(row => {
                const dateCell = row.querySelector('td:nth-child(2)');
                const orderDate = new Date(dateCell.textContent.trim());
                
                if (selectedDate.toDateString() === orderDate.toDateString()) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Load orders pertama kali
        loadOrders();

        // Set interval untuk memperbarui data setiap 30 detik
        setInterval(loadOrders, 30000);

        // Variabel global untuk menyimpan status labels
        const statusLabels = <?php echo json_encode($statusLabels); ?>;
    </script>
</body>
</html> 
