@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Documents</span></div>
    <h1>{{ $archived ? 'Archived Documents' : 'Document Repository' }}</h1>
    <p>{{ $archived ? 'Documents archived out of the active list — restore or delete from here.' : 'Store and manage activity materials, reports, and media files' }}</p>
  </div>
  @unless($archived)
  <button class="btn btn-primary" onclick="openModal('uploadDoc')"><i class="fas fa-plus"></i> Add Document</button>
  @endunless
</div>

<!-- Documents, grouped by Parent Program -->
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">{{ $archived ? 'Archived Documents' : 'All Documents' }}</div>
      <div class="card-subtitle">{{ $totalCount }} file{{ $totalCount === 1 ? '' : 's' }}</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <form method="GET" action="{{ route('ec.documents') }}" style="display:flex;gap:8px;flex-wrap:wrap">
        @if($archived)<input type="hidden" name="archived" value="1"/>@endif
        <div class="search-box" style="max-width:280px">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" name="q" value="{{ $q }}" placeholder="Search documents…"/>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
        @if($q)<a href="{{ route('ec.documents', array_filter(['archived' => $archived ? 1 : null])) }}" class="btn btn-ghost btn-sm">Clear</a>@endif
      </form>
      <a href="{{ route('ec.documents', array_filter(['q' => $q, 'archived' => $archived ? null : 1])) }}" class="btn btn-outline btn-sm">
        @if($archived)<i class="fas fa-arrow-left"></i> Back to Active @else <i class="fas fa-box-archive"></i> Archived Documents @endif
      </a>
    </div>
  </div>
  <div class="table-wrap">
    @if($programGroups->isEmpty() && $general->isEmpty())
      <div class="empty-state">
        <i class="fas fa-folder-open"></i>
        <p>
          @if($archived)
            No archived documents.
          @else
            No documents yet. Click <strong>Add Document</strong> to get started.
          @endif
        </p>
      </div>
    @else
      @foreach($programGroups as $group)
        <div class="doc-date-group-header">
          <i class="fas fa-diagram-project" style="margin-right:6px;opacity:.6"></i>{{ $group['program']->title }}
          <span style="font-weight:400;margin-left:8px;opacity:.7">({{ $group['documents']->count() + $group['activities']->sum(fn($a) => $a['documents']->count()) }} file{{ ($group['documents']->count() + $group['activities']->sum(fn($a) => $a['documents']->count())) === 1 ? '' : 's' }})</span>
        </div>
        @if($group['documents']->isNotEmpty())
          @include('partials.document-rows-table', ['documents' => $group['documents'], 'storeRoute' => route('ec.documents.store'), 'archived' => $archived])
        @endif
        @foreach($group['activities'] as $entry)
          <div class="doc-date-group-header" style="padding-left:28px;font-size:12px;background:var(--gray-25, var(--surface))">
            <i class="fas fa-book" style="margin-right:6px;opacity:.6"></i>{{ $entry['activity']->title ?? 'Activity' }}
            <span style="font-weight:400;margin-left:8px;opacity:.7">({{ $entry['documents']->count() }})</span>
          </div>
          @include('partials.document-rows-table', ['documents' => $entry['documents'], 'storeRoute' => route('ec.documents.store'), 'archived' => $archived])
        @endforeach
      @endforeach

      @if($general->isNotEmpty())
        <div class="doc-date-group-header">
          <i class="fas fa-inbox" style="margin-right:6px;opacity:.6"></i>General
          <span style="font-weight:400;margin-left:8px;opacity:.7">({{ $general->count() }})</span>
        </div>
        @include('partials.document-rows-table', ['documents' => $general, 'storeRoute' => route('ec.documents.store'), 'archived' => $archived, 'showLinkedTo' => true])
      @endif
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

<form method="POST" action="{{ route('ec.documents.store') }}" id="docActionForm" style="display:none">
  @csrf
  <input type="hidden" name="action" id="docActionType"/>
  <input type="hidden" name="doc_id" id="docActionId"/>
</form>

<script>
/** Shared by every row rendered via partials.document-rows-table (delete/archive/unarchive). */
function submitDocAction(action, id, name, needsConfirm) {
  if (needsConfirm && !confirm('Delete "' + name + '"? This cannot be undone.')) return;
  document.getElementById('docActionType').value = action;
  document.getElementById('docActionId').value = id;
  document.getElementById('docActionForm').submit();
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
