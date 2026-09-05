@extends('layouts.evaluator')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Dashboard</span></div>
    <h1>Welcome, {{ $userFirstName }}</h1>
    <p>Submit and track your Impact Assessment forms here.</p>
  </div>
  <a href="{{ route('evaluator.impact_assessment') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Assessment</a>
</div>

<!-- Stats -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px">
  @foreach([
    ['fa-clipboard-list', 'Submitted', 'var(--blue-primary)', $totalSubmitted],
    ['fa-pen', 'Drafts', 'var(--amber)', $totalDraft],
    ['fa-square-check', 'Reviewed', 'var(--green)', $totalReviewed],
  ] as [$icon, $label, $color, $val])
  <div class="card" style="padding:20px 24px">
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:44px;height:44px;border-radius:12px;background:{{ $color }};opacity:.15;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;position:relative">
        <i class="fas {{ $icon }}" style="position:absolute;opacity:1;font-size:20px;color:{{ $color }}"></i>
      </div>
      <div>
        <div style="font-size:26px;font-weight:800;color:var(--text-heading)">{{ $val }}</div>
        <div style="font-size:12px;color:var(--gray-400)">{{ $label }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

<!-- Recent submissions -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-clipboard-list"></i> Recent Submissions</div>
    <a href="{{ route('evaluator.impact_assessment') }}" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="card-body" style="padding:0">
    @if($recent->isEmpty())
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No submissions yet. <a href="{{ route('evaluator.impact_assessment') }}" style="color:var(--blue-primary)">Submit your first assessment.</a></div>
    @else
    <table class="data-table">
      <thead><tr><th>Title</th><th>Activity</th><th>Status</th><th>Submitted</th></tr></thead>
      <tbody>
        @foreach($recent as $r)
        <tr>
          <td>{{ $r->title }}</td>
          <td>
            {{ $r->training->title ?? '—' }}
            @if($r->training?->program)
            <div style="font-size:11px;color:var(--gray-400)"><i class="fas fa-diagram-project"></i> {{ $r->training->program->title }}</div>
            @endif
          </td>
          <td><span class="badge {{ $r->status === 'Reviewed' ? 'badge-success' : ($r->status === 'Submitted' ? 'badge-info' : 'badge-warning') }}">{{ $r->status }}</span></td>
          <td>{{ $r->submitted_at?->format('M d, Y') ?? '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>
@endsection
