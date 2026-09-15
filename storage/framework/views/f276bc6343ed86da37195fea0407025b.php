
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
      <form method="POST" action="<?php echo e(route('logout')); ?>" style="margin:0">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-danger"><i class="fas fa-right-from-bracket"></i> Log Out</button>
      </form>
    </div>
  </div>
</div>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/partials/logout-confirm-modal.blade.php ENDPATH**/ ?>