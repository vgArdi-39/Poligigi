<?php 
$current_page = 'barang-keluar';
require_once "assets/config.php";
require_once "assets/session.php";

$session = new Session();
if ($session->get('logged_in') !== true) {
    header("Location: index.php");
    exit();
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $items    = json_decode($_POST['cart_data'], true);
    $id_admin = $session->get('id_admin');

    if (empty($id_admin)) {
        $error = "Session error: silakan login ulang.";
    } elseif (empty($items)) {
        $error = "Keranjang kosong!";
    } else {
        // Step 1: Validate ALL items before touching the DB
        foreach ($items as $item) {
            $id_barang = intval($item['id_barang']);
            $jumlah    = intval($item['jumlah']);

            $stok_stmt = $conn->prepare("SELECT jumlah_stok FROM V_Stok WHERE id_barang = ?");
            $stok_stmt->bind_param("i", $id_barang);
            $stok_stmt->execute();
            $stok = $stok_stmt->get_result()->fetch_assoc();
            $stok_stmt->close();

            if (!$stok || $stok['jumlah_stok'] < $jumlah) {
                $error = "Stok tidak mencukupi untuk salah satu barang!";
                break;
            }
        }

        // Step 2: Insert only if all items passed validation
        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO Permintaan_Barang (id_admin) VALUES (?)");
            $stmt->bind_param("i", $id_admin);

            if (!$stmt->execute()) {
                $error = "Gagal membuat permintaan: " . $stmt->error;
            } else {
                $id_permintaan = $conn->insert_id;
                $stmt->close();

                $keluar_stmt = $conn->prepare(
                    "INSERT INTO Barang_Keluar (id_permintaan, id_barang, jumlah, keterangan) VALUES (?, ?, ?, ?)"
                );
                $detail_stmt = $conn->prepare(
                    "INSERT INTO Detail_Permintaan (id_permintaan, id_barang, jumlah) VALUES (?, ?, ?)"
                );

                foreach ($items as $item) {
                    $id_barang  = intval($item['id_barang']);
                    $jumlah     = intval($item['jumlah']);
                    $keterangan = trim($item['keterangan'] ?? '');

                    $keluar_stmt->bind_param("iiis", $id_permintaan, $id_barang, $jumlah, $keterangan);
                    $keluar_stmt->execute();

                    $detail_stmt->bind_param("iii", $id_permintaan, $id_barang, $jumlah);
                    $detail_stmt->execute();
                }

                $keluar_stmt->close();
                $detail_stmt->close();

                $success = "Barang berhasil dikeluarkan!";
            }
        }
    }
}

// Fetch barang list (only items with stock > 0)
$barang_list = [];
$result = $conn->query(
    "SELECT id_barang, nama_barang, jumlah_stok FROM V_Stok WHERE jumlah_stok > 0 ORDER BY nama_barang ASC"
);
while ($row = $result->fetch_assoc()) {
    $barang_list[] = $row;
}
$barang_json = json_encode($barang_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
</head>
<body>

<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <h1>Barang Keluar</h1>

        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="itemOut-Container">
            <select id="select-barang" class="itemOut-fields">
                <option value="">Pilih Barang</option>
                <?php foreach ($barang_list as $row): ?>
                    <option value="<?= $row['id_barang'] ?>">
                        <?= htmlspecialchars($row['nama_barang']) ?> (Stok: <?= $row['jumlah_stok'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" id="input-jumlah" class="itemOut-fields" placeholder="Jumlah" min="1">
            <input type="text"   id="input-keterangan" class="itemOut-fields" placeholder="Keterangan (opsional)">
            <button type="button" class="itemOut-btn" onclick="addToCart()">Tambahkan</button>

            <table id="cart-table">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>Hapus</th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <tr><td colspan="4">Belum ada barang ditambahkan.</td></tr>
                </tbody>
            </table>

            <form method="POST" id="confirm-form">
                <input type="hidden" name="cart_data" id="cart-data-input">
                <button type="submit" name="confirm" value="confirm" class="Confirm-btn">Confirm</button>
            </form>
        </div>
    </main>
</div>

<script>
    const barangData = <?= $barang_json ?>;
    let cart = [];

    const choicesSelect = new Choices('#select-barang', {
        searchEnabled: true,
        searchPlaceholderValue: 'Cari barang...',
        itemSelectText: '',
        shouldSort: false,
        allowHTML: false
    });

    function addToCart() {
        const id_barang_str = choicesSelect.getValue(true);
        const jumlah        = parseInt(document.getElementById('input-jumlah').value);
        const keterangan    = document.getElementById('input-keterangan').value.trim();

        if (!id_barang_str || !jumlah || jumlah < 1) {
            alert('Pilih barang dan masukkan jumlah yang valid.');
            return;
        }

        const id_barang = parseInt(id_barang_str);
        const barang    = barangData.find(b => parseInt(b.id_barang) === id_barang);

        if (!barang) {
            alert('Barang tidak ditemukan.');
            return;
        }

        const stok     = parseInt(barang.jumlah_stok);
        const existing = cart.find(c => c.id_barang === id_barang);
        const total    = (existing ? existing.jumlah : 0) + jumlah;

        if (total > stok) {
            alert('Jumlah melebihi stok tersedia (' + stok + ')!');
            return;
        }

        if (existing) {
            existing.jumlah = total;
        } else {
            cart.push({ id_barang, nama_barang: barang.nama_barang, jumlah, keterangan, stok });
        }

        renderCart();
        choicesSelect.setChoiceByValue('');
        document.getElementById('input-jumlah').value     = '';
        document.getElementById('input-keterangan').value = '';
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById('cart-body');
        tbody.innerHTML = '';

        if (cart.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4">Belum ada barang ditambahkan.</td></tr>';
            document.getElementById('cart-data-input').value = '';
            return;
        }

        cart.forEach((item, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${item.nama_barang}</td>
                    <td>${item.jumlah}</td>
                    <td>${item.keterangan || '-'}</td>
                    <td><button type="button" onclick="removeFromCart(${index})">Hapus</button></td>
                </tr>`;
        });

        document.getElementById('cart-data-input').value = JSON.stringify(cart);
    }
</script>
</body>
</html>
