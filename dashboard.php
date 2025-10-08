<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); exit;
}
include 'db.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Ambil data total harian, mingguan, bulanan
$today = date("Y-m-d");
$thisWeek = date("Y-m-d", strtotime("-7 days"));
$thisMonth = date("Y-m-01");

$todaySales = $conn->query("SELECT SUM(total) as total FROM sales WHERE DATE(created_at)='$today'")->fetch_assoc()['total'] ?? 0;
$weekSales = $conn->query("SELECT SUM(total) as total FROM sales WHERE created_at >= '$thisWeek'")->fetch_assoc()['total'] ?? 0;
$monthSales = $conn->query("SELECT SUM(total) as total FROM sales WHERE created_at >= '$thisMonth'")->fetch_assoc()['total'] ?? 0;

// Ambil data 7 hari terakhir
$chartData = [];
$res = $conn->query("SELECT DATE(created_at) as tgl, SUM(total) as total 
                     FROM sales WHERE created_at >= '$thisWeek'
                     GROUP BY DATE(created_at) ORDER BY tgl ASC");
while ($row = $res->fetch_assoc()) $chartData[] = $row;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Dashboard - POS Pecel Lele</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
  display: flex;
  min-height: 100vh;
  background: #f5f7fa;
  font-family: 'Segoe UI', Tahoma, sans-serif;
}

.sidebar {
  width: 260px;
  background: linear-gradient(180deg, #0d6efd, #2563eb);
  color: white;
  padding: 20px;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
}

.sidebar h4 {
  font-weight: 700;
  font-size: 1.4rem;
  margin-bottom: 20px;
  text-align: center;
}

.sidebar p {
  text-align: center;
  font-size: 0.9rem;
  margin-bottom: 25px;
}

.sidebar a {
  color: white;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 15px;
  margin-bottom: 10px;
  border-radius: 10px;
  text-decoration: none;
  transition: background 0.3s ease, transform 0.2s ease;
}

.sidebar a:hover,
.sidebar a.active {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(4px);
}

.content {
  flex: 1;
  padding: 25px;
}

.card {
  border-radius: 15px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  background: #fff;
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

@media(max-width: 768px) {
  .sidebar {
    position: fixed;
    left: -260px;
    top: 0;
    height: 100%;
    z-index: 999;
  }
  .sidebar.show {
    left: 0;
  }
  .content {
    margin-left: 0 !important;
    width: 100%;
  }
}

.toggle-btn {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #0d6efd;
}

@media(max-width: 768px) {
  .toggle-btn {
    display: block;
    margin-bottom: 20px;
  }
}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <h4>POS Pecel Lele</h4>
  <p>👋 Halo, <b><?= htmlspecialchars($username) ?></b><br><small><?= ucfirst($role) ?></small></p>
  <a href="dashboard.php" class="active">🏠 Dashboard</a>
  <?php if($role === 'admin'): ?>
    <a href="register.php">➕ Tambah User</a>
    <a href="list_users.php">👥 Daftar User</a>
    <a href="report.php">📊 Laporan</a>
  <?php endif; ?>
  <a href="pos.php">💻 POS / Transaksi</a>
  <a href="report.php">📑 Laporan Penjualan</a>
  <a href="logout.php" style="background:#dc3545;">🚪 Logout</a>
</div>

<div class="content">
  <button class="toggle-btn" onclick="document.getElementById('sidebar').classList.toggle('show')">☰</button>

  <h3 class="mb-4 fw-bold">📊 Dashboard Penjualan</h3>
  
  <div class="row mb-4">
    <div class="col-md-4 mb-3">
      <div class="card text-center p-3">
        <h6 class="text-muted">Penjualan Hari Ini</h6>
        <h3 class="text-success fw-bold">Rp <?= number_format($todaySales,0,",",".") ?></h3>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card text-center p-3">
        <h6 class="text-muted">Penjualan Minggu Ini</h6>
        <h3 class="text-primary fw-bold">Rp <?= number_format($weekSales,0,",",".") ?></h3>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card text-center p-3">
        <h6 class="text-muted">Penjualan Bulan Ini</h6>
        <h3 class="text-warning fw-bold">Rp <?= number_format($monthSales,0,",",".") ?></h3>
      </div>
    </div>
  </div>

  <div class="card p-4">
    <h5 class="mb-3 fw-bold">📈 Grafik Penjualan 7 Hari Terakhir</h5>
    <canvas id="salesChart" height="120"></canvas>
  </div>
</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(13, 110, 253, 0.3)');
gradient.addColorStop(1, 'rgba(13, 110, 253, 0)');

new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($chartData, 'tgl')) ?>,
    datasets: [{
      label: 'Total Penjualan',
      data: <?= json_encode(array_column($chartData, 'total')) ?>,
      backgroundColor: gradient,
      borderColor: '#0d6efd',
      borderWidth: 3,
      fill: true,
      tension: 0.4,
      pointRadius: 5,
      pointHoverRadius: 8,
      pointBackgroundColor: '#fff',
      pointBorderColor: '#0d6efd',
      pointBorderWidth: 3,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        usePointStyle: true,
        callbacks: {
          label: (ctx) => "Rp " + ctx.raw.toLocaleString('id-ID')
        },
        backgroundColor: '#0d6efd',
        titleColor: '#fff',
        bodyColor: '#fff',
        padding: 12,
        displayColors: false
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value) => "Rp " + value.toLocaleString('id-ID')
        },
        grid: { color: 'rgba(0,0,0,0.05)' }
      },
      x: { grid: { display: false } }
    },
    animation: { duration: 1200, easing: 'easeOutQuart' },
    interaction: { mode: 'nearest', intersect: false }
  }
});
</script>
</body>
</html>
