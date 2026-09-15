
<?php $navPrefix = $navPrefix ?? ''; ?>
<?php if($loggedIn): ?>
  <div class="<?php echo e($navPrefix); ?>nav-actions">
    <span style="font-size:13px;font-weight:600;color:rgba(255,255,255,.85);display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;max-width:100%;min-width:0">
      <i class="fas fa-circle-user"></i> <span style="min-width:0;overflow-wrap:anywhere"><?php echo e($userName); ?></span>
      <span style="color:rgba(255,255,255,.5);font-weight:500;overflow-wrap:anywhere">(<?php echo e($roleLabel); ?>)</span>
    </span>
    <a href="<?php echo e($dashboardUrl); ?>" class="<?php echo e($navPrefix); ?>btn-signup">
      <i class="fas fa-gauge"></i> Go to Dashboard
    </a>
  </div>
<?php else: ?>
  <div class="<?php echo e($navPrefix); ?>nav-actions">
    <a href="<?php echo e(route('login')); ?>" class="<?php echo e($navPrefix); ?>btn-login">
      <i class="fas fa-arrow-right"></i> Log In
    </a>
    <a href="<?php echo e(route('choose-role')); ?>" class="<?php echo e($navPrefix); ?>btn-signup">
      <i class="fas fa-plus"></i> Sign Up
    </a>
  </div>
<?php endif; ?>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/public/partials/nav.blade.php ENDPATH**/ ?>