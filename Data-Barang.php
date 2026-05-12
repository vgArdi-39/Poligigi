<?php 
$current_page = 'data-barang';
include('assets/config.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'tambah') {
        $nama   = $conn->real_escape_string($_POST['nama']);
        $satuan = $conn->real_escape_string($_POST['satuan']);
        $conn->query("INSERT INTO Barang (nama_barang, satuan) VALUES ('$nama', '$satuan')");
    }

    if ($action === 'edit') {
        $id     = (int) $_POST['id'];
        $nama   = $conn->real_escape_string($_POST['nama']);
        $satuan = $conn->real_escape_string($_POST['satuan']);
        $conn->query("UPDATE Barang SET nama_barang='$nama', satuan='$satuan' WHERE id_barang=$id");
    }

    if ($action === 'hapus') {
        $id = (int) $_POST['id'];

        // Delete from Detail_Permintaan
        $stmt = $conn->prepare("DELETE FROM Detail_Permintaan WHERE id_barang = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Delete from Barang_Keluar
        $stmt = $conn->prepare("DELETE FROM Barang_Keluar WHERE id_barang = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Delete from Barang_Masuk
        $stmt = $conn->prepare("DELETE FROM Barang_Masuk WHERE id_barang = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Finally delete from Barang
        $stmt = $conn->prepare("DELETE FROM Barang WHERE id_barang = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

        header("Location: Data-Barang.php");
        exit();
}

$result = $conn->query("SELECT * FROM V_Stok ORDER BY nama_barang ASC");
$total  = $result->num_rows;
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">
</head>
<body>

<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>
    
    <main class="main-content">
        <h1>Data Barang</h1>
        <div class="Search-and-total">
            <div class="total-jenis-container">
                <h2>Total Jenis Barang :</h2>
                <h2 class="total-jenis-count"><?= $total ?></h2>
            </div>
            <div class="search-container">
                <svg class="search-glass" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M480 272C480 317.9 465.1 360.3 440 394.7L566.6 521.4C579.1 533.9 579.1 554.2 566.6 566.7C554.1 579.2 533.8 579.2 521.3 566.7L394.7 440C360.3 465.1 317.9 480 272 480C157.1 480 64 386.9 64 272C64 157.1 157.1 64 272 64C386.9 64 480 157.1 480 272zM272 416C351.5 416 416 351.5 416 272C416 192.5 351.5 128 272 128C192.5 128 128 192.5 128 272C128 351.5 192.5 416 272 416z"/></svg>
                <input type="text" placeholder="Search" id="search-input" name="search">
            </div>
            <button onclick="openTambah()">+ Tambah</button>
        </div>
        <div class="main-tb-container">
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?= $row['jumlah_stok'] ?></td>
                            <td><?= htmlspecialchars($row['satuan']) ?></td>
                            <td>
                                <button onclick="openEdit(this, <?= $row['id_barang'] ?>)">Edit</button>
                                <button onclick="openHapus(this, <?= $row['id_barang'] ?>)">Hapus</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
            </table>
        </div>
    </main>
</div> 

<!-- Modal Tambah/Edit -->
<div class="modal-overlay" id="modal-form">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modal-title">Tambah Barang</h3>
                <button onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" id="form-action" value="tambah">
                <input type="hidden" name="id"     id="form-id">
                <label>Nama Barang</label>
                <input type="text" name="nama" id="input-nama" placeholder="Nama Barang">
                <label>Satuan</label>
                <input type="text" name="satuan" id="input-satuan" placeholder="PCS / Box / dll">
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()">Batal</button>
                    <button type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal-overlay" id="modal-hapus">
    <div class="modal">
        <div class="modal-header">
        <form method="POST">
            <input type="hidden" name="action" value="hapus">
            <input type="hidden" name="id" id="hapus-id">
            <p>Yakin ingin menghapus <strong id="hapus-nama"></strong>?</p>
            <div class="modal-footer">
                <button type="button" onclick="closeHapus()">Batal</button>
                <button type="submit">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/Scripts/JS/DataBarang.js"></script>
</body>
</html>