<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
include 'db.php';

// Ambil data meja
$result = $conn->query("SELECT * FROM meja ORDER BY nomor ASC");
$meja_list = [];
while ($row = $result->fetch_assoc()) {
  $meja_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Pilih Meja - POS Restoran</title>
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background-color: #f2f4f7;
    margin: 0;
    padding: 0;
  }

  .container {
    text-align: center;
    margin-top: 40px;
  }

  h1 { margin-bottom: 30px; color: #333; }

  .floor-plan {
    display: grid;
    grid-template-columns: repeat(4, 140px);
    justify-content: center;
    gap: 20px;
  }

  .table {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 4px solid transparent;
  }

  .available {
    background-color: #e8fff1;
    border-color: #34c759;
    color: #2b7a0b;
  }

  .occupied {
    background-color: #ffeaea;
    border-color: #ff3b30;
    color: #a30000;
    cursor: not-allowed;
  }

  .selected {
    transform: scale(1.1);
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
  }

  .button-container {
    margin-top: 30px;
  }

  button {
    background-color: #007aff;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
  }

  button:hover { background-color: #005ecb; }

  .occupied-text {
    font-size: 14px;
    font-weight: 500;
    position: absolute;
    bottom: 10px;
    color: #ff3b30;
  }

  .table-container {
    position: relative;
  }
</style>
</head>
<body>
<div class="container">
  <h1>Pilih Meja Anda 🍽️</h1>
  <div class="floor-plan">
    <?php foreach($meja_list as $m): ?>
      <div class="table-container">
        <div class="table <?= $m['status'] ?>" 
             data-id="<?= $m['id'] ?>" 
             data-status="<?= $m['status'] ?>">
          <?= $m['nomor'] ?>
        </div>
        <?php if($m['status'] == 'occupied'): ?>
          <div class="occupied-text">Sudah diisi</div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="button-container">
    <button id="confirmButton" disabled>Konfirmasi Pilihan</button>
  </div>
</div>

<script>
let selectedTable = null;
const tables = document.querySelectorAll('.table');
const confirmButton = document.getElementById('confirmButton');

tables.forEach(table => {
  table.addEventListener('click', () => {
    const status = table.getAttribute('data-status');
    if (status === 'occupied') return;

    tables.forEach(t => t.classList.remove('selected'));
    table.classList.add('selected');
    selectedTable = table.getAttribute('data-id');
    confirmButton.disabled = false;
  });
});

confirmButton.addEventListener('click', () => {
  if (selectedTable) {
    window.location.href = `pos.php?meja=${selectedTable}`;
  }
});
</script>
</body>
</html>
