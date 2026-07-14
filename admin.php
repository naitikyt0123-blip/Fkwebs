<?php
require 'config.php';
$settings = get_data('settings');

/* ---------- LOGOUT ---------- */
if (isset($_GET['logout'])) { unset($_SESSION['admin_ok']); header('Location: admin.php'); exit; }

/* ---------- LOGIN ---------- */
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($u === ($settings['admin_user'] ?? '') && $p === ($settings['admin_pass'] ?? '')) {
        $_SESSION['admin_ok'] = true;
        header('Location: admin.php'); exit;
    } else { $login_error = 'Invalid username or password.'; }
}
$is_admin = !empty($_SESSION['admin_ok']);

/* ---------- ACTIONS ---------- */
$msg = ''; $msg_type = 'ok';
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_pack'])) {
        $packs = get_data('packs');
        $newId = 1; foreach ($packs as $p) if ($p['id'] >= $newId) $newId = $p['id'] + 1;
        $packs[] = ['id'=>$newId,'diamonds'=>(int)$_POST['diamonds'],
                    'original'=>(int)$_POST['original'],'offer'=>(int)$_POST['offer']];
        save_data('packs',$packs); $msg='Pack added successfully.';
    }
    if (isset($_POST['edit_pack'])) {
        $packs = get_data('packs');
        foreach ($packs as &$p) if ($p['id']==(int)$_POST['id']) {
            $p['diamonds']=(int)$_POST['diamonds']; $p['original']=(int)$_POST['original']; $p['offer']=(int)$_POST['offer'];
        } unset($p);
        save_data('packs',$packs); $msg='Pack updated.';
    }
    if (isset($_POST['delete_pack'])) {
        $packs = array_values(array_filter(get_data('packs'), fn($p)=>$p['id']!=(int)$_POST['id']));
        save_data('packs',$packs); $msg='Pack deleted.';
    }
    if (isset($_POST['save_payment'])) {
        $settings['upi_id']=trim($_POST['upi_id']); $settings['upi_name']=trim($_POST['upi_name']);
        $settings['payment_mode']=$_POST['payment_mode']; $settings['razorpay_key']=trim($_POST['razorpay_key']);
        save_data('settings',$settings); $msg='Payment settings saved.';
    }
    if (isset($_POST['change_pass'])) {
        if (trim($_POST['current_pass'])!==$settings['admin_pass']) { $msg='Current password is incorrect.'; $msg_type='err'; }
        elseif (strlen(trim($_POST['new_pass']))<4) { $msg='New password too short (min 4).'; $msg_type='err'; }
        else { $settings['admin_pass']=trim($_POST['new_pass']); save_data('settings',$settings); $msg='Password changed successfully.'; }
    }
    if (isset($_POST['reset_stats'])) { save_data('stats',['clicks'=>0,'checkouts'=>0]); $msg='Stats reset.'; }
}

$stats = get_data('stats');
$packs = get_data('packs');
$settings = get_data('settings');

// revenue potential (sum of offer prices)
$revenue = 0; foreach ($packs as $p) $revenue += (int)$p['offer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — FF TopUp</title>
<style>
:root{--red:#e01e2b;--red-dark:#b8121f;--yellow:#ffc107;--ink:#1a1a1a;--muted:#8a8a8a;--line:#ececec;--bg:#f4f5f7;--white:#fff;--green:#1b9e4b;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{font-family:'Segoe UI',Roboto,Arial,sans-serif;background:var(--bg);color:var(--ink);line-height:1.5}
button{font-family:inherit;cursor:pointer}
input,select{font-family:inherit}

/* ===== LOGIN ===== */
.login-screen{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
  background:linear-gradient(135deg,#e01e2b 0%,#8f1018 100%)}
.login-card{background:#fff;border-radius:18px;padding:32px 26px;max-width:380px;width:100%;box-shadow:0 12px 40px rgba(0,0,0,.25)}
.login-logo{width:60px;height:60px;background:var(--red);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.login-logo svg{width:32px;height:32px;fill:#fff}
.login-card h1{text-align:center;font-size:22px;margin-bottom:4px}
.login-card p.sub{text-align:center;color:var(--muted);font-size:13px;margin-bottom:22px}
.login-card label{display:block;font-size:13px;font-weight:600;margin:12px 0 6px}
.login-card input{width:100%;padding:13px 14px;border:1.5px solid #ddd;border-radius:10px;font-size:15px;outline:none}
.login-card input:focus{border-color:var(--red)}
.login-card button{width:100%;background:var(--red);color:#fff;border:none;padding:14px;border-radius:10px;font-weight:700;font-size:15px;margin-top:20px;transition:.2s}
.login-card button:hover{background:var(--red-dark)}
.login-err{background:#ffe9ea;color:var(--red);padding:10px 14px;border-radius:10px;font-size:13.5px;text-align:center;margin-bottom:8px}

/* ===== LAYOUT ===== */
.topbar{background:#fff;border-bottom:1px solid var(--line);padding:12px 18px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40}
.topbar .brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:17px}
.topbar .brand .dot{width:32px;height:32px;background:var(--red);border-radius:9px;display:flex;align-items:center;justify-content:center}
.topbar .brand .dot svg{width:18px;height:18px;fill:#fff}
.topbar .logout{background:#fff;border:1.5px solid var(--red);color:var(--red);padding:8px 16px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none}
.topbar .logout:hover{background:var(--red);color:#fff}

.layout{max-width:1000px;margin:auto;padding:18px 16px 60px}

/* ===== TABS ===== */
.tabs{display:flex;gap:8px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:6px;margin-bottom:20px;overflow-x:auto}
.tab-btn{flex:1;min-width:90px;background:none;border:none;padding:11px 10px;border-radius:10px;font-size:13.5px;font-weight:600;color:var(--muted);display:flex;flex-direction:column;align-items:center;gap:4px;transition:.15s;white-space:nowrap}
.tab-btn svg{width:20px;height:20px;fill:currentColor}
.tab-btn.active{background:var(--red);color:#fff}
.tab-content{display:none;animation:fade .3s ease}
.tab-content.active{display:block}
@keyframes fade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}

/* ===== MESSAGE ===== */
.msg{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:600}
.msg.ok{background:#e7f7ee;color:var(--green);border:1px solid #b7e6c9}
.msg.err{background:#ffe9ea;color:var(--red);border:1px solid #f5c0c4}

/* ===== STATS ===== */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.stat-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 14px}
.stat-card .ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
.stat-card .ic svg{width:22px;height:22px;fill:#fff}
.ic.c1{background:#e01e2b}.ic.c2{background:#f59e0b}.ic.c3{background:#2563eb}.ic.c4{background:#1b9e4b}
.stat-num{font-size:26px;font-weight:800;line-height:1}
.stat-lbl{font-size:12.5px;color:var(--muted);margin-top:5px}

/* ===== CARD ===== */
.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;margin-bottom:20px}
.card h3{font-size:15.5px;margin-bottom:16px;padding-left:11px;border-left:4px solid var(--red);display:flex;align-items:center;gap:8px}

/* ===== FORM ===== */
.form-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px}
.fg{display:flex;flex-direction:column}
.fg label{font-size:12.5px;font-weight:600;color:var(--muted);margin-bottom:6px}
.fg input,.fg select{padding:12px;border:1.5px solid #ddd;border-radius:10px;font-size:14.5px;outline:none;width:100%}
.fg input:focus,.fg select:focus{border-color:var(--red)}
.btn{background:var(--red);color:#fff;border:none;padding:13px 22px;border-radius:10px;font-weight:700;font-size:14px;transition:.2s}
.btn:hover{background:var(--red-dark)}
.btn.full{width:100%}
.btn.ghost{background:#fff;border:1.5px solid #ddd;color:var(--ink)}
.btn.sm{padding:8px 14px;font-size:12.5px}
.btn.danger{background:#fff;border:1.5px solid var(--red);color:var(--red)}
.btn.danger:hover{background:var(--red);color:#fff}

/* ===== PACK LIST ===== */
.pack-list{display:flex;flex-direction:column;gap:12px}
.pack-item{border:1px solid var(--line);border-radius:12px;padding:14px}
.pack-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.pack-title{font-weight:800;font-size:16px;display:flex;align-items:center;gap:8px}
.pack-title .badge{background:#fff4d6;color:#a16207;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px}
.pack-inline{display:grid;grid-template-columns:1fr 1fr 1fr auto auto;gap:8px;align-items:end}
.pack-inline .fg label{font-size:11px}
.pack-inline .fg input{padding:9px}

/* ===== TOGGLE MODES ===== */
.mode-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.mode-opt{border:2px solid var(--line);border-radius:12px;padding:14px;cursor:pointer;text-align:center;transition:.15s}
.mode-opt.selected{border-color:var(--red);background:#fff6f6}
.mode-opt .mt{font-weight:700;font-size:14px}
.mode-opt .md{font-size:12px;color:var(--muted);margin-top:3px}

@media(max-width:720px){
  .stats-grid{grid-template-columns:1fr 1fr}
  .form-row{grid-template-columns:1fr}
  .pack-inline{grid-template-columns:1fr 1fr}
  .pack-inline .del-cell,.pack-inline .save-cell{grid-column:span 1}
}
</style>
</head>
<body>

<?php if (!$is_admin): ?>
<!-- ================= LOGIN ================= -->
<div class="login-screen">
  <form class="login-card" method="post">
    <div class="login-logo">
      <svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6z"/></svg>
    </div>
    <h1>Admin Panel</h1>
    <p class="sub">FF Diamond TopUp — Control Center</p>
    <?php if($login_error): ?><div class="login-err"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
    <label>Username</label>
    <input type="text" name="username" placeholder="Enter username" autocomplete="off" required>
    <label>Password</label>
    <input type="password" name="password" placeholder="Enter password" required>
    <button type="submit" name="do_login">Login</button>
  </form>
</div>

<?php else: ?>
<!-- ================= DASHBOARD ================= -->
<div class="topbar">
  <div class="brand">
    <span class="dot"><svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6z"/></svg></span>
    Admin Panel
  </div>
  <a href="admin.php?logout=1" class="logout">Logout</a>
</div>

<div class="layout">

  <?php if($msg): ?><div class="msg <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab-btn active" onclick="openTab(event,'dash')">
      <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>Dashboard
    </button>
    <button class="tab-btn" onclick="openTab(event,'packs')">
      <svg viewBox="0 0 24 24"><path d="M12 2 2 8l10 6 10-6-10-6zM2 16l10 6 10-6M2 12l10 6 10-6"/></svg>Packs
    </button>
    <button class="tab-btn" onclick="openTab(event,'pay')">
      <svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4H4V6h16v2z"/></svg>Payments
    </button>
    <button class="tab-btn" onclick="openTab(event,'settings')">
      <svg viewBox="0 0 24 24"><path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm8.9 4c0-.5 0-1-.1-1.4l2-1.6-2-3.5-2.4 1a7 7 0 0 0-2.5-1.4L15.5 2h-4l-.4 2.6a7 7 0 0 0-2.5 1.4l-2.4-1-2 3.5 2 1.6c0 .9 0 1.9.1 2.8l-2 1.6 2 3.5 2.4-1c.7.6 1.6 1.1 2.5 1.4l.4 2.6h4l.4-2.6c.9-.3 1.8-.8 2.5-1.4l2.4 1 2-3.5-2-1.6c.1-.5.1-1 .1-1.4z"/></svg>Settings
    </button>
  </div>

  <!-- ===== DASHBOARD TAB ===== -->
  <div id="dash" class="tab-content active">
    <div class="stats-grid">
      <div class="stat-card">
        <div class="ic c1"><svg viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5z"/></svg></div>
        <div class="stat-num"><?= number_format($stats['clicks'] ?? 0) ?></div>
        <div class="stat-lbl">Total Website Clicks</div>
      </div>
      <div class="stat-card">
        <div class="ic c2"><svg viewBox="0 0 24 24"><path d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm10 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7.2 14.6l.1.4h11.5v-2H8.5l-.4-1.6L21 11l1-6H5.2L4.3 2H1v2h2l3 12z"/></svg></div>
        <div class="stat-num"><?= number_format($stats['checkouts'] ?? 0) ?></div>
        <div class="stat-lbl">Checkouts (Payment Page)</div>
      </div>
      <div class="stat-card">
        <div class="ic c3"><svg viewBox="0 0 24 24"><path d="M12 2 2 8l10 6 10-6-10-6z"/></svg></div>
        <div class="stat-num"><?= count($packs) ?></div>
        <div class="stat-lbl">Total Diamond Packs</div>
      </div>
      <div class="stat-card">
        <div class="ic c4"><svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.3-.6-3-1.2-3-2.1 0-1 1-1.7 2.5-1.7 1.6 0 2.2.8 2.3 1.9h2c-.1-1.6-1.1-3-3-3.4V3.5h-2.7V5.6C8.2 6 6.9 7.2 6.9 9c0 2 1.7 3 4.1 3.6 2.2.5 2.7 1.3 2.7 2.1 0 .6-.4 1.6-2.5 1.6-1.9 0-2.6-.9-2.7-1.9h-2c.1 2 1.6 3.1 3.4 3.5v2.1h2.7v-2.1c1.8-.3 3.2-1.4 3.2-3.3 0-2.5-2.1-3.3-4.7-3.9z"/></svg></div>
        <div class="stat-num">₹<?= number_format($revenue) ?></div>
        <div class="stat-lbl">Total Pack Value</div>
      </div>
    </div>
    <div class="card">
      <h3>Quick Info</h3>
      <p style="font-size:14px;color:var(--muted)">
        Current payment mode: <b style="color:var(--ink)"><?= $settings['payment_mode']==='razorpay'?'Razorpay':'All UPI' ?></b><br>
        UPI ID: <b style="color:var(--ink)"><?= htmlspecialchars($settings['upi_id']) ?></b>
      </p>
      <form method="post" onsubmit="return confirm('Reset all click & checkout stats to 0?')" style="margin-top:14px">
        <button class="btn danger sm" name="reset_stats">Reset Stats</button>
      </form>
    </div>
  </div>

  <!-- ===== PACKS TAB ===== -->
  <div id="packs" class="tab-content">
    <div class="card">
      <h3>Add New Pack</h3>
      <form method="post">
        <div class="form-row">
          <div class="fg"><label>Diamonds</label><input type="number" name="diamonds" placeholder="100" required></div>
          <div class="fg"><label>Original Price (₹)</label><input type="number" name="original" placeholder="149" required></div>
          <div class="fg"><label>Offer Price (₹)</label><input type="number" name="offer" placeholder="77" required></div>
        </div>
        <button class="btn full" name="add_pack">+ Add Pack</button>
      </form>
    </div>

    <div class="card">
      <h3>Manage Packs (<?= count($packs) ?>)</h3>
      <div class="pack-list">
        <?php foreach ($packs as $p):
          $off = $p['original']>0 ? round((($p['original']-$p['offer'])/$p['original'])*100):0; ?>
          <div class="pack-item">
            <div class="pack-head">
              <div class="pack-title"><?= (int)$p['diamonds'] ?> 💎 <span class="badge"><?= $off ?>% OFF</span></div>
              <form method="post" onsubmit="return confirm('Delete this pack?')">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn danger sm" name="delete_pack">Delete</button>
              </form>
            </div>
            <form method="post">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <div class="pack-inline">
                <div class="fg"><label>Diamonds</label><input type="number" name="diamonds" value="<?= (int)$p['diamonds'] ?>" required></div>
                <div class="fg"><label>Original ₹</label><input type="number" name="original" value="<?= (int)$p['original'] ?>" required></div>
                <div class="fg"><label>Offer ₹</label><input type="number" name="offer" value="<?= (int)$p['offer'] ?>" required></div>
                <div class="fg save-cell"><label>&nbsp;</label><button class="btn sm" name="edit_pack">Save</button></div>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ===== PAYMENTS TAB ===== -->
  <div id="pay" class="tab-content">
    <div class="card">
      <h3>Payment Settings</h3>
      <form method="post">
        <label style="font-size:12.5px;font-weight:600;color:var(--muted);display:block;margin-bottom:8px">Select Payment Mode</label>
        <div class="mode-row">
          <label class="mode-opt <?= $settings['payment_mode']==='all_upi'?'selected':'' ?>" onclick="selMode('all_upi',this)">
            <input type="radio" name="payment_mode" value="all_upi" hidden <?= $settings['payment_mode']==='all_upi'?'checked':'' ?>>
            <div class="mt">All UPI</div>
            <div class="md">PhonePe, Paytm, GPay, QR — direct UPI</div>
          </label>
          <label class="mode-opt <?= $settings['payment_mode']==='razorpay'?'selected':'' ?>" onclick="selMode('razorpay',this)">
            <input type="radio" name="payment_mode" value="razorpay" hidden <?= $settings['payment_mode']==='razorpay'?'checked':'' ?>>
            <div class="mt">Razorpay</div>
            <div class="md">UPI + TR code integration</div>
          </label>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="fg"><label>UPI ID</label><input type="text" name="upi_id" value="<?= htmlspecialchars($settings['upi_id']) ?>" placeholder="name@bank" required></div>
          <div class="fg"><label>UPI / Merchant Name</label><input type="text" name="upi_name" value="<?= htmlspecialchars($settings['upi_name']) ?>" placeholder="FF TopUp Store" required></div>
        </div>
        <div class="fg" style="margin-bottom:14px"><label>Razorpay Key (only for Razorpay mode)</label><input type="text" name="razorpay_key" value="<?= htmlspecialchars($settings['razorpay_key']) ?>" placeholder="rzp_live_xxxxxxxx"></div>
        <button class="btn full" name="save_payment">Save Payment Settings</button>
      </form>
    </div>
  </div>

  <!-- ===== SETTINGS TAB ===== -->
  <div id="settings" class="tab-content">
    <div class="card">
      <h3>Change Admin Password</h3>
      <form method="post">
        <div class="fg" style="margin-bottom:12px"><label>Current Password</label><input type="password" name="current_pass" required></div>
        <div class="fg" style="margin-bottom:14px"><label>New Password</label><input type="password" name="new_pass" required></div>
        <button class="btn full" name="change_pass">Update Password</button>
      </form>
    </div>
    <div class="card">
      <h3>Account Info</h3>
      <p style="font-size:14px;color:var(--muted)">Logged in as: <b style="color:var(--ink)"><?= htmlspecialchars($settings['admin_user']) ?></b></p>
    </div>
  </div>

</div>

<script>
function openTab(e, id){
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  e.currentTarget.classList.add('active');
  window.scrollTo({top:0,behavior:'smooth'});
}
function selMode(mode, el){
  document.querySelectorAll('.mode-opt').forEach(m=>m.classList.remove('selected'));
  el.classList.add('selected');
  el.querySelector('input').checked = true;
}
</script>

<?php endif; ?>
</body>
</html>
