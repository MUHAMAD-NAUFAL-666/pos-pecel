<?php
session_start();
include 'db.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] === 'kasir') {
                header("Location: face_recog.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - POS Pecel Lele</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --bg-color: #ffffff;
    --page-bg: #f7f8fa;
    --text-color: #1a1a1a;
    --text-muted: #6c757d;
    --accent-color: #10b981;
    --border-color: #e5e7eb;
    --error-color: #ef4444;
}

/* DARK MODE */
.dark {
    --bg-color: #1f2937;
    --page-bg: #111827;
    --text-color: #f3f4f6;
    --text-muted: #9ca3af;
    --border-color: #374151;
}

body {
    font-family: 'Inter', sans-serif;
    background-color: var(--page-bg);
    color: var(--text-color);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.login-container {
    width: 100%;
    max-width: 400px;
}

.login-card {
    background-color: var(--bg-color);
    padding: 48px 40px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    text-align: center;
    animation: fadeIn 0.7s ease-out forwards;
    transition: background-color 0.3s ease, color 0.3s ease;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}

.logo-header img {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
}

.logo-header h1 {
    font-size: 24px;
    font-weight: 700;
}

.form-group {
    margin-bottom: 24px;
    text-align: left;
}

.form-label {
    font-weight: 500;
}

.form-control {
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    background-color: var(--bg-color);
    color: var(--text-color);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

.btn-login {
    width: 100%;
    padding: 14px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    color: #fff;
    background-color: var(--accent-color);
    cursor: pointer;
    transition: transform 0.1s, background-color 0.2s;
}

.btn-login:hover {
    background-color: #059669;
    transform: translateY(-1px);
}

.error-alert {
    background: rgba(239, 68, 68, 0.1);
    border-left: 5px solid var(--error-color);
    color: var(--error-color);
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: alertPop 0.4s ease;
}

@keyframes alertPop {
    from {transform: scale(0.8); opacity: 0;}
    to {transform: scale(1); opacity: 1;}
}

/* DARK MODE BUTTON */
.dark-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 30px;
    width: 60px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 4px;
    transition: background 0.3s, border-color 0.3s;
}

.dark-toggle .circle {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--accent-color);
    transition: transform 0.3s ease;
}

.dark .dark-toggle .circle {
    transform: translateX(30px);
}
</style>
</head>
<body>

<div class="dark-toggle" id="darkToggle">
    <div class="circle"></div>
</div>

<div class="login-container">
    <div class="login-card">
        <div class="logo-header">
            <img src="img/logo.png" alt="Logo POS Pecel Lele">
            <h1>POS Depot Lusi</h1>
            <p>Selamat datang kembali</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-alert">
                <i class="fas fa-circle-xmark fa-lg"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" id="loginForm">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn-login" name="login" id="loginButton">
                <span class="btn-text">Masuk</span>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
            </button>
        </form>

        <div class="extra-links mt-3">
            <a href="register.php" style="color: var(--accent-color); text-decoration:none;">Buat akun baru</a>
        </div>

        <div class="demo-info mt-4 text-muted">
            Demo: <b>kasir1</b> / <b>123456</b>
        </div>
    </div>
</div>

<script>
const darkToggle = document.getElementById('darkToggle');
const body = document.body;

// Cek preferensi dark mode dari localStorage
if (localStorage.getItem('dark-mode') === 'true') {
    body.classList.add('dark');
}

// Toggle dark mode
darkToggle.addEventListener('click', () => {
    body.classList.toggle('dark');
    localStorage.setItem('dark-mode', body.classList.contains('dark'));
});

const loginForm = document.getElementById('loginForm');
const loginButton = document.getElementById('loginButton');

loginForm.addEventListener('submit', function(e) {
    loginButton.classList.add('loading');
    loginButton.querySelector('.spinner-border').style.display = 'inline-block';
    loginButton.querySelector('.btn-text').style.display = 'none';
});
</script>

</body>
</html>
