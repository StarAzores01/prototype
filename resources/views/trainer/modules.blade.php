@extends('layouts.trainer')

@section('content')
@php
  $typeIcon = ['pdf'=>['fa-file-pdf','pdf'],'doc'=>['fa-file-word','doc'],'docx'=>['fa-file-word','doc'],
               'xls'=>['fa-file-excel','doc'],'xlsx'=>['fa-file-excel','doc'],
               'jpg'=>['fa-image','img'],'jpeg'=>['fa-image','img'],'png'=>['fa-image','img'],'mp4'=>['fa-file-video','doc']];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Learning Modules</span></div>
    <h1>Learning Modules</h1>
    <p>Upload and manage training materials and resources</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-arrow-up"></i>Upload Module</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('trainer.modules.store') }}" enctype="multipart/form-data" id="uploadForm">
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
        <button type="submit" class="btn btn-primary" style="height:40px"><i class="fas fa-arrow-up"></i> Upload</button>
        <button type="button" class="btn btn-outline" style="height:40px" onclick="document.getElementById('fileInput').click()"><i class="fas fa-folder-open"></i> Browse Files</button>
      </div>
    </form>
  </div>
</div>

<!-- Storage Overview -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-floppy-disk"></i></div><div class="stat-body"><div class="stat-value" style="font-size:18px">{{ round($totalSize / 1024 / 1024, 1) }} MB</div><div class="stat-label">Used Storage</div></div></div>
  <div class="stat-card"><div class="stat-icon red"><i class="fas fa-file"></i></div><div class="stat-body"><div class="stat-value">{{ $pdfCount }}</div><div class="stat-label">PDFs Uploaded</div></div></div>
  <div class="stat-card"><div class="stat-icon navy"><i class="fas fa-video"></i></div><div class="stat-body"><div class="stat-value">{{ $vidCount }}</div><div class="stat-label">Videos Uploaded</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-camera"></i></div><div class="stat-body"><div class="stat-value">{{ $imgCount }}</div><div class="stat-label">Images Uploaded</div></div></div>
</div>

<!-- All Modules -->
<div class="card">
  <div class="card-header"><div class="card-title">All Uploaded Modules</div><div class="card-subtitle">{{ $docs->count() }} files</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Date Uploaded</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse($docs as $d)
        @php
          $ext = strtolower($d->file_type ?? '');
          [$fi, $ic] = $typeIcon[$ext] ?? ['fa-file', 'doc'];
        @endphp
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="upload-item-icon {{ $ic }}" style="width:32px;height:32px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px"><i class="fas {{ $fi }}"></i></div>
            <span style="font-size:13px;font-weight:600">{{ $d->original_name }}</span>
          </div>
        </td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ strtoupper($ext) }}</span></td>
        <td style="font-size:12px;color:var(--gray-600)">{{ $d->training->title ?? 'General' }}</td>
        <td style="font-size:12px;color:var(--gray-400)">{{ $d->created_at?->format('M d, Y') ?? '—' }}</td>
        <td>
          <div class="action-btns">
            <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline"><i class="fas fa-arrow-down"></i></a>
            <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
            <button class="btn btn-sm btn-danger" onclick="if(confirm('Delete this module?')){document.getElementById('del{{ $d->id }}').submit()}"><i class="fas fa-trash"></i></button>
            <form id="del{{ $d->id }}" method="POST" action="{{ route('trainer.modules.store') }}" style="display:none">
              @csrf
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="doc_id" value="{{ $d->id }}"/>
            </form>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--gray-400)">No modules uploaded yet.</td></tr>
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
