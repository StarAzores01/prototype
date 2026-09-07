@extends('layouts.ec')

@section('content')
<style>
.rpt-section { margin-bottom: 40px; }
.rpt-section-header {
  display: flex; align-items: center; gap: 12px;
  padding: 0 0 14px; margin-bottom: 20px;
  border-bottom: 2px solid var(--gray-100);
}
.rpt-section-ico {
  width: 40px; height: 40px; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}
.rpt-section-ico.blue   { background: rgba(26,86,219,.1); }
.rpt-section-ico.green  { background: rgba(16,185,129,.1); }
.rpt-section-ico.purple { background: rgba(139,92,246,.1); }
.rpt-section-ico.orange { background: rgba(245,158,11,.1); }
.rpt-section-title { font-size: 17px; font-weight: 800; color: var(--text-heading); }
.rpt-section-sub   { font-size: 12.5px; color: var(--gray-400); margin-top: 2px; }

/* mini stat row */
.mini-stats { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; }
.mini-stat {
  background: var(--surface); border: 1px solid var(--gray-200); border-radius: 12px;
  padding: 14px 20px; min-width: 120px; flex: 1;
}
.mini-stat-val { font-size: 26px; font-weight: 800; color: var(--text-heading); line-height: 1; }
.mini-stat-lbl { font-size: 11.5px; color: var(--gray-400); margin-top: 4px; }
.mini-stat.blue   .mini-stat-val { color: var(--blue-primary); }
.mini-stat.green  .mini-stat-val { color: var(--green); }
.mini-stat.orange .mini-stat-val { color: var(--yellow); }
.mini-stat.purple .mini-stat-val { color: #8B5CF6; }

/* chart row */
.chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:860px){ .chart-row { grid-template-columns: 1fr; } }
.chart-card {
  background: var(--surface); border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px;
}
.chart-card-title { font-size: 13px; font-weight: 700; color: var(--text-heading); margin-bottom: 16px; }
canvas { max-height: 220px; }

/* bar summary */
.bar-summary { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: flex; align-items: center; gap: 10px; }
.bar-label { min-width: 160px; font-size: 12.5px; color: var(--gray-700); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bar-track { flex: 1; background: var(--gray-100); border-radius: 4px; height: 8px; }
.bar-fill  { height: 8px; border-radius: 4px; }
.bar-count { min-width: 52px; font-size: 12px; color: var(--gray-500); text-align: right; }

/* skills/eval accordion */
.survey-block {
  background: var(--surface); border: 1px solid var(--gray-200); border-radius: 14px;
  margin-bottom: 14px; overflow: hidden;
}
.survey-block-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; cursor: pointer; user-select: none;
  transition: background .18s;
}
.survey-block-header:hover { background: var(--gray-50); }
.survey-block-title { font-size: 14px; font-weight: 700; color: var(--text-heading); }
.survey-block-meta  { font-size: 12px; color: var(--gray-400); margin-top: 2px; }
.survey-block-body  { padding: 0 20px 20px; border-top: 1px solid var(--gray-100); display: none; }
.survey-block-body.open { display: block; }
.survey-q { margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid var(--gray-100); }
.survey-q:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.survey-q-label { font-size: 13px; font-weight: 700; color: var(--text-heading); margin-bottom: 10px; }
.survey-q-type  { font-size: 11px; color: var(--gray-400); font-weight: 400; margin-left: 6px; }
.text-answers   { display: flex; flex-direction: column; gap: 6px; }
.text-answer    { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: 8px 12px; font-size: 13px; color: var(--gray-700); }
.no-resp        { font-size: 13px; color: var(--gray-400); font-style: italic; }

/* response rate pill */
.rate-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
}
.rate-pill.high   { background: rgba(16,185,129,.1); color: #065F46; }
.rate-pill.medium { background: rgba(245,158,11,.1);  color: #92400E; }
.rate-pill.low    { background: rgba(239,68,68,.1);   color: #991B1B; }
</style>

<div class="page-header">
  <div>
    <div class="page-title">Reports</div>
    <div class="page-sub">Comprehensive overview of activity accomplishments, beneficiaries, skills utilization, and evaluations</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TOP SUMMARY STATS
════════════════════════════════════════════════════════════ -->
<div class="mini-stats" style="margin-bottom:36px">
  <div class="mini-stat blue">
    <div class="mini-stat-val">{{ $totalTrainings }}</div>
    <div class="mini-stat-lbl">Total Activities</div>
  </div>
  <div class="mini-stat green">
    <div class="mini-stat-val">{{ $completedCount }}</div>
    <div class="mini-stat-lbl">Completed</div>
  </div>
  <div class="mini-stat orange">
    <div class="mini-stat-val">{{ $ongoingCount }}</div>
    <div class="mini-stat-lbl">Ongoing</div>
  </div>
  <div class="mini-stat" style="">
    <div class="mini-stat-val" style="color:#8B5CF6">{{ $proposedCount }}</div>
    <div class="mini-stat-lbl">Proposed</div>
  </div>
  <div class="mini-stat">
    <div class="mini-stat-val">{{ $totalPart }}</div>
    <div class="mini-stat-lbl">Total Participants</div>
  </div>
  <div class="mini-stat">
    <div class="mini-stat-val">{{ $totalBen }}</div>
    <div class="mini-stat-lbl">Registered Beneficiaries</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 1 — ACTIVITY ACCOMPLISHMENTS
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico blue"><i class="fas fa-book"></i></div>
    <div>
      <div class="rpt-section-title">Activity Accomplishments</div>
      <div class="rpt-section-sub">All activities and their current status</div>
    </div>
  </div>

  <div class="chart-row">
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-chart-column"></i> Activities by Status</div>
      <canvas id="chartStatus"></canvas>
    </div>
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-book"></i> Activities by Technology Area</div>
      <div style="position:relative;height:{{ max(180, count($byArea) * 36) }}px">
        <canvas id="chartArea"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 2 — BENEFICIARY REPORT
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico green"><i class="fas fa-users"></i></div>
    <div>
      <div class="rpt-section-title">Beneficiary Report</div>
      <div class="rpt-section-sub">All registered beneficiaries and their activity enrollment</div>
    </div>
  </div>

  <div class="chart-row" style="grid-template-columns:1fr">
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-book"></i> Participants per Activity</div>
      @if($partPerTraining->isEmpty())
      <div style="text-align:center;padding:40px;color:var(--gray-400);font-size:13px">No data yet.</div>
      @else
      <div style="position:relative;height:{{ max(180, count($partPerTraining) * 36) }}px">
        <canvas id="chartPartPerTraining"></canvas>
      </div>
      @endif
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Address</th>
          <th>Activities Enrolled</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        @forelse($beneficiaries as $b)
        <tr>
          <td style="color:var(--gray-400);font-size:12px">{{ $loop->iteration }}</td>
          <td><strong>{{ $b->full_name }}</strong></td>
          <td style="font-size:12.5px;color:var(--gray-500)">{{ $b->email }}</td>
          <td style="font-size:12.5px;color:var(--gray-600)">{{ $b->address ?? '—' }}</td>
          <td>
            <strong>{{ (int) $b->enrolled_count }}</strong>
            @if($b->trainings_list)
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">{{ \Illuminate\Support\Str::limit($b->trainings_list, 80, '…') }}</div>
            @endif
          </td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $b->created_at->format('M d, Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400)">No beneficiaries found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 3 — SKILLS UTILIZATION
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico purple"><i class="fas fa-chart-line"></i></div>
    <div>
      <div class="rpt-section-title">Skills Utilization</div>
      <div class="rpt-section-sub">Aggregated beneficiary responses to post-activity skills surveys</div>
    </div>
  </div>

  <!-- Skills Utilization Charts removed -->

  @if($skillsForms->isEmpty())
  <div class="card"><div style="text-align:center;padding:48px;color:var(--gray-400)">No skills surveys have been sent yet.</div></div>
  @else
  @foreach($skillsForms as $sf)
    @php
      $sfFields = $sf->fields ?? [];
      $sfTotal  = $sf->responses->count();
      $sfRate   = $sf->total_pax > 0 ? round($sf->responses->count() / $sf->total_pax * 100) : 0;
      $sfRateClass = $sfRate >= 70 ? 'high' : ($sfRate >= 40 ? 'medium' : 'low');
    @endphp
  <div class="survey-block">
    <div class="survey-block-header" onclick="toggleSurvey('sk-{{ $sf->id }}')">
      <div>
        <div class="survey-block-title">{{ $sf->title }}</div>
        <div class="survey-block-meta">
          <i class="fas fa-book"></i> {{ $sf->training->title ?? '' }}
          &nbsp;·&nbsp; {{ $sfTotal }} / {{ $sf->total_pax }} responded
          &nbsp;·&nbsp; <span class="rate-pill {{ $sfRateClass }}">{{ $sfRate }}% response rate</span>
        </div>
      </div>
      <span style="font-size:18px;color:var(--gray-400)" id="sk-{{ $sf->id }}-arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="survey-block-body" id="sk-{{ $sf->id }}">
      @if($sfTotal === 0)
      <div class="no-resp" style="padding:16px 0">No responses submitted yet.</div>
      @else
      @foreach($sfFields as $fi => $field)
        @php
          $tally = [];
          foreach ($sf->responses as $r) {
              $val = trim($r->responses[$fi] ?? '');
              if ($val !== '') $tally[$val] = ($tally[$val] ?? 0) + 1;
          }
          arsort($tally);
        @endphp
      <div class="survey-q">
        <div class="survey-q-label">
          {{ $fi + 1 }}. {{ $field['label'] }}
          <span class="survey-q-type">({{ ucfirst($field['type']) }})</span>
        </div>
        @if(in_array($field['type'], ['radio','select']) && !empty($tally))
        <div class="bar-summary">
          @foreach($tally as $opt => $cnt)
            @php $pct = round($cnt / $sfTotal * 100); @endphp
          <div class="bar-row">
            <div class="bar-label" title="{{ $opt }}">{{ $opt }}</div>
            <div class="bar-track"><div class="bar-fill" style="width:{{ $pct }}%;background:#8B5CF6"></div></div>
            <div class="bar-count">{{ $cnt }} ({{ $pct }}%)</div>
          </div>
          @endforeach
        </div>
        @elseif(!empty($tally))
        <div class="text-answers">
          @foreach(array_keys($tally) as $ans)
          <div class="text-answer">{{ $ans }}</div>
          @endforeach
        </div>
        @else
        <div class="no-resp">No answers recorded.</div>
        @endif
      </div>
      @endforeach
      @endif
    </div>
  </div>
  @endforeach
  @endif
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 4 — EVALUATION SUMMARY
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico orange"><i class="fas fa-star"></i></div>
    <div>
      <div class="rpt-section-title">Evaluation Summary</div>
      <div class="rpt-section-sub">Aggregated participant feedback and satisfaction ratings per activity</div>
    </div>
  </div>

  @if($evalForms->isEmpty())
  <div class="card"><div style="text-align:center;padding:48px;color:var(--gray-400)">No evaluation forms have been sent yet.</div></div>
  @else
  @foreach($evalForms as $ef)
    @php
      $efFields = $ef->fields ?? [];
      $efTotal  = $ef->responses->count();
      $efRate   = $ef->total_pax > 0 ? round($ef->responses->count() / $ef->total_pax * 100) : 0;
      $efRateClass = $efRate >= 70 ? 'high' : ($efRate >= 40 ? 'medium' : 'low');
    @endphp
  <div class="survey-block">
    <div class="survey-block-header" onclick="toggleSurvey('ev-{{ $ef->id }}')">
      <div>
        <div class="survey-block-title">{{ $ef->title }}</div>
        <div class="survey-block-meta">
          <i class="fas fa-book"></i> {{ $ef->training->title ?? '' }}
          &nbsp;·&nbsp; {{ $efTotal }} / {{ $ef->total_pax }} responded
          &nbsp;·&nbsp; <span class="rate-pill {{ $efRateClass }}">{{ $efRate }}% response rate</span>
        </div>
      </div>
      <span style="font-size:18px;color:var(--gray-400)" id="ev-{{ $ef->id }}-arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="survey-block-body" id="ev-{{ $ef->id }}">
      @if($efTotal === 0)
      <div class="no-resp" style="padding:16px 0">No responses submitted yet.</div>
      @else
      @foreach($efFields as $fi => $field)
        @php
          $tally = [];
          foreach ($ef->responses as $r) {
              $val = trim($r->responses[$fi] ?? '');
              if ($val !== '') $tally[$val] = ($tally[$val] ?? 0) + 1;
          }
          arsort($tally);
        @endphp
      <div class="survey-q">
        <div class="survey-q-label">
          {{ $fi + 1 }}. {{ $field['label'] }}
          <span class="survey-q-type">({{ ucfirst($field['type']) }})</span>
        </div>
        @if(in_array($field['type'], ['radio','select','rating']) && !empty($tally))
        <div class="bar-summary">
          @foreach($tally as $opt => $cnt)
            @php $pct = round($cnt / $efTotal * 100); @endphp
          <div class="bar-row">
            <div class="bar-label" title="{{ $opt }}">{{ $opt }}</div>
            <div class="bar-track"><div class="bar-fill" style="width:{{ $pct }}%;background:var(--yellow)"></div></div>
            <div class="bar-count">{{ $cnt }} ({{ $pct }}%)</div>
          </div>
          @endforeach
        </div>
        @elseif(!empty($tally))
        <div class="text-answers">
          @foreach(array_keys($tally) as $ans)
          <div class="text-answer">{{ $ans }}</div>
          @endforeach
        </div>
        @else
        <div class="no-resp">No answers recorded.</div>
        @endif
      </div>
      @endforeach
      @endif
    </div>
  </div>
  @endforeach
  @endif
</div>

<!-- Chart.js is loaded once in layouts.ec, shared by every EC page's charts -->
<script>
// ── Status doughnut ───────────────────────────────────────────────────────
const statusData = {!! json_encode($byStatus->pluck('cnt')->values()) !!};
const statusLabels = {!! json_encode($byStatus->pluck('status')->values()) !!};
new Chart(document.getElementById('chartStatus'), {
  type: 'doughnut',
  data: {
    labels: statusLabels,
    datasets: [{ data: statusData,
      backgroundColor: ['#1A56DB','#10B981','#F59E0B','#8B5CF6'],
      borderWidth: 2, borderColor: '#fff' }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } }, cutout: '62%' }
});

// ── Area bar ──────────────────────────────────────────────────────────────
const areaLabels = {!! json_encode($byArea->pluck('area')->values()) !!};
const areaData   = {!! json_encode($byArea->pluck('cnt')->values()) !!};
new Chart(document.getElementById('chartArea'), {
  type: 'bar',
  data: {
    labels: areaLabels,
    datasets: [{ label: 'Activities', data: areaData,
      backgroundColor: 'rgba(26,86,219,.75)', borderRadius: 6, borderSkipped: false }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false }, ticks: { font: { size: 11 } } },
              y: { grid: { display: false }, ticks: { font: { size: 11 } } } }
  }
});


// ── Participants per activity (horizontal bar) ────────────────────────────
@if($partPerTraining->isNotEmpty())
new Chart(document.getElementById('chartPartPerTraining'), {
  type: 'bar',
  data: {
    labels: {!! json_encode($partPerTraining->map(fn($r) => \Illuminate\Support\Str::limit($r->title, 30, '…'))->values()) !!},
    datasets: [{
      label: 'Participants',
      data: {!! json_encode($partPerTraining->pluck('cnt')->values()) !!},
      backgroundColor: Array.from({length: {{ count($partPerTraining) }}}, (_, i) => `hsl(${210 + i * 18}, 70%, 55%)`),
      borderRadius: 5,
      borderSkipped: false
    }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.raw} participant${ctx.raw !== 1 ? 's' : ''}` } } },
    scales: {
      x: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } }, beginAtZero: true },
      y: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }
  }
});
@endif

// ── Accordion toggle ──────────────────────────────────────────────────────
function toggleSurvey(id) {
  const body  = document.getElementById(id);
  const arrow = document.getElementById(id + '-arrow');
  const open  = body.classList.toggle('open');
  if (arrow) arrow.innerHTML = open ? '<i class="fas fa-chevron-up"></i>' : '<i class="fas fa-chevron-down"></i>';
}
</script>
@endsection
