<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}
include 'db.php';

 $last_sale_id = 0;
 $success = "";

// Tangkap ID meja dari URL jika ada
 $meja_id = isset($_GET['meja']) ? intval($_GET['meja']) : 0;

// PROSES BAYAR
if (isset($_POST['bayar'])) {
    $user_id = intval($_SESSION['user_id']);
    $menu_ids = $_POST['menu_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    
    // MODIFIKASI: Ambil data metode pembayaran dari form
    $payment_method_post = $_POST['payment_method'] ?? 'cash';
    $bank_name = $_POST['bank_name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';

    // Hitung total berdasarkan DB (keamanan)
    $total = 0.0;
    foreach ($menu_ids as $i => $menu_id) {
        $mid = intval($menu_id);
        $qty = intval($qtys[$i]);
        if ($qty <= 0) continue;
        $stmtp = $conn->prepare("SELECT price FROM menu WHERE id = ? LIMIT 1");
        $stmtp->bind_param("i", $mid);
        $stmtp->execute();
        $rp = $stmtp->get_result()->fetch_assoc();
        $price = $rp ? (float)$rp['price'] : 0;
        $total += $price * $qty;
        $stmtp->close();
    }

    // MODIFIKASI: Tentukan nilai akhir untuk kolom payment_method
    // Jika metodenya transfer, gunakan nama bank yang dipilih
    $payment_method_to_save = $payment_method_post;
    if ($payment_method_post === 'transfer' && !empty($bank_name)) {
        $payment_method_to_save = $bank_name; // Contoh: 'bca', 'bri'
    }

    // Simpan sales
    $ins = $conn->prepare("INSERT INTO sales (user_id, total, payment_method) VALUES (?, ?, ?)");
$ins->bind_param("ids", $user_id, $total, $payment_method_to_save);
    $ins->execute();
    $sale_id = $ins->insert_id;
    $ins->close();
    $last_sale_id = $sale_id;

    // Simpan sale_details
    $stmtDetail = $conn->prepare("INSERT INTO sale_details (sale_id, menu_id, qty, subtotal) VALUES (?, ?, ?, ?)");
    for ($i = 0; $i < count($menu_ids); $i++) {
        $mid = intval($menu_ids[$i]);
        $qty = intval($qtys[$i]);
        if ($qty <= 0) continue;
        $stmtp = $conn->prepare("SELECT price FROM menu WHERE id = ? LIMIT 1");
        $stmtp->bind_param("i", $mid);
        $stmtp->execute();
        $rp = $stmtp->get_result()->fetch_assoc();
        $price = $rp ? (float)$rp['price'] : 0;
        $subtotal = $price * $qty;
        $stmtp->close();

        $stmtDetail->bind_param("iiid", $sale_id, $mid, $qty, $subtotal);
        $stmtDetail->execute();
    }
    $stmtDetail->close();

    // ✅ Update status meja jika ada meja dipilih
    if ($meja_id > 0) {
        $stmtm = $conn->prepare("UPDATE meja SET status = 'occupied' WHERE id = ?");
        $stmtm->bind_param("i", $meja_id);
        $stmtm->execute();
        $stmtm->close();
    }

    $success = "Transaksi berhasil! Total: Rp " . number_format($total, 0, ",", ".");
}

// Ambil menu dan kategori
 $menuRes = $conn->query("SELECT * FROM menu ORDER BY id ASC");
 $menus = [];
 $categories = [];
while ($r = $menuRes->fetch_assoc()) {
    $menus[] = $r;
    if (!empty($r['category'])) $categories[$r['category']] = $r['category'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS - Pecel Lele</title>
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
    --success-color: #22c55e;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
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
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* NAVBAR */
.navbar {
    background-color: var(--bg-color);
    border-bottom: 1px solid var(--border-color);
    padding: 12px 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--text-color);
}

.navbar-brand:hover {
    color: var(--accent-color);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-badge {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--accent-color);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
}

/* MAIN CONTENT */
.main-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--text-muted);
    font-size: 1rem;
}

/* SEARCH AND FILTER */
.search-filter-container {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.search-input {
    flex: 1;
    min-width: 200px;
    padding: 10px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: var(--bg-color);
    color: var(--text-color);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.category-dropdown {
    position: relative;
}

.category-btn {
    padding: 10px 16px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: var(--bg-color);
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: border-color 0.2s ease;
}

.category-btn:hover {
    border-color: var(--accent-color);
}

.category-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 10;
    padding: 8px;
    margin-top: 4px;
    display: none;
}

.category-menu.show {
    display: block;
}

.category-item {
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.category-item:hover {
    background-color: var(--page-bg);
}

.category-item.active {
    background-color: rgba(16, 185, 129, 0.1);
    color: var(--accent-color);
}

/* MENU GRID */
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.menu-card {
    background-color: var(--bg-color);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.menu-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.menu-image {
    width: 100%;
    height: 160px;
    object-fit: cover;
}

.menu-content {
    padding: 16px;
}

.menu-name {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 4px;
}

.menu-category {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin-bottom: 12px;
}

.menu-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.menu-price {
    font-weight: 700;
    color: var(--accent-color);
    font-size: 1.1rem;
}

.add-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--accent-color);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.add-btn:hover {
    background-color: #059669;
}

/* CART */
.cart-container {
    background-color: var(--bg-color);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 20px;
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-color);
}

.cart-title {
    font-weight: 600;
    font-size: 1.25rem;
}

.cart-count {
    background-color: var(--page-bg);
    color: var(--text-muted);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
}

.cart-items {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 16px;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-info {
    flex: 1;
}

.cart-item-name {
    font-weight: 600;
    margin-bottom: 4px;
}

.cart-item-price {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.cart-item-quantity {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-btn {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    background-color: var(--page-bg);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.quantity-btn:hover {
    background-color: var(--border-color);
}

.cart-item-subtotal {
    font-weight: 600;
    margin-left: 12px;
    min-width: 80px;
    text-align: right;
}

.cart-remove {
    color: var(--danger-color);
    cursor: pointer;
    margin-left: 8px;
}

.cart-empty {
    text-align: center;
    padding: 20px 0;
    color: var(--text-muted);
}

.cart-summary {
    padding-top: 12px;
    border-top: 1px solid var(--border-color);
}

.cart-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.total-label {
    font-weight: 600;
}

.total-amount {
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--accent-color);
}

.cart-actions {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background-color: var(--accent-color);
    color: white;
}

.btn-primary:hover {
    background-color: #059669;
}

.btn-outline {
    background-color: transparent;
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background-color: var(--page-bg);
}

/* PAYMENT MODAL */
.modal-content {
    background-color: var(--bg-color);
    color: var(--text-color);
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-bottom: 1px solid var(--border-color);
    padding: 16px 20px;
}

.modal-title {
    font-weight: 600;
}

.modal-body {
    padding: 20px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 16px;
}

.payment-label {
    color: var(--text-muted);
}

.payment-value {
    font-weight: 600;
}

.payment-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: var(--bg-color);
    color: var(--text-color);
}

.payment-input:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.modal-footer {
    border-top: 1px solid var(--border-color);
    padding: 16px 20px;
}

/* PAYMENT METHOD SELECTION */
.payment-method-container {
    margin-bottom: 20px;
}

.payment-method-title {
    font-weight: 600;
    margin-bottom: 12px;
}

.payment-method-options {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.payment-method-option {
    flex: 1;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.payment-method-option:hover {
    border-color: var(--accent-color);
}

.payment-method-option.active {
    border-color: var(--accent-color);
    background-color: rgba(16, 185, 129, 0.1);
}

.payment-method-icon {
    font-size: 24px;
    margin-bottom: 8px;
    color: var(--accent-color);
}

.payment-method-name {
    font-weight: 600;
}

/* PAYMENT DETAILS */
.payment-details {
    margin-top: 20px;
    padding: 16px;
    border-radius: 8px;
    background-color: var(--page-bg);
    display: none;
}

.payment-details.active {
    display: block;
}

.qris-container {
    text-align: center;
}

.qris-image {
    max-width: 200px;
    margin: 16px auto;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.qris-instruction {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin-top: 8px;
}

.bank-options {
    margin-bottom: 16px;
}

.bank-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: var(--bg-color);
    color: var(--text-color);
    margin-bottom: 16px;
}

.bank-select:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

/* SUCCESS ALERT */
.success-alert {
    background-color: rgba(34, 197, 94, 0.1);
    color: var(--success-color);
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
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
    z-index: 1000;
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

/* RESPONSIVE */
@media (max-width: 991px) {
    .cart-container {
        position: static;
        margin-top: 24px;
    }
    
    .menu-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 576px) {
    .menu-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }
    
    .search-filter-container {
        flex-direction: column;
    }
    
    .search-input {
        width: 100%;
    }
    
    .payment-method-options {
        flex-direction: column;
    }
}
</style>
<style>
.payment-method-option {
  text-align: center;
  cursor: pointer;
  padding: 15px;
  border-radius: 10px;
  border: 2px solid transparent;
  transition: 0.3s;
}
.payment-method-option:hover {
  border-color: #0d6efd;
  background: #eef5ff;
}
.payment-method-option.active {
  border-color: #0d6efd;
  background: #e0edff;
}
.payment-details { display: none; }
.payment-details.active { display: block; }
</style>

</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline btn-sm me-3 d-lg-none" onclick="window.location.href='pilih_meja.php'">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-utensils me-2"></i> Pecel Lele POS
                </a>
            </div>
            <div class="user-info">
                <div class="d-none d-md-block">
                    <span class="text-muted">Halo, </span>
                    <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                </div>
                <?php if ($meja_id > 0): ?>
                    <div class="table-badge">
                        <i class="fas fa-chair me-1"></i> Meja #<?= $meja_id ?>
                    </div>
                <?php endif; ?>
                <a href="report.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-file-alt"></i>
                    <span class="d-none d-md-inline"> Laporan</span>
                </a>
                <a href="logout.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="d-none d-md-inline"> Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- DARK MODE TOGGLE -->
<div class="dark-toggle" id="darkToggle">
    <div class="circle"></div>
</div>

<!-- MAIN CONTENT -->
<div class="main-container">
    <div class="row">
        <!-- MENU SECTION -->
        <div class="col-lg-8">
            <div class="page-header">
                <h1 class="page-title">Menu</h1>
                <p class="page-subtitle">Pilih menu yang ingin dipesan</p>
            </div>

            <div class="search-filter-container">
                <input type="text" class="search-input" id="searchInput" placeholder="Cari menu...">
                <div class="category-dropdown">
                    <button class="category-btn" id="categoryBtn">
                        <i class="fas fa-filter"></i>
                        <span id="categoryText">Semua Kategori</span>
                        <i class="fas fa-chevron-down ms-auto"></i>
                    </button>
                    <div class="category-menu" id="categoryMenu">
                        <div class="category-item active" data-cat="all">Semua</div>
                        <?php foreach ($categories as $cat): ?>
                            <div class="category-item" data-cat="<?= htmlspecialchars($cat) ?>">
                                <?= htmlspecialchars($cat) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="menu-grid" id="menuGrid">
                <?php foreach ($menus as $m): ?>
                    <div class="menu-item" 
                         data-name="<?= htmlspecialchars(strtolower($m['name'])) ?>" 
                         data-category="<?= htmlspecialchars($m['category']) ?>">
                        <div class="menu-card" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-price="<?= $m['price'] ?>">
                            <?php if (!empty($m['image_url'])): ?>
                                <img src="<?= htmlspecialchars($m['image_url']) ?>" alt="<?= htmlspecialchars($m['name']) ?>" class="menu-image">
                            <?php else: ?>
                                <div class="menu-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-utensils fa-2x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="menu-content">
                                <h5 class="menu-name"><?= htmlspecialchars($m['name']) ?></h5>
                                <div class="menu-category"><?= htmlspecialchars($m['category'] ?: 'Tidak ada kategori') ?></div>
                                <div class="menu-footer">
                                    <div class="menu-price">Rp <?= number_format($m['price'], 0, ",", ".") ?></div>
                                    <button class="add-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CART SECTION -->
        <div class="col-lg-4">
            <div class="cart-container">
                <div class="cart-header">
                    <h3 class="cart-title">Keranjang</h3>
                    <div class="cart-count" id="cartCount">0 item</div>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="success-alert">
                        <i class="fas fa-check-circle"></i>
                        <span><?= $success ?></span>
                        <?php if ($last_sale_id): ?>
                            <a href="cetak_struk.php?id=<?= $last_sale_id ?>" target="_blank" class="btn btn-sm btn-warning ms-auto">
                                <i class="fas fa-print"></i> Cetak Struk
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="cart-items" id="cartItems">
                    <div class="cart-empty">
                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                        <p>Belum ada item</p>
                    </div>
                </div>

                <div class="cart-summary">
                    <div class="cart-total">
                        <div class="total-label">Total</div>
                        <div class="total-amount">Rp <span id="grandTotal">0</span></div>
                    </div>
                    <div class="cart-actions">
                        <button class="btn btn-primary flex-fill" id="openPayBtn" data-bs-toggle="modal" data-bs-target="#payModal">
                            <i class="fas fa-money-bill-wave"></i> Bayar
                        </button>
                        <button class="btn btn-outline" id="clearCart">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-4">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="payModalLabel">Pembayaran</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="payment-row mb-3">
          <div class="fw-bold">Total yang harus dibayar:</div>
          <div class="fs-4 text-success">Rp <span id="payTotal">0</span></div>
        </div>

        <!-- Pilihan Metode Pembayaran -->
        <div class="payment-method-container mb-4">
          <div class="payment-method-title fw-semibold mb-2">Pilih Metode Pembayaran</div>
          <div class="d-flex justify-content-around">
            <div class="payment-method-option active" data-method="cash">
              <i class="fas fa-money-bill-wave fa-2x text-success"></i>
              <div class="mt-2">Tunai</div>
            </div>
            <div class="payment-method-option" data-method="xendit">
              <i class="fas fa-credit-card fa-2x text-primary"></i>
              <div class="mt-2">Bayar via Xendit</div>
            </div>
          </div>
        </div>

        <!-- Tunai -->
        <div class="payment-details active" id="cashDetails">
          <div class="mb-3">
            <label for="cashInput" class="form-label">Tunai diterima</label>
            <input type="number" min="0" class="form-control" id="cashInput" placeholder="Masukkan jumlah tunai">
          </div>
          <div class="payment-row">
            <div class="fw-semibold">Kembalian:</div>
            <div class="fs-5 text-danger">Rp <span id="changeOutput">0</span></div>
          </div>
        </div>

        <!-- Xendit -->
        <div class="payment-details" id="xenditDetails">
          <p class="text-muted">Klik tombol di bawah untuk membayar melalui Xendit.</p>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="confirmPayBtn">Konfirmasi & Bayar</button>
      </div>
    </div>
  </div>
</div>

<!-- FORM -->
<form method="post" id="cartForm" class="d-none">
  <input type="hidden" name="bayar" value="1">
  <input type="hidden" name="payment_method" id="paymentMethodInput" value="cash">
</form>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const options = document.querySelectorAll('.payment-method-option');
  const paymentMethodInput = document.getElementById('paymentMethodInput');
  const cashDetails = document.getElementById('cashDetails');
  const xenditDetails = document.getElementById('xenditDetails');
  const cashInput = document.getElementById('cashInput');
  const payTotal = document.getElementById('payTotal');
  const changeOutput = document.getElementById('changeOutput');
  const confirmPayBtn = document.getElementById('confirmPayBtn');

  // Contoh total bayar (nanti bisa diganti dari PHP)
  let totalBayar = 50000;
  payTotal.textContent = totalBayar.toLocaleString('id-ID');

  options.forEach(opt => {
    opt.addEventListener('click', () => {
      options.forEach(o => o.classList.remove('active'));
      opt.classList.add('active');
      const method = opt.dataset.method;
      paymentMethodInput.value = method;

      cashDetails.classList.toggle('active', method === 'cash');
      xenditDetails.classList.toggle('active', method === 'xendit');
    });
  });

  cashInput.addEventListener('input', () => {
    const tunai = parseFloat(cashInput.value) || 0;
    const kembalian = tunai - totalBayar;
    changeOutput.textContent = (kembalian > 0 ? kembalian : 0).toLocaleString('id-ID');
  });

  confirmPayBtn.addEventListener('click', async () => {
    const method = paymentMethodInput.value;

    if (method === 'cash') {
      alert('Pembayaran tunai berhasil!');
      document.getElementById('cartForm').submit();
    } else if (method === 'xendit') {
      // Arahkan ke proses PHP Xendit
      window.location.href = 'xendit_payment.php?amount=' + totalBayar;
    }
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========================
    // 🌙 DARK MODE TOGGLE
    // ========================
    const darkToggle = document.getElementById('darkToggle');
    const body = document.body;
    
    if (localStorage.getItem('dark-mode') === 'true') {
        body.classList.add('dark');
    }
    
    darkToggle.addEventListener('click', () => {
        body.classList.toggle('dark');
        localStorage.setItem('dark-mode', body.classList.contains('dark'));
    });

    // ========================
    // 📂 CATEGORY DROPDOWN
    // ========================
    const categoryBtn = document.getElementById('categoryBtn');
    const categoryMenu = document.getElementById('categoryMenu');
    const categoryText = document.getElementById('categoryText');
    
    categoryBtn.addEventListener('click', () => {
        categoryMenu.classList.toggle('show');
    });
    
    document.addEventListener('click', (e) => {
        if (!categoryBtn.contains(e.target) && !categoryMenu.contains(e.target)) {
            categoryMenu.classList.remove('show');
        }
    });
    
    // ========================
    // 🧩 CATEGORY FILTER
    // ========================
    const categoryItems = document.querySelectorAll('.category-item');

    categoryItems.forEach(item => {
        item.addEventListener('click', () => {
            const cat = item.getAttribute('data-cat');
            
            categoryItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            
            categoryText.textContent = cat === 'all' ? 'Semua Kategori' : item.textContent;
            
            document.querySelectorAll('.menu-item').forEach(i => {
                i.style.display = (cat === 'all' || i.getAttribute('data-category') === cat)
                    ? 'block'
                    : 'none';
            });
            
            categoryMenu.classList.remove('show');
        });
    });
    
    // ========================
    // 💳 PAYMENT METHOD SELECTION
    // ========================
    const paymentMethodOptions = document.querySelectorAll('.payment-method-option');
    const cashDetails = document.getElementById('cashDetails');
    const qrisDetails = document.getElementById('qrisDetails');
    const transferDetails = document.getElementById('transferDetails');
    const paymentMethodInput = document.getElementById('paymentMethodInput');
    
    paymentMethodOptions.forEach(option => {
        option.addEventListener('click', () => {
            const method = option.getAttribute('data-method');
            
            // Update active state
            paymentMethodOptions.forEach(opt => opt.classList.remove('active'));
            option.classList.add('active');
            
            // Hide all payment details
            cashDetails.classList.remove('active');
            qrisDetails.classList.remove('active');
            transferDetails.classList.remove('active');
            
            // Show relevant payment details
            if (method === 'cash') {
                cashDetails.classList.add('active');
                paymentMethodInput.value = 'cash';
            } else if (method === 'qris') {
                qrisDetails.classList.add('active');
                paymentMethodInput.value = 'qris';
            } else if (method === 'transfer') {
                transferDetails.classList.add('active');
                // MODIFIKASI: Set nilai awal 'transfer', akan diupdate saat bank dipilih
                paymentMethodInput.value = 'transfer'; 
            }
        });
    });
});


// ========================
// 🛒 CART FUNCTIONALITY
// ========================
let cart = {};

function formatIDR(num) {
    return Number(num).toLocaleString('id-ID');
}

function renderCart() {
    let $c = $('#cartItems');
    $c.empty();

    let total = 0;
    let count = 0;

    for (let id in cart) {
        const i = cart[id];
        const sub = i.price * i.qty;
        total += sub;
        count += i.qty;

        $c.append(`
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${i.name}</div>
                    <div class="cart-item-price">Rp ${formatIDR(i.price)}</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn btn-decrease" data-id="${id}">-</button>
                    <span>${i.qty}</span>
                    <button class="quantity-btn btn-increase" data-id="${id}">+</button>
                </div>
                <div class="cart-item-subtotal">Rp ${formatIDR(sub)}</div>
                <i class="fas fa-times cart-remove btn-remove" data-id="${id}"></i>
            </div>
        `);
    }

    if (count === 0) {
        $c.html(`
            <div class="cart-empty">
                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                <p>Belum ada item</p>
            </div>
        `);
    }

    $('#grandTotal').text(formatIDR(total));
    $('#payTotal').text(formatIDR(total));
    $('#cartCount').text(count + ' item');

    const $f = $('#cartForm');
    $f.empty();

    for (let id in cart) {
        $('<input>').attr({ type: 'hidden', name: 'menu_id[]', value: id }).appendTo($f);
        $('<input>').attr({ type: 'hidden', name: 'qty[]', value: cart[id].qty }).appendTo($f);
    }

    $('<input>').attr({ type: 'hidden', name: 'bayar', value: '1' }).appendTo($f);
    $('<input>').attr({ type: 'hidden', name: 'payment_method', id: 'paymentMethodInput', value: 'cash' }).appendTo($f);
    $('<input>').attr({ type: 'hidden', name: 'bank_name', id: 'bankNameInput', value: '' }).appendTo($f);
    $('<input>').attr({ type: 'hidden', name: 'account_number', id: 'accountNumberInput', value: '' }).appendTo($f);
}


// ========================
// ⚙️ EVENT HANDLERS (JQUERY)
// ========================
 $(function() {

    // Tambah item ke keranjang
    $('.add-btn, .menu-card').on('click', function() {
        const c = $(this).closest('.menu-card');
        const id = c.data('id');
        const name = c.data('name');
        const price = parseFloat(c.data('price'));

        if (!cart[id]) cart[id] = { id, name, price, qty: 1 };
        else cart[id].qty++;

        renderCart();
    });

    // Tombol tambah jumlah
    $(document).on('click', '.btn-increase', function() {
        const id = $(this).data('id');
        cart[id].qty++;
        renderCart();
    });

    // Tombol kurangi jumlah
    $(document).on('click', '.btn-decrease', function() {
        const id = $(this).data('id');
        cart[id].qty--;
        if (cart[id].qty <= 0) delete cart[id];
        renderCart();
    });

    // Hapus item dari keranjang
    $(document).on('click', '.btn-remove', function() {
        delete cart[$(this).data('id')];
        renderCart();
    });

    // Kosongkan keranjang
    $('#clearCart').on('click', function() {
        if (confirm('Kosongkan keranjang?')) {
            cart = {};
            renderCart();
        }
    });

    // Pencarian menu
    $('#searchInput').on('input', function() {
        const q = $(this).val().trim().toLowerCase();
        $('.menu-item').each(function() {
            const n = $(this).data('name').toLowerCase();
            $(this).toggle(n.includes(q));
        });
    });

    // Hitung kembalian otomatis
    $('#cashInput').on('input', function() {
        const cash = parseFloat($(this).val()) || 0;
        const total = parseFloat($('#grandTotal').text().replace(/\./g, '')) || 0;
        const change = cash - total > 0 ? cash - total : 0;
        $('#changeOutput').text(formatIDR(change));
    });

    // MODIFIKASI: Update payment method saat bank dipilih
    $('#bankSelect').on('change', function() {
        const selectedBank = $(this).val();
        $('#bankNameInput').val(selectedBank); // Untuk form submission
        
        // Ini adalah kunci perubahan: update payment method utama
        if (selectedBank) {
            $('#paymentMethodInput').val(selectedBank); // e.g., 'bca'
        } else {
            $('#paymentMethodInput').val('transfer'); // Kembali ke default jika tidak ada yang dipilih
        }
    });

    // Update account number when input changes
    $('#accountNumber').on('input', function() {
        $('#accountNumberInput').val($(this).val());
    });

    renderCart();
});
</script>
</body>
</html>