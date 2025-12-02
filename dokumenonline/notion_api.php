<?php
// File: notion_api.php
require_once 'config.php';

class NotionAPI {
    private $token;
    private $databaseId;
    private $version;
    private $url;

    public function __construct() {
        // Mengambil konfigurasi dari file config.php
        $this->token = NOTION_API_KEY;
        $this->databaseId = NOTION_DATABASE_ID;
        $this->version = NOTION_API_VERSION;
        $this->url = NOTION_API_URL;
    }

    /**
     * Mengirim data dokumen ke Notion
     */
    public function saveDocument($data) {
        $endpoint = $this->url . '/pages';
        
        // MENYUSUN DATA AGAR COCOK DENGAN KOLOM NOTION ANDA
        $payload = [
            "parent" => ["database_id" => $this->databaseId],
            "properties" => [
                // 1. Kolom Judul (Wajib: Nama dokumen)
                "Nama dokumen" => [
                    "title" => [
                        [
                            "text" => ["content" => $data['original_name']]
                        ]
                    ]
                ],
                
                // 2. Kolom Kategori (Wajib: Kategori)
                "Kategori" => [
                    "select" => [
                        "name" => $data['category']
                    ]
                ],
                
                // 3. Kolom Pengunggah (Tipe Text yang baru Anda buat)
                "Pengunggah" => [
                    "rich_text" => [
                        [
                            "text" => ["content" => $data['uploader']]
                        ]
                    ]
                ],

                // 4. Kolom Nama File Fisik (Tipe Text yang baru Anda buat)
                "Nama File Fisik" => [
                    "rich_text" => [
                        [
                            "text" => ["content" => $data['stored_name']]
                        ]
                    ]
                ]
            ]
        ];

        // Kirim ke Notion
        return $this->sendRequest($endpoint, 'POST', $payload);
    }

    /**
     * Mengarsipkan dokumen (Delete soft)
     */
    public function archiveDocument($pageId) {
        $endpoint = $this->url . '/pages/' . $pageId;
        $payload = ["archived" => true];
        
        $response = $this->sendRequest($endpoint, 'PATCH', $payload);
        return isset($response['id']); // Berhasil jika mengembalikan ID
    }

    /**
     * Fungsi Helper untuk mengirim Request cURL
     */
    private function sendRequest($url, $method, $data = []) {
        $ch = curl_init();
        
        $headers = [
            "Authorization: Bearer " . $this->token,
            "Notion-Version: " . $this->version,
            "Content-Type: application/json"
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return [
                'success' => false, 
                'message' => 'Koneksi Error: ' . curl_error($ch)
            ];
        }
        
        curl_close($ch);
        
        $response = json_decode($result, true);

        // Validasi Response dari Notion
        // Notion mengembalikan object 'page' jika sukses membuat halaman baru
        if (isset($response['object']) && $response['object'] === 'page') {
            return ['success' => true, 'data' => $response];
        } 
        // Jika arsip sukses, objectnya juga 'page' tapi kita cek ID-nya saja
        else if (isset($response['id']) && $method === 'PATCH') {
             return ['success' => true, 'data' => $response];
        }
        else {
            // Jika Gagal, ambil pesan errornya
            $msg = $response['message'] ?? 'Unknown error';
            $code = $response['status'] ?? 'Unknown code';
            return [
                'success' => false, 
                'message' => "Notion menolak ($code): $msg"
            ];
        }
    }
}
?>