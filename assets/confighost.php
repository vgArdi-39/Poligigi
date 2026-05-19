<?php
$host = 'sql201.infinityfree.com';
$db   = 'if0_41959112_db_poligigi';
$user = 'if0_41959112';   
$pass = 'Hosting995';       

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>