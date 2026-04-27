<?php 
$current_page = 'barang-masuk';
require_once "assets/config.php";
require_once "assets/session.php";

$session = new Session();
if ($session->get('logged_in') !== true) {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $id_barang   = intval($_POST['id_barang'] ?? 0);
    $jumlah      = intval($_POST['jumlah'] ?? 0);
    $keterangan  = trim($_POST['keterangan'] ?? '');
    $id_admin    = $session->get('id_admin'); // from login session

    if ($id_barang > 0 && $jumlah > 0) {
        $stmt = $conn->prepare("INSERT INTO Barang_Masuk (id_barang, id_admin, jumlah, keterangan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $id_barang, $id_admin, $jumlah, $keterangan);
        if ($stmt->execute()) {
            $success = "Barang berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan barang: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Harap isi semua field dengan benar.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Masuk</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">

    <!-- Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
</head>
<body>

<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <h1>Barang Masuk</h1>

        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="itemAdd-Container">
                <select name="id_barang" id="select-barang" class="itemAdd-fields" required>
                    <option value="" disabled selected>Pilih Barang</option>
                    <?php
                    $result = $conn->query("SELECT id_barang, nama_barang FROM Barang ORDER BY nama_barang ASC");
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['id_barang'] . '">' . htmlspecialchars($row['nama_barang']) . '</option>';
                    }
                    ?>
                </select>
                <input type="number" name="jumlah" class="itemAdd-fields" placeholder="Jumlah" min="1" required>
                <input type="text" name="keterangan" class="itemAdd-fields" placeholder="Keterangan (opsional)">
                <button type="submit" class="itemAdd-btn" name="submit" value="submit">Tambah</button>
            </div>
        </form>
    </main>
</div>

<script>
    // Initialize Tom Select on the dropdown
    new TomSelect('#select-barang', {
        placeholder: 'Cari barang...',
        allowEmptyOption: false,
        caseinsensitive: true,
        searchfields: ['text']
    });
</script>

</body>
</html>