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
$error   = '';

// ── POST handler (first's prepared-statement approach) ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $id_barang  = intval($_POST['id_barang'] ?? 0);
    $jumlah     = intval($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $id_admin   = $session->get('id_admin');

    if (empty($id_admin)) {
        $error = "Session error: silakan login ulang.";
    } elseif ($id_barang > 0 && $jumlah > 0) {
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

// ── Barang list for datalist + select ───────────────────────────────────────
$query_produk = $conn->query("SELECT id_barang, nama_barang, satuan FROM Barang ORDER BY nama_barang ASC");
$barang_rows  = [];
while ($r = $query_produk->fetch_assoc()) {
    $barang_rows[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Masuk - Polinventory</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">
    <link rel="stylesheet" href="assets/css/barang_masuk.css">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <header class="content-header">
            <h3>Barang Masuk</h3>
        </header>

        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Input Form (second's UI — datalist + cart; first's form fields) -->
        <section class="card-input">
            <h4 class="card-title">Katalog Barang</h4>
            <form id="formMasuk">
                <!-- Searchable select via Choices.js (first) + datalist fallback (second) -->
                <div class="input-group">
                    <select name="id_barang" id="select-barang-masuk" required>
                        <option value="">Pilih Barang</option>
                        <?php foreach ($barang_rows as $row): ?>
                            <option value="<?= $row['id_barang'] ?>"
                                    data-satuan="<?= htmlspecialchars($row['satuan']) ?>">
                                <?= htmlspecialchars($row['nama_barang']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <input type="number" id="jumlah" placeholder="Jumlah" min="1" required>
                </div>
                <div class="input-group">
                    <input type="text" id="satuan_auto" placeholder="Satuan" readonly>
                </div>
                <div class="input-group">
                    <input type="text" id="keterangan" placeholder="Keterangan (Boleh Kosong)">
                </div>
                <div class="button-group">
                    <button type="button" class="btn-main" onclick="tambahKeKeranjang()">Tambahkan ke Keranjang</button>
                    <button type="button" class="btn-secondary" onclick="openModalImport()">Import Excel</button>
                </div>
            </form>
        </section>

        <!-- Cart Table (second's UI) -->
        <section class="table-section">
            <h4 class="table-title">Barang yang Baru Ditambahkan</h4>
            <table class="data-table" id="tabelKeranjang">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" style="text-align:center;">Belum ada barang ditambahkan.</td></tr>
                </tbody>
            </table>

            <!-- Hidden form submits to first's POST handler -->
            <form method="POST" id="confirm-form" action="">
                <input type="hidden" name="id_barang"  id="hidden-id-barang">
                <input type="hidden" name="jumlah"     id="hidden-jumlah">
                <input type="hidden" name="keterangan" id="hidden-keterangan">
                <div class="action-footer">
                <button type="button" class="btn-confirm" onclick="konfirmasiSimpan()">Confirm</button>
                </div>
            </form>
        </section>
    </main>
</div>

<!-- Import Excel Modal (second's UI) -->
<div class="modal-overlay" id="modalImport" style="display:none;">
    <div class="modal-card">
        <div class="modal-header">
            <h4>Import File Excel</h4>
            <button class="close-modal" onclick="closeModalImport()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom:15px;font-size:14px;">
                Pilih file Excel (.xlsx) dengan header: <b>Nama, Jumlah, Satuan</b>
            </p>
            <input type="file" id="fileExcelInput" accept=".xlsx,.xls" class="input-file-modal">
            <button type="button" class="btn-execute-import" onclick="prosesImportExcel()">Mulai Import</button>
        </div>
    </div>
</div>

<script>
    // ── Choices.js searchable select ────────────────────────────────────────
    const choicesMasuk = new Choices('#select-barang-masuk', {
        searchEnabled: true,
        searchPlaceholderValue: 'Cari barang...',
        itemSelectText: '',
        shouldSort: false,
        allowHTML: false
    });

    // Auto-fill satuan when a barang is selected
    document.getElementById('select-barang-masuk').addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        document.getElementById('satuan_auto').value = selected.dataset.satuan || '';
    });

    // ── Cart logic ──────────────────────────────────────────────────────────
    let cart = [];

    function tambahKeKeranjang() {
        const id_barang  = choicesMasuk.getValue(true);
        const jumlah     = parseInt(document.getElementById('jumlah').value);
        const keterangan = document.getElementById('keterangan').value.trim();
        const satuan     = document.getElementById('satuan_auto').value.trim();
        const selectEl   = document.getElementById('select-barang-masuk');
        const nama       = selectEl.options[selectEl.selectedIndex]?.text || '';

        if (!id_barang || !jumlah || jumlah < 1) {
            alert('Pilih barang dan masukkan jumlah yang valid.');
            return;
        }

        const existing = cart.find(c => c.id_barang === id_barang);
        if (existing) {
            existing.jumlah += jumlah;
        } else {
            cart.push({ id_barang, nama, jumlah, satuan, keterangan });
        }

        renderCart();
        choicesMasuk.setChoiceByValue('');
        document.getElementById('jumlah').value      = '';
        document.getElementById('keterangan').value  = '';
        document.getElementById('satuan_auto').value = '';
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function renderCart() {
        const tbody = document.querySelector('#tabelKeranjang tbody');
        tbody.innerHTML = '';

        if (cart.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Belum ada barang ditambahkan.</td></tr>';
            return;
        }

        cart.forEach((item, i) => {
            tbody.innerHTML += `
                <tr>
                    <td>${item.nama}</td>
                    <td>${item.satuan}</td>
                    <td>${item.jumlah}</td>
                    <td>${item.keterangan || '-'}</td>
                    <td style="text-align:center;">
                        <button class="btn-delete" onclick="removeFromCart(${i})">Hapus</button>
                    </td>
                </tr>`;
        });
    }

    // On confirm: populate hidden fields for the first item in cart
    // For multi-item, each item submits via proses_barang_masuk.php via fetch (see below)
    function konfirmasiSimpan() {
        if (cart.length === 0) {
            alert('Keranjang masih kosong!');
            return;
        }

        // Send all cart items to proses_barang_masuk.php (second's approach)
        fetch('proses_barang_masuk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(cart.map(c => ({
                nama:   c.nama,
                qty:    c.jumlah,
                satuan: c.satuan,
                ket:    c.keterangan
            })))
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Barang berhasil disimpan!');
                cart = [];
                renderCart();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
    }

    // ── Import Excel (second's logic) ───────────────────────────────────────
    function openModalImport()  { document.getElementById('modalImport').style.display = 'flex'; }
    function closeModalImport() { document.getElementById('modalImport').style.display = 'none'; }

    function prosesImportExcel() {
        const file = document.getElementById('fileExcelInput').files[0];
        if (!file) { alert('Pilih file terlebih dahulu.'); return; }

        const reader = new FileReader();
        reader.onload = function (e) {
            const wb   = XLSX.read(e.target.result, { type: 'binary' });
            const ws   = wb.Sheets[wb.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(ws);

            rows.forEach(row => {
                cart.push({
                    id_barang:   '',
                    nama:        row['Nama']   || '',
                    jumlah:      parseInt(row['Jumlah']) || 0,
                    satuan:      row['Satuan'] || '',
                    keterangan:  ''
                });
            });

            renderCart();
            closeModalImport();
        };
        reader.readAsBinaryString(file);
    }
</script>
</body>
</html>