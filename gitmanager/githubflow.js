/**
 * ============================================================
 *  githubflow.js  —  GitHub API backed File Manager (Node.js)
 * ============================================================
 *  Sab kuch SEEDHA GitHub pe hota hai (real commits):
 *    - Repository create / delete
 *    - File create / edit / delete   (repo me commit)
 *    - Folder create (.gitkeep commit)  / folder delete
 *    - ZIP upload -> auto extract -> saari files ek-ek karke commit
 *
 *  PAT token + username config.php se aata hai (single source).
 *
 *  Run:
 *    npm install express express-session cookie-parser multer adm-zip
 *    node githubflow.js
 *  Browser:  http://localhost:3000
 * ============================================================
 */

const express      = require('express');
const session      = require('express-session');
const cookieParser = require('cookie-parser');
const multer       = require('multer');
const AdmZip       = require('adm-zip');
const fs           = require('fs');
const path         = require('path');

const app  = express();
const PORT = process.env.PORT || 3000;
const GH   = 'https://api.github.com';

/* ============================================================
   CONFIG — config.php se token/username/login padho
   ============================================================ */
function readPhpConst(name) {
  try {
    const php = fs.readFileSync(path.join(__dirname, 'config.php'), 'utf8');
    const m = php.match(new RegExp("define\\(\\s*'" + name + "'\\s*,\\s*'([^']*)'\\s*\\)"));
    return m ? m[1] : '';
  } catch (e) { return ''; }
}
const CFG = {
  TOKEN:    process.env.GITHUB_PAT || readPhpConst('GITHUB_PAT'),
  USERNAME: process.env.GITHUB_USERNAME || readPhpConst('GITHUB_USERNAME'),
  ADMIN_USER: readPhpConst('ADMIN_USERNAME') || 'admin',
  ADMIN_PASS: readPhpConst('ADMIN_PASSWORD') || 'admin123',
  SECRET:   readPhpConst('APP_SECRET') || 'change-me'
};

/* ============================================================
   GitHub API helper (Node 18+ me global fetch available hai)
   ============================================================ */
async function gh(method, endpoint, body) {
  const res = await fetch(GH + endpoint, {
    method,
    headers: {
      'Authorization': 'Bearer ' + CFG.TOKEN,
      'Accept': 'application/vnd.github+json',
      'X-GitHub-Api-Version': '2022-11-28',
      'User-Agent': 'FileManagerApp',
      ...(body ? { 'Content-Type': 'application/json' } : {})
    },
    body: body ? JSON.stringify(body) : undefined
  });
  const text = await res.text();
  let data;
  try { data = text ? JSON.parse(text) : {}; } catch { data = { raw: text }; }
  if (!res.ok) {
    const msg = (data && data.message) ? data.message : ('GitHub error ' + res.status);
    const err = new Error(msg);
    err.status = res.status;
    throw err;
  }
  return data;
}

// base64 helpers (GitHub content API base64 me hota hai)
const toB64   = (buf) => Buffer.from(buf).toString('base64');
const fromB64 = (s)   => Buffer.from(s, 'base64');

/* ============================================================
   Middleware
   ============================================================ */
app.use(express.json({ limit: '5mb' }));
app.use(cookieParser());
app.use(session({
  secret: CFG.SECRET, resave: false, saveUninitialized: false,
  cookie: { httpOnly: true, maxAge: 1000 * 60 * 60 * 8 }
}));

function requireAuth(req, res, next) {
  if (req.session && req.session.loggedIn) return next();
  return res.status(401).json({ ok: false, error: 'Not authenticated' });
}
function fail(res, err) {
  res.status(err.status || 400).json({ ok: false, error: err.message || String(err) });
}

/* ============================================================
   AUTH
   ============================================================ */
app.post('/api/login', (req, res) => {
  const { username, password } = req.body;
  if (username === CFG.ADMIN_USER && password === CFG.ADMIN_PASS) {
    req.session.loggedIn = true;
    return res.json({ ok: true });
  }
  res.status(401).json({ ok: false, error: 'Galat username ya password' });
});
app.post('/api/logout', (req, res) => req.session.destroy(() => res.json({ ok: true })));
app.get('/api/me', (req, res) =>
  res.json({ ok: true, loggedIn: !!(req.session && req.session.loggedIn), user: CFG.USERNAME }));

/* ============================================================
   REPOSITORIES  —  list / create / delete
   ============================================================ */

// List repos
app.get('/api/repos', requireAuth, async (req, res) => {
  try {
    const repos = await gh('GET', '/user/repos?per_page=100&sort=updated&affiliation=owner');
    res.json({ ok: true, repos: repos.map(r => ({
      name: r.name, private: r.private, default_branch: r.default_branch,
      updated: r.updated_at, url: r.html_url
    })) });
  } catch (e) { fail(res, e); }
});

// Create repo (real, GitHub account me)
app.post('/api/repo/create', requireAuth, async (req, res) => {
  try {
    const { name, private: priv } = req.body;
    if (!name) throw new Error('Repo name chahiye');
    const repo = await gh('POST', '/user/repos', {
      name, private: !!priv, auto_init: true   // auto_init -> README + default branch ban jati hai
    });
    res.json({ ok: true, name: repo.name });
  } catch (e) { fail(res, e); }
});

// Delete repo (⚠ real delete — needs delete_repo scope on PAT)
app.post('/api/repo/delete', requireAuth, async (req, res) => {
  try {
    const { name } = req.body;
    await gh('DELETE', `/repos/${CFG.USERNAME}/${name}`);
    res.json({ ok: true });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   CONTENTS  —  list files/folders in a repo path
   ============================================================ */
app.get('/api/contents', requireAuth, async (req, res) => {
  try {
    const { repo, path: p = '' } = req.query;
    const data = await gh('GET',
      `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(p).replace(/%2F/g, '/')}`);
    const arr = Array.isArray(data) ? data : [data];
    const items = arr
      .filter(x => x.name !== '.gitkeep')   // .gitkeep chhupa do
      .map(x => ({
        name: x.name,
        type: x.type === 'dir' ? 'folder' : 'file',
        size: x.size || 0,
        path: x.path,
        sha:  x.sha,
        ext:  x.type === 'dir' ? '' : (x.name.split('.').pop() || '').toLowerCase()
      }));
    items.sort((a, b) =>
      a.type === b.type ? a.name.localeCompare(b.name) : (a.type === 'folder' ? -1 : 1));
    res.json({ ok: true, path: p, items });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   READ file (editor ke liye)
   ============================================================ */
app.get('/api/read', requireAuth, async (req, res) => {
  try {
    const { repo, path: p } = req.query;
    const data = await gh('GET',
      `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(p).replace(/%2F/g, '/')}`);
    const content = fromB64(data.content || '').toString('utf8');
    res.json({ ok: true, content, sha: data.sha });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   PUT (create/update) a file — real commit
   ============================================================ */
async function putFile(repo, filePath, contentBuf, message, sha) {
  const body = {
    message: message || `Update ${filePath}`,
    content: toB64(contentBuf)
  };
  if (sha) body.sha = sha;
  return gh('PUT',
    `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(filePath).replace(/%2F/g, '/')}`,
    body);
}

// Create new file
app.post('/api/file/create', requireAuth, async (req, res) => {
  try {
    const { repo, path: dir = '', name, content = '' } = req.body;
    const filePath = (dir ? dir + '/' : '') + name;
    await putFile(repo, filePath, Buffer.from(content, 'utf8'), `Create ${filePath}`);
    res.json({ ok: true });
  } catch (e) { fail(res, e); }
});

// Save / edit existing file (sha zaroori hota hai)
app.post('/api/file/save', requireAuth, async (req, res) => {
  try {
    const { repo, path: filePath, content, sha } = req.body;
    await putFile(repo, filePath, Buffer.from(content ?? '', 'utf8'), `Edit ${filePath}`, sha);
    res.json({ ok: true });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   DELETE file — real commit
   ============================================================ */
app.post('/api/file/delete', requireAuth, async (req, res) => {
  try {
    const { repo, path: filePath, sha } = req.body;
    await gh('DELETE',
      `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(filePath).replace(/%2F/g, '/')}`,
      { message: `Delete ${filePath}`, sha });
    res.json({ ok: true });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   CREATE FOLDER — GitHub me folder tabhi banta hai jab file ho,
   isliye ek .gitkeep commit karte hain.
   ============================================================ */
app.post('/api/folder/create', requireAuth, async (req, res) => {
  try {
    const { repo, path: dir = '', name } = req.body;
    const filePath = (dir ? dir + '/' : '') + name + '/.gitkeep';
    await putFile(repo, filePath, Buffer.from('', 'utf8'), `Create folder ${name}`);
    res.json({ ok: true });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   DELETE FOLDER — recursive: andar ki saari files delete
   ============================================================ */
async function deleteTree(repo, dirPath) {
  const data = await gh('GET',
    `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(dirPath).replace(/%2F/g, '/')}`);
  const arr = Array.isArray(data) ? data : [data];
  for (const item of arr) {
    if (item.type === 'dir') {
      await deleteTree(repo, item.path);
    } else {
      await gh('DELETE',
        `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(item.path).replace(/%2F/g, '/')}`,
        { message: `Delete ${item.path}`, sha: item.sha });
    }
  }
}
app.post('/api/folder/delete', requireAuth, async (req, res) => {
  try {
    const { repo, path: dirPath } = req.body;
    await deleteTree(repo, dirPath);
    res.json({ ok: true });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   UPLOAD files (multiple) — har file ka real commit
   ============================================================ */
const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 100 * 1024 * 1024 } });

app.post('/api/upload', requireAuth, upload.array('files'), async (req, res) => {
  try {
    const { repo, path: dir = '' } = req.query;
    let count = 0;
    for (const f of (req.files || [])) {
      const filePath = (dir ? dir + '/' : '') + f.originalname;
      // agar file pehle se ho to sha chahiye (overwrite ke liye)
      let sha;
      try {
        const ex = await gh('GET',
          `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(filePath).replace(/%2F/g, '/')}`);
        sha = ex.sha;
      } catch (_) { /* nayi file */ }
      await putFile(repo, filePath, f.buffer, `Upload ${filePath}`, sha);
      count++;
    }
    res.json({ ok: true, count });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   UPLOAD ZIP -> AUTO EXTRACT -> har file GitHub pe commit
   (Git Data API se ek hi commit me saari files daal dete hain — fast)
   ============================================================ */
app.post('/api/upload-zip', requireAuth, upload.single('zip'), async (req, res) => {
  try {
    const { repo, path: dir = '' } = req.query;
    if (!req.file) throw new Error('Koi zip nahi mili');

    const zip = new AdmZip(req.file.buffer);
    const entries = zip.getEntries().filter(e => !e.isDirectory);
    if (!entries.length) throw new Error('Zip khaali hai');

    // 1) default branch + latest commit sha
    const repoInfo = await gh('GET', `/repos/${CFG.USERNAME}/${repo}`);
    const branch   = repoInfo.default_branch;
    const ref      = await gh('GET', `/repos/${CFG.USERNAME}/${repo}/git/ref/heads/${branch}`);
    const baseSha  = ref.object.sha;
    const baseCommit = await gh('GET', `/repos/${CFG.USERNAME}/${repo}/git/commits/${baseSha}`);
    const baseTree = baseCommit.tree.sha;

    // 2) har file ke liye blob banao
    const treeItems = [];
    for (const entry of entries) {
      const blob = await gh('POST', `/repos/${CFG.USERNAME}/${repo}/git/blobs`, {
        content: entry.getData().toString('base64'),
        encoding: 'base64'
      });
      const filePath = (dir ? dir + '/' : '') + entry.entryName.replace(/^\/+/, '');
      treeItems.push({ path: filePath, mode: '100644', type: 'blob', sha: blob.sha });
    }

    // 3) naya tree
    const newTree = await gh('POST', `/repos/${CFG.USERNAME}/${repo}/git/trees`, {
      base_tree: baseTree, tree: treeItems
    });

    // 4) naya commit
    const commit = await gh('POST', `/repos/${CFG.USERNAME}/${repo}/git/commits`, {
      message: `Upload & extract ZIP (${entries.length} files)`,
      tree: newTree.sha,
      parents: [baseSha]
    });

    // 5) branch ko aage badhao
    await gh('PATCH', `/repos/${CFG.USERNAME}/${repo}/git/refs/heads/${branch}`, {
      sha: commit.sha
    });

    res.json({ ok: true, message: `ZIP extract ho gayi — ${entries.length} files commit`, count: entries.length });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   RENAME file (GitHub me = read + create new + delete old)
   ============================================================ */
app.post('/api/rename', requireAuth, async (req, res) => {
  try {
    const { repo, path: oldPath, newName, sha } = req.body;
    const parent  = oldPath.includes('/') ? oldPath.slice(0, oldPath.lastIndexOf('/')) : '';
    const newPath = (parent ? parent + '/' : '') + newName;
    const cur = await gh('GET',
      `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(oldPath).replace(/%2F/g, '/')}`);
    await putFile(repo, newPath, fromB64(cur.content), `Rename to ${newName}`);
    await gh('DELETE',
      `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(oldPath).replace(/%2F/g, '/')}`,
      { message: `Remove old ${oldPath}`, sha: cur.sha });
    res.json({ ok: true });
  } catch (e) { fail(res, e); }
});

/* ============================================================
   RAW file (image preview / download)
   ============================================================ */
app.get('/api/raw', requireAuth, async (req, res) => {
  try {
    const { repo, path: p } = req.query;
    const data = await gh('GET',
      `/repos/${CFG.USERNAME}/${repo}/contents/${encodeURIComponent(p).replace(/%2F/g, '/')}`);
    const buf = fromB64(data.content || '');
    if (req.query.download === '1')
      res.setHeader('Content-Disposition', `attachment; filename="${data.name}"`);
    res.send(buf);
  } catch (e) { fail(res, e); }
});

/* ============================================================
   STATIC UI
   ============================================================ */
app.use(express.static(path.join(__dirname, 'public')));
app.get('*', (req, res) => res.sendFile(path.join(__dirname, 'public', 'index.html')));

app.listen(PORT, () => {
  console.log(`\n  GitHub File Manager:  http://localhost:${PORT}`);
  console.log(`  GitHub user: ${CFG.USERNAME || '(config.php me daalo)'}\n`);
});
