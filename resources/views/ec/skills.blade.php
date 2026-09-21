@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Skills Utilization</span></div>
    <h1>Skills Utilization</h1>
    <p>View beneficiary responses to skills surveys across all activities</p>
  </div>
  @if($viewForm)
  <a href="{{ route('ec.skills') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to All Forms</a>
  @else
  <a href="{{ route('ec.evaluation') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  @endif
</div>

@if(!$viewForm)
{{-- ═══════════════ BENEFICIARY PROGRESS MONITORING ═══════════════ --}}
<div style="margin-bottom:20px">
  <div style="position:relative;max-width:400px">
    <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none"></i>
    <input type="text" id="skillsEntrySearch" placeholder="Search beneficiary or activity..." oninput="filterSkillsEntries()"
      style="width:100%;padding:9px 12px 9px 36px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);font-size:13px;color:var(--gray-800);background:var(--white);outline:none"
      onfocus="this.style.borderColor='var(--blue-primary)'" onblur="this.style.borderColor='var(--gray-200)'"/>
  </div>
</div>
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-chart-line"></i> Beneficiary Progress Entries</div>
      <div class="card-subtitle">Read-only — how beneficiaries are applying their skills over time</div>
    </div>
    <div style="text-align:right">
      <div style="font-size:11px;color:var(--gray-400)">Total Recorded Earnings</div>
      <div style="font-size:18px;font-weight:800;color:var(--green)">&#8369;{{ number_format((float) $totalRecordedEarnings, 2) }}</div>
    </div>
  </div>

  @if($progressEntries->isEmpty())
  <div class="empty-state" style="padding:32px 24px;text-align:center">
    <i class="fas fa-chart-line" style="font-size:24px;color:var(--gray-300)"></i>
    <p style="margin-top:8px;color:var(--gray-400)">No beneficiary progress entries recorded yet.</p>
  </div>
  @else
  @foreach($entriesByActivity as $activityName => $activityEntries)
  @php
    // One row per beneficiary (their most recent entry for this
    // activity), not one row per submission — a beneficiary who logged
    // progress several times used to repeat as several near-identical
    // rows. Older entries are still all there, just tucked behind the
    // chevron toggle so the table reads as one line per person by
    // default. $activityEntries is already sorted newest-first (see
    // SkillsController::index()), so groupBy preserves that ordering
    // and ->first() below is always the latest entry.
    $entriesByBeneficiary = $activityEntries->groupBy(fn ($e) => $e->beneficiary_id ?? 'unlinked-' . $e->id);
  @endphp
  <div class="skills-activity-group" data-activity="{{ strtolower($activityName) }}" style="{{ $loop->last ? '' : 'border-bottom:1px solid var(--gray-100)' }}">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:var(--gray-50)">
      <div style="font-size:13px;font-weight:700;color:var(--text-heading)">
        <i class="fas fa-layer-group" style="color:var(--gray-400);margin-right:6px"></i>{{ $activityName }}
      </div>
      <div style="font-size:12px;color:var(--gray-400)">
        {{ $activityEntries->count() }} entr{{ $activityEntries->count() !== 1 ? 'ies' : 'y' }}
        &nbsp;·&nbsp;
        <span style="font-weight:700;color:var(--green)">&#8369;{{ number_format((float) $activityEntries->sum('service_fee'), 2) }}</span>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Beneficiary</th>
            <th>Date</th>
            <th>Progress / Outcome</th>
            <th>Service Fee / Earnings</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          @foreach($entriesByBeneficiary as $beneficiaryKey => $beneficiaryEntries)
          @php
            $latest = $beneficiaryEntries->first();
            $olderEntries = $beneficiaryEntries->skip(1);
            $beneficiaryName = $latest->beneficiary->full_name ?? '—';
          @endphp
          <tr class="skills-entry-row skills-beneficiary-summary" data-beneficiary="{{ $beneficiaryKey }}"
            data-search="{{ strtolower($beneficiaryName . ' ' . $activityName . ' ' . $latest->outcome_type) }}">
            <td>
              @if($olderEntries->isNotEmpty())
              <button type="button" class="skills-expand-btn" onclick="toggleBeneficiaryEntries(this)"
                title="Show all {{ $beneficiaryEntries->count() }} entries for {{ $beneficiaryName }}"
                style="background:none;border:none;cursor:pointer;color:var(--gray-400);padding:2px 4px;margin-right:2px;vertical-align:middle">
                <i class="fas fa-chevron-down" style="font-size:11px"></i>
              </button>
              @endif
              {{ $beneficiaryName }}
              @if($olderEntries->isNotEmpty())
              <span style="font-size:10.5px;font-weight:700;color:var(--blue-primary);background:rgba(26,86,219,.08);border-radius:20px;padding:1px 7px;margin-left:4px">&times;{{ $beneficiaryEntries->count() }}</span>
              @endif
            </td>
            <td style="white-space:nowrap">{{ $latest->activity_date->format('M d, Y') }}</td>
            <td><span class="badge badge-active">{{ $latest->outcome_type }}</span></td>
            <td style="white-space:nowrap;font-weight:600">&#8369;{{ number_format((float) $latest->service_fee, 2) }}</td>
            <td style="max-width:220px;white-space:normal;color:var(--gray-600)">{{ $latest->remarks ?: '—' }}</td>
          </tr>
          @foreach($olderEntries as $e)
          <tr class="skills-entry-row skills-extra-row" data-beneficiary="{{ $beneficiaryKey }}"
            data-search="{{ strtolower($beneficiaryName . ' ' . $activityName . ' ' . $e->outcome_type) }}" style="display:none;background:var(--gray-50)">
            <td style="padding-left:30px;color:var(--gray-500)"><i class="fas fa-turn-up fa-rotate-90" style="font-size:10px;margin-right:6px;color:var(--gray-300)"></i>{{ $beneficiaryName }}</td>
            <td style="white-space:nowrap">{{ $e->activity_date->format('M d, Y') }}</td>
            <td><span class="badge badge-active">{{ $e->outcome_type }}</span></td>
            <td style="white-space:nowrap;font-weight:600">&#8369;{{ number_format((float) $e->service_fee, 2) }}</td>
            <td style="max-width:220px;white-space:normal;color:var(--gray-600)">{{ $e->remarks ?: '—' }}</td>
          </tr>
          @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endforeach
  @endif
  <div id="skillsEntryNoMatch" style="display:none;padding:40px 24px;text-align:center;color:var(--gray-400)">No entries match your search.</div>
</div>

<script>
function toggleBeneficiaryEntries(btn) {
  var row = btn.closest('tr');
  var table = btn.closest('table');
  var bid = row.dataset.beneficiary;
  var expand = !row.classList.contains('expanded');
  row.classList.toggle('expanded', expand);
  var icon = btn.querySelector('i');
  icon.classList.toggle('fa-chevron-down', !expand);
  icon.classList.toggle('fa-chevron-up', expand);
  table.querySelectorAll('.skills-extra-row[data-beneficiary="' + bid + '"]').forEach(function (r) {
    r.style.display = expand ? '' : 'none';
  });
}

function filterSkillsEntries() {
  const q = document.getElementById('skillsEntrySearch').value.toLowerCase().trim();
  const groups = document.querySelectorAll('.skills-activity-group');
  let anyGroupVisible = false;
  groups.forEach(group => {
    let groupHasMatch = false;
    group.querySelectorAll('.skills-beneficiary-summary').forEach(summary => {
      const bid = summary.dataset.beneficiary;
      const extras = group.querySelectorAll('.skills-extra-row[data-beneficiary="' + bid + '"]');
      let extraMatch = false;
      extras.forEach(x => { if (q && x.dataset.search.includes(q)) extraMatch = true; });
      const summaryMatch = !q || summary.dataset.search.includes(q);
      const rowMatch = summaryMatch || extraMatch;
      summary.style.display = rowMatch ? '' : 'none';
      if (rowMatch) groupHasMatch = true;
      // While actively searching, auto-expand a beneficiary whose match
      // is hidden in an older entry, so the matching row is visible
      // instead of collapsed away; clearing the search collapses again.
      const shouldExpand = q ? extraMatch : false;
      summary.classList.toggle('expanded', shouldExpand);
      const icon = summary.querySelector('.skills-expand-btn i');
      if (icon) {
        icon.classList.toggle('fa-chevron-down', !shouldExpand);
        icon.classList.toggle('fa-chevron-up', shouldExpand);
      }
      extras.forEach(x => {
        const match = !q || x.dataset.search.includes(q);
        x.style.display = (rowMatch && (shouldExpand || match)) ? '' : 'none';
      });
    });
    group.style.display = groupHasMatch ? '' : 'none';
    if (groupHasMatch) anyGroupVisible = true;
  });
  const noMatch = document.getElementById('skillsEntryNoMatch');
  if (noMatch) noMatch.style.display = (groups.length > 0 && !anyGroupVisible) ? '' : 'none';
}
</script>
@endif

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
    <div style="font-size:28px;font-weight:800;color:var(--text-heading)">{{ $rate }}%</div>
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
        <i class="fas fa-book"></i> {{ $viewForm->training->title ?? '—' }}
        &nbsp;·&nbsp; <i class="fas fa-user"></i> {{ $viewForm->training->trainer->full_name ?? 'N/A' }}
        @if($viewForm->sent_at)
        &nbsp;·&nbsp; <i class="fas fa-bell"></i> Sent {{ $viewForm->sent_at->format('M d, Y') }}
        @endif
      </div>
    </div>
  </div>
</div>

@if($responses->isEmpty())
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    <i class="fas fa-chart-line"></i>
    <div style="font-size:15px;font-weight:700;color:var(--text-heading);margin:12px 0 6px">No responses yet</div>
    <p style="font-size:13px;color:var(--gray-400)">Beneficiaries haven't submitted this survey yet.</p>
  </div>
</div>
@else

<!-- Per-question summary -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-chart-column"></i> Response Summary by Question</div></div>
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
        <div style="font-size:13px;font-weight:700;color:var(--text-heading);margin-bottom:10px">
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
  <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Individual Responses</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Beneficiary</th>
          <th>Submitted</th>
          @foreach($fields as $field)
          <th style="min-width:160px">{{ \Illuminate\Support\Str::limit($field['label'], 40, '…') }}</th>
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

@endif
@endsection
