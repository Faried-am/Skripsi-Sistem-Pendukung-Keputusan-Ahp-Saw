<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect(BASE_URL . 'index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $q = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username' LIMIT 1");
        $user = $q ? mysqli_fetch_assoc($q) : null;

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                redirect(BASE_URL . 'admin/index.php');
            } else {
                redirect(BASE_URL . 'pimpinan/index.php');
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk - <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #1d3f5c, #2c5f8a 60%, #f2a71b);
  }
  .login-card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 10px 35px rgba(0,0,0,.25);
    overflow: hidden;
  }
  .login-brand {
    background: #1d3f5c;
    color: #fff;
    padding: 2.5rem 2rem;
  }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
      <div class="card login-card">
        <div class="row g-0">
          <div class="col-md-5 login-brand d-flex flex-column justify-content-center">
            <i class="bi bi-clipboard2-data display-4 mb-3"></i>
            <h4 class="fw-bold">SPK Kelayakan Penerima Bantuan</h4>
            <p class="small mb-0 opacity-75">Metode AHP &amp; SAW untuk mendukung keputusan penentuan penerima bantuan sosial secara objektif dan transparan.</p>
          </div>
          <div class="col-md-7">
            <div class="p-4 p-md-5">
              <h5 class="fw-semibold mb-4">Masuk ke Sistem</h5>
              <?php if ($error): ?>
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= clean($error) ?></div>
              <?php endif; ?>
              <form method="POST" autocomplete="off">
                <div class="mb-3">
                  <label class="form-label small fw-semibold">Username</label>
                  <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                  <label class="form-label small fw-semibold">Password</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">
                  <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
              </form>
              <hr class="my-4">
              
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
