<?php $__env->startSection('content'); ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Project Leaders</span></div>
    <h1>Project Leaders</h1>
    <p>Manage approved Project Leaders and their system access</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addWhitelist')"><i class="fas fa-plus"></i> Add Project Leader</button>
</div>

<!-- APPROVED LIST (Whitelist) -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-circle" style="font-size:6px"></i> Approved Project Leaders List</div>
      <div class="card-subtitle">Only these Project Leaders can register an account in the system</div>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Assigned ID</th><th>Specialization</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $__currentLoopData = $whitelist; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td><strong><?php echo e($w->first_name); ?> <?php echo e($w->last_name); ?></strong></td>
        <td>
          <?php if($w->id_number): ?>
          <span style="font-size:12px;font-weight:700;color:var(--blue-primary);background:var(--blue-soft);padding:3px 8px;border-radius:6px"><?php echo e($w->id_number); ?></span>
          <?php else: ?>
          <span style="font-size:12px;color:var(--gray-400)">—</span>
          <?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--gray-600)"><?php echo e($w->specialization); ?></td>
        <td>
          <?php if($w->is_registered): ?>
            <span class="badge badge-active">Registered</span>
          <?php else: ?>
            <span class="badge badge-pending">Not yet registered</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if(!$w->is_registered): ?>
          <form method="POST" action="<?php echo e(route('ec.trainers.store')); ?>" style="display:inline" onsubmit="return confirm('Remove <?php echo e(addslashes($w->first_name)); ?> <?php echo e(addslashes($w->last_name)); ?> from the approved list?')">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="remove_whitelist"/>
            <input type="hidden" name="whitelist_id" value="<?php echo e($w->id); ?>"/>
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Remove</button>
          </form>
          <?php else: ?>
            <span style="font-size:12px;color:var(--gray-400)">Account exists</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php if($whitelist->isEmpty()): ?>
      <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--gray-400)">No approved Project Leaders yet. Click "Add Project Leader" to add one.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- REGISTERED PROJECT LEADER ACCOUNTS -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title">Registered Project Leader Accounts</div><div class="card-subtitle"><?php echo e($trainers->count()); ?> accounts</div></div>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" action="<?php echo e(route('ec.trainers')); ?>" class="filter-row">
      <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search by name or email…"/>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
      <?php if($q): ?><a href="<?php echo e(route('ec.trainers')); ?>" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Project Leader ID</th><th>Email</th><th>Position</th><th>Activities</th><th>Date Created</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $colors = ['#1A56DB','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4']; ?>
      <?php $__empty_1 = true; $__currentLoopData = $trainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $trInitials = strtoupper(mb_substr($tr->first_name, 0, 1) . mb_substr($tr->last_name, 0, 1));
          $color = $colors[$tr->id % count($colors)];
          $trId = $trainerIds[$tr->id] ?? '—';
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div class="participant-avatar" style="background:<?php echo e($color); ?>"><?php echo e($trInitials); ?></div>
              <div>
                <div style="font-weight:600;font-size:13px"><?php echo e($tr->first_name); ?> <?php echo e($tr->last_name); ?></div>
                <div style="font-size:11px;color:var(--gray-400)">Project Leader</div>
              </div>
            </div>
          </td>
          <td>
            <span style="font-size:12px;font-weight:700;color:var(--blue-primary);background:var(--blue-soft);padding:3px 8px;border-radius:6px"><?php echo e($trId); ?></span>
          </td>
          <td style="font-size:12px"><?php echo e($tr->email ?? '—'); ?></td>
          <td style="font-size:12px;color:var(--gray-600)"><?php echo e($tr->position ?? '—'); ?></td>
          <td><strong><?php echo e($tr->training_count); ?></strong></td>
          <td style="font-size:12px;color:var(--gray-400)"><?php echo e($tr->created_at?->format('M d, Y') ?? '—'); ?></td>
          <td><span class="badge <?php echo e($tr->is_active ? 'badge-active' : 'badge-inactive'); ?>"><?php echo e($tr->is_active ? 'Active' : 'Inactive'); ?></span></td>
          <td>
            <div class="action-btns">
              <button class="btn btn-sm btn-outline" onclick="openEditModal(<?php echo e($tr->toJson()); ?>)"><i class="fas fa-pen"></i> Edit</button>
              <form method="POST" action="<?php echo e(route('ec.trainers.store')); ?>" style="display:inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="toggle"/>
                <input type="hidden" name="user_id" value="<?php echo e($tr->id); ?>"/>
                <button type="submit" class="btn btn-sm <?php echo e($tr->is_active ? 'btn-danger' : 'btn-outline'); ?>"
                        onclick="return confirm('<?php echo e($tr->is_active ? 'Disable' : 'Enable'); ?> access for <?php echo e(addslashes($tr->first_name)); ?>?')">
                  <i class="fas <?php echo e($tr->is_active ? 'fa-ban' : 'fa-check-circle'); ?>"></i>
                  <?php echo e($tr->is_active ? 'Disable' : 'Enable'); ?>

                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">No registered Project Leader accounts yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL: ADD TO WHITELIST -->
<div class="modal-overlay" id="modal-addWhitelist">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i>Add Approved Project Leader</h2>
      <button class="modal-close" onclick="closeModal('addWhitelist')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.trainers.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="add_whitelist"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px">
          <i class="fas fa-circle-info"></i> Adding a Project Leader here allows them to create an account. They will register themselves using their name.
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-control" placeholder="e.g. Juan" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-control" placeholder="e.g. Dela Cruz" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Specialization *</label>
          <select name="specialization" class="form-control" required>
            <option value="">— Select Area —</option>
            <?php $__currentLoopData = $areas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($a); ?>"><?php echo e($a); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addWhitelist')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Add to Approved Project Leaders List</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: EDIT PROJECT LEADER -->
<div class="modal-overlay" id="modal-editTrainer">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-pen"></i> Edit Project Leader</h2>
      <button class="modal-close" onclick="closeModal('editTrainer')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.trainers.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="edit_trainer"/>
      <input type="hidden" name="user_id" id="edit_user_id"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" id="edit_first_name" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" id="edit_last_name" class="form-control" required/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="edit_email" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">ID Number</label>
            <input type="text" name="id_number" id="edit_id_number" class="form-control"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Position</label>
          <select name="position" id="edit_position" class="form-control">
            <option value="Professor">Professor</option>
            <option value="Assistant Professor">Assistant Professor</option>
            <option value="Instructor">Instructor</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editTrainer')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(tr) {
  document.getElementById('edit_user_id').value    = tr.id;
  document.getElementById('edit_first_name').value = tr.first_name;
  document.getElementById('edit_last_name').value  = tr.last_name;
  document.getElementById('edit_email').value      = tr.email || '';
  document.getElementById('edit_id_number').value  = tr.id_number || '';
  document.getElementById('edit_position').value   = tr.position || 'Instructor';
  openModal('editTrainer');
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.ec', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/trainers.blade.php ENDPATH**/ ?>