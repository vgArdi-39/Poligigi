let daftarBarang = [];

function setSatuan() {
    const val = document.getElementById('nama_barang').value;
    const opts = document.getElementById('data_produk').options;
    const satuanInput = document.getElementById('satuan');

    for (let i = 0; i < opts.length; i++) {
        if (opts[i].value === val) {
            satuanInput.value = opts[i].getAttribute('data-satuan');
            return;
        }
    }
}

window.addEventListener('beforeunload', function (e) {
    if (daftarBarang.length > 0) {
        const message = "Data belum dikonfirmasi!";
        e.returnValue = message;
        return message;
    }
});

function tambahAtauUpdate() {
    const index = document.getElementById('edit_index').value;
    const nama = document.getElementById('nama_barang').value;
    const jumlah = document.getElementById('jumlah_barang').value;
    const satuan = document.getElementById('satuan').value;
    const keterangan = document.getElementById('keterangan').value || "-";

    if (nama === "" || jumlah === "" || satuan === "") {
        alert("Wajib diisi!");
        return;
    }

    if (index === "-1") {
        daftarBarang.push({ nama, satuan, jumlah, keterangan });
    } else {
        daftarBarang[index] = { nama, satuan, jumlah, keterangan };
        resetForm();
    }

    updateTabel();
    document.getElementById('formKatalog').reset();
    document.getElementById('nama_barang').focus();
}

function updateTabel() {
    const tbody = document.querySelector('#tabelPermintaan tbody');
    tbody.innerHTML = '';

    if (daftarBarang.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">Daftar Kosong</td></tr>';
        return;
    }

    daftarBarang.forEach((item, index) => {
        tbody.innerHTML += `<tr>
            <td>${item.nama.toUpperCase()}</td>
            <td>${item.satuan.toUpperCase()}</td>
            <td>${item.jumlah}</td>
            <td>${item.keterangan}</td>
            <td>
                <div class="action-buttons">
                    <button onclick="editBaris(${index})" style="background:#4988C4; color:white; border:none; padding:5px 10px; border-radius:5px;">Edit</button>
                    <button onclick="hapusBaris(${index})" style="background:#0F2854; color:white; border:none; padding:5px 10px; border-radius:5px;">Hapus</button>
                </div>
            </td>
        </tr>`;
    });
}

function editBaris(index) {
    const item = daftarBarang[index];
    document.getElementById('edit_index').value = index;
    document.getElementById('nama_barang').value = item.nama;
    document.getElementById('jumlah_barang').value = item.jumlah;
    document.getElementById('satuan').value = item.satuan;
    document.getElementById('keterangan').value = item.keterangan === "-" ? "" : item.keterangan;
    document.getElementById('btn-submit').innerText = "Update";
    document.getElementById('btn-cancel').style.display = "block";
}

function resetForm() {
    document.getElementById('edit_index').value = "-1";
    document.getElementById('formKatalog').reset();
    document.getElementById('btn-submit').innerText = "Tambahkan";
    document.getElementById('btn-cancel').style.display = "none";
}

function hapusBaris(index) {
    if (confirm("Hapus?")) {
        daftarBarang.splice(index, 1);
        updateTabel();
    }
}

function simpanKeDatabase() {
    if (daftarBarang.length === 0) return alert("Kosong!");

    if (confirm("Konfirmasi pengeluaran?")) {
        fetch('proses_barang_keluar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(daftarBarang)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Berhasil!");
                daftarBarang = [];
                updateTabel();
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(err => alert("Error sistem"));
    }
}

function konfirmasiEkspor(tipe) {
    if (daftarBarang.length === 0) return alert("Kosong!");
    tipe === 'pdf' ? exportKePDF() : exportKeExcel();
}

function exportKeExcel() {
    const namaUser = document.getElementById('nama_user_login').innerText;
    const data = [
        ["FAKTUR PERMINTAAN"], ["POLIKLINIK GIGI"],
        ["Petugas: " + namaUser], ["Tanggal: " + new Date().toLocaleDateString()],
        [], ["NO", "NAMA BARANG", "JUMLAH", "SATUAN", "KETERANGAN"]
    ];
    daftarBarang.forEach((item, i) => {
        data.push([i + 1, item.nama.toUpperCase(), item.jumlah, item.satuan.toUpperCase(), item.keterangan]);
    });
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Data");
    XLSX.writeFile(wb, `Faktur_${namaUser}.xlsx`);
}

function exportKePDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const namaUser = document.getElementById('nama_user_login').innerText;
    doc.setFontSize(16).setTextColor(15, 40, 84).text("POLIKLINIK GIGI", 105, 15, { align: "center" });
    doc.setDrawColor(15, 40, 84).line(14, 25, 196, 25);
    const rows = daftarBarang.map((item, i) => [i + 1, item.nama.toUpperCase(), item.jumlah, item.satuan.toUpperCase(), item.keterangan]);
    doc.autoTable({
        head: [['NO', 'NAMA BARANG', 'QTY', 'SATUAN', 'KETERANGAN']],
        body: rows,
        startY: 35,
        theme: 'grid',
        headStyles: { fillColor: [15, 40, 84] }
    });
    doc.save(`Faktur_${namaUser}.pdf`);
}