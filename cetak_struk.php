<?php
include 'db.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo "ID transaksi tidak valid"; exit; }

$sale = $conn->query("SELECT s.*, u.username FROM sales s JOIN users u ON s.user_id=u.id WHERE s.id = $id")->fetch_assoc();
$detailsRes = $conn->query("SELECT sd.*, m.name FROM sale_details sd JOIN menu m ON sd.menu_id=m.id WHERE sd.sale_id = $id");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Struk #<?= $id ?></title>
<style>
  body {
    font-family: monospace;
    width: 58mm;        /* ukuran thermal 58mm */
    margin: 0 auto;
    font-size: 12px;
  }
  .center { text-align: center; }
  .logo { max-height: 50px; margin-bottom: 5px; }
  .qris { max-width: 100%; margin-top: 5px; }
  hr { border: none; border-top: 1px dashed #000; margin: 5px 0; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  td { vertical-align: top; }
  .total { font-weight: bold; font-size: 14px; }
  .footer { margin-top: 8px; }
</style>
</head>
<body onload="window.print()">

<div class="center">
  <img src="img/logo.png" alt="Logo" class="logo"><br>
  <strong>Pecel Lele Depot Lusi</strong><br>
  Jl. DewiSartika, Samping Toko Roti Dewi<br>
  Karawang, Jawa Barat<br>
  Telp: 0815-7363-5413
  <hr>
  <div><?= date("d/m/Y H:i", strtotime($sale['created_at'])) ?></div>
  <div>Kasir: <?= htmlspecialchars($sale['username']) ?></div>
  <div>No. Transaksi: #<?= $id ?></div>
  <hr>
</div>

<table>
<?php while($d = $detailsRes->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($d['name']) ?> x<?= $d['qty'] ?></td>
  <td style="text-align:right">Rp <?= number_format($d['subtotal'],0,",",".") ?></td>
</tr>
<?php endwhile; ?>
</table>

<hr>
<div class="center total">
  Total: Rp <?= number_format($sale['total'],0,",",".") ?>
</div>
<hr>

<div class="center footer">
  <p>*** Terima Kasih ***<br>
  Semoga Hari Anda Menyenangkan</p>
  <small>Barang yang sudah dibeli tidak dapat dikembalikan</small>
  <br><br>
  <strong>Bayar via QRIS:</strong><br>
  <img src="img/qriss.jpeg" alt="QRIS" class="qris">
</div>

</body>
</html>
