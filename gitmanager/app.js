let editor;
let currentRepo = '';
let currentPath = ''; 
let currentFileSha = '';
let currentSelectedFile = '';

require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.38.0/min/vs' }});
require(['vs/editor/editor.main'], function() {
    editor = monaco.editor.create(document.getElementById('editor-container'), {
        value: "// Select a file to edit",
        language: "javascript",
        theme: "vs-dark",
        automaticLayout: true
    });
});

async function loadRepos() {
    try {
        const res = await fetch('./github-api.php?action=list_repos');
        const data = await res.json();
        const repoList = document.getElementById('repo-list');
        repoList.innerHTML = '';

        if(data.code === 200) {
            data.body.forEach(repo => {
                const icon = repo.private ? 'fa-lock' : 'fa-globe';
                const el = document.createElement('div');
                el.className = 'cursor-pointer p-2 hover:bg-brutalYellow dark:hover:bg-gray-700 rounded flex items-center gap-2 truncate border-2 border-transparent transition-all';
                el.innerHTML = `<i class="fas ${icon} text-gray-800"></i> ${repo.name}`;
                el.onclick = () => openRepo(repo.name, '');
                repoList.appendChild(el);
            });
        } else {
            repoList.innerHTML = `<div class="text-red-500 font-bold p-2 text-xs">ERROR: ${data.body.message || 'Check Token'}</div>`;
        }
    } catch (err) {
        document.getElementById('repo-list').innerHTML = `<div class="text-red-500 p-2 text-xs font-bold">NETWORK ERROR</div>`;
    }
}

async function createNewRepo() {
    const name = prompt("Enter new repository name:");
    if (!name) return;
    
    document.getElementById('breadcrumb').innerHTML = "CREATING REPO... WAIT";
    
    const res = await fetch('./github-api.php?action=create_repo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, private: true })
    });
    
    const data = await res.json();
    if(data.code === 201 || data.code === 200) {
        alert("REPOSITORY CREATED SUCCESSFULLY!");
        loadRepos();
    } else {
        alert("FAILED TO CREATE REPO: " + (data.body.message || "Unknown error"));
    }
}

async function deleteCurrentRepo() {
    if (!currentRepo) return;
    const confirmDelete = prompt(`DANGER: Type "${currentRepo}" to delete this repository.`);
    if (confirmDelete === currentRepo) {
        const res = await fetch('./github-api.php?action=delete_repo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ repo: currentRepo })
        });
        
        const data = await res.json();
        if(data.code === 204 || data.code === 200) {
            alert("REPOSITORY DELETED!");
            document.getElementById('repo-tools').style.display = 'none';
            document.getElementById('file-explorer').classList.add('hidden');
            document.getElementById('editor-area').classList.add('hidden');
            loadRepos();
        } else {
            alert("FAILED TO DELETE: " + (data.body.message || "Unknown error"));
        }
    } else if (confirmDelete !== null) {
        alert("NAME MISMATCH. DELETION CANCELLED.");
    }
}

async function openRepo(repoName, path = '') {
    currentRepo = repoName;
    currentPath = path;
    document.getElementById('repo-tools').style.display = 'flex';
    document.getElementById('breadcrumb').innerHTML = `<i class="fas fa-folder-open text-yellow-500 cursor-pointer" onclick="openRepo('${repoName}', '')"></i> ${repoName} / ${path}`;
    document.getElementById('file-explorer').classList.remove('hidden');
    document.getElementById('editor-area').classList.remove('hidden');
    
    document.getElementById('btn-save').classList.add('hidden');
    document.getElementById('btn-delete').classList.add('hidden');
    if (editor) editor.setValue("// Select a file to edit");

    const fileList = document.getElementById('file-list');
    fileList.innerHTML = `<li class="p-2 text-gray-500 font-bold">LOADING FILES...</li>`;

    const res = await fetch(`./github-api.php?action=get_repo_contents&repo=${repoName}&path=${path}`);
    const data = await res.json();

    fileList.innerHTML = '';
    if(data.code === 200 && Array.isArray(data.body)) {
        if (path !== '') {
            const backPath = path.split('/').slice(0, -1).join('/');
            const backEl = document.createElement('li');
            backEl.className = 'cursor-pointer p-2 hover:bg-brutalYellow rounded flex items-center gap-2 font-black border-2 border-black';
            backEl.innerHTML = `<i class="fas fa-level-up-alt"></i> BACK`;
            backEl.onclick = () => openRepo(repoName, backPath);
            fileList.appendChild(backEl);
        }

        data.body.sort((a, b) => a.type === b.type ? 0 : a.type === 'dir' ? -1 : 1);
        data.body.forEach(item => {
            const isDir = item.type === 'dir';
            const icon = isDir ? 'fa-folder text-yellow-600' : 'fa-file-code text-blue-600';
            const li = document.createElement('li');
            li.className = 'cursor-pointer p-2 hover:bg-brutalYellow rounded flex items-center gap-2 truncate font-bold';
            li.innerHTML = `<i class="fas ${icon}"></i> ${item.name}`;
            
            li.onclick = () => {
                if(isDir) openRepo(repoName, item.path);
                else openFile(repoName, item.path);
            };
            fileList.appendChild(li);
        });
    } else {
        fileList.innerHTML = `<li class="p-2 text-red-500 font-bold">EMPTY OR ERROR</li>`;
    }
}

async function openFile(repo, path) {
    currentSelectedFile = path;
    document.getElementById('breadcrumb').innerHTML = "LOADING FILE...";
    
    const res = await fetch(`./github-api.php?action=get_file&repo=${repo}&path=${path}`);
    const data = await res.json();
    
    if(data.code === 200) {
        currentFileSha = data.body.sha;
        const content = data.body.decoded_content || '';
        const ext = path.split('.').pop();
        let lang = 'plaintext';
        if(['js', 'json', 'php', 'html', 'css'].includes(ext)) lang = ext;
        if(ext === 'js') lang = 'javascript';
        
        monaco.editor.setModelLanguage(editor.getModel(), lang);
        editor.setValue(content);
        
        document.getElementById('breadcrumb').innerHTML = `<i class="fas fa-file"></i> ${path}`;
        document.getElementById('btn-save').classList.remove('hidden');
        document.getElementById('btn-delete').classList.remove('hidden');
    } else {
        alert("FAILED TO LOAD FILE: " + (data.body.message || "Unknown error"));
        document.getElementById('breadcrumb').innerHTML = "FILE LOAD FAILED";
    }
}

async function createNewItem(type) {
    let name = prompt(`Enter ${type} name (e.g., style.css):`);
    if (!name) return;

    let newPath = currentPath ? `${currentPath}/${name}` : name;
    let content = " "; // GitHub does not like completely empty files sometimes, so we send a space.

    if (type === 'folder') {
        newPath = `${newPath}/.gitkeep`;
        content = "Auto-generated folder placeholder.";
    }

    document.getElementById('breadcrumb').innerHTML = "CREATING... PLEASE WAIT";

    const res = await fetch('./github-api.php?action=save_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            repo: currentRepo,
            path: newPath,
            content: content,
            message: `Create ${type} ${name}`
        })
    });
    
    const data = await res.json();
    if(data.code === 201 || data.code === 200) {
        alert(`${type.toUpperCase()} CREATED SUCCESSFULLY!`);
    } else {
        alert(`FAILED TO CREATE ${type.toUpperCase()}: ` + (data.body.message || "Unknown error"));
    }
    
    openRepo(currentRepo, currentPath); // Refresh
}

async function deleteCurrentFile() {
    if (!confirm(`DANGER: Are you sure you want to delete ${currentSelectedFile}?`)) return;

    document.getElementById('breadcrumb').innerHTML = "DELETING... PLEASE WAIT";

    const res = await fetch('./github-api.php?action=delete_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            repo: currentRepo,
            path: currentSelectedFile,
            sha: currentFileSha
        })
    });
    
    const data = await res.json();
    if (data.code === 200 || data.code === 204) {
        alert("FILE DELETED SUCCESSFULLY!");
        document.getElementById('btn-save').classList.add('hidden');
        document.getElementById('btn-delete').classList.add('hidden');
        editor.setValue("// File deleted");
    } else {
        alert("FAILED TO DELETE FILE: " + (data.body.message || "Unknown error"));
    }
    
    openRepo(currentRepo, currentPath); // Refresh folder
}

document.getElementById('btn-save').onclick = async () => {
    const btn = document.getElementById('btn-save');
    btn.innerHTML = 'SAVING...';
    btn.disabled = true;
    
    const res = await fetch('./github-api.php?action=save_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            repo: currentRepo,
            path: currentSelectedFile,
            content: editor.getValue(),
            sha: currentFileSha,
            message: `Update ${currentSelectedFile}`
        })
    });
    
    const data = await res.json();
    if (data.code === 200 || data.code === 201) {
        currentFileSha = data.body.content.sha;
        alert('FILE SAVED SUCCESSFULLY!');
    } else {
        alert("FAILED TO SAVE: " + (data.body.message || "Unknown error"));
    }
    
    btn.innerHTML = '<i class="fas fa-save"></i> SAVE';
    btn.disabled = false;
};

async function uploadZip(input) {
    if (!input.files[0] || !currentRepo) return;
    
    const formData = new FormData();
    formData.append('zipfile', input.files[0]);
    formData.append('repo', currentRepo);
    formData.append('path', currentPath);

    document.getElementById('breadcrumb').innerHTML = "EXTRACTING ZIP... DO NOT CLOSE";

    const res = await fetch('./github-api.php?action=upload_zip', {
        method: 'POST',
        body: formData
    });
    
    const data = await res.json();
    if (data.code === 200) {
        alert("ZIP UPLOADED & EXTRACTED SUCCESSFULLY!");
    } else {
        alert("ERROR EXTRACTING ZIP: " + (data.error || "Unknown error"));
    }
    
    input.value = ''; // Reset input
    openRepo(currentRepo, currentPath);
}

loadRepos();
