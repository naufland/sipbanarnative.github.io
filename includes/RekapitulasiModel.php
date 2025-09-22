<?php
class RekapitulasiModel {
    private $conn;
    private $table_name = "rup_paket"; // Ganti jika nama tabel Anda berbeda

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getRekapPerencanaan() {
        // Query untuk mendapatkan data rekapitulasi per metode untuk 'Penyedia'
        $query_penyedia = "
            SELECT
                Metode_Pengadaan,
                COUNT(id) as Jumlah_Paket,
                SUM(Pagu_Rp) as Total_Pagu
            FROM
                " . $this->table_name . "
            WHERE
                Tipe_Pengadaan = 'Penyedia' AND Status_Aktif = 'ya'
            GROUP BY
                Metode_Pengadaan
            ORDER BY
                Total_Pagu DESC
        ";

        // Query untuk mendapatkan total 'Swakelola'
        $query_swakelola = "
            SELECT
                COUNT(id) as Jumlah_Paket,
                SUM(Pagu_Rp) as Total_Pagu
            FROM
                " . $this->table_name . "
            WHERE
                Tipe_Pengadaan = 'Swakelola' AND Status_Aktif = 'ya'
        ";

        // Eksekusi query
        $stmt_penyedia = $this->conn->prepare($query_penyedia);
        $stmt_penyedia->execute();
        $penyedia_data = $stmt_penyedia->fetchAll(PDO::FETCH_ASSOC);

        $stmt_swakelola = $this->conn->prepare($query_swakelola);
        $stmt_swakelola->execute();
        $swakelola_data = $stmt_swakelola->fetch(PDO::FETCH_ASSOC);

        // Hitung total
        $total_penyedia_paket = 0;
        $total_penyedia_pagu = 0;
        foreach ($penyedia_data as $row) {
            $total_penyedia_paket += $row['Jumlah_Paket'];
            $total_penyedia_pagu += $row['Total_Pagu'];
        }

        $total_swakelola_paket = $swakelola_data['Jumlah_Paket'] ?? 0;
        $total_swakelola_pagu = $swakelola_data['Total_Pagu'] ?? 0;

        // Susun hasil akhir
        $result = [
            "penyedia" => $penyedia_data,
            "total_penyedia" => [
                "Jumlah_Paket" => $total_penyedia_paket,
                "Total_Pagu" => $total_penyedia_pagu
            ],
            "swakelola" => [
                "Jumlah_Paket" => $total_swakelola_paket,
                "Total_Pagu" => $total_swakelola_pagu
            ],
            "grand_total" => [
                "Jumlah_Paket" => $total_penyedia_paket + $total_swakelola_paket,
                "Total_Pagu" => $total_penyedia_pagu + $total_swakelola_pagu
            ]
        ];

        return $result;
    }
}
?>