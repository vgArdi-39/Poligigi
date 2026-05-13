//Fungsi pindah halaman
function switchPage(pageId) {
    document.querySelectorAll('.container').forEach(container => {
        container.classList.add('hidden');
    });
    document.getElementById(pageId).classList.remove('hidden');
}

//Fungsi overlay password
function openPassword() {
    const username = document.getElementById('username_login').value;
    if (username === "") {
        document.getElementById('error-username_login').innerText = "Masukkan username terlebih dahulu!";
        return;
    }
    document.getElementById('error-username_login').innerText = "";
    document.getElementById('overlay-password').classList.remove('hidden');
}

function closeOverlay() {
    document.getElementById('overlay-password').classList.add('hidden');
}

async function handleLogin() {
    const username = document.getElementById('username_login').value;
    const password = document.getElementById('password-field').value;
    const errorDisplay = document.getElementById('error-password-field');

    // Reset pesan error
    errorDisplay.innerText = "";

    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);

    try {
        const response = await fetch('auth.php?action=login', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status === 'success') {
            window.location.href = 'dashboard.php';
        } else {
            // Menampilkan peringatan spesifik
            errorDisplay.innerText = result.message;
        }
    } catch (err) {
        errorDisplay.innerText = "Terjadi kesalahan koneksi server.";
    }
}

async function handleRegister() {
    //Ambil data
    const user = document.getElementById('reg_user').value;
    const email = document.getElementById('reg_email').value;
    const pEmail = document.getElementById('reg_pass_email').value;
    const pass = document.getElementById('reg_pass').value;
    const confirm = document.getElementById('reg_pass_confirm').value;

    //Cek Password
    if (pass !== confirm) {
        document.getElementById('error-reg_pass_confirm').innerText = "Konfirmasi password tidak cocok!";
        return;
    }

    const formData = new FormData();
    formData.append('username', user);
    formData.append('email', email);
    formData.append('pass_email', pEmail);
    formData.append('password', pass);

    try {
        const response = await fetch('auth.php?action=register', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        console.log("Respon Server:", text); 
        
        const result = JSON.parse(text);

        if (result.status === 'success') {
            alert(result.message);
            switchPage('page-login');
        } else {
            alert(result.message);
        }
    } catch (err) {
        console.error("Error Detail:", err);
        alert("Gagal terhubung ke server. Cek console (F12) untuk detail.");
    }
}

//Fungsi toggle mata
function toggleRegisterPass(inputId, element) {
    const input = document.getElementById(inputId);
    const img = element.querySelector('img');
    if (input.type === "password") {
        input.type = "text";
        img.src = "Asset/mataBuka.png";
    } else {
        input.type = "password";
        img.src = "Asset/mataTutup.png";
    }
}