<?php
require_once __DIR__ . '/../config/config.php';
session_unset();
session_destroy();
redirect(BASE_URL . 'auth/login.php');
