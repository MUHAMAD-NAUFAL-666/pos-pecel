<?php
include 'db.php';
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["error" => "user_id tidak ditemukan"]);
    exit;
}

// Misal kamu menyimpan data wajah di tabel `face_data`
$query = $conn->prepare("SELECT label, descriptor FROM face_data WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

if ($row = $result->fetch_assoc()) {
    // pastikan descriptor disimpan dalam bentuk JSON string di database
    echo json_encode([
        "label" => $row['label'],
        "descriptor" => json_decode($row['descriptor'])
    ]);
} else {
    echo json_encode(["error" => "Data wajah tidak ditemukan"]);
}
?>
