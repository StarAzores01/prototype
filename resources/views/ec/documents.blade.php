@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Documents</span></div>
    <h1>Document Repository</h1>
    <p>Store and manage activity materials, reports, and media files</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-arrow-up"></i>Upload Document</div></div>
  <div class="card-body">
    @include('partials.document-upload-form', [
      'actionRoute'     => route('ec.documents.store'),
      'idSuffix'        => 'Dash',
      'trainingOptions' => $trainings,
      'submitLabel'     => 'Upload',
    ])
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
      <thead><tr><th>File Name</th><th>Type</th><th>Linked To</th><th>Visibility</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      @php
        $visColor = ['private' => 'var(--red)', 'ec_trainer' => 'var(--blue-primary)', 'public' => 'var(--green)'];
        $linkIcon = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square'];
      @endphp
      @forelse($docs as $d)
        @php $vis = $d->visibility ?? 'public'; @endphp
        <tr>
          <td>
            <span style="font-size:13px;font-weight:600">
              @if($d->isLink())<i class="{{ $linkIcon[$d->link_type] ?? 'fa-solid fa-link' }}" style="color:var(--gray-400);margin-right:4px"></i>@endif
              {{ $d->original_name }}
            </span>
          </td>
          <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ $d->isLink() ? $d->link_type : $d->file_type }}</span></td>
          <td style="font-size:12px;color:var(--gray-600)">
            @if($d->program)
              <i class="fas fa-diagram-project" style="color:var(--gray-400)"></i> {{ $d->program->title }}
            @elseif($d->activity)
              <i class="fas fa-book" style="color:var(--gray-400)"></i> {{ $d->activity->title }}
            @else
              {{ $d->training->title ?? 'General' }}
            @endif
          </td>
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
              @if($d->isLink())
              <a href="{{ $d->link_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline"><i class="fas fa-arrow-up-right-from-square"></i></a>
              @else
              <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline"><i class="fas fa-arrow-down"></i></a>
              <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
              @endif
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
function confirmDelete(id, name) {
  if (confirm('Delete "' + name + '"? This cannot be undone.')) {
    document.getElementById('deleteDocId').value = id;
    document.getElementById('deleteDocForm').submit();
  }
}
</script>
@endsection
