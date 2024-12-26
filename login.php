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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi!";
    } else {
        $db = new Database();
        $collection = $db->getDatabase()->users;
        
        $user = $collection->findOne(['email' => $email]);
        
        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = (string)$user->_id;
            $_SESSION['user_nama'] = $user->nama;
            $_SESSION['user_role'] = $user->role ?? 'user';
            
            if ($user->role === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Email atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Sparepart Motor</title>
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

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-top: 3rem;
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-header {
            background: var(--primary-color);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
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

        .login-header i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .login-body {
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

        .btn-login {
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

        .btn-login:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 82, 152, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.1);
            color: var(--text-color);
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .login-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .login-footer a:hover::after {
            width: 100%;
        }

        .features {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            color: white;
            backdrop-filter: blur(5px);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .features:hover {
            transform: translateY(-5px);
        }

        .feature-item {
            text-align: center;
            padding: 1.2rem;
            transition: all 0.3s ease;
            border-radius: 12px;
            cursor: pointer;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .feature-item i {
            font-size: 2.5rem;
            margin-bottom: 0.8rem;
            transition: all 0.3s ease;
        }

        .feature-item:hover i {
            transform: scale(1.2);
            color: var(--accent-color);
        }

        .feature-item .feature-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-item .feature-desc {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .login-container {
                margin-top: 1rem;
                margin-bottom: 1rem;
            }
            
            .features {
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles-js"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-container animate__animated animate__fadeIn">
                    <div class="login-header">
                        <i class="bi bi-gear-wide-connected"></i>
                        <h3 class="animate__animated animate__fadeInDown">Toko Sparepart Motor</h3>
                        <p class="mb-0 animate__animated animate__fadeInUp">Silakan login untuk melanjutkan</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__shakeX" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="animate__animated animate__fadeIn animate__delay-1s">
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

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                        placeholder="Masukkan password Anda">
                                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                                        <i class="bi bi-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Ingat saya</label>
                                <a href="#" class="float-end text-decoration-none">Lupa password?</a>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                        </form>

                        <div class="login-footer animate__animated animate__fadeIn animate__delay-1s">
                            <p>Belum punya akun? <a href="register.php" class="ms-1">Daftar Sekarang</a></p>
                            <a href="index.php" class="d-inline-block mt-2">
                                <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>

                <div class="features animate__animated animate__fadeIn animate__delay-2s">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="feature-item">
                                <i class="bi bi-shield-check"></i>
                                <div class="feature-title">Produk Original</div>
                                <div class="feature-desc">Garansi keaslian produk 100%</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-item">
                                <i class="bi bi-truck"></i>
                                <div class="feature-title">Pengiriman Cepat</div>
                                <div class="feature-desc">Pengiriman ke seluruh Indonesia</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-item">
                                <i class="bi bi-headset"></i>
                                <div class="feature-title">Layanan 24 Jam</div>
                                <div class="feature-desc">Siap membantu setiap saat</div>
                            </div>
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
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