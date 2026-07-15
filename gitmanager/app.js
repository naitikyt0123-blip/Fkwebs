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
                el.className = 'cursor-pointer p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded flex items-center gap-2 truncate';
                el.innerHTML = `<i class="fas ${icon} text-gray-500"></i> ${repo.name}`;
                el.onclick = () => openRepo(repo.name, '');
                repoList.appendChild(el);
            });
        }
    } catch (err) {
        document.getElementById('repo-list').innerHTML = `<div class="text-red-500 p-2 text-xs">Error Loading Repos</div>`;
    }
}

async function createNewRepo() {
    const name = prompt("Enter new repository name:");
    if (!name) return;
    
    await fetch('./github-api.php?action=create_repo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, private: true })
    });
    loadRepos();
}

async function deleteCurrentRepo() {
    if (!currentRepo) return;
    const confirmDelete = prompt(`DANGER: Type "${currentRepo}" to delete this repository.`);
    if (confirmDelete === currentRepo) {
        await fetch('./github-api.php?action=delete_repo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ repo: currentRepo })
        });
        document.getElementById('repo-tools').style.display = 'none';
        document.getElementById('file-explorer').classList.add('hidden');
        document.getElementById('editor-area').classList.add('hidden');
        loadRepos();
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
    editor.setValue("// Select a file to edit");

    const fileList = document.getElementById('file-list');
    fileList.innerHTML = `<li class="p-2 text-gray-400">Loading files...</li>`;

    // Added ./ to fix the 404 HTML parse error
    const res = await fetch(`./github-api.php?action=get_repo_contents&repo=${repoName}&path=${path}`);
    const data = await res.json();

    fileList.innerHTML = '';
    if(data.code === 200 && Array.isArray(data.body)) {
        if (path !== '') {
            // Add back button for folders
            const backPath = path.split('/').slice(0, -1).join('/');
            const backEl = document.createElement('li');
            backEl.className = 'cursor-pointer p-2 hover:bg-gray-700 rounded flex items-center gap-2 font-bold';
            backEl.innerHTML = `<i class="fas fa-level-up-alt"></i> ..`;
            backEl.onclick = () => openRepo(repoName, backPath);
            fileList.appendChild(backEl);
        }

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
    } else {
        fileList.innerHTML = `<li class="p-2 text-gray-400">Empty directory</li>`;
    }
}

async function openFile(repo, path) {
    currentSelectedFile = path;
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
        
        document.getElementById('btn-save').classList.remove('hidden');
        document.getElementById('btn-delete').classList.remove('hidden');
    }
}

async function createNewItem(type) {
    let name = prompt(`Enter ${type} name:`);
    if (!name) return;

    let newPath = currentPath ? `${currentPath}/${name}` : name;
    let content = "";

    // GitHub requires folders to have at least one file. We create a hidden .gitkeep
    if (type === 'folder') {
        newPath = `${newPath}/.gitkeep`;
        content = "Auto-generated folder placeholder.";
    }

    await fetch('./github-api.php?action=save_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            repo: currentRepo,
            path: newPath,
            content: content,
            message: `Create ${type} ${name}`
        })
    });
    
    openRepo(currentRepo, currentPath); // Refresh
}

async function deleteCurrentFile() {
    if (!confirm(`Are you sure you want to delete ${currentSelectedFile}?`)) return;

    await fetch('./github-api.php?action=delete_file', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            repo: currentRepo,
            path: currentSelectedFile,
            sha: currentFileSha
        })
    });
    
    document.getElementById('btn-save').classList.add('hidden');
    document.getElementById('btn-delete').classList.add('hidden');
    editor.setValue("// File deleted");
    openRepo(currentRepo, currentPath); // Refresh folder
}

document.getElementById('btn-save').onclick = async () => {
    const btn = document.getElementById('btn-save');
    btn.innerHTML = 'Saving...';
    
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
        alert('File saved successfully!');
    }
    btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save Code';
};

async function uploadZip(input) {
    if (!input.files[0] || !currentRepo) return;
    
    const formData = new FormData();
    formData.append('zipfile', input.files[0]);
    formData.append('repo', currentRepo);
    formData.append('path', currentPath);

    document.getElementById('breadcrumb').innerHTML += " - <b>Extracting ZIP... Please wait.</b>";

    const res = await fetch('./github-api.php?action=upload_zip', {
        method: 'POST',
        body: formData
    });
    
    const data = await res.json();
    if (data.code === 200) {
        alert("ZIP uploaded and extracted successfully!");
        openRepo(currentRepo, currentPath);
    } else {
        alert("Error extracting ZIP.");
    }
    input.value = ''; // Reset input
}

loadRepos();
