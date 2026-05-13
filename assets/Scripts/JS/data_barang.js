function bukaPopupTambah() {
    document.getElementById('modalTambah').style.display = 'flex';
}

function tutupPopupTambah() {
    document.getElementById('modalTambah').style.display = 'none';
}

function bukaPopupEdit(id, nama, satuan) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_satuan').value = satuan;
    document.getElementById('modalEdit').style.display = 'flex';
}

function tutupPopupEdit() {
    document.getElementById('modalEdit').style.display = 'none';
}

function konfirmasiHapus(id, nama) {
    if (confirm("Hapus barang: " + nama + "?")) {
        window.location.href = "proses_hapus.php?id=" + id;
    }
}

window.onclick = function(event) {
    if (event.target == document.getElementById('modalTambah')) tutupPopupTambah();
    if (event.target == document.getElementById('modalEdit')) tutupPopupEdit();
}