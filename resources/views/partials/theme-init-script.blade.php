{{-- Runs synchronously, first thing in <head>, before CSS paints — sets
     data-theme="dark" on <html> from localStorage so there's no flash of
     light-mode before the toggle script (partials.theme-toggle-button)
     takes over. Shared by all 4 role layouts; do not copy-paste this. --}}
<script>
(function () {
  try {
    if (localStorage.getItem('pathrive-theme') === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  } catch (e) {}
})();
</script>
