<aside class="sidebar">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <div class="logo-section">
        <img src="assets/img/logo_polije.png" alt="Logo" class="logo-img">
        <h2>POLI GIGI</h2>
    </div>

    <nav class="nav-menu">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <img src="assets/img/dashboard_hitam.png" class="nav-icon"> Dashboard
        </a>
        <a href="Data-Barang.php" class="nav-item <?php echo ($current_page == 'Data-Barang.php') ? 'active' : ''; ?>">
            <img src="assets/img/databarang_hitam.png" class="nav-icon"> Data Barang
        </a>
        
        <a href="Barang-Masuk.php" class="nav-item <?php echo ($current_page == 'Barang-Masuk.php') ? 'active' : ''; ?>">
            <img src="assets/img/mintabarang_hitam.png" class="nav-icon"> Barang Masuk
        </a>
        <a href="Barang-Keluar.php" class="nav-item <?php echo ($current_page == 'Barang-Keluar.php') ? 'active' : ''; ?>">
            <img src="assets/img/mintabarang_hitam.png" class="nav-icon"> Barang Keluar
        </a>
        <a href="riwayat.php" class="nav-item <?php echo ($current_page == 'riwayat.php') ? 'active' : ''; ?>">
            <img src="assets/img/Riwayat.png" class="nav-icon"> Riwayat
        </a>
    </nav>

    <div class="logout-section" style="padding: 20px;">
        <a href="logout.php" class="nav-item" style="color: #721c24;">
            <img src="assets/img/logout.png" class="nav-icon"> Logout
        </a>
    </div>
</aside>