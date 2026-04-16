<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polinventory - Login</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="form-container">
  <div id="step-username" class="form-step">
    <h2>Login</h2>
    <input type="text" id="username" placeholder="Enter Username" required>
    <button type="button" onclick="showNextStep()">Selanjutnya</button>
  </div>

  <div id="step-password" class="form-step hidden">
    <h2>Masukan Kata Sandi</h2>
    <input type="password" id="password" placeholder="Enter Kata Sandi" required>
    <a href="dashboard.php"><button type="submit">Login</button></a>
  </div>
</div>
<script src="assets/Scripts/JS/Login.js"></script>
</html>