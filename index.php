<?php
// Pintu masuk website: belum login -> popup login, sudah login -> dashboard
session_start();
header('Location: ' . (isset($_SESSION['user']) ? 'APLIKASI_3/dashboard.php' : 'APLIKASI_3/login.php'));
exit;
