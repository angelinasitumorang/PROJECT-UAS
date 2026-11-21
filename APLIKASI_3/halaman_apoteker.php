<?php
require_once 'config.php';
// OTORISASI: hanya admin dan apoteker (hak akses dikelola admin)
wajibRole(['admin', 'apoteker']);

$daftarKategori = ['Vitamin & Suplemen', 'Cairan Infus', 'Antiseptik', 'Perlengkapan Medis', 'Obat Luar'];
$awalan = ['bebas' => 'OB', 'keras' => 'OK', 'lain' => 'OL'];

$pesan = '';
$tipePesan = '';
if (isset($_SESSION['flash_obat'])) {          // pesan sukses setelah redirect
    $pesan = $_SESSION['flash_obat'];
    $tipePesan = 'ok';
    unset($_SESSION['flash_obat']);
}

$input = ['nama' => '', 'tipe' => 'bebas', 'kategori' => '', 'stok' => '', 'harga' => ''];

// ---------- FORM TAMBAH OBAT BARU ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['nama']     = trim($_POST['nama'] ?? '');
    $input['tipe']     = $_POST['tipe'] ?? 'bebas';
    $input['kategori'] = $_POST['kategori'] ?? '';
    $input['stok']     = trim($_POST['stok'] ?? '');
    $input['harga']    = trim($_POST['harga'] ?? '');

    $error = '';
    if ($input['nama'] === '') {
        $error = 'Nama obat wajib diisi.';
    } elseif (mb_strlen($input['nama']) > 150) {
        $error = 'Nama obat maksimal 150 karakter.';
    } elseif (!isset($awalan[$input['tipe']])) {
        $error = 'Jenis obat tidak valid.';
    } elseif ($input['tipe'] === 'lain' && !in_array($input['kategori'], $daftarKategori, true)) {
        $error = 'Pilih kategori untuk obat jenis Lainnya.';
    } elseif (!ctype_digit($input['stok']) || (int)$input['stok'] > 1000000) {
        $error = 'Stok harus berupa angka bulat (0 sampai 1.000.000).';
    } elseif (!ctype_digit($input['harga']) || (int)$input['harga'] < 1 || (int)$input['harga'] > 100000000) {
        $error = 'Harga harus berupa angka lebih dari 0.';
    } else {
        // Cegah nama obat dobel
        $cek = $pdo->prepare("SELECT COUNT(*) FROM obat WHERE LOWER(nama) = LOWER(:nama)");
        $cek->execute([':nama' => $input['nama']]);
        if ((int)$cek->fetchColumn() > 0) {
            $error = 'Obat dengan nama tersebut sudah ada.';
        }
    }

    if ($error === '') {
        // Kode dibuat otomatis: OB### (bebas), OK### (keras), OL### (lainnya)
        $prefix = $awalan[$input['tipe']];
        $q = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(kode, 3) AS UNSIGNED)) FROM obat WHERE kode LIKE :p");
        $q->execute([':p' => $prefix . '%']);
        $kode = $prefix . str_pad((string)((int)$q->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);

        if ($input['tipe'] === 'bebas')      { $kategori = ''; }
        elseif ($input['tipe'] === 'keras')  { $kategori = 'G'; }
        else                                 { $kategori = $input['kategori']; }

        try {
            $ins = $pdo->prepare("INSERT INTO obat (kode, nama, tipe, kategori, stok, harga)
                                  VALUES (:kode, :nama, :tipe, :kategori, :stok, :harga)");
            $ins->execute([
                ':kode' => $kode, ':nama' => $input['nama'], ':tipe' => $input['tipe'],
                ':kategori' => $kategori, ':stok' => (int)$input['stok'], ':harga' => (int)$input['harga'],
            ]);
            $_SESSION['flash_obat'] = 'Obat ' . $input['nama'] . ' berhasil ditambahkan dengan kode ' . $kode . '.';
            header('Location: halaman_apoteker.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan obat. Silakan coba lagi.';
        }
    }
    $pesan = $error;
    $tipePesan = 'err';
}

// ---------- DAFTAR STOK ----------
$obat = $pdo->query("SELECT kode, nama, tipe, kategori, stok FROM obat ORDER BY stok ASC, nama ASC")->fetchAll();

function labelJenis(array $o): string {
    if ($o['tipe'] === 'bebas') return 'Obat Bebas';
    if ($o['tipe'] === 'keras') return 'Obat Keras (Gol. ' . $o['kategori'] . ')';
    return $o['kategori'];
}
function statusStok(int $stok): array {
    if ($stok <= 50)  return ['Hampir Habis', 'bad'];
    if ($stok <= 100) return ['Menipis', 'warn'];
    return ['Aman', ''];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Farmasi Rumah Sakit - Stok Obat</title>
<link rel="stylesheet" href="style.css">
<style>
 [hidden]{display:none !important}
 h2{text-align:center;margin:45px 0 18px;font-size:22px}
 .box{background:#fff;padding:26px 30px;border-radius:12px;max-width:560px;margin:0 auto;box-shadow:0 1px 6px rgba(0,0,0,.1)}
 .box h3{margin:0 0 4px;text-align:center;color:#0d6efd;font-size:20px}
 .box .ket{text-align:center;color:#6c757d;font-size:13px;margin:0 0 6px}
 label{display:block;margin-top:14px;font-weight:bold;font-size:14px}
 input[type=text],select{width:100%;padding:10px;box-sizing:border-box;margin-top:5px;border:1px solid #ced4da;border-radius:6px;font-family:inherit;font-size:14px;background:#fff}
 .dua{display:grid;grid-template-columns:1fr 1fr;gap:14px}
 .hint{color:#6c757d;font-size:12px;margin-top:4px}
 .btn-wrap{text-align:center;margin-top:22px}
 button{padding:11px 36px;background:#0d6efd;color:#fff;border:0;border-radius:6px;cursor:pointer;font-size:15px}
 button:hover{background:#0a58ca}
 .ok{background:#d1e7dd;color:#0f5132;padding:11px;border-radius:6px;text-align:center;margin:0 auto 14px;max-width:560px}
 .err{background:#f8d7da;color:#842029;padding:11px;border-radius:6px;text-align:center;margin:0 auto 14px;max-width:560px}
 .table-wrap{max-height:520px;overflow:auto}
 th{position:sticky;top:0}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="wrap">
 <h1>Stok Obat</h1>
 <p class="sub">Kelola persediaan obat apotek (khusus admin dan apoteker)</p>

 <?php if ($pesan): ?><div class="<?= $tipePesan ?>"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>

 <div class="box">
  <h3>Tambah Obat Baru</h3>
  <p class="ket">Kode obat dibuat otomatis oleh sistem</p>
  <form method="POST">
   <label>Nama Obat</label>
   <input type="text" name="nama" value="<?= htmlspecialchars($input['nama']) ?>" placeholder="Contoh: Ibuprofen 400mg" required>

   <label>Jenis Obat</label>
   <select name="tipe" id="tipe">
    <option value="bebas" <?= $input['tipe'] === 'bebas' ? 'selected' : '' ?>>Obat Bebas (tanpa resep)</option>
    <option value="keras" <?= $input['tipe'] === 'keras' ? 'selected' : '' ?>>Obat Keras (wajib resep dokter)</option>
    <option value="lain"  <?= $input['tipe'] === 'lain'  ? 'selected' : '' ?>>Lainnya (vitamin, infus, antiseptik, dll)</option>
   </select>

   <div id="grup-kategori" hidden>
    <label>Kategori</label>
    <select name="kategori">
     <option value="">-- Pilih kategori --</option>
     <?php foreach ($daftarKategori as $k): ?>
      <option value="<?= htmlspecialchars($k) ?>" <?= $input['kategori'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
     <?php endforeach; ?>
    </select>
   </div>
   <div id="info-keras" class="hint" hidden>Obat keras otomatis dicatat sebagai Golongan G.</div>

   <div class="dua">
    <div>
     <label>Stok Awal</label>
     <input type="text" name="stok" value="<?= htmlspecialchars($input['stok']) ?>" placeholder="Contoh: 100" inputmode="numeric" required>
    </div>
    <div>
     <label>Harga (Rp)</label>
     <input type="text" name="harga" value="<?= htmlspecialchars($input['harga']) ?>" placeholder="Contoh: 15000" inputmode="numeric" required>
    </div>
   </div>

   <div class="btn-wrap"><button type="submit">Simpan Obat</button></div>
  </form>
 </div>

 <h2>Daftar Stok Obat</h2>
 <p class="sub" style="margin-top:-10px">Diurutkan dari stok paling sedikit &mdash; total <?= count($obat) ?> jenis obat</p>
 <div class="table-wrap">
 <table>
  <thead><tr><th>No</th><th>Kode</th><th>Nama Obat</th><th>Jenis</th><th>Stok</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($obat as $i => $o): [$status, $kelas] = statusStok((int)$o['stok']); ?>
   <tr>
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($o['kode']) ?></td>
    <td><?= htmlspecialchars($o['nama']) ?></td>
    <td><?= htmlspecialchars(labelJenis($o)) ?></td>
    <td><?= (int)$o['stok'] ?></td>
    <td><span class="badge <?= $kelas ?>"><?= $status ?></span></td>
   </tr>
  <?php endforeach; ?>
  </tbody>
 </table>
 </div>
 <div class="back"><a class="btn" href="dashboard.php">Kembali ke Dashboard</a></div>
</div>

<script>
 const tipe = document.getElementById('tipe');
 const grupKategori = document.getElementById('grup-kategori');
 const infoKeras = document.getElementById('info-keras');
 function ubahForm() {
   grupKategori.hidden = tipe.value !== 'lain';
   infoKeras.hidden = tipe.value !== 'keras';
 }
 tipe.addEventListener('change', ubahForm);
 ubahForm();
</script>
</body>
</html>
