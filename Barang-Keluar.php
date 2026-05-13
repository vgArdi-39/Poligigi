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

// ── POST handler (first's full validation + transaction approach) ─────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $items    = json_decode($_POST['cart_data'], true);
    $id_admin = $session->get('id_admin');

    if (empty($id_admin)) {
        $error = "Session error: silakan login ulang.";
    } elseif (empty($items)) {
        $error = "Keranjang kosong!";
    } else {
        // Step 1: validate ALL items before touching DB
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

        // Step 2: insert only if all items passed validation
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

// ── Fetch barang list (only items with stock > 0) ────────────────────────────
$barang_list = [];
$result = $conn->query(
    "SELECT id_barang, nama_barang, satuan, jumlah_stok FROM V_Stok WHERE jumlah_stok > 0 ORDER BY nama_barang ASC"
);
while ($row = $result->fetch_assoc()) {
    $barang_list[] = $row;
}
$barang_json = json_encode($barang_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar - Polinventory</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">
    <link rel="stylesheet" href="assets/css/barang_keluar.css">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <!-- PDF & Excel export (second's libraries) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <header class="content-header">
            <h3>Barang Keluar</h3>
        </header>

        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <section class="request-container">
            <!-- Form Card (second's UI layout + first's id-based select) -->
            <div class="card-form">
                <h4 class="card-title">Katalog Barang</h4>
                <div class="input-group-custom">
                    <select id="select-barang" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach ($barang_list as $row): ?>
                            <option value="<?= $row['id_barang'] ?>"
                                    data-satuan="<?= htmlspecialchars($row['satuan']) ?>"
                                    data-stok="<?= $row['jumlah_stok'] ?>">
                                <?= htmlspecialchars($row['nama_barang']) ?> (Stok: <?= $row['jumlah_stok'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group-custom">
                    <input type="number" id="input-jumlah" placeholder="Jumlah" min="1">
                </div>
                <div class="input-group-custom">
                    <input type="text" id="input-satuan" placeholder="Satuan" readonly>
                </div>
                <div class="input-group-custom">
                    <input type="text" id="input-keterangan" placeholder="Keterangan (Boleh Kosong)">
                </div>
                <button type="button" class="btn-add-list" onclick="addToCart()">Tambahkan ke Daftar</button>
            </div>

            <!-- Summary Card (second's UI) -->
            <div class="card-summary">
                <h4 class="card-title">Total Permintaan</h4>
                <div class="summary-content">
                    <span class="total-number" id="total_jenis">0</span>
                    <span class="total-label">Jenis Barang</span>
                </div>
            </div>
        </section>

        <!-- Cart Table + confirm form (first's hidden cart_data approach) -->
        <section class="table-container">
            <h4 class="card-title">Daftar Permintaan</h4>
            <table class="data-table" id="tabelPermintaan">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th class="th-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <tr><td colspan="5">Belum ada barang ditambahkan.</td></tr>
                </tbody>
            </table>

            <form method="POST" id="confirm-form">
                <input type="hidden" name="cart_data" id="cart-data-input">
                <div class="action-footer">
                    <div class="export-buttons">
                        <button type="button" class="btn-pdf"   onclick="konfirmasiEkspor('pdf')">Cetak PDF</button>
                        <button type="button" class="btn-excel" onclick="konfirmasiEkspor('excel')">Cetak Excel</button>
                    </div>
                    <button type="submit" name="confirm" value="confirm" class="btn-confirm">Confirm</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
    const barangData = <?= $barang_json ?>;
    let cart = [];

    // ── Choices.js ────────────────────────────────────────────────────────────
    const choicesSelect = new Choices('#select-barang', {
        searchEnabled: true,
        searchPlaceholderValue: 'Cari barang...',
        itemSelectText: '',
        shouldSort: false,
        allowHTML: false
    });

    // Auto-fill satuan
    document.getElementById('select-barang').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        document.getElementById('input-satuan').value = opt.dataset.satuan || '';
    });

    // ── Cart functions ────────────────────────────────────────────────────────
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
        if (!barang) { alert('Barang tidak ditemukan.'); return; }

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
            cart.push({ id_barang, nama_barang: barang.nama_barang, satuan: barang.satuan, jumlah, keterangan });
        }

        renderCart();
        choicesSelect.setChoiceByValue('');
        document.getElementById('input-jumlah').value     = '';
        document.getElementById('input-keterangan').value = '';
        document.getElementById('input-satuan').value     = '';
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById('cart-body');
        tbody.innerHTML = '';

        document.getElementById('total_jenis').textContent = cart.length;

        if (cart.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">Belum ada barang ditambahkan.</td></tr>';
            document.getElementById('cart-data-input').value = '';
            return;
        }

        cart.forEach((item, i) => {
            tbody.innerHTML += `
                <tr>
                    <td>${item.nama_barang}</td>
                    <td>${item.satuan}</td>
                    <td>${item.jumlah}</td>
                    <td>${item.keterangan || '-'}</td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn-delete-action" onclick="removeFromCart(${i})">Hapus</button>
                        </div>
                    </td>
                </tr>`;
        });

        document.getElementById('cart-data-input').value = JSON.stringify(cart);
    }

    // ── Export (second's logic) ───────────────────────────────────────────────
    function konfirmasiEkspor(type) {
        if (cart.length === 0) { alert('Daftar permintaan masih kosong!'); return; }

        if (type === 'pdf') {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.text('Daftar Barang Keluar', 14, 15);
            doc.autoTable({
                startY: 22,
                head: [['Nama Barang', 'Satuan', 'Jumlah', 'Keterangan']],
                body: cart.map(i => [i.nama_barang, i.satuan, i.jumlah, i.keterangan || '-'])
            });
            doc.save('barang_keluar.pdf');
        } else {
            const ws   = XLSX.utils.json_to_sheet(cart.map(i => ({
                'Nama Barang': i.nama_barang, 'Satuan': i.satuan,
                'Jumlah': i.jumlah, 'Keterangan': i.keterangan || '-'
            })));
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Barang Keluar');
            XLSX.writeFile(wb, 'barang_keluar.xlsx');
        }
    }
</script>
</body>
</html>