<?php
$hash = '$2y$12$eImiTXuWVxfM37uY4JANjOe5XceXjYguwuwnoJDR0o.OtZnomeRHm';
$pass = 'Admin@1234';

echo "Verify existing hash: ";
echo password_verify($pass, $hash) ? "MATCH ✓" : "NO MATCH ✗";
echo "\n\n";

// Generate a fresh correct hash
$newHash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Fresh hash for Admin@1234:\n";
echo $newHash . "\n\n";

// Also generate SQL to fix it
echo "Run this SQL to fix the account:\n";
echo "UPDATE users SET password_hash='" . $newHash . "' WHERE username='anacruz';\n";
