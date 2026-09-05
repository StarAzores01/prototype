@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluation</span></div>
    <h1>Evaluation</h1>
    <p>Evaluations, Impact Assessment, and Skills Utilization at a glance</p>
  </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon {{ $pendingEvaluations > 0 ? 'red' : 'green' }}"><i class="fas fa-star-half-stroke"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $pendingEvaluations }}</div><div class="stat-label">Pending Evaluations</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-paper-plane"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $evalFormsSent }}</div><div class="stat-label">Evaluation Forms Sent</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon {{ $pendingAssessments > 0 ? 'yellow' : 'green' }}"><i class="fas fa-clipboard-list"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $pendingAssessments }}</div><div class="stat-label">Impact Assessments Awaiting Review</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-chart-line"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $skillsResponses }}</div><div class="stat-label">Skills Survey Responses</div></div>
  </div>
</div>

<!-- Go to sub-features -->
<div class="home-grid" style="margin-bottom:24px">
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-star" style="color:var(--blue-primary)"></i> Evaluations</div>
      <div class="training-card-desc">Build and send per-activity evaluation forms, and review submitted responses.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('ec.evaluations') }}" class="btn btn-sm btn-primary">Open Evaluations <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-clipboard-list" style="color:var(--blue-primary)"></i> Impact Assessment</div>
      <div class="training-card-desc">Review assessments submitted by Evaluators and manage participant survey forms.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('ec.impact_assessment') }}" class="btn btn-sm btn-primary">Open Impact Assessment <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-chart-line" style="color:var(--blue-primary)"></i> Skills Utilization</div>
      <div class="training-card-desc">Track skills-utilization survey forms and responses across all activities.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('ec.skills') }}" class="btn btn-sm btn-primary">Open Skills Utilization <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Impact Assessments -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Impact Assessments</div></div>
    <a href="{{ route('ec.impact_assessment') }}" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Title</th><th>Evaluator</th><th>Activity</th><th>Status</th><th>Submitted</th></tr></thead>
      <tbody>
      @forelse($recentAssessments as $a)
        <tr>
          <td><strong>{{ $a->title }}</strong></td>
          <td style="font-size:12px">{{ $a->evaluator->full_name ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $a->training->title ?? '—' }}</td>
          <td><span class="badge {{ $a->status === 'Reviewed' ? 'badge-success' : 'badge-info' }}">{{ $a->status }}</span></td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $a->submitted_at?->format('M d, Y') ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-400)">No impact assessments submitted yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
