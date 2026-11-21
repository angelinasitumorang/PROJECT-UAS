<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Obat.php';
require_once __DIR__ . '/src/ObatBebas.php';
require_once __DIR__ . '/src/ObatKeras.php';
require_once __DIR__ . '/src/ObatLain.php';
require_once __DIR__ . '/src/Apotek.php';
require_once __DIR__ . '/config.php';

// NAMESPACE: memanggil class dari namespace berbeda
use Farmasi\Model\ObatBebas;
use Farmasi\Model\ObatKeras;
use Farmasi\Model\ObatLain;
use Farmasi\Service\Apotek;

// DATABASE (PDO): ambil seluruh data obat dari tabel obat di db_farmasi
wajibLogin(); // hanya petugas yang sudah login (admin, apoteker, dokter)
$dataObat = $pdo->query("SELECT * FROM obat ORDER BY id")->fetchAll();

// CLASS & OBJECT: ubah setiap baris database menjadi object
$apotek = new Apotek();
foreach ($dataObat as $r) {
    $kode  = $r['kode'];
    $nama  = $r['nama'];
    $stok  = (int)$r['stok'];
    $harga = (float)$r['harga'];

    if ($r['tipe'] === 'bebas') {
        $obat = new ObatBebas($kode, $nama, $stok, $harga);
    } elseif ($r['tipe'] === 'keras') {
        $obat = new ObatKeras($kode, $nama, $stok, $harga, $r['kategori']);
    } else {
        $obat = new ObatLain($kode, $nama, $stok, $harga, $r['kategori']);
    }
    $apotek->tambahObat($obat);
}

$daftar = $apotek->getDaftarObat();

// Siapkan data untuk kartu ringkasan & tabel yang muncul saat kartu ditekan
$rows = [];
$totalStok = 0;
$jumlah = ['bebas' => 0, 'keras' => 0, 'lain' => 0];
foreach ($daftar as $o) {
    if ($o instanceof ObatKeras)      { $kel = 'keras'; }
    elseif ($o instanceof ObatBebas)  { $kel = 'bebas'; }
    else                              { $kel = 'lain'; }
    $jumlah[$kel]++;
    $totalStok += $o->getStok();
    $rows[] = [
        'kode'     => $o->getKode(),
        'nama'     => $o->getNama(),
        'jenis'    => $o->getJenis(),
        'stok'     => $o->getStok(),
        'harga'    => $o->getHarga(),
        'resep'    => $o->perluResep(),
        'status'   => $o->getStatusStok(),
        'kelompok' => $kel,
    ];
}
$dataJson = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

// Pengingat stok: 6 obat dengan stok paling sedikit
$perluPerhatian = array_values(array_filter($daftar, fn($o) => $o->getStatusStok() !== 'Aman'));
usort($perluPerhatian, fn($a, $b) => $a->getStok() <=> $b->getStok());
$totalPerhatian = count($perluPerhatian);
$tampilPerhatian = array_slice($perluPerhatian, 0, 6);

$kelasStatus = ['Aman' => 'ok', 'Menipis' => 'warn', 'Hampir Habis' => 'bad'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Farmasi Rumah Sakit - Data Obat</title>
<style>
 [hidden]{display:none !important}
 body{font-family:Arial,sans-serif;background:#f4f7fb;color:#212529}
 .wrap{max-width:1150px;margin:0 auto;padding:35px 20px 50px}
 h1{text-align:center;color:#0d6efd;margin:0 0 6px;font-size:34px}
 .sub{text-align:center;color:#6c757d;margin:0 0 30px}
 h2{text-align:center;margin:40px 0 8px;font-size:22px}
 .petunjuk{text-align:center;color:#6c757d;font-size:14px;margin:0 0 18px}

 .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px}
 .stat{font:inherit;cursor:pointer;border:0;border-top:4px solid #0d6efd;background:#fff;border-radius:10px;padding:22px 10px;text-align:center;box-shadow:0 1px 5px rgba(0,0,0,.08);transition:transform .15s,box-shadow .15s}
 .stat:hover{transform:translateY(-3px);box-shadow:0 5px 14px rgba(0,0,0,.15)}
 .stat.aktif{outline:3px solid #0d6efd;outline-offset:-3px;background:#f1f6ff}
 .stat .angka{display:block;font-size:34px;font-weight:bold;color:#0d6efd}
 .stat .label{display:block;color:#212529;margin-top:4px;font-size:15px;font-weight:bold}
 .stat .ket{display:block;color:#6c757d;font-size:12px;margin-top:3px}
 .stat.ungu{border-top-color:#6f42c1}.stat.ungu .angka{color:#6f42c1}
 .stat.hijau{border-top-color:#198754}.stat.hijau .angka{color:#198754}
 .stat.merah{border-top-color:#dc3545}.stat.merah .angka{color:#dc3545}
 .stat.abu{border-top-color:#fd7e14}.stat.abu .angka{color:#fd7e14}

 .panel{margin-top:24px;background:#fff;border-radius:12px;box-shadow:0 1px 8px rgba(0,0,0,.12);padding:22px 22px 18px;scroll-margin-top:80px}
 .panel-head{display:flex;justify-content:space-between;align-items:center;gap:10px}
 .panel-head h3{margin:0;color:#0d6efd;font-size:20px}
 .tutup{font:inherit;cursor:pointer;border:1px solid #ced4da;background:#fff;border-radius:6px;padding:6px 14px}
 .tutup:hover{background:#f1f3f5}
 .panel-ket{color:#6c757d;font-size:14px;margin:6px 0 14px}
 .table-wrap{overflow:auto;max-height:430px;border:1px solid #e9ecef;border-radius:8px}
 table{border-collapse:collapse;width:100%}
 th,td{padding:12px 12px;text-align:center;border-bottom:1px solid #e9ecef}
 th{background:#0d6efd;color:#fff;font-weight:600;position:sticky;top:0}
 tr:last-child td{border-bottom:0}
 tbody tr:hover{background:#f1f6ff}
 tr.total td{background:#e7f0ff;font-weight:bold}
 .badge{display:inline-block;padding:3px 12px;border-radius:12px;color:#fff;font-size:12px;font-weight:bold}
 .ok{background:#198754}.warn{background:#fd7e14}.bad{background:#dc3545}

 .reminders{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
 .reminder{background:#fff;border-radius:10px;padding:20px;text-align:center;box-shadow:0 1px 5px rgba(0,0,0,.08);border-left:5px solid #fd7e14}
 .reminder.bad{border-left-color:#dc3545}
 .reminder .nama{font-weight:bold;font-size:17px;margin-bottom:6px}
 .reminder .teks{color:#495057;margin-bottom:12px;font-size:14px}
 .aman{background:#d1e7dd;color:#0f5132;padding:16px;border-radius:10px;text-align:center}
 .catatan{text-align:center;color:#6c757d;font-size:13px;margin-top:14px}
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="wrap">
 <h1>Farmasi Rumah Sakit</h1>
 <p class="sub">Data obat dan persediaan stok apotek</p>

 <h2>Ringkasan Stok</h2>
 <p class="petunjuk">Tekan salah satu kartu untuk melihat datanya</p>
 <div class="stats">
  <button type="button" class="stat" data-kunci="semua">
   <span class="angka"><?= count($daftar) ?></span>
   <span class="label">Jenis Obat</span>
   <span class="ket">Semua jenis obat</span>
  </button>
  <button type="button" class="stat ungu" data-kunci="total">
   <span class="angka"><?= number_format($totalStok, 0, ',', '.') ?></span>
   <span class="label">Total Stok</span>
   <span class="ket">Jumlah seluruh unit obat</span>
  </button>
  <button type="button" class="stat hijau" data-kunci="bebas">
   <span class="angka"><?= $jumlah['bebas'] ?></span>
   <span class="label">Obat Bebas</span>
   <span class="ket">Tanpa resep dokter</span>
  </button>
  <button type="button" class="stat merah" data-kunci="keras">
   <span class="angka"><?= $jumlah['keras'] ?></span>
   <span class="label">Obat Keras</span>
   <span class="ket">Wajib resep dokter</span>
  </button>
  <button type="button" class="stat abu" data-kunci="lain">
   <span class="angka"><?= $jumlah['lain'] ?></span>
   <span class="label">Lainnya</span>
   <span class="ket">Vitamin, infus, antiseptik, dll</span>
  </button>
 </div>

 <div class="panel" id="panel" hidden>
  <div class="panel-head">
   <h3 id="panel-judul"></h3>
   <button type="button" class="tutup" id="tutup">Tutup</button>
  </div>
  <p class="panel-ket" id="panel-ket"></p>
  <div class="table-wrap">
   <table>
    <thead id="kepala"></thead>
    <tbody id="isi"></tbody>
   </table>
  </div>
 </div>

 <h2>Pengingat Stok</h2>
 <?php if ($totalPerhatian === 0): ?>
  <div class="aman">Semua stok obat masih aman.</div>
 <?php else: ?>
  <p class="petunjuk">Ada <?= $totalPerhatian ?> obat yang stoknya menipis. Berikut 6 obat dengan stok paling sedikit.</p>
  <div class="reminders">
  <?php foreach ($tampilPerhatian as $o): $status = $o->getStatusStok(); ?>
   <div class="reminder <?= $status === 'Hampir Habis' ? 'bad' : '' ?>">
    <div class="nama"><?= htmlspecialchars($o->getNama()) ?></div>
    <div class="teks">Sisa stok tinggal <b><?= $o->getStok() ?></b>. Segera tambah persediaan.</div>
    <span class="badge <?= $kelasStatus[$status] ?>"><?= $status ?></span>
   </div>
  <?php endforeach; ?>
  </div>
 <?php endif; ?>
</div>

<script>
const DATA = <?= $dataJson ?>;

const KELOMPOK = {
  semua: {judul: 'Semua Jenis Obat',  ket: 'Daftar lengkap seluruh jenis obat yang tersedia di apotek.'},
  bebas: {judul: 'Obat Bebas',        ket: 'Obat yang bisa dibeli tanpa resep dokter.'},
  keras: {judul: 'Obat Keras',        ket: 'Obat yang hanya boleh diberikan dengan resep dokter.'},
  lain:  {judul: 'Obat Lainnya',      ket: 'Vitamin, cairan infus, antiseptik, obat luar, dan perlengkapan medis.'}
};
const KELAS_STATUS = {'Aman': 'ok', 'Menipis': 'warn', 'Hampir Habis': 'bad'};
const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');
const angka  = n => Number(n).toLocaleString('id-ID');

const panel = document.getElementById('panel');
const kepala = document.getElementById('kepala');
const isi = document.getElementById('isi');

function sel(teks, kelas) {
  const td = document.createElement('td');
  if (teks instanceof Node) { td.appendChild(teks); } else { td.textContent = teks; }
  if (kelas) td.className = kelas;
  return td;
}
function badge(teks, kelas) {
  const s = document.createElement('span');
  s.className = 'badge ' + kelas;
  s.textContent = teks;
  return s;
}
function isiKepala(daftar) {
  kepala.innerHTML = '';
  const tr = document.createElement('tr');
  daftar.forEach(t => { const th = document.createElement('th'); th.textContent = t; tr.appendChild(th); });
  kepala.appendChild(tr);
}

function tampilDaftar(kunci) {
  const data = kunci === 'semua' ? DATA : DATA.filter(d => d.kelompok === kunci);
  isiKepala(['No', 'Kode', 'Nama Obat', 'Jenis', 'Stok', 'Harga', 'Perlu Resep', 'Status Stok']);
  isi.innerHTML = '';
  data.forEach((d, i) => {
    const tr = document.createElement('tr');
    tr.appendChild(sel(i + 1));
    tr.appendChild(sel(d.kode));
    tr.appendChild(sel(d.nama));
    tr.appendChild(sel(d.jenis));
    tr.appendChild(sel(angka(d.stok)));
    tr.appendChild(sel(rupiah(d.harga)));
    tr.appendChild(sel(badge(d.resep ? 'Ya' : 'Tidak', d.resep ? 'bad' : 'ok')));
    tr.appendChild(sel(badge(d.status, KELAS_STATUS[d.status])));
    isi.appendChild(tr);
  });
  return {judul: KELOMPOK[kunci].judul + ' (' + data.length + ')', ket: KELOMPOK[kunci].ket};
}

function tampilTotal() {
  isiKepala(['Kelompok Obat', 'Jumlah Jenis', 'Total Stok']);
  isi.innerHTML = '';
  let jenis = 0, stok = 0;
  ['bebas', 'keras', 'lain'].forEach(k => {
    const data = DATA.filter(d => d.kelompok === k);
    const s = data.reduce((a, d) => a + d.stok, 0);
    jenis += data.length; stok += s;
    const tr = document.createElement('tr');
    tr.appendChild(sel(KELOMPOK[k].judul));
    tr.appendChild(sel(data.length));
    tr.appendChild(sel(angka(s)));
    isi.appendChild(tr);
  });
  const tr = document.createElement('tr');
  tr.className = 'total';
  tr.appendChild(sel('Total'));
  tr.appendChild(sel(jenis));
  tr.appendChild(sel(angka(stok)));
  isi.appendChild(tr);
  return {
    judul: 'Total Stok: ' + angka(stok) + ' unit',
    ket: 'Total stok adalah jumlah seluruh unit obat (tablet, botol, kantong, dan lainnya) dari semua jenis obat yang ada di apotek.'
  };
}

document.querySelectorAll('.stat').forEach(btn => {
  btn.addEventListener('click', () => {
    const kunci = btn.dataset.kunci;
    document.querySelectorAll('.stat').forEach(b => b.classList.remove('aktif'));
    btn.classList.add('aktif');
    const info = kunci === 'total' ? tampilTotal() : tampilDaftar(kunci);
    document.getElementById('panel-judul').textContent = info.judul;
    document.getElementById('panel-ket').textContent = info.ket;
    panel.hidden = false;
    panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
  });
});

document.getElementById('tutup').addEventListener('click', () => {
  panel.hidden = true;
  document.querySelectorAll('.stat').forEach(b => b.classList.remove('aktif'));
});
</script>
</body>
</html>
