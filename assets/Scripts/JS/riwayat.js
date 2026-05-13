function openExportModal(type) {
    const modal = document.getElementById('modalExport');
    const typeText = document.getElementById('exportTypeText');
    const formatInput = document.getElementById('exportFormat');

    modal.style.display = 'flex';
    typeText.innerText = type;
    formatInput.value = type.toLowerCase();
}

function closeExportModal() {
    document.getElementById('modalExport').style.display = 'none';
}

// Close if clicked outside
window.onclick = function(event) {
    const modal = document.getElementById('modalExport');
    if (event.target == modal) {
        closeExportModal();
    }
}