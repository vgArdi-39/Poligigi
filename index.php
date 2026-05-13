<?php
require_once 'assets/config.php';
require_once 'assets/session.php';

$session = new Session();

if ($session->get('logged_in') === true) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// ════════════ HANDLE LOGIN ════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Tambahkan pengambilan 'email' jika sewaktu-waktu dibutuhkan di session
        $stmt = $conn->prepare("SELECT id_admin, username, email, password FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $session->set('logged_in', true);
            $session->set('username', $user['username']);
            $session->set('email', $user['email']);
            $session->set('id_admin', $user['id_admin']);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Username atau password salah.";
        }
        $stmt->close();
    } else {
        $error = "Harap isi username dan password.";
    }
}

// ════════════ HANDLE REGISTRATION ════════════
$reg_error = '';
$reg_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $reg_user       = trim($_POST['reg_user'] ?? '');
    $reg_email      = trim($_POST['reg_email'] ?? '');
    $reg_pass       = $_POST['reg_pass'] ?? '';
    $reg_pass_confirm = $_POST['reg_pass_confirm'] ?? '';

    if (empty($reg_user) || empty($reg_email) || empty($reg_pass) || empty($reg_pass_confirm)) {
        $reg_error = "Semua kolom wajib diisi.";
    } elseif (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
        $reg_error = "Format email tidak valid.";
    } elseif ($reg_pass !== $reg_pass_confirm) {
        $reg_error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($reg_pass) < 6) {
        $reg_error = "Password minimal 6 karakter.";
    } else {
        // Cek apakah username atau email sudah digunakan
        $stmt_check = $conn->prepare("SELECT id_admin FROM admin WHERE username = ? OR email = ? LIMIT 1");
        $stmt_check->bind_param("ss", $reg_user, $reg_email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $reg_error = "Username atau Email sudah terdaftar.";
        } else {
            // Lakukan Insert user baru
            $hashed_password = password_hash($reg_pass, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare("INSERT INTO admin (username, email, password) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $reg_user, $reg_email, $hashed_password);

            if ($stmt_insert->execute()) {
                $reg_success = "Pendaftaran berhasil! Silakan login.";
                // Mengosongkan field setelah berhasil
                $_POST['reg_user'] = '';
                $_POST['reg_email'] = '';
            } else {
                $reg_error = "Terjadi kesalahan sistem saat mendaftar.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="data:,">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Login Poliklinik</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ── PHP error banner ── */
        .php-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 12px;
            font-size: 0.875rem;
            text-align: center;
        }
        .php-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 12px;
            font-size: 0.875rem;
            text-align: center;
        }
        /* Show login page with error automatically */
        .show-login  #page-login  { display: flex !important; }
        .show-login  #page-register { display: none !important; }
        .show-register #page-register { display: flex !important; }
        .show-register #page-login  { display: none !important; }
    </style>
</head>
<body class="<?= !empty($error) ? 'show-login' : (!empty($reg_error) || !empty($reg_success) ? 'show-register' : '') ?>">

    <div id="main-content" class="main-wrapper">

        <div id="page-login" class="container">
            <div class="card">
                <h2 class="title">LOGIN</h2>

                <?php if (!empty($error)): ?>
                    <div class="php-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="login-form">
                    <div class="input-group">
                        <label>User Name</label>
                        <input
                            type="text"
                            id="username_login"
                            name="username"
                            placeholder="Masukkan User Name anda"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        >
                        <span class="error-msg" id="error-username_login"></span>
                    </div>

                    <div id="password-inline-group" class="input-group <?= empty($error) ? 'hidden' : '' ?>">
                        <label>Kata Sandi</label>
                        <div class="password-container">
                            <input type="password" id="password-inline" name="password" placeholder="Masukkan Kata Sandi anda">
                            <div class="eye-btn" onclick="toggleRegisterPass('password-inline', this)">
                                <img src="assets/img/mataTutup.png" alt="eye" class="eye-icon-img">
                            </div>
                        </div>
                        <span class="error-msg" id="error-password-inline"></span>
                    </div>

                    <div class="forgot-right" id="forgot-login" style="<?= empty($error) ? 'display:none' : '' ?>">
                        <a href="#" class="link-blue">Lupa kata sandi?</a>
                    </div>

                    <button type="button" class="btn-next" id="btn-next-login" onclick="openPasswordInline()" <?= !empty($error) ? 'style="display:none"' : '' ?>>
                        Selanjutnya
                    </button>

                    <button type="submit" name="login" class="btn-next" id="btn-submit-login" <?= empty($error) ? 'style="display:none"' : '' ?>>
                        MASUK
                    </button>
                </form>

                <div class="footer-text">
                    <a href="#" class="link-blue">Lupa kata sandi?</a>
                    <p>Belum punya akun? <br>
                        <span class="link-blue" style="cursor:pointer" onclick="switchPage('page-register')">Daftar sekarang</span>
                    </p>
                </div>
            </div>
        </div>

        <div id="page-register" class="container hidden">
            <div class="card card-wide" style="position: relative;">
                <div class="back-arrow" onclick="switchPage('page-login')">
                    <img src="assets/img/panah.png" alt="Back" class="back-icon-img">
                </div>

                <h2 class="title">DAFTAR</h2>

                <?php if (!empty($reg_error)): ?>
                    <div class="php-error"><?= htmlspecialchars($reg_error) ?></div>
                <?php endif; ?>
                <?php if (!empty($reg_success)): ?>
                    <div class="php-success"><?= htmlspecialchars($reg_success) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label>User Name</label>
                        <input type="text" id="reg_user" name="reg_user" placeholder="Masukkan User Name anda"
                               value="<?= htmlspecialchars($_POST['reg_user'] ?? '') ?>">
                        <span class="error-msg" id="error-reg_user"></span>
                    </div>
                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" id="reg_email" name="reg_email" placeholder="contoh: nama@gmail.com"
                               value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
                        <span class="error-msg" id="error-reg_email"></span>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <div class="password-container">
                            <input type="password" id="reg_pass" name="reg_pass" placeholder="Password minimal 6 karakter">
                            <div class="eye-btn" onclick="toggleRegisterPass('reg_pass', this)">
                                <img src="assets/img/mataTutup.png" alt="eye" class="eye-icon-img">
                            </div>
                        </div>
                        <span class="error-msg" id="error-reg_pass"></span>
                    </div>
                    <div class="input-group">
                        <label>Konfirmasi Password</label>
                        <div class="password-container">
                            <input type="password" id="reg_pass_confirm" name="reg_pass_confirm" placeholder="Masukkan ulang password anda">
                            <div class="eye-btn" onclick="toggleRegisterPass('reg_pass_confirm', this)">
                                <img src="assets/img/mataTutup.png" alt="eye" class="eye-icon-img">
                            </div>
                        </div>
                        <span class="error-msg" id="error-reg_pass_confirm"></span>
                    </div>
                    <button type="submit" name="register" class="btn-next" onclick="return handleRegister()">Daftar</button>
                </form>
            </div>
        </div>

    </div><div class="initialop" style="display:flex; justify-content:center; margin-top: 20px;">
        <form method="POST" action="assets/initop.php">
            <button type="submit" style="padding: 10px; cursor:pointer;">Init user</button>
        </form>
    </div>

    <script src="assets/Scripts/JS/script.js"></script>
    <script>
    // ── Inline password reveal (replaces the overlay approach) ──────────────
    function openPasswordInline() {
        const usernameVal = document.getElementById('username_login').value.trim();
        const errEl = document.getElementById('error-username_login');

        if (!usernameVal) {
            errEl.textContent = 'User Name tidak boleh kosong.';
            return;
        }
        errEl.textContent = '';

        document.getElementById('password-inline-group').classList.remove('hidden');
        document.getElementById('forgot-login').style.display = 'block';
        document.getElementById('btn-next-login').style.display = 'none';
        document.getElementById('btn-submit-login').style.display = '';
        document.getElementById('password-inline').focus();
    }

    // ── Client-side register validation ──
    function handleRegister() {
        const fields = [
            { id: 'reg_user',         errId: 'error-reg_user',         label: 'User Name' },
            { id: 'reg_email',        errId: 'error-reg_email',        label: 'Email' },
            { id: 'reg_pass',         errId: 'error-reg_pass',         label: 'Password' },
            { id: 'reg_pass_confirm', errId: 'error-reg_pass_confirm', label: 'Konfirmasi Password' },
        ];

        let valid = true;

        fields.forEach(f => {
            const el  = document.getElementById(f.id);
            const err = document.getElementById(f.errId);
            if (!el.value.trim()) {
                err.textContent = f.label + ' tidak boleh kosong.';
                valid = false;
            } else {
                err.textContent = '';
            }
        });

        // Email Format Validation
        const emailEl = document.getElementById('reg_email');
        if (emailEl.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value.trim())) {
            document.getElementById('error-reg_email').textContent = 'Format email tidak valid.';
            valid = false;
        }

        const pass    = document.getElementById('reg_pass').value;
        const confirm = document.getElementById('reg_pass_confirm').value;

        if (pass && confirm && pass !== confirm) {
            document.getElementById('error-reg_pass_confirm').textContent = 'Password tidak cocok.';
            valid = false;
        }

        if (pass && pass.length < 6) {
            document.getElementById('error-reg_pass').textContent = 'Password minimal 6 karakter.';
            valid = false;
        }

        return valid; // allow form submit only when valid
    }

    // ── Toggle password visibility ───────────────────────────────────────────
    function toggleRegisterPass(inputId, btn) {
        const input = document.getElementById(inputId);
        const img   = btn.querySelector('img');
        if (input.type === 'password') {
            input.type = 'text';
            img.src = 'assets/img/mataBuka.png';
        } else {
            input.type = 'password';
            img.src = 'assets/img/mataTutup.png';
        }
    }

    // ── Page switcher ────────────────────────────────────────────────────────
    function switchPage(pageId) {
        document.body.classList.remove('show-login', 'show-register');

        document.querySelectorAll('.container').forEach(el => el.classList.add('hidden'));

        document.getElementById(pageId).classList.remove('hidden');
    }
    </script>
</body>
</html>