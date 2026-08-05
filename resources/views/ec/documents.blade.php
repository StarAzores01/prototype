@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Documents</span></div>
    <h1>Document Repository</h1>
    <p>Store and manage training materials, reports, and media files</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-arrow-up"></i>Upload File</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('ec.documents.store') }}" enctype="multipart/form-data" id="uploadForm">
      @csrf
      <input type="hidden" name="action" value="upload"/>
      <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
        <i class="fas fa-arrow-up"></i>
        <p style="font-size:14px;color:var(--gray-600);font-weight:600">Drag &amp; drop files here</p>
        <p style="font-size:12px;color:var(--gray-400);margin-top:4px">PDF, DOCX, XLSX, JPG, MP4 — max 20 MB</p>
        <input type="file" id="fileInput" name="file" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4"/>
        <div id="fileChosen" style="margin-top:12px;font-size:13px;color:var(--blue-primary);font-weight:600"></div>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="flex:1;min-width:200px;margin:0">
          <label class="form-label">Link to Training (optional)</label>
          <select name="training_id" class="form-control">
            <option value="">— General / No Training —</option>
            @foreach($trainings as $t)
            <option value="{{ $t->id }}">{{ $t->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group" style="min-width:180px;margin:0">
          <label class="form-label">Visibility</label>
          <select name="visibility" class="form-control">
            <option value="public"><i class="fas fa-globe"></i> Public (All users)</option>
            <option value="ec_trainer"><i class="fas fa-users"></i> EC &amp; Project Leaders only</option>
            <option value="private"><i class="fas fa-lock"></i> Private (EC only)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:40px"><i class="fas fa-arrow-up"></i> Upload File</button>
        <button type="button" class="btn btn-outline" style="height:40px" onclick="document.getElementById('fileInput').click()"><i class="fas fa-folder-open"></i> Browse Files</button>
      </div>
    </form>
  </div>
</div>

<!-- Documents Table -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title">All Documents</div><div class="card-subtitle">{{ $docs->count() }} files</div></div>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" action="{{ route('ec.documents') }}" class="filter-row">
      <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" name="q" value="{{ $q }}" placeholder="Search documents…"/>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
      @if($q)<a href="{{ route('ec.documents') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Visibility</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      @php
        $visColor = ['private' => 'var(--red)', 'ec_trainer' => 'var(--blue-primary)', 'public' => 'var(--green)'];
      @endphp
      @forelse($docs as $d)
        @php $vis = $d->visibility ?? 'public'; @endphp
        <tr>
          <td><span style="font-size:13px;font-weight:600">{{ $d->original_name }}</span></td>
          <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ strtoupper($d->file_type) }}</span></td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $d->training->title ?? 'General' }}</td>
          <td>
            <form method="POST" action="{{ route('ec.documents.store') }}" style="display:inline">
              @csrf
              <input type="hidden" name="action" value="set_visibility"/>
              <input type="hidden" name="doc_id" value="{{ $d->id }}"/>
              <select name="visibility" class="filter-select" style="font-size:12px;padding:4px 8px;border-radius:6px;color:{{ $visColor[$vis] }}" onchange="this.form.submit()">
                <option value="private" {{ $vis === 'private' ? 'selected' : '' }}>Private (EC only)</option>
                <option value="ec_trainer" {{ $vis === 'ec_trainer' ? 'selected' : '' }}>EC &amp; Project Leaders</option>
                <option value="public" {{ $vis === 'public' ? 'selected' : '' }}>Public (All users)</option>
              </select>
            </form>
          </td>
          <td style="font-size:12px">{{ $d->uploader?->full_name ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $d->created_at?->format('M d, Y') ?? '—' }}</td>
          <td>
            <div class="action-btns">
              <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline"><i class="fas fa-arrow-down"></i></a>
              <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
              <button class="btn btn-sm btn-danger" onclick="confirmDelete({{ $d->id }}, '{{ addslashes($d->original_name) }}')"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">No documents uploaded yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<form method="POST" action="{{ route('ec.documents.store') }}" id="deleteDocForm" style="display:none">
  @csrf
  <input type="hidden" name="action" value="delete"/>
  <input type="hidden" name="doc_id" id="deleteDocId"/>
</form>

<script>
const dz = document.getElementById('dropZone');
const fi = document.getElementById('fileInput');
const fc = document.getElementById('fileChosen');

fi.addEventListener('change', () => { fc.textContent = fi.files[0]?.name || ''; });
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('dragover');
  fi.files = e.dataTransfer.files;
  fc.textContent = fi.files[0]?.name || '';
});

function confirmDelete(id, name) {
  if (confirm('Delete "' + name + '"? This cannot be undone.')) {
    document.getElementById('deleteDocId').value = id;
    document.getElementById('deleteDocForm').submit();
  }
}
</script>
@endsection
