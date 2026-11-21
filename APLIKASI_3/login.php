<?php
require_once 'config.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$roleDipilih = $_POST['role'] ?? 'admin';
if (!in_array($roleDipilih, ['admin', 'apoteker', 'dokter'])) {
    $roleDipilih = 'admin';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // OTENTIKASI: cek username & password
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $error = 'Username atau password salah.';
    } elseif ($user['role'] !== $roleDipilih) {
        $error = 'Akun ini bukan akun ' . $roleDipilih . '. Pilih peran yang sesuai.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'nama'     => $user['nama_lengkap'],
            'role'     => $user['role'],
        ];
        header('Location: dashboard.php');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Farmasi Rumah Sakit - Login</title>
<style>
 *{box-sizing:border-box}
 body{margin:0;font-family:Arial,sans-serif;min-height:100vh;background:linear-gradient(135deg,#0d6efd 0%,#5b3df5 100%);color:#fff}
 .latar{position:fixed;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px}
 .latar h1{font-size:54px;margin:0 0 10px}
 .latar p{font-size:20px;margin:0;opacity:.9}
 .overlay{position:fixed;inset:0;background:rgba(10,20,50,.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:20px}
 .modal{background:#fff;color:#212529;width:100%;max-width:410px;border-radius:14px;padding:30px 32px;box-shadow:0 12px 40px rgba(0,0,0,.35);animation:muncul .35s ease}
 @keyframes muncul{from{opacity:0;transform:translateY(-25px) scale(.95)}to{opacity:1;transform:none}}
 .modal h2{margin:0 0 4px;text-align:center;color:#0d6efd;font-size:25px}
 .modal .desc{text-align:center;color:#6c757d;font-size:14px;margin:0 0 18px}
 .peran{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:6px}
 .peran input{display:none}
 .peran span{display:block;text-align:center;padding:9px 4px;border:1px solid #ced4da;border-radius:8px;cursor:pointer;font-size:14px;transition:.15s}
 .peran input:checked + span{background:#0d6efd;border-color:#0d6efd;color:#fff;font-weight:bold}
 label.f{display:block;font-weight:bold;font-size:14px;margin-top:14px}
 input[type=text],input[type=password]{width:100%;padding:11px;margin-top:5px;border:1px solid #ced4da;border-radius:8px;font-size:14px}
 input:focus{outline:2px solid #9ec5fe;border-color:#0d6efd}
 button{width:100%;margin-top:20px;padding:12px;background:#0d6efd;color:#fff;border:0;border-radius:8px;cursor:pointer;font-size:15px;font-weight:bold}
 button:hover{background:#0a58ca}
 .err{background:#f8d7da;color:#842029;padding:10px;border-radius:8px;text-align:center;margin-bottom:6px;font-size:14px}
 .contoh{margin-top:18px;padding-top:14px;border-top:1px solid #e9ecef;color:#6c757d;font-size:12px;text-align:center;line-height:1.8}
</style>
</head>
<body>
<div class="latar">
 <h1>Farmasi Rumah Sakit</h1>
 <p>Sistem Informasi Farmasi</p>
</div>

<div class="overlay">
 <div class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-login">
  <h2 id="judul-login">Login </h2>
  <p class="desc">Pilih peran Anda, lalu masuk</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
   <div class="peran">
    <label><input type="radio" name="role" value="admin"    <?= $roleDipilih === 'admin' ? 'checked' : '' ?>><span>Admin</span></label>
    <label><input type="radio" name="role" value="apoteker" <?= $roleDipilih === 'apoteker' ? 'checked' : '' ?>><span>Apoteker</span></label>
    <label><input type="radio" name="role" value="dokter"   <?= $roleDipilih === 'dokter' ? 'checked' : '' ?>><span>Dokter</span></label>
   </div>
   <label class="f">Username</label>
   <input type="text" name="username" placeholder="Masukkan username" required autofocus>
   <label class="f">Password</label>
   <input type="password" name="password" placeholder="Masukkan password" required>
   <button type="submit">Masuk</button>
  </form>
  <div class="contoh">
   Akun contoh:<br>
   admin / admin123 &nbsp;|&nbsp; apoteker / apoteker123 &nbsp;|&nbsp; dokter / dokter123
  </div>
 </div>
</div>
</body>
</html>
