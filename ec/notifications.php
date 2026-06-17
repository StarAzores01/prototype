<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireEC();

if (isset($_GET['mark_all_read'])) {
    try {
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE role="all" OR user_id=?')
            ->execute([$_SESSION['user_id']]);
    } catch (\Throwable $e) {}
}

redirect(BASE_URL . '/ec/dashboard.php');
