@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluations</span></div>
    <h1>Evaluations &amp; Feedback</h1>
    <p>Participant evaluation results for your training programs</p>
  </div>
</div>

@if($mode === 'responses')
{{-- ═══════════════════ RESPONSES VIEW ═══════════════════ --}}
<div style="margin-bottom:20px">
  <a href="{{ route('trainer.evaluations') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Evaluations</a>
</div>
<div class="card" style="margin-bottom:16px">
  <div class="card-header">
    <div>
      <div class="card-title">{{ $viewForm->title }}</div>
      <div class="card-subtitle">{{ $viewTraining->title }} &middot; {{ $responses->count() }} response(s)</div>
    </div>
  </div>
</div>

@if($responses->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:48px;color:var(--gray-400)">No responses submitted yet.</div></div>
@else
@php $fields = $viewForm->fields ?? []; @endphp
<div style="display:flex;flex-direction:column;gap:16px">
  @foreach($responses as $resp)
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title"><i class="fas fa-user"></i> {{ $resp->beneficiary->first_name }} {{ $resp->beneficiary->last_name }}</div>
        <div class="card-subtitle">Submitted {{ $resp->submitted_at->format('M d, Y g:i A') }}</div>
      </div>
    </div>
    <div class="card-body">
      @foreach($fields as $fi => $field)
      <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">{{ $field['label'] }}</div>
        <div style="font-size:14px;color:var(--gray-800)">{{ $resp->responses[$fi] ?? '—' }}</div>
      </div>
      @endforeach
    </div>
  </div>
  @endforeach
</div>
@endif

@else
{{-- ═══════════════════ MAIN VIEW ═══════════════════ --}}
@if($sentForms->isNotEmpty())
<div class="card" style="margin-bottom:24px;border:2px solid var(--blue-soft)">
  <div class="card-header" style="background:var(--blue-soft)">
    <div class="card-title"><i class="fas fa-bell"></i> Evaluation Forms Sent to You</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Training</th><th>Form Title</th><th>Sent On</th></tr></thead>
      <tbody>
      @foreach($sentForms as $f)
      <tr>
        <td><strong>{{ $f->training->title }}</strong></td>
        <td>{{ $f->title }}</td>
        <td style="font-size:12px;color:var(--gray-400)">{{ $f->sent_at->format('M d, Y') }}</td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-star"></i></div><div class="stat-body"><div class="stat-value">{{ $avgRating ?? '—' }}</div><div class="stat-label">Overall Avg. Rating</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-square-check"></i></div><div class="stat-body"><div class="stat-value">{{ $submitted }}</div><div class="stat-label">Submitted Evaluations</div></div></div>
  <div class="stat-card"><div class="stat-icon red"><i class="fas fa-clock"></i></div><div class="stat-body"><div class="stat-value">{{ $pending }}</div><div class="stat-label">Pending Evaluations</div></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Trainings — Evaluation Status</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Training Name</th><th>Date</th><th>Participants</th><th>Submitted</th><th>Pending</th><th>Avg. Rating</th><th>Status</th><th>Responses</th></tr></thead>
      <tbody>
      @forelse($evalData as $e)
        @php
          $done = $e->total_pax > 0 && $e->pending == 0;
          $eStatus = $done ? 'Completed' : ($e->submitted > 0 ? 'Ongoing' : 'Pending');
          $eBadge = $done ? 'badge-completed' : ($e->submitted > 0 ? 'badge-ongoing' : 'badge-pending');
        @endphp
      <tr>
        <td><strong>{{ $e->title }}</strong></td>
        <td style="font-size:12px;color:var(--gray-400)">{{ $e->date_start?->format('Y-m-d') ?? '—' }}</td>
        <td><strong>{{ (int) $e->total_pax }}</strong></td>
        <td style="color:var(--green);font-weight:700">{{ (int) $e->submitted }}</td>
        <td style="color:var(--yellow);font-weight:700">{{ (int) $e->pending }}</td>
        <td>{{ $e->avg_rating ? '⭐ '.round($e->avg_rating, 1) : '—' }}</td>
        <td><span class="badge {{ $eBadge }}">{{ $eStatus }}</span></td>
        <td>
          <span style="font-weight:700;color:{{ $e->response_count > 0 ? 'var(--green)' : 'var(--gray-400)' }}">{{ $e->response_count }}</span>
          @if($e->response_count > 0)
          <a href="?responses={{ $e->id }}" class="btn btn-sm btn-ghost" style="margin-left:4px"><i class="fas fa-eye"></i> View</a>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">No evaluation data yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif
@endsection
