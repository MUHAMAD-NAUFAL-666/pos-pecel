<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftarkan Wajah Kasir</title>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<style>
body {
  background: linear-gradient(135deg, #56ab2f, #a8e063);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  color: white;
  text-align: center;
  font-family: 'Poppins', sans-serif;
}
h2 {
  margin-bottom: 10px;
}
#status {
  font-size: 16px;
  margin-bottom: 15px;
}
video {
  border: 3px solid white;
  border-radius: 10px;
  margin-top: 10px;
  display: none;
}
button {
  background-color: #fff;
  color: #56ab2f;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}
button:hover {
  background-color: #f2f2f2;
  transform: scale(1.05);
}
</style>
</head>
<body>
<h2>📸 Daftarkan Wajah Kasir</h2>
<p id="status">Klik tombol di bawah untuk mulai merekam wajah Anda.</p>
<button id="btnStart">🎥 Mulai Perekaman</button>
<video id="video" width="320" height="240" autoplay muted></video>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const video = document.getElementById('video');
  const statusText = document.getElementById('status');
  const btnStart = document.getElementById('btnStart');

  btnStart.addEventListener('click', async () => {
    btnStart.disabled = true;
    btnStart.textContent = "Memuat model...";
    statusText.textContent = "⏳ Memuat model deteksi wajah...";

    try {
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('models')
      ]);

      const stream = await navigator.mediaDevices.getUserMedia({ video: true });
      video.srcObject = stream;
      video.style.display = "block";
      btnStart.style.display = "none";
      statusText.textContent = "🧠 Arahkan wajah ke kamera...";

      startTraining();
    } catch (err) {
      console.error(err);
      statusText.textContent = "❌ Tidak bisa mengakses kamera: " + err.message;
      btnStart.disabled = false;
    }
  });

  async function startTraining() {
    const userId = <?php echo json_encode($user_id); ?>;
    const username = <?php echo json_encode($username); ?>;

    let descriptorCollected = null;

    const interval = setInterval(async () => {
      const detection = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (detection) {
        descriptorCollected = Array.from(detection.descriptor);
        clearInterval(interval);
        video.pause();

        statusText.textContent = "✅ Wajah berhasil terekam!";
        alert("✅ Wajah berhasil didaftarkan!");

        // Kirim ke server untuk disimpan
        fetch("save_train_face.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            user_id: userId,
            username: username,
            descriptor: descriptorCollected
          })
        })
        .then(res => res.text())
        .then(data => {
          console.log("Server:", data);
          statusText.textContent = "✅ Data wajah tersimpan!";
          setTimeout(() => window.location.href = "face_recog.php", 1500);
        })
        .catch(err => {
          console.error(err);
          statusText.textContent = "❌ Gagal menyimpan ke server!";
        });
      } else {
        statusText.textContent = "📷 Wajah belum terdeteksi, coba dekatkan ke kamera...";
      }
    }, 1000);
  }
});
</script>
</body>
</html>
