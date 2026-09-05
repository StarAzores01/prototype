@extends('layouts.evaluator')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluation</span></div>
    <h1>Evaluation</h1>
    <p>Your Impact Assessment submissions at a glance</p>
  </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-paper-plane"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $submittedCount + $reviewedCount }}</div><div class="stat-label">Total Submitted</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $submittedCount }}</div><div class="stat-label">Awaiting EC Review</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-check"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $reviewedCount }}</div><div class="stat-label">Reviewed</div></div>
  </div>
</div>

<!-- Go to sub-feature -->
<div class="home-grid" style="margin-bottom:24px">
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-clipboard-list" style="color:var(--blue-primary)"></i> Impact Assessment</div>
      <div class="training-card-desc">Submit new impact assessments and manage your previously submitted work.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="{{ route('evaluator.impact_assessment') }}" class="btn btn-sm btn-primary">Open Impact Assessment <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Impact Assessments -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Submissions</div></div>
    <a href="{{ route('evaluator.impact_assessment') }}" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Title</th><th>Activity</th><th>Status</th><th>Submitted</th></tr></thead>
      <tbody>
      @forelse($recentAssessments as $a)
        <tr>
          <td><strong>{{ $a->title }}</strong></td>
          <td style="font-size:12px;color:var(--gray-600)">
            {{ $a->training->title ?? '—' }}
            @if($a->training?->program)
            <div style="font-size:11px;color:var(--gray-400)"><i class="fas fa-diagram-project"></i> {{ $a->training->program->title }}</div>
            @endif
          </td>
          <td><span class="badge {{ $a->status === 'Reviewed' ? 'badge-success' : ($a->status === 'Submitted' ? 'badge-info' : 'badge-warning') }}">{{ $a->status }}</span></td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $a->submitted_at?->format('M d, Y') ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--gray-400)">No assessments submitted yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
