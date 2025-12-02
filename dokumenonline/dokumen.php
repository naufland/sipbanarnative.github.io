<?php
// =================================================================
// == FILE WORKSPACE / NOTION CLONE (DARK MODE FIXED) ==============
// =================================================================

// --- 1. KONFIGURASI DATABASE ---
$host = 'localhost';
$user = 'root';      // Sesuaikan user database Anda
$pass = '';          // Sesuaikan password database Anda
$db   = 'sipbanar';  // Sesuaikan nama database

// Koneksi ke Database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- 2. INISIALISASI TABEL ---
$conn->query("CREATE TABLE IF NOT EXISTS uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    file_size INT,
    file_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS app_state (
    id INT PRIMARY KEY,
    json_data LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// --- 3. HANDLE API REQUESTS ---

// A. Handle Upload File
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    error_reporting(0);
    ini_set('display_errors', 0);

    $uploadDir = '../../uploads/workspace/'; 
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

    $fileName = basename($_FILES['file_upload']['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $targetFilePath = $uploadDir . uniqid() . '_' . $fileName; 
    
    $response = ['success' => false, 'message' => 'Upload failed'];
    
    if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $targetFilePath)) {
        $fileSize = $_FILES['file_upload']['size'];
        
        $stmt = $conn->prepare("INSERT INTO uploads (file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $fileName, $targetFilePath, $fileSize, $fileType);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'url' => $targetFilePath,
                'name' => $fileName,
                'ext' => $fileType,
                'size' => $fileSize
            ];
        } else {
            $response['message'] = "Database Error: " . $stmt->error;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// B. Handle Save State
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_state') {
    error_reporting(0);
    ini_set('display_errors', 0);

    $data = $_POST['data'];
    
    $check = $conn->query("SELECT id FROM app_state WHERE id = 1");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE app_state SET json_data = ? WHERE id = 1");
    } else {
        $stmt = $conn->prepare("INSERT INTO app_state (id, json_data) VALUES (1, ?)");
    }
    
    $stmt->bind_param("s", $data);
    $success = $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// C. Load Initial State
$initialData = 'null';
$res = $conn->query("SELECT json_data FROM app_state WHERE id = 1");
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    if (!empty($row['json_data'])) {
        $initialData = $row['json_data'];
    }
}

$page_title = "Workspace Dokumen - SIP BANAR";

// --- 4. INCLUDE HEADER ---
if (file_exists('../../navbar/header.php')) {
    include '../../navbar/header.php';
} elseif (file_exists('../navbar/header.php')) {
    include '../navbar/header.php';
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<script>
    tailwind.config = {
        prefix: 'tw-', 
        darkMode: 'class',
        corePlugins: { preflight: false },
        theme: {
            extend: {
                colors: {
                    // Warna Notion Light & Dark yang Akurat
                    notion: { bg: '#FFFFFF', sidebar: '#F7F7F5', hover: '#EFEFEF', text: '#37352F', border: '#E9E9E8', gray: '#9B9A97' },
                    dark: { bg: '#191919', sidebar: '#202020', hover: '#2C2C2C', text: '#D4D4D4', border: '#2F2F2F', hovertext: '#FFFFFF' }
                }
            }
        }
    }
</script>

<style>
    .workspace-outer-wrapper { padding: 20px 0; min-height: calc(100vh - 80px); position: relative; z-index: 0; }
    #notion-app-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; height: 85vh; overflow: hidden; position: relative; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); z-index: 0; }
    [contenteditable]:empty:before { content: attr(placeholder); color: #9ca3af; cursor: text; }
    .block-wrapper .drag-handle { opacity: 0; transition: opacity 0.2s; }
    .block-wrapper:hover .drag-handle { opacity: 1; }
    .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background: #4b5563; }
    .dashboard-card { transition: all 0.2s ease; }
    .dashboard-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    
    /* Smooth Transition untuk Dark Mode */
    #notion-app-container, #sidebar, header, .dashboard-card {
        transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
    }
</style>

<div class="container-fluid">
    <div class="workspace-outer-wrapper">
        <div id="notion-root"> 
            
            <div id="notion-app-container" class="tw-bg-notion-bg tw-dark:tw-bg-dark-bg tw-text-notion-text tw-dark:tw-text-dark-text tw-flex">

                <aside id="sidebar" class="tw-w-64 tw-bg-notion-sidebar tw-dark:tw-bg-dark-sidebar tw-border-r tw-border-notion-border tw-dark:tw-border-dark-border tw-flex tw-flex-col tw-h-full tw-flex-shrink-0 tw-z-0">
                    <div class="tw-h-14 tw-flex tw-items-center tw-px-4 tw-font-bold tw-text-sm tw-cursor-pointer tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover tw-transition tw-text-orange-500" onclick="setAppView('dashboard')">
                        <div class="tw-w-6 tw-h-6 tw-bg-orange-600 tw-text-white tw-rounded tw-flex tw-items-center tw-justify-center tw-text-xs tw-mr-2 tw-font-bold">N</div>
                        Workspace Kantor
                    </div>

                    <div class="tw-px-3 tw-pb-2 tw-space-y-1 tw-text-sm tw-text-gray-500">
                        <div onclick="setAppView('dashboard')" class="tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-1.5 tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover tw-rounded tw-cursor-pointer">
                            <i class="ph ph-squares-four"></i> <span>Dashboard</span>
                        </div>
                    </div>

                    <div class="tw-flex-1 tw-overflow-y-auto tw-py-2 custom-scroll" id="sidebar-list"></div>

                    <div class="tw-p-3 tw-border-t tw-border-notion-border tw-dark:tw-border-dark-border">
                        <button onclick="createNewCategory()" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-500 tw-hover:tw-text-white tw-px-2 tw-py-1 tw-w-full tw-text-left tw-rounded tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover tw-transition">
                            <i class="ph ph-plus"></i> Kategori Baru
                        </button>
                    </div>
                </aside>

                <main class="tw-flex-1 tw-flex tw-flex-col tw-h-full tw-relative tw-w-full tw-bg-notion-bg tw-dark:tw-bg-dark-bg">
                    <header class="tw-h-12 tw-flex tw-items-center tw-justify-between tw-px-4 tw-sticky tw-top-0 tw-bg-white/95 tw-dark:tw-bg-dark-bg/95 tw-backdrop-blur tw-z-10 tw-border-b tw-border-transparent tw-hover:tw-border-notion-border tw-dark:tw-hover:tw-border-dark-border">
                        <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-notion-text tw-dark:tw-text-gray-400 tw-overflow-hidden" id="breadcrumbs"></div>
                        <div class="tw-flex tw-items-center tw-gap-1 tw-text-notion-text tw-dark:tw-text-gray-400">
                            <span id="save-status" class="tw-text-xs tw-text-green-600 tw-dark:tw-text-green-400 tw-mr-2 tw-opacity-0 tw-transition">Saved</span>
                            <button onclick="toggleTheme()" class="tw-p-1.5 tw-rounded tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover tw-transition" title="Ganti Mode">
                                <i id="theme-icon" class="ph ph-moon tw-text-lg"></i>
                            </button>
                        </div>
                    </header>

                    <div id="editor-toolbar" class="tw-h-12 tw-border-b tw-border-notion-border tw-dark:tw-border-dark-border tw-flex tw-items-center tw-px-4 tw-gap-2 tw-bg-white tw-dark:tw-bg-dark-bg tw-sticky tw-top-0 tw-z-10 tw-hidden">
                        <button onclick="triggerUpload('file')" class="tw-flex tw-items-center tw-gap-2 tw-px-2 tw-py-1 tw-rounded tw-bg-blue-500/10 tw-text-blue-400 tw-hover:tw-bg-blue-500/20 tw-text-xs tw-font-medium tw-transition"><i class="ph ph-paperclip"></i> File</button>
                        <button onclick="triggerUpload('image')" class="tw-flex tw-items-center tw-gap-2 tw-px-2 tw-py-1 tw-rounded tw-bg-green-500/10 tw-text-green-400 tw-hover:tw-bg-green-500/20 tw-text-xs tw-font-medium tw-transition"><i class="ph ph-image"></i> Gambar</button>
                        <div class="tw-w-px tw-h-5 tw-bg-gray-300 tw-mx-2"></div>
                        <button onclick="addBlock('h1')" class="tw-p-1.5 tw-rounded tw-hover:tw-bg-dark-hover tw-text-gray-400 tw-hover:tw-text-white"><i class="ph ph-text-h-one"></i></button>
                        <button onclick="addBlock('text')" class="tw-p-1.5 tw-rounded tw-hover:tw-bg-dark-hover tw-text-gray-400 tw-hover:tw-text-white"><i class="ph ph-text-t"></i></button>
                        <button onclick="addBlock('todo')" class="tw-p-1.5 tw-rounded tw-hover:tw-bg-dark-hover tw-text-gray-400 tw-hover:tw-text-white"><i class="ph ph-check-square"></i></button>
                    </div>

                    <div id="main-scroller" class="tw-flex-1 tw-overflow-y-auto custom-scroll">
                        <div id="workspace-content"></div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div>

<input type="file" id="hidden-file-input" class="tw-hidden">

<div id="global-context-menu" class="tw-hidden tw-fixed tw-z-[9999] tw-w-56 tw-bg-white tw-dark:tw-bg-gray-800 tw-rounded-lg tw-shadow-xl tw-border tw-border-gray-200 tw-dark:tw-border-gray-700 tw-py-1 tw-text-sm tw-overflow-hidden tw-transition-opacity tw-duration-200">
    </div>

<script>
    // --- DATA & STATE ---
    const serverData = <?php echo $initialData; ?>;
    
    let appData = serverData || {
        categories: [
            { id: 'cat_1', name: 'Pengadaan', icon: 'ph-shopping-cart' },
            { id: 'cat_2', name: 'Administrasi', icon: 'ph-files' },
            { id: 'cat_3', name: 'Laporan', icon: 'ph-chart-pie-slice' }
        ],
        docs: []
    };
    
    let currentView = 'dashboard'; 
    let currentDocId = null;
    let contextTarget = { type: null, id: null, index: null };

    // --- INIT ---
    document.addEventListener('DOMContentLoaded', () => {
        // --- LOGIC DARK MODE ---
        // Cek localStorage, jika 'dark' atau sistem komputer user dark mode
        const savedTheme = localStorage.getItem('workspace_theme');
        const root = document.getElementById('notion-root');
        const icon = document.getElementById('theme-icon');

        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
            icon.classList.remove('ph-moon');
            icon.classList.add('ph-sun'); // Ganti icon jadi matahari
        } else {
            root.classList.remove('dark');
            icon.classList.remove('ph-sun');
            icon.classList.add('ph-moon'); // Ganti icon jadi bulan
        }

        renderSidebar();
        setAppView('dashboard');

        // Global Click listener
        document.addEventListener('click', (e) => {
            const menu = document.getElementById('global-context-menu');
            if (menu && !menu.contains(e.target)) hideContextMenu();
        });
        document.addEventListener('scroll', hideContextMenu, true);
    });

    // --- THEME TOGGLE FUNCTION (DIPERBAIKI) ---
    function toggleTheme() {
        const root = document.getElementById('notion-root');
        const icon = document.getElementById('theme-icon');
        
        if (root.classList.contains('dark')) {
            // Switch to Light
            root.classList.remove('dark');
            localStorage.setItem('workspace_theme', 'light');
            
            // Icon animation logic
            icon.classList.remove('ph-sun');
            icon.classList.add('ph-moon');
        } else {
            // Switch to Dark
            root.classList.add('dark');
            localStorage.setItem('workspace_theme', 'dark');
            
            // Icon animation logic
            icon.classList.remove('ph-moon');
            icon.classList.add('ph-sun');
        }
    }

    // --- VIEW CONTROLLER ---
    function setAppView(view, docId = null) {
        currentView = view;
        currentDocId = docId;
        const container = document.getElementById('workspace-content');
        const toolbar = document.getElementById('editor-toolbar');
        
        if(view === 'dashboard') {
            toolbar.classList.add('tw-hidden');
            document.getElementById('breadcrumbs').innerHTML = `<span class="tw-opacity-50 tw-cursor-pointer"><i class="ph ph-house"></i> Home</span>`;
            container.innerHTML = renderDashboardHTML();
        } else if (view === 'editor') {
            toolbar.classList.remove('tw-hidden');
            renderEditor(docId);
        }
    }

    // --- LOGIC BUKA KATEGORI ---
    function openCategoryAction(catId) {
        const sub = document.getElementById(`sub-${catId}`);
        if(sub) sub.classList.remove('tw-hidden');

        const firstDoc = appData.docs.find(d => d.catId === catId);
        if (firstDoc) {
            setAppView('editor', firstDoc.id);
        }
    }

    // --- RENDER DASHBOARD ---
    function renderDashboardHTML() {
        return `
        <div class="tw-max-w-5xl tw-mx-auto tw-p-12 tw-fade-enter-active">
            <div class="tw-text-3xl tw-font-bold tw-mb-2 tw-text-center tw-text-gray-800 tw-dark:tw-text-white">Workspace Pengadaan</div>
            <div class="tw-text-sm tw-text-center tw-text-gray-500 tw-mb-10">Kelola dokumen, catatan, dan arsip dalam satu tempat.</div>
            
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6">
                ${appData.categories.map(c => {
                    const count = appData.docs.filter(d => d.catId === c.id).length;
                    return `
                    <div onclick="openCategoryAction('${c.id}')" oncontextmenu="openContextMenu(event, 'category', '${c.id}')" class="dashboard-card tw-p-6 tw-border tw-border-gray-200 tw-dark:tw-border-gray-700 tw-rounded-xl tw-bg-white tw-dark:tw-bg-dark-sidebar tw-cursor-pointer tw-hover:tw-border-blue-500 tw-relative tw-group">
                        <i class="ph ${c.icon || 'ph-folder'} tw-text-3xl tw-text-blue-500 tw-mb-4 tw-block"></i>
                        <div class="tw-font-bold tw-text-lg tw-text-gray-800 tw-dark:tw-text-gray-200 tw-mb-1">${c.name}</div>
                        <div class="tw-text-sm tw-text-gray-500">${count} Dokumen</div>
                    </div>
                    `;
                }).join('')}
                
                <div onclick="createNewCategory()" class="dashboard-card tw-p-6 tw-border tw-border-dashed tw-border-gray-300 tw-dark:tw-border-gray-700 tw-rounded-xl tw-bg-transparent tw-cursor-pointer tw-hover:tw-border-gray-500 tw-hover:tw-bg-gray-100 tw-dark:tw-hover:tw-bg-gray-800/50 tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-gray-500 tw-h-full tw-min-h-[140px]">
                    <i class="ph ph-plus tw-text-3xl tw-mb-2"></i>
                    <span>Tambah Kategori</span>
                </div>
            </div>
        </div>`;
    }

    // --- RENDER SIDEBAR ---
    function renderSidebar() {
        const list = document.getElementById('sidebar-list');
        list.innerHTML = '';
        
        appData.categories.forEach(cat => {
            const group = document.createElement('div');
            group.className = "tw-mb-1";
            
            const header = document.createElement('div');
            header.className = "tw-px-3 tw-py-1 tw-text-gray-600 tw-dark:tw-text-gray-400 tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover tw-rounded tw-cursor-pointer tw-flex tw-items-center tw-justify-between tw-group/cat tw-select-none tw-transition-colors";
            header.innerHTML = `
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-medium"><i class="ph ${cat.icon || 'ph-folder'}"></i> ${cat.name}</div>
                <div class="tw-opacity-0 group-hover/cat:tw-opacity-100 tw-flex tw-gap-1">
                    <button onclick="createNewDoc('${cat.id}')" class="tw-text-xs tw-text-gray-400 tw-hover:tw-text-black tw-dark:tw-hover:tw-text-white" title="Tambah Doc">+</button>
                </div>
            `;
            
            header.onclick = (e) => { 
                if(e.target.tagName !== 'BUTTON') {
                    const sub = document.getElementById(`sub-${cat.id}`);
                    sub.classList.toggle('tw-hidden');
                    const firstDoc = appData.docs.find(d => d.catId === cat.id);
                    if(firstDoc) setAppView('editor', firstDoc.id);
                }
            };
            header.oncontextmenu = (e) => { openContextMenu(e, 'category', cat.id); };
            
            const sub = document.createElement('div');
            sub.id = `sub-${cat.id}`;
            sub.className = "tw-ml-4 tw-pl-2 tw-border-l tw-border-gray-300 tw-dark:tw-border-gray-700 tw-hidden tw-mt-1 tw-space-y-0.5";
            
            const catDocs = appData.docs.filter(d => d.catId === cat.id);
            if(catDocs.length === 0) {
                 sub.innerHTML = `<div class="tw-px-3 tw-text-xs tw-text-gray-400 tw-italic tw-py-1">Belum ada dokumen</div>`;
            } else {
                catDocs.forEach(doc => {
                    const isActive = doc.id === currentDocId;
                    const item = document.createElement('div');
                    item.className = `tw-px-2 tw-py-1 tw-text-sm tw-rounded tw-cursor-pointer tw-flex tw-items-center tw-gap-2 tw-truncate tw-transition ${isActive ? 'tw-bg-blue-100 tw-dark:tw-bg-blue-900/30 tw-text-blue-600 tw-dark:tw-text-blue-400' : 'tw-text-gray-600 tw-dark:tw-text-gray-400 tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover'}`;
                    item.innerHTML = `<i class="ph ph-file-text tw-text-xs"></i> <span class="tw-truncate">${doc.title || 'Untitled'}</span>`;
                    item.onclick = (e) => { e.stopPropagation(); setAppView('editor', doc.id); };
                    item.oncontextmenu = (e) => { openContextMenu(e, 'doc', doc.id); };
                    sub.appendChild(item);
                });
            }
            group.appendChild(header);
            group.appendChild(sub);
            list.appendChild(group);
        });
    }

    // --- RENDER EDITOR ---
    function renderEditor(docId) {
        const doc = appData.docs.find(d => d.id === docId);
        if(!doc) return setAppView('dashboard');

        const cat = appData.categories.find(c => c.id === doc.catId);
        
        document.getElementById('breadcrumbs').innerHTML = `
            <span class="tw-opacity-50 tw-cursor-pointer tw-hover:tw-opacity-100" onclick="setAppView('dashboard')">Home</span> 
            <span class="tw-opacity-30">/</span> 
            <span class="tw-opacity-70">${cat ? cat.name : 'Unknown'}</span>
            <span class="tw-opacity-30">/</span>
            <span>${doc.title || 'Untitled'}</span>
        `;

        document.getElementById('workspace-content').innerHTML = `
            <div class="tw-max-w-3xl tw-mx-auto tw-px-12 tw-py-12 tw-pb-32 tw-min-h-[500px]">
                <input type="text" id="doc-title" placeholder="Judul Halaman" value="${doc.title}" class="tw-text-4xl tw-font-bold tw-w-full tw-bg-transparent tw-border-none tw-outline-none tw-placeholder-gray-400 tw-mb-6 tw-text-gray-800 tw-dark:tw-text-white" autocomplete="off">
                <div id="blocks-container" class="tw-space-y-1"></div>
                <div class="tw-mt-8 tw-pt-8 tw-border-t tw-border-dashed tw-border-gray-300 tw-dark:tw-border-gray-800 tw-text-gray-400 tw-text-sm tw-italic tw-cursor-text tw-hover:tw-text-gray-600 tw-dark:tw-hover:tw-text-gray-300 tw-transition" onclick="addBlock('text')">
                    Klik di sini untuk mengetik baris baru...
                </div>
            </div>
        `;
        
        const titleInput = document.getElementById('doc-title');
        titleInput.oninput = (e) => { doc.title = e.target.value; saveData(); renderSidebar(); };
        
        renderBlocks(doc);
    }

    function renderBlocks(doc) {
        const container = document.getElementById('blocks-container');
        container.innerHTML = '';
        
        doc.blocks.forEach((block, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = "block-wrapper tw-group tw-relative tw-flex tw-items-start -tw-ml-8 tw-pl-8 tw-py-1 tw-rounded hover:tw-bg-gray-50 tw-dark:hover:tw-bg-gray-800/30 tw-transition-colors";
            
            wrapper.oncontextmenu = (e) => { openContextMenu(e, 'block', doc.id, index); };

            const handle = document.createElement('div');
            handle.className = "drag-handle tw-absolute tw-left-0 tw-top-1.5 tw-p-1 tw-cursor-grab tw-text-gray-400 tw-hover:tw-text-gray-600 tw-dark:tw-hover:tw-text-gray-300 tw-rounded tw-hover:tw-bg-gray-200 tw-dark:tw-hover:tw-bg-gray-800";
            handle.innerHTML = `<i class="ph ph-dots-six-vertical"></i>`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = "tw-flex-1 tw-min-w-0";

            if(['text', 'h1', 'todo'].includes(block.type)) {
                const editable = document.createElement('div');
                editable.contentEditable = true;
                editable.innerText = block.content;
                editable.className = "tw-outline-none tw-text-gray-700 tw-dark:tw-text-gray-300 empty:before:tw-text-gray-400";
                
                if(block.type === 'h1') {
                    editable.className += " tw-text-3xl tw-font-bold tw-mt-4 tw-mb-2 tw-text-gray-900 tw-dark:tw-text-white";
                    editable.setAttribute('placeholder', 'Heading 1');
                } else if(block.type === 'todo') {
                    wrapper.classList.add('tw-items-center');
                    const chk = document.createElement('input');
                    chk.type = 'checkbox';
                    chk.checked = block.checked || false;
                    chk.className = "tw-mr-3 tw-w-4 tw-h-4 tw-rounded tw-bg-gray-200 tw-dark:tw-bg-gray-700 tw-border-gray-400 tw-text-blue-500 focus:tw-ring-offset-gray-900";
                    chk.onchange = (e) => { block.checked = e.target.checked; editable.classList.toggle('tw-line-through', block.checked); editable.classList.toggle('tw-opacity-50', block.checked); saveData(); };
                    contentDiv.prepend(chk);
                    contentDiv.className += " tw-flex tw-items-center";
                    editable.className += " tw-flex-1 " + (block.checked ? "tw-line-through tw-opacity-50" : "");
                    editable.setAttribute('placeholder', 'To-do');
                } else {
                    editable.className += " tw-text-base tw-leading-relaxed";
                    editable.setAttribute('placeholder', 'Ketik sesuatu...');
                }

                editable.oninput = (e) => { block.content = e.target.innerText; triggerSave(); };
                editable.onkeydown = (e) => {
                    if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); addBlock('text', index + 1); }
                    if(e.key === 'Backspace' && editable.innerText === '') { e.preventDefault(); deleteBlock(index); }
                };
                contentDiv.appendChild(editable);

            } else if (['file', 'image'].includes(block.type)) {
                const isImg = block.type === 'image';
                const card = document.createElement('div');
                card.className = "tw-flex tw-items-center tw-gap-3 tw-p-3 tw-border tw-border-gray-200 tw-dark:tw-border-gray-700 tw-rounded-md tw-bg-white tw-dark:tw-bg-gray-800/50 tw-hover:tw-bg-gray-50 tw-dark:tw-hover:tw-bg-gray-800 tw-transition tw-select-none";
                let thumb = isImg && block.url ? `<img src="${block.url}" class="tw-w-10 tw-h-10 tw-object-cover tw-rounded tw-bg-gray-200 tw-dark:tw-bg-gray-900">` : 
                               (isImg ? `<i class="ph ph-image tw-text-2xl tw-text-green-500"></i>` : `<i class="ph ph-file-pdf tw-text-2xl tw-text-red-500"></i>`);

                card.innerHTML = `
                    <div class="tw-shrink-0">${thumb}</div>
                    <div class="tw-flex-1 tw-min-w-0 tw-overflow-hidden">
                        <div class="tw-text-sm tw-font-medium tw-truncate tw-text-gray-800 tw-dark:tw-text-gray-200">${block.filename}</div>
                        <div class="tw-text-xs tw-text-gray-500">${block.size ? (block.size/1024).toFixed(0)+' KB' : 'File'} • ${block.ext || 'FILE'}</div>
                    </div>
                    <a href="${block.url}" download target="_blank" class="tw-p-1.5 tw-text-gray-400 tw-hover:tw-text-blue-400"><i class="ph ph-download-simple"></i></a>
                `;
                contentDiv.appendChild(card);
            }
            wrapper.appendChild(handle);
            wrapper.appendChild(contentDiv);
            container.appendChild(wrapper);
        });
        
        new Sortable(container, {
            handle: '.drag-handle', animation: 150,
            onEnd: (evt) => {
                const item = doc.blocks.splice(evt.oldIndex, 1)[0];
                doc.blocks.splice(evt.newIndex, 0, item);
                saveData(); renderBlocks(doc);
            }
        });
    }

    // --- MANAJEMEN DATA ---
    function createNewCategory() {
        const name = prompt("Nama Kategori Baru:");
        if(name) {
            appData.categories.push({ id: 'cat_' + Date.now(), name: name, icon: 'ph-folder' });
            saveData(); renderSidebar();
        }
    }

    function createNewDoc(catId) {
        const newDoc = { id: 'doc_' + Date.now(), catId: catId, title: '', blocks: [{type:'text', content:''}] };
        appData.docs.push(newDoc);
        saveData();
        renderSidebar(); 
        setAppView('editor', newDoc.id); // Langsung buka
        const sub = document.getElementById(`sub-${catId}`);
        if(sub) sub.classList.remove('tw-hidden');
    }

    function addBlock(type, index = null) {
        if(!currentDocId) return;
        const doc = appData.docs.find(d => d.id === currentDocId);
        const newBlock = { type: type, content: '' };
        if(index !== null) doc.blocks.splice(index, 0, newBlock); else doc.blocks.push(newBlock);
        saveData(); renderBlocks(doc);
    }

    function deleteBlock(index) {
        const doc = appData.docs.find(d => d.id === currentDocId);
        if(doc.blocks.length <= 1) return;
        doc.blocks.splice(index, 1);
        saveData(); renderBlocks(doc);
    }

    // --- CONTEXT MENU SYSTEM ---
    function openContextMenu(e, type, id, index = null) {
        e.preventDefault(); e.stopPropagation();
        
        contextTarget = { type, id, index };
        const menu = document.getElementById('global-context-menu');
        menu.innerHTML = '';

        let items = [];
        if (type === 'category') {
            const cat = appData.categories.find(c => c.id === id);
            items = [
                { label: 'Rename', icon: 'ph-pencil-simple', action: () => renameCategory(id) },
                { divider: true },
                { label: 'Delete Category', icon: 'ph-trash', color: 'tw-text-red-500', action: () => deleteCategory(id) }
            ];
        } else if (type === 'doc') {
            const doc = appData.docs.find(d => d.id === id);
            items = [
                { label: 'Rename', icon: 'ph-pencil-simple', action: () => renameDoc(id) },
                { label: 'Open in New Tab', icon: 'ph-arrow-square-out', action: () => window.open('#'+id, '_blank') },
                { divider: true },
                { label: 'Delete Page', icon: 'ph-trash', color: 'tw-text-red-500', action: () => deleteDoc(id) }
            ];
        } else if (type === 'block') {
            items = [
                { label: 'Duplicate', icon: 'ph-copy', action: () => duplicateBlock(index) },
                { label: 'Turn into Heading 1', icon: 'ph-text-h-one', action: () => transformBlock(index, 'h1') },
                { label: 'Turn into Text', icon: 'ph-text-t', action: () => transformBlock(index, 'text') },
                { divider: true },
                { label: 'Delete Block', icon: 'ph-trash', color: 'tw-text-red-500', action: () => deleteBlock(index) }
            ];
        }

        items.forEach(item => {
            if (item.divider) {
                menu.innerHTML += `<div class="tw-border-t tw-border-gray-200 tw-dark:tw-border-gray-700 tw-my-1"></div>`;
            } else {
                const el = document.createElement('div');
                el.className = `tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-cursor-pointer tw-hover:tw-bg-gray-100 tw-dark:tw-hover:tw-bg-gray-700 tw-transition-colors ${item.color || 'tw-text-gray-700 tw-dark:tw-text-gray-200'}`;
                el.innerHTML = `<i class="ph ${item.icon} tw-text-lg"></i> <span>${item.label}</span>`;
                el.onclick = () => { item.action(); hideContextMenu(); };
                menu.appendChild(el);
            }
        });

        let x = e.clientX; let y = e.clientY;
        const menuWidth = 224; const menuHeight = menu.offsetHeight || 200;
        if (x + menuWidth > window.innerWidth) x = window.innerWidth - menuWidth;
        if (y + menuHeight > window.innerHeight) y = window.innerHeight - menuHeight;
        menu.style.left = `${x}px`; menu.style.top = `${y}px`;
        menu.classList.remove('tw-hidden');
    }

    function hideContextMenu() { document.getElementById('global-context-menu').classList.add('tw-hidden'); }

    // --- ACTIONS IMPLEMENTATION ---
    function renameCategory(id) {
        const cat = appData.categories.find(c => c.id === id);
        const newName = prompt("Nama Baru Kategori:", cat.name);
        if (newName && newName !== cat.name) { cat.name = newName; saveData(); renderSidebar(); if(currentView === 'dashboard') setAppView('dashboard'); }
    }
    function deleteCategory(id) {
        if(confirm("Hapus kategori ini beserta semua dokumen di dalamnya?")) {
            appData.categories = appData.categories.filter(c => c.id !== id);
            appData.docs = appData.docs.filter(d => d.catId !== id);
            saveData(); renderSidebar(); setAppView('dashboard');
        }
    }
    function renameDoc(id) {
        const doc = appData.docs.find(d => d.id === id);
        const newTitle = prompt("Judul Dokumen:", doc.title);
        if (newTitle !== null) { doc.title = newTitle; saveData(); renderSidebar(); if(currentDocId === id) document.getElementById('doc-title').value = newTitle; }
    }
    function deleteDoc(id) {
        if(confirm("Hapus dokumen ini secara permanen?")) {
            appData.docs = appData.docs.filter(d => d.id !== id);
            saveData(); renderSidebar(); if(currentDocId === id) setAppView('dashboard');
        }
    }
    function duplicateBlock(index) {
        const doc = appData.docs.find(d => d.id === currentDocId);
        const block = JSON.parse(JSON.stringify(doc.blocks[index]));
        doc.blocks.splice(index + 1, 0, block); saveData(); renderBlocks(doc);
    }
    function transformBlock(index, newType) {
        const doc = appData.docs.find(d => d.id === currentDocId);
        doc.blocks[index].type = newType; saveData(); renderBlocks(doc);
    }

    // --- UPLOAD & SAVE ---
    function triggerUpload(type) {
        if(!currentDocId) return alert("Buka dokumen dulu!");
        const fileInput = document.getElementById('hidden-file-input');
        fileInput.value = '';
        fileInput.onchange = (e) => handleUploadFile(e.target.files[0], type);
        fileInput.click();
    }
    async function handleUploadFile(file, type) {
        if(!file) return;
        const doc = appData.docs.find(d => d.id === currentDocId);
        doc.blocks.push({ type: 'text', content: '⏳ Uploading...' });
        renderBlocks(doc);
        const formData = new FormData(); formData.append('file_upload', file);
        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await res.json();
            doc.blocks.pop(); 
            if(data.success) {
                doc.blocks.push({ type: type, filename: data.name, url: data.url, ext: data.ext, size: data.size });
                saveData(); renderBlocks(doc);
            } else { alert('Upload Gagal'); }
        } catch(e) { console.error(e); doc.blocks.pop(); renderBlocks(doc); }
    }

    let saveTimeout;
    function triggerSave() {
        const el = document.getElementById('save-status');
        el.innerText = 'Menyimpan...'; el.style.opacity = 1;
        clearTimeout(saveTimeout); saveTimeout = setTimeout(() => { saveData(); }, 1000);
    }
    async function saveData() {
        const formData = new FormData(); formData.append('action', 'save_state'); formData.append('data', JSON.stringify(appData));
        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const json = await res.json();
            const el = document.getElementById('save-status');
            if(json.success) { el.innerText = 'Tersimpan'; setTimeout(() => el.style.opacity = 0, 2000); }
        } catch(e) { console.error('Save error', e); }
    }
</script>

<?php
if (file_exists('../../navbar/footer.php')) {
    include '../../navbar/footer.php';
} elseif (file_exists('../navbar/footer.php')) {
    include '../navbar/footer.php';
}
?>