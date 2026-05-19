<?php
$current_page = 'Data-Barang';
require_once "assets/config.php";
require_once "assets/session.php";

// Pengecekan Sesi Login
$session = new Session();
if ($session->get('logged_in') !== true) {
    header("Location: index.php");
    exit();
}

// ─── HANDLE CRUD POST REQUESTS (Tambah, Edit, Hapus) ───────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ── PROSES TAMBAH BARANG ──
    if ($action === 'tambah') {
        $nama   = trim($_POST['nama'] ?? '');
        $satuan = trim($_POST['satuan'] ?? '');
        $stok   = intval($_POST['jumlah_stok'] ?? 0);

        if ($nama !== '' && $satuan !== '') {
            $stmt = $conn->prepare("INSERT INTO barang (nama_barang, satuan) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama, $satuan);
            $stmt->execute();
            
            $id_barang_baru = $stmt->insert_id; 
            $stmt->close();

            if ($stok > 0 && $id_barang_baru > 0) {
                $id_admin = $session->get('id_admin'); 
                $ket = "Stok Awal";
                
                $stmt_masuk = $conn->prepare("INSERT INTO barang_masuk (id_barang, id_admin, jumlah, keterangan) VALUES (?, ?, ?, ?)");
                $stmt_masuk->bind_param("iiis", $id_barang_baru, $id_admin, $stok, $ket);
                $stmt_masuk->execute();
                $stmt_masuk->close();
            }
        }
    }

    // ── PROSES EDIT BARANG ──
    if ($action === 'edit') {
        $id     = intval($_POST['id']);
        $nama   = trim($_POST['nama'] ?? '');
        $satuan = trim($_POST['satuan'] ?? '');
        
        if ($id > 0 && $nama !== '' && $satuan !== '') {
            $stmt = $conn->prepare("UPDATE barang SET nama_barang=?, satuan=? WHERE id_barang=?");
            $stmt->bind_param("ssi", $nama, $satuan, $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // ── PROSES HAPUS BARANG ──
    if ($action === 'hapus') {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: Data-Barang.php");
    exit();
}

// ─── SEARCH LOGIC (TANPA PAGINATION) ─────────────────────────────
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause  = $search !== '' ? "WHERE nama_barang LIKE '%$search%' OR satuan LIKE '%$search%'" : '';

// Query mengambil SEMUA data tanpa LIMIT
$stok_query = "SELECT b.id_barang, b.nama_barang, b.satuan,
    COALESCE(m.total_masuk,0) - COALESCE(k.total_keluar,0) AS jumlah_stok
    FROM barang b
    LEFT JOIN (SELECT id_barang, SUM(jumlah) AS total_masuk FROM barang_masuk GROUP BY id_barang) m ON b.id_barang = m.id_barang
    LEFT JOIN (SELECT id_barang, SUM(jumlah) AS total_keluar FROM barang_keluar GROUP BY id_barang) k ON b.id_barang = k.id_barang";

$having_clause = $search !== '' ? "HAVING nama_barang LIKE '%$search%' OR satuan LIKE '%$search%'" : '';
$result = $conn->query("$stok_query $having_clause ORDER BY nama_barang ASC");

// Menghitung total keseluruhan jenis barang di Database
$total_query = $conn->query("SELECT COUNT(*) AS c FROM barang");
$total       = $total_query ? $total_query->fetch_assoc()['c'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Polinventory</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <header class="content-header">
            <h3>Data Barang</h3>
        </header>

        <div class="Search-and-total">
            <div class="total-jenis-container">
                <h2>Total Jenis Barang :</h2>
                <h2 class="total-jenis-count"><?= $total ?></h2>
            </div>

            <form action="Data-Barang.php" method="GET" class="search-container">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                    <path d="M480 272C480 317.9 465.1 360.3 440 394.7L566.6 521.4C579.1 533.9 579.1 554.2 566.6 566.7C554.1 579.2 533.8 579.2 521.3 566.7L394.7 440C360.3 465.1 317.9 480 272 480C157.1 480 64 386.9 64 272C64 157.1 157.1 64 272 64C386.9 64 480 157.1 480 272zM272 416C351.5 416 416 351.5 416 272C416 192.5 351.5 128 272 128C192.5 128 128 192.5 128 272C128 351.5 192.5 416 272 416z"/>
                </svg>
                <input type="text" name="search" id="search-input" placeholder="Cari nama barang atau satuan..."
                       value="<?= htmlspecialchars($search) ?>">
            </form>

            <button class="btn-add" onclick="openTambah()">+ Tambah Barang</button>
        </div>

        <div class="main-tb-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1; // Penomoran selalu mulai dari 1
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= strtoupper(htmlspecialchars($row['nama_barang'])) ?></td>
                        <td><?= htmlspecialchars($row['jumlah_stok'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($row['satuan']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-edit" onclick="openEdit(this, <?= $row['id_barang'] ?>)">Edit</button>
                                <button class="btn-delete" onclick="openHapus(this, <?= $row['id_barang'] ?>)">Hapus</button>
                            </div>
                        </td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <tr><td colspan="5" style="text-align:center;">Tidak ada data barang ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </main>
</div>

<div class="modal-overlay" id="modal-form">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-title" style="margin: 0;">Tambah Barang</h3>
            <span class="close-modal" onclick="closeModal()">✕</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="action" id="form-action" value="tambah">
                <input type="hidden" name="id"     id="form-id">
                
                <div class="form-group">
                    <label>Nama Barang</label>
                    <input type="text" name="nama" id="input-nama" placeholder="Nama Barang..." required>
                </div>
                
                <div class="form-group">
                    <label>Satuan</label>
                    <input type="text" name="satuan" id="input-satuan" placeholder="PCS / Box / dll..." required>
                </div>
                
                <div class="form-group" id="group-stok">
                    <label>Stok Awal</label>
                    <input type="number" name="jumlah_stok" id="input-stok" placeholder="0" min="0" value="0">
                </div>
                
                <button type="submit" class="btn-simpan">Simpan</button>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-hapus">
    <div class="modal" style="width: 350px;">
        <div class="modal-header">
            <h3 style="margin: 0; color: #dc3545;">Konfirmasi Hapus</h3>
            <span class="close-modal" onclick="closeHapus()">✕</span>
        </div>
        <div class="modal-body" style="text-align: center; margin-top: 10px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" id="hapus-id">
                <p style="margin-bottom: 20px; font-size: 16px;">Yakin ingin menghapus <br><strong id="hapus-nama"></strong>?</p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" class="btn-edit" onclick="closeHapus()" style="flex: 1;">Batal</button>
                    <button type="submit" class="btn-delete" style="flex: 1;">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openTambah() {
        document.getElementById('modal-title').textContent  = 'Tambah Barang';
        document.getElementById('form-action').value        = 'tambah';
        document.getElementById('form-id').value            = '';
        document.getElementById('input-nama').value         = '';
        document.getElementById('input-satuan').value       = '';
        document.getElementById('input-stok').value         = '0';
        document.getElementById('group-stok').style.display = 'block'; 
        document.getElementById('modal-form').style.display = 'flex';
    }

    function openEdit(btn, id) {
        const row = btn.closest('tr');
        document.getElementById('modal-title').textContent  = 'Edit Barang';
        document.getElementById('form-action').value        = 'edit';
        document.getElementById('form-id').value            = id;
        document.getElementById('input-nama').value         = row.cells[1].textContent.trim();
        document.getElementById('input-satuan').value       = row.cells[3].textContent.trim();
        document.getElementById('group-stok').style.display = 'none';
        document.getElementById('modal-form').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modal-form').style.display = 'none';
    }

    function openHapus(btn, id) {
        const row = btn.closest('tr');
        document.getElementById('hapus-id').value           = id;
        document.getElementById('hapus-nama').textContent   = row.cells[1].textContent.trim();
        document.getElementById('modal-hapus').style.display = 'flex';
    }

    function closeHapus() {
        document.getElementById('modal-hapus').style.display = 'none';
    }

    // Live search client-side
    document.getElementById('search-input').addEventListener('input', function () {
        const kw   = this.value.toLowerCase().trim();
        const rows = Array.from(document.querySelectorAll('tbody tr'));
        rows.forEach(row => {
            if(row.cells.length === 1) return; 
            const nama   = row.cells[1]?.textContent.toLowerCase() || '';
            const satuan = row.cells[3]?.textContent.toLowerCase() || '';
            row.style.display = (!kw || nama.includes(kw) || satuan.includes(kw)) ? '' : 'none';
        });
    });
</script>
</body>
</html>