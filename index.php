<?php
require_once 'assets/config.php';
require_once 'assets/session.php';

$session = new Session();

if ($session->get('logged_in') === true) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Query the correct table and column
        $stmt = $conn->prepare("SELECT id_admin, username, password FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $session->set('logged_in', true);
            $session->set('username', $user['username']);
            $session->set('id_admin', $user['id_admin']); // correct column name
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username atau password.";
        }
    } else {
        $error = "Harap isi username dan password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polinventory - Login</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="form-container">
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- form tag with method POST is required for $_POST to work -->
    <form method="POST" action="">
        <div id="step-username" class="form-step">
            <h2>Login</h2>
            <input type="text" name="username" id="username" placeholder="Enter Username" required>
            <button type="button" onclick="showNextStep()">Selanjutnya</button>
        </div>

        <div id="step-password" class="form-step hidden">
            <h2>Masukan Kata Sandi</h2>
            <input type="password" name="password" id="password" placeholder="Enter Kata Sandi" required>
            <!-- name="login" triggers the PHP login block; no <a> wrapper -->
            <button type="submit" name="login">Login</button>
        </div>
    </form>
</div>
<script src="assets/Scripts/JS/Login.js"></script>
</body>
</html>