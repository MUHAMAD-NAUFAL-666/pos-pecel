<?php
include 'db.php';

// Ambil invoice_id dari URL (yang dikirim dari Xendit)
$invoice_id = $_GET['invoice_id'] ?? '';

if (empty($invoice_id)) {
    die("Invoice ID tidak ditemukan.");
}

// Ambil data transaksi dari database
$stmt = $conn->prepare("SELECT id, total, xendit_status FROM sales WHERE xendit_invoice_id = ? LIMIT 1");
$stmt->bind_param("s", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();
$sale = $result->fetch_assoc();
$stmt->close();

if (!$sale) {
    die("Transaksi tidak ditemukan di database.");
}

// Format total ke rupiah
$total_rp = "Rp " . number_format($sale['total'], 0, ",", ".");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .main-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .success-card {
            animation: fadeInUp 0.8s ease-out;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success-icon-wrapper {
            width: 100px;
            height: 100px;
            margin: -50px auto 20px;
            background-color: #28a745;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.3);
            animation: iconBounce 1s ease-out 0.3s both;
        }

        .success-icon {
            font-size: 50px;
            color: white;
        }

        @keyframes iconBounce {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .btn-primary {
            padding: 10px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

    </style>
</head>
<body>


    <div class="main-container">
        <div class="card success-card text-center" style="width: 22rem;">
            <div class="success-icon-wrapper">
                <i class="bi bi-check-lg success-icon"></i>
            </div>

            <div class="card-body pt-1">
                <h2 class="fw-bold mb-3 text-success">Pembayaran Berhasil!</h2>
                <p class="text-muted mb-4">
                    Terima kasih, pembayaran Anda telah kami terima dan dikonfirmasi.
                </p>

                <div class="mt-3 p-3 bg-light rounded">
                    <p class="mb-1"><strong>No. Transaksi:</strong> <?= htmlspecialchars($invoice_id) ?></p>
                    <p class="mb-0"><strong>Total:</strong> <?= $total_rp ?></p>
                </div>

                <a href="pos.php" class="btn btn-primary mt-4">
                    <i class="bi bi-house-door me-2"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Confetti -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

</body>
</html>
