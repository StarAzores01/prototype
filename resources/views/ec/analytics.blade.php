@extends('layouts.ec')

@section('content')
<style>
/* mini stat row */
.mini-stats { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 28px; }
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

/* chart row */
.chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:860px){ .chart-row { grid-template-columns: 1fr; } }
.chart-card { background: var(--surface); border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px; }
.chart-card-title { font-size: 13px; font-weight: 700; color: var(--text-heading); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.chart-empty { text-align: center; padding: 40px 0; color: var(--gray-400); font-size: 13px; }

/* file vs link bar */
.ratio-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.ratio-label { min-width: 130px; font-size: 12.5px; color: var(--gray-700); }
.ratio-track { flex: 1; background: var(--gray-100); border-radius: 4px; height: 10px; overflow: hidden; }
.ratio-fill { height: 100%; border-radius: 4px; }
.ratio-count { min-width: 90px; font-size: 12px; color: var(--gray-500); text-align: right; }
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Analytics</span></div>
    <h1>Document Analytics</h1>
    <p>Storage, upload activity, and scope breakdown for every document and link in the system</p>
  </div>
</div>

<!-- TOP SUMMARY STATS -->
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

<div class="chart-row">
  <!-- BY FILE TYPE -->
  <div class="chart-card">
    <div class="chart-card-title"><i class="fas fa-chart-pie"></i> Documents &amp; Links by Type</div>
    @if(empty($byType))
      <div class="chart-empty">No documents yet.</div>
    @else
      <canvas id="chartByType" style="max-height:260px"></canvas>
    @endif
  </div>

  <!-- BY SCOPE -->
  <div class="chart-card">
    <div class="chart-card-title"><i class="fas fa-layer-group"></i> By Scope</div>
    @if($totalDocuments === 0)
      <div class="chart-empty">No documents yet.</div>
    @else
      <canvas id="chartByScope" style="max-height:260px"></canvas>
    @endif
  </div>
</div>

<div class="chart-row" style="grid-template-columns:1fr">
  <!-- UPLOADS OVER TIME -->
  <div class="chart-card">
    <div class="chart-card-title"><i class="fas fa-chart-line"></i> Uploads Over Time (Last 12 Months)</div>
    <canvas id="chartOverTime" style="max-height:260px"></canvas>
  </div>
</div>

<div class="chart-row">
  <!-- FILE VS LINK RATIO -->
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

  <!-- TOP 5 -->
  <div class="chart-card">
    <div class="chart-card-title"><i class="fas fa-ranking-star"></i> Top 5 Programs &amp; Activities by Document Count</div>
    @if(empty($topByCount))
      <div class="chart-empty">No program- or activity-scoped documents yet.</div>
    @else
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
    @endif
  </div>
</div>

<script>
(function () {
  const palette = ['#1A56DB','#10B981','#F59E0B','#8B5CF6','#EF4444','#38BDF8','#EC4899','#64748B','#0EA5E9','#7C3AED'];

  @if(!empty($byType))
  new Chart(document.getElementById('chartByType'), {
    type: 'doughnut',
    data: {
      labels: {!! json_encode(array_keys($byType)) !!},
      datasets: [{ data: {!! json_encode(array_values($byType)) !!}, backgroundColor: palette }],
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } },
  });
  @endif

  @if($totalDocuments > 0)
  new Chart(document.getElementById('chartByScope'), {
    type: 'bar',
    data: {
      labels: {!! json_encode(array_keys($byScope)) !!},
      datasets: [{ data: {!! json_encode(array_values($byScope)) !!}, backgroundColor: ['#1A56DB','#10B981','#F59E0B'] }],
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
