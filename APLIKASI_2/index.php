<?php
require_once 'config.php';
wajibLogin();

$role       = $_SESSION['user']['role'];
$namaDokter = $_SESSION['user']['nama'];
$adalahDokter = ($role === 'dokter');   // hanya dokter yang boleh mengisi form

$pesan = '';
$tipe  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adalahDokter) {
    $pesan = 'Hanya dokter yang boleh mengirim resep.'; $tipe = 'err';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama_pasien'] ?? '');
    $no_rm  = trim($_POST['no_pasien'] ?? '');
    $ket    = trim($_POST['keterangan'] ?? '');
    $dokter = $namaDokter;   // nama dokter otomatis dari akun yang login

    if ($nama === '' || $no_rm === '') {
        $pesan = 'Nama pasien dan nomor pasien wajib diisi.'; $tipe = 'err';
    } elseif (!isset($_FILES['file_resep']) || $_FILES['file_resep']['error'] !== UPLOAD_ERR_OK) {
        $pesan = 'Silakan pilih file resep yang valid.'; $tipe = 'err';
    } else {
        $file     = $_FILES['file_resep'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $boleh    = ['jpg', 'jpeg', 'png', 'pdf'];
        $maxSize  = 2 * 1024 * 1024; // 2 MB

        if (!in_array($ext, $boleh)) {
            $pesan = 'Format file harus JPG, PNG, atau PDF.'; $tipe = 'err';
        } elseif ($file['size'] > $maxSize) {
            $pesan = 'Ukuran file maksimal 2 MB.'; $tipe = 'err';
        } else {
            $namaBaru = uniqid('resep_') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/' . $namaBaru)) {
                // Simpan ke database dengan PDO
                $stmt = $pdo->prepare("INSERT INTO resep
                    (nama_pasien, no_rm, nama_dokter, keterangan, nama_file, nama_asli, tipe_file, ukuran_file)
                    VALUES (:nama, :rm, :dokter, :ket, :file, :asli, :tipe, :ukuran)");
                $stmt->execute([
                    ':nama'   => $nama,
                    ':rm'     => $no_rm,
                    ':dokter' => $dokter,
                    ':ket'    => $ket,
                    ':file'   => $namaBaru,
                    ':asli'   => $file['name'],
                    ':tipe'   => $file['type'],
                    ':ukuran' => $file['size'],
                ]);
                $pesan = 'Resep berhasil dikirim ke apotek.'; $tipe = 'ok';
            } else {
                $pesan = 'Gagal menyimpan file. Pastikan folder uploads ada.'; $tipe = 'err';
            }
        }
    }
}

// Dokter hanya melihat resep miliknya. Admin dan apoteker melihat semua resep dari dokter.
if ($adalahDokter) {
    $q = $pdo->prepare("SELECT * FROM resep WHERE nama_dokter = :d ORDER BY id DESC");
    $q->execute([':d' => $namaDokter]);
    $data = $q->fetchAll();
} else {
    $data = $pdo->query("SELECT * FROM resep ORDER BY id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Farmasi Rumah Sakit - Resep Dokter</title>
<style>
 body{font-family:Arial,sans-serif;background:#f4f7fb;color:#212529}
 .wrap{max-width:1100px;margin:0 auto;padding:35px 20px 50px}
 h1{text-align:center;color:#0d6efd;margin:0 0 6px;font-size:34px}
 .sub{text-align:center;color:#6c757d;margin:0 0 28px}
 h2{text-align:center;margin:45px 0 18px;font-size:22px}
 .box{background:#fff;padding:28px 30px;border-radius:12px;max-width:560px;margin:0 auto;box-shadow:0 1px 6px rgba(0,0,0,.1)}
 label{display:block;margin-top:14px;font-weight:bold;font-size:14px}
 input[type=text],textarea,input[type=file]{width:100%;padding:10px;box-sizing:border-box;margin-top:5px;border:1px solid #ced4da;border-radius:6px;font-family:inherit;font-size:14px}
 input[readonly]{background:#e9ecef;color:#495057}
 .hint{color:#6c757d;font-size:12px;margin-top:4px}
 .btn-wrap{text-align:center;margin-top:22px}
 button{padding:11px 36px;background:#0d6efd;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:15px}
 button:hover{background:#0a58ca}
 .ok{background:#d1e7dd;color:#0f5132;padding:11px;border-radius:6px;text-align:center;margin-bottom:6px}
 .err{background:#f8d7da;color:#842029;padding:11px;border-radius:6px;text-align:center;margin-bottom:6px}
 .info{background:#e7f0ff;color:#084298;padding:14px;border-radius:10px;text-align:center;max-width:560px;margin:0 auto;font-size:14px;line-height:1.6}
 .table-wrap{overflow-x:auto;background:#fff;border-radius:10px;box-shadow:0 1px 5px rgba(0,0,0,.08)}
 table{border-collapse:collapse;width:100%}
 th,td{padding:13px 12px;text-align:center;border-bottom:1px solid #e9ecef}
 th{background:#0d6efd;color:#fff;font-weight:600}
 tr:last-child td{border-bottom:0}
 tbody tr:hover{background:#f1f6ff}
 a{color:#0d6efd}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="wrap">
 <h1>Resep Dokter</h1>
 <?php if ($adalahDokter): ?>
  <p class="sub">Isi formulir berikut untuk mengirim resep pasien ke apotek</p>

  <div class="box">
   <?php if ($pesan): ?><div class="<?= $tipe ?>"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
   <form method="POST" enctype="multipart/form-data">
    <label>Nama Pasien</label>
    <input type="text" name="nama_pasien" placeholder="Contoh: Budi Santoso" required>

    <label>Nomor Pasien</label>
    <input type="text" name="no_pasien" placeholder="Contoh: 001234" required>

    <label>Nama Dokter</label>
    <input type="text" value="<?= htmlspecialchars($namaDokter) ?>" readonly>

    <label>Catatan Tambahan</label>
    <textarea name="keterangan" rows="3" placeholder="Boleh dikosongkan"></textarea>

    <label>File Resep</label>
    <input type="file" name="file_resep" required>
    <div class="hint">Format JPG, PNG, atau PDF. Ukuran maksimal 2 MB.</div>

    <div class="btn-wrap"><button type="submit">Kirim Resep</button></div>
   </form>
  </div>
 <?php else: ?>
  <p class="sub">Resep pasien yang dikirim oleh dokter</p>
  <div class="info">
   Anda masuk sebagai <b><?= htmlspecialchars($role) ?></b>. Form resep hanya diisi oleh dokter,
   resep yang dikirim dokter akan otomatis muncul pada tabel di bawah.
  </div>
  <?php if ($pesan): ?><div class="err" style="max-width:560px;margin:12px auto 0"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
 <?php endif; ?>

 <h2>Data Resep</h2>
 <div class="table-wrap">
 <table>
  <thead>
   <tr><th>No</th><th>Nama Pasien</th><th>Nomor Pasien</th><th>Dokter</th><th>Catatan</th><th>File Resep</th><th>Ukuran</th><th>Waktu Kirim</th></tr>
  </thead>
  <tbody>
  <?php foreach ($data as $i => $r): ?>
   <tr>
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($r['nama_pasien']) ?></td>
    <td><?= htmlspecialchars($r['no_rm']) ?></td>
    <td><?= htmlspecialchars($r['nama_dokter']) ?></td>
    <td><?= htmlspecialchars($r['keterangan'] ?: '-') ?></td>
    <td><a href="uploads/<?= htmlspecialchars($r['nama_file']) ?>" target="_blank"><?= htmlspecialchars($r['nama_asli']) ?></a></td>
    <td><?= round($r['ukuran_file'] / 1024, 1) ?> KB</td>
    <td><?= $r['created_at'] ?></td>
   </tr>
  <?php endforeach; ?>
  <?php if (!$data): ?><tr><td colspan="8">Belum ada resep.</td></tr><?php endif; ?>
  </tbody>
 </table>
 </div>
</div>
</body>
</html>
