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
$users = $db->getDatabase()->users;

// Jika request AJAX untuk mendapatkan data realtime
if(isset($_GET['action']) && $_GET['action'] === 'get_users') {
    $allUsers = $users->find(
        [],
        [
            'sort' => ['created_at' => -1],
            'limit' => 50
        ]
    )->toArray();

    $response = [];
    foreach($allUsers as $user) {
        $response[] = [
            'id' => (string)$user->_id,
            'nama' => $user->nama,
            'email' => $user->email,
            'telepon' => $user->telepon ?? '-',
            'alamat' => $user->alamat ?? '-',
            'role' => $user->role ?? 'user',
            'created_at' => $user->created_at->toDateTime()->format('d/m/Y H:i')
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Update role user jika ada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    try {
        $users->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['user_id'])],
            ['$set' => ['role' => $_POST['role']]]
        );
        $success = 'Role pengguna berhasil diperbarui!';
    } catch (Exception $e) {
        $error = 'Gagal memperbarui role pengguna.';
    }
}

// Array untuk label role
$roleLabels = [
    'admin' => ['label' => 'Admin', 'class' => 'danger'],
    'user' => ['label' => 'User', 'class' => 'primary']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - Admin Dashboard</title>
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
        .user-detail {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        #userTableBody tr {
            transition: all 0.3s ease;
        }
        #userTableBody tr:hover {
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
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart-check me-1"></i>Pesanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
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
                            <input type="text" class="form-control" id="searchUser" placeholder="Cari pengguna...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterRole">
                            <option value="">Semua Role</option>
                            <?php foreach ($roleLabels as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" id="filterDate">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" id="refreshUsers">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Role</th>
                                <th>Terdaftar</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
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

    <!-- Template untuk Modal Detail -->
    <div class="modal fade" id="userDetailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-circle me-2"></i>
                        Detail Pengguna
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be dynamically loaded -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk memuat data pengguna
        function loadUsers() {
            const tableBody = document.getElementById('userTableBody');
            const spinner = document.getElementById('loadingSpinner');
            
            spinner.style.display = 'block';
            
            fetch('users.php?action=get_users')
                .then(response => response.json())
                .then(users => {
                    spinner.style.display = 'none';
                    tableBody.innerHTML = '';
                    
                    users.forEach(user => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="ps-3">
                                <i class="bi bi-person me-1"></i>
                                ${user.nama}
                            </td>
                            <td>
                                <i class="bi bi-envelope me-1"></i>
                                ${user.email}
                            </td>
                            <td>
                                <i class="bi bi-telephone me-1"></i>
                                ${user.telepon}
                            </td>
                            <td>
                                <span class="badge bg-${getRoleClass(user.role)}">
                                    ${getRoleLabel(user.role)}
                                </span>
                            </td>
                            <td>
                                <i class="bi bi-calendar3 me-1"></i>
                                ${user.created_at}
                            </td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-info btn-sm" 
                                        onclick="showUserDetail('${user.id}', ${JSON.stringify(user)})">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </button>
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

        // Fungsi untuk menampilkan detail pengguna
        function showUserDetail(userId, userData) {
            const modal = document.getElementById('userDetailModal');
            const modalContent = document.getElementById('modalContent');

            modalContent.innerHTML = `
                <div class="user-detail">
                    <h6 class="mb-3">
                        <i class="bi bi-person me-2"></i>Informasi Pengguna
                    </h6>
                    <p class="mb-1"><strong>Nama:</strong> ${userData.nama}</p>
                    <p class="mb-1"><strong>Email:</strong> ${userData.email}</p>
                    <p class="mb-1"><strong>Telepon:</strong> ${userData.telepon}</p>
                    <p class="mb-0"><strong>Alamat:</strong> ${userData.alamat}</p>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="user_id" value="${userId}">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-shield me-2"></i>Update Role
                        </label>
                        <select class="form-select" name="role">
                            ${Object.entries(roleLabels).map(([value, label]) => `
                                <option value="${value}" ${userData.role === value ? 'selected' : ''}>
                                    ${label.label}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-2"></i>Update Role
                    </button>
                </form>
            `;

            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
        }

        // Fungsi helper untuk mendapatkan label role
        function getRoleLabel(role) {
            const labels = {
                'admin': 'Admin',
                'user': 'User'
            };
            return labels[role] || role;
        }

        // Fungsi helper untuk mendapatkan kelas role
        function getRoleClass(role) {
            const classes = {
                'admin': 'danger',
                'user': 'primary'
            };
            return classes[role] || 'secondary';
        }

        // Event listener untuk refresh manual
        document.getElementById('refreshUsers').addEventListener('click', loadUsers);

        // Event listener untuk pencarian
        document.getElementById('searchUser').addEventListener('keyup', function(e) {
            const searchText = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#userTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Event listener untuk filter role
        document.getElementById('filterRole').addEventListener('change', function(e) {
            const role = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#userTableBody tr');
            
            rows.forEach(row => {
                if (!role) {
                    row.style.display = '';
                    return;
                }
                
                const roleBadge = row.querySelector('.badge');
                const rowRole = roleBadge.textContent.toLowerCase();
                row.style.display = rowRole.includes(role) ? '' : 'none';
            });
        });

        // Event listener untuk filter tanggal
        document.getElementById('filterDate').addEventListener('change', function(e) {
            const selectedDate = new Date(e.target.value);
            const rows = document.querySelectorAll('#userTableBody tr');
            
            rows.forEach(row => {
                const dateCell = row.querySelector('td:nth-child(5)');
                const userDate = new Date(dateCell.textContent.trim());
                
                if (selectedDate.toDateString() === userDate.toDateString()) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Load users pertama kali
        loadUsers();

        // Set interval untuk memperbarui data setiap 30 detik
        setInterval(loadUsers, 30000);

        // Variabel global untuk menyimpan role labels
        const roleLabels = <?php echo json_encode($roleLabels); ?>;
    </script>
</body>
</html> 