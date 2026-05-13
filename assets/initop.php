<?php
require_once 'config.php';

// ── Change these before running ──────────────────────────
$new_username = 'adminyami';
$new_password = '123';
// ────────────────────────────────────────────────────────

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $new_username, $hashed_password);

try {
    if ($stmt->execute()) {
        echo "✅ Akun berhasil dibuat! Username: " . htmlspecialchars($new_username);
    }
} catch (Exception $e) {
    echo "❌ Registration failed: " . $e->getMessage();
}

$stmt->close();
$conn->close();
?>