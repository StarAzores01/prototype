@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Documents</span></div>
    <h1>Documents</h1>
    <p>Upload and manage activity materials and resources</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-arrow-up"></i> Upload Document</div></div>
  <div class="card-body">
    @include('partials.document-upload-form', [
      'actionRoute'     => route('trainer.documents.store'),
      'idSuffix'        => 'Dash',
      'trainingOptions' => $myTrainings,
      'submitLabel'     => 'Upload',
    ])
  </div>
</div>

<!-- My Documents -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">My Documents</div><div class="card-subtitle">{{ $myDocs->count() }} files</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Activity</th><th>Visibility</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      @php
        $linkIcon = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square'];
      @endphp
      @forelse($myDocs as $d)
        @php
          $vis = $d->visibility ?? 'public';
          $visColor = ['private' => 'var(--red)', 'ec_trainer' => 'var(--blue-primary)', 'public' => 'var(--green)'];
        @endphp
      <tr>
        <td>
          <span style="font-size:13px;font-weight:600">
            @if($d->isLink())<i class="{{ $linkIcon[$d->link_type] ?? 'fa-solid fa-link' }}" style="color:var(--gray-400);margin-right:4px"></i>@endif
            {{ $d->original_name }}
          </span>
        </td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ $d->isLink() ? $d->link_type : $d->file_type }}</span></td>
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
            @if($d->isLink())
            <a href="{{ $d->link_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline"><i class="fas fa-arrow-up-right-from-square"></i></a>
            @else
            <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline"><i class="fas fa-arrow-down"></i></a>
            <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
            @endif
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
      <thead><tr><th>File Name</th><th>Type</th><th>Activity</th><th>Shared By</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse($sharedDocs as $d)
      <tr>
        <td>
          <span style="font-size:13px;font-weight:600">
            @if($d->isLink())<i class="{{ $linkIcon[$d->link_type] ?? 'fa-solid fa-link' }}" style="color:var(--gray-400);margin-right:4px"></i>@endif
            {{ $d->original_name }}
          </span>
        </td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ $d->isLink() ? $d->link_type : $d->file_type }}</span></td>
        <td style="font-size:12px;color:var(--gray-600)">{{ $d->training->title ?? 'General' }}</td>
        <td style="font-size:12px;color:var(--gray-600)">{{ $d->uploader->full_name ?? '—' }}</td>
        <td style="font-size:12px;color:var(--gray-400)">{{ $d->created_at?->format('M d, Y') ?? '—' }}</td>
        <td>
          <div class="action-btns">
            @if($d->isLink())
            <a href="{{ $d->link_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline"><i class="fas fa-arrow-up-right-from-square"></i></a>
            @else
            <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline"><i class="fas fa-arrow-down"></i></a>
            <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
            @endif
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
@endsection
