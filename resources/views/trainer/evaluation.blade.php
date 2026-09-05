@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluation</span></div>
    <h1>Evaluation</h1>
    <p>Evaluations and Skills Utilization for your activities</p>
  </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-paper-plane"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $sentForms }}</div><div class="stat-label">Evaluation Forms Sent</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-check"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $submitted }}</div><div class="stat-label">Responses Submitted</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon {{ $pending > 0 ? 'yellow' : 'green' }}"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $pending }}</div><div class="stat-label">Responses Pending</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-star"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $avgRating ?? '—' }}</div><div class="stat-label">Average Rating</div></div>
  </div>
</div>

<!-- Go to sub-features -->
<div class="home-grid" style="margin-bottom:24px">
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-star" style="color:var(--blue-primary)"></i> Evaluations</div>
      <div class="training-card-desc">Build and send evaluation forms for your activities, and review submitted responses.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('trainer.evaluations') }}" class="btn btn-sm btn-primary">Open Evaluations <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-chart-line" style="color:var(--blue-primary)"></i> Skills Utilization</div>
      <div class="training-card-desc">Track how your beneficiaries are applying their skills after training.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('trainer.skills') }}" class="btn btn-sm btn-primary">Open Skills Utilization <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>

<!-- Skills Utilization Overview -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-chart-line"></i> Skills Utilization Overview</div></div>
    <a href="{{ route('trainer.skills') }}" class="btn btn-ghost btn-sm">View Details</a>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
    @foreach([['Personal Use', $skillsOverview['personal']], ['Income-Generating', $skillsOverview['income']], ['Employment', $skillsOverview['employment']]] as [$label, $pct])
    <div style="text-align:center;padding:16px;background:var(--gray-50);border-radius:10px">
      <div style="font-size:24px;font-weight:800;color:var(--navy)">{{ $pct }}%</div>
      <div style="font-size:12px;color:var(--gray-500);margin-top:4px">{{ $label }}</div>
    </div>
    @endforeach
  </div>
</div>
@endsection
