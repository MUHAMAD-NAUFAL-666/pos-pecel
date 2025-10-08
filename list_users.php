<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: index.php'); exit; }
include 'db.php';

// hapus user jika ada ?hapus=id
if (isset($_GET['hapus'])) {
  $id = intval($_GET['hapus']);
  $conn->query("DELETE FROM users WHERE id = $id");
  header("Location: list_users.php"); exit;
}

$res = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Daftar User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<div class="container">
  <h3>Daftar User</h3>
  <a href="register.php" class="btn btn-success mb-3">Tambah User</a>
  <table class="table table-bordered">
    <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php while($u = $res->fetch_assoc()): ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= $u['role'] ?></td>
        <td><?= $u['created_at'] ?></td>
        <td>
          <a href="list_users.php?hapus=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus user?')">Hapus</a>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body></html>
