// app.js
let editor;
let currentRepo = '';
let currentPath = '';
let currentFileSha = '';

// Initialize Monaco Editor
require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.38.0/min/vs' }});
require(['vs/editor/editor.main'], function() {
    editor = monaco.editor.create(document.getElementById('editor-container'), {
        value: "// Select a file to edit",
        language: "javascript",
        theme: "vs-dark",
        automaticLayout: true,
        wordWrap: "on",
        minimap: { enabled: true }
    });
});

// Load Repositories on Boot
async function loadRepos() {
    try {
        const res = await fetch('/gitmanager/github-api.php?action=list_repos');
        const data = await res.json();
        const repoList = document.getElementById('repo-list');
        repoList.innerHTML = '';

        if(data.code === 200) {
            data.body.forEach(repo => {
                const icon = repo.private ? 'fa-lock' : 'fa-globe';
                const el = document.createElement('div');
                el.className = 'cursor-pointer p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded flex items-center gap-2 truncate';
                el.innerHTML = `<i class="fas ${icon} text-gray-500"></i> ${repo.name}`;
                el.onclick = () => openRepo(repo.name);
                repoList.appendChild(el);
            });
        } else {
            repoList.innerHTML = `<div class="text-red-500 p-2">Error loading repos</div>`;
        }
    } catch (err) {
        console.error(err);
    }
}

// Open a Repository and List Files
async function openRepo(repoName, path = '') {
    currentRepo = repoName;
    document.getElementById('breadcrumb').innerHTML = `<i class="fas fa-folder-open text-yellow-500"></i> ${repoName} / ${path}`;
    document.getElementById('file-explorer').classList.remove('hidden');
    document.getElementById('editor-area').classList.remove('hidden');
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = `<li class="p-2 text-gray-400">Loading files...</li>`;

    const res = await fetch(`/gitmanager)github-api.php?action=get_repo_contents&repo=${repoName}&path=${path}`);
    const data = await res.json();

    fileList.innerHTML = '';
    if(data.code === 200 && Array.isArray(data.body)) {
        // Sort: Folders first
        data.body.sort((a, b) => a.type === b.type ? 0 : a.type === 'dir' ? -1 : 1);
        
        data.body.forEach(item => {
            const isDir = item.type === 'dir';
            const icon = isDir ? 'fa-folder text-yellow-500' : 'fa-file-code text-blue-400';
            const li = document.createElement('li');
            li.className = 'cursor-pointer p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded flex items-center gap-2 truncate';
            li.innerHTML = `<i class="fas ${icon}"></i> ${item.name}`;
            
            li.onclick = () => {
                if(isDir) openRepo(repoName, item.path);
                else openFile(repoName, item.path);
            };
            fileList.appendChild(li);
        });
    }
}

// Open File in Monaco
async function openFile(repo, path) {
    currentPath = path;
    const res = await fetch(`/gitmanager)github-api.php?action=get_file&repo=${repo}&path=${path}`);
    const data = await res.json();
    
    if(data.code === 200) {
        currentFileSha = data.body.sha;
        const content = data.body.decoded_content || '';
        
        // Auto-detect language via extension
        const ext = path.split('.').pop();
        const model = editor.getModel();
        let lang = 'plaintext';
        if(['js', 'json', 'php', 'html', 'css'].includes(ext)) lang = ext;
        if(ext === 'js') lang = 'javascript';
        
        monaco.editor.setModelLanguage(model, lang);
        editor.setValue(content);
        
        document.getElementById('btn-save').classList.remove('hidden');
    }
}

// Save File to GitHub
document.getElementById('btn-save').onclick = async () => {
    const btn = document.getElementById('btn-save');
    btn.innerHTML = '<i class="fas fa-spinner spinner mr-1"></i> Saving...';
    btn.disabled = true;

    const content = editor.getValue();
    const message = prompt("Commit message:", `Update ${currentPath}`);
    
    if(!message) {
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save Commit';
        btn.disabled = false;
        return;
    }

    const payload = {
        repo: currentRepo,
        path: currentPath,
        content: content,
        sha: currentFileSha,
        message: message
    };

    const res = await fetch('/gitmanager/github-api.php?action=save_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    
    const data = await res.json();
    if(data.code === 200 || data.code === 201) {
        alert('Commit Successful!');
        currentFileSha = data.body.content.sha; // Update SHA for next edit
    } else {
        alert('Error saving file. Check console.');
        console.error(data);
    }
    
    btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save Commit';
    btn.disabled = false;
};

// Start App
loadRepos();
