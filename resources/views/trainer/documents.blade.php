@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Documents</span></div>
    <h1>Documents</h1>
    <p>Upload and manage training materials and resources</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-arrow-up"></i> Upload Document</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('trainer.documents.store') }}" enctype="multipart/form-data">
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
            <option value="">— General —</option>
            @foreach($myTrainings as $t)<option value="{{ $t->id }}">{{ $t->title }}</option>@endforeach
          </select>
        </div>
        <div class="form-group" style="min-width:180px;margin:0">
          <label class="form-label">Visibility</label>
          <select name="visibility" class="form-control">
            <option value="public">Public (All users)</option>
            <option value="ec_trainer">EC &amp; Project Leaders only</option>
            <option value="private">Private (me only)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:40px"><i class="fas fa-arrow-up"></i> Upload</button>
      </div>
    </form>
  </div>
</div>

<!-- My Documents -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">My Documents</div><div class="card-subtitle">{{ $myDocs->count() }} files</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Visibility</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse($myDocs as $d)
        @php
          $ext = strtolower($d->file_type ?? '');
          $vis = $d->visibility ?? 'public';
          $visColor = ['private' => 'var(--red)', 'ec_trainer' => 'var(--blue-primary)', 'public' => 'var(--green)'];
        @endphp
      <tr>
        <td><span style="font-size:13px;font-weight:600">{{ $d->original_name }}</span></td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ strtoupper($ext) }}</span></td>
        <td style="font-size:12px;color:var(--gray-600)">{{ $d->training->title ?? 'General' }}</td>
        <td>
          <form method="POST" action="{{ route('trainer.documents.store') }}" style="display:inline">
            @csrf
            <input type="hidden" name="action" value="set_visibility"/>
            <input type="hidden" name="doc_id" value="{{ $d->id }}"/>
            <select name="visibility" class="filter-select" style="font-size:12px;padding:4px 8px;border-radius:6px;color:{{ $visColor[$vis] }};font-weight:600;border-color:{{ $visColor[$vis] }}" onchange="this.form.submit()">
              <option value="private" {{ $vis === 'private' ? 'selected' : '' }}>Private</option>
              <option value="ec_trainer" {{ $vis === 'ec_trainer' ? 'selected' : '' }}>EC &amp; Project Leaders</option>
              <option value="public" {{ $vis === 'public' ? 'selected' : '' }}>Public</option>
            </select>
          </form>
        </td>
        <td style="font-size:12px;color:var(--gray-400)">{{ $d->created_at?->format('M d, Y') ?? '—' }}</td>
        <td>
          <div class="action-btns">
            <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline"><i class="fas fa-arrow-down"></i></a>
            <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
            <button class="btn btn-sm btn-danger" onclick="if(confirm('Delete this document?')){document.getElementById('del{{ $d->id }}').submit()}"><i class="fas fa-trash"></i></button>
            <form id="del{{ $d->id }}" method="POST" action="{{ route('trainer.documents.store') }}" style="display:none">
              @csrf
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="doc_id" value="{{ $d->id }}"/>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400)">No documents uploaded yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Shared Documents -->
<div class="card">
  <div class="card-header"><div class="card-title">Shared Documents</div><div class="card-subtitle">Documents shared by the EC or other trainers</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Shared By</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse($sharedDocs as $d)
        @php $ext = strtolower($d->file_type ?? ''); @endphp
      <tr>
        <td><span style="font-size:13px;font-weight:600">{{ $d->original_name }}</span></td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ strtoupper($ext) }}</span></td>
        <td style="font-size:12px;color:var(--gray-600)">{{ $d->training->title ?? 'General' }}</td>
        <td style="font-size:12px;color:var(--gray-600)">{{ $d->uploader->full_name ?? '—' }}</td>
        <td style="font-size:12px;color:var(--gray-400)">{{ $d->created_at?->format('M d, Y') ?? '—' }}</td>
        <td>
          <div class="action-btns">
            <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline"><i class="fas fa-arrow-down"></i></a>
            <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400)">No shared documents yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
const dz=document.getElementById('dropZone'),fi=document.getElementById('fileInput'),fc=document.getElementById('fileChosen');
fi.addEventListener('change',()=>{fc.textContent=fi.files[0]?.name||'';});
dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('dragover');});
dz.addEventListener('dragleave',()=>dz.classList.remove('dragover'));
dz.addEventListener('drop',e=>{e.preventDefault();dz.classList.remove('dragover');fi.files=e.dataTransfer.files;fc.textContent=fi.files[0]?.name||'';});
</script>
@endsection
