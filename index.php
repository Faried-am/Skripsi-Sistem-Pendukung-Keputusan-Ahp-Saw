<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    if (currentRole() === 'admin') {
        redirect(BASE_URL . 'admin/index.php');
    } else {
        redirect(BASE_URL . 'pimpinan/index.php');
    }
} else {
    redirect(BASE_URL . 'auth/login.php');
}
