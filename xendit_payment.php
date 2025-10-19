<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

if ($amount <= 0) {
  die("Jumlah pembayaran tidak valid.");
}

$apiKey = "xnd_development_auDG5xL9cnb9wJ40wRRlAPQwuH09zkyUH6J729yFXMUhPF2RLg15AO7WZK"; // SECRET KEY

// 1️⃣ Buat external_id unik untuk invoice
$external_id = "INV-" . time() . "-" . $user_id;

// 2️⃣ Simpan data ke database lebih dulu
$stmt = $conn->prepare("INSERT INTO sales (user_id, total, payment_method, xendit_invoice_id, xendit_status) VALUES (?, ?, 'xendit', ?, 'PENDING')");
$stmt->bind_param("ids", $user_id, $amount, $external_id);
$stmt->execute();
$stmt->close();

// 3️⃣ Buat invoice ke Xendit
$data = [
  "external_id" => $external_id,
  "payer_email" => "customer@example.com",
  "description" => "Pembayaran POS Pecel Lele",
  "amount" => (int)$amount,
  "success_redirect_url" => "http://localhost:8888/pos-pecel/success.php?invoice_id=$external_id",
  "failure_redirect_url" => "http://localhost:8888/pos-pecel/failed.php?invoice_id=$external_id",
  "customer_notification_preference" => [
    "invoice_created" => ["email"],
    "invoice_reminder" => ["email"],
    "invoice_paid" => ["email"],
    "invoice_expired" => ["email"]
  ]
];


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.xendit.co/v2/invoices");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['invoice_url'])) {
  header("Location: " . $result['invoice_url']);
  exit;
} else {
  echo "Gagal membuat invoice Xendit: <br>";
  echo "<pre>" . print_r($result, true) . "</pre>";
}
?>
