<?php
require 'config.php';
$packs = get_data('packs');
function off_percent($o, $of){ return $o > 0 ? round((($o - $of) / $o) * 100) : 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Shop Diamonds — FF TopUp</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-enter">

<header class="app-header">
  <div class="hdr-left">
    <a href="index.php" class="back-btn">
      <svg viewBox="0 0 24 24" class="tag-svg"><path d="M15 5l-7 7 7 7"/></svg>
    </a>
  </div>
  <div class="hdr-center"><img src="assets/bisicon.png" alt="bis" class="hdr-icon-lg"></div>
  <div class="hdr-right"></div>
</header>

<main class="container">
  <div class="shop-user" id="shopUser"></div>

  <h2 class="section-title center">Select Diamond Pack</h2>

  <div class="packs-grid">
    <?php foreach ($packs as $p): $off = off_percent($p['original'], $p['offer']); ?>
      <div class="pack-card"
           data-diamonds="<?= htmlspecialchars($p['diamonds']) ?>"
           data-offer="<?= (int)$p['offer'] ?>"
           data-original="<?= (int)$p['original'] ?>">
        <span class="off-tag"><?= $off ?>% OFF</span>
        <img src="assets/daimond_icon.png" class="pack-diamond" alt="diamond">
        <div class="pack-amount"><?= htmlspecialchars($p['diamonds']) ?></div>
        <div class="pack-sub">Diamonds</div>
        <button type="button" class="btn-price" onclick="pickPack(this)">
          <span class="old-price">₹<?= (int)$p['original'] ?></span>
          <span class="new-price">₹<?= (int)$p['offer'] ?></span>
        </button>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<footer class="app-footer">
  <img src="assets/garenalogo.png" alt="garena" class="footer-logo">
  <p class="footer-copy">© <?= date('Y') ?> FF Diamond TopUp.</p>
</footer>

<script>
// show verified account
(function(){
  const acc = JSON.parse(localStorage.getItem('ffAccount') || '{}');
  const box = document.getElementById('shopUser');
  if (acc.UID) {
    box.innerHTML =
      '<img src="assets/avatar.png" alt="av">' +
      '<div><div class="su-name">' + (acc.Name || 'Player') + '</div>' +
      '<div class="su-uid">UID: ' + acc.UID + '</div></div>';
  } else {
    box.style.display = 'none';
  }
})();

function pickPack(btn){
  const c = btn.closest('.pack-card');
  const pack = {
    diamonds: c.dataset.diamonds,
    offer:    c.dataset.offer,
    original: c.dataset.original
  };
  localStorage.setItem('ffPack', JSON.stringify(pack));
  document.body.classList.add('page-leave');
  setTimeout(function(){ location.href = 'payment.php'; }, 350);
}
</script>
</body>
</html>
