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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verifikasi Wajah Kasir - POS Pecel Lele</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<style>
:root {
    --bg-color: #ffffff;
    --page-bg: #f7f8fa;
    --text-color: #1a1a1a;
    --text-muted: #6c757d;
    --accent-color: #10b981;
    --border-color: #e5e7eb;
    --error-color: #ef4444;
    --success-color: #22c55e;
    --warning-color: #f59e0b;
}

/* DARK MODE */
.dark {
    --bg-color: #1f2937;
    --page-bg: #111827;
    --text-color: #f3f4f6;
    --text-muted: #9ca3af;
    --border-color: #374151;
}

body {
    font-family: 'Inter', sans-serif;
    background-color: var(--page-bg);
    color: var(--text-color);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.recognition-container {
    width: 100%;
    max-width: 500px;
}

.recognition-card {
    background-color: var(--bg-color);
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    text-align: center;
    animation: fadeIn 0.7s ease-out forwards;
    transition: background-color 0.3s ease, color 0.3s ease;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}

.logo-header {
    margin-bottom: 30px;
}

.logo-header img {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
}

.logo-header h1 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.logo-header p {
    color: var(--text-muted);
    font-size: 16px;
}

.video-container {
    position: relative;
    margin: 25px 0;
    border-radius: 12px;
    overflow: hidden;
    background-color: #000;
    display: none;
}

.video-container.active {
    display: block;
}

video {
    width: 100%;
    height: auto;
    border-radius: 12px;
    transform: scaleX(-1);
}

.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.face-frame {
    width: 200px;
    height: 200px;
    border: 3px solid var(--accent-color);
    border-radius: 50%;
    position: relative;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);}
    70% {box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);}
    100% {box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);}
}

.status-container {
    margin: 20px 0;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-message {
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 8px;
    background-color: var(--page-bg);
    transition: all 0.3s ease;
}

.status-message.success {
    background-color: rgba(34, 197, 94, 0.1);
    color: var(--success-color);
}

.status-message.error {
    background-color: rgba(239, 68, 68, 0.1);
    color: var(--error-color);
}

.status-message.warning {
    background-color: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
}

.btn-start {
    width: 100%;
    padding: 14px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    color: #fff;
    background-color: var(--accent-color);
    cursor: pointer;
    transition: transform 0.1s, background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-start:hover {
    background-color: #059669;
    transform: translateY(-1px);
}

.btn-start:disabled {
    background-color: var(--text-muted);
    cursor: not-allowed;
    transform: none;
}

.btn-start .spinner-border {
    width: 18px;
    height: 18px;
    display: none;
}

.btn-start.loading .spinner-border {
    display: inline-block;
}

.btn-start.loading .btn-text {
    display: none;
}

/* DARK MODE BUTTON */
.dark-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 30px;
    width: 60px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 4px;
    transition: background 0.3s, border-color 0.3s;
}

.dark-toggle .circle {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--accent-color);
    transition: transform 0.3s ease;
}

.dark .dark-toggle .circle {
    transform: translateX(30px);
}

.progress-container {
    margin: 20px 0;
    display: none;
}

.progress-container.active {
    display: block;
}

.progress {
    height: 6px;
    background-color: var(--border-color);
    border-radius: 3px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background-color: var(--accent-color);
    width: 0%;
    transition: width 0.3s ease;
}

.instructions {
    margin-top: 20px;
    padding: 15px;
    background-color: var(--page-bg);
    border-radius: 8px;
    font-size: 14px;
    color: var(--text-muted);
}

.instructions ul {
    text-align: left;
    padding-left: 20px;
    margin-bottom: 0;
}

.instructions li {
    margin-bottom: 5px;
}
</style>
</head>
<body>

<div class="dark-toggle" id="darkToggle">
    <div class="circle"></div>
</div>

<div class="recognition-container">
    <div class="recognition-card">
        <div class="logo-header">
            <img src="img/logo.png" alt="Logo POS Pecel Lele">
            <h1>Verifikasi Wajah</h1>
            <p>Silakan arahkan wajah Anda ke kamera untuk verifikasi</p>
        </div>

        <div class="video-container" id="videoContainer">
            <video id="video" autoplay muted></video>
            <div class="video-overlay">
                <div class="face-frame"></div>
            </div>
        </div>

        <div class="status-container">
            <div id="statusMessage" class="status-message">
                <i class="fas fa-info-circle"></i>
                <span>Klik tombol di bawah untuk memulai verifikasi</span>
            </div>
        </div>

        <div class="progress-container" id="progressContainer">
            <div class="progress">
                <div class="progress-bar" id="progressBar"></div>
            </div>
        </div>

        <button id="btnStart" class="btn-start">
            <i class="fas fa-camera"></i>
            <span class="btn-text">Aktifkan Kamera</span>
            <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
        </button>

        <div class="instructions">
            <p class="mb-2"><strong>Petunjuk:</strong></p>
            <ul>
                <li>Pastikan wajah Anda terlihat jelas di kamera</li>
                <li>Berada dalam kondisi pencahayaan yang baik</li>
                <li>Hindari penggunaan topeng atau aksesoris yang menutupi wajah</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const video = document.getElementById('video');
    const videoContainer = document.getElementById('videoContainer');
    const statusMessage = document.getElementById('statusMessage');
    const btnStart = document.getElementById('btnStart');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    
    // Dark mode toggle
    const darkToggle = document.getElementById('darkToggle');
    const body = document.body;
    
    // Cek preferensi dark mode dari localStorage
    if (localStorage.getItem('dark-mode') === 'true') {
        body.classList.add('dark');
    }
    
    // Toggle dark mode
    darkToggle.addEventListener('click', () => {
        body.classList.toggle('dark');
        localStorage.setItem('dark-mode', body.classList.contains('dark'));
    });

    // Update status message helper function
    function updateStatus(message, type = 'info') {
        statusMessage.className = 'status-message';
        if (type === 'success') statusMessage.classList.add('success');
        if (type === 'error') statusMessage.classList.add('error');
        if (type === 'warning') statusMessage.classList.add('warning');
        
        let icon = 'fa-info-circle';
        if (type === 'success') icon = 'fa-check-circle';
        if (type === 'error') icon = 'fa-exclamation-circle';
        if (type === 'warning') icon = 'fa-exclamation-triangle';
        
        statusMessage.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
    }
    
    // Update progress bar
    function updateProgress(percent) {
        progressBar.style.width = `${percent}%`;
    }

    btnStart.addEventListener('click', async () => {
        btnStart.disabled = true;
        btnStart.classList.add('loading');
        progressContainer.classList.add('active');
        updateProgress(10);
        updateStatus("Memuat model deteksi wajah...", "warning");

        try {
            // Muat model FaceAPI
            updateProgress(30);
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri('models'),
                faceapi.nets.faceLandmark68Net.loadFromUri('models'),
                faceapi.nets.faceRecognitionNet.loadFromUri('models')
            ]);
            
            updateProgress(60);
            updateStatus("Mengaktifkan kamera...", "warning");

            // Aktifkan kamera
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            videoContainer.classList.add('active');
            btnStart.style.display = 'none';
            updateProgress(80);
            updateStatus("Arahkan wajah ke kamera...", "warning");
            
            updateProgress(100);
            setTimeout(() => {
                progressContainer.classList.remove('active');
                updateProgress(0);
            }, 500);

            // Mulai pengenalan wajah
            startRecognition();
        } catch (err) {
            console.error(err);
            updateStatus("Tidak bisa mengakses kamera: " + err.message, "error");
            btnStart.disabled = false;
            btnStart.classList.remove('loading');
            progressContainer.classList.remove('active');
        }
    });

    async function startRecognition() {
        const userId = <?php echo json_encode($user_id); ?>;

        // Ambil data wajah dari server
        try {
            const response = await fetch(`get_face_data.php?user_id=${userId}`);
            const labeledDescriptor = await response.json();

            if (!labeledDescriptor || !labeledDescriptor.label) {
                updateStatus("Data wajah belum terdaftar untuk akun ini!", "error");
                return;
            }

            // Pastikan label berupa string
            const labelName = String(labeledDescriptor.label);

            // Buat LabeledFaceDescriptors
            const labeledFace = new faceapi.LabeledFaceDescriptors(
                labelName,
                [new Float32Array(labeledDescriptor.descriptor)]
            );

            const faceMatcher = new faceapi.FaceMatcher(labeledFace, 0.6);
            updateStatus("Mendeteksi wajah...", "warning");

            let detectionCount = 0;
            const maxDetections = 5;
            const interval = setInterval(async () => {
                const detections = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detections) {
                    detectionCount++;
                    updateProgress((detectionCount / maxDetections) * 100);
                    
                    const bestMatch = faceMatcher.findBestMatch(detections.descriptor);

                    if (bestMatch.label === labelName) {
                        clearInterval(interval);
                        updateStatus("Wajah dikenali! Login berhasil.", "success");
                        video.pause();

                        // Simpan log ke database
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

                        // Redirect ke dashboard
                        setTimeout(() => window.location.href = "pilih_meja.php", 1500);
                    } else {
                        if (detectionCount >= maxDetections) {
                            clearInterval(interval);
                            updateStatus("Wajah tidak cocok! Silakan coba lagi.", "error");
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        }
                    }
                }
            }, 1000);
        } catch (error) {
            console.error(error);
            updateStatus("Terjadi kesalahan saat memuat data wajah", "error");
        }
    }
});
</script>
</body>
</html>