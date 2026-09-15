
<?php $rows = $rows ?? 3; $fieldValue = $fieldValue ?? null; $extraClass = $extraClass ?? ''; ?>
<div class="form-group">
  <label class="form-label"><?php echo e($label); ?></label>

  <?php if($type === 'image'): ?>
    <?php if($fieldValue): ?>
      <div style="margin-bottom:10px">
        <img src="<?php echo e($fieldValue); ?>" alt="<?php echo e($label); ?>" style="max-width:240px;max-height:140px;border-radius:8px;border:1px solid var(--gray-200);object-fit:cover;display:block"/>
        <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:12px;color:var(--gray-500);cursor:pointer">
          <input type="checkbox" name="sections[<?php echo e($pageKey); ?>][<?php echo e($key); ?>_clear]" value="1"/> Remove this image (revert to default)
        </label>
      </div>
    <?php else: ?>
      <div class="form-hint" style="margin-bottom:8px">No custom image set — showing the default.</div>
    <?php endif; ?>
    <input type="file" name="sections[<?php echo e($pageKey); ?>][<?php echo e($key); ?>]" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp"/>
    <div class="form-hint"><?php echo e($hint ?? 'JPG, PNG, GIF, or WEBP — max 5 MB. Leave empty to keep the current image.'); ?></div>
  <?php else: ?>
    <textarea name="sections[<?php echo e($pageKey); ?>][<?php echo e($key); ?>]" class="form-control <?php echo e($extraClass); ?>" rows="<?php echo e($rows); ?>"
      placeholder="<?php echo e(($fieldValue !== null && $fieldValue !== '') ? '' : 'Leave blank to use the default text'); ?>"><?php echo e($fieldValue !== null ? $fieldValue : ''); ?></textarea>
    <?php if(!empty($hint)): ?>
      <div class="form-hint"><?php echo e($hint); ?></div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/page-content/_field.blade.php ENDPATH**/ ?>