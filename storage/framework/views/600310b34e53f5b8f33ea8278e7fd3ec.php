
<script>
(function () {
  var link = document.getElementById('goBackLink');
  if (!link) return;
  link.addEventListener('click', function (e) {
    if (document.referrer) {
      try {
        if (new URL(document.referrer).origin === window.location.origin && window.history.length > 1) {
          e.preventDefault();
          window.history.back();
        }
      } catch (err) { /* malformed/opaque referrer — let the href fallback run */ }
    }
    // No same-origin referrer to go back to — default action proceeds,
    // navigating to the link's own href (Home).
  });
})();
</script>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/public/partials/smart-back-script.blade.php ENDPATH**/ ?>