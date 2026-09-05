@extends('layouts.beneficiary')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluation</span></div>
    <h1>Evaluation</h1>
    <p>Evaluations, Impact Assessment, and Skills Utilization surveys sent to you</p>
  </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon {{ $evalPending > 0 ? 'yellow' : 'green' }}"><i class="fas fa-star"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $evalPending }} / {{ $evalTotal }}</div><div class="stat-label">Evaluations Pending</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon {{ $iaPending > 0 ? 'yellow' : 'green' }}"><i class="fas fa-clipboard-list"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $iaPending }} / {{ $iaTotal }}</div><div class="stat-label">Impact Assessments Pending</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon {{ $skillsPending > 0 ? 'yellow' : 'green' }}"><i class="fas fa-chart-line"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $skillsPending }} / {{ $skillsTotal }}</div><div class="stat-label">Skills Surveys Pending</div></div>
  </div>
</div>

<!-- Go to sub-features -->
<div class="home-grid" style="margin-bottom:24px">
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-star" style="color:var(--blue-primary)"></i> Evaluations</div>
      <div class="training-card-desc">Answer training evaluations sent to you by the Extension Coordinator or Project Leader.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('beneficiary.evaluations') }}" class="btn btn-sm btn-primary">Open Evaluations <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-clipboard-list" style="color:var(--blue-primary)"></i> Impact Assessment</div>
      <div class="training-card-desc">Answer impact assessment surveys about how training has affected you.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('beneficiary.impact_assessment') }}" class="btn btn-sm btn-primary">Open Impact Assessment <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-chart-line" style="color:var(--blue-primary)"></i> Skills Utilization</div>
      <div class="training-card-desc">Tell us how you've applied the skills you learned.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('beneficiary.skills') }}" class="btn btn-sm btn-primary">Open Skills Utilization <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Impact Assessment Surveys -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Impact Assessment Surveys</div></div>
    <a href="{{ route('beneficiary.impact_assessment') }}" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Survey</th><th>Activity</th><th>Sent</th><th>Status</th></tr></thead>
      <tbody>
      @forelse($recentAssessmentForms as $f)
        <tr>
          <td><strong>{{ $f->title }}</strong></td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $f->training->title ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $f->sent_at?->format('M d, Y') ?? '—' }}</td>
          <td><span class="badge {{ $f->myResponse ?? null ? 'badge-success' : 'badge-warning' }}">{{ ($f->myResponse ?? null) ? 'Answered' : 'Pending' }}</span></td>
        </tr>
      @empty
        <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--gray-400)">No impact assessment surveys sent to you yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
