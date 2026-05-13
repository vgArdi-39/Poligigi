<?php
$current_page = 'dashboard';
require_once "assets/config.php";
require_once "assets/session.php";

$session = new Session();
if ($session->get('logged_in') !== true) {
    header("Location: index.php");
    exit();
}

// ── Stats (from second) ──────────────────────────────────────────────────────
$total_jenis = $conn->query("SELECT COUNT(*) AS c FROM Barang")->fetch_assoc()['c'];
$total_stok  = $conn->query("SELECT SUM(jumlah_stok) AS s FROM v_stok")->fetch_assoc()['s'] ?? 0;
$total_limit = $conn->query("SELECT COUNT(*) AS c FROM v_stok WHERE jumlah_stok <= 5 AND jumlah_stok > 0")->fetch_assoc()['c'];

// ── Stock summary table (from first — uses V_Stok view) ─────────────────────
$result = $conn->query("SELECT * FROM V_Stok ORDER BY jumlah_stok ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Polinventory</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <header class="content-header">
            <h3>Halo, <?= htmlspecialchars($session->get('username')) ?>!</h3>
        </header>

        <!-- Stat Cards (first layout + second's extra stats) -->
        <div class="dashboard-cards">
            <div class="stat-card">
                <h4>Total Jenis Barang</h4>
                <p class="value"><?= $total_jenis ?></p>
            </div>
            <div class="stat-card">
                <h4>Total Stok</h4>
                <p class="value"><?= number_format($total_stok, 0, ',', '.') ?></p>
            </div>
            <div class="stat-card">
                <h4>Barang Hampir Habis</h4>
                <p class="value"><?= $total_limit ?></p>
            </div>
            <div class="stat-card">
                <h4>Jam</h4>
                <p class="value" id="clock"></p>
            </div>
        </div>

        <!-- Stock Summary (first's scrollable card + second's status badges) -->
        <div class="Ringkasan-Stok-Barang">
            <h2>Ringkasan Stok Barang</h2>
            <table class="summary-stok-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Jumlah Stok</th>
                        <th>Satuan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $stok = $row['jumlah_stok'];
                            if ($stok <= 0) {
                                $status_text  = 'Habis';
                                $status_class = 'status-danger';
                            } elseif ($stok <= 10) {
                                $status_text  = 'Hampir Habis';
                                $status_class = 'status-warning';
                            } else {
                                $status_text  = 'Aman';
                                $status_class = 'status-safe';
                            }
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td><?= $stok ?></td>
                        <td><?= htmlspecialchars($row['satuan']) ?></td>
                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <?= $status_text ?>
                            </span>
                        </td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">Data barang masih kosong.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    function updateClock() {
        document.getElementById('clock').textContent = new Date().toLocaleTimeString();
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>
</body>
</html>