<?php require 'config.php'; count_click(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>FF Diamond TopUp — Fast & Secure</title>
<link rel="stylesheet" href="style.css">
<style>
/* ===== HOME EXTRA (scoped) ===== */
.brand-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px 16px;
  display:flex;align-items:center;gap:14px;margin-top:16px;box-shadow:0 3px 12px rgba(0,0,0,.05)}
.brand-card .bc-logo{width:56px;height:56px;border-radius:2px;object-fit:cover;flex-shrink:0;
  border:1px solid var(--line)}
.brand-card .bc-name{font-size:20px;font-weight:800;letter-spacing:.3px;line-height:1.1}
.brand-card .bc-sub{font-size:12px;color:var(--muted);margin-top:3px;display:flex;align-items:center;gap:5px}
.brand-card .bc-sub svg{width:14px;height:14px;fill:#1b9e4b}
.brand-card .bc-right{margin-left:auto;text-align:right}
.brand-card .bc-badge{background:#fff2f3;color:var(--red);font-size:11px;font-weight:800;
  padding:5px 11px;border-radius:20px;display:inline-flex;align-items:center;gap:4px}
.brand-card .bc-badge svg{width:13px;height:13px;fill:var(--red)}

/* trust strip */
.trust-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:16px 0}
.trust{background:#fff;border:1px solid var(--line);border-radius:13px;padding:13px 8px;text-align:center}
.trust .t-ic{width:34px;height:34px;border-radius:9px;background:#fff2f3;display:flex;align-items:center;justify-content:center;margin:0 auto 8px}
.trust .t-ic svg{width:19px;height:19px;fill:var(--red)}
.trust .t-num{font-size:15px;font-weight:800}
.trust .t-lbl{font-size:10.5px;color:var(--muted);margin-top:2px}

/* header icon fix — original width */
.hdr-center img.hdr-icon-lg{height:40px;width:auto;max-width:none}

/* result region flag */
.region-flag{width:20px;height:14px;border-radius:2px;object-fit:cover;vertical-align:middle;margin-right:5px;
  box-shadow:0 0 0 1px rgba(0,0,0,.08)}
.info-item .val.region-val{display:inline-flex;align-items:center;justify-content:center;gap:2px}

/* bigger info icons look */
.info-item{padding:14px 8px}
.info-svg{width:26px;height:26px}
</style>
</head>
<body>

<!-- HEADER -->
<header class="app-header">
  <div class="hdr-left"><img src="assets/ffmax_icon.png" alt="ffmax" class="hdr-icon"></div>
  <div class="hdr-center"><img src="assets/bisicon.png" alt="bis" class="hdr-icon-lg"></div>
  <div class="hdr-right"></div>
</header>

<main class="container">

  <!-- BANNER -->
  <section class="banner-wrap">
    <img src="assets/banner.png" alt="banner" class="banner-img">
  </section>

  <!-- TAGS -->
  <div class="tags-row">
    <span class="tag">
      <svg viewBox="0 0 24 24" class="tag-svg"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg>
      Fast Checkout
    </span>
    <span class="tag">
      <svg viewBox="0 0 24 24" class="tag-svg"><path d="M21 7 9 19l-5.5-5.5 1.4-1.4L9 16.2 19.6 5.6z"/></svg>
      Instant Delivery
    </span>
  </div>

  <!-- TRUST STRIP -->
  <div class="trust-row">
    <div class="trust">
      <div class="t-ic"><svg viewBox="0 0 24 24"><path d="M12 1 3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5l-9-4zm-1 15-4-4 1.4-1.4L11 13.2l5.6-5.6L18 9l-7 7z"/></svg></div>
      <div class="t-num">100%</div>
      <div class="t-lbl">Safe Payment</div>
    </div>
    <div class="trust">
      <div class="t-ic"><svg viewBox="0 0 24 24"><path d="M13 2 3 14h7l-1 8 10-12h-7z"/></svg></div>
      <div class="t-num">Instant</div>
      <div class="t-lbl">Auto Delivery</div>
    </div>
    <div class="trust">
      <div class="t-ic"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 5h-2v6l5 3 1-1.7-4-2.3V7z"/></svg></div>
      <div class="t-num">24/7</div>
      <div class="t-lbl">Support</div>
    </div>
  </div>

  <!-- BRAND CARD (above Verify) -->
  <div class="brand-card">
    <img src="assets/fflogo.png" alt="Free Fire" class="bc-logo">
    <div>
      <div class="bc-name">Free Fire</div>
      <div class="bc-sub">
        <svg viewBox="0 0 24 24"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>
        Official Diamond TopUp
      </div>
    </div>
    <div class="bc-right">
      <span class="bc-badge">
        <svg viewBox="0 0 24 24"><path d="M12 1 3 5v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V5l-9-4z"/></svg>
        Verified
      </span>
    </div>
  </div>

  <!-- VERIFY SECTION -->
  <section class="verify-section" style="margin-top:16px">
    <h2 class="section-title">Verify Your UID</h2>

    <div class="uid-box">
      <input type="text" id="uidInput" inputmode="numeric" placeholder="Enter your Free Fire UID" autocomplete="off">
      <button id="verifyBtn" class="btn-verify">Verify</button>
    </div>
    <p id="uidError" class="uid-error"></p>

    <!-- LOADING -->
    <div id="loadingBox" class="loading-box hidden">
      <img src="assets/fficon.png" class="loading-icon" alt="loading">
      <p class="loading-text">Verifying your account...</p>
    </div>

    <!-- RESULT -->
    <div id="resultCard" class="result-card hidden">
      <div class="avatar-wrap"><img src="assets/avatar.png" alt="avatar" class="avatar-img"></div>
      <h3 id="rName" class="r-name"></h3>

      <div class="info-grid">
        <div class="info-item">
          <svg viewBox="0 0 24 24" class="info-svg"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5z"/></svg>
          <span class="lbl">UID</span><span id="rUid" class="val"></span>
        </div>
        <div class="info-item">
          <svg viewBox="0 0 24 24" class="info-svg"><path d="M12 2 15 9l7.5.6-5.7 4.9 1.8 7.3L12 18l-6.6 3.8 1.8-7.3L1.5 9.6 9 9z"/></svg>
          <span class="lbl">Level</span><span id="rLevel" class="val"></span>
        </div>
        <div class="info-item">
          <svg viewBox="0 0 24 24" class="info-svg"><path d="M12 21.3-1.5-1.4C4 16 1 13.3 1 9.9 1 7.1 3.2 5 6 5c1.5 0 3 .7 4 1.9C11 5.7 12.5 5 14 5c2.8 0 5 2.1 5 4.9 0 3.4-3 6.1-7.5 10l-1.5 1.4z"/></svg>
          <span class="lbl">Likes</span><span id="rLikes" class="val"></span>
        </div>
        <div class="info-item">
          <svg viewBox="0 0 24 24" class="info-svg"><path d="M12 2C7 2 3 6 3 11c0 6 9 11 9 11s9-5 9-11c0-5-4-9-9-9zm0 12a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
          <span class="lbl">Region</span><span id="rRegion" class="val region-val"></span>
        </div>
      </div>

      <button id="continueBtn" class="btn-continue">Continue Shopping</button>
    </div>
  </section>
</main>

<!-- FOOTER -->
<footer class="app-footer">
  <img src="assets/garenalogo.png" alt="garena" class="footer-logo">
  <p class="footer-note">This is an independent top-up store. Not officially affiliated with Garena.</p>
  <p class="footer-copy">© <?= date('Y') ?> FF Diamond TopUp. All rights reserved.</p>
</footer>

<div id="confetti" class="confetti-layer"></div>

<script>
const verifyBtn  = document.getElementById('verifyBtn');
const uidInput   = document.getElementById('uidInput');
const loadingBox = document.getElementById('loadingBox');
const resultCard = document.getElementById('resultCard');
const uidError   = document.getElementById('uidError');

verifyBtn.addEventListener('click', doVerify);
uidInput.addEventListener('keydown', e => { if (e.key === 'Enter') doVerify(); });

// country flags (online)
const FLAGS = {
  IND:'https://flagcdn.com/w40/in.png',
  ID:'https://flagcdn.com/w40/id.png',
  BR:'https://flagcdn.com/w40/br.png',
  US:'https://flagcdn.com/w40/us.png',
  BD:'https://flagcdn.com/w40/bd.png',
  PK:'https://flagcdn.com/w40/pk.png',
  TH:'https://flagcdn.com/w40/th.png',
  VN:'https://flagcdn.com/w40/vn.png',
  SG:'https://flagcdn.com/w40/sg.png',
  ME:'https://flagcdn.com/w40/sa.png'
};
const REGION_NAME = { IND:'India', ID:'Indonesia', BR:'Brazil', US:'USA', BD:'Bangladesh',
  PK:'Pakistan', TH:'Thailand', VN:'Vietnam', SG:'Singapore', ME:'Middle East' };

async function doVerify() {
  const uid = uidInput.value.trim();
  uidError.textContent = '';
  resultCard.classList.add('hidden');

  if (!/^\d{5,15}$/.test(uid)) {
    uidError.textContent = 'Please enter a valid numeric UID.';
    return;
  }

  loadingBox.classList.remove('hidden');
  loadingBox.classList.add('fade-loop');

  try {
    const res  = await fetch('check.php?uid=' + encodeURIComponent(uid));
    const json = await res.json();
    await new Promise(r => setTimeout(r, 1400));

    loadingBox.classList.add('hidden');

    if (!json.success || !json.data) {
      uidError.textContent = 'UID not found. Please check and try again.';
      return;
    }

    const d = json.data;
    document.getElementById('rName').textContent   = d.Name;
    document.getElementById('rUid').textContent    = d.UID;
    document.getElementById('rLevel').textContent  = d.Level;
    document.getElementById('rLikes').textContent  = d.Likes;

    // region with flag
    const reg = (d.Region || '').toUpperCase();
    const flag = FLAGS[reg];
    const rn = REGION_NAME[reg] || d.Region;
    const regEl = document.getElementById('rRegion');
    regEl.innerHTML = (flag ? '<img class="region-flag" src="'+flag+'" alt="">' : '') + rn;

    localStorage.setItem('ffAccount', JSON.stringify(d));

    resultCard.classList.remove('hidden');
    resultCard.classList.add('pop-in');
    celebrate();
  } catch (err) {
    loadingBox.classList.add('hidden');
    uidError.textContent = 'Server error. Please try again later.';
  }
}

document.getElementById('continueBtn').addEventListener('click', () => {
  document.body.classList.add('page-leave');
  setTimeout(() => location.href = 'shop.php', 350);
});

function celebrate() {
  const layer  = document.getElementById('confetti');
  const colors = ['#e01e2b','#ff5964','#ffd23f','#ffffff','#ff8fa3'];
  for (let i = 0; i < 90; i++) {
    const p = document.createElement('span');
    p.className = 'conf-piece';
    p.style.left = Math.random() * 100 + 'vw';
    p.style.background = colors[Math.floor(Math.random() * colors.length)];
    p.style.animationDelay = Math.random() * 0.5 + 's';
    layer.appendChild(p);
    setTimeout(() => p.remove(), 2600);
  }
}
</script>
</body>
</html>
