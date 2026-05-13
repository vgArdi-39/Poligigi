<?php
include 'assets/config.php';
/** @var mysqli $conn */

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$limit = 10;

$page_m = isset($_GET['p_masuk']) ? (int)$_GET['p_masuk'] : 1;
$start_m = ($page_m > 1) ? ($page_m * $limit) - $limit : 0;
$where_m = !empty($search) ? "WHERE tanggal LIKE '%$search%'" : "";

$sql_masuk = "SELECT * FROM Barang_Masuk $where_m ORDER BY tanggal DESC LIMIT $start_m, $limit";
$query_masuk = mysqli_query($conn, $sql_masuk);

$total_m_res = mysqli_query($conn, "SELECT id_masuk FROM Barang_Masuk $where_m");
$total_masuk = mysqli_num_rows($total_m_res);
$pages_masuk = ceil($total_masuk / $limit);

$page_k = isset($_GET['p_keluar']) ? (int)$_GET['p_keluar'] : 1;
$start_k = ($page_k > 1) ? ($page_k * $limit) - $limit : 0;
$where_k = !empty($search) ? "WHERE tanggal LIKE '%$search%'" : "";

$sql_keluar = "SELECT * FROM Barang_Keluar $where_k ORDER BY tanggal DESC LIMIT $start_k, $limit";
$query_keluar = mysqli_query($conn, $sql_keluar);

$total_k_res = mysqli_query($conn, "SELECT id_keluar FROM Barang_Keluar $where_k");
$total_keluar = mysqli_num_rows($total_k_res);
$pages_keluar = ceil($total_keluar / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - Poli Gigi</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/riwayat.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'assets/sidebar.php'; ?>
        <main class="main-content">
            <header class="content-header">
                <h3 style="color: #ECF5FD; margin-bottom: 20px;">Riwayat</h3>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Riwayat Barang Masuk</span>
                    <span class="stat-value"><?php echo $total_masuk; ?></span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Riwayat Barang Keluar</span>
                    <span class="stat-value"><?php echo $total_keluar; ?></span>
                </div>
            </section>

            <div class="search-container">
                <form action="" method="GET">
                    <input type="text" name="search" placeholder="Cari Tanggal (YYYY-MM-DD)..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>

            <section class="table-container">
                <h4 class="table-title">Riwayat Barang Masuk</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_masuk) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query_masuk)): 
                                $id_b = $row['id_barang'];
                                $res_b = mysqli_query($conn, "SELECT nama_barang, satuan FROM Barang WHERE id_barang = '$id_b'");
                                $data_b = mysqli_fetch_assoc($res_b);
                                $nama = $data_b['nama_barang'] ?? 'Terhapus';
                                $satuan = $data_b['satuan'] ?? '-';
                                $ket = $row['keterangan'] ?? '-';
                            ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo strtoupper($nama); ?></td>
                                    <td><?php echo strtoupper($satuan); ?></td>
                                    <td><?php echo $row['jumlah']; ?></td>
                                    <td><?php echo $ket; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">Data tidak ditemukan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination-wrapper">
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $pages_masuk; $i++): ?>
                            <a href="?p_masuk=<?php echo $i; ?>&search=<?php echo $search; ?>&p_keluar=<?php echo $page_k; ?>" class="page-item <?php echo ($page_m == $i) ? 'active-page' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

            <section class="table-container">
                <h4 class="table-title">Riwayat Barang Keluar</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_keluar) > 0): ?>
                            <?php while ($rowk = mysqli_fetch_assoc($query_keluar)): 
                                $id_bk = $rowk['id_barang'];
                                $res_bk = mysqli_query($conn, "SELECT nama_barang, satuan FROM Barang WHERE id_barang = '$id_bk'");
                                $data_bk = mysqli_fetch_assoc($res_bk);
                                $nama_k = $data_bk['nama_barang'] ?? 'Terhapus';
                                $satuan_k = $data_bk['satuan'] ?? '-';
                            ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($rowk['tanggal'])); ?></td>
                                    <td><?php echo strtoupper($nama_k); ?></td>
                                    <td><?php echo strtoupper($satuan_k); ?></td>
                                    <td><?php echo $rowk['jumlah']; ?></td>
                                    <td><?php echo $rowk['keterangan']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">Data tidak ditemukan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination-wrapper">
                    <div class="pagination">
                        <?php for ($j = 1; $j <= $pages_keluar; $j++): ?>
                            <a href="?p_keluar=<?php echo $j; ?>&search=<?php echo $search; ?>&p_masuk=<?php echo $page_m; ?>" class="page-item <?php echo ($page_k == $j) ? 'active-page' : ''; ?>"><?php echo $j; ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

            <div class="export-actions">
                <button class="btn-export" onclick="openExportModal()">Cetak PDF</button>
            </div>
        </main>
    </div>

    <div class="modal-overlay-riwayat" id="modalExport">
        <div class="modal-content-riwayat">
            <div class="modal-header-riwayat">
                <h4 class="modal-title">Ekspor Data PDF</h4>
                <button onclick="closeExportModal()" class="close-btn-riwayat">&times;</button>
            </div>
            <form action="proses_ekspor.php" method="POST">
                <input type="hidden" name="format" value="pdf">
                <div class="form-group-riwayat">
                    <label>Kategori Riwayat</label>
                    <select name="kategori" required class="input-riwayat-modal">
                        <option value="masuk">Barang Masuk</option>
                        <option value="keluar">Barang Keluar</option>
                    </select>
                </div>
                <div class="form-row-riwayat">
                    <div class="form-group-riwayat" style="flex:1;">
                        <label>Dari Tanggal</label>
                        <input type="date" name="tgl_mulai" required class="input-riwayat-modal">
                    </div>
                    <div class="form-group-riwayat" style="flex:1;">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="tgl_selesai" required class="input-riwayat-modal">
                    </div>
                </div>
                <button type="submit" class="btn-confirm-riwayat">Mulai Unduh</button>
            </form>
        </div>
    </div>

    <script src="js/riwayat.js"></script>
</body>
</html>