<?php
session_start();

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_logged_in'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

require_once 'config/database.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];

    if (empty($nama) || empty($email) || empty($password) || empty($confirm_password) || empty($no_hp) || empty($alamat)) {
        $error = "Semua field harus diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $db = new Database();
        $collection = $db->getDatabase()->users;
        
        // Cek apakah email sudah terdaftar
        $existingUser = $collection->findOne(['email' => $email]);
        
        if ($existingUser) {
            $error = "Email sudah terdaftar!";
        } else {
            try {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Data user baru
                $userData = [
                    'nama' => $nama,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'no_hp' => $no_hp,
                    'alamat' => $alamat,
                    'role' => 'user',
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];

                $result = $collection->insertOne($userData);

                if ($result->getInsertedCount() > 0) {
                    $success = "Pendaftaran berhasil! Silakan login.";
                    // Redirect ke login setelah 2 detik
                    header("refresh:2;url=login.php");
                } else {
                    $error = "Gagal mendaftar!";
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
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
    <title>Daftar - Toko Sparepart Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #2a5298;
            --secondary-color: #1e3c72;
            --accent-color: #ff4d4d;
            --text-color: #333;
            --light-color: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
            opacity: 0.1;
            z-index: -1;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-top: 3rem;
            margin-bottom: 3rem;
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .register-container:hover {
            transform: translateY(-5px);
        }

        .register-header {
            background: var(--primary-color);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .register-header i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .register-body {
            padding: 2.5rem;
            position: relative;
        }

        .form-control {
            border-radius: 12px;
            padding: 1rem 1.2rem;
            border: 2px solid #e1e1e1;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(42, 82, 152, 0.15);
            border-color: var(--primary-color);
            background: white;
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            border: 2px solid #e1e1e1;
            border-right: none;
            background: var(--light-color);
        }

        .btn-register {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: 0 4px 15px rgba(42, 82, 152, 0.3);
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 82, 152, 0.4);
        }

        .register-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.1);
            color: var(--text-color);
        }

        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .register-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .register-footer a:hover::after {
            width: 100%;
        }

        .alert {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            border: none;
            margin-bottom: 2rem;
        }

        .alert-danger {
            background: rgba(255, 82, 82, 0.1);
            border-left: 4px solid var(--accent-color);
            color: var(--accent-color);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            color: #28a745;
        }

        .password-toggle {
            cursor: pointer;
            padding: 0.5rem;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .register-container {
                margin-top: 1rem;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles-js"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="register-container animate__animated animate__fadeIn">
                    <div class="register-header">
                        <i class="bi bi-gear-wide-connected"></i>
                        <h3 class="animate__animated animate__fadeInDown">Toko Sparepart Motor</h3>
                        <p class="mb-0 animate__animated animate__fadeInUp">Daftar untuk mulai berbelanja</p>
                    </div>
                    
                    <div class="register-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show animate__animated animate__bounceIn" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="animate__animated animate__fadeIn animate__delay-1s">
                            <div class="mb-4">
                                <label for="nama" class="form-label">
                                    <i class="bi bi-person me-2"></i>Nama Lengkap
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="nama" name="nama" required 
                                        placeholder="Masukkan nama lengkap Anda">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope me-2"></i>Email
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                        placeholder="Masukkan email Anda">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="password" class="form-label">
                                            <i class="bi bi-lock me-2"></i>Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required 
                                                placeholder="Minimal 6 karakter">
                                            <span class="input-group-text password-toggle" onclick="togglePassword('password')">
                                                <i class="bi bi-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label">
                                            <i class="bi bi-lock-fill me-2"></i>Konfirmasi Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                                placeholder="Ulangi password">
                                            <span class="input-group-text password-toggle" onclick="togglePassword('confirm_password')">
                                                <i class="bi bi-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="no_hp" class="form-label">
                                    <i class="bi bi-phone me-2"></i>Nomor HP
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="tel" class="form-control" id="no_hp" name="no_hp" required 
                                        placeholder="Masukkan nomor HP aktif">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="alamat" class="form-label">
                                    <i class="bi bi-geo-alt me-2"></i>Alamat Lengkap
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" required 
                                        placeholder="Masukkan alamat lengkap Anda"></textarea>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-register">
                                    <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                                </button>
                            </div>
                        </form>

                        <div class="register-footer animate__animated animate__fadeIn animate__delay-1s">
                            <p>Sudah punya akun? <a href="login.php" class="ms-1">Login Sekarang</a></p>
                            <a href="index.php" class="d-inline-block mt-2">
                                <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Inisialisasi Particles.js
        particlesJS('particles-js', {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.5, random: false },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: '#ffffff', opacity: 0.4, width: 1 },
                move: { enable: true, speed: 6, direction: 'none', random: false, straight: false, out_mode: 'out', bounce: false }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: { enable: true, mode: 'repulse' },
                    onclick: { enable: true, mode: 'push' },
                    resize: true
                }
            },
            retina_detect: true
        });

        // Toggle Password Visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        // Animasi smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 