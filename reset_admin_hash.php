<?php
// ============================================================
// PAThrive – One-Time Admin Password Hash Reset
// Run once via browser or CLI, then DELETE this file.
// ============================================================

// ── DB Config (match your login.php) ────────────────────────
$host    = 'localhost';
$dbname  = 'pathrive_db';
$dbuser  = 'root';       // change if needed
$dbpass  = '';           // change if needed

// ── New password to set ──────────────────────────────────────
$username    = 'anacruz';
$newPassword = 'Admin@1234';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser, $dbpass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Generate a fresh bcrypt hash
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        'UPDATE users SET password_hash = ? WHERE username = ?'
    );
    $stmt->execute([$hash, $username]);

    if ($stmt->rowCount() > 0) {
        echo "✅ Password hash updated successfully for user: <strong>$username</strong><br>";
        echo "🔑 New password: <strong>$newPassword</strong><br>";
        echo "<br><strong style='color:red'>⚠️ Delete this file immediately after use!</strong>";
    } else {
        echo "❌ User '$username' not found. No changes made.";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage());
}