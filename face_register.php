<?php
session_start();
include 'db.php';

if (!isset($_GET['user_id'])) {
  header("Location: register.php");
  exit;
}

$user_id = intval($_GET['user_id']);

// ambil data user
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
  die("User tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $imageData = $_POST['image'] ?? '';

  if ($imageData) {
    // simpan ke kolom face_image di tabel users
    $upd = $conn->prepare("UPDATE users SET face_image = ? WHERE id = ?");
    $upd->bind_param("si", $imageData, $user_id);
    $upd->execute();

    // simpan ke tabel face_data
    $label = $user['username'];
    $descriptor = $imageData; // sementara simpan base64 (bisa diubah ke vector nanti)
    $ins = $conn->prepare("INSERT INTO face_data (user_id, label, descriptor, created_at) VALUES (?, ?, ?, NOW())");
    $ins->bind_param("iss", $user_id, $label, $descriptor);
    $ins->execute();

    $success = "✅ Wajah berhasil direkam! Silakan login.";
  } else {
    $error = "❌ Gagal merekam wajah.";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Registrasi Wajah Kasir</title>
  <script defer src="face-api.min.js"></script>
  <style>
    * {box-sizing: border-box; font-family: 'Poppins', sans-serif;}
    body {
      background: linear-gradient(135deg, #00b894, #00cec9);
      display: flex; justify-content: center; align-items: center;
      height: 100vh; margin: 0;
    }
    .card {
      background: #fff; padding: 30px; border-radius: 20px;
      width: 400px; text-align: center;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      animation: fadeIn 0.8s ease;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(10px);}
      to {opacity: 1; transform: translateY(0);}
    }
    video {
      border-radius: 12px;
      width: 100%;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      margin: 15px 0;
    }
    button {
      background: #0984e3;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 15px;
      transition: all 0.3s ease;
      width: 100%;
    }
    button:hover {background: #0766b1;}
    .success, .error {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 10px;
    }
    .success {background: #d4edda; color: #155724;}
    .error {background: #f8d7da; color: #721c24;}
  </style>
</head>
<body>
  <div class="card">
    <h3>📸 Registrasi Wajah - <?= htmlspecialchars($user['username']); ?></h3>
    <?php if(!empty($success)): ?><div class="success"><?= $success ?></div><?php endif; ?>
    <?php if(!empty($error)): ?><div class="error"><?= $error ?></div><?php endif; ?>

    <form method="post" id="formFace">
      <video id="video" autoplay muted></video>
      <button type="button" id="capture">Ambil Foto Wajah</button>
      <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>
      <input type="hidden" name="image" id="image">
      <button type="submit" class="mt-3" style="background:#00b894;">Simpan Wajah</button>
    </form>

    <p style="font-size:13px;color:#636e72;margin-top:10px;">
      Pastikan wajahmu terlihat jelas di kamera.
    </p>
    <a href="index.php" style="display:block;margin-top:10px;text-decoration:none;color:#0984e3;">⬅ Kembali ke Login</a>
  </div>

  <script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const capture = document.getElementById('capture');
    const imageInput = document.getElementById('image');

    navigator.mediaDevices.getUserMedia({ video: true })
      .then(stream => video.srcObject = stream)
      .catch(err => alert("❌ Gagal mengakses kamera: " + err));

    capture.addEventListener('click', () => {
      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const dataURL = canvas.toDataURL('image/png');
      imageInput.value = dataURL;

      capture.textContent = "✅ Wajah Tersimpan";
      capture.style.background = "#00b894";
      capture.disabled = true;

      alert("✅ Wajah berhasil diambil, klik 'Simpan Wajah' untuk menyimpan.");
    });
  </script>
</body>
</html>
