@extends('layouts.ec')

@section('content')
@if($mode === 'responses')
{{-- ═══════════════════ RESPONSES VIEW ═══════════════════ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <a href="{{ route('ec.evaluations') }}" style="color:var(--blue-primary)">Evaluations</a> <i class="fas fa-chevron-right"></i> <span>Responses</span></div>
    <h1>{{ $viewForm->title }}</h1>
    <p>{{ $viewTraining->title ?? '' }}</p>
  </div>
  <a href="{{ route('ec.evaluations') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

@if($responses->isEmpty())
<div class="card"><div class="card-body" style="text-align:center;padding:48px;color:var(--gray-400)">No responses submitted yet.</div></div>
@else
@php $fields = $viewForm->fields ?? []; @endphp
<div style="display:flex;flex-direction:column;gap:16px">
  @foreach($responses as $resp)
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title"><i class="fas fa-user"></i> {{ $resp->beneficiary->first_name }} {{ $resp->beneficiary->last_name }}</div>
        <div class="card-subtitle">Submitted {{ $resp->submitted_at->format('M d, Y g:i A') }}</div>
      </div>
    </div>
    <div class="card-body">
      @foreach($fields as $fi => $field)
      <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">{{ $field['label'] }}</div>
        <div style="font-size:14px;color:var(--gray-800)">{{ $resp->responses[$fi] ?? '—' }}</div>
      </div>
      @endforeach
    </div>
  </div>
  @endforeach
</div>
@endif

@elseif($mode === 'builder')
{{-- ═══════════════════ FORM BUILDER ═══════════════════ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluations</span></div>
    <h1>Evaluations &amp; Feedback</h1>
    <p>Create and send evaluation forms to Project Leaders and beneficiaries</p>
  </div>
</div>

@php
  $existingFields = $editForm->fields ?? [];
  if (empty($existingFields)) {
      $existingFields = [
          ['label' => 'Overall Rating', 'type' => 'radio', 'required' => true, 'options' => ['1 - Poor', '2 - Fair', '3 - Good', '4 - Very Good', '5 - Excellent']],
          ['label' => 'What did you learn from this activity?', 'type' => 'textarea', 'required' => false, 'options' => []],
          ['label' => 'Suggestions for improvement', 'type' => 'textarea', 'required' => false, 'options' => []],
      ];
  }
@endphp

<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-file-lines"></i> {{ $editForm ? 'Edit' : 'Create' }} Evaluation Form</div>
      <div class="card-subtitle">For: <strong>{{ $editTraining->title }}</strong></div>
    </div>
    <a href="{{ route('ec.evaluations') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('ec.evaluations.store') }}" id="formBuilder">
      @csrf
      <input type="hidden" name="action" value="save_form"/>
      <input type="hidden" name="training_id" value="{{ $editTraining->id }}"/>
      <div class="form-group" style="margin-bottom:20px">
        <label class="form-label">Form Title</label>
        <input type="text" name="form_title" class="form-control" value="{{ $editForm->title ?? 'Activity Evaluation Form' }}" required/>
      </div>

      <div id="fieldsContainer">
        @foreach($existingFields as $fi => $field)
          @php
            $ftype = $field['type'] ?? 'text';
            $fopts = implode("\n", $field['options'] ?? []);
          @endphp
          <div class="field-row" id="field-{{ $fi }}" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
            <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
              <div class="form-group" style="flex:2;min-width:200px;margin:0">
                <label class="form-label">Question / Label</label>
                <input type="text" name="field_label[]" class="form-control" value="{{ $field['label'] }}" placeholder="e.g. Overall Rating" required/>
              </div>
              <div class="form-group" style="min-width:150px;margin:0">
                <label class="form-label">Field Type</label>
                <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
                  @foreach(['text','textarea','radio','select','rating'] as $ft)
                  <option value="{{ $ft }}" {{ $ftype === $ft ? 'selected' : '' }}>{{ ucfirst($ft) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group" style="min-width:120px;margin:0;padding-top:22px">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                  <input type="checkbox" name="field_required[{{ $fi }}]" {{ !empty($field['required']) ? 'checked' : '' }}/> Required
                </label>
              </div>
              <div style="padding-top:22px">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeField(this)"><i class="fas fa-trash"></i></button>
              </div>
            </div>
            <div class="options-wrap" style="margin-top:10px;{{ in_array($ftype, ['radio','select']) ? '' : 'display:none' }}">
              <label class="form-label">Options (one per line)</label>
              <textarea name="field_options[]" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3">{{ $fopts }}</textarea>
            </div>
            @if(!in_array($ftype, ['radio','select']))
            <textarea name="field_options[]" class="form-control" style="display:none" rows="1"></textarea>
            @endif
          </div>
        @endforeach
      </div>

      <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
        <button type="button" class="btn btn-outline" onclick="addField()"><i class="fas fa-plus"></i> Add Question</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Form</button>
      </div>
    </form>
  </div>
</div>

<script>
let fieldCount = {{ count($existingFields) }};

function addField() {
  const i = fieldCount++;
  const html = `
  <div class="field-row" id="field-${i}" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
    <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
      <div class="form-group" style="flex:2;min-width:200px;margin:0">
        <label class="form-label">Question / Label</label>
        <input type="text" name="field_label[]" class="form-control" placeholder="e.g. Your question here" required/>
      </div>
      <div class="form-group" style="min-width:150px;margin:0">
        <label class="form-label">Field Type</label>
        <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
          <option value="text">Text</option>
          <option value="textarea">Textarea</option>
          <option value="radio">Radio</option>
          <option value="select">Select</option>
          <option value="rating">Rating</option>
        </select>
      </div>
      <div class="form-group" style="min-width:120px;margin:0;padding-top:22px">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
          <input type="checkbox" name="field_required[${i}]"/> Required
        </label>
      </div>
      <div style="padding-top:22px">
        <button type="button" class="btn btn-sm btn-danger" onclick="removeField(this)"><i class="fas fa-trash"></i></button>
      </div>
    </div>
    <div class="options-wrap" style="margin-top:10px;display:none">
      <label class="form-label">Options (one per line)</label>
      <textarea name="field_options[]" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
    </div>
    <textarea name="field_options[]" class="form-control hidden-opts" style="display:none" rows="1"></textarea>
  </div>`;
  document.getElementById('fieldsContainer').insertAdjacentHTML('beforeend', html);
}

function removeField(btn) {
  btn.closest('.field-row').remove();
}

function toggleOptions(sel) {
  const row = sel.closest('.field-row');
  const optWrap = row.querySelector('.options-wrap');
  const hiddenOpt = row.querySelector('.hidden-opts');
  const needsOpts = ['radio','select'].includes(sel.value);
  if (optWrap) optWrap.style.display = needsOpts ? '' : 'none';
  if (hiddenOpt) hiddenOpt.style.display = needsOpts ? 'none' : 'none';
}

document.querySelectorAll('.field-type-sel').forEach(s => toggleOptions(s));
</script>

@else
{{-- ═══════════════════ ACTIVITIES TABLE ═══════════════════ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluations</span></div>
    <h1>Evaluations &amp; Feedback</h1>
    <p>Create and send evaluation forms to Project Leaders and beneficiaries</p>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Activities — Evaluation Status</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Activity Name</th>
          <th>Date</th>
          <th>Participants</th>
          <th>Submitted</th>
          <th>Pending</th>
          <th>Responses</th>
          <th>Form</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($trainings as $t)
        @php
          $form = $t->evalForms->first();
          $allDone = $t->total_pax > 0 && $t->pending_count == 0;
          $evalStatus = $allDone ? 'Completed' : ($t->submitted_count > 0 ? 'Ongoing' : 'Pending');
          $badgeClass = $allDone ? 'badge-completed' : ($t->submitted_count > 0 ? 'badge-ongoing' : 'badge-pending');
          $rc = $form?->responses_count ?? 0;
        @endphp
        <tr>
          <td><strong>{{ $t->title }}</strong></td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $t->date_start?->format('Y-m-d') ?? '—' }}</td>
          <td><strong>{{ (int) $t->total_pax }}</strong></td>
          <td style="color:var(--green);font-weight:700">{{ (int) $t->submitted_count }}</td>
          <td style="color:var(--yellow);font-weight:700">{{ (int) $t->pending_count }}</td>
          <td>
            <span style="font-weight:700;color:{{ $rc > 0 ? 'var(--green)' : 'var(--gray-400)' }}">{{ $rc }}</span>
            @if($rc > 0 && $form)
            <a href="{{ route('ec.evaluations') }}?responses={{ $t->id }}" class="btn btn-sm btn-ghost" style="margin-left:4px"><i class="fas fa-eye"></i> View</a>
            @endif
          </td>
          <td>
            @if($form)
              <span class="badge badge-active" title="Form created"><i class="fas fa-file-lines"></i> Created</span>
              @if($form->sent_at)
                <div style="font-size:11px;color:var(--gray-400);margin-top:2px">Sent {{ $form->sent_at->format('M d') }}</div>
              @endif
            @else
              <span class="badge badge-pending">No form</span>
            @endif
          </td>
          <td><span class="badge {{ $badgeClass }}">{{ $evalStatus }}</span></td>
          <td>
            <div class="action-btns">
              <a href="{{ route('ec.evaluations') }}?edit_form={{ $t->id }}" class="btn btn-sm btn-outline">
                {!! $form ? '<i class="fas fa-pen"></i> Edit Form' : '<i class="fas fa-plus"></i> Create Form' !!}
              </a>
              @if($form)
              <form method="POST" action="{{ route('ec.evaluations.store') }}" style="display:inline" onsubmit="return confirm('Send evaluation form to Project Leader and beneficiaries for this activity?')">
                @csrf
                <input type="hidden" name="action" value="send_form"/>
                <input type="hidden" name="training_id" value="{{ $t->id }}"/>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-bell"></i> Send</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--gray-400)">No activity data available.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif
@endsection
