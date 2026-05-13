<?php
session_start();
require_once 'assets/config.php';
/** @var mysqli $conn */

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Data kosong']);
    exit;
}

// Get id_admin from session
$id_admin = $_SESSION['id_admin'] ?? null;
if (!$id_admin) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired, silakan login ulang.']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    foreach ($data as $item) {
        $nama   = mysqli_real_escape_string($conn, $item['nama']);
        $qty    = (int)$item['qty'];
        $ket    = mysqli_real_escape_string($conn, $item['ket']);

        // 1. Find id_barang by name
        $res = mysqli_query($conn, "SELECT id_barang FROM barang WHERE nama_barang = '$nama' LIMIT 1");

        if (mysqli_num_rows($res) === 0) {
            throw new Exception("Barang '$nama' tidak terdaftar di database.");
        }

        $id_barang = mysqli_fetch_assoc($res)['id_barang'];

        // 2. Insert into barang_masuk (stock is calculated automatically by v_stok view)
        $sql = "INSERT INTO barang_masuk (id_barang, id_admin, jumlah, keterangan) 
                VALUES ($id_barang, $id_admin, $qty, '$ket')";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Gagal menyimpan: " . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}