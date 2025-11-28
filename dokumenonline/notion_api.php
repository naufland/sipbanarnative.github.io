<?php
// File: notion_api.php
// Helper untuk interaksi dengan Notion API

require_once 'config.php';

class NotionAPI {
    private $apiKey;
    private $databaseId;
    private $apiVersion;
    private $apiUrl;
    
    public function __construct() {
        $this->apiKey = NOTION_API_KEY;
        $this->databaseId = NOTION_DATABASE_ID;
        $this->apiVersion = NOTION_API_VERSION;
        $this->apiUrl = NOTION_API_URL;
    }
    
    /**
     * Kirim request ke Notion API
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        $ch = curl_init($url);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Notion-Version: ' . $this->apiVersion
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            error_log("Notion API Error: HTTP $httpCode - " . $response);
            return ['success' => false, 'error' => $response, 'http_code' => $httpCode];
        }
        
        return ['success' => true, 'data' => json_decode($response, true)];
    }
    
    /**
     * Simpan metadata dokumen ke Notion Database
     * Sesuai dengan struktur Notion yang ada
     */
    public function saveDocument($documentData) {
        $data = [
            'parent' => [
                'database_id' => $this->databaseId
            ],
            'properties' => [
                // Nama dokumen (Title/Name column)
                'Nama dokumen' => [
                    'title' => [
                        [
                            'text' => [
                                'content' => $documentData['original_name']
                            ]
                        ]
                    ]
                ],
                // Kategori (Select) - User pilih manual
                'Kategori' => [
                    'select' => [
                        'name' => $documentData['category']
                    ]
                ],
                // Dibuat oleh (Created by - Person)
                'Dibuat oleh' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $documentData['uploader']
                            ]
                        ]
                    ]
                ],
                // Waktu dibuat (Created time - Date)
                'Waktu dibuat' => [
                    'date' => [
                        'start' => date('Y-m-d\TH:i:s', strtotime($documentData['upload_date']))
                    ]
                ],
                // Terakhir diedit oleh (Last edited by - Person)
                'Terakhir diedit oleh' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $documentData['uploader']
                            ]
                        ]
                    ]
                ],
                // Waktu terakhir diperbarui (Last edited time - Date)
                'Waktu terakhir diperbarui' => [
                    'date' => [
                        'start' => date('Y-m-d\TH:i:s', strtotime($documentData['upload_date']))
                    ]
                ]
            ]
        ];
        
        return $this->makeRequest('/pages', 'POST', $data);
    }
    
    /**
     * DEPRECATED: Tidak digunakan lagi karena kategori dipilih user
     */
    private function getCategory($fileType) {
        return 'Dokumen strategi'; // Default fallback
    }
    
    /**
     * Ambil semua dokumen dari Notion Database
     */
    public function getDocuments($filter = null) {
        $data = [
            'page_size' => 100
        ];
        
        if ($filter) {
            $data['filter'] = $filter;
        }
        
        // Sort by created time descending
        $data['sorts'] = [
            [
                'timestamp' => 'created_time',
                'direction' => 'descending'
            ]
        ];
        
        return $this->makeRequest('/databases/' . $this->databaseId . '/query', 'POST', $data);
    }
    
    /**
     * Update dokumen di Notion
     */
    public function updateDocument($pageId, $documentData) {
        $data = [
            'properties' => [
                'Terakhir diedit oleh' => [
                    'rich_text' => [
                        [
                            'text' => [
                                'content' => $documentData['uploader'] ?? 'System'
                            ]
                        ]
                    ]
                ],
                'Waktu terakhir diperbarui' => [
                    'date' => [
                        'start' => date('Y-m-d\TH:i:s')
                    ]
                ]
            ]
        ];
        
        return $this->makeRequest('/pages/' . $pageId, 'PATCH', $data);
    }
    
    /**
     * Archive/hapus dokumen di Notion (set archived = true)
     */
    public function archiveDocument($pageId) {
        $data = [
            'archived' => true
        ];
        
        return $this->makeRequest('/pages/' . $pageId, 'PATCH', $data);
    }
    
    /**
     * Cari dokumen berdasarkan nama file
     */
    public function findDocumentByName($fileName) {
        $filter = [
            'property' => 'Nama dokumen',
            'title' => [
                'equals' => $fileName
            ]
        ];
        
        $result = $this->getDocuments($filter);
        
        if ($result['success'] && isset($result['data']['results']) && count($result['data']['results']) > 0) {
            return [
                'success' => true,
                'page_id' => $result['data']['results'][0]['id'],
                'data' => $result['data']['results'][0]
            ];
        }
        
        return ['success' => false, 'error' => 'Document not found'];
    }
    
    /**
     * Parse properties dari Notion page
     */
    public function parseProperties($properties) {
        $parsed = [];
        
        foreach ($properties as $key => $value) {
            switch ($value['type']) {
                case 'title':
                    $parsed[$key] = $value['title'][0]['text']['content'] ?? '';
                    break;
                case 'rich_text':
                    $parsed[$key] = $value['rich_text'][0]['text']['content'] ?? '';
                    break;
                case 'number':
                    $parsed[$key] = $value['number'] ?? 0;
                    break;
                case 'select':
                    $parsed[$key] = $value['select']['name'] ?? '';
                    break;
                case 'date':
                    $parsed[$key] = $value['date']['start'] ?? '';
                    break;
                case 'created_time':
                    $parsed[$key] = $value['created_time'] ?? '';
                    break;
                case 'last_edited_time':
                    $parsed[$key] = $value['last_edited_time'] ?? '';
                    break;
                case 'created_by':
                    $parsed[$key] = $value['created_by']['name'] ?? '';
                    break;
                case 'last_edited_by':
                    $parsed[$key] = $value['last_edited_by']['name'] ?? '';
                    break;
                default:
                    $parsed[$key] = '';
            }
        }
        
        return $parsed;
    }
    
    /**
     * Sinkronisasi dari Notion ke lokal
     * Ambil dokumen dari Notion dan simpan ke metadata.json
     */
    public function syncFromNotion($uploadDir) {
        $result = $this->getDocuments();
        
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to fetch documents from Notion'];
        }
        
        $notionDocs = $result['data']['results'] ?? [];
        $metadataFile = $uploadDir . 'metadata.json';
        $localMetadata = [];
        
        if (file_exists($metadataFile)) {
            $localMetadata = json_decode(file_get_contents($metadataFile), true) ?? [];
        }
        
        $synced = 0;
        foreach ($notionDocs as $doc) {
            if (isset($doc['archived']) && $doc['archived']) {
                continue; // Skip archived documents
            }
            
            $props = $this->parseProperties($doc['properties']);
            
            // Cek apakah dokumen sudah ada di lokal
            $fileName = $props['Nama dokumen'] ?? '';
            $found = false;
            
            foreach ($localMetadata as $storedName => $meta) {
                if ($meta['original_name'] === $fileName) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // Dokumen ada di Notion tapi tidak di lokal
                // Bisa ditambahkan logika untuk handle ini
                error_log("Document in Notion but not in local: " . $fileName);
            } else {
                $synced++;
            }
        }
        
        return ['success' => true, 'synced' => $synced, 'total' => count($notionDocs)];
    }
}
?>