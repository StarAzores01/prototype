
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Program Title *</label>
    <input type="text" name="title" class="form-control" placeholder="e.g. Community Livelihood Initiative 2026" required/>
  </div>
  <div class="form-group">
    <label class="form-label">Area / Specialization *</label>
    <input type="text" name="area" class="form-control" placeholder="e.g. Culinary Technology" maxlength="120" required/>
  </div>
</div>
<div class="form-group">
  <label class="form-label">Description (optional)</label>
  <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the program…"></textarea>
</div>
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Timeline Start *</label>
    <input type="date" name="timeline_start" id="programTimelineStart" data-range-end="programTimelineEnd" class="form-control" required/>
  </div>
  <div class="form-group">
    <label class="form-label">Timeline End *</label>
    <input type="date" name="timeline_end" id="programTimelineEnd" class="form-control" required/>
  </div>
</div>
<div class="form-group">
  <label class="form-label">Budget Allocated (₱) *</label>
  <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 200000" min="0" step="0.01" required/>
</div>

<hr style="border:none;border-top:1px solid var(--gray-100);margin:8px 0 16px">
<div class="form-group">
  <label class="form-label">Project Lead * <span style="font-weight:400;color:var(--gray-400)">(exactly one, from Project Leaders)</span></label>
  <select name="lead_id" class="form-control" required>
    <option value="">— Select Project Lead —</option>
    <?php $__currentLoopData = $trainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($tr->id); ?>"><?php echo e($tr->first_name); ?> <?php echo e($tr->last_name); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
<div class="form-group">
  <label class="form-label">Team Members <span style="font-weight:400;color:var(--gray-400)">(optional, up to 3)</span></label>
  <div class="form-row" style="grid-template-columns:1fr 1fr 1fr">
    <?php for($i = 0; $i < 3; $i++): ?>
    <select name="member_ids[]" class="form-control">
      <option value="">— None —</option>
      <?php $__currentLoopData = $trainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($tr->id); ?>"><?php echo e($tr->first_name); ?> <?php echo e($tr->last_name); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php endfor; ?>
  </div>
  <div style="font-size:11px;color:var(--gray-400);margin-top:4px">A team member can't also be the Project Lead, and can't be selected twice.</div>
</div>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/partials/program-create-fields.blade.php ENDPATH**/ ?>