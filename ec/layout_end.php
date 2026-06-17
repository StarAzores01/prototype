  </div><!-- /page-content -->
</main>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<!-- Footer links -->
<div style="text-align:center;padding:12px 24px;font-size:11.5px;color:var(--gray-400);border-top:1px solid var(--gray-100);margin-left:var(--sidebar-w)">
  <a href="<?= BASE_URL ?>/terms.php"   style="color:var(--gray-400);margin:0 8px">Terms of Use</a> ·
  <a href="<?= BASE_URL ?>/privacy.php" style="color:var(--gray-400);margin:0 8px">Privacy Policy</a>
  · PAThrive © <?= date('Y') ?> CIT-SLSU
</div>

<!-- SHARED JS -->
<script>
function toggleDropdown() {
  const d = document.getElementById('profileDropdown');
  d.classList.toggle('open');
  document.getElementById('notifDropdown')?.style && (document.getElementById('notifDropdown').style.display = 'none');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.profile-btn')) {
    document.getElementById('profileDropdown')?.classList.remove('open');
  }
  if (!e.target.closest('.notif-btn')) {
    const nd = document.getElementById('notifDropdown');
    if (nd) nd.style.display = 'none';
  }
});

function toggleNotifDropdown() {
  const nd = document.getElementById('notifDropdown');
  if (!nd) return;
  nd.style.display = nd.style.display === 'none' ? 'block' : 'none';
  document.getElementById('profileDropdown')?.classList.remove('open');
}

// Modal helpers
function openModal(id) {
  document.getElementById('modal-' + id)?.classList.add('open');
}
function closeModal(id) {
  document.getElementById('modal-' + id)?.classList.remove('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

// Toast
function showToast(msg, type = 'info') {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
  t.innerHTML = `<i class="fas ${icon}"></i> ${msg}`;
  c.appendChild(t);
  setTimeout(() => {
    t.style.cssText = 'opacity:0;transform:translateX(100%);transition:all .3s ease';
    setTimeout(() => t.remove(), 300);
  }, 3500);
}

// Auto-show PHP flash messages
<?php
$flash = getFlash();
if ($flash): ?>
showToast(<?= json_encode($flash['msg']) ?>, <?= json_encode($flash['type']) ?>);
<?php endif; ?>
</script>
</body>
</html>
