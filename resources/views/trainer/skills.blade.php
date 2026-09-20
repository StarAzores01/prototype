@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Skills Utilization</span></div>
    <h1>Skills Utilization</h1>
    <p>Create and send skills surveys to beneficiaries</p>
  </div>
  @if(!in_array($mode, ['responses', 'builder'], true))
  <a href="{{ route('trainer.evaluation') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  @endif
</div>

@if($mode === 'responses')
{{-- ═══════════════ VIEW RESPONSES ═══════════════ --}}
@php
  $fields = $viewForm->fields ?? [];
  $rate = $viewForm->total_pax > 0 ? round($responses->count() / $viewForm->total_pax * 100) : 0;
@endphp

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px">
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--blue-primary)">{{ (int) $viewForm->total_pax }}</div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Total Participants</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--green)">{{ $responses->count() }}</div>
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

<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">{{ $viewForm->title }}</div>
      <div class="card-subtitle">
        <i class="fas fa-book"></i> {{ $viewTraining->title }}
        @if($viewForm->sent_at)
        &nbsp;·&nbsp; <i class="fas fa-bell"></i> Sent {{ $viewForm->sent_at->format('M d, Y') }}
        @endif
      </div>
    </div>
    <a href="{{ route('trainer.skills') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
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
        @foreach($tally as $opt => $cnt)
          @php $pct = $responses->count() > 0 ? round($cnt / $responses->count() * 100) : 0; @endphp
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
          <div style="min-width:160px;font-size:12.5px;color:var(--gray-700)">{{ $opt }}</div>
          <div style="flex:1;background:var(--gray-100);border-radius:4px;height:8px">
            <div style="width:{{ $pct }}%;background:var(--blue-primary);height:8px;border-radius:4px"></div>
          </div>
          <div style="min-width:60px;font-size:12px;color:var(--gray-600);text-align:right">{{ $cnt }} ({{ $pct }}%)</div>
        </div>
        @endforeach
      @elseif(!empty($tally))
        <div style="display:flex;flex-direction:column;gap:6px">
          @foreach(array_keys($tally) as $ans)
          <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:8px 12px;font-size:13px;color:var(--gray-700)">{{ $ans }}</div>
          @endforeach
        </div>
      @else
        <div style="font-size:13px;color:var(--gray-400)">No answers yet.</div>
      @endif
    </div>
    @endforeach
  </div>
</div>

<!-- Individual responses -->
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

@elseif($mode === 'builder')
{{-- ═══════════════ FORM BUILDER ═══════════════ --}}
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-file-lines"></i> {{ $editForm ? 'Edit' : 'Create' }} Skills Form</div>
      <div class="card-subtitle">For: <strong>{{ $editTraining->title }}</strong></div>
    </div>
    <a href="{{ route('trainer.skills') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('trainer.skills.store') }}" id="formBuilder">
      @csrf
      <input type="hidden" name="action" value="save_form"/>
      <input type="hidden" name="training_id" value="{{ $editTraining->id }}"/>
      <div class="form-group" style="margin-bottom:20px">
        <label class="form-label">Form Title</label>
        <input type="text" name="form_title" class="form-control"
               value="{{ $editForm->title ?? 'Skills Utilization Survey' }}" required/>
      </div>

      @php
        $existingFields = $editForm->fields ?? [];
        if (empty($existingFields)) {
            $existingFields = [
                ['label' => 'Are you currently using the skills learned from this activity?', 'type' => 'radio', 'required' => true, 'options' => ['Yes', 'No', 'Sometimes']],
                ['label' => 'How are you applying the skills? (select all that apply)', 'type' => 'radio', 'required' => false, 'options' => ['Personal use', 'Income-generating activity', 'Employment', 'Not yet applied']],
                ['label' => 'Please describe how you are using the skills', 'type' => 'textarea', 'required' => false, 'options' => []],
            ];
        }
      @endphp
      <div id="fieldsContainer">
        @foreach($existingFields as $fi => $field)
          @php
            $ftype = $field['type'] ?? 'text';
            $fopts = implode("\n", $field['options'] ?? []);
          @endphp
        <div class="field-row" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
          <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
            <div class="form-group" style="flex:2;min-width:200px;margin:0">
              <label class="form-label">Question</label>
              <input type="text" name="field_label[]" class="form-control"
                     value="{{ $field['label'] }}" required/>
            </div>
            <div class="form-group" style="min-width:140px;margin:0">
              <label class="form-label">Type</label>
              <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
                @foreach(['text','textarea','radio','select'] as $ft)
                <option value="{{ $ft }}" {{ $ftype === $ft ? 'selected' : '' }}>{{ ucfirst($ft) }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group" style="min-width:100px;margin:0;padding-top:22px">
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="field_required[{{ $fi }}]" {{ !empty($field['required']) ? 'checked' : '' }}/> Required
              </label>
            </div>
            <div style="padding-top:22px">
              <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.field-row').remove()"><i class="fas fa-trash"></i></button>
            </div>
          </div>
          <div class="options-wrap" style="margin-top:10px;{{ in_array($ftype, ['radio', 'select']) ? '' : 'display:none' }}">
            <label class="form-label">Options (one per line)</label>
            <textarea name="field_options[]" class="form-control" rows="3">{{ $fopts }}</textarea>
          </div>
          @if(!in_array($ftype, ['radio', 'select']))
          <textarea name="field_options[]" style="display:none" rows="1"></textarea>
          @endif
        </div>
        @endforeach
      </div>

      <div style="display:flex;gap:10px;margin-top:16px">
        <button type="button" class="btn btn-outline" onclick="addField()"><i class="fas fa-plus"></i> Add Question</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Form</button>
      </div>
    </form>
  </div>
</div>

<script>
let fc = {{ count($existingFields) }};
function addField() {
  const i = fc++;
  document.getElementById('fieldsContainer').insertAdjacentHTML('beforeend', `
  <div class="field-row" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
    <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
      <div class="form-group" style="flex:2;min-width:200px;margin:0">
        <label class="form-label">Question</label>
        <input type="text" name="field_label[]" class="form-control" placeholder="Enter question" required/>
      </div>
      <div class="form-group" style="min-width:140px;margin:0">
        <label class="form-label">Type</label>
        <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
          <option value="text">Text</option>
          <option value="textarea">Textarea</option>
          <option value="radio">Radio</option>
          <option value="select">Select</option>
        </select>
      </div>
      <div class="form-group" style="min-width:100px;margin:0;padding-top:22px">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
          <input type="checkbox" name="field_required[${i}]"/> Required
        </label>
      </div>
      <div style="padding-top:22px">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.field-row').remove()"><i class="fas fa-trash"></i></button>
      </div>
    </div>
    <div class="options-wrap" style="margin-top:10px;display:none">
      <label class="form-label">Options (one per line)</label>
      <textarea name="field_options[]" class="form-control" rows="3"></textarea>
    </div>
    <textarea name="field_options[]" style="display:none" rows="1"></textarea>
  </div>`);
}
function toggleOptions(sel) {
  const row = sel.closest('.field-row');
  const ow  = row.querySelector('.options-wrap');
  if (ow) ow.style.display = ['radio','select'].includes(sel.value) ? '' : 'none';
}
document.querySelectorAll('.field-type-sel').forEach(s => toggleOptions(s));
</script>

@else
{{-- ═══════════════ MAIN VIEW ═══════════════ --}}

<!-- Beneficiary Progress Entries -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-chart-line"></i> Beneficiary Progress Entries</div>
      <div class="card-subtitle">Read-only — how your beneficiaries are applying their skills over time</div>
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
    $visibleEntries = $activityEntries->take(3);
    $moreEntries = $activityEntries->slice(3);
  @endphp
  <div style="{{ $loop->last ? '' : 'border-bottom:1px solid var(--gray-100)' }}">
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
          @foreach($visibleEntries as $e)
          <tr>
            <td>{{ $e->beneficiary->full_name ?? '—' }}</td>
            <td style="white-space:nowrap">{{ $e->activity_date->format('M d, Y') }}</td>
            <td><span class="badge badge-active">{{ $e->outcome_type }}</span></td>
            <td style="white-space:nowrap;font-weight:600">&#8369;{{ number_format((float) $e->service_fee, 2) }}</td>
            <td style="max-width:220px;white-space:normal;color:var(--gray-600)">{{ $e->remarks ?: '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if($moreEntries->isNotEmpty())
    <details style="padding:0 20px 14px">
      <summary style="cursor:pointer;font-size:12px;font-weight:600;color:var(--blue-primary);padding:8px 0;list-style:none">
        <i class="fas fa-chevron-down" style="font-size:10px;margin-right:4px"></i>Show {{ $moreEntries->count() }} more
      </summary>
      <div class="table-wrap" style="margin-top:4px">
        <table>
          <tbody>
            @foreach($moreEntries as $e)
            <tr>
              <td>{{ $e->beneficiary->full_name ?? '—' }}</td>
              <td style="white-space:nowrap">{{ $e->activity_date->format('M d, Y') }}</td>
              <td><span class="badge badge-active">{{ $e->outcome_type }}</span></td>
              <td style="white-space:nowrap;font-weight:600">&#8369;{{ number_format((float) $e->service_fee, 2) }}</td>
              <td style="max-width:220px;white-space:normal;color:var(--gray-600)">{{ $e->remarks ?: '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </details>
    @endif
  </div>
  @endforeach
  @endif
</div>

@endif
@endsection
