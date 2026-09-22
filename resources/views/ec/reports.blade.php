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

/* document analysis — mini stat row */
.mini-stats { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; }
.mini-stat {
  background: var(--surface); border: 1px solid var(--gray-200); border-radius: 12px;
  padding: 14px 20px; min-width: 140px; flex: 1;
}
.mini-stat-val { font-size: 26px; font-weight: 800; color: var(--text-heading); line-height: 1; }
.mini-stat-lbl { font-size: 11.5px; color: var(--gray-400); margin-top: 4px; }
.mini-stat.blue   .mini-stat-val { color: var(--blue-primary); }
.mini-stat.green  .mini-stat-val { color: var(--green); }
.mini-stat.orange .mini-stat-val { color: var(--yellow); }
.mini-stat.purple .mini-stat-val { color: #8B5CF6; }

/* document analysis — file vs link bar */
.ratio-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.ratio-label { min-width: 130px; font-size: 12.5px; color: var(--gray-700); }
.ratio-track { flex: 1; background: var(--gray-100); border-radius: 4px; height: 10px; overflow: hidden; }
.ratio-fill { height: 100%; border-radius: 4px; }
.ratio-count { min-width: 90px; font-size: 12px; color: var(--gray-500); text-align: right; }
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Reports</span></div>
    <h1>Reports</h1>
    <p>Comprehensive overview of activity accomplishments, beneficiaries, and document analysis</p>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TOP SUMMARY STATS
════════════════════════════════════════════════════════════ -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:36px">
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-diagram-project"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $totalPrograms }}</div><div class="stat-label">Total Projects</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $totalTrainings }}</div><div class="stat-label">Total Activities</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $completedCount }}</div><div class="stat-label">Completed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $ongoingCount }}</div><div class="stat-label">Ongoing</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-lightbulb"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $proposedCount }}</div><div class="stat-label">Proposed</div></div>
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
      <div class="chart-card-title"><i class="fas fa-book"></i> Activities by Type</div>
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

</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 3 — DOCUMENT ANALYSIS
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico purple"><i class="fas fa-file-lines"></i></div>
    <div>
      <div class="rpt-section-title">Document Analysis</div>
      <div class="rpt-section-sub">Storage, upload activity, and type breakdown for every document and link in the system</div>
    </div>
  </div>

  <div class="mini-stats">
    <div class="mini-stat blue">
      <div class="mini-stat-val">{{ $totalDocuments }}</div>
      <div class="mini-stat-lbl">Total Documents &amp; Links</div>
    </div>
    <div class="mini-stat green">
      <div class="mini-stat-val">{{ $totalStorage }}</div>
      <div class="mini-stat-lbl">Total Storage Used</div>
    </div>
    <div class="mini-stat purple">
      <div class="mini-stat-val">{{ $fileVsLink['file'] }}</div>
      <div class="mini-stat-lbl">Uploaded Files</div>
    </div>
    <div class="mini-stat orange">
      <div class="mini-stat-val">{{ $fileVsLink['link'] }}</div>
      <div class="mini-stat-lbl">External Links</div>
    </div>
  </div>

  <div class="chart-row" style="grid-template-columns:1fr">
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-chart-column"></i> Documents &amp; Links by Type</div>
      @if(empty($byType))
      <div style="text-align:center;padding:40px;color:var(--gray-400);font-size:13px">No documents yet.</div>
      @else
      <canvas id="chartByType" style="max-height:260px"></canvas>
      @endif
    </div>
  </div>

  <div class="chart-row" style="grid-template-columns:1fr">
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-chart-line"></i> Uploads Over Time (Last 12 Months)</div>
      <canvas id="chartOverTime" style="max-height:260px"></canvas>
    </div>
  </div>

  <div class="chart-row" style="grid-template-columns:1fr">
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-scale-balanced"></i> File vs. Link Ratio</div>
      @php $ratioTotal = max(1, $fileVsLink['file'] + $fileVsLink['link']); @endphp
      <div class="ratio-row">
        <div class="ratio-label"><i class="fas fa-file" style="color:var(--blue-primary)"></i> Uploaded Files</div>
        <div class="ratio-track"><div class="ratio-fill" style="width:{{ round($fileVsLink['file'] / $ratioTotal * 100) }}%;background:var(--blue-primary)"></div></div>
        <div class="ratio-count">{{ $fileVsLink['file'] }} ({{ round($fileVsLink['file'] / $ratioTotal * 100) }}%)</div>
      </div>
      <div class="ratio-row">
        <div class="ratio-label"><i class="fas fa-link" style="color:#F59E0B"></i> External Links</div>
        <div class="ratio-track"><div class="ratio-fill" style="width:{{ round($fileVsLink['link'] / $ratioTotal * 100) }}%;background:#F59E0B"></div></div>
        <div class="ratio-count">{{ $fileVsLink['link'] }} ({{ round($fileVsLink['link'] / $ratioTotal * 100) }}%)</div>
      </div>
    </div>

    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-ranking-star"></i> Top 5 Programs &amp; Activities by Document Count</div>
      @if(empty($topByCount))
      <div style="text-align:center;padding:40px;color:var(--gray-400);font-size:13px">No program- or activity-scoped documents yet.</div>
      @else
      <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>Name</th><th>Type</th><th style="text-align:right">Documents</th></tr>
        </thead>
        <tbody>
          @foreach($topByCount as $i => $row)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['label'] }}</td>
            <td><span class="badge">{{ $row['type'] }}</span></td>
            <td style="text-align:right;font-weight:700">{{ $row['count'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      </div>
      @endif
    </div>
  </div>

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
      backgroundColor: ['#1A56DB','#F59E0B','#10B981','#8B5CF6','#EF4444','#6B7280'],
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
    scales: { x: { beginAtZero: true, grid: { display: false }, ticks: { font: { size: 11 }, precision: 0, stepSize: 1 } },
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
      x: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 }, precision: 0, stepSize: 1 }, beginAtZero: true },
      y: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }
  }
});
@endif

// ── Document Analysis ───────────────────────────────────────────────────────
(function () {
  const palette = ['#1A56DB','#10B981','#F59E0B','#8B5CF6','#EF4444','#38BDF8','#EC4899','#64748B','#0EA5E9','#7C3AED'];

  @if(!empty($byType))
  new Chart(document.getElementById('chartByType'), {
    type: 'bar',
    data: {
      labels: {!! json_encode(array_keys($byType)) !!},
      datasets: [{ data: {!! json_encode(array_values($byType)) !!}, backgroundColor: palette }],
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
  @endif

  new Chart(document.getElementById('chartOverTime'), {
    type: 'line',
    data: {
      labels: {!! json_encode($uploadsByMonth['labels']) !!},
      datasets: [{
        label: 'Uploads',
        data: {!! json_encode($uploadsByMonth['counts']) !!},
        borderColor: '#1A56DB',
        backgroundColor: 'rgba(26,86,219,.1)',
        fill: true,
        tension: 0.3,
      }],
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
})();

</script>
@endsection
