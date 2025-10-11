<?php
include 'db.php';

$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];
$image = $_POST['image'];

if ($image) {
  $imageData = explode(',', $image);
  $decoded = base64_decode($imageData[1]);
  $filename = 'wajah/' . $username . '.png';
  file_put_contents($filename, $decoded);

  $sql = "INSERT INTO pengguna (username, password, role, foto_wajah) VALUES ('$username', '$password', '$role', '$filename')";
  mysqli_query($koneksi, $sql);

  echo "<script>alert('Registrasi berhasil!');window.location='index.php';</script>";
} else {
  echo "<script>alert('Ambil foto wajah terlebih dahulu!');history.back();</script>";
}
?>
