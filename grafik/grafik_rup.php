<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabel Perencanaan Pengadaan</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* Semua kode CSS dari file Anda ditempelkan di sini */
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Summary Section Styles */
        .summary-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        .summary-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        .summary-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .summary-header i {
            font-size: 20px;
        }
        .summary-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .summary-content {
            padding: 30px 25px;
        }

        /* Statistics Tables */
        .stats-tables {
            display: grid;
            gap: 30px;
        }
        .stats-table {
            background: white; /* Diubah dari hitam agar sesuai konteks */
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e9ecef;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        .stats-table h4 {
            margin: 0 0 20px 0;
            color: #2c3e50; /* Diubah dari merah */
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f3f4;
        }
        .stats-table h4 i {
            color: #dc3545;
        }
        .stats-table table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-collapse: collapse;
        }
        .stats-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            text-align: left;
        }
        .stats-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: middle;
            color: #34495e;
        }
        .stats-table tr:last-child td {
             border-bottom: none;
        }
        .stats-table tbody tr:hover {
            background: #f8f9fa;
        }
        .text-start {
             text-align: left;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="stats-tables">
            <div class="stats-table">
                <h4><i class="fas fa-chart-pie"></i> PERENCANAAN</h4>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">NO</th>
                            <th style="width: 40%;">METODE PENGADAAN</th>
                            <th style="width: 25%; text-align: right;">JUMLAH PAKET</th>
                            <th style="width: 30%; text-align: right;">PAGU</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align: center;">1</td>
                            <td>E-Purchasing</td>
                            <td style="text-align: right;">6.006</td>
                            <td style="text-align: right;">488.020.620.165</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">2</td>
                            <td>Pengadaan Langsung</td>
                            <td style="text-align: right;">1.465</td>
                            <td style="text-align: right;">127.812.833.569</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">3</td>
                            <td>Penunjukan Langsung</td>
                            <td style="text-align: right;">1</td>
                            <td style="text-align: right;">488.500.000</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">4</td>
                            <td>Seleksi</td>
                            <td style="text-align: right;">29</td>
                            <td style="text-align: right;">10.438.180.000</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">5</td>
                            <td>Tender</td>
                            <td style="text-align: right;">89</td>
                            <td style="text-align: right;">83.679.413.241</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">6</td>
                            <td>Tender Cepat</td>
                            <td style="text-align: right;">0</td>
                            <td style="text-align: right;">0</td>
                        </tr>
                        <tr>
                            <td style="text-align: center;">7</td>
                            <td>Dikecualikan</td>
                            <td style="text-align: right;">1.322</td>
                            <td style="text-align: right;">102.365.751.430</td>
                        </tr>
                        
                        <tr style="background-color: #ffe8d6; font-weight: bold;">
                            <td colspan="2" class="text-start">Penyedia</td>
                            <td style="text-align: right;">8.912</td>
                            <td style="text-align: right;">812.805.298.405</td>
                        </tr>
                        <tr style="background-color: #fff9c4; font-weight: bold;">
                            <td colspan="2" class="text-start">Swakelola</td>
                            <td style="text-align: right;">4.597</td>
                            <td style="text-align: right;">509.774.507.409</td>
                        </tr>
                        <tr style="background-color: #e3f2fd; font-weight: bold;">
                            <td colspan="2" class="text-start">Total</td>
                            <td style="text-align: right;">13.509</td>
                            <td style="text-align: right;">1.322.579.805.814</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>