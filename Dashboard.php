<?php $current_page = 'dashboard'; ?>
<!DOCTYPE html>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <h1>Dashboard</h1>
        <div class="dashboard-cards">
            <div class="stat-card">
                <h4>Total Jenis Barang</h4>
                <p class="value">121</p>
            </div>
            <div class="stat-card">
                <h4>Placeholder</h4>
                <p class="value">0</p>
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
                        <th>Satuan</th>
                        <th>Stok Saat Ini</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Adrenaline</td>
                        <td>Ampul</td>
                        <td>5</td>
                        <td><span class="status-available">Tersedia</span></td>
                    </tr>
                    <tr>
                        <td>HANDSCONE SIZE S</td>
                        <td>Box</td>
                        <td>4</td>
                        <td><span class="status-available">Tersedia</span></td>
                    </tr>
                    <tr>
                        <td>HANDSCONE SIZE M</td>
                        <td>Box</td>
                        <td>2</td>
                        <td><span class="status-available">Tersedia</span></td>
                    </tr>
                    <tr>
                        <td>HANDSCONE SIZE L</td>
                        <td>Box</td>
                        <td>2</td>
                        <td><span class="status-available">Tersedia</span></td>
                    </tr>
                </tbody>
            </table>
    </main>
</div>

</body>
</html>