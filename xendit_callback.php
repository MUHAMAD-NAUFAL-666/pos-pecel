<?php
// xendit_callback.php
$data = file_get_contents('php://input');
$logFile = 'xendit_callback_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - $data\n", FILE_APPEND);

// Kamu bisa decode JSON dan update status transaksi di DB
// $json = json_decode($data, true);
?>
