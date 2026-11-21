<?php
/**
 * Konfigurasi pusat: session, koneksi PDO ke database db_farmasi,
 * pembuatan tabel otomatis, dan fungsi login / hak akses.
 * Dipakai oleh Aplikasi 1, 2, dan 3.
 */
session_start();

// Alamat dasar project, contoh: /PROJECT-UAS-NAVBAR
define('BASE_URL', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/'));

function koneksiDatabase(): PDO {
    $host = 'localhost';
    $db   = 'db_farmasi';
    $user = 'root';
    $pass = '';

    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4");
        $pdo->exec("USE `$db`");
        siapkanTabel($pdo);
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

// Membuat 3 tabel: users, obat, resep (jika belum ada) dan mengisi data awal (jika masih kosong)
function siapkanTabel(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        role ENUM('admin','apoteker','dokter') NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS obat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(20) NOT NULL UNIQUE,
        nama VARCHAR(150) NOT NULL,
        tipe ENUM('bebas','keras','lain') NOT NULL,
        kategori VARCHAR(100) NOT NULL DEFAULT '',
        stok INT NOT NULL DEFAULT 0,
        harga INT NOT NULL DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS resep (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_pasien VARCHAR(100) NOT NULL,
        no_rm VARCHAR(20) NOT NULL,
        nama_dokter VARCHAR(100) NOT NULL,
        keterangan TEXT,
        nama_file VARCHAR(255) NOT NULL,
        nama_asli VARCHAR(255) NOT NULL,
        tipe_file VARCHAR(50) NOT NULL,
        ukuran_file INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // Akun awal (password di-hash)
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $akun = [
            ['admin',    'admin123',    'Administrator RS', 'admin'],
            ['apoteker', 'apoteker123', 'Apt. Sari Dewi',   'apoteker'],
            ['dokter',   'dokter123',   'dr. Budi Santoso', 'dokter'],
        ];
        $ins = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?,?,?,?)");
        foreach ($akun as $a) {
            $ins->execute([$a[0], password_hash($a[1], PASSWORD_DEFAULT), $a[2], $a[3]]);
        }
    }

    // Data obat awal dari Aplikasi 1
    if ((int)$pdo->query("SELECT COUNT(*) FROM obat")->fetchColumn() === 0) {
        $seed = require __DIR__ . '/../APLIKASI_1/data_obat.php';
        $ins = $pdo->prepare("INSERT INTO obat (kode, nama, tipe, kategori, stok, harga)
                              VALUES (:kode, :nama, :tipe, :kategori, :stok, :harga)");
        $pdo->beginTransaction();
        foreach ($seed as $b) {
            $ins->execute([':kode' => $b[1], ':nama' => $b[2], ':tipe' => $b[0],
                           ':kategori' => $b[5], ':stok' => $b[3], ':harga' => $b[4]]);
        }
        $pdo->commit();
    }
}

$pdo = koneksiDatabase();

// ---------- OTENTIKASI: wajib login ----------
function wajibLogin(): void {
    if (!isset($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/APLIKASI_3/login.php');
        exit;
    }
}

// ---------- OTORISASI: wajib punya role tertentu ----------
function wajibRole(array $roleBoleh): void {
    wajibLogin();
    if (!in_array($_SESSION['user']['role'], $roleBoleh)) {
        http_response_code(403);
        echo "<div style='font-family:Arial;text-align:center;margin-top:80px'>"
           . "<h2>403 - Akses Ditolak</h2><p>Role <b>" . htmlspecialchars($_SESSION['user']['role'])
           . "</b> tidak memiliki izin untuk halaman ini.</p>"
           . "<a href='" . BASE_URL . "/APLIKASI_3/dashboard.php'>Kembali ke Dashboard</a></div>";
        exit;
    }
}
