<?php
// ===== Database Connection =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pathrive_db');

$pdo = null;

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:40px;color:#991B1B;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;max-width:500px;margin:60px auto">
        <strong>Database connection failed.</strong><br><br>
        Please check your database credentials in <code>includes/db.php</code> and ensure the
        <code>pathrive_db</code> database exists.<br><br>
        <small>' . htmlspecialchars($e->getMessage()) . '</small>
    </div>');
}

/**
 * Generate the next unique system ID for a given prefix and table.
 * Format: PREFIX-YYYY-0001
 * Uses a transaction + lock to prevent duplicates under concurrent inserts.
 *
 * @param string $prefix  'EC', 'TR', or 'BF'
 * @param string $table   'users', 'beneficiaries', or 'participants'
 * @param string $col     column that stores the id_number (default 'id_number')
 */
function generateNextId(PDO $pdo, string $prefix, string $table, string $col = 'id_number'): string
{
    $year = date('Y');
    $like = $prefix . '-' . $year . '-%';

    // Lock the table for the duration of the read so no two requests get the same sequence
    $pdo->exec("LOCK TABLES `{$table}` WRITE");

    try {
        $stmt = $pdo->prepare(
            "SELECT {$col} FROM `{$table}`
             WHERE {$col} LIKE ?
             ORDER BY {$col} DESC LIMIT 1"
        );
        $stmt->execute([$like]);
        $last = $stmt->fetchColumn();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq   = (int)end($parts) + 1;
        }

        $newId = $prefix . '-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    } finally {
        $pdo->exec("UNLOCK TABLES");
    }

    return $newId;
}
