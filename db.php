<?php

$host="localhost";
$user="root";
$pass="root";
$db="pos-pecellele";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
?>