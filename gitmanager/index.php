<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private GitHub Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.38.0/min/vs/loader.min.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        #editor-container { height: calc(100vh - 120px); width: 100%; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 flex h-screen overflow-hidden text-sm">

    <aside class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 font-bold text-lg flex justify-between items-center">
            <span><i class="fab fa-github mr-2"></i> Repositories</span>
            <button onclick="createNewRepo()" class="text-blue-500 hover:text-blue-400" title="New Repository"><i class="fas fa-plus"></i></button>
        </div>
        <div id="repo-list" class="flex-1 overflow-y-auto p-2 space-y-1">
            <div class="text-center p-4 text-gray-500"><i class="fas fa-spinner spinner"></i> Loading...</div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen">
        <header class="h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4">
            <div id="breadcrumb" class="text-gray-600 dark:text-gray-300 font-medium flex items-center gap-2">
                Select a repository
            </div>
            <div class="flex gap-2" id="repo-tools" style="display:none;">
                <button onclick="createNewItem('folder')" class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-1.5 rounded"><i class="fas fa-folder-plus"></i> New Folder</button>
                <button onclick="createNewItem('file')" class="bg-gray-600 hover:bg-gray-500 text-white px-3 py-1.5 rounded"><i class="fas fa-file-medical"></i> New File</button>
                
                <label class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded cursor-pointer">
                    <i class="fas fa-file-zipper"></i> Upload ZIP
                    <input type="file" id="zip-upload" accept=".zip" class="hidden" onchange="uploadZip(this)">
                </label>

                <button onclick="deleteCurrentFile()" id="btn-delete" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded hidden"><i class="fas fa-trash"></i> Delete File</button>
                <button id="btn-save" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded hidden"><i class="fas fa-save mr-1"></i> Save Code</button>
                <button onclick="deleteCurrentRepo()" class="bg-red-800 hover:bg-red-700 text-white px-3 py-1.5 rounded" title="Delete Repo"><i class="fas fa-trash-alt"></i></button>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden">
            <div id="file-explorer" class="w-1/3 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 overflow-y-auto hidden p-2">
                <ul id="file-list" class="space-y-1"></ul>
            </div>
            <div id="editor-area" class="flex-1 relative bg-white dark:bg-gray-900 hidden">
                <div id="editor-container"></div>
            </div>
        </div>
    </main>

    <script src="app.js"></script>
</body>
</html>
