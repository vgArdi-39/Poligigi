<?php $current_page = 'data-barang'; ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/data-barang.css">
</head>
<body>

<div class="dashboard-wrapper">
    <?php include('assets/sidebar.php'); ?>

    <main class="main-content">
        <h1>Data Barang</h1>
        <div class="main-tb-container">
            <div class="search-container">
                <input type="text">
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Contoh Barang 1</td>
                        <td>9</td>
                        <td>PCS</td>
                    </tr>
                    <tr>
                        <td>Contoh Barang 2</td>
                        <td>22</td>
                        <td>Box</td>
                    </tr>
                    <tr>
                        <td>Contoh Barang 3</td>
                        <td>15</td>
                        <td>Ampul</td>
                    </tr>
                    <tr>
                        <td>Contoh Barang 4</td>
                        <td>12</td>
                        <td>Box</td>
                    </tr>
                    <tr>
                        <td>Contoh Barang 5</td>
                        <td>4</td>
                        <td>Liter</td>
                    </tr>
                    <tr>
                        <td>Contoh Barang 6</td>
                        <td>5</td>
                        <td>Roll</td>
                    </tr>
                                        <tr>
                        <td>Contoh Barang 7</td>
                        <td>6</td>
                        <td>Tube</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</div> </body>
</html>