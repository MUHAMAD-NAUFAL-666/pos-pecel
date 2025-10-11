<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
include 'db.php';

// Proses perubahan status meja
if (isset($_POST['action']) && $_POST['action'] === 'free_table') {
    $table_id = intval($_POST['table_id']);
    $stmt = $conn->prepare("UPDATE meja SET status = 'available' WHERE id = ?");
    $stmt->bind_param("i", $table_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect untuk menghindari pengiriman ulang form
    header("Location: pilih_meja.php");
    exit;
}

// Ambil data meja
 $result = $conn->query("SELECT * FROM meja ORDER BY nomor ASC");
 $meja_list = [];
while ($row = $result->fetch_assoc()) {
    $meja_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Meja - POS Pecel Lele</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --bg-color: #ffffff;
    --page-bg: #f7f8fa;
    --text-color: #1a1a1a;
    --text-muted: #6c757d;
    --accent-color: #10b981;
    --border-color: #e5e7eb;
    --success-color: #22c55e;
    --danger-color: #ef4444;
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
    padding: 20px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

.header {
    text-align: center;
    margin-bottom: 40px;
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
}

.header p {
    color: var(--text-muted);
    font-size: 16px;
}

.table-selection-card {
    background-color: var(--bg-color);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    padding: 30px;
    animation: fadeIn 0.7s ease-out forwards;
    transition: background-color 0.3s ease, color 0.3s ease;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(20px);}
    to {opacity: 1; transform: translateY(0);}
}

.floor-plan {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.table-container {
    position: relative;
    aspect-ratio: 1;
}

.table {
    width: 100%;
    height: 100%;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 3px solid transparent;
    position: relative;
    overflow: hidden;
}

.table i {
    font-size: 24px;
    margin-bottom: 8px;
}

.table-number {
    font-weight: 700;
}

.available {
    background-color: rgba(34, 197, 94, 0.1);
    border-color: var(--success-color);
    color: var(--success-color);
}

.available:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(34, 197, 94, 0.2);
}

.occupied {
    background-color: rgba(239, 68, 68, 0.1);
    border-color: var(--danger-color);
    color: var(--danger-color);
    cursor: pointer;
    opacity: 0.7;
}

.occupied:hover {
    opacity: 1;
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(239, 68, 68, 0.2);
}

.selected {
    background-color: rgba(16, 185, 129, 0.2);
    border-color: var(--accent-color);
    color: var(--accent-color);
    transform: scale(1.05);
    box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);
}

.selected::after {
    content: '\f058';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 16px;
}

.occupied-text {
    font-size: 12px;
    font-weight: 500;
    color: var(--danger-color);
    margin-top: 5px;
    text-align: center;
}

.free-table-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: var(--warning-color);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 10;
}

.table-container:hover .free-table-btn {
    opacity: 1;
}

.free-table-btn:hover {
    transform: scale(1.1);
    background-color: #e05a00;
}

.button-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    gap: 15px;
}

.btn-confirm {
    padding: 14px 30px;
    font-size: 16px;
    font-weight: 600;
    background-color: var(--accent-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-confirm:hover:not(:disabled) {
    background-color: #059669;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
}

.btn-confirm:disabled {
    background-color: var(--text-muted);
    cursor: not-allowed;
    transform: none;
}

.btn-manage {
    padding: 14px 30px;
    font-size: 16px;
    font-weight: 600;
    background-color: var(--warning-color);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-manage:hover {
    background-color: #e05a00;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
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
    z-index: 1000;
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

.legend {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-bottom: 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--text-muted);
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.legend-available {
    background-color: var(--success-color);
}

.legend-occupied {
    background-color: var(--danger-color);
}

.legend-manage {
    background-color: var(--warning-color);
}

.mode-toggle {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.mode-btn {
    padding: 8px 16px;
    margin: 0 5px;
    border: 1px solid var(--border-color);
    background-color: var(--bg-color);
    color: var(--text-color);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mode-btn.active {
    background-color: var(--accent-color);
    color: white;
    border-color: var(--accent-color);
}

@media (max-width: 768px) {
    .floor-plan {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 15px;
    }
    
    .table i {
        font-size: 20px;
    }
    
    .table-number {
        font-size: 16px;
    }
    
    .button-container {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-confirm, .btn-manage {
        width: 100%;
        max-width: 300px;
    }
}
</style>
</head>
<body>

<div class="dark-toggle" id="darkToggle">
    <div class="circle"></div>
</div>

<div class="container">
    <div class="header">
        <h1>Kelola Meja</h1>
        <p>Pilih meja yang tersedia atau kelola meja yang terisi</p>
    </div>
    
    <div class="table-selection-card">
        <div class="mode-toggle">
            <button class="mode-btn active" id="selectMode">
                <i class="fas fa-mouse-pointer me-1"></i> Pilih Meja
            </button>
            <button class="mode-btn" id="manageMode">
                <i class="fas fa-cog me-1"></i> Kelola Meja
            </button>
        </div>
        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color legend-available"></div>
                <span>Tersedia</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-occupied"></div>
                <span>Terisi</span>
            </div>
            <div class="legend-item" id="manageLegend" style="display: none;">
                <div class="legend-color legend-manage"></div>
                <span>Kosongkan</span>
            </div>
        </div>
        
        <div class="floor-plan">
            <?php foreach($meja_list as $m): ?>
                <div class="table-container">
                    <div class="table <?= $m['status'] ?>" 
                         data-id="<?= $m['id'] ?>" 
                         data-status="<?= $m['status'] ?>">
                        <i class="fas fa-chair"></i>
                        <div class="table-number">Meja <?= $m['nomor'] ?></div>
                    </div>
                    <?php if($m['status'] == 'occupied'): ?>
                        <div class="occupied-text">Terisi</div>
                        <form method="post" class="free-table-form" style="display: none;">
                            <input type="hidden" name="action" value="free_table">
                            <input type="hidden" name="table_id" value="<?= $m['id'] ?>">
                        </form>
                        <button class="free-table-btn" title="Kosongkan Meja" data-table-id="<?= $m['id'] ?>">
                            <i class="fas fa-unlock"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="button-container">
            <button id="confirmButton" class="btn-confirm" disabled>
                <i class="fas fa-check"></i>
                <span>Konfirmasi Pilihan</span>
            </button>
            <button id="refreshButton" class="btn-manage">
                <i class="fas fa-sync-alt"></i>
                <span>Refresh Data</span>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    // Mode toggle
    const selectMode = document.getElementById('selectMode');
    const manageMode = document.getElementById('manageMode');
    const manageLegend = document.getElementById('manageLegend');
    const confirmButton = document.getElementById('confirmButton');
    const refreshButton = document.getElementById('refreshButton');
    const freeTableBtns = document.querySelectorAll('.free-table-btn');
    
    let currentMode = 'select'; // 'select' or 'manage'
    let selectedTable = null;
    const tables = document.querySelectorAll('.table');

    // Switch to select mode
    selectMode.addEventListener('click', () => {
        currentMode = 'select';
        selectMode.classList.add('active');
        manageMode.classList.remove('active');
        manageLegend.style.display = 'none';
        confirmButton.style.display = 'flex';
        
        // Reset table selection
        tables.forEach(t => t.classList.remove('selected'));
        selectedTable = null;
        confirmButton.disabled = true;
        
        // Update table click behavior
        tables.forEach(table => {
            table.onclick = function() {
                const status = this.getAttribute('data-status');
                if (status === 'occupied') return;

                tables.forEach(t => t.classList.remove('selected'));
                this.classList.add('selected');
                selectedTable = this.getAttribute('data-id');
                confirmButton.disabled = false;
            };
        });
    });

    // Switch to manage mode
    manageMode.addEventListener('click', () => {
        currentMode = 'manage';
        manageMode.classList.add('active');
        selectMode.classList.remove('active');
        manageLegend.style.display = 'flex';
        confirmButton.style.display = 'none';
        
        // Reset table selection
        tables.forEach(t => t.classList.remove('selected'));
        selectedTable = null;
        
        // Update table click behavior
        tables.forEach(table => {
            table.onclick = function() {
                // No action in manage mode
            };
        });
        
        // Show free table buttons
        freeTableBtns.forEach(btn => {
            btn.style.opacity = '1';
        });
    });

    // Initialize with select mode
    selectMode.click();

    // Free table button click
    freeTableBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const tableId = this.getAttribute('data-table-id');
            const tableContainer = this.closest('.table-container');
            const tableNumber = tableContainer.querySelector('.table-number').textContent;
            
            if (confirm(`Apakah Anda yakin ingin mengubah status ${tableNumber} menjadi tersedia?`)) {
                // Submit form to free the table
                const form = tableContainer.querySelector('.free-table-form');
                form.submit();
            }
        });
    });

    // Confirm button click
    confirmButton.addEventListener('click', () => {
        if (selectedTable) {
            // Add loading state
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memproses...</span>';
            
            // Simulate processing delay
            setTimeout(() => {
                window.location.href = `pos.php?meja=${selectedTable}`;
            }, 500);
        }
    });

    // Refresh button click
    refreshButton.addEventListener('click', () => {
        refreshButton.disabled = true;
        refreshButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memuat...</span>';
        
        // Reload the page
        setTimeout(() => {
            window.location.reload();
        }, 500);
    });
});
</script>
</body>
</html>