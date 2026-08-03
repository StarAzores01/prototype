@extends('layouts.ec')

@section('content')
@if($mode === 'detail')
{{-- ═══════════════════════════════════════════════════════ DETAIL VIEW ═══ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250;
      <a href="{{ route('ec.trainings') }}" style="color:var(--blue-primary)">Trainings</a>
      &#8250; <span>{{ $viewTraining->title }}</span>
    </div>
    <h1>{{ $viewTraining->title }}</h1>
    <p>{{ $viewTraining->area }}</p>
  </div>
  <div style="display:flex;gap:10px">
    <a href="{{ route('ec.trainings') }}" class="btn btn-outline">&#8592; Back</a>
    <button class="btn btn-primary" onclick="openModal('editTraining')">&#9998; Update Training</button>
  </div>
</div>

<!-- Info Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon blue">&#128197;</div>
    <div class="stat-body"><div class="stat-value" style="font-size:16px">{{ $viewTraining->date_start?->format('Y-m-d') ?? '—' }}</div><div class="stat-label">Schedule Date</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon {{ $viewTraining->status === 'Completed' ? 'green' : ($viewTraining->status === 'Ongoing' ? 'blue' : 'yellow') }}">&#8505;</div>
    <div class="stat-body"><div class="stat-value" style="font-size:16px">{{ $viewTraining->status }}</div><div class="stat-label">Status</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy">&#128101;</div>
    <div class="stat-body"><div class="stat-value">{{ $viewParticipants->count() }}</div><div class="stat-label">Enrolled / {{ (int) $viewTraining->target_participants }} target</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">&#128100;</div>
    <div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->trainer->full_name ?? 'TBA' }}</div><div class="stat-label">Project Leader</div></div>
  </div>
</div>

<!-- Project Progress Card -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">&#128200; Project Progress</div>
      <div class="card-subtitle">Budget utilization and timeline status</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:{{ $progress['healthHex'] }}22;color:{{ $progress['healthHex'] }};border:1px solid {{ $progress['healthHex'] }}44">
        &#9679; {{ $progress['healthLabel'] }}
      </span>
      <button class="btn btn-sm btn-outline" onclick="openModal('updateBudget')">Update Budget</button>
    </div>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:28px">

    <!-- Budget Progress -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--navy)">&#128176; Budget Utilization</span>
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
        <span style="font-size:13px;font-weight:700;color:var(--navy)">&#128336; Timeline Progress</span>
        @if($progress['timePct'] !== null)
        <span style="font-size:13px;font-weight:700;color:{{ $progress['timePct'] >= 100 ? '#6B7280' : ($progress['timePct'] >= 80 ? '#F59E0B' : '#1A56DB') }}">{{ $progress['timePct'] }}%</span>
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
          <span style="color:#6B7280">&#10003; Period completed ({{ $progress['totalDays'] }} days)</span>
        @elseif($progress['daysLeft'] !== null)
          <span style="color:#1A56DB"><strong>{{ $progress['daysLeft'] }}</strong> day{{ $progress['daysLeft'] !== 1 ? 's' : '' }} remaining of {{ $progress['totalDays'] }} total</span>
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
      <div class="card-header"><div class="card-title">&#9679;Description</div></div>
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
        <div><div class="card-title">&#128101;Participants</div></div>
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
    <!-- Training Details -->
    <div class="card">
      <div class="card-header"><div class="card-title">Training Details</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        @php
          $details = [
            ['Area / Specialization', $viewTraining->area ?? '—'],
            ['Start Date', $viewTraining->date_start?->format('Y-m-d') ?? '—'],
            ['End Date', $viewTraining->date_end?->format('Y-m-d') ?? '—'],
            ['Status', $viewTraining->status ?? '—'],
            ['Project Leader', $viewTraining->trainer->full_name ?? 'TBA'],
            ['Target Participants', $viewTraining->target_participants ?? '—'],
            ['Budget Allocated', $viewTraining->budget_allocated ? '₱'.number_format((float) $viewTraining->budget_allocated, 2) : '—'],
            ['Budget Used', $viewTraining->budget_used ? '₱'.number_format((float) $viewTraining->budget_used, 2) : '₱0.00'],
            ['Created', $viewTraining->created_at?->format('M d, Y') ?? '—'],
          ];
        @endphp
        @foreach($details as [$label, $val])
        <div style="display:flex;align-items:flex-start;gap:12px">
          <div><div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ $label }}</div><div style="font-size:13px;color:var(--gray-800);font-weight:500;margin-top:2px">{{ $val }}</div></div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Documents -->
    <div class="card">
      <div class="card-header"><div class="card-title">Documents</div><a href="{{ route('ec.documents') }}" class="btn btn-ghost btn-sm">All Docs</a></div>
      <div class="card-body" style="padding-top:8px">
        @forelse($viewDocs as $d)
        <div class="upload-item" style="margin-bottom:8px">
          <div class="upload-item-body">
            <div class="upload-item-name">{{ $d->original_name }}</div>
            <div class="upload-item-meta">{{ $d->uploader->full_name ?? '' }} &middot; {{ $d->created_at?->format('M d, Y') }}</div>
          </div>
        </div>
        @empty
        <div class="empty-state" style="padding:16px">&#128193;<p>No documents attached.</p></div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<!-- MODAL: UPDATE BUDGET -->
<div class="modal-overlay" id="modal-updateBudget">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2>&#128176; Update Budget</h2>
      <button class="modal-close" onclick="closeModal('updateBudget')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="update_budget"/>
      <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          &#8505; Update the allocated budget and the amount currently used for this training.
        </div>
        <div class="form-group">
          <label class="form-label">Budget Allocated (₱)</label>
          <input type="number" name="budget_allocated" class="form-control"
            value="{{ $viewTraining->budget_allocated ?? '' }}"
            min="0" step="0.01" placeholder="e.g. 50000"/>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Total budget assigned to this training</div>
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
        <button type="submit" class="btn btn-primary">&#10003; Save Budget</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: UPDATE TRAINING -->
<div class="modal-overlay" id="modal-editTraining">
  <div class="modal">
    <div class="modal-header">
      <h2>&#9998;Update Training</h2>
      <button class="modal-close" onclick="closeModal('editTraining')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Training Title *</label>
            <input type="text" name="title" class="form-control" value="{{ $viewTraining->title }}" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <select name="area" class="form-control" required>
              @foreach(['Mechanical Technology','Automotive Technology','Computer Technology','Electronics Technology','Culinary Technology','Apparel and Fashion Technology','Print Media Technology','Information Technology'] as $opt)
              <option {{ $viewTraining->area === $opt ? 'selected' : '' }}>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3">{{ $viewTraining->description }}</textarea>
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
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget Allocated (₱)</label>
            <input type="number" name="budget_allocated" class="form-control" value="{{ $viewTraining->budget_allocated }}" min="0" step="0.01" placeholder="e.g. 50000"/>
          </div>
          <div class="form-group">
            <label class="form-label">Budget Used (₱)</label>
            <input type="number" name="budget_used" class="form-control" value="{{ $viewTraining->budget_used ?? 0 }}" min="0" step="0.01"/>
          </div>
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
        <div class="form-group">
          <label class="form-label">Project Leader</label>
          <select name="trainer_id" class="form-control">
            <option value="">— Select Project Leader —</option>
            @foreach($trainers as $tr)
            <option value="{{ $tr->id }}" {{ $viewTraining->trainer_id == $tr->id ? 'selected' : '' }}>{{ $tr->first_name }} {{ $tr->last_name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#9679; Save Changes</button>
      </div>
    </form>
  </div>
</div>

@else
{{-- ═══════════════════════════════════════════════════════ CARD GRID VIEW ════ --}}
@php
  $catEmoji = [
    'Mechanical Technology' => '⚙️', 'Automotive Technology' => '🚗', 'Computer Technology' => '💻',
    'Electronics Technology' => '🔌', 'Culinary Technology' => '🍳', 'Apparel and Fashion Technology' => '🧵',
    'Print Media Technology' => '🖨️', 'Information Technology' => '🖥️',
  ];
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
    <div class="breadcrumb">PAThrive &#8250; <span>Trainings</span></div>
    <h1>Extension Trainings</h1>
    <p>Browse upcoming, ongoing and completed extension training programs</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addTraining')">&#43; Create Training</button>
</div>

<form method="GET" action="{{ route('ec.trainings') }}" class="filter-row">
  <div class="search-box">
    &#128269;
    <input type="text" name="q" value="{{ $q }}" placeholder="Search trainings…"/>
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
  <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
  @if($q || $area || $status)<a href="{{ route('ec.trainings') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
</form>

@if($trainings->isEmpty())
<div class="empty-state">&#128218;<p>No trainings found. <a href="#" onclick="openModal('addTraining')">Create one.</a></p></div>
@else
<div class="home-grid">
  @foreach($trainings as $t)
    @php
      $hs = $statusMap[$t->status] ?? $t->status;
      $sc = $statusClass[$hs] ?? 'badge-approved';
      $em = $catEmoji[$t->area] ?? '📚';
      $c = $catColors[$t->area] ?? ['#1A56DB', '#2E6BF0'];
    @endphp
    <div class="training-card">
      <div class="training-card-img" style="background:linear-gradient(135deg,{{ $c[0] }},{{ $c[1] }})">
        <div class="training-card-cat">{{ $t->area }}</div>
        <span style="z-index:1;position:relative;font-size:48px">{{ $em }}</span>
      </div>
      <div class="training-card-body">
        <div class="training-card-title">{{ $t->title }}</div>
        <div class="training-card-desc">{{ \Illuminate\Support\Str::limit($t->description ?? '', 100) }}</div>
        <div class="training-card-meta">
          <span>&#128197; {{ $t->date_start?->format('Y-m-d') ?? '—' }}</span>
          <span>&#128100; {{ $t->trainer->full_name ?? 'TBA' }}</span>
          <span>&#128101; {{ (int) $t->target_participants }} pax</span>
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

<!-- MODAL: CREATE TRAINING -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2>&#43;Create New Training</h2>
      <button class="modal-close" onclick="closeModal('addTraining')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Training Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Basic Pastry Making" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <select name="area" class="form-control" required>
              <option>Mechanical Technology</option>
              <option>Automotive Technology</option>
              <option>Computer Technology</option>
              <option>Electronics Technology</option>
              <option>Culinary Technology</option>
              <option>Apparel and Fashion Technology</option>
              <option>Print Media Technology</option>
              <option>Information Technology</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description (optional)</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the training…"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="date_start" class="form-control"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end" class="form-control"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget Allocated (₱)</label>
            <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 50000" min="0" step="0.01"/>
          </div>
          <div class="form-group">
            <label class="form-label">Budget Used (₱)</label>
            <input type="number" name="budget_used" class="form-control" placeholder="e.g. 0" min="0" step="0.01" value="0"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option>Proposed</option><option>Approved</option><option>Ongoing</option><option>Completed</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">No. of Participants (target)</label>
            <input type="number" name="target_participants" class="form-control" placeholder="e.g. 30" min="1"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Project Leader</label>
          <select name="trainer_id" class="form-control">
            <option value="">— Select Project Leader —</option>
            @foreach($trainers as $tr)
            <option value="{{ $tr->id }}">{{ $tr->first_name }} {{ $tr->last_name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Training</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection
