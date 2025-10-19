<?php
error_reporting(0);
header("Content-Type: application/json");
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
file_put_contents(__DIR__ . "/xendit_log.txt", date("Y-m-d H:i:s") . " - " . $raw . "\n", FILE_APPEND);

if (!$data || !isset($data['id']) || !isset($data['status']) || !isset($data['external_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload"]);
    exit;
}

$xendit_invoice_id = $data['id'];
$external_id = $data['external_id'];
$xendit_status = strtoupper($data['status']);

include 'db.php';

// Update data berdasarkan external_id
$stmt = $conn->prepare("UPDATE sales SET xendit_invoice_id = ?, xendit_status = ? WHERE xendit_invoice_id = ? OR xendit_invoice_id = ?");
$stmt->bind_param("ssss", $xendit_invoice_id, $xendit_status, $xendit_invoice_id, $external_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    http_response_code(200);
    echo json_encode(["message" => "Status updated", "status" => $xendit_status]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Invoice not found"]);
}

$stmt->close();
$conn->close();
?>
