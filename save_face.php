<?php
include 'db.php';
session_start();
$user_id = $_SESSION['user_id'];

$face_descriptor = $_POST['descriptor']; // array dari face-api.js
$json_desc = json_encode($face_descriptor);

$stmt = $conn->prepare("INSERT INTO session_face (user_id, face_id) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $json_desc);
$stmt->execute();

echo "Face data disimpan.";
?>
