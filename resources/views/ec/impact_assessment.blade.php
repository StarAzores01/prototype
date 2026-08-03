@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; Assessment &#8250; <span>Impact Assessment</span></div>
    <h1>Impact Assessment</h1>
    <p>Manage evaluator submissions and participant survey forms</p>
  </div>
  @if($tab === 'surveys' && !($viewForm ?? null))
  <button class="btn btn-primary" onclick="openModal('createSurveyForm')">&#43; Create Survey Form</button>
  @endif
</div>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--gray-200)">
  <a href="{{ route('ec.impact_assessment', ['tab' => 'evaluators']) }}"
     style="padding:10px 20px;font-size:13.5px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;{{ $tab === 'evaluators' ? 'background:var(--blue-primary);color:#fff' : 'color:var(--gray-500);background:transparent' }}">
    &#128203; Evaluator Submissions
  </a>
  <a href="{{ route('ec.impact_assessment', ['tab' => 'surveys']) }}"
     style="padding:10px 20px;font-size:13.5px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;{{ $tab === 'surveys' ? 'background:var(--blue-primary);color:#fff' : 'color:var(--gray-500);background:transparent' }}">
    &#128221; Participant Surveys
  </a>
</div>

@if($tab === 'evaluators')
{{-- ═══════════════ EVALUATOR SUBMISSIONS TAB ═══════════════ --}}

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('ec.impact_assessment') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="tab" value="evaluators"/>
      <input type="text" name="q" class="form-control" placeholder="Search by title or evaluator..." value="{{ $q }}" style="max-width:280px"/>
      <select name="status" class="form-control" style="max-width:160px">
        <option value="">All Statuses</option>
        <option value="Submitted" {{ $filterStatus === 'Submitted' ? 'selected' : '' }}>Submitted</option>
        <option value="Reviewed" {{ $filterStatus === 'Reviewed' ? 'selected' : '' }}>Reviewed</option>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">&#128269; Filter</button>
      @if($q || $filterStatus)<a href="{{ route('ec.impact_assessment', ['tab' => 'evaluators']) }}" class="btn btn-outline btn-sm">&#10005; Clear</a>@endif
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    @if($assessments->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--gray-400)">No impact assessments received yet.</div>
    @else
    <table class="data-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Evaluator</th>
          <th>Department</th>
          <th>Training</th>
          <th>File</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($assessments as $a)
        <tr>
          <td>
            <div style="font-weight:600">{{ $a->title }}</div>
            @if($a->description)
            <div style="font-size:11.5px;color:var(--gray-400);margin-top:2px">{{ \Illuminate\Support\Str::limit($a->description, 80) }}</div>
            @endif
          </td>
          <td>
            <div style="font-weight:600">{{ $a->evaluator->first_name ?? '' }} {{ $a->evaluator->last_name ?? '' }}</div>
            <div style="font-size:11px;color:var(--gray-400)">{{ $a->evaluator->id_number ?? '' }}</div>
          </td>
          <td>{{ $a->evaluator->position ?? '—' }}</td>
          <td>{{ $a->training->title ?? '—' }}</td>
          <td>
            @if($a->file_name)
            <a href="{{ asset('storage/uploads/'.$a->file_name) }}" target="_blank" class="btn btn-outline btn-sm">&#128196; {{ $a->original_name ?? 'View' }}</a>
            @else
            <span style="color:var(--gray-300)">No file</span>
            @endif
          </td>
          <td><span class="badge {{ $a->status === 'Reviewed' ? 'badge-success' : 'badge-info' }}">{{ $a->status }}</span></td>
          <td>{{ $a->submitted_at?->format('M d, Y') ?? '—' }}</td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            @if($a->status !== 'Reviewed')
            <button class="btn btn-sm btn-primary" onclick="openReviewModal({{ $a->id }}, {!! json_encode($a->title) !!})">&#9989; Review</button>
            @else
            <span style="font-size:12px;color:var(--green)">&#9989; Reviewed</span>
            @endif
            <form method="POST" action="{{ route('ec.impact_assessment.store') }}" onsubmit="return confirm('Delete this assessment?')" style="display:inline">
              @csrf
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="assessment_id" value="{{ $a->id }}"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465;</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>

@else
{{-- ═══════════════ PARTICIPANT SURVEYS TAB ═══════════════ --}}

@if($viewForm)
<!-- ── Response detail view ── -->
<div style="margin-bottom:16px">
  <a href="{{ route('ec.impact_assessment', ['tab' => 'surveys']) }}" class="btn btn-outline btn-sm">&#8592; Back to Survey Forms</a>
</div>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div>
      <div class="card-title">{{ $viewForm->title }}</div>
      <div class="card-subtitle">&#128218; {{ $viewForm->training->title ?? '—' }} &middot; Sent {{ $viewForm->sent_at?->format('M d, Y') ?? '—' }}</div>
    </div>
  </div>
</div>

@if($responses->isEmpty())
<div class="card">
  <div class="card-body" style="text-align:center;padding:40px;color:var(--gray-400)">
    No participants have submitted this assessment yet.
  </div>
</div>
@else
@php $fields = $viewForm->fields ?? []; @endphp
@foreach($responses as $resp)
<div class="card" style="margin-bottom:16px">
  <div class="card-header">
    <div>
      <div style="font-weight:700;color:var(--navy)">{{ $resp->beneficiary->first_name }} {{ $resp->beneficiary->last_name }}</div>
      <div style="font-size:12px;color:var(--gray-400)">{{ $resp->beneficiary->username ?? '' }} &middot; Submitted {{ $resp->submitted_at->format('M d, Y') }}</div>
    </div>
    <span class="badge badge-completed">&#9989; Submitted</span>
  </div>
  <div class="card-body">
    @foreach($fields as $fi => $field)
      @php $val = $resp->responses[$fi] ?? ''; @endphp
      <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:12.5px;font-weight:700;color:var(--gray-500);margin-bottom:4px">{{ $fi + 1 }}. {{ $field['label'] }}</div>
        <div style="font-size:13.5px;color:var(--navy)">
          @if($val !== '')
            {{ is_array($val) ? implode(', ', $val) : $val }}
          @else
            <span style="color:var(--gray-300)">—</span>
          @endif
        </div>
      </div>
    @endforeach
  </div>
</div>
@endforeach
@endif

@else
<!-- ── Survey forms list ── -->
@if($surveyForms->isEmpty())
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px;color:var(--gray-400)">
    &#128221;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No participant survey forms yet</div>
    <p style="font-size:13px">Click <strong>Create Survey Form</strong> to build and send an impact assessment to participants.</p>
  </div>
</div>
@else
<div class="card">
  <div class="card-body" style="padding:0">
    <table class="data-table">
      <thead>
        <tr>
          <th>Form Title</th>
          <th>Training</th>
          <th>Sent</th>
          <th>Completion</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($surveyForms as $sf)
          @php
            $total = (int) $sf->total_participants;
            $done = (int) $sf->responses_count;
            $pct = $total > 0 ? round($done / $total * 100) : 0;
          @endphp
          <tr>
            <td style="font-weight:600">{{ $sf->title }}</td>
            <td>{{ $sf->training->title ?? '—' }}</td>
            <td>{{ $sf->sent_at?->format('M d, Y') ?? '—' }}</td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="flex:1;background:#E2E8F0;border-radius:99px;height:8px;min-width:80px">
                  <div style="width:{{ $pct }}%;background:{{ $pct === 100 ? 'var(--green)' : 'var(--blue-primary)' }};height:8px;border-radius:99px;transition:width .3s"></div>
                </div>
                <span style="font-size:12px;font-weight:700;color:{{ $pct === 100 ? 'var(--green)' : 'var(--navy)' }};white-space:nowrap">
                  {{ $done }}/{{ $total }} ({{ $pct }}%)
                </span>
              </div>
            </td>
            <td style="display:flex;gap:6px;flex-wrap:wrap">
              <a href="{{ route('ec.impact_assessment', ['tab' => 'surveys', 'view_form' => $sf->id]) }}" class="btn btn-sm btn-outline">&#128065; View Responses</a>
              <form method="POST" action="{{ route('ec.impact_assessment.store') }}" onsubmit="return confirm('Delete this survey form and all responses?')" style="display:inline">
                @csrf
                <input type="hidden" name="action" value="delete_form"/>
                <input type="hidden" name="form_id" value="{{ $sf->id }}"/>
                <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465;</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif
@endif

@endif

<!-- ── Review Modal (evaluator) ── -->
<div class="modal-overlay" id="modal-reviewAssessment">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">&#9989; Mark as Reviewed</div>
      <button class="modal-close" onclick="closeModal('reviewAssessment')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.impact_assessment.store') }}">
      @csrf
      <input type="hidden" name="action" value="review"/>
      <input type="hidden" name="assessment_id" id="review_id"/>
      <div class="modal-body">
        <p id="review_title" style="font-weight:600;color:var(--navy);margin-bottom:14px"></p>
        <div class="form-group">
          <label class="form-label">Notes / Feedback (optional)</label>
          <textarea name="ec_notes" class="form-control" rows="4" placeholder="Add any notes or feedback for the evaluator..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('reviewAssessment')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#9989; Confirm Review</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Create Survey Form Modal ── -->
<div class="modal-overlay" id="modal-createSurveyForm">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <div class="modal-title">&#128221; Create Impact Assessment Survey</div>
      <button class="modal-close" onclick="closeModal('createSurveyForm')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.impact_assessment.store') }}">
      @csrf
      <input type="hidden" name="action" value="create_form"/>
      <div class="modal-body" style="max-height:70vh;overflow-y:auto">
        <div class="form-group">
          <label class="form-label">Training <span style="color:var(--red)">*</span></label>
          <select name="training_id" class="form-control" required>
            <option value="">— Select Training —</option>
            @foreach($trainings ?? [] as $t)
            <option value="{{ $t->id }}">{{ $t->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Form Title</label>
          <input type="text" name="form_title" class="form-control" value="Impact Assessment Survey" placeholder="e.g. Post-Training Impact Assessment"/>
        </div>

        <div style="border-top:1px solid var(--gray-200);padding-top:16px;margin-top:4px">
          <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:12px">Questions</div>
          <div id="fieldsContainer"></div>
          <button type="button" class="btn btn-outline btn-sm" onclick="addField()" style="margin-top:8px">&#43; Add Question</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('createSurveyForm')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#128228; Send to Participants</button>
      </div>
    </form>
  </div>
</div>

<script>
function openReviewModal(id, title) {
  document.getElementById('review_id').value = id;
  document.getElementById('review_title').textContent = title;
  openModal('reviewAssessment');
}

let fieldCount = 0;
function addField() {
  const i = fieldCount++;
  const div = document.createElement('div');
  div.style.cssText = 'background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:10px;padding:14px;margin-bottom:12px';
  div.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <span style="font-size:12px;font-weight:700;color:var(--gray-500)">Question ${i+1}</span>
      <button type="button" onclick="this.closest('div[data-field]').remove()" style="background:none;border:none;color:#EF4444;cursor:pointer;font-size:16px">&#10005;</button>
    </div>
    <div class="form-group">
      <label class="form-label">Label *</label>
      <input type="text" name="field_label[${i}]" class="form-control" placeholder="e.g. How has this training impacted your livelihood?" required/>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label class="form-label">Type</label>
        <select name="field_type[${i}]" class="form-control" onchange="toggleOptions(this,${i})">
          <option value="text">Short Text</option>
          <option value="textarea">Long Text</option>
          <option value="radio">Multiple Choice</option>
          <option value="select">Dropdown</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
        <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;cursor:pointer">
          <input type="checkbox" name="field_required[${i}]" value="1" style="width:15px;height:15px"/> Required
        </label>
      </div>
    </div>
    <div class="form-group" id="opts_${i}" style="display:none">
      <label class="form-label">Options <span style="font-weight:400;color:#94A3B8">(one per line)</span></label>
      <textarea name="field_options[${i}]" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
    </div>`;
  div.setAttribute('data-field', i);
  document.getElementById('fieldsContainer').appendChild(div);
}
function toggleOptions(sel, i) {
  const show = sel.value === 'radio' || sel.value === 'select';
  document.getElementById('opts_'+i).style.display = show ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.querySelector('[onclick="openModal(\'createSurveyForm\')"]');
  if (btn) {
    btn.addEventListener('click', function() {
      if (document.getElementById('fieldsContainer').children.length === 0) {
        setTimeout(addField, 80);
      }
    });
  }
});
</script>
@endsection
