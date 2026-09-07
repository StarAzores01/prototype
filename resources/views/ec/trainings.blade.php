@extends('layouts.ec')

@section('content')
@if($mode === 'detail')
{{-- ═══════════════════════════════════════════════════════ DETAIL VIEW ═══ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i>
      <a href="{{ route('ec.trainings') }}" style="color:var(--blue-primary)">Activities</a>
      <i class="fas fa-chevron-right"></i> <span>{{ $viewTraining->title }}</span>
    </div>
    <h1>{{ $viewTraining->title }}</h1>
    <p>{{ $viewTraining->area }}</p>
  </div>
  <div style="display:flex;gap:10px">
    <a href="{{ route('ec.trainings') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <button class="btn btn-primary" onclick="openModal('editTraining')"><i class="fas fa-pen"></i> Update Activity</button>
  </div>
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
    <button class="btn btn-sm btn-outline" style="position:absolute;bottom:12px;right:12px;background:rgba(255,255,255,.94)" onclick="openModal('uploadCover')">
      <i class="fas fa-camera"></i> Change Display Picture
    </button>
  </div>
</div>

<!-- Info Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-calendar"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:16px">{{ $viewTraining->date_start?->format('Y-m-d') ?? '—' }}</div><div class="stat-label">Schedule Date</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon {{ $viewTraining->status === 'Completed' ? 'green' : ($viewTraining->status === 'Ongoing' ? 'blue' : 'yellow') }}"><i class="fas fa-circle-info"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:16px">{{ $viewTraining->status }}</div><div class="stat-label">Status</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-users"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $viewParticipants->count() }}</div><div class="stat-label">Enrolled / {{ (int) $viewTraining->target_participants }} target</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-user"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->trainer->full_name ?? 'TBA' }}</div><div class="stat-label">Project Leader</div></div>
  </div>
</div>

<!-- Project Progress Card -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-chart-line"></i> Project Progress</div>
      <div class="card-subtitle">Budget utilization and timeline status</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:{{ $progress['healthHex'] }}22;color:{{ $progress['healthHex'] }};border:1px solid {{ $progress['healthHex'] }}44">
        <i class="fas fa-circle" style="font-size:6px"></i> {{ $progress['healthLabel'] }}
      </span>
      <button class="btn btn-sm btn-outline" onclick="openModal('updateBudget')">Log Budget Usage</button>
    </div>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:28px">

    <!-- Budget Progress -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--text-heading)"><i class="fas fa-sack-dollar"></i> Budget Utilization</span>
        @if($progress['budgetPct'] !== null)
        <span style="font-size:13px;font-weight:700;color:{{ $progress['budgetPct'] >= 100 ? '#EF4444' : ($progress['budgetPct'] >= 80 ? '#F59E0B' : '#10B981') }}">{{ $progress['budgetPct'] }}%</span>
        @endif
      </div>
      @if($progress['budgetAlloc'] > 0)
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:{{ $progress['budgetPct'] }}%;background:{{ $progress['budgetPct'] >= 100 ? '#EF4444' : ($progress['budgetPct'] >= 80 ? '#F59E0B' : '#10B981') }};transition:width .4s"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Used: <strong style="color:var(--gray-800)">&#8369;{{ number_format($progress['budgetUsed'], 2) }}</strong></span>
        <span>Allocated: <strong style="color:var(--gray-800)">&#8369;{{ number_format($progress['budgetAlloc'], 2) }}</strong></span>
      </div>
      @if($progress['budgetRemain'] !== null)
      <div style="margin-top:8px;font-size:12px;color:{{ $progress['budgetRemain'] < 0 ? '#EF4444' : 'var(--gray-500)' }}">
        @if($progress['budgetRemain'] >= 0)
          Remaining: <strong>&#8369;{{ number_format($progress['budgetRemain'], 2) }}</strong>
        @else
          <strong>Over by &#8369;{{ number_format(abs($progress['budgetRemain']), 2) }}</strong>
        @endif
      </div>
      @endif
      @else
      <div style="color:var(--gray-400);font-size:13px;padding:12px 0">No budget assigned yet.</div>
      @endif
    </div>

    <!-- Time Progress -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--text-heading)"><i class="fas fa-clock"></i> Timeline Progress</span>
        @if($progress['timePct'] !== null)
        <span style="font-size:13px;font-weight:700;color:{{ $progress['timePct'] >= 100 ? 'var(--gray-500)' : ($progress['timePct'] >= 80 ? '#F59E0B' : 'var(--blue-primary)') }}">{{ $progress['timePct'] }}%</span>
        @endif
      </div>
      @if($progress['dateStart'] && $progress['dateEnd'])
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:{{ $progress['timePct'] }}%;background:{{ $progress['timePct'] >= 100 ? '#6B7280' : ($progress['timePct'] >= 80 ? '#F59E0B' : '#1A56DB') }};transition:width .4s"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Start: <strong style="color:var(--gray-800)">{{ $progress['dateStart']->format('M d, Y') }}</strong></span>
        <span>End: <strong style="color:var(--gray-800)">{{ $progress['dateEnd']->format('M d, Y') }}</strong></span>
      </div>
      <div style="margin-top:8px;font-size:12px;color:var(--gray-500)">
        @if($progress['timePct'] >= 100)
          <span style="color:var(--gray-500)"><i class="fas fa-check"></i> Period completed ({{ $progress['totalDays'] }} days)</span>
        @elseif($progress['daysLeft'] !== null)
          <span style="color:var(--blue-primary)"><strong>{{ $progress['daysLeft'] }}</strong> day{{ $progress['daysLeft'] !== 1 ? 's' : '' }} remaining of {{ $progress['totalDays'] }} total</span>
        @endif
      </div>
      @else
      <div style="color:var(--gray-400);font-size:13px;padding:12px 0">No period assigned yet.</div>
      @endif
    </div>

  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <!-- Description -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-circle" style="font-size:6px"></i> Description</div></div>
      <div class="card-body">
        @if($viewTraining->description)
          <p style="font-size:14px;color:var(--gray-700);line-height:1.8">{!! nl2br(e($viewTraining->description)) !!}</p>
        @else
          <p style="color:var(--gray-400);font-size:13px">No description provided.</p>
        @endif
      </div>
    </div>

    <!-- Participants -->
    <div class="card">
      <div class="card-header">
        <div><div class="card-title"><i class="fas fa-users"></i>Participants</div></div>
        <a href="{{ route('ec.participants') }}?training={{ $viewTraining->id }}" class="btn btn-sm btn-outline">Manage</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>ID Number</th><th>Evaluation</th></tr></thead>
          <tbody>
          @forelse($viewParticipants as $p)
            @php $evalStatus = $p->evaluations->first()->status ?? 'Pending'; @endphp
            <tr>
              <td style="color:var(--gray-400)">{{ $loop->iteration }}</td>
              <td><strong>{{ $p->full_name }}</strong></td>
              <td style="font-size:12px;color:var(--gray-500)">{{ $p->id_number ?? '—' }}</td>
              <td><span class="badge badge-{{ strtolower($evalStatus) }}">{{ $evalStatus }}</span></td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--gray-400)">No participants enrolled yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="dash-side">
    <!-- Activity Details -->
    <div class="card">
      <div class="card-header"><div class="card-title">Activity Details</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        @php
          $programVal = $viewTraining->program
            ? '<a href="'.route('ec.programs').'?view='.$viewTraining->program_id.'" style="color:var(--blue-primary)">'.e($viewTraining->program->title).'</a>'
            : '— Not yet assigned —';
          $details = [
            ['Program', $programVal],
            ['Area / Specification', $viewTraining->area ?? '—'],
            ['Start Date', $viewTraining->date_start?->format('Y-m-d') ?? '—'],
            ['End Date', $viewTraining->date_end?->format('Y-m-d') ?? '—'],
            ['Status', $viewTraining->status ?? '—'],
            ['Project Leader', $viewTraining->trainer->full_name ?? 'TBA'],
            ['Team Members', $viewTraining->members->pluck('full_name')->implode(', ') ?: '—'],
            ['Target Participants', $viewTraining->target_participants ?? '—'],
            ['Budget Allocated', $viewTraining->budget_allocated ? '₱'.number_format((float) $viewTraining->budget_allocated, 2) : '—'],
            ['Budget Used', $viewTraining->budget_used ? '₱'.number_format((float) $viewTraining->budget_used, 2) : '₱0.00'],
            ['Created', $viewTraining->created_at?->format('M d, Y') ?? '—'],
          ];
        @endphp
        @foreach($details as [$label, $val])
        <div style="display:flex;align-items:flex-start;gap:12px">
          <div><div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ $label }}</div><div style="font-size:13px;color:var(--gray-800);font-weight:500;margin-top:2px">{!! $label === 'Program' ? $val : e($val) !!}</div></div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Documents: this Activity's own repository, scoped via activity_id -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Documents</div>
        <div style="display:flex;gap:6px">
          <button class="btn btn-ghost btn-sm" onclick="openModal('uploadActivityDoc')">
            <i class="fas fa-upload"></i> Upload
          </button>
          <a href="{{ route('ec.documents') }}" class="btn btn-ghost btn-sm">All Docs</a>
        </div>
      </div>
      <div class="card-body" style="padding-top:8px">
        @php $linkIcon = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square']; @endphp
        @forelse($viewDocs as $d)
        <a href="{{ $d->isLink() ? $d->link_url : route('files.document', $d) }}" target="_blank" rel="noopener" class="upload-item" style="margin-bottom:8px;text-decoration:none;color:inherit">
          <div class="upload-item-icon {{ $d->isLink() ? 'img' : 'doc' }}">
            <i class="{{ $d->isLink() ? ($linkIcon[$d->link_type] ?? 'fa-solid fa-link') : 'fa-solid fa-file' }}"></i>
          </div>
          <div class="upload-item-body">
            <div class="upload-item-name">{{ $d->original_name }}</div>
            <div class="upload-item-meta">{{ $d->uploader->full_name ?? '' }} &middot; {{ $d->created_at?->format('M d, Y') }}</div>
          </div>
        </a>
        @empty
        <div class="empty-state" style="padding:16px"><i class="fas fa-folder-open"></i><p>No documents attached.</p></div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<!-- MODAL: UPDATE BUDGET -->
<div class="modal-overlay" id="modal-updateBudget">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2><i class="fas fa-sack-dollar"></i> Log Budget Usage</h2>
      <button class="modal-close" onclick="closeModal('updateBudget')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="update_budget"/>
      <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> Log how much of this activity's budget has been used so far.
        </div>
        <div class="form-group">
          <label class="form-label">Budget Allocated (₱)</label>
          <div style="font-size:13px;color:var(--gray-700);background:var(--gray-50);border-radius:8px;padding:9px 12px">
            {{ $viewTraining->budget_allocated ? '₱'.number_format((float) $viewTraining->budget_allocated, 2) : 'Not set' }}
          </div>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px"><i class="fas fa-lock"></i> Fixed at creation — cannot be changed</div>
        </div>
        <div class="form-group">
          <label class="form-label">Budget Used (₱)</label>
          <input type="number" name="budget_used" class="form-control"
            value="{{ $viewTraining->budget_used ?? 0 }}"
            min="0" step="0.01"/>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Amount spent so far</div>
        </div>
        @if($progress['budgetAlloc'] > 0)
        <div style="background:var(--gray-50);border-radius:10px;padding:12px 14px;font-size:13px;color:var(--gray-600)">
          Current: <strong style="color:var(--gray-800)">&#8369;{{ number_format($progress['budgetUsed'], 2) }}</strong>
          used of <strong style="color:var(--gray-800)">&#8369;{{ number_format($progress['budgetAlloc'], 2) }}</strong>
          allocated
          <span style="margin-left:8px;font-weight:700;color:{{ $progress['budgetPct'] >= 100 ? '#EF4444' : ($progress['budgetPct'] >= 80 ? '#F59E0B' : '#10B981') }}">({{ $progress['budgetPct'] }}%)</span>
        </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('updateBudget')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Budget</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: UPDATE ACTIVITY -->
<div class="modal-overlay" id="modal-editTraining">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-pen"></i>Update Activity</h2>
      <button class="modal-close" onclick="closeModal('editTraining')"><i class="fas fa-xmark"></i></button>
    </div>
    @php
      $currentLeadId = optional($viewTraining->lead->first())->id;
      $currentMembers = $viewTraining->members->values();
    @endphp
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Activity Title *</label>
            <input type="text" name="title" class="form-control" value="{{ $viewTraining->title }}" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specification *</label>
            <input type="text" name="area" class="form-control" value="{{ $viewTraining->area }}" placeholder="e.g. Culinary Technology" maxlength="120" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3">{{ $viewTraining->description }}</textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Program</label>
          <select name="program_id" class="form-control">
            <option value="">— None —</option>
            @foreach($programs as $prog)
            <option value="{{ $prog->id }}" {{ $viewTraining->program_id == $prog->id ? 'selected' : '' }}>{{ $prog->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="date_start" class="form-control" value="{{ $viewTraining->date_start?->format('Y-m-d') }}"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end" class="form-control" value="{{ $viewTraining->date_end?->format('Y-m-d') }}"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Budget Allocated (₱)</label>
          <div style="font-size:13px;color:var(--gray-700);background:var(--gray-50);border-radius:8px;padding:9px 12px">
            {{ $viewTraining->budget_allocated ? '₱'.number_format((float) $viewTraining->budget_allocated, 2) : 'Not set' }}
          </div>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px"><i class="fas fa-lock"></i> Fixed at creation — use "Log Budget Usage" to record spending</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              @foreach(['Proposed','Approved','Ongoing','Completed'] as $s)
              <option {{ $viewTraining->status === $s ? 'selected' : '' }}>{{ $s }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Participants</label>
            <input type="number" name="target_participants" class="form-control" value="{{ (int) $viewTraining->target_participants }}" min="1"/>
          </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--gray-100);margin:8px 0 16px">
        <div class="form-group">
          <label class="form-label">Project Leader * <span style="font-weight:400;color:var(--gray-400)">(exactly one)</span></label>
          <select name="lead_id" class="form-control" required>
            <option value="">— Select Project Leader —</option>
            @foreach($trainers as $tr)
            <option value="{{ $tr->id }}" {{ (int) $currentLeadId === $tr->id ? 'selected' : '' }}>{{ $tr->first_name }} {{ $tr->last_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Team Members <span style="font-weight:400;color:var(--gray-400)">(optional, up to 3)</span></label>
          <div class="form-row" style="grid-template-columns:1fr 1fr 1fr">
            @for($i = 0; $i < 3; $i++)
            @php $cur = $currentMembers->get($i); @endphp
            <select name="member_ids[]" class="form-control">
              <option value="">— None —</option>
              @foreach($trainers as $tr)
              <option value="{{ $tr->id }}" {{ $cur && $cur->id === $tr->id ? 'selected' : '' }}>{{ $tr->first_name }} {{ $tr->last_name }}</option>
              @endforeach
            </select>
            @endfor
          </div>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">A team member can't also be the Project Leader, and can't be selected twice.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: UPLOAD ACTIVITY DOCUMENT -->
<div class="modal-overlay" id="modal-uploadActivityDoc">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-upload"></i> Upload Document</h2>
      <button class="modal-close" onclick="closeModal('uploadActivityDoc')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      @include('partials.document-upload-form', [
        'actionRoute' => route('ec.trainings.store'),
        'idSuffix'    => 'Activity',
        'scopeField'  => ['name' => 'activity_id', 'value' => $viewTraining->id],
        'submitLabel' => 'Save Document',
      ])
    </div>
  </div>
</div>

<!-- MODAL: CHANGE DISPLAY PICTURE -->
<div class="modal-overlay" id="modal-uploadCover">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2><i class="fas fa-camera"></i> Change Display Picture</h2>
      <button class="modal-close" onclick="closeModal('uploadCover')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}" enctype="multipart/form-data">
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

@else
{{-- ═══════════════════════════════════════════════════════ CARD GRID VIEW ════ --}}
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

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Activities</span></div>
    <h1>Extension Activities</h1>
    <p>Browse upcoming, ongoing and completed extension activities</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addTraining')"><i class="fas fa-plus"></i> Create Activity</button>
</div>

<form method="GET" action="{{ route('ec.trainings') }}" class="filter-row">
  <div class="search-box">
    <i class="fas fa-magnifying-glass"></i>
    <input type="text" name="q" value="{{ $q }}" placeholder="Search activities…"/>
  </div>
  <select name="area" class="filter-select" onchange="this.form.submit()">
    <option value="">All Areas</option>
    @foreach($areas as $a)
    <option value="{{ $a }}" {{ $area === $a ? 'selected' : '' }}>{{ $a }}</option>
    @endforeach
  </select>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Status</option>
    <option value="Upcoming" {{ $status === 'Upcoming' ? 'selected' : '' }}>Upcoming</option>
    <option value="Ongoing" {{ $status === 'Ongoing' ? 'selected' : '' }}>Ongoing</option>
    <option value="Completed" {{ $status === 'Completed' ? 'selected' : '' }}>Completed</option>
  </select>
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
  @if($q || $area || $status)<a href="{{ route('ec.trainings') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
</form>

@if($trainings->isEmpty())
<div class="empty-state"><i class="fas fa-book"></i><p>No activities found. <a href="#" onclick="openModal('addTraining')">Create one.</a></p></div>
@else
<div class="home-grid">
  @foreach($trainings as $t)
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
        <div class="training-card-cat" style="z-index:2;position:relative">{{ $t->area }}</div>
        @if(!$t->cover_image)
          <i class="fas {{ $icon }}" style="z-index:1;position:relative;font-size:40px"></i>
        @endif
      </div>
      <div class="training-card-body">
        <div class="training-card-title">{{ $t->title }}</div>
        <div class="training-card-desc">{{ \Illuminate\Support\Str::limit($t->description ?? '', 100, '…') }}</div>
        <div class="training-card-meta">
          <span><i class="fas fa-calendar"></i> {{ $t->date_start?->format('Y-m-d') ?? '—' }}</span>
          <span><i class="fas fa-user"></i> {{ $t->trainer->full_name ?? 'TBA' }}</span>
          <span><i class="fas fa-users"></i> {{ (int) $t->target_participants }} pax</span>
        </div>
        <div class="training-card-meta" style="margin-top:-4px">
          <span><i class="fas fa-diagram-project"></i> {{ $t->program->title ?? 'No program assigned' }}</span>
        </div>
        <div class="training-card-footer">
          <span class="badge {{ $sc }}">{{ $hs }}</span>
          <a href="{{ route('ec.trainings') }}?view={{ $t->id }}" class="btn btn-sm btn-primary">View Details</a>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endif

<!-- MODAL: CREATE ACTIVITY -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i>Create New Activity</h2>
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
@endif
@endsection
