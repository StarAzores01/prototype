
<button type="button" class="topbar-icon-btn theme-toggle-btn" id="themeToggleBtn" title="Toggle dark mode" onclick="pathriveToggleTheme()">
  <i class="fas fa-moon" id="themeToggleIcon"></i>
</button>
<script>
(function () {
  function applyIcon() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var icon = document.getElementById('themeToggleIcon');
    if (icon) icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    var btn = document.getElementById('themeToggleBtn');
    if (btn) btn.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
  }

  window.pathriveToggleTheme = function () {
    var html = document.documentElement;
    var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    if (next === 'dark') {
      html.setAttribute('data-theme', 'dark');
    } else {
      html.removeAttribute('data-theme');
    }
    try { localStorage.setItem('pathrive-theme', next); } catch (e) {}
    applyIcon();
  };

  document.addEventListener('DOMContentLoaded', applyIcon);
})();
</script>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/partials/theme-toggle-button.blade.php ENDPATH**/ ?>