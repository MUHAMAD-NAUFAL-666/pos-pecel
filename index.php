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

            // Arahkan berdasarkan role
            if ($row['role'] === 'kasir') {
                header("Location: face_recog.php"); // ke halaman face recognition
            } else {
                header("Location: dashboard.php"); // admin langsung ke dashboard
            }
            exit;
        } else {
            $error = "⚠️ Password salah.";
        }
    } else {
        $error = "❌ Username tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Login - POS Pecel Lele</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #00b09b, #96c93d);
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.card {
  border: none;
  border-radius: 15px;
  animation: fadeIn 1s ease;
}
@keyframes fadeIn {
  from {opacity: 0; transform: translateY(20px);}
  to {opacity: 1; transform: translateY(0);}
}
.btn-primary {
  background-color: #00b09b;
  border: none;
  transition: 0.3s;
}
.btn-primary:hover {
  background-color: #029e8c;
  transform: scale(1.05);
}
.form-control:focus {
  border-color: #00b09b;
  box-shadow: 0 0 0 0.2rem rgba(0,176,155,0.25);
}
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card shadow-lg p-4">
        <div class="text-center mb-3">
          <img src="img/logo.png" alt="Logo" width="80" class="mb-2">
          <h4 class="fw-bold">POS Pecel Lele</h4>
          <p class="text-muted mb-4">Silakan login untuk melanjutkan</p>
        </div>
        <?php if(!empty($error)): ?>
          <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">👤 Username</label>
            <input type="text" name="username" class="form-control" required autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label">🔒 Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100" name="login">Masuk</button>
        </form>
        <div class="text-center mt-3">
          <a href="register.php" class="text-decoration-none">➕ Buat user baru</a>
        </div>
      </div>
      <p class="text-center text-white mt-3 small">
        Demo: <b>kasir1</b> / <b>123456</b>
      </p>
    </div>
  </div>
</div>
</body>
</html>
