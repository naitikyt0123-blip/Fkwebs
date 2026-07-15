/* ============================================================
   GitHub File Manager — app.js  (frontend logic)
   Sab operations GitHub API pe (real commits).
   ============================================================ */

let currentRepo = null;   // selected repo name
let currentPath = '';     // repo ke andar path
let repos = [];

/* ---------- helpers ---------- */
const $  = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

function toast(msg, type = '') {
  const t = $('#toast');
  t.textContent = msg;
  t.className = 'toast ' + type;
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.add('hidden'), 3000);
}
function loading(on) { $('#loadingBar').classList.toggle('hidden', !on); }

async function apiGet(action, params = {}) {
  const qs = new URLSearchParams(params).toString();
  const r = await fetch(`/api/${action}` + (qs ? '?' + qs : ''), { credentials: 'same-origin' });
  return r.json();
}
async function apiPost(action, body = {}) {
  const r = await fetch(`/api/${action}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(body)
  });
  return r.json();
}

function fmtSize(bytes) {
  if (!bytes) return '—';
  const u = ['B', 'KB', 'MB', 'GB']; let i = 0, n = bytes;
  while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
  return n.toFixed(n < 10 && i > 0 ? 1 : 0) + ' ' + u[i];
}
function iconFor(item) {
  if (item.type === 'folder') return '📁';
  const img = ['jpg','jpeg','png','gif','webp','svg','bmp'];
  const code = ['js','ts','php','html','css','json','py','java','c','cpp','sh','xml','yml','yaml'];
  if (img.includes(item.ext)) return '🖼';
  if (['zip','rar','7z'].includes(item.ext)) return '🗜';
  if (item.ext === 'pdf') return '📕';
  if (['txt','md'].includes(item.ext)) return '📝';
  if (code.includes(item.ext)) return '📜';
  return '📄';
}
const isImage = (ext) => ['jpg','jpeg','png','gif','webp','svg','bmp'].includes(ext);
const isText  = (ext) => ['txt','md','js','ts','php','html','css','json','py','java','c','cpp','sh','xml','yml','yaml','csv','log','env','ini','conf','gitignore',''].includes(ext);
function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

/* ---------- prompt modal ---------- */
function askPrompt(title, def = '') {
  return new Promise((resolve) => {
    $('#promptTitle').textContent = title;
    const inp = $('#promptInput'); inp.value = def;
    openModal('promptModal'); inp.focus(); inp.select();
    const ok = () => { cleanup(); resolve(inp.value.trim()); };
    const cancel = () => { cleanup(); resolve(null); };
    function cleanup() {
      closeModal('promptModal');
      $('#promptOk').removeEventListener('click', ok);
      inp.removeEventListener('keydown', onKey);
      $$('#promptModal [data-close]').forEach(b => b.removeEventListener('click', cancel));
    }
    function onKey(e) { if (e.key === 'Enter') ok(); if (e.key === 'Escape') cancel(); }
    $('#promptOk').addEventListener('click', ok);
    inp.addEventListener('keydown', onKey);
    $$('#promptModal [data-close]').forEach(b => b.addEventListener('click', cancel));
  });
}

/* ---------- modals ---------- */
function openModal(id) { $('#' + id).classList.remove('hidden'); }
function closeModal(id) { $('#' + id).classList.add('hidden'); }
document.addEventListener('click', (e) => {
  if (e.target.matches('[data-close]')) { const m = e.target.closest('.modal'); if (m) m.classList.add('hidden'); }
  if (e.target.classList.contains('modal')) e.target.classList.add('hidden');
});

/* ---------- AUTH ---------- */
$('#loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  $('#loginError').textContent = '';
  const res = await apiPost('login', { username: $('#loginUser').value, password: $('#loginPass').value });
  if (res.ok) { showApp(); initApp(); }
  else $('#loginError').textContent = res.error || 'Login fail';
});
$('#logoutBtn').addEventListener('click', async () => { await apiPost('logout'); location.reload(); });
function showApp() { $('#loginScreen').classList.add('hidden'); $('#app').classList.remove('hidden'); }

/* ---------- INIT ---------- */
async function initApp() {
  const me = await apiGet('me');
  if (me.user) $('#userChip').textContent = '@' + me.user;
  await loadRepos();
  showReposView();
}

/* ---------- REPOSITORIES ---------- */
async function loadRepos() {
  loading(true);
  const res = await apiGet('repos');
  loading(false);
  if (!res.ok) { toast(res.error || 'Repos load fail', 'err'); return; }
  repos = res.repos;
  renderRepoSidebar();
  renderRepoTable();
}
function renderRepoSidebar() {
  const box = $('#repoList');
  box.innerHTML = '';
  repos.forEach(r => {
    const b = document.createElement('button');
    b.className = 'side-item repo-btn' + (r.name === currentRepo ? ' active' : '');
    b.innerHTML = `<span>📦 ${escapeHtml(r.name)}</span><span class="lock">${r.private ? '🔒' : ''}</span>`;
    b.onclick = () => { openRepo(r.name); closeSidebar(); };
    box.appendChild(b);
  });
}
function renderRepoTable() {
  const tb = $('#repoTable'); tb.innerHTML = '';
  repos.forEach(r => {
    const tr = document.createElement('tr');
    const tdN = document.createElement('td');
    const nm = document.createElement('div');
    nm.className = 'fname';
    nm.innerHTML = `<span class="ico">📦</span><span class="txt">${escapeHtml(r.name)}</span>`;
    nm.onclick = () => openRepo(r.name);
    tdN.appendChild(nm);
    const tdT = document.createElement('td'); tdT.className = 'col-size muted';
    tdT.textContent = r.private ? 'Private' : 'Public';
    const tdA = document.createElement('td'); tdA.className = 'col-act';
    const w = document.createElement('div'); w.className = 'row-actions';
    w.appendChild(mkBtn('Open', () => openRepo(r.name)));
    w.appendChild(mkBtn('Delete', () => deleteRepo(r.name), 'Delete repo', 'btn-danger'));
    tdA.appendChild(w);
    tr.append(tdN, tdT, tdA); tb.appendChild(tr);
  });
}
async function createRepo() {
  const name = $('#repoName').value.trim();
  if (!name) { toast('Repo name daalo', 'err'); return; }
  const priv = $('#repoPrivate').checked;
  loading(true);
  const res = await apiPost('repo/create', { name, private: priv });
  loading(false);
  if (res.ok) { toast('Repository ban gayi ✔', 'ok'); closeModal('repoModal'); $('#repoName').value = ''; await loadRepos(); openRepo(res.name); }
  else toast(res.error || 'Create fail', 'err');
}
async function deleteRepo(name) {
  const yes = await askPrompt(`Delete repo "${name}"? Type the repo name to confirm`, '');
  if (yes !== name) { toast('Cancelled — naam match nahi hua'); return; }
  loading(true);
  const res = await apiPost('repo/delete', { name });
  loading(false);
  if (res.ok) { toast('Repository delete ✔', 'ok'); if (currentRepo === name) { currentRepo = null; showReposView(); } await loadRepos(); }
  else toast(res.error || 'Delete fail (PAT me delete_repo scope chahiye)', 'err');
}

/* ---------- VIEWS ---------- */
function showReposView() {
  $('#reposView').classList.remove('hidden');
  $('#filesView').classList.add('hidden');
  currentRepo = null; currentPath = '';
  renderBreadcrumb();
  renderRepoSidebar();
}
function showFilesView() {
  $('#reposView').classList.add('hidden');
  $('#filesView').classList.remove('hidden');
}

/* ---------- OPEN repo / dir ---------- */
function openRepo(name) { currentRepo = name; currentPath = ''; showFilesView(); renderRepoSidebar(); loadDir(''); }

async function loadDir(p) {
  loading(true);
  const res = await apiGet('contents', { repo: currentRepo, path: p });
  loading(false);
  if (!res.ok) { toast(res.error || 'Load fail', 'err'); return; }
  currentPath = res.path;
  renderBreadcrumb();
  renderList(res.items);
}

/* ---------- breadcrumb ---------- */
function renderBreadcrumb() {
  const bc = $('#breadcrumb'); bc.innerHTML = '';
  const root = document.createElement('a');
  root.textContent = '📚 Repos'; root.href = '#';
  root.onclick = (e) => { e.preventDefault(); showReposView(); loadRepos(); };
  bc.appendChild(root);
  if (!currentRepo) return;

  const sep0 = document.createElement('span'); sep0.className = 'sep'; sep0.textContent = '/';
  bc.appendChild(sep0);
  const repoLink = document.createElement('a');
  repoLink.textContent = '📦 ' + currentRepo; repoLink.href = '#';
  repoLink.onclick = (e) => { e.preventDefault(); loadDir(''); };
  bc.appendChild(repoLink);

  if (!currentPath) return;
  const parts = currentPath.split('/'); let acc = '';
  parts.forEach((part, i) => {
    const sep = document.createElement('span'); sep.className = 'sep'; sep.textContent = '/'; bc.appendChild(sep);
    acc += (acc ? '/' : '') + part;
    if (i === parts.length - 1) {
      const cur = document.createElement('span'); cur.className = 'cur'; cur.textContent = part; bc.appendChild(cur);
    } else {
      const a = document.createElement('a'); const t = acc; a.textContent = part; a.href = '#';
      a.onclick = (e) => { e.preventDefault(); loadDir(t); }; bc.appendChild(a);
    }
  });
}

/* ---------- file list ---------- */
function renderList(items) {
  const tb = $('#fileList'); tb.innerHTML = '';
  $('#emptyState').classList.toggle('hidden', items.length > 0);
  items.forEach(item => {
    const tr = document.createElement('tr');
    const tdN = document.createElement('td'); tdN.className = 'col-name';
    const nm = document.createElement('div'); nm.className = 'fname';
    nm.innerHTML = `<span class="ico">${iconFor(item)}</span><span class="txt">${escapeHtml(item.name)}</span>`;
    nm.onclick = () => onOpen(item); tdN.appendChild(nm);

    const tdS = document.createElement('td'); tdS.className = 'col-size muted';
    tdS.textContent = item.type === 'folder' ? '—' : fmtSize(item.size);

    const tdA = document.createElement('td'); tdA.className = 'col-act';
    const w = document.createElement('div'); w.className = 'row-actions';
    if (item.type === 'file' && isText(item.ext)) w.appendChild(mkBtn('Edit', () => openEditor(item)));
    if (item.type === 'file') w.appendChild(mkBtn('⬇', () => downloadFile(item), 'Download'));
    w.appendChild(mkBtn('Rename', () => renameItem(item)));
    w.appendChild(mkBtn('Delete', () => deleteItem(item), 'Delete', 'btn-danger'));
    tdA.appendChild(w);

    tr.append(tdN, tdS, tdA); tb.appendChild(tr);
  });
}
function mkBtn(label, fn, title = '', extra = '') {
  const b = document.createElement('button');
  b.className = 'btn btn-sm ' + extra; b.textContent = label;
  if (title) b.title = title; b.onclick = fn; return b;
}

/* ---------- open item ---------- */
function onOpen(item) {
  if (item.type === 'folder') return loadDir(item.path);
  if (isImage(item.ext)) return previewImage(item);
  if (isText(item.ext)) return openEditor(item);
  downloadFile(item);
}
function rawUrl(p, dl) {
  return `/api/raw?repo=${encodeURIComponent(currentRepo)}&path=${encodeURIComponent(p)}` + (dl ? '&download=1' : '');
}
function previewImage(item) {
  $('#previewTitle').textContent = item.name;
  $('#previewBody').innerHTML = `<img src="${rawUrl(item.path)}" alt="${escapeHtml(item.name)}" />`;
  openModal('previewModal');
}
function downloadFile(item) { window.open(rawUrl(item.path, true), '_blank'); }

/* ---------- editor ---------- */
let editing = null;
async function openEditor(item) {
  loading(true);
  const res = await apiGet('read', { repo: currentRepo, path: item.path });
  loading(false);
  if (!res.ok) { toast(res.error, 'err'); return; }
  editing = { path: item.path, sha: res.sha };
  $('#editorTitle').textContent = 'Edit: ' + item.name;
  $('#editorArea').value = res.content;
  openModal('editorModal');
}
$('#saveFileBtn').addEventListener('click', async () => {
  loading(true);
  const res = await apiPost('file/save', { repo: currentRepo, path: editing.path, content: $('#editorArea').value, sha: editing.sha });
  loading(false);
  if (res.ok) { toast('Committed ✔', 'ok'); closeModal('editorModal'); loadDir(currentPath); }
  else toast(res.error || 'Save fail', 'err');
});

/* ---------- rename ---------- */
async function renameItem(item) {
  if (item.type === 'folder') { toast('Folder rename abhi support nahi — file rename karo'); return; }
  const nn = await askPrompt('New name', item.name);
  if (!nn || nn === item.name) return;
  loading(true);
  const res = await apiPost('rename', { repo: currentRepo, path: item.path, newName: nn, sha: item.sha });
  loading(false);
  if (res.ok) { toast('Renamed ✔', 'ok'); loadDir(currentPath); }
  else toast(res.error || 'Rename fail', 'err');
}

/* ---------- delete ---------- */
async function deleteItem(item) {
  const yes = await askPrompt(`Delete "${item.name}"? Type YES to confirm`, '');
  if (yes !== 'YES') { toast('Cancelled'); return; }
  loading(true);
  let res;
  if (item.type === 'folder') res = await apiPost('folder/delete', { repo: currentRepo, path: item.path });
  else res = await apiPost('file/delete', { repo: currentRepo, path: item.path, sha: item.sha });
  loading(false);
  if (res.ok) { toast('Deleted ✔', 'ok'); loadDir(currentPath); }
  else toast(res.error || 'Delete fail', 'err');
}

/* ---------- new folder / file ---------- */
async function newFolder() {
  const name = await askPrompt('New folder name');
  if (!name) return;
  loading(true);
  const res = await apiPost('folder/create', { repo: currentRepo, path: currentPath, name });
  loading(false);
  if (res.ok) { toast('Folder ban gaya ✔', 'ok'); loadDir(currentPath); }
  else toast(res.error || 'Fail', 'err');
}
async function newFile() {
  const name = await askPrompt('New file name (e.g. index.html)');
  if (!name) return;
  loading(true);
  const res = await apiPost('file/create', { repo: currentRepo, path: currentPath, name, content: '' });
  loading(false);
  if (res.ok) { toast('File ban gayi ✔', 'ok'); loadDir(currentPath); }
  else toast(res.error || 'Fail', 'err');
}

/* ---------- upload files ---------- */
$('#fileInput').addEventListener('change', async (e) => {
  const files = e.target.files; if (!files.length) return;
  const fd = new FormData();
  for (const f of files) fd.append('files', f);
  loading(true);
  const res = await fetch(`/api/upload?repo=${encodeURIComponent(currentRepo)}&path=${encodeURIComponent(currentPath)}`,
    { method: 'POST', body: fd, credentials: 'same-origin' }).then(r => r.json());
  loading(false); e.target.value = '';
  if (res.ok) { toast(`${res.count} file(s) commit ✔`, 'ok'); loadDir(currentPath); }
  else toast(res.error || 'Upload fail', 'err');
});

/* ---------- upload zip (auto extract) ---------- */
$('#zipInput').addEventListener('change', async (e) => {
  const f = e.target.files[0]; if (!f) return;
  const fd = new FormData(); fd.append('zip', f);
  loading(true);
  toast('ZIP extract ho rahi hai…');
  const res = await fetch(`/api/upload-zip?repo=${encodeURIComponent(currentRepo)}&path=${encodeURIComponent(currentPath)}`,
    { method: 'POST', body: fd, credentials: 'same-origin' }).then(r => r.json());
  loading(false); e.target.value = '';
  if (res.ok) { toast(res.message || 'ZIP extract ✔', 'ok'); loadDir(currentPath); }
  else toast(res.error || 'ZIP fail', 'err');
});

/* ---------- action dispatch ---------- */
document.addEventListener('click', (e) => {
  const el = e.target.closest('[data-act]'); if (!el) return;
  const act = el.dataset.act;
  if (act === 'newRepo') openModal('repoModal');
  else if (act === 'allRepos') { showReposView(); loadRepos(); closeSidebar(); }
  else if (act === 'newFolder') newFolder();
  else if (act === 'newFile') newFile();
  else if (act === 'upload') $('#fileInput').click();
  else if (act === 'uploadZip') $('#zipInput').click();
  if (['newRepo','allRepos'].includes(act)) return;
  closeSidebar();
});
$('#repoCreateBtn').addEventListener('click', createRepo);
$('#refreshBtn').addEventListener('click', () => { if (currentRepo) loadDir(currentPath); else loadRepos(); });

/* ---------- mobile sidebar ---------- */
function closeSidebar() { $('#sidebar').classList.remove('open'); $('#backdrop').classList.remove('show'); }
$('#menuToggle').addEventListener('click', () => { $('#sidebar').classList.toggle('open'); $('#backdrop').classList.toggle('show'); });
$('#backdrop').addEventListener('click', closeSidebar);

/* ---------- boot ---------- */
(async function boot() {
  const me = await apiGet('me');
  if (me.ok && me.loggedIn) { showApp(); initApp(); }
})();
