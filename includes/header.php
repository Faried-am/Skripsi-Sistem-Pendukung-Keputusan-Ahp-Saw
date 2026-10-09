<?php
// $pageTitle harus di-set sebelum include file ini
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle) ?> - <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
  <div class="container-fluid">
    <button class="btn btn-link text-white d-lg-none" id="btnToggleSidebar"><i class="bi bi-list fs-4"></i></button>
    <a class="navbar-brand fw-semibold" href="<?= BASE_URL ?>index.php">
      <i class="bi bi-clipboard2-data me-2"></i><?= APP_NAME ?>
    </a>
    <div class="ms-auto d-flex align-items-center text-white">
      <span class="me-3 d-none d-md-inline">
        <i class="bi bi-person-circle me-1"></i>
        <?= clean($_SESSION['nama'] ?? '') ?>
        <span class="badge bg-light text-dark ms-1"><?= clean(ucfirst($_SESSION['role'] ?? '')) ?></span>
      </span>
      <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-sm btn-outline-light">
        <i class="bi bi-box-arrow-right"></i> Keluar
      </a>
    </div>
  </div>
</nav>
<div class="app-wrapper">
