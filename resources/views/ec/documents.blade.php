@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Documents</span></div>
    <h1>Document Repository</h1>
    <p>Store and manage activity materials, reports, and media files</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('uploadDoc')"><i class="fas fa-plus"></i> Add Document</button>
</div>

<!-- Documents Table -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title">All Documents</div><div class="card-subtitle">{{ $docs->count() }} file{{ $docs->count() === 1 ? '' : 's' }}</div></div>
    <form method="GET" action="{{ route('ec.documents') }}" style="display:flex;gap:8px;flex-wrap:wrap">
      <div class="search-box" style="max-width:280px">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" name="q" value="{{ $q }}" placeholder="Search documents…"/>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
      @if($q)<a href="{{ route('ec.documents') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
    </form>
  </div>
  <div class="table-wrap">
    @if($docs->isEmpty())
      <div class="empty-state"><i class="fas fa-folder-open"></i><p>No documents yet. Click <strong>Add Document</strong> to get started.</p></div>
    @else
    @php
      $visColor  = ['private' => 'var(--red)', 'ec_trainer' => 'var(--blue-primary)', 'public' => 'var(--green)'];
      $linkIcon  = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square'];

      // Group documents by date label (Today / Yesterday / date string)
      $today     = now()->startOfDay();
      $yesterday = now()->subDay()->startOfDay();
      $grouped   = $docs->groupBy(function ($d) use ($today, $yesterday) {
        $ts = $d->created_at;
        if (! $ts) return 'Unknown Date';
        if ($ts->startOfDay()->eq($today))     return 'Today';
        if ($ts->startOfDay()->eq($yesterday)) return 'Yesterday';
        return $ts->format('F j, Y');
      });
    @endphp

    @foreach($grouped as $dateLabel => $group)
      <div class="doc-date-group-header">
        <i class="fas fa-calendar-alt" style="margin-right:6px;opacity:.6"></i>{{ $dateLabel }}
        <span style="font-weight:400;margin-left:8px;opacity:.7">({{ $group->count() }} file{{ $group->count() === 1 ? '' : 's' }})</span>
      </div>
      <table class="data-table">
        <thead><tr>
          <th>Document</th>
          <th>Type</th>
          <th class="doc-table-linked-to">Linked To</th>
          <th>Visibility</th>
          <th class="doc-table-uploaded-by">Uploaded By</th>
          <th>Time</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>
        @foreach($group as $d)
          @php $vis = $d->visibility ?? 'public'; @endphp
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px;min-width:0">
                @if($d->isLink())
                  <span style="width:32px;height:32px;border-radius:8px;background:var(--blue-soft);color:var(--blue-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                    <i class="{{ $linkIcon[$d->link_type] ?? 'fa-solid fa-link' }}"></i>
                  </span>
                @else
                  @php
                    $ico = match(strtolower($d->file_type ?? '')) {
                      'pdf'  => ['fa-file-pdf',  '#FEE2E2', '#EF4444'],
                      'doc','docx' => ['fa-file-word', '#DBEAFE', '#1A56DB'],
                      'xls','xlsx' => ['fa-file-excel','#D1FAE5','#10B981'],
                      'jpg','jpeg','png','gif','webp' => ['fa-file-image','#E0E7FF','#6366F1'],
                      'mp4' => ['fa-file-video','#FEF3C7','#F59E0B'],
                      default => ['fa-file','#F1F5F9','#64748B'],
                    };
                  @endphp
                  <span style="width:32px;height:32px;border-radius:8px;background:{{ $ico[1] }};color:{{ $ico[2] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                    <i class="fas {{ $ico[0] }}"></i>
                  </span>
                @endif
                <span style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px" title="{{ $d->original_name }}">{{ $d->original_name }}</span>
              </div>
            </td>
            <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ $d->isLink() ? $d->link_type : $d->file_type }}</span></td>
            <td class="doc-table-linked-to" style="font-size:12px;color:var(--gray-600)">
              @if($d->program)
                <i class="fas fa-diagram-project" style="color:var(--gray-400)"></i> {{ Str::limit($d->program->title, 28) }}
              @elseif($d->activity)
                <i class="fas fa-book" style="color:var(--gray-400)"></i> {{ Str::limit($d->activity->title, 28) }}
              @else
                {{ Str::limit($d->training->title ?? 'General', 28) }}
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
            <td class="doc-table-uploaded-by" style="font-size:12px">{{ $d->uploader?->full_name ?? '—' }}</td>
            <td style="font-size:12px;color:var(--gray-400);white-space:nowrap">{{ $d->created_at?->format('g:i A') ?? '—' }}</td>
            <td>
              <div class="action-btns">
                @if($d->isLink())
                <a href="{{ $d->link_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline" title="Open link"><i class="fas fa-arrow-up-right-from-square"></i></a>
                @else
                <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline" title="Download"><i class="fas fa-arrow-down"></i></a>
                <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline" title="Preview"><i class="fas fa-eye"></i></a>
                @endif
                <button class="btn btn-sm btn-danger" onclick="confirmDelete({{ $d->id }}, '{{ addslashes($d->original_name) }}')" title="Delete"><i class="fas fa-trash"></i></button>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endforeach
    @endif
  </div>
</div>

<!-- Add Document Modal -->
<div class="modal-overlay" id="modal-uploadDoc">
  <div class="modal" style="max-width:580px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-plus"></i> Add Document</div>
      <button class="modal-close" onclick="closeModal('uploadDoc')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      {{-- Tab selector: Upload File or Add Link --}}
      <div style="display:flex;gap:0;margin-bottom:20px;border-radius:var(--radius-sm);overflow:hidden;border:1.5px solid var(--gray-200)">
        <button type="button" id="tabFileBtn" onclick="switchDocTab('file')"
          style="flex:1;padding:9px 16px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:var(--blue-primary);color:#fff;transition:var(--transition)">
          <i class="fas fa-arrow-up"></i> Upload File
        </button>
        <button type="button" id="tabLinkBtn" onclick="switchDocTab('link')"
          style="flex:1;padding:9px 16px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:var(--surface);color:var(--gray-600);transition:var(--transition)">
          <i class="fas fa-link"></i> Add Link
        </button>
      </div>

      {{-- Upload File form --}}
      <form method="POST" action="{{ route('ec.documents.store') }}" enctype="multipart/form-data" id="formFileUpload">
        @csrf
        <input type="hidden" name="action" value="upload"/>
        <input type="hidden" name="training_id" value=""/>

        <div id="panelFile">
          <div class="drop-zone" id="dzModal" onclick="document.getElementById('fileInputModal').click()">
            <i class="fas fa-cloud-arrow-up" style="font-size:28px;color:var(--blue-primary);margin-bottom:8px"></i>
            <p style="font-size:14px;font-weight:600;color:var(--gray-700)">Click or drag &amp; drop a file here</p>
            <p style="font-size:12px;color:var(--gray-400);margin-top:4px">PDF, DOCX, XLSX, JPG, PNG, MP4 — max 20 MB</p>
            <input type="file" id="fileInputModal" name="file" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4"/>
            <div id="fileChosenModal" style="margin-top:10px;font-size:13px;color:var(--blue-primary);font-weight:600"></div>
          </div>
          <div class="form-row" style="margin-top:14px">
            <div class="form-group" style="margin:0">
              <label class="form-label">Link to Activity (optional)</label>
              <select name="training_id" class="form-control">
                <option value="">— General —</option>
                @foreach($trainings as $t)
                <option value="{{ $t->id }}">{{ $t->title }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Visibility</label>
              <select name="visibility" class="form-control">
                <option value="public">Public (All users)</option>
                <option value="ec_trainer">EC &amp; Project Leaders only</option>
                <option value="private">Private (EC only)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer" id="footerFile" style="padding:16px 0 0;border:none">
          <button type="button" class="btn btn-outline" onclick="closeModal('uploadDoc')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-up"></i> Upload</button>
        </div>
      </form>

      {{-- Add Link form --}}
      <form method="POST" action="{{ route('ec.documents.store') }}" id="formLinkUpload" style="display:none">
        @csrf
        <input type="hidden" name="action" value="upload"/>

        <div id="panelLink">
          <div class="form-group">
            <label class="form-label">Document Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="link_title" class="form-control" placeholder="e.g. Activity Plan (Google Drive)" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Link Type <span style="color:var(--red)">*</span></label>
            <select name="link_type" class="form-control" required>
              <option value="">— Select type —</option>
              <option value="gdrive">Google Drive</option>
              <option value="youtube">YouTube</option>
              <option value="external">External / Other URL</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">URL <span style="color:var(--red)">*</span></label>
            <input type="url" name="link_url" class="form-control" placeholder="https://drive.google.com/…" required/>
            <div class="form-hint">Paste the full URL. The system stores the link — it does not import or download the file.</div>
          </div>
          <div class="form-row">
            <div class="form-group" style="margin:0">
              <label class="form-label">Link to Activity (optional)</label>
              <select name="training_id" class="form-control">
                <option value="">— General —</option>
                @foreach($trainings as $t)
                <option value="{{ $t->id }}">{{ $t->title }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Visibility</label>
              <select name="visibility" class="form-control">
                <option value="public">Public (All users)</option>
                <option value="ec_trainer">EC &amp; Project Leaders only</option>
                <option value="private">Private (EC only)</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer" style="padding:16px 0 0;border:none">
          <button type="button" class="btn btn-outline" onclick="closeModal('uploadDoc')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Save Link</button>
        </div>
      </form>
    </div>
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

function switchDocTab(tab) {
  const isFile = tab === 'file';
  document.getElementById('formFileUpload').style.display = isFile ? 'block' : 'none';
  document.getElementById('formLinkUpload').style.display = isFile ? 'none' : 'block';
  document.getElementById('tabFileBtn').style.background = isFile ? 'var(--blue-primary)' : 'var(--surface)';
  document.getElementById('tabFileBtn').style.color      = isFile ? '#fff' : 'var(--gray-600)';
  document.getElementById('tabLinkBtn').style.background = isFile ? 'var(--surface)' : 'var(--blue-primary)';
  document.getElementById('tabLinkBtn').style.color      = isFile ? 'var(--gray-600)' : '#fff';
}

(function () {
  const dz = document.getElementById('dzModal');
  const fi = document.getElementById('fileInputModal');
  const fc = document.getElementById('fileChosenModal');

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
@endsection
