<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
include 'db.php';

$result = $conn->query("SELECT s.*, u.username FROM sales s JOIN users u ON s.user_id=u.id ORDER BY s.created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: #e9f7ef;
        }
    </style>
</head>
<body class="p-4">
    <nav class="navbar navbar-expand-lg navbar-white bg-white shadow-sm mb-3">
  <div class="container-fluid">
  <!-- Tombol Kembali hanya tampil di mobile & tablet -->
  <div class="mb-3 d-block d-lg-none">
    <button class="btn btn-outline-secondary btn-sm" onclick="history.back()">
      <i class="bi bi-arrow-left"></i> Kembali
    </button>
  </div>

    <a class="navbar-brand" href="dashboard.php">🍽️ Pecel Lele POS</a>
    <div class="d-flex align-items-center gap-2">
      <div class="small-muted me-3">Halo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
      <a href="report.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-text"></i> Laporan</a>
      <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>
<div class="container">
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">📊 Laporan Penjualan</h2>
            <div>
                <button onclick="window.print()" class="btn btn-outline-primary btn-sm">🖨 Print</button>
                <a href="export_csv.php" class="btn btn-outline-success btn-sm">📥 Export CSV</a>
            </div>
        </div>

        <input type="text" id="search" class="form-control mb-3" placeholder="🔎 Cari kasir atau tanggal...">

        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>ID</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody id="salesTable">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= $row['id'] ?></span></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><span class="badge bg-success">Rp <?= number_format($row['total'], 0, ",", ".") ?></span></td>
                        <td><?= date("d M Y H:i", strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('search').addEventListener('keyup', function(){
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#salesTable tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>
</body>
</html>
