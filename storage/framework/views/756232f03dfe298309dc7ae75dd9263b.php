
<?php $scopedProgram = $scopedProgram ?? null; ?>
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Activity Title *</label>
    <input type="text" name="title" class="form-control" placeholder="e.g. Basic Pastry Making" required/>
  </div>
  <div class="form-group">
    <label class="form-label">Area / Specification *</label>
    <input type="text" name="area" class="form-control" placeholder="e.g. Culinary Technology" maxlength="120" required/>
  </div>
</div>
<div class="form-group">
  <label class="form-label">Description (optional)</label>
  <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the activity…"></textarea>
</div>

<?php if($scopedProgram): ?>
  <input type="hidden" name="program_id" value="<?php echo e($scopedProgram->id); ?>"/>
  <div class="form-group">
    <label class="form-label">Program</label>
    <div style="font-size:13px;color:var(--gray-700);background:var(--gray-50);border-radius:8px;padding:9px 12px">
      <i class="fas fa-diagram-project" style="color:var(--gray-400)"></i> <?php echo e($scopedProgram->title); ?>

    </div>
  </div>
  <?php $scopedLead = $scopedProgram->lead->first(); $scopedMembers = $scopedProgram->members; ?>
  <div class="form-group">
    <label class="form-label">Project Leader &amp; Team <span style="font-weight:400;color:var(--gray-400)">(inherited from this program)</span></label>
    <div style="font-size:13px;color:var(--gray-700);background:var(--gray-50);border-radius:8px;padding:9px 12px">
      <i class="fas fa-user-tie" style="color:var(--gray-400)"></i>
      <?php echo e($scopedLead?->full_name ?? 'No Project Leader assigned yet'); ?>

      <?php if($scopedMembers->isNotEmpty()): ?>
        &middot; <?php echo e($scopedMembers->pluck('full_name')->implode(', ')); ?>

      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="form-group">
    <label class="form-label">Program *</label>
    <select name="program_id" class="form-control" required>
      <option value="">— Select Program —</option>
      <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($prog->id); ?>"><?php echo e($prog->title); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <div class="form-hint"><i class="fas fa-circle-info"></i> The activity's Project Leader and Team Members are inherited from whoever is assigned to this program — they're not set per activity.</div>
  </div>
<?php endif; ?>

<div class="form-row">
  <div class="form-group">
    <label class="form-label">Start Date</label>
    <input type="date" name="date_start" id="activityDateStart" data-range-end="activityDateEnd" class="form-control"/>
  </div>
  <div class="form-group">
    <label class="form-label">End Date</label>
    <input type="date" name="date_end" id="activityDateEnd" class="form-control"/>
  </div>
</div>
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Budget Allocated (₱)</label>
    <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 50000" min="0" step="0.01"/>
  </div>
  <div class="form-group">
    <label class="form-label">Budget Used (₱)</label>
    <input type="number" name="budget_used" class="form-control" placeholder="e.g. 0" min="0" step="0.01" value="0"/>
  </div>
</div>
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Status</label>
    <select name="status" class="form-control">
      <option>Proposed</option><option>Approved</option><option>Ongoing</option><option>Completed</option>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label">No. of Participants (target)</label>
    <input type="number" name="target_participants" class="form-control" placeholder="e.g. 30" min="1"/>
  </div>
</div>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/partials/activity-create-fields.blade.php ENDPATH**/ ?>