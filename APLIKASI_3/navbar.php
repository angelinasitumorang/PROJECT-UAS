<?php
// Navbar 3 menu + info petugas. Halaman yang memakai navbar ini sudah wajib login.
$navUser = $_SESSION['user'] ?? null;
$navPath = $_SERVER['SCRIPT_NAME'];
$navAktif = fn($no) => strpos($navPath, "/APLIKASI_$no/") !== false ? ' class="active"' : '';
?>

<style>
 body{margin:0 !important}
 .navbar{position:sticky;top:0;z-index:10;background:#0d6efd;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;padding:0 30px;font-family:Arial,sans-serif;box-shadow:0 2px 6px rgba(0,0,0,.2)}
 .navbar .brand{color:#fff;font-weight:bold;font-size:19px;text-decoration:none;padding:16px 0}
 .navbar .menu{display:flex;flex-wrap:wrap;align-items:center}
 .navbar .menu a{color:#e7f0ff;text-decoration:none;padding:17px 18px;font-size:15px}
 .navbar .menu a:hover{background:rgba(255,255,255,.15);color:#fff}
 .navbar .menu a.active{background:#0a58ca;color:#fff;font-weight:bold}
 .navbar .user{color:#fff;font-size:14px;padding:0 6px 0 14px}
 .navbar .badge-role{background:#198754;color:#fff;padding:2px 10px;border-radius:10px;font-size:12px;margin-left:6px}
 .navbar .menu a.logout{background:#dc3545;border-radius:5px;margin-left:8px;padding:8px 14px;color:#fff}
 .navbar .menu a.logout:hover{background:#bb2d3b}
</style>
<nav class="navbar">
  <a class="brand" href="<?= BASE_URL ?>/APLIKASI_3/dashboard.php">Farmasi Rumah Sakit</a>
  <div class="menu">
    <a href="<?= BASE_URL ?>/APLIKASI_1/index.php"<?= $navAktif(1) ?>>Data Obat</a>
    <a href="<?= BASE_URL ?>/APLIKASI_2/index.php"<?= $navAktif(2) ?>>Resep Dokter</a>
    <a href="<?= BASE_URL ?>/APLIKASI_3/dashboard.php"<?= $navAktif(3) ?>>Dashboard Petugas</a>
    <?php if ($navUser): ?>
      <span class="user"><?= htmlspecialchars($navUser['nama']) ?><span class="badge-role"><?= htmlspecialchars($navUser['role']) ?></span></span>
      <a href="<?= BASE_URL ?>/APLIKASI_3/logout.php" class="logout">Logout</a>
    <?php endif; ?>
  </div>
</nav>
