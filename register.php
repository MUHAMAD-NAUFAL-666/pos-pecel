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
<meta charset="utf-8">
<title>Register - POS Pecel Lele</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 mt-5">
      <div class="card shadow">
        <div class="card-body">
          <h4 class="mb-3">👤 Buat User</h4>
          <?php if(!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
          <?php if(!empty($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
          <form method="post">
            <div class="mb-3"><label>Username</label><input name="username" class="form-control" required></div>
            <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
            <div class="mb-3">
              <label>Role</label>
              <select name="role" class="form-select" required>
                <option value="kasir">Kasir</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <button name="register" class="btn btn-success w-100">Daftar</button>
          </form>
          <a class="d-block mt-3 text-center" href="index.php">Kembali ke Login</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
