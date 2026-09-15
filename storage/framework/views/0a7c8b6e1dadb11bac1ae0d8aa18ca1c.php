<?php $__env->startSection('content'); ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluators</span></div>
    <h1>Evaluators</h1>
    <p>Manage approved evaluators and their accounts</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addEvaluator')"><i class="fas fa-plus"></i> Add Evaluator</button>
</div>

<!-- Whitelist (Approved List) — shown first, matching the Project Leaders page's
     established order: the approved/pre-registration list before the registered one. -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-clipboard-list"></i> Approved Evaluators List</div>
  </div>
  <div class="card-body" style="padding:0">
    <?php if($whitelist->isEmpty()): ?>
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No evaluators on the approved list yet.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>Assigned ID</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php $__currentLoopData = $whitelist; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
          <td><?php echo e($w->first_name); ?> <?php echo e($w->last_name); ?></td>
          <td><code><?php echo e($w->id_number ?? '—'); ?></code></td>
          <td><?php echo e($w->department); ?></td>
          <td><span class="badge <?php echo e($w->is_registered ? 'badge-success' : 'badge-warning'); ?>"><?php echo e($w->is_registered ? 'Registered' : 'Pending'); ?></span></td>
          <td>
            <?php if(!$w->is_registered): ?>
            <form method="POST" action="<?php echo e(route('ec.evaluators.store')); ?>" onsubmit="return confirm('Remove from approved list?')" style="display:inline">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="remove_whitelist"/>
              <input type="hidden" name="whitelist_id" value="<?php echo e($w->id); ?>"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer"><i class="fas fa-trash"></i> Remove</button>
            </form>
            <?php else: ?>
            <span style="font-size:12px;color:var(--gray-400)">Registered</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Registered Evaluators -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-user"></i> Registered Evaluators</div>
    <form method="GET" action="<?php echo e(route('ec.evaluators')); ?>" style="display:flex;gap:8px">
      <input type="text" name="q" class="form-control" placeholder="Search..." value="<?php echo e($q); ?>" style="width:220px"/>
      <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-magnifying-glass"></i></button>
      <?php if($q): ?><a href="<?php echo e(route('ec.evaluators')); ?>" class="btn btn-outline btn-sm"><i class="fas fa-xmark"></i></a><?php endif; ?>
    </form>
  </div>
  <div class="card-body" style="padding:0">
    <?php if($evaluators->isEmpty()): ?>
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No registered evaluators yet.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Name</th><th>ID</th><th>Department</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php $__currentLoopData = $evaluators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
          <td style="font-weight:600"><?php echo e($ev->first_name); ?> <?php echo e($ev->last_name); ?></td>
          <td><code><?php echo e($ev->id_number); ?></code></td>
          <td><?php echo e($ev->department); ?></td>
          <td><?php echo e($ev->email); ?></td>
          <td>
            <span class="badge <?php echo e($ev->is_active ? 'badge-success' : 'badge-danger'); ?>">
              <?php echo e($ev->is_active ? 'Active' : 'Inactive'); ?>

            </span>
          </td>
          <td>
            <div class="action-btns">
              <button class="btn btn-outline btn-sm" onclick="openEditModal(<?php echo e($ev->toJson()); ?>)"><i class="fas fa-pen"></i> Edit</button>
              <form method="POST" action="<?php echo e(route('ec.evaluators.store')); ?>" style="display:inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="toggle"/>
                <input type="hidden" name="user_id" value="<?php echo e($ev->id); ?>"/>
                <button type="submit" class="btn btn-sm <?php echo e($ev->is_active ? '' : 'btn-outline'); ?>" style="<?php echo e($ev->is_active ? 'background:#FEE2E2;color:#991B1B;border:none' : ''); ?>">
                  <?php echo $ev->is_active ? '<i class="fas fa-ban"></i> Disable' : '<i class="fas fa-square-check"></i> Enable'; ?>

                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add Evaluator Modal -->
<div class="modal-overlay" id="modal-addEvaluator">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-plus"></i> Add Evaluator to Approved List</div>
      <button class="modal-close" onclick="closeModal('addEvaluator')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.evaluators.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="add_whitelist"/>
      <div class="modal-body">
        <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px">The evaluator will use their assigned ID to register an account.</p>
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name <span style="color:var(--red)">*</span></label><input type="text" name="first_name" class="form-control" required/></div>
          <div class="form-group"><label class="form-label">Last Name <span style="color:var(--red)">*</span></label><input type="text" name="last_name" class="form-control" required/></div>
        </div>
        <div class="form-group"><label class="form-label">Department <span style="color:var(--red)">*</span></label>
          <select name="department" class="form-control" required>
            <option value="">— Select Department —</option>
            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option><?php echo e($d); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addEvaluator')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add &amp; Assign ID</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Evaluator Modal -->
<div class="modal-overlay" id="modal-editEvaluator">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-pen"></i> Edit Evaluator</div>
      <button class="modal-close" onclick="closeModal('editEvaluator')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.evaluators.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="edit_evaluator"/>
      <input type="hidden" name="user_id" id="edit_user_id"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" id="edit_first" class="form-control" required/></div>
          <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" id="edit_last" class="form-control" required/></div>
        </div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Department</label>
          <select name="department" id="edit_dept" class="form-control">
            <option value="">— Select Department —</option>
            <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option><?php echo e($d); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">ID Number</label><input type="text" name="id_number" id="edit_id" class="form-control"/></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editEvaluator')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(ev) {
  document.getElementById('edit_user_id').value = ev.id;
  document.getElementById('edit_first').value   = ev.first_name;
  document.getElementById('edit_last').value    = ev.last_name;
  document.getElementById('edit_email').value   = ev.email;
  document.getElementById('edit_id').value      = ev.id_number;
  const deptSel = document.getElementById('edit_dept');
  for (let i = 0; i < deptSel.options.length; i++) {
    deptSel.options[i].selected = deptSel.options[i].value === ev.department;
  }
  openModal('editEvaluator');
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.ec', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/evaluators.blade.php ENDPATH**/ ?>