@extends('layouts.trainer')

@section('content')
@if($mode === 'detail')
{{-- ═══════════════════════════════════════════════════════ DETAIL VIEW ═══ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i>
      <a href="{{ route('trainer.trainings') }}" style="color:var(--blue-primary)">My Activities</a>
      @if($viewTraining->program)
      <i class="fas fa-chevron-right"></i> <span>{{ $viewTraining->program->title }}</span>
      @endif
      <i class="fas fa-chevron-right"></i> <span>{{ $viewTraining->title }}</span>
    </div>
    <h1>{{ $viewTraining->title }}</h1>
    <p>{{ $viewTraining->area }}</p>
  </div>
  <a href="{{ route('trainer.trainings') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<!-- Display Picture -->
<div class="card" style="margin-bottom:24px;overflow:hidden;padding:0">
  <div style="position:relative;height:220px">
    @if($viewTraining->cover_image)
      <img src="{{ route('files.activity-cover', $viewTraining) }}" alt="{{ $viewTraining->title }}" style="width:100%;height:100%;object-fit:cover;display:block"/>
    @else
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
        <i class="fas {{ \App\Support\TrainingCategoryIcon::icon($viewTraining->area) }}" style="font-size:64px;color:rgba(255,255,255,.85)"></i>
      </div>
    @endif
    @if($canChangeCover)
    <button class="btn btn-sm btn-outline" style="position:absolute;bottom:12px;right:12px;background:rgba(255,255,255,.94)" onclick="openModal('uploadCover')">
      <i class="fas fa-camera"></i> Change Display Picture
    </button>
    @endif
  </div>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-calendar"></i></div><div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->date_start?->format('Y-m-d') ?? '—' }}</div><div class="stat-label">Schedule</div></div></div>
  <div class="stat-card"><div class="stat-icon {{ $viewTraining->status === 'Completed' ? 'green' : ($viewTraining->status === 'Ongoing' ? 'blue' : 'yellow') }}"><i class="fas fa-circle-info"></i></div><div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->status }}</div><div class="stat-label">Status</div></div></div>
  <div class="stat-card"><div class="stat-icon navy"><i class="fas fa-users"></i></div><div class="stat-body"><div class="stat-value">{{ $viewParticipants->count() }}</div><div class="stat-label">Trainees</div></div></div>
</div>

<!-- Documents — moved up front so it's visible without scrolling -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">Documents</div><button type="button" class="btn btn-ghost btn-sm" onclick="openModal('uploadDocActivity')">Upload</button></div>
  <div class="card-body" style="padding-top:8px">
    @forelse($viewDocs as $d)
    <div class="upload-item" style="margin-bottom:8px">
      <div class="upload-item-body"><div class="upload-item-name">{{ $d->original_name }}</div><div class="upload-item-meta">{{ $d->created_at?->format('M d, Y') ?? '—' }}</div></div>
    </div>
    @empty
    <div class="empty-state" style="padding:16px"><i class="fas fa-folder-open"></i><p>No documents uploaded.</p></div>
    @endforelse
  </div>
</div>

<!-- Project Progress -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-chart-line"></i> Project Progress</div><div class="card-subtitle">Budget status</div></div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:{{ $progress['healthHex'] }}22;color:{{ $progress['healthHex'] }};border:1px solid {{ $progress['healthHex'] }}44"><i class="fas fa-circle" style="font-size:6px"></i> {{ $progress['healthLabel'] }}</span>
      <button class="btn btn-sm btn-outline" onclick="openModal('updateBudget')"><i class="fas fa-sack-dollar"></i> Budget Log</button>
    </div>
  </div>
  <div class="card-body">
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--text-heading)"><i class="fas fa-sack-dollar"></i> Budget</span>
        @if($progress['budgetPct'] !== null)<span style="font-size:13px;font-weight:700;color:{{ $progress['budgetPct'] >= 100 ? '#EF4444' : ($progress['budgetPct'] >= 80 ? '#F59E0B' : '#10B981') }}">{{ $progress['budgetPct'] }}%</span>@endif
      </div>
      @if($progress['budgetAlloc'] > 0)
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:{{ $progress['budgetPct'] }}%;background:{{ $progress['budgetPct'] >= 100 ? '#EF4444' : ($progress['budgetPct'] >= 80 ? '#F59E0B' : '#10B981') }}"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Used: <strong>&#8369;{{ number_format($progress['budgetUsed'], 2) }}</strong></span>
        <span>Total: <strong>&#8369;{{ number_format($progress['budgetAlloc'], 2) }}</strong></span>
      </div>
      @else
      <div style="color:var(--gray-400);font-size:13px;padding:8px 0">No budget assigned.</div>
      @endif
    </div>

  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <div class="card">
      <div class="card-header"><div class="card-title">Description</div></div>
      <div class="card-body"><p style="font-size:14px;color:var(--gray-700);line-height:1.8;overflow-wrap:anywhere">{!! $viewTraining->description ? nl2br(e($viewTraining->description)) : '<span style="color:var(--gray-400)">No description.</span>' !!}</p></div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Participants</div><a href="{{ route('trainer.attendance') }}?training={{ $viewTraining->id }}" class="btn btn-sm btn-outline">Manage</a></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>ID Number</th><th>Evaluation</th></tr></thead>
          <tbody>
          @forelse($viewParticipants as $p)
            @php $evalStatus = $p->evaluations->first()->status ?? 'Pending'; @endphp
            <tr>
              <td><strong>{{ $p->full_name }}</strong></td>
              <td style="font-size:12px;color:var(--gray-500)">{{ $p->id_number ?? '—' }}</td>
              <td><span class="badge badge-{{ strtolower($evalStatus) }}">{{ $evalStatus }}</span></td>
            </tr>
          @empty
            <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray-400)">No participants yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="dash-side">
    <div class="card">
      <div class="card-header"><div class="card-title">Details</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        @php
          $details = [
            ['Program', $viewTraining->program->title ?? '—'],
            ['Area', $viewTraining->area],
            ['Start Date', $viewTraining->date_start?->format('Y-m-d') ?? '—'],
            ['End Date', $viewTraining->date_end?->format('Y-m-d') ?? '—'],
            ['Status', $viewTraining->status],
            ['Target', $viewTraining->target_participants . ' pax'],
            ['Budget Allocated', $viewTraining->budget_allocated ? '₱'.number_format((float) $viewTraining->budget_allocated, 2) : '—'],
            ['Budget Used', '₱'.number_format((float) ($viewTraining->budget_used ?? 0), 2)],
          ];
        @endphp
        @foreach($details as [$lb, $vl])
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:4px 12px;padding:8px 0;border-bottom:1px solid var(--gray-100)">
          <span style="font-size:12px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ $lb }}</span>
          <span style="font-size:13px;font-weight:600;color:var(--gray-800);min-width:0;overflow-wrap:anywhere;text-align:right">{{ $vl }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

<!-- MODAL: UPDATE BUDGET -->
@include('partials.budget-log-modal', [
  'storeRoute'   => route('trainer.trainings.store'),
  'viewTraining' => $viewTraining,
  'progress'     => $progress,
  'budgetItems'  => $budgetItems,
])

@if($canChangeCover)
<!-- MODAL: CHANGE DISPLAY PICTURE -->
<div class="modal-overlay" id="modal-uploadCover">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2><i class="fas fa-camera"></i> Change Display Picture</h2>
      <button class="modal-close" onclick="closeModal('uploadCover')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('trainer.trainings.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="action" value="upload_cover"/>
      <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Image *</label>
          <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" required/>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">JPG, PNG, GIF, or WEBP — max 5 MB</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('uploadCover')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="pathriveRequestImageUpload(this.form, { title: 'Upload this image?' })"><i class="fas fa-check"></i> Save Picture</button>
      </div>
    </form>
  </div>
</div>
@endif

<!-- MODAL: UPLOAD DOCUMENT (scoped to this activity) -->
<div class="modal-overlay" id="modal-uploadDocActivity">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-arrow-up"></i> Upload Document</div>
      <button class="modal-close" onclick="closeModal('uploadDocActivity')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Add As *</label>
        <select id="docAddAsActivity" class="form-control" onchange="switchDocAddAsActivity(this.value)">
          <option value="file">Uploaded File</option>
          <option value="link">Link</option>
        </select>
      </div>

      <form method="POST" action="{{ route('trainer.documents.store') }}" enctype="multipart/form-data" id="formFileUploadActivity">
        @csrf
        <input type="hidden" name="action" value="upload"/>
        <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
        <div class="drop-zone" id="dzModalActivity" onclick="document.getElementById('fileInputModalActivity').click()">
          <i class="fas fa-cloud-arrow-up" style="font-size:28px;color:var(--blue-primary);margin-bottom:8px"></i>
          <p style="font-size:14px;font-weight:600;color:var(--gray-700)">Drag &amp; drop a file here</p>
          <p style="font-size:12px;color:var(--gray-400);margin-top:4px">PDF, DOCX, XLSX, JPG, MP4 — max 20 MB</p>
          <input type="file" id="fileInputModalActivity" name="file" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4"/>
          <div id="fileChosenModalActivity" style="margin-top:10px;font-size:13px;color:var(--blue-primary);font-weight:600"></div>
        </div>
        <div class="form-group" style="margin-top:14px">
          <label class="form-label">Visibility</label>
          <select name="visibility" class="form-control">
            <option value="public">Public (All users)</option>
            <option value="ec_trainer">EC &amp; Project Leaders only</option>
            <option value="private">Private</option>
          </select>
        </div>
        <div class="modal-footer" style="padding:16px 0 0;border:none">
          <button type="button" class="btn btn-outline" onclick="closeModal('uploadDocActivity')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-up"></i> Save Document</button>
        </div>
      </form>

      <form method="POST" action="{{ route('trainer.documents.store') }}" id="formLinkUploadActivity" style="display:none">
        @csrf
        <input type="hidden" name="action" value="upload"/>
        <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
        <div class="form-group">
          <label class="form-label">Document Name <span style="color:var(--red)">*</span></label>
          <input type="text" name="link_title" class="form-control" placeholder="e.g. Activity Plan (Google Drive)" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Link Type <span style="color:var(--red)">*</span></label>
          <select name="link_type" class="form-control" required>
            <option value="" disabled selected>— Select type —</option>
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
        <div class="form-group">
          <label class="form-label">Visibility</label>
          <select name="visibility" class="form-control">
            <option value="public">Public (All users)</option>
            <option value="ec_trainer">EC &amp; Project Leaders only</option>
            <option value="private">Private</option>
          </select>
        </div>
        <div class="modal-footer" style="padding:16px 0 0;border:none">
          <button type="button" class="btn btn-outline" onclick="closeModal('uploadDocActivity')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Save Document</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchDocAddAsActivity(kind) {
  const isFile = kind === 'file';
  document.getElementById('formFileUploadActivity').style.display = isFile ? 'block' : 'none';
  document.getElementById('formLinkUploadActivity').style.display = isFile ? 'none' : 'block';
}

(function () {
  const dz = document.getElementById('dzModalActivity');
  const fi = document.getElementById('fileInputModalActivity');
  const fc = document.getElementById('fileChosenModalActivity');
  if (!dz || !fi) return;
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

@else
{{-- ═══════════════════════════════════════════════════════ LIST VIEW ═══ --}}
{{--
  NOTE: the original PHP's list-view branch is missing its opening
  `<div class="page-header"><div class="page-header-left">` markup (a
  copy/paste slip — it jumps straight to `<h1>` then closes two divs that
  were never opened, corrupting the DOM for the rest of the page). Every
  other list page in this app wraps its heading the same way, so that
  wrapper is reconstructed here rather than reproduced broken.
--}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>My Activities</span></div>
    <h1>My Activities</h1>
    <p>All extension activities assigned to you by the Extension Coordinator</p>
  </div>
</div>

<form method="GET" action="{{ route('trainer.trainings') }}" class="filter-row">
  <div class="search-box"><i class="fas fa-magnifying-glass"></i><input type="text" name="q" value="{{ $q }}" placeholder="Search activities…"/></div>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Status</option>
    @foreach(['Proposed','Approved','Ongoing','Completed'] as $s)
    <option {{ $status === $s ? 'selected' : '' }}>{{ $s }}</option>
    @endforeach
  </select>
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
  @if($q || $status)<a href="{{ route('trainer.trainings') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
</form>

@php
  $catColors = [
    'Mechanical Technology' => ['#374151', '#6B7280'], 'Automotive Technology' => ['#B45309', '#D97706'],
    'Computer Technology' => ['#1D4ED8', '#0284C7'], 'Electronics Technology' => ['#0F766E', '#0891B2'],
    'Culinary Technology' => ['#7C3AED', '#A855F7'], 'Apparel and Fashion Technology' => ['#BE185D', '#EC4899'],
    'Print Media Technology' => ['#7C2D12', '#C2410C'], 'Information Technology' => ['#065F46', '#059669'],
  ];
  $statusMap = ['Proposed' => 'Upcoming', 'Approved' => 'Upcoming', 'Ongoing' => 'Ongoing', 'Completed' => 'Completed'];
  $statusClass = ['Upcoming' => 'badge-proposed', 'Ongoing' => 'badge-ongoing', 'Completed' => 'badge-completed'];
@endphp

@if($trainings->isEmpty())
<div class="empty-state"><i class="fas fa-book"></i><p>No activities assigned yet.</p></div>
@else
@foreach($trainingsByProject as $projectTrainings)
  @php $project = $projectTrainings->first()->program; @endphp
  <div class="card-group">
    <div class="card-group-header">
      <div class="card-group-title">
        <i class="fas fa-diagram-project"></i>
        @if($project)
          <a href="{{ route('trainer.programs') }}?view={{ $project->id }}" style="color:inherit">{{ $project->title }}</a>
        @else
          No Project Assigned
        @endif
      </div>
      @if($project)<span class="badge badge-{{ strtolower($project->status) }}">{{ $project->status }}</span>@endif
      <span class="card-group-count">{{ $projectTrainings->count() }} {{ \Illuminate\Support\Str::plural('activity', $projectTrainings->count()) }}</span>
    </div>
    <div class="home-grid home-grid-grouped">
      @foreach($projectTrainings as $t)
        @php
          $hs = $statusMap[$t->status] ?? $t->status;
          $sc = $statusClass[$hs] ?? 'badge-approved';
          $icon = \App\Support\TrainingCategoryIcon::icon($t->area);
          $c = $catColors[$t->area] ?? ['#1A56DB', '#2E6BF0'];
        @endphp
        <div class="training-card">
          <div class="training-card-img" style="background:linear-gradient(135deg,{{ $c[0] }},{{ $c[1] }})">
            @if($t->cover_image)
              <img src="{{ route('files.activity-cover', $t) }}" alt="{{ $t->title }}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;z-index:0"/>
            @endif
            @if(!$t->cover_image)
              <i class="fas {{ $icon }}" style="z-index:1;position:relative;font-size:40px"></i>
            @endif
          </div>
          <div class="training-card-body">
            <div class="training-card-title">{{ $t->title }}</div>
            <div class="training-card-footer" style="margin-top:8px;flex-wrap:wrap;gap:8px">
              <span class="badge {{ $sc }}">{{ $hs }}</span>
              <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap">
                @if($t->isLeadUser(auth('web')->id()))
                <button class="btn btn-outline" style="padding:7px 12px;font-size:13px;line-height:1"
                  onclick="openEditTrainingModal({{ $t->id }}, '{{ addslashes($t->title) }}', '{{ addslashes($t->area) }}', '{{ addslashes($t->description ?? '') }}', '{{ $t->date_start?->format('Y-m-d') ?? '' }}', '{{ $t->date_end?->format('Y-m-d') ?? '' }}', '{{ $t->status }}', {{ (int)$t->target_participants }})">
                  <i class="fas fa-pen"></i>
                </button>
                <button class="btn btn-danger" style="padding:7px 12px;font-size:13px;line-height:1"
                  onclick="openDeleteTrainingModal({{ $t->id }}, '{{ addslashes($t->title) }}')">
                  <i class="fas fa-trash"></i>
                </button>
                @endif
                <a href="{{ route('trainer.trainings') }}?view={{ $t->id }}" class="btn btn-primary" style="padding:7px 14px;font-size:13px;line-height:1">View</a>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endforeach
@endif

<script>
function openEditTrainingModal(id, title, area, description, dateStart, dateEnd, status, targetPax) {
  document.getElementById('editTrainingId').value = id;
  document.getElementById('editTrainingTitle').value = title;
  document.getElementById('editTrainingArea').value = area;
  document.getElementById('editTrainingDesc').value = description;
  document.getElementById('editTrainingDateStart').value = dateStart;
  document.getElementById('editTrainingDateEnd').value = dateEnd;
  document.getElementById('editTrainingTargetPax').value = targetPax;
  openModal('editTraining');
}
function openDeleteTrainingModal(id, title) {
  document.getElementById('deleteTrainingId').value = id;
  document.getElementById('deleteTrainingName').textContent = title;
  openModal('deleteTraining');
}
</script>

<!-- MODAL: EDIT ACTIVITY -->
<div class="modal-overlay" id="modal-editTraining">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-pen"></i> Edit Activity</h2>
      <button class="modal-close" onclick="closeModal('editTraining')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('trainer.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="training_id" id="editTrainingId"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Activity Title *</label>
            <input type="text" name="title" id="editTrainingTitle" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <input type="text" name="area" id="editTrainingArea" class="form-control" maxlength="120" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="editTrainingDesc" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="date_start" id="editTrainingDateStart" class="form-control"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end" id="editTrainingDateEnd" class="form-control"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <div style="padding:9px 12px;border-radius:var(--radius-sm);background:var(--gray-50);font-size:13px;color:var(--gray-500)">
              <i class="fas fa-lock" style="margin-right:4px"></i> Set automatically from dates
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Target Participants</label>
            <input type="number" name="target_participants" id="editTrainingTargetPax" class="form-control" min="1"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: DELETE ACTIVITY -->
<div class="modal-overlay" id="modal-deleteTraining">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2><i class="fas fa-trash"></i> Delete Activity</h2>
      <button class="modal-close" onclick="closeModal('deleteTraining')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('trainer.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="training_id" id="deleteTrainingId"/>
      <div class="modal-body">
        <p style="font-size:14px;color:var(--gray-700)">Are you sure you want to delete <strong id="deleteTrainingName"></strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('deleteTraining')">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Yes, Delete</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection
