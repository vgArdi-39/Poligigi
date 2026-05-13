document.addEventListener('DOMContentLoaded', function () {
    const navItems = document.querySelectorAll('.nav-item');
    //Logika Klik Menu
    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            if (this.getAttribute('href') === '#' || this.getAttribute('href') === '') {
                e.preventDefault();

                // Hapus class active dari semua item
                navItems.forEach(nav => nav.classList.remove('active'));

                // Tambahkan class active ke item yang baru diklik
                this.classList.add('active');
            }
        });
    });
    //Konfirmasi Logout
    const logoutBtn = document.querySelector('.logout');
    if (logoutBtn) {
        logoutBtn.onclick = function (e) {
            const yakin = confirm("Apakah anda yakin ingin keluar dari sistem?");
            if (!yakin) {
                e.preventDefault();
            }
        };
    }
    console.log("Dashboard Script Active with Smooth Transitions!");
});