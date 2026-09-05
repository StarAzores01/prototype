@extends('layouts.trainer')

@section('content')
<style>
@media print {
  .no-print { display:none !important; }
  .main-wrap { margin-left:0 !important; }
  .sidebar, .topbar { display:none !important; }
  .page-content { padding:0 !important; }
  .card { box-shadow:none !important; border:1px solid #ddd !important; break-inside:avoid; }
  .report-header { text-align:center; margin-bottom:24px; }
}
.photo-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; }
.photo-item { border-radius:10px; overflow:hidden; border:1px solid var(--gray-200); position:relative; }
.photo-item img { width:100%; height:160px; object-fit:cover; display:block; }
.photo-caption { padding:8px 10px; font-size:12px; color:var(--gray-600); background:var(--surface); }
.photo-del { position:absolute; top:6px; right:6px; background:rgba(0,0,0,.55); color:#fff; border:none; border-radius:6px; padding:3px 8px; font-size:11px; cursor:pointer; }
.photo-del:hover { background:var(--red); }
</style>

<div class="page-header no-print">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Activity Report</span></div>
    <h1>Activity Report</h1>
    <p>Activity summary with evaluation, skills utilization, and documentation</p>
  </div>
  @if($training)
  <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-book-open"></i> Print / Export</button>
  @endif
</div>

<!-- Activity selector -->
<div class="card no-print" style="margin-bottom:20px">
  <div class="card-body">
    <form method="GET" action="{{ route('trainer.activity') }}" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap">
      <div class="form-group" style="flex:1;min-width:220px;margin:0">
        <label class="form-label">Select Activity</label>
        <select name="training" class="form-control" onchange="this.form.submit()">
          <option value="">— Choose Activity —</option>
          @foreach($myTrainings as $t)
          <option value="{{ $t->id }}" {{ $selectedId == $t->id ? 'selected' : '' }}>
            {{ $t->title }} @if($t->status === 'Completed')(Completed)@endif
          </option>
          @endforeach
        </select>
      </div>
    </form>
  </div>
</div>

@if(!$training)
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    <i class="fas fa-file"></i>
    <div style="font-size:15px;font-weight:700;color:var(--text-heading);margin:12px 0 6px">Select an activity to generate the report</div>
    <p style="font-size:13px;color:var(--gray-400)">Choose an activity from the dropdown above.</p>
  </div>
</div>
@else

{{-- ═══════════════ REPORT DOCUMENT ═══════════════ --}}

<!-- Report Header -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="text-align:center;padding:28px">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray-400);margin-bottom:6px">PAThrive · CIT-SLSU Extension Program</div>
    <div style="font-size:22px;font-weight:800;color:var(--text-heading);margin-bottom:4px">Activity Report</div>
    <div style="font-size:16px;font-weight:600;color:var(--blue-primary);margin-bottom:8px">{{ $training->title }}</div>
    <div style="font-size:13px;color:var(--gray-500)">
      {{ $training->area }} &nbsp;·&nbsp;
      {{ $training->date_start?->format('Y-m-d') ?? '—' }}
      @if($training->date_end) to {{ $training->date_end->format('Y-m-d') }}@endif
      &nbsp;·&nbsp; Status: <strong>{{ $training->status }}</strong>
    </div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:6px">Generated: {{ now()->format('F d, Y') }} &nbsp;·&nbsp; Trainer: {{ $trainerName }}</div>
  </div>
</div>

<!-- 1. Participants -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">1. Participants ({{ $participants->count() }})</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>ID Number</th><th>Age</th><th>Sex</th><th>Address</th></tr></thead>
      <tbody>
      @forelse($participants as $p)
      <tr>
        <td style="color:var(--gray-400)">{{ $loop->iteration }}</td>
        <td><strong>{{ $p->full_name }}</strong></td>
        <td style="font-size:12px">{{ $p->id_number ?? '—' }}</td>
        <td style="font-size:12px">{{ $p->age ?? '—' }}</td>
        <td style="font-size:12px">{{ $p->sex ?? '—' }}</td>
        <td style="font-size:12px;color:var(--gray-500)">{{ $p->address ?? '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">No participants enrolled.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- 2. Evaluation Summary -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">2. Evaluation Summary</div>
    <div class="card-subtitle">{{ $evalForm ? $evalForm->title : 'No evaluation form sent' }}</div>
  </div>
  @if(!$evalForm || $evalResponses->isEmpty())
  <div class="card-body" style="color:var(--gray-400);font-size:13px">
    {{ !$evalForm ? 'No evaluation form has been sent for this activity.' : 'No responses received yet.' }}
  </div>
  @else
  @php
    $evalFields = $evalForm->fields ?? [];
    $aggregated = [];
    foreach ($evalResponses as $resp) {
        foreach (($resp->responses ?? []) as $fi => $ans) {
            $aggregated[$fi][] = $ans;
        }
    }
  @endphp
  <div class="card-body">
    <div style="font-size:13px;color:var(--gray-500);margin-bottom:16px">
      <strong>{{ $evalResponses->count() }}</strong> of <strong>{{ $participants->count() }}</strong> participants responded.
    </div>
    @foreach($evalFields as $fi => $field)
    <div style="margin-bottom:20px">
      <div style="font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:8px">{{ $fi + 1 }}. {{ $field['label'] }}</div>
      @php $answers = $aggregated[$fi] ?? []; @endphp
      @if(in_array($field['type'], ['radio', 'select']))
        @php $counts = array_count_values($answers); $total = count($answers); @endphp
        @foreach($field['options'] as $opt)
          @php $cnt = $counts[$opt] ?? 0; $pct = $total > 0 ? round($cnt / $total * 100) : 0; @endphp
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
            <div style="width:140px;font-size:12px;color:var(--gray-700)">{{ $opt }}</div>
            <div style="flex:1;background:var(--gray-100);border-radius:4px;height:8px">
              <div style="width:{{ $pct }}%;background:var(--blue-primary);height:8px;border-radius:4px"></div>
            </div>
            <div style="font-size:12px;font-weight:600;color:var(--gray-700);width:50px">{{ $cnt }} ({{ $pct }}%)</div>
          </div>
        @endforeach
      @else
        @forelse($answers as $ans)
          <div style="font-size:13px;color:var(--gray-700);padding:6px 10px;background:var(--gray-50);border-radius:6px;margin-bottom:4px">{{ $ans }}</div>
        @empty
          <div style="font-size:13px;color:var(--gray-400)">No responses.</div>
        @endforelse
      @endif
    </div>
    @endforeach
  </div>
  @endif
</div>

<!-- 3. Skills Utilization Summary -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">3. Skills Utilization Summary</div>
    <div class="card-subtitle">{{ $skillsForm ? $skillsForm->title : 'No skills survey sent' }}</div>
  </div>
  @if(!$skillsForm || $skillsResponses->isEmpty())
  <div class="card-body" style="color:var(--gray-400);font-size:13px">
    {{ !$skillsForm ? 'No skills utilization survey has been sent for this activity.' : 'No responses received yet.' }}
  </div>
  @else
  @php
    $skillsFields = $skillsForm->fields ?? [];
    $skAggregated = [];
    foreach ($skillsResponses as $resp) {
        foreach (($resp->responses ?? []) as $fi => $ans) {
            $skAggregated[$fi][] = $ans;
        }
    }
  @endphp
  <div class="card-body">
    <div style="font-size:13px;color:var(--gray-500);margin-bottom:16px">
      <strong>{{ $skillsResponses->count() }}</strong> of <strong>{{ $participants->count() }}</strong> participants responded.
    </div>
    @foreach($skillsFields as $fi => $field)
    <div style="margin-bottom:20px">
      <div style="font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:8px">{{ $fi + 1 }}. {{ $field['label'] }}</div>
      @php $answers = $skAggregated[$fi] ?? []; @endphp
      @if(in_array($field['type'], ['radio', 'select']))
        @php $counts = array_count_values($answers); $total = count($answers); @endphp
        @foreach($field['options'] as $opt)
          @php $cnt = $counts[$opt] ?? 0; $pct = $total > 0 ? round($cnt / $total * 100) : 0; @endphp
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
            <div style="width:180px;font-size:12px;color:var(--gray-700)">{{ $opt }}</div>
            <div style="flex:1;background:var(--gray-100);border-radius:4px;height:8px">
              <div style="width:{{ $pct }}%;background:var(--green);height:8px;border-radius:4px"></div>
            </div>
            <div style="font-size:12px;font-weight:600;color:var(--gray-700);width:50px">{{ $cnt }} ({{ $pct }}%)</div>
          </div>
        @endforeach
      @else
        @forelse($answers as $ans)
          <div style="font-size:13px;color:var(--gray-700);padding:6px 10px;background:var(--gray-50);border-radius:6px;margin-bottom:4px">{{ $ans }}</div>
        @empty
          <div style="font-size:13px;color:var(--gray-400)">No responses.</div>
        @endforelse
      @endif
    </div>
    @endforeach
  </div>
  @endif
</div>

<!-- 4. Documentation Photos -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">4. Documentation</div>
    <div class="card-subtitle">Activity photos</div>
  </div>
  <div class="card-body">

    <!-- Upload form -->
    <form method="POST" action="{{ route('trainer.activity.store') }}" enctype="multipart/form-data" class="no-print" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-100)">
      @csrf
      <input type="hidden" name="action" value="upload_doc"/>
      <input type="hidden" name="training_id" value="{{ $selectedId }}"/>
      <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
          <label class="form-label">Upload Photo</label>
          <input type="file" name="photo" class="form-control" accept="image/*" required style="padding:6px"/>
        </div>
        <div class="form-group" style="flex:1;min-width:200px;margin:0">
          <label class="form-label">Caption (optional)</label>
          <input type="text" name="caption" class="form-control" placeholder="e.g. Opening ceremony"/>
        </div>
        <button type="submit" class="btn btn-primary" style="height:40px"><i class="fas fa-arrow-up"></i> Upload</button>
      </div>
    </form>

    <!-- Photo grid -->
    @if($photos->isEmpty())
    <div style="text-align:center;padding:24px;color:var(--gray-400);font-size:13px">No photos uploaded yet. Upload documentation photos above.</div>
    @else
    <div class="photo-grid">
      @foreach($photos as $ph)
      <div class="photo-item">
        <img src="{{ route('files.training-doc', $ph) }}" alt="{{ $ph->caption ?? '' }}"/>
        @if($ph->caption)
        <div class="photo-caption">{{ $ph->caption }}</div>
        @endif
        <form method="POST" action="{{ route('trainer.activity.store') }}" class="no-print" onsubmit="return confirm('Remove this photo?')">
          @csrf
          <input type="hidden" name="action" value="delete_doc"/>
          <input type="hidden" name="doc_id" value="{{ $ph->id }}"/>
          <input type="hidden" name="training_id" value="{{ $selectedId }}"/>
          <button type="submit" class="photo-del"><i class="fas fa-trash"></i></button>
        </form>
      </div>
      @endforeach
    </div>
    @endif
  </div>
</div>

@endif
@endsection
