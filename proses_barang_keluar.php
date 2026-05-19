<?php
session_start();
require_once 'assets/config.php';
header('Content-Type: application/json');

$input       = file_get_contents('php://input');
$data_barang = json_decode($input, true);

if (!$data_barang || empty($data_barang)) {
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
    // Create one permintaan_barang entry for this batch
    $stmt_pb = mysqli_prepare($conn, "INSERT INTO permintaan_barang (id_admin) VALUES (?)");
    mysqli_stmt_bind_param($stmt_pb, "i", $id_admin);
    if (!mysqli_stmt_execute($stmt_pb)) {
        throw new Exception("Gagal membuat permintaan: " . mysqli_stmt_error($stmt_pb));
    }
    $id_permintaan = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_pb);

    $stmt_keluar = mysqli_prepare($conn,
        "INSERT INTO barang_keluar (id_permintaan, id_barang, jumlah, keterangan) VALUES (?, ?, ?, ?)"
    );

    foreach ($data_barang as $item) {
        $id_barang = intval($item['id_barang'] ?? 0);
        $qty       = intval($item['jumlah']    ?? 0);
        $ket       = trim($item['keterangan']  ?? '');

        if ($id_barang < 1 || $qty < 1) {
            throw new Exception("Data tidak valid.");
        }

        // Check stock without using v_stok view
        $stok_result = mysqli_query($conn,
            "SELECT 
                COALESCE((SELECT SUM(jumlah) FROM barang_masuk WHERE id_barang=$id_barang),0) -
                COALESCE((SELECT SUM(jumlah) FROM barang_keluar WHERE id_barang=$id_barang),0)
             AS stok"
        );
        $stok = intval(mysqli_fetch_assoc($stok_result)['stok'] ?? 0);

        if ($stok < $qty) {
            $nama_result = mysqli_query($conn, "SELECT nama_barang FROM barang WHERE id_barang=$id_barang");
            $nama        = mysqli_fetch_assoc($nama_result)['nama_barang'] ?? "ID $id_barang";
            throw new Exception("Stok '$nama' tidak cukup. Tersedia: $stok.");
        }

        mysqli_stmt_bind_param($stmt_keluar, "iiis", $id_permintaan, $id_barang, $qty, $ket);
        if (!mysqli_stmt_execute($stmt_keluar)) {
            throw new Exception("Gagal menyimpan: " . mysqli_stmt_error($stmt_keluar));
        }
    }

    mysqli_stmt_close($stmt_keluar);
    mysqli_commit($conn);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}