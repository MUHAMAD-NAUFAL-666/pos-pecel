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

 // Hitung total pendapatan hari ini
$todayQuery = $conn->query("SELECT SUM(total) AS total FROM sales WHERE DATE(created_at) = CURDATE()");
$todaySales = $todayQuery->fetch_assoc()['total'] ?? 0;

// Hitung total pendapatan minggu ini
$weekQuery = $conn->query("SELECT SUM(total) AS total FROM sales WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$weekSales = $weekQuery->fetch_assoc()['total'] ?? 0;

// Hitung total pendapatan bulan ini
$monthQuery = $conn->query("SELECT SUM(total) AS total FROM sales WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
$monthSales = $monthQuery->fetch_assoc()['total'] ?? 0;


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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Import Font Inter */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

/* CSS Variables untuk Tema */
:root {
    --bg-color: #ffffff;
    --page-bg: #f7f8fa;
    --sidebar-bg: #ffffff;
    --text-color: #1a1a1a;
    --text-muted: #6c757d;
    --accent-color: #10b981;
    --accent-color-light: #d1fae5;
    --border-color: #e5e7eb;
    --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    --header-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* DARK MODE */
.dark {
    --bg-color: #1f2937;
    --page-bg: #111827;
    --sidebar-bg: #1f2937;
    --text-color: #f3f4f6;
    --text-muted: #9ca3af;
    --border-color: #374151;
    --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    --header-shadow: 0 2px 10px rgba(0,0,0,0.3);
    --accent-color-light: #064e3b;
}

body {
    display: flex;
    min-height: 100vh;
    background-color: var(--page-bg);
    color: var(--text-color);
    font-family: 'Inter', sans-serif;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Sidebar Styling */
.sidebar {
    width: 260px;
    background-color: var(--sidebar-bg);
    color: var(--text-color);
    padding: 20px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--border-color);
    transition: all 0.3s ease;
    box-shadow: var(--header-shadow);
    z-index: 100;
}

.sidebar h4 {
    font-weight: 700;
    font-size: 1.4rem;
    margin-bottom: 20px;
    text-align: center;
    color: var(--accent-color);
}

.sidebar p {
    text-align: center;
    font-size: 0.9rem;
    margin-bottom: 25px;
    color: var(--text-muted);
}

.sidebar a {
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    margin-bottom: 10px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar a:hover,
.sidebar a.active {
    background-color: var(--accent-color);
    color: white;
    transform: translateX(5px);
}

.sidebar a[style*="background:#dc3545;"] {
    background-color: #dc3545 !important;
    color: white !important;
}

/* Content Area */
.content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Header Styling */
.content-header {
    background-color: var(--bg-color);
    padding: 20px 30px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--header-shadow);
}

.content-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

/* Main Content */
.main-content {
    padding: 30px;
    flex-grow: 1;
    overflow-y: auto;
}

/* Card Styling */
.stat-card {
    border-radius: 16px;
    border: none;
    box-shadow: var(--card-shadow);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: var(--bg-color);
    color: var(--text-color);
    overflow: hidden;
    position: relative;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stat-card .card-body {
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-card .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-card .stat-info h5 {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-card .stat-info h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
}

/* Chart Card */
.chart-card {
    border-radius: 16px;
    border: none;
    box-shadow: var(--card-shadow);
    background-color: var(--bg-color);
    color: var(--text-color);
}

.chart-card .card-body {
    padding: 25px;
}

.chart-card h5 {
    font-weight: 600;
    margin-bottom: 20px;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

/* Dark Mode Toggle Button */
.dark-toggle {
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

/* Mobile Responsiveness */
@media(max-width: 768px) {
    .sidebar {
        position: fixed;
        left: -260px;
        top: 0;
        height: 100%;
        z-index: 1000;
    }
    .sidebar.show {
        left: 0;
    }
    .content {
        margin-left: 0;
        width: 100%;
    }
    .content-header {
        padding: 15px 20px;
    }
    .main-content {
        padding: 20px;
    }
    .stat-card .card-body {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}

.toggle-btn {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--text-color);
    cursor: pointer;
}

@media(max-width: 768px) {
    .toggle-btn {
        display: block;
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
    <div class="content-header">
        <button class="toggle-btn" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="fas fa-bars"></i>
        </button>
        <h1>Dashboard Penjualan</h1>
        <div class="dark-toggle" id="darkToggle">
            <div class="circle"></div>
        </div>
    </div>

    <div class="main-content">
       <div class="row mb-4 fade-in-up">
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card stat-card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6>Hari Ini</h6>
                    <h4 class="text-success">Rp <?= number_format($todaySales, 0, ',', '.') ?></h4>
                </div>
                <div class="stat-icon" style="background-color: rgba(34,197,94,0.1); color:#22c55e;">
                    <i class="fas fa-calendar-day fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card stat-card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6>Minggu Ini</h6>
                    <h4 class="text-primary">Rp <?= number_format($weekSales, 0, ',', '.') ?></h4>
                </div>
                <div class="stat-icon" style="background-color: rgba(59,130,246,0.1); color:#3b82f6;">
                    <i class="fas fa-calendar-week fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card stat-card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6>Bulan Ini</h6>
                    <h4 class="text-warning">Rp <?= number_format($monthSales, 0, ',', '.') ?></h4>
                </div>
                <div class="stat-icon" style="background-color: rgba(245,158,11,0.1); color:#f59e0b;">
                    <i class="fas fa-calendar-alt fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

        </div>

        <!-- ... kode kartu statistik ... -->

<div class="card chart-card fade-in-up">
    <div class="card-body">
        <h5>📈 Grafik Penjualan 7 Hari Terakhir</h5>
        <div class="chart-container" style="position: relative; height: 280px; width: 100%;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</div>
    </div>
</div>

<script>
// Dark mode toggle
const darkToggle = document.getElementById('darkToggle');
const body = document.body;

if (localStorage.getItem('dark-mode') === 'true') {
    body.classList.add('dark');
}

darkToggle.addEventListener('click', () => {
    body.classList.toggle('dark');
    localStorage.setItem('dark-mode', body.classList.contains('dark'));
    updateChartTheme();
});

// Counter Animation
const counters = document.querySelectorAll('.counter');
const speed = 300; // The lower the faster

const runCounter = (counter) => {
    const target = +counter.getAttribute('data-target');
    const count = +counter.innerText;
    const increment = target / speed;

    if (count < target) {
        counter.innerText = Math.ceil(count + increment).toLocaleString('id-ID');
        setTimeout(() => runCounter(counter), 10);
    } else {
        counter.innerText = target.toLocaleString('id-ID');
    }
};

// Intersection Observer for counter animation
const observerOptions = {
    threshold: 0.5
};

const counterObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            runCounter(entry.target);
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

counters.forEach(counter => {
    counterObserver.observe(counter);
});


// Chart.js Configuration
const ctx = document.getElementById('salesChart').getContext('2d');
let salesChart;

const createChart = () => {
    const isDark = body.classList.contains('dark');
    const accentColor = getComputedStyle(document.documentElement).getPropertyValue('--accent-color').trim();
    const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim();
    const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim();

    // Hancurkan grafik lama jika ada untuk mencegah duplikasi saat tema berubah
    if (salesChart) {
        salesChart.destroy();
    }

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    // Mengurangi opacity gradien agar lebih halus
    gradient.addColorStop(0, accentColor + '20'); // 12.5% opacity
    gradient.addColorStop(1, accentColor + '00'); // 0% opacity

    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($chartData, 'tgl')) ?>,
            datasets: [{
                label: 'Total Penjualan',
                data: <?= json_encode(array_column($chartData, 'total')) ?>,
                backgroundColor: gradient,
                borderColor: accentColor,
                borderWidth: 2.5, // Sedikit lebih tipis
                fill: true,
                tension: 0.3,     // Kurangi kelengkungan garis
                pointRadius: 4,    // Titik lebih kecil
                pointHoverRadius: 6,
                pointBackgroundColor: isDark ? '#1f2937' : '#fff',
                pointBorderColor: accentColor,
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Penting agar grafik mengikuti container
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#374151' : '#fff',
                    titleColor: isDark ? '#f3f4f6' : '#1a1a1a',
                    bodyColor: isDark ? '#f3f4f6' : '#1a1a1a',
                    borderColor: gridColor,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => "Rp " + ctx.raw.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        callback: (value) => "Rp " + value.toLocaleString('id-ID')
                    },
                    grid: { 
                        color: gridColor,
                        drawBorder: false,
                        // Mengurangi jumlah garis grid agar lebih bersih
                        borderDash: [5, 5] 
                    }
                },
                x: { 
                    grid: { 
                        display: false,
                        drawBorder: false
                    },
                    ticks: { 
                        color: textColor,
                        maxRotation: 0, // Mencegah label miring
                        autoSkip: true, // Lewati label jika terlalu padat
                        maxTicksLimit: 7 // Maksimal 7 titik di sumbu X
                    }
                }
            },
            animation: {
                duration: 1000, // Sedikit lebih cepat
                easing: 'easeInOutCubic'
            },
            interaction: {
                mode: 'index',
                intersect: false
            }
        }
    });
};
const updateChartTheme = () => {
    createChart();
};

// Initialize chart on page load
document.addEventListener('DOMContentLoaded', () => {
    createChart();
});
</script>
</body>
</html>