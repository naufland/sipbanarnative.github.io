<?php
// =================================================================
// == FILE WORKSPACE / NOTION CLONE (FULL FEATURES + RENAME FIX) ===
// =================================================================

// --- 1. KONFIGURASI DATABASE ---
$host = 'localhost';
$user = 'root';      // Sesuaikan
$pass = '';          // Sesuaikan
$db   = 'sipbanar';  // Sesuaikan

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// --- 2. INISIALISASI TABEL ---
$conn->query("CREATE TABLE IF NOT EXISTS uploads (id INT AUTO_INCREMENT PRIMARY KEY, file_name VARCHAR(255), file_path VARCHAR(255), file_size INT, file_type VARCHAR(50), uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS app_state (id INT PRIMARY KEY, json_data LONGTEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

// --- 3. API HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload Handler
    if (isset($_FILES['file_upload'])) {
        error_reporting(0); ini_set('display_errors', 0);
        $uploadDir = '../../uploads/workspace/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['file_upload']['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $targetFilePath = $uploadDir . uniqid() . '_' . $fileName;
        
        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $targetFilePath)) {
            $fileSize = $_FILES['file_upload']['size'];
            $conn->query("INSERT INTO uploads (file_name, file_path, file_size, file_type) VALUES ('$fileName', '$targetFilePath', $fileSize, '$fileType')");
            echo json_encode(['success' => true, 'url' => $targetFilePath, 'name' => $fileName, 'ext' => $fileType, 'size' => $fileSize]);
        } else { echo json_encode(['success' => false, 'message' => 'Gagal Upload']); }
        exit;
    }
    // Save State Handler
    if (isset($_POST['action']) && $_POST['action'] === 'save_state') {
        $data = $conn->real_escape_string($_POST['data']);
        $check = $conn->query("SELECT id FROM app_state WHERE id = 1");
        if ($check->num_rows > 0) $conn->query("UPDATE app_state SET json_data = '$data' WHERE id = 1");
        else $conn->query("INSERT INTO app_state (id, json_data) VALUES (1, '$data')");
        echo json_encode(['success' => true]);
        exit;
    }
}

// Load Data
$initialData = 'null';
$res = $conn->query("SELECT json_data FROM app_state WHERE id = 1");
if ($res->num_rows > 0) { $row = $res->fetch_assoc(); if (!empty($row['json_data'])) $initialData = $row['json_data']; }

// Include Header
if (file_exists('../../navbar/header_login.php')) include '../../navbar/header_login.php';
elseif (file_exists('../navbar/header.php')) include '../navbar/header.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    tailwind.config = {
        prefix: 'tw-', darkMode: 'class', corePlugins: { preflight: false },
        theme: { extend: { colors: { notion: { bg: '#FFFFFF', sidebar: '#F7F7F5', hover: '#EFEFEF', text: '#37352F', border: '#E9E9E8' }, dark: { bg: '#191919', sidebar: '#202020', hover: '#2C2C2C', text: '#D4D4D4', border: '#2F2F2F' } } } }
    }
</script>

<style>
    .workspace-outer-wrapper { padding: 20px 0; min-height: calc(100vh - 80px); position: relative; z-index: 0; }
    #notion-app-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif; height: 85vh; overflow: hidden; position: relative; border: 1px solid #d1d5db; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    [contenteditable]:empty:before { content: attr(placeholder); color: #9ca3af; cursor: text; }
    .block-wrapper .drag-handle { opacity: 0; transition: opacity 0.2s; }
    .block-wrapper:hover .drag-handle { opacity: 1; }
    .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .dark .custom-scroll::-webkit-scrollbar-thumb { background: #4b5563; }
    
    /* FIX POPUP Z-INDEX & Context Menu */
    .swal2-container { z-index: 2147483647 !important; }
    #global-context-menu { z-index: 99999; }
</style>

<div class="container-fluid">
    <div class="workspace-outer-wrapper">
        <div id="notion-root"> 
            <div id="notion-app-container" class="tw-bg-notion-bg tw-dark:tw-bg-dark-bg tw-text-notion-text tw-dark:tw-text-dark-text tw-flex">
                <aside id="sidebar" class="tw-w-64 tw-bg-notion-sidebar tw-dark:tw-bg-dark-sidebar tw-border-r tw-border-notion-border tw-dark:tw-border-dark-border tw-flex tw-flex-col tw-h-full tw-flex-shrink-0">
                    <div class="tw-h-14 tw-flex tw-items-center tw-px-4 tw-font-bold tw-text-sm tw-cursor-pointer tw-text-orange-500" onclick="window.setAppView('dashboard')">
                        <div class="tw-w-6 tw-h-6 tw-bg-orange-600 tw-text-white tw-rounded tw-flex tw-items-center tw-justify-center tw-text-xs tw-mr-2">N</div> Workspace
                    </div>
                    <div class="tw-px-3 tw-pb-2">
                        <div onclick="window.setAppView('dashboard')" class="tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-1.5 tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover tw-rounded tw-cursor-pointer tw-text-sm tw-text-gray-500">
                            <i class="ph ph-squares-four"></i> <span>Dashboard</span>
                        </div>
                    </div>
                    <div class="tw-flex-1 tw-overflow-y-auto tw-py-2 custom-scroll" id="sidebar-list"></div>
                    <div class="tw-p-3 tw-border-t tw-border-notion-border tw-dark:tw-border-dark-border">
                        <button onclick="window.createNewCategory()" class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-500 tw-hover:tw-text-white tw-px-2 tw-py-1 tw-w-full tw-text-left tw-rounded tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover">
                            <i class="ph ph-plus"></i> Kategori Baru
                        </button>
                    </div>
                </aside>

                <main class="tw-flex-1 tw-flex tw-flex-col tw-h-full tw-relative tw-w-full tw-bg-notion-bg tw-dark:tw-bg-dark-bg">
                    <header class="tw-h-12 tw-flex tw-items-center tw-justify-between tw-px-4 tw-border-b tw-border-transparent tw-hover:tw-border-notion-border tw-dark:tw-hover:tw-border-dark-border">
                        <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-notion-text tw-dark:tw-text-gray-400" id="breadcrumbs"></div>
                        <div class="tw-flex tw-items-center tw-gap-1">
                            <span id="save-status" class="tw-text-xs tw-text-green-600 tw-opacity-0 tw-transition">Saved</span>
                            <button onclick="window.toggleTheme()" class="tw-p-1.5 tw-rounded tw-hover:tw-bg-notion-hover tw-dark:tw-hover:tw-bg-dark-hover"><i id="theme-icon" class="ph ph-moon"></i></button>
                        </div>
                    </header>

                    <div id="editor-toolbar" class="tw-h-12 tw-border-b tw-border-notion-border tw-dark:tw-border-dark-border tw-flex tw-items-center tw-px-4 tw-gap-2 tw-bg-white tw-dark:tw-bg-dark-bg tw-hidden">
                        <button onclick="window.triggerUpload('file')" class="tw-flex tw-items-center tw-gap-2 tw-px-2 tw-py-1 tw-rounded tw-bg-blue-500/10 tw-text-blue-400 tw-hover:tw-bg-blue-500/20 tw-text-xs tw-font-medium"><i class="ph ph-paperclip"></i> File</button>
                        <button onclick="window.triggerUpload('image')" class="tw-flex tw-items-center tw-gap-2 tw-px-2 tw-py-1 tw-rounded tw-bg-green-500/10 tw-text-green-400 tw-hover:tw-bg-green-500/20 tw-text-xs tw-font-medium"><i class="ph ph-image"></i> Gambar</button>
                        <div class="tw-w-px tw-h-5 tw-bg-gray-300 tw-mx-2"></div>
                        <button onclick="window.addBlock('h1')" class="tw-p-1.5 tw-rounded hover:tw-bg-gray-100 tw-dark:hover:tw-bg-gray-800" title="Heading 1"><i class="ph ph-text-h-one"></i></button>
                        <button onclick="window.addBlock('text')" class="tw-p-1.5 tw-rounded hover:tw-bg-gray-100 tw-dark:hover:tw-bg-gray-800" title="Text"><i class="ph ph-text-t"></i></button>
                        <button onclick="window.addBlock('todo')" class="tw-p-1.5 tw-rounded hover:tw-bg-gray-100 tw-dark:hover:tw-bg-gray-800" title="To-do List"><i class="ph ph-check-square"></i></button>
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
<div id="global-context-menu" class="tw-hidden tw-fixed tw-bg-white tw-dark:tw-bg-gray-800 tw-rounded-lg tw-shadow-xl tw-border tw-border-gray-200 tw-dark:tw-border-gray-700 tw-py-1 tw-text-sm tw-overflow-hidden"></div>

<script>
    // --- GLOBAL VARIABLES (ATTACH TO WINDOW) ---
    window.serverData = <?php echo $initialData; ?>;
    window.appData = window.serverData || { categories: [{ id: 'cat_' + Date.now(), name: 'Umum', icon: 'ph-folder' }], docs: [] };
    window.currentView = 'dashboard'; 
    window.currentDocId = null;
    window.saveTimeout = null;

    // --- INITIALIZATION ---
    document.addEventListener('DOMContentLoaded', () => {
        document.body.appendChild(document.getElementById('global-context-menu'));

        const savedTheme = localStorage.getItem('workspace_theme');
        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.getElementById('notion-root').classList.add('dark');
            document.getElementById('theme-icon').classList.replace('ph-moon', 'ph-sun');
        }

        window.renderSidebar();
        window.setAppView('dashboard');

        document.addEventListener('click', (e) => {
            const menu = document.getElementById('global-context-menu');
            if (!menu.classList.contains('tw-hidden') && !menu.contains(e.target)) window.hideContextMenu();
        });
    });

    // --- GLOBAL FUNCTIONS ---
    window.toggleTheme = function() {
        const root = document.getElementById('notion-root');
        const icon = document.getElementById('theme-icon');
        if (root.classList.contains('dark')) {
            root.classList.remove('dark'); localStorage.setItem('workspace_theme', 'light');
            icon.classList.replace('ph-sun', 'ph-moon');
        } else {
            root.classList.add('dark'); localStorage.setItem('workspace_theme', 'dark');
            icon.classList.replace('ph-moon', 'ph-sun');
        }
    };

    window.setAppView = function(view, docId = null) {
        window.currentView = view;
        window.currentDocId = docId;
        const container = document.getElementById('workspace-content');
        const toolbar = document.getElementById('editor-toolbar');
        
        if(view === 'dashboard') {
            toolbar.classList.add('tw-hidden');
            document.getElementById('breadcrumbs').innerHTML = `<span class="tw-cursor-pointer" onclick="window.setAppView('dashboard')">Home</span>`;
            
            let html = `
            <div class="tw-max-w-5xl tw-mx-auto tw-p-12">
                <div class="tw-text-3xl tw-font-bold tw-mb-8 tw-text-center tw-text-gray-800 tw-dark:tw-text-white">Workspace</div>
                <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-6">`;
            
            window.appData.categories.forEach(c => {
                const count = window.appData.docs.filter(d => String(d.catId) === String(c.id)).length;
                html += `
                <div onclick="window.openCategoryAction('${c.id}')" oncontextmenu="window.openContextMenu(event, 'category', '${c.id}')" class="tw-p-6 tw-border tw-border-gray-200 tw-dark:tw-border-gray-700 tw-rounded-xl tw-bg-white tw-dark:tw-bg-dark-sidebar tw-cursor-pointer hover:tw-border-blue-500 tw-group">
                    <i class="ph ${c.icon || 'ph-folder'} tw-text-3xl tw-text-blue-500 tw-mb-4 tw-block"></i>
                    <div class="tw-font-bold tw-text-lg tw-text-gray-800 tw-dark:tw-text-gray-200">${c.name}</div>
                    <div class="tw-text-sm tw-text-gray-500">${count} items</div>
                </div>`;
            });

            html += `
                <div onclick="window.createNewCategory()" class="tw-p-6 tw-border tw-border-dashed tw-border-gray-300 tw-rounded-xl tw-cursor-pointer hover:tw-bg-gray-50 tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-gray-500">
                    <i class="ph ph-plus tw-text-3xl tw-mb-2"></i> <span>Add Category</span>
                </div>
            </div></div>`;
            container.innerHTML = html;
        } else {
            toolbar.classList.remove('tw-hidden');
            window.renderEditor(docId);
        }
    };

    window.openCategoryAction = function(catId) {
        const sub = document.getElementById(`sub-${catId}`);
        if(sub) sub.classList.remove('tw-hidden');
        const firstDoc = window.appData.docs.find(d => String(d.catId) === String(catId));
        if (firstDoc) window.setAppView('editor', firstDoc.id);
    };

    window.renderSidebar = function() {
        const list = document.getElementById('sidebar-list');
        list.innerHTML = '';
        
        window.appData.categories.forEach(cat => {
            const group = document.createElement('div');
            group.className = "tw-mb-1";
            
            const headerHTML = `
                <div class="tw-px-3 tw-py-1 tw-text-gray-600 tw-dark:tw-text-gray-400 hover:tw-bg-notion-hover tw-dark:hover:tw-bg-dark-hover tw-rounded tw-cursor-pointer tw-flex tw-items-center tw-justify-between tw-group/cat"
                     onclick="const sub = document.getElementById('sub-${cat.id}'); sub.classList.toggle('tw-hidden');"
                     oncontextmenu="window.openContextMenu(event, 'category', '${cat.id}')">
                    <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-medium"><i class="ph ${cat.icon || 'ph-folder'}"></i> ${cat.name}</div>
                    <button onclick="event.stopPropagation(); window.createNewDoc('${cat.id}')" class="tw-opacity-0 group-hover/cat:tw-opacity-100 tw-text-xs">+</button>
                </div>
                <div id="sub-${cat.id}" class="tw-ml-4 tw-pl-2 tw-border-l tw-border-gray-300 tw-dark:tw-border-gray-700 tw-hidden tw-mt-1"></div>
            `;
            group.innerHTML = headerHTML;
            list.appendChild(group);

            const sub = group.querySelector(`#sub-${cat.id}`);
            const catDocs = window.appData.docs.filter(d => String(d.catId) === String(cat.id));
            
            if(catDocs.length === 0) {
                sub.innerHTML = `<div class="tw-px-3 tw-text-xs tw-text-gray-400 tw-italic">Empty</div>`;
            } else {
                catDocs.forEach(doc => {
                    const isActive = doc.id === window.currentDocId;
                    const item = document.createElement('div');
                    item.className = `tw-px-2 tw-py-1 tw-text-sm tw-rounded tw-cursor-pointer tw-flex tw-items-center tw-gap-2 tw-truncate ${isActive ? 'tw-bg-blue-100 tw-text-blue-600' : 'tw-text-gray-600 hover:tw-bg-notion-hover'}`;
                    item.innerHTML = `<i class="ph ph-file-text tw-text-xs"></i> <span class="tw-truncate">${doc.title || 'Untitled'}</span>`;
                    item.onclick = (e) => { e.stopPropagation(); window.setAppView('editor', doc.id); };
                    item.oncontextmenu = (e) => { window.openContextMenu(e, 'doc', doc.id); };
                    sub.appendChild(item);
                });
            }
        });
    };

    // --- CONTEXT MENU SYSTEM ---
    window.openContextMenu = function(e, type, id, index = null) {
        e.preventDefault(); e.stopPropagation();
        const menu = document.getElementById('global-context-menu');
        
        let html = '';
        const addItem = (label, icon, actionJs, color = 'tw-text-gray-700 tw-dark:tw-text-gray-200') => {
            html += `<div onclick="window.hideContextMenu(); setTimeout(() => { ${actionJs} }, 50);" class="tw-flex tw-items-center tw-gap-2 tw-px-3 tw-py-2 tw-cursor-pointer hover:tw-bg-gray-100 tw-dark:hover:tw-bg-gray-700 ${color}"><i class="ph ${icon} tw-text-lg"></i> ${label}</div>`;
        };
        const addDiv = () => { html += `<div class="tw-border-t tw-border-gray-200 tw-dark:tw-border-gray-700 tw-my-1"></div>`; };

        if (type === 'category') {
            addItem('Rename', 'ph-pencil-simple', `window.renameCategory('${id}')`);
            addDiv();
            addItem('Delete', 'ph-trash', `window.deleteCategory('${id}')`, 'tw-text-red-500');
        } else if (type === 'doc') {
            addItem('Rename', 'ph-pencil-simple', `window.renameDoc('${id}')`);
            addItem('Open New Tab', 'ph-arrow-square-out', `window.open('#${id}', '_blank')`);
            addDiv();
            addItem('Delete', 'ph-trash', `window.deleteDoc('${id}')`, 'tw-text-red-500');
        } else if (type === 'block') {
            addItem('Duplicate', 'ph-copy', `window.duplicateBlock(${index})`);
            addDiv();
            addItem('Delete', 'ph-trash', `window.deleteBlock(${index})`, 'tw-text-red-500');
        }

        menu.innerHTML = html;
        let x = e.clientX, y = e.clientY;
        if (x + 200 > window.innerWidth) x = window.innerWidth - 200;
        if (y + 200 > window.innerHeight) y = window.innerHeight - 200;
        menu.style.left = x + 'px'; menu.style.top = y + 'px';
        menu.classList.remove('tw-hidden');
    };

    window.hideContextMenu = function() {
        document.getElementById('global-context-menu').classList.add('tw-hidden');
    };

    // --- RENAME & DELETE ACTIONS ---
    window.renameCategory = function(id) {
        const cat = window.appData.categories.find(c => String(c.id) === String(id));
        if (!cat) return;
        Swal.fire({ title: 'Rename Category', input: 'text', inputValue: cat.name, showCancelButton: true, confirmButtonText: 'Save' }).then((result) => {
            if (result.isConfirmed && result.value) { cat.name = result.value; window.saveData(); window.renderSidebar(); if(window.currentView === 'dashboard') window.setAppView('dashboard'); }
        });
    };

    window.deleteCategory = function(id) {
        Swal.fire({ title: 'Delete Category?', text: "All docs inside will be deleted!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete' }).then((res) => {
            if(res.isConfirmed) {
                window.appData.categories = window.appData.categories.filter(c => String(c.id) !== String(id));
                window.appData.docs = window.appData.docs.filter(d => String(d.catId) !== String(id));
                window.saveData(); window.renderSidebar(); window.setAppView('dashboard');
            }
        });
    };

    window.renameDoc = function(id) {
        const doc = window.appData.docs.find(d => String(d.id) === String(id));
        if(!doc) return;
        Swal.fire({ title: 'Rename Page', input: 'text', inputValue: doc.title, showCancelButton: true }).then((res) => {
            if(res.isConfirmed) { doc.title = res.value || 'Untitled'; window.saveData(); window.renderSidebar(); if(window.currentDocId === id) document.getElementById('doc-title').value = doc.title; }
        });
    };

    window.deleteDoc = function(id) {
        Swal.fire({ title: 'Delete Page?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' }).then((res) => {
            if(res.isConfirmed) { window.appData.docs = window.appData.docs.filter(d => String(d.id) !== String(id)); window.saveData(); window.renderSidebar(); if(window.currentDocId === id) window.setAppView('dashboard'); }
        });
    };

    // --- DATA MANAGEMENT ---
    window.createNewCategory = function() {
        Swal.fire({ title: 'New Category', input: 'text', showCancelButton: true }).then((res) => {
            if(res.value) { window.appData.categories.push({ id: 'cat_' + Date.now(), name: res.value, icon: 'ph-folder' }); window.saveData(); window.renderSidebar(); window.setAppView('dashboard'); }
        });
    };

    window.createNewDoc = function(catId) {
        const newDoc = { id: 'doc_' + Date.now(), catId: catId, title: '', blocks: [{type:'text', content:''}] };
        window.appData.docs.push(newDoc); window.saveData(); window.renderSidebar(); window.setAppView('editor', newDoc.id);
        const sub = document.getElementById(`sub-${catId}`); if(sub) sub.classList.remove('tw-hidden');
    };

    window.saveData = async function() {
        const formData = new FormData(); formData.append('action', 'save_state'); formData.append('data', JSON.stringify(window.appData));
        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const json = await res.json();
            const el = document.getElementById('save-status');
            if(json.success) { el.innerText = 'Saved'; el.style.opacity = 1; setTimeout(() => el.style.opacity = 0, 2000); }
        } catch(e) { console.error(e); }
    };

    // --- EDITOR & BLOCK RENDERING (FULL FEATURE) ---
    window.renderEditor = function(docId) {
        const doc = window.appData.docs.find(d => d.id === docId);
        if(!doc) return window.setAppView('dashboard');
        const cat = window.appData.categories.find(c => c.id === doc.catId);
        document.getElementById('breadcrumbs').innerHTML = `<span class="tw-opacity-50" onclick="window.setAppView('dashboard')">Home</span> / <span>${cat ? cat.name : '...'}</span> / <span>${doc.title || 'Untitled'}</span>`;
        document.getElementById('workspace-content').innerHTML = `
            <div class="tw-max-w-3xl tw-mx-auto tw-px-12 tw-py-12 tw-pb-32">
                <input type="text" id="doc-title" placeholder="Untitled" value="${doc.title}" class="tw-text-4xl tw-font-bold tw-w-full tw-bg-transparent tw-border-none tw-outline-none tw-mb-6 tw-text-gray-800 tw-dark:tw-text-white">
                <div id="blocks-container" class="tw-space-y-1"></div>
            </div>`;
        document.getElementById('doc-title').oninput = (e) => { doc.title = e.target.value; window.saveData(); window.renderSidebar(); };
        window.renderBlocks(doc);
    };

    window.renderBlocks = function(doc) {
        const container = document.getElementById('blocks-container');
        container.innerHTML = '';
        doc.blocks.forEach((block, index) => {
            const div = document.createElement('div');
            div.className = "block-wrapper tw-group tw-flex tw-items-start -tw-ml-8 tw-pl-8 tw-py-1 hover:tw-bg-gray-50 tw-dark:hover:tw-bg-gray-800/30 tw-relative";
            div.oncontextmenu = (e) => window.openContextMenu(e, 'block', doc.id, index);
            
            const handle = document.createElement('div');
            handle.className = "drag-handle tw-absolute tw-left-0 tw-top-1.5 tw-p-1 tw-cursor-grab tw-text-gray-400 opacity-0 group-hover:opacity-100";
            handle.innerHTML = `<i class="ph ph-dots-six-vertical"></i>`;
            
            const content = document.createElement('div');
            content.className = "tw-flex-1 tw-min-w-0";
            
            // --- LOGIKA RENDER BERDASARKAN TIPE ---
            if (['text', 'h1', 'todo'].includes(block.type)) {
                const edit = document.createElement('div');
                edit.contentEditable = true;
                edit.innerText = block.content;
                edit.className = "tw-outline-none tw-text-gray-700 tw-dark:tw-text-gray-300 empty:before:tw-text-gray-400";
                
                edit.oninput = (e) => { block.content = e.target.innerText; clearTimeout(window.saveTimeout); window.saveTimeout = setTimeout(window.saveData, 1000); };
                edit.onkeydown = (e) => { 
                    if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); window.addBlock('text', index + 1); } 
                    if(e.key === 'Backspace' && !edit.innerText) { e.preventDefault(); window.deleteBlock(index); }
                };

                if(block.type === 'h1') {
                    edit.className += " tw-text-3xl tw-font-bold tw-mt-4 tw-mb-2 tw-text-gray-900 tw-dark:tw-text-white"; 
                    edit.setAttribute('placeholder','Heading 1');
                } else if (block.type === 'todo') {
                    div.classList.add('tw-items-center');
                    const chk = document.createElement('input');
                    chk.type = 'checkbox';
                    chk.checked = block.checked || false;
                    chk.className = "tw-mr-3 tw-w-4 tw-h-4 tw-rounded tw-bg-gray-200";
                    chk.onchange = (e) => { block.checked = e.target.checked; window.renderBlocks(doc); window.saveData(); };
                    content.prepend(chk);
                    content.className += " tw-flex tw-items-center";
                    edit.className += " tw-flex-1 " + (block.checked ? "tw-line-through tw-opacity-50" : "");
                    edit.setAttribute('placeholder','To-do');
                } else { 
                    edit.setAttribute('placeholder','Type something...'); 
                }
                content.appendChild(edit);

            } else if (['file', 'image'].includes(block.type)) {
                // RENDER CARD FILE/GAMBAR
                const isImg = block.type === 'image';
                const card = document.createElement('div');
                card.className = "tw-flex tw-items-center tw-gap-3 tw-p-3 tw-border tw-border-gray-200 tw-dark:tw-border-gray-700 tw-rounded-md tw-bg-white tw-dark:tw-bg-gray-800/50 tw-select-none";
                let thumb = isImg && block.url ? `<img src="${block.url}" class="tw-w-10 tw-h-10 tw-object-cover tw-rounded">` : (isImg ? `<i class="ph ph-image tw-text-2xl tw-text-green-500"></i>` : `<i class="ph ph-file-pdf tw-text-2xl tw-text-red-500"></i>`);
                
                card.innerHTML = `
                    <div class="tw-shrink-0">${thumb}</div>
                    <div class="tw-flex-1 tw-min-w-0 tw-overflow-hidden">
                        <div class="tw-text-sm tw-font-medium tw-truncate tw-text-gray-800 tw-dark:tw-text-gray-200">${block.filename}</div>
                        <div class="tw-text-xs tw-text-gray-500">${block.size ? (block.size/1024).toFixed(0)+' KB' : 'File'}</div>
                    </div>
                    <a href="${block.url}" download target="_blank" class="tw-p-1.5 tw-text-gray-400 hover:tw-text-blue-400"><i class="ph ph-download-simple"></i></a>
                `;
                content.appendChild(card);
            }
            
            div.appendChild(handle);
            div.appendChild(content);
            container.appendChild(div);
        });
        new Sortable(container, { handle: '.drag-handle', animation: 150, onEnd: (evt) => { const item = doc.blocks.splice(evt.oldIndex, 1)[0]; doc.blocks.splice(evt.newIndex, 0, item); window.saveData(); }});
    };

    window.addBlock = function(type, index = null) {
        if(!window.currentDocId) return;
        const doc = window.appData.docs.find(d => d.id === window.currentDocId);
        const blk = {type: type, content: ''};
        if(index !== null) doc.blocks.splice(index, 0, blk); else doc.blocks.push(blk);
        window.saveData(); window.renderBlocks(doc);
    };

    window.duplicateBlock = function(index) {
        const doc = window.appData.docs.find(d => d.id === window.currentDocId);
        const blk = JSON.parse(JSON.stringify(doc.blocks[index]));
        doc.blocks.splice(index + 1, 0, blk); window.saveData(); window.renderBlocks(doc);
    };

    window.deleteBlock = function(index) {
        const doc = window.appData.docs.find(d => d.id === window.currentDocId);
        if(doc.blocks.length > 1) { doc.blocks.splice(index, 1); window.saveData(); window.renderBlocks(doc); }
    };

    // --- UPLOAD SYSTEM ---
    window.triggerUpload = function(type) {
        if(!window.currentDocId) return Swal.fire('Info', 'Open a doc first', 'info');
        const inp = document.getElementById('hidden-file-input');
        inp.value = '';
        inp.onchange = (e) => window.handleUpload(e.target.files[0], type);
        inp.click();
    };

    window.handleUpload = async function(file, type) {
        if(!file) return;
        const doc = window.appData.docs.find(d => d.id === window.currentDocId);
        doc.blocks.push({ type: 'text', content: '⏳ Uploading...' }); window.renderBlocks(doc);
        
        const formData = new FormData(); formData.append('file_upload', file);
        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await res.json();
            doc.blocks.pop(); 
            if(data.success) {
                doc.blocks.push({ type: type, filename: data.name, url: data.url, ext: data.ext, size: data.size });
                window.saveData(); window.renderBlocks(doc);
            } else { Swal.fire('Error', data.message, 'error'); }
        } catch(e) { doc.blocks.pop(); window.renderBlocks(doc); Swal.fire('Error', 'Upload failed', 'error'); }
    };
</script>

<?php if (file_exists('../../navbar/footer.php')) include '../../navbar/footer.php'; ?>