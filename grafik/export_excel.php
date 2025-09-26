<?php
// Memastikan semua error ditampilkan untuk debugging, bisa dihapus saat produksi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Memanggil autoloader dari Composer
require '../vendor/autoload.php';
// Memanggil koneksi database
require_once '../config/database.php';

// Menggunakan class-class yang dibutuhkan dari PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// =======================================================
// LOGIKA PENGAMBILAN & PENGOLAHAN DATA (Disalin dari dashboard)
// =======================================================

$rekap_metode = [];
$penyedia_stats = ['paket' => 0, 'pagu' => 0];
$swakelola_stats = ['paket' => 0, 'pagu' => 0];

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = "
        SELECT 
            'Penyedia' AS Cara, Metode AS Metode, COUNT(ID) AS Jumlah, SUM(Pagu_Rp) AS Pagu 
        FROM rup_keseluruhan 
        WHERE Metode IS NOT NULL AND TRIM(Metode) != '' 
        GROUP BY Metode
        UNION ALL
        SELECT 
            'Swakelola' AS Cara, Tipe_Swakelola AS Metode, COUNT(ID) AS Jumlah, SUM(Pagu_Rp) AS Pagu 
        FROM rup_swakelola 
        WHERE Tipe_Swakelola IS NOT NULL AND TRIM(Tipe_Swakelola) != '' 
        GROUP BY Metode
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $results = $stmt->fetchAll();
        foreach ($results as $row) {
            $metode_name = trim($row['Metode']);
            if (!isset($rekap_metode[$metode_name])) {
                $rekap_metode[$metode_name] = ['paket' => 0, 'pagu' => 0];
            }
            $rekap_metode[$metode_name]['paket'] += $row['Jumlah'];
            $rekap_metode[$metode_name]['pagu'] += $row['Pagu'];
            
            if ($row['Cara'] === 'Swakelola') {
                $swakelola_stats['paket'] += $row['Jumlah'];
                $swakelola_stats['pagu'] += $row['Pagu'];
            } else {
                $penyedia_stats['paket'] += $row['Jumlah'];
                $penyedia_stats['pagu'] += $row['Pagu'];
            }
        }
    }
} catch (Exception $e) {
    // Jika ada error, hentikan dan tampilkan pesan
    die("Error saat mengambil data: " . $e->getMessage());
}

$total_paket = $penyedia_stats['paket'] + $swakelola_stats['paket'];
$total_pagu = $penyedia_stats['pagu'] + $swakelola_stats['pagu'];

// Urutkan data sesuai format di gambar
$metode_order = ['E-Purchasing', 'Pengadaan Langsung', 'Penunjukan Langsung', 'Seleksi', 'Tender', 'Tender Cepat', 'Dikecualikan'];
$rekap_metode_sorted = [];
foreach ($metode_order as $metode) {
    $rekap_metode_sorted[$metode] = $rekap_metode[$metode] ?? ['paket' => 0, 'pagu' => 0];
}

// =======================================================
// PROSES PEMBUATAN FILE EXCEL
// =======================================================

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- STYLING ---
$headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => '000000']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']]];
$totalStyle = ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']]];
$penyediaStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE699']]];
$swakelolaStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFD966']]];
$allBorders = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];

// --- HEADER UTAMA ---
$sheet->mergeCells('A1:D1');
$sheet->setCellValue('A1', 'PERENCANAAN');
$sheet->getStyle('A1')->applyFromArray($headerStyle);
$sheet->getStyle('A1')->getFont()->setSize(14);

// --- HEADER TABEL ---
$sheet->setCellValue('A2', 'NO');
$sheet->setCellValue('B2', 'METODE PENGADAAN');
$sheet->setCellValue('C2', 'JUMLAH PAKET RUP');
$sheet->setCellValue('D2', 'PAGU');
$sheet->getStyle('A2:D2')->applyFromArray($headerStyle);

// --- MENGISI DATA ---
$rowNumber = 3;
$no = 1;
foreach ($rekap_metode_sorted as $metode => $stats) {
    $sheet->setCellValue('A' . $rowNumber, $no++);
    $sheet->setCellValue('B' . $rowNumber, $metode);
    $sheet->setCellValue('C' . $rowNumber, $stats['paket']);
    $sheet->setCellValue('D' . $rowNumber, $stats['pagu']);
    $rowNumber++;
}

// --- MENGISI SUBTOTAL & TOTAL ---
$sheet->mergeCells('A'.$rowNumber.':B'.$rowNumber);
$sheet->setCellValue('A' . $rowNumber, 'Penyedia');
$sheet->setCellValue('C' . $rowNumber, $penyedia_stats['paket']);
$sheet->setCellValue('D' . $rowNumber, $penyedia_stats['pagu']);
$sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($penyediaStyle);
$rowNumber++;

$sheet->mergeCells('A'.$rowNumber.':B'.$rowNumber);
$sheet->setCellValue('A' . $rowNumber, 'Swakelola');
$sheet->setCellValue('C' . $rowNumber, $swakelola_stats['paket']);
$sheet->setCellValue('D' . $rowNumber, $swakelola_stats['pagu']);
$sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($swakelolaStyle);
$rowNumber++;

$sheet->mergeCells('A'.$rowNumber.':B'.$rowNumber);
$sheet->setCellValue('A' . $rowNumber, 'Total');
$sheet->setCellValue('C' . $rowNumber, $total_paket);
$sheet->setCellValue('D' . $rowNumber, $total_pagu);
$sheet->getStyle('A' . $rowNumber . ':D' . $rowNumber)->applyFromArray($totalStyle);

// --- FORMATTING & PENYESUAIAN AKHIR ---
$sheet->getStyle('C3:D' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(25);
$sheet->getStyle('A1:D' . $rowNumber)->applyFromArray($allBorders);
$sheet->getStyle('A3:A'.($rowNumber-3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


// --- MENGIRIM FILE KE BROWSER ---
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Rekap_Perencanaan_' . date('Y-m-d') . '.xlsx"');
header('CacheS-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;