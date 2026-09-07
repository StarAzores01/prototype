{{--
  Shared "Log out?" confirmation modal — one instance per page, opened by
  every Logout link/button across all 4 role layouts (sidebar footer,
  profile dropdown) instead of submitting the logout form immediately.

  Markup reuses the existing .modal-overlay/.modal/.modal-header/
  .modal-body/.modal-footer classes (assets/css/style.css) so it matches
  every other modal in the app. The real logout form lives here, once —
  each layout's Logout trigger just calls openModal('logoutConfirm');
  Cancel calls closeModal('logoutConfirm') and sends nothing.
--}}
<div class="modal-overlay" id="modal-logoutConfirm">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h2><i class="fas fa-right-from-bracket"></i> Log out?</h2>
      <button type="button" class="modal-close" onclick="closeModal('logoutConfirm')" aria-label="Cancel"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <p style="margin:0;color:var(--gray-600);font-size:13.5px">You'll need to log in again to access your dashboard.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="closeModal('logoutConfirm')">Cancel</button>
      <form method="POST" action="{{ route('logout') }}" style="margin:0">
        @csrf
        <button type="submit" class="btn btn-danger"><i class="fas fa-right-from-bracket"></i> Log Out</button>
      </form>
    </div>
  </div>
</div>
