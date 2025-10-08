<?php
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['user_id']) || !isset($data['descriptor'])) {
    echo "❌ Data tidak lengkap!";
    exit;
}

$user_id = $data['user_id'];
$username = $data['username'];
$descriptor = json_encode($data['descriptor']); // Simpan sebagai JSON

// Pastikan tabel ada (contoh)
$createTable = "CREATE TABLE IF NOT EXISTS face_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label VARCHAR(100) NOT NULL,
    descriptor JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTable);

// Simpan data wajah
$stmt = $conn->prepare("REPLACE INTO face_data (user_id, label, descriptor) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $username, $descriptor);

if ($stmt->execute()) {
    echo "✅ Wajah berhasil disimpan.";
} else {
    echo "❌ Gagal menyimpan data wajah: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
