@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Skills Utilization</span></div>
    <h1>Skills Utilization</h1>
    <p>View beneficiary responses to skills surveys across all trainings</p>
  </div>
  @if($viewForm)
  <a href="{{ route('ec.skills') }}" class="btn btn-outline">&#8592; Back to All Forms</a>
  @endif
</div>

@if($viewForm)
{{-- ═══════════════ RESPONSES VIEW ═══════════════ --}}
@php
  $fields = $viewForm->fields ?? [];
  $rate   = $viewForm->total_pax > 0 ? round($viewForm->responses_count / $viewForm->total_pax * 100) : 0;
@endphp

<!-- Summary bar -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px">
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--blue-primary)">{{ (int) $viewForm->total_pax }}</div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Total Participants</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--green)">{{ (int) $viewForm->responses_count }}</div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Responded</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--navy)">{{ $rate }}%</div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Response Rate</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--gray-600)">{{ count($fields) }}</div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Questions</div>
  </div>
</div>

<!-- Form info -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">{{ $viewForm->title }}</div>
      <div class="card-subtitle">
        &#128218; {{ $viewForm->training->title ?? '—' }}
        &nbsp;·&nbsp; &#128100; {{ $viewForm->training->trainer->full_name ?? 'N/A' }}
        @if($viewForm->sent_at)
        &nbsp;·&nbsp; &#128276; Sent {{ $viewForm->sent_at->format('M d, Y') }}
        @endif
      </div>
    </div>
  </div>
</div>

@if($responses->isEmpty())
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128200;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No responses yet</div>
    <p style="font-size:13px;color:var(--gray-400)">Beneficiaries haven't submitted this survey yet.</p>
  </div>
</div>
@else

<!-- Per-question summary -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">&#128202; Response Summary by Question</div></div>
  <div class="card-body">
    @foreach($fields as $fi => $field)
      @php
        $tally = [];
        foreach ($responses as $r) {
            $val = trim($r->responses[$fi] ?? '');
            if ($val !== '') $tally[$val] = ($tally[$val] ?? 0) + 1;
        }
        arsort($tally);
      @endphp
      <div style="margin-bottom:28px;padding-bottom:24px;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:10px">
          {{ $fi + 1 }}. {{ $field['label'] }}
          <span style="font-size:11px;font-weight:400;color:var(--gray-400);margin-left:6px">({{ ucfirst($field['type']) }})</span>
        </div>

        @if(in_array($field['type'], ['radio', 'select']) && !empty($tally))
          <div style="display:flex;flex-direction:column;gap:6px">
            @foreach($tally as $opt => $cnt)
              @php $pct = $responses->count() > 0 ? round($cnt / $responses->count() * 100) : 0; @endphp
              <div style="display:flex;justify-content:space-between;align-items:center;background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:8px 12px">
                <span style="font-size:13px;color:var(--gray-700)">{{ $opt }}</span>
                <span style="font-size:12px;font-weight:700;color:var(--blue-primary)">{{ $cnt }} response{{ $cnt !== 1 ? 's' : '' }} ({{ $pct }}%)</span>
              </div>
            @endforeach
          </div>
        @elseif(!empty($tally))
          <!-- text/textarea: list unique answers -->
          <div style="display:flex;flex-direction:column;gap:6px">
            @foreach(array_keys($tally) as $ans)
              <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:8px 12px;font-size:13px;color:var(--gray-700)">
                {{ $ans }}
              </div>
            @endforeach
          </div>
        @else
          <div style="font-size:13px;color:var(--gray-400)">No answers yet.</div>
        @endif
      </div>
    @endforeach
  </div>
</div>

<!-- Individual responses table -->
<div class="card">
  <div class="card-header"><div class="card-title">&#128101; Individual Responses</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Beneficiary</th>
          <th>Submitted</th>
          @foreach($fields as $field)
          <th style="min-width:160px">{{ \Illuminate\Support\Str::limit($field['label'], 40) }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($responses as $r)
        <tr>
          <td style="color:var(--gray-400);font-size:12px">{{ $loop->iteration }}</td>
          <td><strong>{{ $r->beneficiary->first_name }} {{ $r->beneficiary->last_name }}</strong></td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $r->submitted_at->format('M d, Y g:i A') }}</td>
          @foreach($fields as $fi => $field)
          <td style="font-size:13px;color:var(--gray-700)">{{ $r->responses[$fi] ?? '—' }}</td>
          @endforeach
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@else
{{-- ═══════════════ FORMS LIST ═══════════════ --}}

@if($forms->isEmpty())
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128200;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No skills surveys found</div>
    <p style="font-size:13px;color:var(--gray-400)">Trainers create and send skills surveys to beneficiaries. They will appear here once created.</p>
  </div>
</div>
@else
<div class="card">
  <div class="card-header">
    <div class="card-title">All Skills Surveys</div>
    <div class="card-subtitle">{{ $forms->count() }} form{{ $forms->count() !== 1 ? 's' : '' }} found</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Form Title</th>
          <th>Training</th>
          <th>Project Leader</th>
          <th>Sent</th>
          <th>Participants</th>
          <th>Responded</th>
          <th>Response Rate</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($forms as $f)
          @php $rate = $f->total_pax > 0 ? round($f->responses_count / $f->total_pax * 100) : 0; @endphp
          <tr>
            <td><strong>{{ $f->title }}</strong></td>
            <td style="font-size:12px;color:var(--gray-700)">{{ $f->training->title ?? '—' }}</td>
            <td style="font-size:12px;color:var(--gray-400)">{{ $f->training->trainer->full_name ?? 'N/A' }}</td>
            <td style="font-size:12px;color:var(--gray-400)">
              @if($f->sent_at)
                {{ $f->sent_at->format('M d, Y') }}
              @else
                <span style="color:var(--gray-300)">Not sent</span>
              @endif
            </td>
            <td><strong>{{ (int) $f->total_pax }}</strong></td>
            <td style="color:var(--green);font-weight:700">{{ (int) $f->responses_count }}</td>
            <td>
              <span style="font-size:13px;font-weight:700;color:var(--gray-700)">{{ $rate }}%</span>
            </td>
            <td>
              <a href="{{ route('ec.skills') }}?form={{ $f->id }}" class="btn btn-sm btn-outline">&#128065; View Responses</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@endif
@endsection
