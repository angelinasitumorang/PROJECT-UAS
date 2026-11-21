<?php
require_once 'config.php';
wajibRole(['admin']); // hanya admin

$users = $pdo->query("SELECT id, username, nama_lengkap, role FROM users ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Farmasi Rumah Sakit - Kelola User</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="wrap">
 <h1>Kelola User</h1>
 <p class="sub">Daftar akun petugas rumah sakit (khusus admin)</p>
 <div class="table-wrap">
 <table>
  <thead><tr><th>No</th><th>Username</th><th>Nama Lengkap</th><th>Role</th></tr></thead>
  <tbody>
  <?php foreach ($users as $i => $r): ?>
   <tr>
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($r['username']) ?></td>
    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
    <td><span class="badge info"><?= htmlspecialchars($r['role']) ?></span></td>
   </tr>
  <?php endforeach; ?>
  </tbody>
 </table>
 </div>
 <div class="back"><a class="btn" href="dashboard.php">Kembali ke Dashboard</a></div>
</div>
</body>
</html>
