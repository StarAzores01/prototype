{{--
  "Go Back" for the Terms of Use / Privacy Policy pages.

  Every link to these pages (signup forms, dashboard footers, the
  Terms <-> Privacy cross-links) opens in the same tab, so real browser
  history always has the actual previous page in it — a plain
  history.back() is enough, restoring the previous page (e.g. a signup
  form's typed-in fields) via the browser's normal back/forward cache.

  The link's own href="{{ route('home') }}" is the fallback for the one
  case history.back() can't help with: the page opened directly (typed
  URL, bookmark) with no same-site page before it in this tab's history.
--}}
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
