  </div><!-- /.page-content -->
</main>

<div class="toast-container" id="toastContainer"></div>

<div style="text-align:center;padding:12px 24px;font-size:11.5px;color:var(--gray-400);border-top:1px solid var(--gray-100);margin-left:var(--sidebar-w)">
  <a href="<?= BASE_URL ?>/terms.php"   style="color:var(--gray-400);margin:0 8px">Terms of Use</a> ·
  <a href="<?= BASE_URL ?>/privacy.php" style="color:var(--gray-400);margin:0 8px">Privacy Policy</a>
  · PAThrive © <?= date('Y') ?> CIT-SLSU
</div>

<script>
function toggleDropdown() {
  document.getElementById('profileDropdown')?.classList.toggle('open');
  const nd = document.getElementById('notifDropdown');
  if (nd) nd.style.display = 'none';
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.profile-btn'))
    document.getElementById('profileDropdown')?.classList.remove('open');
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
function openModal(id)  { document.getElementById('modal-' + id)?.classList.add('open'); }
function closeModal(id) { document.getElementById('modal-' + id)?.classList.remove('open'); }
function showToast(msg, type='info') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = msg;
  c.appendChild(t);
  setTimeout(() => { t.style.cssText='opacity:0;transform:translateX(100%);transition:all .3s ease'; setTimeout(()=>t.remove(),300); }, 3500);
}
<?php $flash = getFlash(); if ($flash): ?>
showToast(<?= json_encode($flash['msg']) ?>, <?= json_encode($flash['type']) ?>);
<?php endif; ?>
</script>
</body>
</html>
