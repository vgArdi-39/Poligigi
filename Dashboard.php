<?php $current_page = 'dashboard'; 
require_once "assets/config.php";
require_once "assets/session.php";

$session = new Session();
if ($session->get('logged_in') !== true) {
    header("Location: index.php");
    exit();
}

$result = $conn->query("SELECT * FROM V_Stok ORDER BY nama_barang ASC");
$total  = $result->num_rows;
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <h1>Halo, <?= htmlspecialchars($session->get('username')) ?>!</h1>
        <div class="dashboard-cards">
            <div class="stat-card">
                <h4>Total Jenis Barang</h4>
                <p class="value"><?= $total ?></p>
            </div>
            <div class="stat-card">
                <h4>Jam</h4>
                <p class="value" id="clock"></p>
            </div>
            <div class="stat-card">
                <h4>Placeholder</h4>
                <p class="value">0</p>
            </div>
        </div>

        <div class="Ringkasan-Stok-Barang">
            <h2>Ringkasan Stok Barang</h2>
            <table class="summary-stok-table">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Jumlah Stok</th>
                        <th>Satuan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td><?= $row['jumlah_stok'] ?></td>
                        <td><?= htmlspecialchars($row['satuan']) ?></td>
                        <td>
                            <?php if ($row['jumlah_stok'] > 10): ?>
                                <span class="status in-stock">Stok tersedia</span>
                            <?php elseif ($row['jumlah_stok'] > 0): ?>
                                <span class="status low-stock">Stok hampir habis</span>
                            <?php else: ?>
                                <span class="status out-of-stock">Stok habis</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script>
        function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent = now.toLocaleTimeString();
    }

    updateClock(); // run immediately
    setInterval(updateClock, 1000); // then every second
</script>
</body>
</html>