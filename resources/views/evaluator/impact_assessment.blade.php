@extends('layouts.evaluator')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <a href="{{ route('evaluator.evaluation') }}" style="color:var(--blue-primary)">Evaluation</a> <i class="fas fa-chevron-right"></i> <span>Impact Assessment</span></div>
    <h1>Impact Assessment</h1>
    <p>Submit assessment forms to the Extension Coordinator</p>
  </div>
  <div style="display:flex;gap:10px">
    <a href="{{ route('evaluator.evaluation') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <button class="btn btn-primary" onclick="openModal('submitAssessment')"><i class="fas fa-plus"></i> Submit Assessment</button>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:20px">
  <i class="fas fa-square-check"></i> {{ session('success') }}
</div>
@elseif(session('error'))
<div class="alert alert-danger" style="margin-bottom:20px">
  <i class="fas fa-triangle-exclamation"></i> {{ session('error') }}
</div>
@endif

<!-- Search -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('evaluator.impact_assessment') }}" style="display:flex;gap:10px;align-items:center">
      <input type="text" name="q" class="form-control" placeholder="Search assessments..." value="{{ $q }}" style="max-width:320px"/>
      <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
      @if($q)<a href="{{ route('evaluator.impact_assessment') }}" class="btn btn-outline btn-sm"><i class="fas fa-xmark"></i> Clear</a>@endif
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    @if($assessments->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--gray-400)">
      No assessments yet. Click <strong>Submit Assessment</strong> to get started.
    </div>
    @else
    <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Activity</th>
          <th>File</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Due Date</th>
          <th>Participants</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($assessments as $a)
        <tr>
          <td style="font-weight:600">{{ $a->title }}</td>
          <td>
            {{ $a->training->title ?? '—' }}
          </td>
          <td>
            @if($a->form_data)
            <button type="button" class="btn btn-outline btn-sm" onclick="openModal('viewForm{{ $a->id }}')"><i class="fas fa-list-check"></i> Form &middot; {{ count($a->form_data) }} question{{ count($a->form_data) === 1 ? '' : 's' }}</button>
            @elseif($a->file_name)
            <a href="{{ route('files.impact-assessment', $a) }}" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-file"></i> {{ $a->original_name ?? 'View' }}</a>
            @else
            <span style="color:var(--gray-400)">No file</span>
            @endif
          </td>
          <td><span class="badge {{ $a->status === 'Reviewed' ? 'badge-success' : ($a->status === 'Submitted' ? 'badge-info' : 'badge-warning') }}">{{ $a->status }}</span></td>
          <td>{{ $a->submitted_at?->format('M d, Y') ?? '—' }}</td>
          <td>{{ $a->due_date?->format('M d, Y') ?? '—' }}</td>
          <td>
            @if($a->participant_form_id)
            <span class="badge badge-success"><i class="fas fa-paper-plane"></i> Sent</span>
            @else
            <span class="badge badge-warning">Waiting for EC</span>
            @endif
          </td>
          <td>
            @if($a->status !== 'Reviewed')
            <form method="POST" action="{{ route('evaluator.impact_assessment.store') }}" onsubmit="return confirm('Delete this assessment?')" style="display:inline">
              @csrf
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="assessment_id" value="{{ $a->id }}"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer"><i class="fas fa-trash"></i> Delete</button>
            </form>
            @else
            <span style="font-size:12px;color:var(--gray-400)">Reviewed</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    @endif
  </div>
</div>

<!-- Submit Modal -->
<div class="modal-overlay" id="modal-submitAssessment">
  <div class="modal" style="max-width:640px;max-height:92vh;display:flex;flex-direction:column">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-clipboard-list"></i> Submit Impact Assessment</div>
      <button class="modal-close" onclick="closeModal('submitAssessment')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('evaluator.impact_assessment.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="action" value="submit"/>
      <div class="modal-body" style="overflow-y:auto">
        <div class="form-group">
          <label class="form-label">Title <span style="color:var(--red)">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Post-Activity Impact Assessment – Q1 2026" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Activity <span style="color:var(--red)">*</span></label>
          <select name="training_id" class="form-control" required>
            <option value="" disabled selected>— Select a completed activity —</option>
            @foreach($trainings as $t)
            <option value="{{ $t->id }}">{{ $t->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Due Date for Participants <span style="color:var(--red)">*</span></label>
          <input type="date" name="due_date" class="form-control" min="{{ now()->format('Y-m-d') }}" required/>
          <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">The date participants must answer by. The sent date is recorded automatically when you submit.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Description / Notes</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the assessment..."></textarea>
        </div>
        <input type="hidden" name="mode" id="iaMode" value="file"/>
        <div style="display:flex;gap:6px;margin-bottom:14px;border-bottom:2px solid var(--gray-200)">
          <button type="button" id="iaTabFile" onclick="iaSetMode('file')" style="padding:8px 16px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;background:var(--blue-primary);color:#fff"><i class="fas fa-upload"></i> Upload a File</button>
          <button type="button" id="iaTabForm" onclick="iaSetMode('form')" style="padding:8px 16px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;background:transparent;color:var(--gray-500)"><i class="fas fa-list-check"></i> Build a Form</button>
        </div>
        <div id="iaFileSection" class="form-group">
          <label class="form-label">Upload Form / Document (PDF, DOC, DOCX, JPG, PNG – max 10 MB)</label>
          <input type="file" name="assessment_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"/>
        </div>
        <div id="iaFormSection" style="display:none">
          <div style="font-size:12.5px;color:var(--gray-500);margin-bottom:10px">Add your questions and fill in your answers. The Extension Coordinator will see both, and the questions are sent straight to the participants of the activity.</div>
          <div id="iaQuestions"></div>
          <button type="button" class="btn btn-outline btn-sm" onclick="iaAddQuestion()"><i class="fas fa-plus"></i> Add Question</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('submitAssessment')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-clipboard-list"></i> Submit Assessment</button>
      </div>
    </form>
  </div>
</div>
@php
  $formAssessments = $assessments->filter(function ($item) { return ! empty($item->form_data); });
@endphp
@foreach($formAssessments as $a)
  @include('partials.assessment-form-modal', ['a' => $a])
@endforeach

<script>
function iaSetMode(mode) {
  document.getElementById('iaMode').value = mode;
  document.getElementById('iaFileSection').style.display = mode === 'file' ? '' : 'none';
  document.getElementById('iaFormSection').style.display = mode === 'form' ? '' : 'none';
  var on  = 'background:var(--blue-primary);color:#fff';
  var off = 'background:transparent;color:var(--gray-500)';
  var base = 'padding:8px 16px;font-size:13px;font-weight:600;border-radius:8px 8px 0 0;';
  document.getElementById('iaTabFile').style.cssText = base + (mode === 'file' ? on : off);
  document.getElementById('iaTabForm').style.cssText = base + (mode === 'form' ? on : off);
  document.querySelectorAll('#iaQuestions .ia-q').forEach(iaApplyRequired);
  if (mode === 'form' && !document.querySelector('#iaQuestions .ia-q')) iaAddQuestion();
}

var RATING_SCALE = ['5 - Excellent', '4 - Very Good', '3 - Good', '2 - Fair', '1 - Poor'];

function iaAddQuestion() {
  var row = document.createElement('div');
  row.className = 'ia-q';
  row.style.cssText = 'border:1px solid var(--gray-200);border-radius:10px;padding:14px;margin-bottom:12px;background:var(--gray-50)';
  row.innerHTML =
    '<div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap">' +
      '<div class="form-group" style="flex:2;min-width:180px;margin:0"><label class="form-label">Question</label>' +
        '<input type="text" name="form_label[]" class="form-control" placeholder="e.g. Are the beneficiaries applying the skills taught?"/></div>' +
      '<div class="form-group" style="min-width:130px;margin:0"><label class="form-label">Type</label>' +
        '<select name="form_type[]" class="form-control" onchange="iaTypeChange(this)">' +
          '<option value="text">Text</option><option value="textarea">Textarea</option>' +
          '<option value="radio">Radio</option><option value="select">Select</option><option value="rating">Rating</option></select></div>' +
      '<div class="form-group" style="margin:0;padding-top:22px"><label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;white-space:nowrap">' +
        '<input type="checkbox" class="ia-req" onchange="iaReqChange(this)"/> Required</label>' +
        '<input type="hidden" name="form_required[]" value="0"/></div>' +
      '<div style="padding-top:22px"><button type="button" class="btn btn-sm btn-danger" onclick="iaRemove(this)"><i class="fas fa-trash"></i></button></div>' +
    '</div>' +
    '<div class="ia-opts" style="margin-top:10px;display:none"><label class="form-label">Choices (one per line)</label>' +
      '<textarea name="form_options[]" class="form-control" rows="3" placeholder="Choice 1&#10;Choice 2" oninput="iaRender(this.closest(\'.ia-q\'))"></textarea></div>' +
    '<div style="margin-top:10px"><label class="form-label">Your Answer<span class="ia-star" style="color:var(--red);display:none"> *</span></label><div class="ia-ans"></div></div>';
  document.getElementById('iaQuestions').appendChild(row);
  iaRender(row);
}

function iaRemove(btn) { btn.closest('.ia-q').remove(); }

// "Required" posts as a hidden 0/1 (always one per row, so it lines up with
// the other form_* arrays — an unchecked checkbox posts nothing). The native
// required attribute is only applied in Build a Form mode, otherwise hidden
// questions would block an ordinary file upload.
function iaReqChange(cb) {
  var row = cb.closest('.ia-q');
  row.querySelector('input[name="form_required[]"]').value = cb.checked ? '1' : '0';
  iaApplyRequired(row);
}
function iaApplyRequired(row) {
  var req = row.querySelector('.ia-req').checked;
  var inForm = document.getElementById('iaMode').value === 'form';
  var ans = row.querySelector('[name="form_answer[]"]');
  if (ans) ans.required = req && inForm;
  row.querySelector('.ia-star').style.display = req ? '' : 'none';
}

function iaTypeChange(sel) {
  var row = sel.closest('.ia-q');
  row.querySelector('.ia-opts').style.display = (sel.value === 'radio' || sel.value === 'select') ? '' : 'none';
  iaRender(row);
}

// Rebuild the answer widget for the row's current type/choices, keeping
// whatever was typed if it is still valid. Always exactly one form_answer[].
function iaRender(row) {
  var type = row.querySelector('select[name="form_type[]"]').value;
  var holder = row.querySelector('.ia-ans');
  var prev = holder.querySelector('[name="form_answer[]"]');
  var prevVal = prev ? prev.value : '';
  var el;
  if (type === 'textarea') {
    el = document.createElement('textarea'); el.rows = 2;
  } else if (type === 'rating' || type === 'radio' || type === 'select') {
    el = document.createElement('select');
    var choices = type === 'rating' ? RATING_SCALE :
      row.querySelector('textarea[name="form_options[]"]').value.split('\n').map(function (s) { return s.trim(); }).filter(Boolean);
    var blank = document.createElement('option'); blank.value = ''; blank.textContent = '— Select —'; el.appendChild(blank);
    choices.forEach(function (c) { var o = document.createElement('option'); o.value = c; o.textContent = c; el.appendChild(o); });
  } else {
    el = document.createElement('input'); el.type = 'text';
  }
  el.name = 'form_answer[]';
  el.className = 'form-control';
  holder.innerHTML = '';
  holder.appendChild(el);
  if (prevVal) el.value = prevVal;
  iaApplyRequired(row);
}

// Reopen the modal on the Build a Form tab if validation sent us back with one.
@if(old('mode') === 'form')
document.addEventListener('DOMContentLoaded', function () { iaSetMode('form'); });
@endif
</script>
@endsection
