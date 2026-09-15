
<?php
  $scopeField = $scopeField ?? null;
  $submitLabel = $submitLabel ?? 'Upload';
?>
<form method="POST" action="<?php echo e($actionRoute); ?>" enctype="multipart/form-data" id="uploadForm<?php echo e($idSuffix); ?>">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="upload"/>
  <?php if($scopeField): ?>
    <input type="hidden" name="<?php echo e($scopeField['name']); ?>" value="<?php echo e($scopeField['value']); ?>"/>
  <?php endif; ?>

  <div class="form-group" style="margin-bottom:14px">
    <label class="form-label">Add As *</label>
    <select id="uploadMode<?php echo e($idSuffix); ?>" class="form-control" style="max-width:220px" onchange="toggleUploadMode<?php echo e($idSuffix); ?>()">
      <option value="file">Uploaded File</option>
      <option value="link">Link (Drive / YouTube / External)</option>
    </select>
  </div>

  <div id="fileSection<?php echo e($idSuffix); ?>">
    <div class="drop-zone" id="dropZone<?php echo e($idSuffix); ?>" onclick="document.getElementById('fileInput<?php echo e($idSuffix); ?>').click()">
      <i class="fas fa-arrow-up"></i>
      <p style="font-size:14px;color:var(--gray-600);font-weight:600">Drag &amp; drop a file here</p>
      <p style="font-size:12px;color:var(--gray-400);margin-top:4px">PDF, DOCX, XLSX, JPG, MP4 — max 20 MB</p>
      <input type="file" id="fileInput<?php echo e($idSuffix); ?>" name="file" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4"/>
      <div id="fileChosen<?php echo e($idSuffix); ?>" style="margin-top:12px;font-size:13px;color:var(--blue-primary);font-weight:600"></div>
    </div>
  </div>

  <div id="linkSection<?php echo e($idSuffix); ?>" style="display:none">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Link Type *</label>
        <select id="linkType<?php echo e($idSuffix); ?>" name="link_type" class="form-control" disabled>
          <option value="">— Select type —</option>
          <option value="gdrive">Google Drive</option>
          <option value="youtube">YouTube</option>
          <option value="external">External</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Title (optional)</label>
        <input type="text" name="link_title" class="form-control" placeholder="e.g. Activity Plan (Drive)" disabled id="linkTitle<?php echo e($idSuffix); ?>"/>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">URL *</label>
      <input type="url" id="linkUrl<?php echo e($idSuffix); ?>" name="link_url" class="form-control" placeholder="https://…" disabled/>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:16px;align-items:flex-end;flex-wrap:wrap">
    <?php if(! $scopeField): ?>
      <div class="form-group" style="flex:1;min-width:200px;margin:0">
        <label class="form-label">Link to Activity (optional)</label>
        <select name="training_id" class="form-control">
          <option value="">— General —</option>
          <?php $__currentLoopData = $trainingOptions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($t->id); ?>"><?php echo e($t->title); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
    <?php endif; ?>
    <div class="form-group" style="min-width:180px;margin:0">
      <label class="form-label">Visibility</label>
      <select name="visibility" class="form-control">
        <option value="public">Public (All users)</option>
        <option value="ec_trainer">EC &amp; Project Leaders only</option>
        <option value="private">Private</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary" style="height:40px"><i class="fas fa-arrow-up"></i> <?php echo e($submitLabel); ?></button>
  </div>
</form>

<script>
function toggleUploadMode<?php echo e($idSuffix); ?>() {
  const isFile = document.getElementById('uploadMode<?php echo e($idSuffix); ?>').value === 'file';

  document.getElementById('fileSection<?php echo e($idSuffix); ?>').style.display = isFile ? 'block' : 'none';
  document.getElementById('linkSection<?php echo e($idSuffix); ?>').style.display = isFile ? 'none' : 'block';

  document.getElementById('fileInput<?php echo e($idSuffix); ?>').disabled = ! isFile;
  document.getElementById('linkType<?php echo e($idSuffix); ?>').disabled = isFile;
  document.getElementById('linkTitle<?php echo e($idSuffix); ?>').disabled = isFile;
  document.getElementById('linkUrl<?php echo e($idSuffix); ?>').disabled = isFile;
}

(function() {
  const dz = document.getElementById('dropZone<?php echo e($idSuffix); ?>');
  const fi = document.getElementById('fileInput<?php echo e($idSuffix); ?>');
  const fc = document.getElementById('fileChosen<?php echo e($idSuffix); ?>');

  fi.addEventListener('change', () => { fc.textContent = fi.files[0]?.name || ''; });
  dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
  dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
  dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('dragover');
    fi.files = e.dataTransfer.files;
    fc.textContent = fi.files[0]?.name || '';
  });
})();
</script>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/partials/document-upload-form.blade.php ENDPATH**/ ?>