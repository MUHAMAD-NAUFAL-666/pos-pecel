<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Verifikasi Wajah Kasir - POS Pecel Lele</title>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<style>
body {
  background: linear-gradient(135deg, #00b09b, #96c93d);
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
  margin-bottom: 10px;
}
video {
  border: 3px solid white;
  border-radius: 10px;
  margin-top: 10px;
  display: none;
}
button {
  background-color: #fff;
  color: #00b09b;
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
<h2>👀 Verifikasi Wajah Kasir</h2>
<p id="status">Klik tombol di bawah untuk mengaktifkan kamera.</p>
<button id="btnStart">🎥 Aktifkan Kamera</button>
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
      // ✅ Muat model FaceAPI
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('models')
      ]);

      // ✅ Aktifkan kamera
      const stream = await navigator.mediaDevices.getUserMedia({ video: true });
      video.srcObject = stream;
      video.style.display = "block";
      btnStart.style.display = "none";
      statusText.textContent = "📸 Arahkan wajah ke kamera...";

      // ✅ Mulai pengenalan wajah
      startRecognition();
    } catch (err) {
      console.error(err);
      statusText.textContent = "❌ Tidak bisa mengakses kamera: " + err.message;
      btnStart.disabled = false;
    }
  });

  async function startRecognition() {
    const userId = <?php echo json_encode($user_id); ?>;

    // 🔹 Ambil data wajah dari server
    const response = await fetch(`get_face_data.php?user_id=${userId}`);
    const labeledDescriptor = await response.json();

    if (!labeledDescriptor || !labeledDescriptor.label) {
      alert("❌ Data wajah belum terdaftar untuk akun ini!");
      statusText.textContent = "❌ Data wajah belum terdaftar.";
      return;
    }

    // ✅ Pastikan label berupa string
    const labelName = String(labeledDescriptor.label);

    // ✅ Buat LabeledFaceDescriptors
    const labeledFace = new faceapi.LabeledFaceDescriptors(
      labelName,
      [new Float32Array(labeledDescriptor.descriptor)]
    );

    const faceMatcher = new faceapi.FaceMatcher(labeledFace, 0.6);
    statusText.textContent = "🔍 Mendeteksi wajah...";

    const interval = setInterval(async () => {
      const detections = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (detections) {
        const bestMatch = faceMatcher.findBestMatch(detections.descriptor);

        if (bestMatch.label === labelName) {
          clearInterval(interval);
          statusText.textContent = "✅ Wajah dikenali! Login berhasil.";
          video.pause();

          // ✅ Alert sukses
          alert("✅ Wajah cocok! Login berhasil.");

          // ✅ Simpan log ke database
          fetch("save_face.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              user_id: userId,
              status: "berhasil"
            })
          })
          .then(res => res.text())
          .then(data => console.log("Server:", data))
          .catch(err => console.error(err));

          // ✅ Redirect ke dashboard
          setTimeout(() => window.location.href = "pilih_meja.php", 1000);
        } else {
          statusText.textContent = "❌ Wajah tidak cocok!";
        }
      }
    }, 1000);
  }
});
</script>
</body>
</html>
