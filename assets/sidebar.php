<?php
require_once "assets/config.php";
require_once "assets/session.php";


$session = new Session();
if ($session->get('logged_in') !== true) {
    header("Location: index.php");
    exit();
}

?>
<aside class="sidebar">
    
    <div class="sidebar-header">
        <img src="assets/img/logoPOLIJE.png" alt="Logo" class="sidebar-logo">
        <a href="dashboard.php">
            <h3>POLIKLINIK</h3> 
        </a>
    </div>
<nav class="sidebar-nav">
    <p class="nav-item username"><strong><?= htmlspecialchars($session->get('username')) ?></strong><strong class="active-indic">ACTIVE</strong></p>
    <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">Dashboard</a>
    <a href="data-barang.php" class="nav-item <?php echo ($current_page == 'data-barang') ? 'active' : ''; ?>">Data Barang</a>
    <a href="barang-keluar.php" class="nav-item <?php echo ($current_page == 'barang-keluar') ? 'active' : ''; ?>">Barang Keluar</a>
    <a href="barang-masuk.php" class="nav-item <?php echo ($current_page == 'barang-masuk') ? 'active' : ''; ?>">Barang Masuk</a>
    <a href="riwayat.php" class="nav-item <?php echo ($current_page == 'riwayat') ? 'active' : ''; ?>">Riwayat</a>
    <a href="logout.php" class="nav-item logout">Logout</a>
</nav>
</aside>