<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
session_destroy();
redirect(BASE_URL . '/login.php');
