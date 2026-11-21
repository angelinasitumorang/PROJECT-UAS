<?php
require_once 'config.php';
wajibLogin();
$u = $_SESSION['user'];

// Daftar halaman + role yang berhak (OTORISASI)
$menu = [
    ['url' => BASE_URL . '/APLIKASI_1/index.php', 
      'judul' => 'Data Obat',    
      'isi' => 'Daftar obat dan ringkasan stok apotek.',        
      'role' => ['admin', 'apoteker', 'dokter'], 'khusus' => 'Semua petugas'],
 
      ['url' => BASE_URL . '/APLIKASI_2/index.php', 
        'judul' => 'Resep Dokter', 
        'isi' => 'Dokter mengisi form resep, admin dan apoteker melihat datanya.',             
        'role' => ['admin', 'apoteker', 'dokter'], 'khusus' => 'Form hanya untuk dokter'],
    
      ['url' => 'halaman_apoteker.php',             
          'judul' => 'Stok Obat',    
          'isi' => 'Pantau persediaan dan tambah obat baru.',             
          'role' => ['admin', 'apoteker'],           
          'khusus' => 'Khusus admin dan apoteker'],
      
      ['url' => 'halaman_admin.php',                
        'judul' => 'Kelola User',  
        'isi' => 'Lihat daftar akun petugas rumah sakit.',        
        'role' => ['admin'],                       
        'khusus' => 'Khusus admin'],
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Farmasi Rumah Sakit - Dashboard Petugas</title>
<link rel="stylesheet" href="style.css">
<style>
 .welcome{text-align:center;margin-bottom:30px}
 .welcome h1{margin-bottom:10px}
 .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}
 .kartu{background:#fff;border-radius:12px;padding:26px 20px;text-align:center;box-shadow:0 1px 6px rgba(0,0,0,.1);border-top:4px solid #0d6efd}
 .kartu h3{margin:0 0 8px;color:#0d6efd}
 .kartu p{color:#6c757d;font-size:14px;margin:0 0 8px}
 .kartu .khusus{font-size:12px;color:#495057;margin-bottom:16px}
 .kartu.terkunci{border-top-color:#adb5bd;background:#f1f3f5;box-shadow:none}
 .kartu.terkunci h3{color:#868e96}
 .kunci{display:inline-block;padding:10px 22px;background:#adb5bd;color:#fff;border-radius:6px;font-size:14px}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="wrap">
 <div class="welcome">
  <h1>Selamat Datang, <?= htmlspecialchars($u['nama']) ?></h1>
  <p class="sub">Anda masuk sebagai <span class="badge info"><?= htmlspecialchars($u['role']) ?></span> &mdash; halaman yang terkunci tidak bisa Anda buka</p>
 </div>

 <div class="cards">
 <?php foreach ($menu as $m): $boleh = in_array($u['role'], $m['role']); ?>
  <div class="kartu <?= $boleh ? '' : 'terkunci' ?>">
   <h3><?= $m['judul'] ?></h3>
   <p><?= $m['isi'] ?></p>
   <div class="khusus"><?= $m['khusus'] ?></div>
   <?php if ($boleh): ?>
     <a class="btn" href="<?= $m['url'] ?>">Buka</a>
   <?php else: ?>
     <span class="kunci">Tidak ada akses</span>
   <?php endif; ?>
  </div>
 <?php endforeach; ?>
 </div>
</div>
</body>
</html>
