<?php
session_start();
require_once 'assets/config.php';
/** @var mysqli $conn */

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || !is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Data kosong']);
    exit;
}

$id_admin = $_SESSION['id_admin'] ?? null;
if (!$id_admin) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired, silakan login ulang.']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO barang_masuk (id_barang, id_admin, jumlah, keterangan) VALUES (?, ?, ?, ?)"
    );

    foreach ($data as $item) {
        $id_barang  = intval($item['id_barang']  ?? 0);
        $jumlah     = intval($item['jumlah']     ?? 0);
        $keterangan = trim($item['keterangan']   ?? '');

        if ($id_barang < 1 || $jumlah < 1) {
            throw new Exception("Data tidak valid: id_barang=$id_barang, jumlah=$jumlah");
        }

        mysqli_stmt_bind_param($stmt, "iiis", $id_barang, $id_admin, $jumlah, $keterangan);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menyimpan: " . mysqli_stmt_error($stmt));
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($conn);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}