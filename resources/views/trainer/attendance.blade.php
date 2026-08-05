@extends('layouts.trainer')

@section('content')
<style>
@media print {
  @page { size: landscape; margin: 10mm; }

  * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

  .no-print { display:none !important; }
  .sidebar, .topbar, .main-wrap > *:not(.page-content) { display:none !important; }
  .main-wrap { margin-left:0 !important; }
  .page-content { padding:0 !important; }

  /* strip card chrome */
  .card { box-shadow:none !important; border:none !important; margin:0 !important; padding:0 !important; }
  .card-header { display:none !important; }

  /* show print-only header */
  .print-header-block { display:block !important; }

  /* table fills the page */
  .att-wrap { overflow:visible !important; }
  .att-grid {
    width: 100% !important;
    font-size: 8pt !important;
    border-collapse: collapse !important;
    table-layout: fixed !important;
  }
  .att-grid th {
    background: #09182F !important;
    color: #fff !important;
    padding: 4pt 5pt !important;
    font-size: 7pt !important;
    white-space: nowrap;
    border: 1px solid #334155 !important;
  }
  .att-grid td {
    padding: 4pt 5pt !important;
    border: 1px solid #CBD5E1 !important;
    font-size: 8pt !important;
    vertical-align: middle !important;
  }
  .att-grid th.name-col,
  .att-grid td.name-col { width: 28% !important; text-align: left !important; }
  .att-grid th:first-child,
  .att-grid td:first-child { width: 22pt !important; text-align: center !important; }

  /* hide interactive elements, show print symbols */
  .att-grid input[type=checkbox] { display:none !important; }
  .att-grid .print-status { display:inline !important; font-size:10pt !important; }
  .att-grid .id-number-cell { display:none !important; }
  .day-present-count { display:none !important; }

  /* alternate row shading */
  .att-grid tbody tr:nth-child(even) td { background: #F8FAFF !important; }
}

/* ── Screen styles (unchanged) ── */
.att-wrap { overflow-x: auto; }
.att-grid { border-collapse: collapse; min-width: 100%; font-size: 13px; }
.att-grid th {
  background: var(--navy); color: #fff;
  padding: 10px 16px; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .5px;
  white-space: nowrap; text-align: center;
}
.att-grid th.name-col { text-align: left; min-width: 200px; }
.att-grid td {
  padding: 10px 16px; border-bottom: 1px solid var(--gray-100);
  text-align: center; vertical-align: middle;
}
.att-grid td.name-col { text-align: left; }
.att-grid tbody tr:hover td { background: var(--blue-xsoft); }
.att-grid tbody tr:nth-child(even) td { background: var(--gray-50); }
.att-grid tbody tr:nth-child(even):hover td { background: var(--blue-xsoft); }
.day-check { width: 20px; height: 20px; accent-color: var(--blue-primary); cursor: pointer; }
.day-num { display: block; font-size: 12px; font-weight: 700; }
.day-date-sub { display: block; font-size: 10px; color: rgba(255,255,255,.5); margin-top: 1px; }
.day-present-count { display: block; font-size: 10px; color: #6EE7B7; margin-top: 3px; font-weight: 600; }
</style>

<div class="page-header no-print">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Attendance</span></div>
    <h1>Attendance</h1>
    <p>Check the box if the participant attended that day</p>
  </div>
</div>

<!-- Training selector -->
<div class="card no-print" style="margin-bottom:20px">
  <div class="card-body">
    <form method="GET" action="{{ route('trainer.attendance') }}" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="flex:2;min-width:220px;margin:0">
        <label class="form-label">Training</label>
        <select name="training" class="form-control" onchange="this.form.submit()">
          <option value="">— Select Training —</option>
          @foreach($myTrainings as $t)
          <option value="{{ $t->id }}" {{ $selectedTraining == $t->id ? 'selected' : '' }}>
            {{ $t->title }}
          </option>
          @endforeach
        </select>
      </div>
    </form>
  </div>
</div>

@if(!$selectedTraining || !$trainingInfo)
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    <i class="fas fa-clipboard-list"></i>
    <div style="font-size:15px;font-weight:700;color:var(--text-heading);margin:12px 0 6px">Select a training to begin</div>
    <p style="font-size:13px;color:var(--gray-400)">Choose a training from the dropdown above.</p>
  </div>
</div>

@elseif($participants->isEmpty())
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    <i class="fas fa-users"></i>
    <div style="font-size:15px;font-weight:700;color:var(--text-heading);margin:12px 0 6px">No participants enrolled</div>
    <p style="font-size:13px;color:var(--gray-400)">Add participants to this training first.</p>
  </div>
</div>

@else

<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">{{ $trainingInfo->title }}</div>
      <div class="card-subtitle">
        {{ $participants->count() }} participant{{ $participants->count() !== 1 ? 's' : '' }}
        &nbsp;·&nbsp; {{ $days->count() }} day{{ $days->count() !== 1 ? 's' : '' }}
      </div>
    </div>
    @if($days->isNotEmpty() && $participants->isNotEmpty())
    <div style="display:flex;gap:8px" class="no-print">
      <button type="button" class="btn btn-outline btn-sm" onclick="exportCSV()"><i class="fas fa-floppy-disk"></i> Export CSV</button>
      <button type="button" class="btn btn-outline btn-sm" onclick="window.print()"><i class="fas fa-book-open"></i> Print / Export</button>
    </div>
    @endif
  </div>

  <!-- Print-only report header -->
  <div style="display:none" class="print-header-block">
    <div style="text-align:center;padding:8px 0 10px">
      <div style="font-size:8pt;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748B;margin-bottom:2px">PAThrive · CIT-SLSU Extension Program</div>
      <div style="font-size:14pt;font-weight:800;color:#09182F;margin-bottom:2px">Attendance Sheet</div>
      <div style="font-size:11pt;font-weight:600;color:#1A56DB;margin-bottom:4px">{{ $trainingInfo->title }}</div>
      <div style="font-size:8pt;color:#64748B">
        {{ $trainingInfo->area ?? '' }}
        &nbsp;·&nbsp; {{ $trainingInfo->date_start?->format('Y-m-d') ?? '—' }}
        &nbsp;·&nbsp; {{ $participants->count() }} participant{{ $participants->count() !== 1 ? 's' : '' }}
        &nbsp;·&nbsp; {{ $days->count() }} day{{ $days->count() !== 1 ? 's' : '' }}
        &nbsp;·&nbsp; Generated: {{ now()->format('F d, Y') }}
      </div>
    </div>
  </div>

  @if($days->isEmpty())
  <div class="card-body" style="text-align:center;padding:48px 24px;color:var(--gray-400)">
    <i class="fas fa-calendar"></i> No days yet — add Day 1 below.
  </div>
  @else

  <form method="POST" action="{{ route('trainer.attendance.store') }}" id="attForm">
    @csrf
    <input type="hidden" name="action" value="save"/>
    <input type="hidden" name="training_id" value="{{ $selectedTraining }}"/>
    @foreach($days as $di => $day)
    <input type="hidden" name="days[{{ $di }}]" value="{{ $day }}"/>
    @endforeach

    <div class="att-wrap">
      <table class="att-grid">
        <thead>
          <tr>
            <th class="name-col" style="width:32px;min-width:32px">#</th>
            <th class="name-col">Participant</th>
            @foreach($days as $di => $day)
              @php
                $cnt = 0;
                foreach ($participants as $p) {
                    if (($attGrid[$day][$p->id] ?? '') === 'Present') $cnt++;
                }
              @endphp
            <th style="min-width:80px">
              <span class="day-num">Day {{ $di + 1 }}</span>
              <span class="day-date-sub">{{ \Illuminate\Support\Carbon::parse($day)->format('M d') }}</span>
              <span class="day-present-count" id="cnt-{{ $di }}">{{ $cnt }}/{{ $participants->count() }}</span>
            </th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($participants as $i => $p)
          <tr>
            <td class="name-col" style="color:var(--gray-400);font-size:12px;width:32px">{{ $i + 1 }}</td>
            <td class="name-col">
              <strong>{{ $p->full_name }}</strong>
              @if($p->id_number)
              <div class="id-number-cell" style="font-size:11px;color:var(--gray-400)">{{ $p->id_number }}</div>
              @endif
            </td>
            @foreach($days as $di => $day)
              @php $present = ($attGrid[$day][$p->id] ?? '') === 'Present'; @endphp
            <td>
              <input type="checkbox"
                     class="day-check"
                     name="present[{{ $di }}][{{ $p->id }}]"
                     value="1"
                     {{ $present ? 'checked' : '' }}
                     onchange="updateCount({{ $di }})"/>
              <span class="print-status" style="display:none;font-weight:700;font-size:14px;color:{{ $present ? '#059669' : '#CBD5E1' }}">{{ $present ? '✔' : '—' }}</span>
            </td>
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);display:flex;justify-content:flex-end" class="no-print">
      <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Attendance</button>
    </div>
  </form>

  @endif

  <!-- Add Day -->
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);background:var(--gray-50);display:flex;align-items:center;gap:12px;flex-wrap:wrap" class="no-print">
    <form method="POST" action="{{ route('trainer.attendance.store') }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap" id="addDayForm">
      @csrf
      <input type="hidden" name="action" value="add_day"/>
      <input type="hidden" name="training_id" value="{{ $selectedTraining }}"/>
      <span style="font-size:13px;font-weight:600;color:var(--text-heading)">Add Day {{ $days->count() + 1 }}:</span>
      <input type="date" name="new_date" class="form-control" style="width:170px" value="{{ now()->format('Y-m-d') }}" required/>
      <button type="submit" class="btn btn-outline"><i class="fas fa-plus"></i> Add Day</button>
    </form>
  </div>
</div>

<script>
function updateCount(di) {
  const boxes   = document.querySelectorAll(`input.day-check[name^="present[${di}]"]`);
  const present = [...boxes].filter(b => b.checked).length;
  const el = document.getElementById('cnt-' + di);
  if (el) el.textContent = present + '/' + boxes.length;
}

function exportCSV() {
  const table = document.querySelector('.att-grid');
  if (!table) return;
  const rows = [];
  const ths = table.querySelectorAll('thead th');
  const header = [];
  ths.forEach((th, i) => {
    if (i === 0) { header.push('Participant'); header.push('ID Number'); }
    else {
      const dayNum = th.querySelector('.day-num')?.textContent?.trim() ?? '';
      const daySub = th.querySelector('.day-date-sub')?.textContent?.trim() ?? '';
      header.push(`${dayNum} (${daySub})`);
    }
  });
  rows.push(header);
  table.querySelectorAll('tbody tr').forEach(tr => {
    const tds = tr.querySelectorAll('td');
    const row = [];
    tds.forEach((td, i) => {
      if (i === 0) {
        row.push(csvEsc(td.querySelector('strong')?.textContent?.trim() ?? ''));
        row.push(csvEsc(td.querySelector('div')?.textContent?.trim() ?? ''));
      } else {
        const cb = td.querySelector('input[type=checkbox]');
        row.push(cb ? (cb.checked ? 'Present' : 'Absent') : '');
      }
    });
    rows.push(row);
  });
  const csv = rows.map(r => r.join(',')).join('\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
  a.download = {!! json_encode(preg_replace('/[^a-z0-9]+/i', '_', $trainingInfo->title ?? 'attendance') . '_attendance.csv') !!};
  a.click();
}
function csvEsc(v) { v = String(v).replace(/"/g,'""'); return /[,"\n]/.test(v)?`"${v}"`:v; }
</script>

@endif
@endsection
