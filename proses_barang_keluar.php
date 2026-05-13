<?php
require_once 'assets/config.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data_barang = json_decode($input, true);

if (!$data_barang || empty($data_barang)) {
    echo json_encode(['status' => 'error', 'message' => 'Data kosong']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    foreach ($data_barang as $item) {
        $nama = mysqli_real_escape_string($conn, $item['nama']);
        $qty = (int)$item['jumlah'];
        $ket = mysqli_real_escape_string($conn, $item['keterangan']);
        $tgl = date('Y-m-d');

        $cek = mysqli_query($conn, "SELECT id_barang, jumlah_stok FROM Barang WHERE nama_barang = '$nama'");
        $row = mysqli_fetch_assoc($cek);

        if (!$row) throw new Exception("Barang '$nama' tidak ada.");

        $id_barang = $row['id_barang'];
        if ($row['jumlah_stok'] < $qty) throw new Exception("Stok '$nama' kurang.");

        mysqli_query($conn, "UPDATE Barang SET jumlah_stok = jumlah_stok - $qty WHERE id_barang = '$id_barang'");
        mysqli_query($conn, "INSERT INTO Barang_Keluar (id_barang, tanggal, jumlah, keterangan) VALUES ('$id_barang', '$tgl', '$qty', '$ket')");
        mysqli_query($conn, "INSERT INTO Riwayat (id_barang, tanggal, nama_barang, jumlah_keluar, keterangan) VALUES ('$id_barang', '$tgl', '$nama', '$qty', '$ket')");
    }
    mysqli_commit($conn);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>