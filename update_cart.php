<?php
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['error_message'] = 'Silakan login terlebih dahulu untuk mengakses keranjang belanja.';
    header('Location: login.php');
    exit;
}

// Pastikan keranjang ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Pastikan ada action dan index
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['index'])) {
    $action = $_POST['action'];
    $index = (int)$_POST['index'];

    // Pastikan index valid
    if (isset($_SESSION['cart'][$index])) {
        switch ($action) {
            case 'increase':
                // Tambah quantity jika masih dalam batas stok
                if ($_SESSION['cart'][$index]['quantity'] < $_SESSION['cart'][$index]['stok']) {
                    $_SESSION['cart'][$index]['quantity']++;
                    $_SESSION['success_message'] = 'Quantity berhasil ditambah.';
                } else {
                    $_SESSION['error_message'] = 'Quantity tidak bisa melebihi stok yang tersedia.';
                }
                break;

            case 'decrease':
                // Kurangi quantity jika masih lebih dari 1
                if ($_SESSION['cart'][$index]['quantity'] > 1) {
                    $_SESSION['cart'][$index]['quantity']--;
                    $_SESSION['success_message'] = 'Quantity berhasil dikurangi.';
                }
                break;

            case 'remove':
                // Hapus item dari keranjang
                unset($_SESSION['cart'][$index]);
                // Reindex array
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                $_SESSION['success_message'] = 'Item berhasil dihapus dari keranjang.';
                break;
        }
    }
}

// Redirect kembali ke halaman keranjang
header('Location: cart.php');
exit;
?> 