<?php
session_start();

// Secure Server-Side Authentication
$auth_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    if ($user === 'zero' && $pass === 'spade') {
        $_SESSION['logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $auth_error = 'ACCESS DENIED. INVALID CREDENTIALS.';
    }
}

// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$is_auth = $_SESSION['logged_in'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SYSTEM // ZERO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if ($is_auth): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.38.0/min/vs/loader.min.js"></script>
    <?php endif; ?>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brutalBg: '#e0e0e0',
                        brutalYellow: '#FFD700',
                        brutalRed: '#FF4136',
                        brutalBlue: '#0074D9',
                        brutalGreen: '#2ECC40',
                        brutalPurple: '#B10DC9'
                    },
                    boxShadow: {
                        'brutal': '6px 6px 0px 0px rgba(0,0,0,1)',
                        'brutal-sm': '3px 3px 0px 0px rgba(0,0,0,1)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Brutalist Base */
        body { 
            font-family: 'Courier New', Courier, monospace; 
            background-color: #f4f4f0; 
            color: #000;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 12px; height: 12px; border-left: 3px solid #000; }
        ::-webkit-scrollbar-track { background: #fff; }
        ::-webkit-scrollbar-thumb { background: #000; border: 2px solid #fff; }
        
        /* Brutal Hover & Active States */
        .brutal-btn {
            border: 3px solid #000;
            box-shadow: 4px 4px 0px 0px rgba(0,0,0,1);
            transition: all 0.05s ease-in-out;
            border-radius: 0 !important;
            font-weight: bold;
            text-transform: uppercase;
        }
        .brutal-btn:active {
            transform: translate(4px, 4px);
            box-shadow: 0px 0px 0px 0px rgba(0,0,0,1);
        }

        .brutal-box {
            border: 4px solid #000;
            box-shadow: 6px 6px 0px 0px rgba(0,0,0,1);
            border-radius: 0 !important;
        }

        #editor-container { height: 100%; width: 100%; }
        
        /* Mobile Specific Overrides */
        @media (max-width: 768px) {
            #mobile-sidebar.open { display: flex !important; position: absolute; z-index: 50; width: 100%; height: 100%; }
            .mobile-hide { display: none; }
        }

        /* Hide scrollbar for editor toolbar to keep it clean on mobile */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden flex flex-col font-mono selection:bg-brutalYellow selection:text-black">

<?php if (!$is_auth): ?>
    <div class="flex-1 flex items-center justify-center bg-brutalYellow p-4">
        <div class="bg-white brutal-box p-6 w-full max-w-sm flex flex-col gap-6">
            <div class="border-b-4 border-black pb-2 text-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter">Auth_Wall</h1>
                <p class="text-sm font-bold mt-1">RESTRICTED ACCESS ONLY</p>
            </div>
            
            <?php if ($auth_error): ?>
                <div class="bg-brutalRed text-white p-3 border-4 border-black font-bold text-center">
                    <?= htmlspecialchars($auth_error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <label class="font-bold text-lg">USERNAME</label>
                    <input type="text" name="username" class="p-3 border-4 border-black outline-none focus:bg-brutalBg font-bold text-lg" required autocomplete="off">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label class="font-bold text-lg">PASSWORD</label>
                    <input type="password" name="password" class="p-3 border-4 border-black outline-none focus:bg-brutalBg font-bold text-lg" required>
                </div>
                
                <button type="submit" name="login" class="bg-black text-white p-4 text-xl brutal-btn mt-2 cursor-pointer hover:bg-gray-800">
                    ENTER SYSTEM <i class="fas fa-chevron-right ml-2"></i>
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <header class="bg-white border-b-4 border-black h-16 flex items-center justify-between px-4 z-20 shrink-0">
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="md:hidden brutal-btn bg-brutalYellow px-3 py-1">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-black tracking-tighter uppercase"><i class="fab fa-github"></i> REPO_MGR_</h1>
        </div>
        <div>
            <a href="?logout=true" class="brutal-btn bg-brutalRed text-white px-4 py-2 text-sm">
                LOGOUT <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden relative bg-brutalBg">
        
        <aside id="mobile-sidebar" class="w-full md:w-72 bg-white border-r-4 border-black flex-col hidden md:flex z-40">
            <div class="p-4 border-b-4 border-black bg-brutalYellow flex justify-between items-center shrink-0">
                <span class="font-black text-lg">REPOSITORIES</span>
                <button onclick="createNewRepo()" class="brutal-btn bg-white px-2 py-1 text-sm"><i class="fas fa-plus"></i></button>
            </div>
            <button onclick="toggleSidebar()" class="md:hidden bg-brutalRed text-white font-bold p-2 border-b-4 border-black uppercase">
                CLOSE MENU <i class="fas fa-times"></i>
            </button>
            
            <div id="repo-list" class="flex-1 overflow-y-auto p-3 space-y-3 bg-white">
                <div class="p-4 font-bold border-4 border-black border-dashed text-center">LOADING...</div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <div class="bg-white border-b-4 border-black p-3 shrink-0 flex flex-col md:flex-row gap-3 justify-between items-start md:items-center overflow-x-auto no-scrollbar">
                <div id="breadcrumb" class="font-bold text-sm bg-brutalYellow border-2 border-black px-3 py-1 truncate max-w-full inline-block shadow-brutal-sm whitespace-nowrap">
                    AWAITING SELECTION...
                </div>
                
                <div id="repo-tools" class="flex flex-wrap md:flex-nowrap gap-2 w-full md:w-auto" style="display:none;">
                    <button onclick="createNewItem('folder')" class="brutal-btn bg-white px-3 py-1.5 text-xs"><i class="fas fa-folder-plus"></i> DIR</button>
                    <button onclick="createNewItem('file')" class="brutal-btn bg-white px-3 py-1.5 text-xs"><i class="fas fa-file-medical"></i> FILE</button>
                    
                    <label class="brutal-btn bg-brutalBlue text-white px-3 py-1.5 text-xs cursor-pointer flex items-center gap-1">
                        <i class="fas fa-file-zipper"></i> ZIP
                        <input type="file" id="zip-upload" accept=".zip" class="hidden" onchange="uploadZip(this)">
                    </label>

                    <button onclick="deleteCurrentFile()" id="btn-delete" class="brutal-btn bg-brutalRed text-white px-3 py-1.5 text-xs hidden"><i class="fas fa-trash"></i> DEL</button>
                    <button id="btn-save" class="brutal-btn bg-brutalGreen text-black px-4 py-1.5 text-xs hidden"><i class="fas fa-save"></i> SAVE</button>
                    <button onclick="deleteCurrentRepo()" class="brutal-btn bg-black text-white px-3 py-1.5 text-xs ml-auto md:ml-2" title="Delete Repo"><i class="fas fa-skull"></i></button>
                </div>
            </div>

            <div class="flex-1 flex flex-col md:flex-row overflow-hidden relative">
                
                <div id="file-explorer" class="w-full md:w-1/3 border-b-4 md:border-b-0 md:border-r-4 border-black overflow-y-auto bg-white hidden shrink-0" style="max-height: 40vh; md:max-height: none;">
                    <ul id="file-list" class="p-2 space-y-2"></ul>
                </div>
                
                <div id="editor-area" class="flex-1 flex flex-col relative hidden bg-black p-1">
                    
                    <div class="bg-white border-4 border-black mb-1 p-1.5 flex gap-2 overflow-x-auto no-scrollbar shrink-0">
                        <button onclick="execEditorAction('find')" class="brutal-btn bg-brutalYellow px-3 py-1 text-xs" title="Search Code">
                            <i class="fas fa-search"></i> FIND
                        </button>
                        <button onclick="execEditorAction('gotoLine')" class="brutal-btn bg-white px-3 py-1 text-xs" title="Jump to Line">
                            <i class="fas fa-level-down-alt"></i> JUMP
                        </button>
                        <button onclick="execEditorAction('zoomIn')" class="brutal-btn bg-white px-3 py-1 text-xs font-black" title="Zoom In">
                            <i class="fas fa-search-plus"></i> +
                        </button>
                        <button onclick="execEditorAction('zoomOut')" class="brutal-btn bg-white px-3 py-1 text-xs font-black" title="Zoom Out">
                            <i class="fas fa-search-minus"></i> -
                        </button>
                        <button onclick="execEditorAction('copy')" class="brutal-btn bg-brutalBlue text-white px-3 py-1 text-xs ml-auto" title="Copy All">
                            <i class="fas fa-copy"></i> COPY
                        </button>
                        <button onclick="execEditorAction('clear')" class="brutal-btn bg-brutalRed text-white px-3 py-1 text-xs" title="Clear All Code">
                            <i class="fas fa-eraser"></i> WIPE
                        </button>
                    </div>

                    <div class="w-full flex-1 border-4 border-white relative">
                        <div id="editor-container"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden', 'mobile-hide');
                sidebar.classList.add('open');
            } else {
                sidebar.classList.add('hidden', 'mobile-hide');
                sidebar.classList.remove('open');
            }
        }

        // Editor Control Functions
        function execEditorAction(action) {
            // 'editor' object is globally defined in app.js
            if (typeof editor === 'undefined' || !editor) return;

            switch(action) {
                case 'find':
                    editor.trigger('keyboard', 'actions.find', null);
                    break;
                case 'gotoLine':
                    editor.trigger('keyboard', 'editor.action.gotoLine', null);
                    editor.focus();
                    break;
                case 'zoomIn':
                    editor.trigger('keyboard', 'editor.action.fontZoomIn', null);
                    break;
                case 'zoomOut':
                    editor.trigger('keyboard', 'editor.action.fontZoomOut', null);
                    break;
                case 'copy':
                    navigator.clipboard.writeText(editor.getValue()).then(() => {
                        const btn = event.currentTarget;
                        const originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-check"></i> COPIED';
                        btn.classList.replace('bg-brutalBlue', 'bg-brutalGreen');
                        setTimeout(() => {
                            btn.innerHTML = originalHtml;
                            btn.classList.replace('bg-brutalGreen', 'bg-brutalBlue');
                        }, 2000);
                    });
                    break;
                case 'clear':
                    if (confirm('DANGER: WIPE ALL TEXT FROM EDITOR?')) {
                        editor.setValue('');
                    }
                    break;
            }
        }
        
        // CSS Overrides specifically for dynamically added elements via app.js
        document.addEventListener('DOMContentLoaded', () => {
            const style = document.createElement('style');
            style.innerHTML = `
                /* Override app.js classes for Brutalism */
                #repo-list div, #file-list li {
                    border: 3px solid #000;
                    margin-bottom: 6px;
                    padding: 8px 12px;
                    font-weight: bold;
                    background: #fff;
                    box-shadow: 3px 3px 0px 0px rgba(0,0,0,1);
                    transition: all 0.05s;
                    border-radius: 0 !important;
                }
                #repo-list div:hover, #file-list li:hover {
                    background: #FFD700;
                    transform: translate(-1px, -1px);
                    box-shadow: 4px 4px 0px 0px rgba(0,0,0,1);
                }
                #repo-list div:active, #file-list li:active {
                    transform: translate(2px, 2px);
                    box-shadow: 0px 0px 0px 0px rgba(0,0,0,1);
                }
            `;
            document.head.appendChild(style);
        });
    </script>
    
    <script src="app.js"></script>

<?php endif; ?>
</body>
</html>
