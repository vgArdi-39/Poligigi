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

                foreach ($items as $item) {
                    $id_barang  = intval($item['id_barang']);
                    $jumlah     = intval($item['jumlah']);
                    $keterangan = trim($item['keterangan'] ?? '');

                    $keluar_stmt->bind_param("iiis", $id_permintaan, $id_barang, $jumlah, $keterangan);
                    $keluar_stmt->execute();
                }

                $keluar_stmt->close();

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
    <title>Barang Keluar - Poli Gigi</title>
    <link rel="icon" href="assets/img/logo_polije.png">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">
    <link rel="stylesheet" href="assets/css/barang_keluar.css">
    <link href="assets/css/choices.min.css" rel="stylesheet">
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
        <section class="table-container" style="min-height: auto;">
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

<div id="pdf-template" font-family="Arial, sans-serif;">

        <style>
        #pdf-template table,
        #pdf-template th,
        #pdf-template td,
        #pdf-template tr {
            border-radius: 0 !important;
        }
    </style>

    <!-- PDF template-->

    <div class="judul" style="text-align: center; display: flex; flex-direction: row; align-items: center; gap: 15px;justify-content: center;">
    <img src="assets/img/LogoPOLIJE.png" alt="Logo" style="width: 80px; height: auto; margin-bottom: 10px;">
    
    <div style="display: flex; flex-direction: column; column-height: 1.5;justify-content: center; align-items: center;">
        <h4>KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h4>
        <h4>POLITEKNIK NEGERI JEMBER</h4>
        <h4>KLINIK PRATAMA</h4>
        <h5>Jalan Mastrip Jember Kotak Pos 164, 68101 Telp.(0331) 333532-34 Faks 333531</h5>
        <h5>Email: klinikpratama@polije.ac.id</h5>
    </div>
    </div>
    <hr style="border: 1px solid black; margin-top: 20px;">
    <table style="width:100%; border: 1px solid black; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background:#ffffff; color:black; border: 1px solid black; border-collapse: collapse;">
                <th style="padding:8px; border: 1px solid black;">No</th>
                <th style="padding:8px; border: 1px solid black;">Nama Barang</th>
                <th style="padding:8px; border: 1px solid black;">Satuan</th>
                <th style="padding:8px; border: 1px solid black;">Jumlah</th>
                <th style="padding:8px; border: 1px solid black;">Keterangan</th>
            </tr>
        </thead>
        <tbody id="pdf-body">
            <!-- filled by JS before export -->
        </tbody>
    </table>

    <div class="ttd_field" style="display: flex; flex-direction: row; justify-content: center; gap: 10%;">

        <div class="ttd_item" style="margin-top: 50px; display: flex; flex-direction: column;">
            <h5>Menyetujui,</h5>
            <h5>Farmasi,</h5>
            <div style="height: 80px;"></div>
            <h5>..............................................</h5>
        </div>
        
        <div class="ttd_item" style="margin-top: 50px; display: flex; flex-direction: column;">
        <h5>Mengetahui,</h5>
        <h5><br></h5>
        <div style="height: 80px;"></div>
        <h5>drg...........................................</h5>
        </div>

        <div class="ttd_item" style="margin-top: 50px; display: flex; flex-direction: column;">
            <h5>Jember, _____________________</h5>
            <h5>Pemohon</h5>
            <div style="height: 80px;"></div>
            <h5>..............................................</h5>
            <h5>NIP/NRP.</h5>
        </div>
        
    </div>
</div>



</body>
</html>



<script src="assets/scripts/JS/choices.min.js"></script>
<script src="assets/scripts/JS/html2pdf.bundle.min.js"></script>
<script src="assets/scripts/JS/xlsx.full.min.js"></script>
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
        // fill the template
        document.getElementById('pdf-body').innerHTML = cart.map((i, index) => `
            <tr>
                <td style="padding:8px; border:1px solid black; border-radius: 0px;">${index + 1}</td>
                <td style="padding:8px; border:1px solid black; border-radius: 0px;">${i.nama_barang}</td>
                <td style="padding:8px; border:1px solid black; border-radius: 0px;">${i.satuan}</td>
                <td style="padding:8px; border:1px solid black; border-radius: 0px;">${i.jumlah}</td>
                <td style="padding:8px; border:1px solid black; border-radius: 0px;">${i.keterangan || '-'}</td>
            </tr>
        `).join('');

        const el = document.getElementById('pdf-template');
        el.style.display = 'block';

        html2pdf().set({
            filename: 'barang_keluar.pdf',
            margin: 10,
            jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' },
            html2canvas: { scale: 2, useCORS: true }
        }).from(el).save().then(() => {
            el.style.display = 'none'; // hide again after export
        });
    } else if (type === 'excel') {
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