<?php
include 'assets/config.php';
/** @var mysqli $conn */

$tgl_mulai   = isset($_GET['tgl_mulai'])   && $_GET['tgl_mulai']   !== '' ? mysqli_real_escape_string($conn, $_GET['tgl_mulai'])   : '';
$tgl_selesai = isset($_GET['tgl_selesai']) && $_GET['tgl_selesai'] !== '' ? mysqli_real_escape_string($conn, $_GET['tgl_selesai']) : '';
$limit = 10;

function buildWhere($tgl_mulai, $tgl_selesai) {
    if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
        return "WHERE DATE(tanggal) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
    } elseif (!empty($tgl_mulai)) {
        return "WHERE DATE(tanggal) >= '$tgl_mulai'";
    } elseif (!empty($tgl_selesai)) {
        return "WHERE DATE(tanggal) <= '$tgl_selesai'";
    }
    return "";
}

$where_m = buildWhere($tgl_mulai, $tgl_selesai);
$where_k = buildWhere($tgl_mulai, $tgl_selesai);

$page_m  = isset($_GET['p_masuk'])  ? (int)$_GET['p_masuk']  : 1;
$start_m = ($page_m > 1) ? ($page_m * $limit) - $limit : 0;

$query_masuk = mysqli_query($conn, "SELECT * FROM Barang_Masuk $where_m ORDER BY tanggal DESC LIMIT $start_m, $limit");
$total_masuk = mysqli_num_rows(mysqli_query($conn, "SELECT id_masuk FROM Barang_Masuk $where_m"));
$pages_masuk = ceil($total_masuk / $limit);

$page_k  = isset($_GET['p_keluar']) ? (int)$_GET['p_keluar'] : 1;
$start_k = ($page_k > 1) ? ($page_k * $limit) - $limit : 0;

$query_keluar = mysqli_query($conn, "SELECT * FROM Barang_Keluar $where_k ORDER BY tanggal DESC LIMIT $start_k, $limit");
$total_keluar = mysqli_num_rows(mysqli_query($conn, "SELECT id_keluar FROM Barang_Keluar $where_k"));
$pages_keluar = ceil($total_keluar / $limit);

// ── Fetch ALL rows for export (no pagination limit) ───────────────────────────
$all_masuk = [];
$res_all_m = mysqli_query($conn, "SELECT * FROM Barang_Masuk $where_m ORDER BY tanggal DESC");
while ($r = mysqli_fetch_assoc($res_all_m)) {
    $res_b  = mysqli_query($conn, "SELECT nama_barang, satuan FROM Barang WHERE id_barang = '{$r['id_barang']}'");
    $data_b = mysqli_fetch_assoc($res_b);
    $r['nama_barang'] = $data_b['nama_barang'] ?? 'Terhapus';
    $r['satuan']      = $data_b['satuan']      ?? '-';
    $r['keterangan']  = $r['keterangan']       ?? '-';
    $all_masuk[] = $r;
}

$all_keluar = [];
$res_all_k  = mysqli_query($conn, "SELECT * FROM Barang_Keluar $where_k ORDER BY tanggal DESC");
while ($r = mysqli_fetch_assoc($res_all_k)) {
    $res_b  = mysqli_query($conn, "SELECT nama_barang, satuan FROM Barang WHERE id_barang = '{$r['id_barang']}'");
    $data_b = mysqli_fetch_assoc($res_b);
    $r['nama_barang'] = $data_b['nama_barang'] ?? 'Terhapus';
    $r['satuan']      = $data_b['satuan']      ?? '-';
    $r['keterangan']  = $r['keterangan']       ?? '-';
    $all_keluar[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - Poli Gigi</title>
    <link rel="stylesheet" href="assets/css/riwayat.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
                <form action="" method="GET" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <label style="color:white; font-weight:600;">Dari</label>
                    <input type="date" name="tgl_mulai" class="search-input"
                           value="<?php echo htmlspecialchars($tgl_mulai); ?>">
                    <label style="color:white; font-weight:600;">Sampai</label>
                    <input type="date" name="tgl_selesai" class="search-input"
                           value="<?php echo htmlspecialchars($tgl_selesai); ?>">
                    <button type="submit" class="btn-search">Cari</button>
                    <?php if ($tgl_mulai || $tgl_selesai): ?>
                        <a href="Riwayat.php" class="btn-reset">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Barang Masuk -->
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
                                $id_b   = $row['id_barang'];
                                $res_b  = mysqli_query($conn, "SELECT nama_barang, satuan FROM Barang WHERE id_barang = '$id_b'");
                                $data_b = mysqli_fetch_assoc($res_b);
                                $nama   = $data_b['nama_barang'] ?? 'Terhapus';
                                $satuan = $data_b['satuan']      ?? '-';
                                $ket    = $row['keterangan']     ?? '-';
                            ?>
                                <tr>
                                    <td><?php echo date('d-m-Y H:i:s', strtotime($row['tanggal'])); ?></td>
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
                            <a href="?p_masuk=<?php echo $i; ?>&tgl_mulai=<?php echo urlencode($tgl_mulai); ?>&tgl_selesai=<?php echo urlencode($tgl_selesai); ?>&p_keluar=<?php echo $page_k; ?>"
                               class="page-item <?php echo ($page_m == $i) ? 'active-page' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

            <!-- Barang Keluar -->
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
                                $id_bk    = $rowk['id_barang'];
                                $res_bk   = mysqli_query($conn, "SELECT nama_barang, satuan FROM Barang WHERE id_barang = '$id_bk'");
                                $data_bk  = mysqli_fetch_assoc($res_bk);
                                $nama_k   = $data_bk['nama_barang'] ?? 'Terhapus';
                                $satuan_k = $data_bk['satuan']      ?? '-';
                            ?>
                                <tr>
                                    <td><?php echo date('d-m-Y H:i:s', strtotime($rowk['tanggal'])); ?></td>
                                    <td><?php echo strtoupper($nama_k); ?></td>
                                    <td><?php echo strtoupper($satuan_k); ?></td>
                                    <td><?php echo $rowk['jumlah']; ?></td>
                                    <td><?php echo $rowk['keterangan'] ?? '-'; ?></td>
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
                            <a href="?p_keluar=<?php echo $j; ?>&tgl_mulai=<?php echo urlencode($tgl_mulai); ?>&tgl_selesai=<?php echo urlencode($tgl_selesai); ?>&p_masuk=<?php echo $page_m; ?>"
                               class="page-item <?php echo ($page_k == $j) ? 'active-page' : ''; ?>">
                                <?php echo $j; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

            <div class="export-actions">
                <button class="btn-export" onclick="eksporExcel()">Cetak Excel</button>
            </div>
        </main>
    </div>

    <script src="assets/scripts/JS/xlsx.full.min.js"></script>
    <script>
        const dataMasuk = <?php echo json_encode(array_map(function($r) {
            return [
                'Tanggal'     => date('d-m-Y H:i:s', strtotime($r['tanggal'])),
                'Nama Barang' => strtoupper($r['nama_barang']),
                'Satuan'      => strtoupper($r['satuan']),
                'Jumlah'      => (int)$r['jumlah'],
                'Keterangan'  => $r['keterangan'],
            ];
        }, $all_masuk)); ?>;

        const dataKeluar = <?php echo json_encode(array_map(function($r) {
            return [
                'Tanggal'     => date('d-m-Y H:i:s', strtotime($r['tanggal'])),
                'Nama Barang' => strtoupper($r['nama_barang']),
                'Satuan'      => strtoupper($r['satuan']),
                'Jumlah'      => (int)$r['jumlah'],
                'Keterangan'  => $r['keterangan'],
            ];
        }, $all_keluar)); ?>;

        function eksporExcel() {
            const wb = XLSX.utils.book_new();

            const wsMasuk = XLSX.utils.json_to_sheet(dataMasuk.length ? dataMasuk : [{'Info': 'Tidak ada data'}]);
            XLSX.utils.book_append_sheet(wb, wsMasuk, 'Barang Masuk');

            const wsKeluar = XLSX.utils.json_to_sheet(dataKeluar.length ? dataKeluar : [{'Info': 'Tidak ada data'}]);
            XLSX.utils.book_append_sheet(wb, wsKeluar, 'Barang Keluar');

            const tanggal = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');
            XLSX.writeFile(wb, `riwayat_${tanggal}.xlsx`);
        }
    </script>
    <script src="js/riwayat.js"></script>
</body>
</html>