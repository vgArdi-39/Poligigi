function openTambah() {
    document.getElementById('modal-title').textContent = 'Tambah Barang';
    document.getElementById('form-action').value = 'tambah';
    document.getElementById('form-id').value     = '';
    document.getElementById('input-nama').value  = '';
    document.getElementById('input-satuan').value = '';
    document.getElementById('modal-form').style.display = 'flex';
}

function openEdit(btn, id) {
    const row = btn.closest('tr');
    const cells = row.querySelectorAll('td');
    document.getElementById('modal-title').textContent = 'Edit Barang';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('form-id').value     = id;
    document.getElementById('input-nama').value  = cells[0].textContent.trim();
    document.getElementById('input-satuan').value = cells[2].textContent.trim();
    document.getElementById('modal-form').style.display = 'flex';
}

function openHapus(btn, id) {
    document.getElementById('hapus-id').value = id;
    document.getElementById('hapus-nama').textContent = btn.closest('tr').cells[0].textContent.trim();
    document.getElementById('modal-hapus').style.display = 'flex';
}

function closeModal() {
    document.getElementById('modal-form').style.display = 'none';
}

function closeHapus() {
    document.getElementById('modal-hapus').style.display = 'none';
}