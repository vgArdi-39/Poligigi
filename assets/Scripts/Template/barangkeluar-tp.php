<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang keluar PDF</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 20px;">

    <div class="judul" style="text-align: center; display: flex; flex-direction: column; align-items: center; line-height: 0.15;">
    <h4>KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h4>
    <h4>POLITEKNIK NEGERI JEMBER</h4>
    <h4>KLINIK PRATAMA</h4>
    <h5 style="justify-content: center;">Jalan Mastrip Jember Kotak Pos 164, 68101 Telp.(0331) 333532-34 Faks 333531</h5>
    </div>
    
    <table style="width:100%; border: 2px solid black; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background:#ffffff; color:black; border: 2px solid black; border-collapse: collapse;">
                <th style="padding:8px; border: 2px solid black;">No</th>
                <th style="padding:8px; border: 2px solid black;">Nama Barang</th>
                <th style="padding:8px; border: 2px solid black;">Satuan</th>
                <th style="padding:8px; border: 2px solid black;">Jumlah</th>
                <th style="padding:8px; border: 2px solid black;">Keterangan</th>
            </tr>
        </thead>
        <tbody id="pdf-body">
            <!-- filled by JS before export -->
        </tbody>
    </table>
</div>  
</body>
</html>