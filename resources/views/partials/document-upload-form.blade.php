{{--
  Shared "upload a Document" form — a file OR a link (Google Drive/YouTube/
  external), never both. Used by the dashboard-level Documents pages (EC and
  Trainer) and by the Program-/Activity-scoped upload modals.

  Required: $actionRoute (form action URL), $idSuffix (unique per instance
  on a page, so element ids never collide).
  Optional: $scopeField — ['name' => 'program_id'|'activity_id', 'value' => $id]
    renders a hidden input instead of the legacy "Link to Activity" dropdown.
    Omit entirely for a dashboard-level "general" upload.
  Optional: $trainingOptions — Collection of trainings for the legacy
    dropdown; ignored when $scopeField is set.
  Optional: $submitLabel (default "Upload").
--}}
@php
  $scopeField = $scopeField ?? null;
  $submitLabel = $submitLabel ?? 'Upload';
@endphp
<form method="POST" action="{{ $actionRoute }}" enctype="multipart/form-data" id="uploadForm{{ $idSuffix }}">
  @csrf
  <input type="hidden" name="action" value="upload"/>
  @if($scopeField)
    <input type="hidden" name="{{ $scopeField['name'] }}" value="{{ $scopeField['value'] }}"/>
  @endif

  <div class="form-group" style="margin-bottom:14px">
    <label class="form-label">Add As *</label>
    <select id="uploadMode{{ $idSuffix }}" class="form-control" style="max-width:220px" onchange="toggleUploadMode{{ $idSuffix }}()">
      <option value="file">Uploaded File</option>
      <option value="link">Link (Drive / YouTube / External)</option>
    </select>
  </div>

  <div id="fileSection{{ $idSuffix }}">
    <div class="drop-zone" id="dropZone{{ $idSuffix }}" onclick="document.getElementById('fileInput{{ $idSuffix }}').click()">
      <i class="fas fa-arrow-up"></i>
      <p style="font-size:14px;color:var(--gray-600);font-weight:600">Drag &amp; drop a file here</p>
      <p style="font-size:12px;color:var(--gray-400);margin-top:4px">PDF, DOCX, XLSX, JPG, MP4 — max 20 MB</p>
      <input type="file" id="fileInput{{ $idSuffix }}" name="file" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4"/>
      <div id="fileChosen{{ $idSuffix }}" style="margin-top:12px;font-size:13px;color:var(--blue-primary);font-weight:600"></div>
    </div>
  </div>

  <div id="linkSection{{ $idSuffix }}" style="display:none">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Link Type *</label>
        <select id="linkType{{ $idSuffix }}" name="link_type" class="form-control" disabled>
          <option value="">— Select type —</option>
          <option value="gdrive">Google Drive</option>
          <option value="youtube">YouTube</option>
          <option value="external">External</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Title (optional)</label>
        <input type="text" name="link_title" class="form-control" placeholder="e.g. Activity Plan (Drive)" disabled id="linkTitle{{ $idSuffix }}"/>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">URL *</label>
      <input type="url" id="linkUrl{{ $idSuffix }}" name="link_url" class="form-control" placeholder="https://…" disabled/>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:16px;align-items:flex-end;flex-wrap:wrap">
    @if(! $scopeField)
      <div class="form-group" style="flex:1;min-width:200px;margin:0">
        <label class="form-label">Link to Activity (optional)</label>
        <select name="training_id" class="form-control">
          <option value="">— General —</option>
          @foreach($trainingOptions ?? [] as $t)
          <option value="{{ $t->id }}">{{ $t->title }}</option>
          @endforeach
        </select>
      </div>
    @endif
    <div class="form-group" style="min-width:180px;margin:0">
      <label class="form-label">Visibility</label>
      <select name="visibility" class="form-control">
        <option value="public">Public (All users)</option>
        <option value="ec_trainer">EC &amp; Project Leaders only</option>
        <option value="private">Private</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary" style="height:40px"><i class="fas fa-arrow-up"></i> {{ $submitLabel }}</button>
  </div>
</form>

<script>
function toggleUploadMode{{ $idSuffix }}() {
  const isFile = document.getElementById('uploadMode{{ $idSuffix }}').value === 'file';

  document.getElementById('fileSection{{ $idSuffix }}').style.display = isFile ? 'block' : 'none';
  document.getElementById('linkSection{{ $idSuffix }}').style.display = isFile ? 'none' : 'block';

  document.getElementById('fileInput{{ $idSuffix }}').disabled = ! isFile;
  document.getElementById('linkType{{ $idSuffix }}').disabled = isFile;
  document.getElementById('linkTitle{{ $idSuffix }}').disabled = isFile;
  document.getElementById('linkUrl{{ $idSuffix }}').disabled = isFile;
}

(function() {
  const dz = document.getElementById('dropZone{{ $idSuffix }}');
  const fi = document.getElementById('fileInput{{ $idSuffix }}');
  const fc = document.getElementById('fileChosen{{ $idSuffix }}');

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
