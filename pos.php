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

    // Simpan sales
    $ins = $conn->prepare("INSERT INTO sales (user_id, total) VALUES (?, ?)");
    $ins->bind_param("id", $user_id, $total);
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
<meta charset="utf-8">
<title>POS - Pecel Lele</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f5f7fb; }
.navbar-brand { font-weight:700; }
.menu-card {
  cursor:pointer;
  padding:12px;
  border-radius:12px;
  border:1px solid rgba(0,0,0,0.06);
  background:linear-gradient(180deg,#ffffff,#fbfcff);
  box-shadow: 0 6px 12px rgba(18,38,63,0.04);
  transition: transform .12s ease, box-shadow .12s ease;
  min-height:110px;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
}
.menu-card img {
  width:100%;
  height:100px;
  object-fit:cover;
  border-radius:8px;
}
.menu-card:hover {
  transform:translateY(-6px);
  box-shadow:0 12px 20px rgba(18,38,63,0.08);
}
.price-badge { font-weight:700; color:#198754; }
.cart-sidebar { position:sticky; top:20px; }
.small-muted { color:#6c757d; font-size:0.9rem; }
.empty-state { color:#6c757d; }
@media (max-width:991px){ .cart-sidebar{ position:static; margin-top:18px; } }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-white bg-white shadow-sm mb-3">
  <div class="container-fluid">
    <div class="mb-3 d-block d-lg-none">
      <button class="btn btn-outline-secondary btn-sm" onclick="window.location.href='pilih_meja.php'">
        <i class="bi bi-arrow-left"></i> Kembali
      </button>
    </div>
    <a class="navbar-brand" href="dashboard.php">🍽️ Pecel Lele POS</a>
    <div class="d-flex align-items-center gap-2">
      <div class="small-muted me-3">Halo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
      <?php if ($meja_id > 0): ?>
        <span class="badge bg-success">Meja #<?= $meja_id ?></span>
      <?php endif; ?>
      <a href="report.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-text"></i> Laporan</a>
      <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row gx-4">
    <!-- MENU -->
    <div class="col-lg-8">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Menu</h5>
        <div class="d-flex gap-2">
          <input id="searchInput" class="form-control form-control-sm" style="min-width:220px" placeholder="Cari menu...">
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-funnel"></i> Kategori
            </button>
            <ul class="dropdown-menu p-2" style="min-width:240px;">
              <button class="btn btn-sm btn-outline-secondary w-100 mb-2 category-filter" data-cat="all">Semua</button>
              <?php foreach ($categories as $cat): ?>
                <button class="btn btn-sm btn-outline-secondary w-100 mb-2 category-filter" data-cat="<?= htmlspecialchars($cat) ?>">
                  <?= htmlspecialchars($cat) ?>
                </button>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>

      <div id="menuGrid" class="row g-3">
        <?php foreach ($menus as $m): ?>
          <div class="col-6 col-sm-4 col-md-3 menu-item" 
               data-name="<?= htmlspecialchars(strtolower($m['name'])) ?>" 
               data-category="<?= htmlspecialchars($m['category']) ?>">
            <div class="menu-card" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-price="<?= $m['price'] ?>">
              <?php if (!empty($m['image_url'])): ?>
                <img src="<?= htmlspecialchars($m['image_url']) ?>" alt="<?= htmlspecialchars($m['name']) ?>">
              <?php endif; ?>
              <div>
                <h6 class="mb-1"><?= htmlspecialchars($m['name']) ?></h6>
                <div class="small-muted">ID: <?= $m['id'] ?> • <?= htmlspecialchars($m['category'] ?: '-') ?></div>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="price-badge">Rp <?= number_format($m['price'], 0, ",", ".") ?></div>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-secondary btn-add" type="button"><i class="bi bi-plus-lg"></i></button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- KERANJANG -->
    <div class="col-lg-4">
      <div class="cart-sidebar">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Keranjang <small class="small-muted" id="cartCount">0 item</small></h5>

            <?php if (!empty($success)): ?>
              <div class="alert alert-success small mb-3">
                <?= $success ?>
                <?php if ($last_sale_id): ?>
                  <div class="mt-2"><a href="cetak_struk.php?id=<?= $last_sale_id ?>" target="_blank" class="btn btn-sm btn-warning"><i class="bi bi-printer"></i> Cetak Struk</a></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div id="cartItems" style="max-height:340px; overflow:auto;">
              <p class="empty-state">Belum ada item</p>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="small-muted">Total</div>
              <div><strong>Rp <span id="grandTotal">0</span></strong></div>
            </div>
            <div class="d-grid gap-2">
              <button id="openPayBtn" class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-currency-dollar"></i> Bayar</button>
              <button id="clearCart" class="btn btn-outline-secondary" type="button"><i class="bi bi-trash"></i> Kosongkan</button>
            </div>
            <form method="post" id="cartForm" class="d-none">
              <input type="hidden" name="bayar" value="1">
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Bayar -->
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="payModalLabel">Pembayaran</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label small-muted">Total yang harus dibayar</label>
          <div class="h5">Rp <span id="payTotal">0</span></div>
        </div>
        <div class="mb-2">
          <label class="form-label">Tunai diterima</label>
          <input type="number" min="0" step="1" class="form-control" id="cashInput" placeholder="Masukkan jumlah tunai">
        </div>
        <div class="mb-2">
          <label class="form-label small-muted">Kembalian</label>
          <div class="h6">Rp <span id="changeOutput">0</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button id="confirmPayBtn" class="btn btn-primary">Konfirmasi & Bayar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let cart = {};
function formatIDR(num){ return Number(num).toLocaleString('id-ID'); }

function renderCart(){
  let $c = $('#cartItems'); $c.empty();
  let total = 0; let count = 0;
  for (let id in cart){
    const i = cart[id]; const sub = i.price * i.qty; total += sub; count += i.qty;
    $c.append(`<div class="d-flex justify-content-between align-items-center mb-2">
      <div style="flex:1"><strong>${i.name}</strong><br><div class="small-muted">Rp ${formatIDR(i.price)} x ${i.qty}</div></div>
      <div class="text-end ms-2">
        <div class="btn-group btn-group-sm mb-1">
          <button class="btn btn-outline-secondary btn-decrease" data-id="${id}">-</button>
          <button class="btn btn-outline-secondary btn-increase" data-id="${id}">+</button>
        </div>
        <div class="small-muted">Rp ${formatIDR(sub)}</div>
        <div class="mt-1"><button class="btn btn-sm btn-link text-danger btn-remove" data-id="${id}">Hapus</button></div>
      </div>
    </div>`);
  }
  if(count===0){ $c.html('<p class="empty-state">Belum ada item</p>'); }
  $('#grandTotal').text(formatIDR(total)); $('#payTotal').text(formatIDR(total)); $('#cartCount').text(count+' item');
  const $f = $('#cartForm'); $f.empty(); for(let id in cart){ $('<input>').attr({type:'hidden',name:'menu_id[]',value:id}).appendTo($f); $('<input>').attr({type:'hidden',name:'qty[]',value:cart[id].qty}).appendTo($f); }
  $('<input>').attr({type:'hidden',name:'bayar',value:'1'}).appendTo($f);
}

$(function(){
  $('.btn-add, .menu-card').on('click',function(){
    const c=$(this).closest('.menu-card'); const id=c.data('id'),name=c.data('name'),price=parseFloat(c.data('price'));
    if(!cart[id])cart[id]={id,name,price,qty:1}; else cart[id].qty++; renderCart();
  });
  $(document).on('click','.btn-increase',function(){const id=$(this).data('id');cart[id].qty++;renderCart();});
  $(document).on('click','.btn-decrease',function(){const id=$(this).data('id');cart[id].qty--;if(cart[id].qty<=0)delete cart[id];renderCart();});
  $(document).on('click','.btn-remove',function(){delete cart[$(this).data('id')];renderCart();});
  $('#clearCart').on('click',function(){if(confirm('Kosongkan keranjang?')){cart={};renderCart();}});
  $('#searchInput').on('input',function(){const q=$(this).val().trim().toLowerCase();$('.menu-item').each(function(){const n=$(this).data('name');$(this).toggle(n.indexOf(q)!==-1);});});
  $('.category-filter').on('click',function(){const cat=$(this).data('cat');if(cat==='all')$('.menu-item').show();else $('.menu-item').each(function(){$(this).toggle($(this).data('category')===cat);});});
  $('#cashInput').on('input',function(){const cash=parseFloat($(this).val())||0,total=parseFloat($('#grandTotal').text().replace(/\./g,''))||0;$('#changeOutput').text(formatIDR(cash-total>0?cash-total:0));});
  $('#confirmPayBtn').on('click',function(){const total=parseFloat($('#grandTotal').text().replace(/\./g,''))||0;if(total<=0)return alert('Keranjang kosong.');
    const cash=parseFloat($('#cashInput').val())||0;if(cash<total&&!confirm('Tunai kurang, lanjutkan?'))return;$('#cartForm')[0].submit();});
  renderCart();
});
</script>
</body>
</html>
