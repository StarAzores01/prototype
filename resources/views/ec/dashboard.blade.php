@extends('layouts.ec')

@section('content')
@php
  $statusColors = ['Proposed' => '#F59E0B', 'Approved' => '#1A56DB', 'Ongoing' => '#10B981', 'Completed' => '#6B7280'];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Dashboard</span></div>
    <h1>Dashboard Overview</h1>
    <p>Welcome back, {{ $userFirstName }}! Here's what's happening in CIT extension programs.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addProgram')"><i class="fas fa-plus"></i> Create Project</button>
</div>

<!-- Projects Table -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Projects</div>
      <div class="card-subtitle">All extension programs</div>
    </div>
    <a href="{{ route('ec.programs') }}" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table id="programsTable">
      <thead>
        <tr>
          <th>Project Name</th>
          <th>Area</th>
          <th>Project Leader</th>
          <th>Timeline</th>
          <th>Activities</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($recentPrograms as $p)
        @php $col = $statusColors[$p->status] ?? '#6B7280'; @endphp
        <tr>
          <td style="font-size:12px"><strong>{{ $p->title }}</strong></td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $p->area }}</td>
          <td style="font-size:12px;color:var(--gray-700)">{{ optional($p->lead->first())->full_name ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $p->timeline_start?->format('M d, Y') ?? '—' }}</td>
          <td style="font-size:12px"><strong>{{ $p->activity_count }}</strong></td>
          <td style="font-size:12px"><span style="font-weight:600;color:{{ $col }}">{{ $p->status }}</span></td>
          <td style="font-size:12px"><a href="{{ route('ec.programs') }}?view={{ $p->id }}" class="btn btn-sm btn-outline">View</a></td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-400)">No projects yet. <a href="#" onclick="openModal('addProgram')">Create one.</a></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($recentPrograms->count() > 5)
  <div style="padding:10px 16px;border-top:1px solid var(--gray-100);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--gray-500)">
    <span id="programsInfo"></span>
    <div style="display:flex;gap:6px">
      <button class="btn btn-sm btn-outline" id="programsPrev" onclick="paginate('programs',-1)"><i class="fas fa-chevron-left"></i></button>
      <button class="btn btn-sm btn-outline" id="programsNext" onclick="paginate('programs',1)"><i class="fas fa-chevron-right"></i></button>
    </div>
  </div>
  @endif
</div>

<!-- Activities Table -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Activities</div>
      <div class="card-subtitle">All extension activities</div>
    </div>
    <a href="{{ route('ec.trainings') }}" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table id="activitiesTable">
      <thead>
        <tr>
          <th>Activity Name</th>
          <th>Project</th>
          <th>Area</th>
          <th>Project Leader</th>
          <th>Schedule</th>
          <th>Enrolled</th>
          <th>Target</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($recentTrainings as $t)
        @php $col = $statusColors[$t->status] ?? '#6B7280'; @endphp
        <tr>
          <td style="font-size:12px"><strong style="font-size:12px">{{ $t->title }}</strong></td>
          <td style="font-size:12px;color:var(--gray-700)">{{ $t->program?->title ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $t->area }}</td>
          <td style="font-size:12px;color:var(--gray-700)">{{ $t->trainer?->full_name ?? 'TBA' }}</td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $t->date_start?->format('Y-m-d') ?? '—' }}</td>
          <td style="font-size:12px"><strong style="font-size:12px">{{ $t->enrolled }}</strong></td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $t->target_participants }}</td>
          <td style="font-size:12px"><span style="font-weight:600;color:{{ $col }}">{{ $t->status }}</span></td>
          <td style="font-size:12px"><a href="{{ route('ec.trainings') }}?view={{ $t->id }}" class="btn btn-sm btn-outline">Manage</a></td>
        </tr>
      @empty
        <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--gray-400)">No activities yet. <a href="#" onclick="openModal('addTraining')">Create one.</a></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($recentTrainings->count() > 5)
  <div style="padding:10px 16px;border-top:1px solid var(--gray-100);display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--gray-500)">
    <span id="activitiesInfo"></span>
    <div style="display:flex;gap:6px">
      <button class="btn btn-sm btn-outline" id="activitiesPrev" onclick="paginate('activities',-1)"><i class="fas fa-chevron-left"></i></button>
      <button class="btn btn-sm btn-outline" id="activitiesNext" onclick="paginate('activities',1)"><i class="fas fa-chevron-right"></i></button>
    </div>
  </div>
  @endif
</div>

<script>
const PAGE_SIZE = 5;
const paginationState = {};

function paginate(tableId, dir) {
  const state = paginationState[tableId];
  const newPage = state.page + dir;
  if (newPage < 0 || newPage >= Math.ceil(state.rows.length / PAGE_SIZE)) return;
  state.page = newPage;
  renderPage(tableId);
}

function renderPage(tableId) {
  const state   = paginationState[tableId];
  const start   = state.page * PAGE_SIZE;
  const end     = start + PAGE_SIZE;
  state.rows.forEach((row, i) => { row.style.display = (i >= start && i < end) ? '' : 'none'; });
  const total   = state.rows.length;
  const info    = document.getElementById(tableId + 'Info');
  const prev    = document.getElementById(tableId + 'Prev');
  const next    = document.getElementById(tableId + 'Next');
  if (info) info.textContent = `Showing ${Math.min(start + 1, total)}–${Math.min(end, total)} of ${total}`;
  if (prev) prev.disabled = state.page === 0;
  if (next) next.disabled = end >= total;
}

function initPagination(tableId) {
  const tbody = document.querySelector('#' + tableId + ' tbody');
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll('tr'));
  if (rows.length <= PAGE_SIZE) return;
  paginationState[tableId] = { rows, page: 0 };
  renderPage(tableId);
}

document.addEventListener('DOMContentLoaded', function () {
  initPagination('programsTable');
  initPagination('activitiesTable');
});
</script>

<!-- Bottom panels -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

  <div class="card">
    <div class="card-header">
      <div class="card-title">Latest Uploads</div>
      <a href="{{ route('ec.documents') }}" class="btn btn-ghost btn-sm">See all</a>
    </div>
    <div class="card-body" style="padding-top:12px">
      @forelse($latestDocs as $d)
      <div class="upload-item">
        <div class="upload-item-body">
          <div class="upload-item-name">{{ $d->original_name ?? $d->file_name }}</div>
          <div class="upload-item-meta">{{ $d->training->title ?? 'General' }} · {{ $d->created_at->format('M d, Y') }}</div>
        </div>
      </div>
      @empty
      <div class="empty-state" style="padding:20px"><i class="fas fa-folder-open"></i><p>No documents yet.</p></div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Skills Utilization</div></div>
    <div class="card-body">
      @foreach([
        ['Personal Use', $skills['personal']],
        ['Income-Generating', $skills['income']],
        ['Employment', $skills['employment']],
        ['Community Service', $skills['community']],
        ['Training Application', $skills['application']],
        ['Other', $skills['other']],
      ] as [$label, $pct])
      <div class="skills-row">
        <div class="skills-label">{{ $label }}</div>
        <div style="flex:1"><div class="progress-bar-wrap"><div class="progress-bar" style="width:{{ $pct }}%"></div></div></div>
        <div class="skills-pct">{{ $pct }}%</div>
      </div>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Quick Links</div></div>
    <div class="card-body">
      <div class="quick-links">
        <div class="quick-link-item" onclick="openModal('addTraining')">Create New Activity</div>
        <a href="{{ route('ec.trainings') }}" class="quick-link-item">All Activities</a>
        <a href="{{ route('ec.evaluations') }}" class="quick-link-item">Evaluations</a>
        <a href="{{ route('ec.reports') }}" class="quick-link-item">Generate Reports</a>
        <a href="{{ route('ec.documents') }}" class="quick-link-item">Upload Documents</a>
        <a href="{{ route('ec.participants') }}" class="quick-link-item">Register Participant</a>
      </div>
    </div>
  </div>

</div>

<!-- Homepage Video Montage — site-wide, shown on the public landing page's
     "Course Highlights" section. EC-only (this whole page sits behind
     role:extension_coordinator), unlike the per-program video which was
     removed from the Programs section in favor of this single spot. -->
<div class="card" style="margin-top:20px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-film"></i> Homepage Video Montage</div>
      <div class="card-subtitle">The video shown to visitors on the public homepage's Course Highlights section</div>
    </div>
    <button class="btn btn-sm btn-outline" onclick="openModal('uploadHomepageVideo')">
      <i class="fas fa-film"></i> {{ $homepageVideo?->video_url ? 'Update Video' : 'Add Video Montage' }}
    </button>
  </div>
  <div class="card-body">
    @if($homepageVideo?->video_url)
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--gray-50);border-radius:var(--radius-sm)">
      <span style="background:rgba(56,189,248,.15);color:var(--blue-primary);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;padding:3px 9px;border-radius:20px;flex-shrink:0">Live</span>
      <a href="{{ $homepageVideo->video_url }}" target="_blank" rel="noopener" style="font-size:13px;font-weight:600;color:var(--text-heading);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">
        {{ $homepageVideo->video_title ?: $homepageVideo->video_url }}
      </a>
      <a href="{{ $homepageVideo->video_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline">
        <i class="fas fa-play"></i> Watch
      </a>
    </div>
    @else
    <div class="empty-state" style="padding:20px"><i class="fas fa-film"></i><p>No video montage yet — visitors will see an empty placeholder until you add one.</p></div>
    @endif
  </div>
</div>

<!-- MODAL: CREATE ACTIVITY -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Create New Activity</h2>
      <button class="modal-close" onclick="closeModal('addTraining')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        @include('ec.partials.activity-create-fields', ['trainers' => $trainers, 'programs' => $programs])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Activity</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: CREATE PROJECT (Program) — the dashboard's quick-create shortcut;
     same fields/route as ec/programs.blade.php's own "Create Program" modal,
     see ec.partials.program-create-fields. -->
<div class="modal-overlay" id="modal-addProgram">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Create New Project</h2>
      <button class="modal-close" onclick="closeModal('addProgram')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.programs.store') }}">
      @csrf
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        @include('ec.partials.program-create-fields', ['trainers' => $trainers])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addProgram')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Project</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: ADD / UPDATE HOMEPAGE VIDEO MONTAGE -->
<div class="modal-overlay" id="modal-uploadHomepageVideo">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h2><i class="fas fa-film"></i> {{ $homepageVideo?->video_url ? 'Update' : 'Add' }} Video Montage</h2>
      <button class="modal-close" onclick="closeModal('uploadHomepageVideo')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.dashboard.store') }}">
      @csrf
      <input type="hidden" name="action" value="upload_homepage_video"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> Paste an external video link (YouTube, Google Drive, Vimeo, etc.). The system stores the link — no file is uploaded. This replaces the video every visitor sees on the public homepage.
        </div>
        <div class="form-group">
          <label class="form-label">Video Title (optional)</label>
          <input type="text" name="video_title" class="form-control" placeholder="e.g. CIT Extension Training — 2026 Highlights" value="{{ $homepageVideo?->video_title }}"/>
        </div>
        <div class="form-group">
          <label class="form-label">Video URL <span style="color:var(--red)">*</span></label>
          <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=…" value="{{ $homepageVideo?->video_url }}" required/>
          <div class="form-hint">YouTube, Google Drive, Vimeo, or any public video URL</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('uploadHomepageVideo')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Link</button>
      </div>
    </form>
  </div>
</div>
@endsection
