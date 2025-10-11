<?php
session_start();
include 'db.php';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // cek duplicate
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $r = $stmt->get_result();

    if ($r && $r->num_rows > 0) {
        $error = "Username sudah digunakan.";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $ins->bind_param("sss", $username, $hash, $role);
        $ins->execute();
        if ($ins->affected_rows > 0) {
            $success = "User berhasil dibuat. Silakan login.";
        } else {
            $error = "Gagal membuat user.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - POS Pecel Lele</title>
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
    --success-color: #22c55e;
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

.register-container {
    width: 100%;
    max-width: 400px;
}

.register-card {
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

.logo-header {
    margin-bottom: 40px;
}

.logo-header img {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
}

.logo-header h1 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.logo-header p {
    color: var(--text-muted);
    font-size: 15px;
}

.form-group {
    margin-bottom: 24px;
    text-align: left;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--text-color);
}

.form-control, .form-select {
    width: 100%;
    padding: 12px 16px;
    font-size: 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: var(--bg-color);
    color: var(--text-color);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.btn-register {
    width: 100%;
    padding: 14px;
    margin-top: 8px;
    font-size: 16px;
    font-weight: 600;
    background-color: var(--accent-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.1s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-register:hover {
    background-color: #059669;
    transform: translateY(-1px);
}

.btn-register:active {
    transform: translateY(0);
}

.btn-register .spinner-border {
    width: 18px;
    height: 18px;
    margin-left: 10px;
    display: none;
}

.btn-register.loading .spinner-border {
    display: inline-block;
}

.btn-register.loading .btn-text {
    display: none;
}

/* Alert Styles */
.alert {
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
    border: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: alertPop 0.4s ease;
}

@keyframes alertPop {
    from {transform: scale(0.95); opacity: 0;}
    to {transform: scale(1); opacity: 1;}
}

.alert-success {
    background-color: rgba(34, 197, 94, 0.1);
    color: var(--success-color);
}

.alert-danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--error-color);
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

.extra-links {
    margin-top: 32px;
    font-size: 14px;
}

.extra-links a {
    color: var(--accent-color);
    text-decoration: none;
    font-weight: 500;
    transition: text-decoration 0.2s ease;
}

.extra-links a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="dark-toggle" id="darkToggle">
    <div class="circle"></div>
</div>

<div class="register-container">
    <div class="register-card">
        <div class="logo-header">
            <img src="img/logo.png" alt="Logo POS Pecel Lele">
            <h1>Daftar Akun Baru</h1>
            <p>Lengkapi data di bawah untuk membuat akun</p>
        </div>

        <form method="post" id="registerForm">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-circle-xmark fa-lg"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-circle-check fa-lg"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
            </div>

            <div class="form-group">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="" selected disabled>Pilih role</option>
                    <option value="kasir">Kasir</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn-register" name="register" id="registerButton">
                <span class="btn-text">Daftar</span>
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <div class="extra-links">
            <a href="index.php">Kembali ke Login</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dark mode toggle
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

    // Form submission with loading state
    const registerForm = document.getElementById('registerForm');
    const registerButton = document.getElementById('registerButton');

    registerForm.addEventListener('submit', function(e) {
        // Tampilkan animasi loading
        registerButton.classList.add('loading');
        registerButton.disabled = true;
    });
});
</script>

</body>
</html>