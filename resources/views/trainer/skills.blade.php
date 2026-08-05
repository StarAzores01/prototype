@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Skills Utilization</span></div>
    <h1>Skills Utilization</h1>
    <p>Create and send skills surveys to beneficiaries</p>
  </div>
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
                ['label' => 'Are you currently using the skills learned from this training?', 'type' => 'radio', 'required' => true, 'options' => ['Yes', 'No', 'Sometimes']],
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

<!-- Overview bars -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">Skills Utilization Overview</div></div>
  <div class="card-body">
    @foreach([
      ['Personal Use', $overview['personal']],
      ['Income-Generating', $overview['income']],
      ['Employment', $overview['employment']],
    ] as [$label, $pct])
    <div class="skills-row">
      <div class="skills-label">{{ $label }}</div>
      <div style="flex:1"><div class="progress-bar-wrap"><div class="progress-bar" style="width:{{ $pct }}%"></div></div></div>
      <div class="skills-pct">{{ $pct }}%</div>
    </div>
    @endforeach
  </div>
</div>

<!-- Per-training table -->
<div class="card">
  <div class="card-header"><div class="card-title">Skills Forms by Training</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Training</th>
          <th>Date</th>
          <th>Participants</th>
          <th>Answered</th>
          <th>Response Rate</th>
          <th>Form</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($trainingSummary as $row)
        @php $rate = $row->total_pax > 0 ? round($row->answered / $row->total_pax * 100) : 0; @endphp
      <tr>
        <td><strong>{{ $row->title }}</strong></td>
        <td style="font-size:12px;color:var(--gray-400)">{{ $row->date_start?->format('Y-m-d') ?? '—' }}</td>
        <td><strong>{{ (int) $row->total_pax }}</strong></td>
        <td style="color:var(--green);font-weight:700">{{ (int) $row->answered }}</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div style="flex:1;background:var(--gray-100);border-radius:4px;height:6px;min-width:80px">
              <div style="width:{{ $rate }}%;background:var(--blue-primary);height:6px;border-radius:4px"></div>
            </div>
            <span style="font-size:12px;font-weight:600;color:var(--gray-700)">{{ $rate }}%</span>
          </div>
        </td>
        <td>
          @if($row->form)
            <span class="badge badge-active"><i class="fas fa-file-lines"></i> Created</span>
            @if($row->form->sent_at)
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">Sent {{ $row->form->sent_at->format('M d') }}</div>
            @endif
          @else
            <span class="badge badge-pending">No form</span>
          @endif
        </td>
        <td>
          <div class="action-btns">
            <a href="{{ route('trainer.skills') }}?edit_form={{ $row->id }}"
               class="btn btn-sm btn-outline">
              {!! $row->form ? '<i class="fas fa-pen"></i> Edit' : '<i class="fas fa-plus"></i> Create' !!}
            </a>
            @if($row->form)
            <a href="{{ route('trainer.skills') }}?view_responses={{ $row->form->id }}"
               class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> Responses
              @if($row->answered > 0)
              <span style="background:var(--green);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:4px">{{ (int) $row->answered }}</span>
              @endif
            </a>
            <form method="POST" action="{{ route('trainer.skills.store') }}" style="display:inline"
                  onsubmit="return confirm('Send this skills survey to all beneficiaries of this training?')">
              @csrf
              <input type="hidden" name="action" value="send_form"/>
              <input type="hidden" name="training_id" value="{{ $row->id }}"/>
              <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-bell"></i> Send</button>
            </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">No trainings assigned yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

@endif
@endsection
