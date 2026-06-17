<?php
// ===== Auth Helpers =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require the user to be logged in as a beneficiary.
 */
function requireBeneficiary(): void {
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'beneficiary') {
        header('Location: ' . BASE_URL . '/login.php?msg=Please+log+in+to+continue.');
        exit;
    }
}

/**
 * Require the user to be logged in as an evaluator.
 */
function requireEvaluator(): void {
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'evaluator') {
        header('Location: ' . BASE_URL . '/login.php?msg=Please+log+in+to+continue.');
        exit;
    }
}

/**
 * Require the user to be logged in as an extension coordinator.
 * Redirects to login page if not authenticated.
 */
function requireEC(): void {
    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'extension_coordinator') {
        header('Location: ' . BASE_URL . '/login.php?msg=Please+log+in+to+continue.');
        exit;
    }
}

/**
 * Check if the current session user is logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Return the logged-in user's display name.
 */
function currentUserName(): string {
    return htmlspecialchars(($_SESSION['user_first'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));
}

/**
 * Return initials for the avatar.
 */
function currentUserInitials(): string {
    $f = $_SESSION['user_first'][0] ?? '';
    $l = $_SESSION['user_last'][0]  ?? '';
    return strtoupper($f . $l);
}

/**
 * Sanitize output.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message helpers.
 */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
