{{-- Shared topbar/modal/toast behavior for all 4 role layouts
     (ec/trainer/evaluator/beneficiary) — was copy-pasted 4x with drift
     (beneficiary was missing the click-outside-to-close wiring for
     modals). Included once per layout instead. --}}
<script>
/* ── Dropdowns ── */
function toggleDropdown() {
  const d = document.getElementById('profileDropdown');
  d?.classList.toggle('open');
  const nd = document.getElementById('notifDropdown');
  if (nd) nd.style.display = 'none';
}

function toggleNotifDropdown() {
  const nd = document.getElementById('notifDropdown');
  if (!nd) return;
  nd.style.display = nd.style.display === 'none' ? 'block' : 'none';
  document.getElementById('profileDropdown')?.classList.remove('open');
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

/* ── Modals ── */
function openModal(id) {
  document.getElementById('modal-' + id)?.classList.add('open');
}
function closeModal(id) {
  document.getElementById('modal-' + id)?.classList.remove('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

/* ── Toast ── */
function showToast(msg, type = 'info') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
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

@if(session('success'))
showToast(@json(session('success')), 'success');
@elseif(session('error'))
showToast(@json(session('error')), 'error');
@endif

/* ── Unsaved-changes guard ──────────────────────────────────────────────
   Tracks whether any guarded form has been modified since the last page
   load / successful save. Only shows the browser's "Leave site?" dialog
   when there really ARE unsaved changes — not on every navigation.

   Guarding is opt-OUT, not opt-in: with 50+ forms across every role's
   views, an opt-in attribute is guaranteed to be forgotten on most of
   them (that's exactly what happened before this comment was written —
   the guard existed but zero forms carried the opt-in attribute, so it
   never actually fired for real unsaved data). Every form is guarded
   automatically unless it plainly has nothing worth warning about:
     • method="GET"                     → search/filter forms, no data
     • action ends in /logout           → the logout button
     • [data-no-unsaved-guard]          → manual escape hatch
     • no visible input/textarea/select → delete / approve / status-toggle
       forms that carry only a hidden id + a submit button (these already
       get their own onsubmit confirm() where it matters)

   Other helpers:
     pathriveMarkDirty()   ← call manually if you modify the DOM outside
                              a form input event
     pathriveMarkClean()   ← call after a successful AJAX save

   The guard fires on:
     • Closing the tab / window
     • Navigating to an EXTERNAL page (e.g. the public home button → /)
     • Any unload that isn't a clean internal form submit

   The guard is suppressed on:
     • Form submit (the user is intentionally saving) — this covers both
       a real 'submit' event AND a plain form.submit() call (used by the
       avatar uploader and the auto-submit-on-change status dropdowns);
       form.submit() deliberately does NOT fire a 'submit' event per the
       DOM spec, so it's patched below instead of relying on that event
     • Internal navigation links (sidebar, topbar, profile dropdown)
       — navigating between authenticated pages never causes data loss
   ──────────────────────────────────────────────────────────────────── */
(function () {
  var _dirty = false;

  window.pathriveMarkDirty = function () { _dirty = true; };
  window.pathriveMarkClean = function () { _dirty = false; };

  function isGuardable(form) {
    if (form.hasAttribute('data-no-unsaved-guard')) return false;
    if ((form.getAttribute('method') || 'GET').toUpperCase() === 'GET') return false;
    if (/\/logout\/?$/.test(form.getAttribute('action') || '')) return false;
    return Array.prototype.some.call(form.elements, function (el) {
      var type = (el.type || '').toLowerCase();
      return ['hidden', 'submit', 'button', 'reset'].indexOf(type) === -1 && !el.disabled;
    });
  }

  function attachGuard(form) {
    form.addEventListener('input',  function () { _dirty = true; }, { passive: true });
    form.addEventListener('change', function () { _dirty = true; }, { passive: true });
  }

  function guardIfEligible(form) {
    if (isGuardable(form)) attachGuard(form);
  }

  document.querySelectorAll('form').forEach(guardIfEligible);

  /* form.submit() bypasses the 'submit' event by design (so onsubmit
     handlers can't recurse) — patch it so programmatic submits still
     clear the dirty flag right before the navigation they cause. */
  var nativeFormSubmit = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = function () {
    _dirty = false;
    return nativeFormSubmit.apply(this, arguments);
  };

  /* Also watch forms added dynamically (modals injected after load, etc.) */
  var mo = new MutationObserver(function (mutations) {
    mutations.forEach(function (m) {
      m.addedNodes.forEach(function (node) {
        if (node.nodeType !== 1) return;
        if (node.matches && node.matches('form')) guardIfEligible(node);
        node.querySelectorAll && node.querySelectorAll('form').forEach(guardIfEligible);
      });
    });
  });
  mo.observe(document.body, { childList: true, subtree: true });

  /* Suppress on form submit — user is intentionally saving */
  document.addEventListener('submit', function () {
    _dirty = false;
  }, { capture: true });

  /* Suppress on internal app links (sidebar, topbar, profile dropdown) */
  document.addEventListener('click', function (e) {
    var anchor = e.target.closest('a[href]');
    if (!anchor) return;
    /* The public Home button lives inside .topbar alongside the (truly
       internal) notification links, but it deliberately leaves the
       authenticated section for the public marketing site — the one
       case this guard's own doc comment calls out as something that
       SHOULD warn. Handle it before the .closest('.topbar') check below
       would otherwise swallow it. */
    if (anchor.classList.contains('topbar-home-btn')) return;
    var href = anchor.getAttribute('href') || '';
    if (!href || href.startsWith('#') || href.startsWith('mailto:')) return;
    var internalPrefixes = ['/ec/', '/trainer/', '/evaluator/', '/beneficiary/', '/files/'];
    /* route() emits absolute URLs (scheme + host) everywhere in this app,
       so matching the raw href string against a path prefix never hits —
       anchor.pathname is browser-normalized to just the path regardless
       of whether the href attribute was relative or absolute. */
    var isInternal = internalPrefixes.some(function (p) { return anchor.pathname.startsWith(p); })
                  || anchor.closest('.sidebar')
                  || anchor.closest('.topbar')
                  || anchor.closest('.profile-dropdown');
    if (isInternal) {
      _dirty = false;
    }
  }, { capture: true });

  /* beforeunload — fires when leaving the page normally (tab close,
     external link, typing a new URL, the public Home button).
     Modern browsers ignore the custom message string and show their own
     generic "Leave site?" dialog — setting returnValue is enough. */
  window.addEventListener('beforeunload', function (e) {
    if (!_dirty) return;
    e.preventDefault();
    e.returnValue = '';   /* required by Chrome/Edge to trigger the dialog */
  });

  /* ── Back/Forward button bfcache guard ──────────────────────────────
     Browsers cache authenticated pages in memory (bfcache) so that
     pressing Back restores the page instantly without an HTTP request —
     bypassing both the no-cache headers set by PreventBackHistoryCache
     middleware AND the beforeunload handler above.

     Fix: listen for `pageshow` with `event.persisted === true`, which
     means the page was just restored from bfcache. Force a full reload
     so the server's no-cache headers take effect and an unauthenticated
     user cannot see stale authenticated content just by pressing Back.

     Additionally: if the page was dirty (unsaved changes) and we detect
     a bfcache restore, reload anyway — the data was already not saved,
     and showing a stale cached version of the form would be confusing.
  ──────────────────────────────────────────────────────────────────── */
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
      /* Page was restored from bfcache after Back/Forward navigation.
         Reload to enforce authentication and avoid stale content. */
      window.location.reload();
    }
  });

  /* pagehide with persisted=false means the page is actually being
     unloaded (not cached). Nothing extra needed — beforeunload already
     handles the warning in that path. */

})();
<<<<<<< Updated upstream
=======

/* ── Image upload confirmation ───────────────────────────────────────────
   Every image/banner/cover upload (profile picture, program cover image,
   activity display picture) shows a custom "Upload this image?" panel
   with a thumbnail preview before the real POST fires, instead of
   uploading immediately — see partials.upload-confirm-modal for the
   markup (reuses the existing .modal-overlay/.modal classes, so it looks
   like every other modal in the app rather than a one-off popup).

     pathriveConfirmImageUpload(form, file, opts)
       Shows the panel for `file`, wires Confirm to submit `form` and
       Cancel to run opts.onCancel (if given). opts.title overrides the
       panel heading (defaults to "Upload this image?").

     pathriveRequestImageUpload(form, opts)
       Convenience for a form whose Save button is type="button" (never
       triggers a real 'submit') — reads the file from the form's
       input[type=file], runs native required-field validation if it's
       empty, otherwise hands off to pathriveConfirmImageUpload.

   Confirm calls form.submit() — the same native, guard-patched submit
   used by the avatar auto-uploader (see layout-scripts guard above),
   which does NOT dispatch a 'submit' event per the DOM spec, so it goes
   straight through instead of re-entering any submit-time logic.
   ──────────────────────────────────────────────────────────────────── */
(function () {
  function panelEls() {
    var overlay = document.getElementById('modal-imageUploadConfirm');
    if (!overlay) return null;
    return {
      overlay: overlay,
      img: document.getElementById('imageUploadConfirmPreview'),
      title: document.getElementById('imageUploadConfirmTitle'),
      confirmBtn: document.getElementById('imageUploadConfirmBtn'),
      cancelBtn: document.getElementById('imageUploadCancelBtn'),
      closeBtn: document.getElementById('imageUploadCancelXBtn'),
    };
  }

  window.pathriveConfirmImageUpload = function (form, file, opts) {
    opts = opts || {};
    var els = panelEls();
    if (!els || !file) {
      /* Fail open: no confirmation panel available on this page (or
         nothing was actually selected) — submit rather than silently
         drop the upload. */
      form.submit();
      return;
    }

    els.title.textContent = opts.title || 'Upload this image?';
    els.img.src = '';

    var reader = new FileReader();
    reader.onload = function (e) { els.img.src = e.target.result; };
    reader.readAsDataURL(file);

    els.overlay.classList.add('open');

    function cleanup() {
      els.overlay.classList.remove('open');
      els.confirmBtn.removeEventListener('click', onConfirm);
      els.cancelBtn.removeEventListener('click', onCancel);
      els.closeBtn.removeEventListener('click', onCancel);
    }
    function onConfirm() {
      cleanup();
      form.submit();
    }
    function onCancel() {
      cleanup();
      if (opts.onCancel) opts.onCancel();
    }

    els.confirmBtn.addEventListener('click', onConfirm);
    els.cancelBtn.addEventListener('click', onCancel);
    els.closeBtn.addEventListener('click', onCancel);
  };

  window.pathriveRequestImageUpload = function (form, opts) {
    var fileInput = form.querySelector('input[type="file"]');
    var file = fileInput && fileInput.files && fileInput.files[0];
    if (!file) {
      if (fileInput && fileInput.reportValidity) fileInput.reportValidity();
      return;
    }
    window.pathriveConfirmImageUpload(form, file, opts);
  };
})();

/* ── Date-range pairing ──────────────────────────────────────────────────
   Every start/end date pair in the app (Program create, Activity create/
   edit) wires up automatically via two HTML attributes — no per-page
   script needed, which matters because this partial is included after the
   page's own main content in every layout, so a content page's own inline
   script can't reliably call into a function defined here (it wouldn't
   exist yet at that point in page load). Auto-wiring from data-attributes
   sidesteps that ordering problem entirely: it just runs, right here, once
   the page's own content is already fully present in the DOM.

   Usage: give the start date-input a data-range-end attribute set to the
   end input's id. That is the whole setup — both inputs just need an id.

   Backend validation (after_or_equal / after) is what actually enforces
   this — this is just the frontend convenience so the picker doesn't even
   offer an invalid date. ──────────────────────────────────────────────── */
(function () {
  function wire(startInput) {
    var endInput = document.getElementById(startInput.dataset.rangeEnd);
    if (!endInput) return;

    function sync() {
      if (startInput.value) {
        endInput.min = startInput.value;
        if (endInput.value && endInput.value < startInput.value) endInput.value = '';
      } else {
        endInput.removeAttribute('min');
      }
    }

    startInput.addEventListener('change', sync);
    sync();
  }

  document.querySelectorAll('input[type="date"][data-range-end]').forEach(wire);
})();
>>>>>>> Stashed changes
</script>
