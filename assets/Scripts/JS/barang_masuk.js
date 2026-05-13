let keranjang = [];

function setSatuan() {
    const val = document.getElementById('nama_barang').value;
    const opts = document.getElementById('data_produk').options;
    const satuanInput = document.getElementById('satuan_auto');

    for (let i = 0; i < opts.length; i++) {
        if (opts[i].value === val) {
            satuanInput.value = opts[i].getAttribute('data-satuan');
            return;
        }
    }
}

function openModalImport() {
    document.getElementById('modalImport').style.display = 'flex';
}

function closeModalImport() {
    document.getElementById('modalImport').style.display = 'none';
    document.getElementById('fileExcelInput').value = '';
}

function tambahKeKeranjang() {
    const nama = document.getElementById('nama_barang').value;
    const qty = document.getElementById('jumlah').value;
    const satuan = document.getElementById('satuan_auto').value;
    const ket = document.getElementById('keterangan').value || "-";

    if (!nama || !qty || !satuan) {
        alert("Nama, Jumlah, dan Satuan wajib diisi!");
        return;
    }

    keranjang.push({ nama, satuan, qty, ket });
    renderTabel();
    document.getElementById('formMasuk').reset();
    document.getElementById('nama_barang').focus();
}

function prosesImportExcel() {
    const fileInput = document.getElementById('fileExcelInput');
    const file = fileInput.files[0];

    if (!file) {
        alert("Pilih file Excel terlebih dahulu!");
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(sheet);

            if (jsonData.length === 0) {
                alert("File Excel kosong!");
                return;
            }

            let validCount = 0;
            jsonData.forEach(row => {
                const n = row.Nama || row['Nama Barang'] || row.nama || row['NAMA BARANG'];
                const j = row.Jumlah || row.jumlah || row.Qty || row.qty || row['JUMLAH'];
                const s = row.Satuan || row.satuan || row['SATUAN'];
                const k = row.Keterangan || row.keterangan || "-";

                if (n && j && s) {
                    keranjang.push({
                        nama: n.toString(),
                        qty: j,
                        satuan: s.toString(),
                        ket: k.toString()
                    });
                    validCount++;
                }
            });

            if (validCount > 0) {
                renderTabel();
                alert(`Berhasil mengimpor ${validCount} data.`);
                closeModalImport();
            } else {
                alert("Format kolom Excel tidak sesuai!");
            }
        } catch (err) {
            alert("Gagal membaca file!");
            console.error(err);
        }
    };
    reader.readAsArrayBuffer(file);
}

function renderTabel() {
    const tbody = document.querySelector('#tabelKeranjang tbody');
    tbody.innerHTML = '';

    if (keranjang.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color: #999;">Keranjang masih kosong.</td></tr>';
        return;
    }

    keranjang.forEach((item, index) => {
        tbody.innerHTML += `
            <tr>
                <td>${item.nama.toUpperCase()}</td>
                <td>${item.satuan.toUpperCase()}</td>
                <td>${item.qty}</td>
                <td>${item.ket}</td>
                <td style="text-align:center;">
                    <button class="btn-delete" onclick="hapusItem(${index})">Hapus</button>
                </td>
            </tr>`;
    });
}

function hapusItem(index) {
    keranjang.splice(index, 1);
    renderTabel();
}

function konfirmasiSimpan() {
    if (keranjang.length === 0) return alert("Keranjang kosong!");
    if (confirm("Konfirmasi penyimpanan barang masuk? Stok akan bertambah otomatis.")) {
        fetch('proses_barang_masuk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(keranjang)
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("Berhasil disimpan!");
                    keranjang = [];
                    renderTabel();
                } else {
                    alert("Gagal: " + data.message);
                }
            })
            .catch(err => {
                alert("Terjadi kesalahan sistem.");
                console.error(err);
            });
    }
}