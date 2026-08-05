@extends('layouts.beneficiary')

@section('content')
@if($mode === 'detail')
{{-- ═══════════════ DETAIL VIEW ═══════════════ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i>
      <a href="{{ route('beneficiary.trainings') }}" style="color:var(--blue-primary)">Trainings</a>
      <i class="fas fa-chevron-right"></i> <span>{{ $viewTraining->title }}</span>
    </div>
    <h1>{{ $viewTraining->title }}</h1>
    <p>{{ $viewTraining->area }}</p>
  </div>
  <a href="{{ route('beneficiary.trainings') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<!-- Info cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-calendar"></i></div><div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->date_start?->format('Y-m-d') ?? '—' }}</div><div class="stat-label">Schedule</div></div></div>
  <div class="stat-card"><div class="stat-icon {{ $viewTraining->status === 'Completed' ? 'green' : ($viewTraining->status === 'Ongoing' ? 'blue' : 'yellow') }}"><i class="fas fa-circle-info"></i></div><div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $viewTraining->status }}</div><div class="stat-label">Status</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-user"></i></div><div class="stat-body"><div class="stat-value" style="font-size:14px">{{ $viewTraining->trainer->full_name ?? 'TBA' }}</div><div class="stat-label">Project Leader</div></div></div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <!-- Description -->
    <div class="card">
      <div class="card-header"><div class="card-title">About this Training</div></div>
      <div class="card-body">
        @if($viewTraining->description)
        <p style="font-size:14px;color:var(--gray-700);line-height:1.8">{!! nl2br(e($viewTraining->description)) !!}</p>
        @else
        <p style="color:var(--gray-400);font-size:13px">No description provided.</p>
        @endif
      </div>
    </div>

    <!-- Documents / Learning Modules -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-folder-open"></i> Learning Materials</div></div>
      <div class="card-body">
        @if($viewDocs->isEmpty())
        <div class="empty-state" style="padding:24px"><i class="fas fa-folder-open"></i><p>No materials uploaded yet.</p></div>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px">
          @php
            $icons = ['pdf' => ['fa-file-pdf', '#EF4444', '#FEE2E2'], 'doc' => ['fa-file-word', '#4F46E5', '#E0E7FF'],
                      'docx' => ['fa-file-word', '#4F46E5', '#E0E7FF'], 'xls' => ['fa-file-excel', '#10B981', '#D1FAE5'],
                      'xlsx' => ['fa-file-excel', '#10B981', '#D1FAE5'], 'jpg' => ['fa-image', '#1A56DB', '#DBEAFE'],
                      'jpeg' => ['fa-image', '#1A56DB', '#DBEAFE'], 'png' => ['fa-image', '#1A56DB', '#DBEAFE'],
                      'mp4' => ['fa-file-video', '#F59E0B', '#FEF3C7'], 'ppt' => ['fa-file-powerpoint', '#EF4444', '#FEE2E2'],
                      'pptx' => ['fa-file-powerpoint', '#EF4444', '#FEE2E2']];
          @endphp
          @foreach($viewDocs as $d)
            @php
              $ext = strtolower($d->file_type ?? '');
              [$ico, $color, $bg] = $icons[$ext] ?? ['fa-file', '#64748B', '#F1F5F9'];
            @endphp
          <a href="{{ route('files.document', $d) }}" target="_blank"
             style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 12px;border-radius:12px;border:1.5px solid var(--gray-200);text-decoration:none;transition:all .2s;background:var(--surface)"
             onmouseover="this.style.borderColor='{{ $color }}';this.style.background='{{ $bg }}'"
             onmouseout="this.style.borderColor='var(--gray-200)';this.style.background='var(--surface)'">
            <div style="width:56px;height:56px;border-radius:12px;background:{{ $bg }};color:{{ $color }};display:flex;align-items:center;justify-content:center;font-size:24px">
              <i class="fas {{ $ico }}"></i>
            </div>
            <div style="text-align:center">
              <div style="font-size:12px;font-weight:600;color:var(--gray-800);word-break:break-word;line-height:1.3">{{ $d->original_name }}</div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:{{ $color }};margin-top:4px">{{ strtoupper($ext) }}</div>
            </div>
          </a>
          @endforeach
        </div>
        @endif
      </div>
    </div>
  </div>

  <div class="dash-side">
    <div class="card">
      <div class="card-header"><div class="card-title">Training Details</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:0">
        @php
          $details = [
            ['Area', $viewTraining->area],
            ['Date', $viewTraining->date_start?->format('Y-m-d') ?? '—'],
            ['Status', $viewTraining->status],
            ['Project Leader', $viewTraining->trainer->full_name ?? 'TBA'],
            ['Target', $viewTraining->target_participants . ' pax'],
          ];
        @endphp
        @foreach($details as [$lb, $vl])
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100)">
          <span style="font-size:12px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.3px">{{ $lb }}</span>
          <span style="font-size:13px;font-weight:600;color:var(--gray-800)">{{ $vl }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

@else
{{-- ═══════════════ CARD GRID ═══════════════ --}}
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
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Trainings</span></div>
    <h1>Training Programs</h1>
    <p>Browse all extension training programs offered by CIT-SLSU</p>
  </div>
</div>

<form method="GET" action="{{ route('beneficiary.trainings') }}" class="filter-row">
  <div class="search-box"><i class="fas fa-magnifying-glass"></i><input type="text" name="q" value="{{ $q }}" placeholder="Search trainings…"/></div>
  <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
  @if($q)<a href="{{ route('beneficiary.trainings') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
</form>

@if($trainings->isEmpty())
<div class="empty-state"><i class="fas fa-book"></i><p>No trainings found.</p></div>
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
          <span><i class="fas fa-user"></i> {{ $t->trainer->full_name ?? 'TBA' }}</span>
          <span><i class="fas fa-users"></i> {{ (int) $t->enrolled }} enrolled</span>
        </div>
        <div class="training-card-footer">
          <span class="badge {{ $sc }}">{{ $hs }}</span>
          <a href="?view={{ $t->id }}" class="btn btn-sm btn-primary">View Details</a>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endif
@endif
@endsection
