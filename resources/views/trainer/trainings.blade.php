@extends('layouts.trainer')

@section('content')
@if($mode === 'detail')
{{-- ═══════════════════════════════════════════════════════ DETAIL VIEW ═══ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i>
      <a href="{{ route('trainer.trainings') }}" style="color:var(--blue-primary)">My Trainings</a>
      <i class="fas fa-chevron-right"></i> <span>{{ $viewTraining->title }}</span>
    </div>
    <h1>{{ $viewTraining->title }}</h1>
    <p>{{ $viewTraining->area }}</p>
  </div>
  <a href="{{ route('trainer.trainings') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-calendar"></i></div><div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->date_start?->format('Y-m-d') ?? '—' }}</div><div class="stat-label">Schedule</div></div></div>
  <div class="stat-card"><div class="stat-icon {{ $viewTraining->status === 'Completed' ? 'green' : ($viewTraining->status === 'Ongoing' ? 'blue' : 'yellow') }}"><i class="fas fa-circle-info"></i></div><div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->status }}</div><div class="stat-label">Status</div></div></div>
  <div class="stat-card"><div class="stat-icon navy"><i class="fas fa-users"></i></div><div class="stat-body"><div class="stat-value">{{ $viewParticipants->count() }}</div><div class="stat-label">Trainees</div></div></div>
</div>

<!-- Project Progress -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-chart-line"></i> Project Progress</div><div class="card-subtitle">Budget and timeline status</div></div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:{{ $progress['healthHex'] }}22;color:{{ $progress['healthHex'] }};border:1px solid {{ $progress['healthHex'] }}44"><i class="fas fa-circle" style="font-size:6px"></i> {{ $progress['healthLabel'] }}</span>
      <button class="btn btn-sm btn-outline" onclick="openModal('updateBudget')"><i class="fas fa-sack-dollar"></i> Update Budget</button>
    </div>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:28px">
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
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--text-heading)"><i class="fas fa-clock"></i> Timeline</span>
        @if($progress['timePct'] !== null)<span style="font-size:13px;font-weight:700;color:{{ $progress['timePct'] >= 100 ? 'var(--gray-500)' : ($progress['timePct'] >= 80 ? '#F59E0B' : 'var(--blue-primary)') }}">{{ $progress['timePct'] }}%</span>@endif
      </div>
      @if($progress['dateStart'] && $progress['dateEnd'])
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:{{ $progress['timePct'] }}%;background:{{ $progress['timePct'] >= 100 ? '#6B7280' : ($progress['timePct'] >= 80 ? '#F59E0B' : '#1A56DB') }}"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>{{ $progress['dateStart']->format('M d, Y') }}</span>
        <span>{{ $progress['dateEnd']->format('M d, Y') }}</span>
      </div>
      <div style="margin-top:6px;font-size:12px;color:var(--gray-500)">
        @if($progress['timePct'] >= 100)
        <span style="color:var(--gray-500)"><i class="fas fa-check"></i> Period completed</span>
        @else
        <span style="color:var(--blue-primary)"><strong>{{ $progress['daysLeft'] }}</strong> day{{ $progress['daysLeft'] !== 1 ? 's' : '' }} remaining</span>
        @endif
      </div>
      @else
      <div style="color:var(--gray-400);font-size:13px;padding:8px 0">No period assigned.</div>
      @endif
    </div>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <div class="card">
      <div class="card-header"><div class="card-title">Description</div></div>
      <div class="card-body"><p style="font-size:14px;color:var(--gray-700);line-height:1.8">{!! $viewTraining->description ? nl2br(e($viewTraining->description)) : '<span style="color:var(--gray-400)">No description.</span>' !!}</p></div>
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
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100)">
          <span style="font-size:12px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ $lb }}</span>
          <span style="font-size:13px;font-weight:600;color:var(--gray-800)">{{ $vl }}</span>
        </div>
        @endforeach
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Documents</div><a href="{{ route('trainer.documents') }}" class="btn btn-ghost btn-sm">Upload</a></div>
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
  </div>
</div>

<!-- MODAL: UPDATE BUDGET -->
<div class="modal-overlay" id="modal-updateBudget">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2>Update Budget</h2>
      <button class="modal-close" onclick="closeModal('updateBudget')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('trainer.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="update_budget"/>
      <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> Enter the allocated budget and the amount you have spent so far.
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
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>My Trainings</span></div>
    <h1>My Training Programs</h1>
    <p>All extension trainings assigned to you by the Extension Coordinator</p>
  </div>
</div>

<form method="GET" action="{{ route('trainer.trainings') }}" class="filter-row">
  <div class="search-box"><i class="fas fa-magnifying-glass"></i><input type="text" name="q" value="{{ $q }}" placeholder="Search trainings…"/></div>
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
<div class="empty-state"><i class="fas fa-book"></i><p>No trainings assigned yet.</p></div>
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
        <div class="training-card-cat">{{ $t->area }}</div>
        <i class="fas {{ $icon }}" style="z-index:1;position:relative;font-size:40px"></i>
      </div>
      <div class="training-card-body">
        <div class="training-card-title">{{ $t->title }}</div>
        <div class="training-card-desc">{{ \Illuminate\Support\Str::limit($t->description ?? '', 100, '…') }}</div>
        <div class="training-card-meta">
          <span><i class="fas fa-calendar"></i> {{ $t->date_start?->format('Y-m-d') ?? '—' }}</span>
          <span><i class="fas fa-users"></i> {{ (int) $t->trainees }} trainees</span>
        </div>
        <div class="training-card-footer">
          <span class="badge {{ $sc }}">{{ $hs }}</span>
          <a href="{{ route('trainer.trainings') }}?view={{ $t->id }}" class="btn btn-sm btn-primary">View Details</a>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endif
@endif
@endsection
