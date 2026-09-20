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
@php
  $existingFields = $editForm->fields ?? [];
  if (empty($existingFields)) {
      // Default questions match the EC's official Evaluation Form
      // (EvaluationForm_ExtensionProgram.docx) — 6 open-ended questions
      // followed by a 12-item rating scale for specific aspects of the
      // activity. The scale (5=Pinakamahusay ... 1='Di Mahusay) is reused
      // as the option set on every rating question below.
      $ratingScale = [
          '5 - Pinakamahusay',
          '4 - Mas Mahusay',
          '3 - Mahusay',
          '2 - Tama Lang',
          "1 - 'Di Mahusay",
      ];
      $existingFields = [
          ['label' => 'Ang aking ikinalugod/nagustuhan sa natapos na pagsasanay ay...', 'type' => 'textarea', 'required' => true, 'options' => []],
          ['label' => "Ang aking hindi ikinalugod/'di nagustuhan sa natapos na pagsasanay ay...", 'type' => 'textarea', 'required' => true, 'options' => []],
          ['label' => 'Ang kapakipakinabang na paksa ay...', 'type' => 'textarea', 'required' => true, 'options' => []],
          ['label' => 'Ang hindi kapakipakinabang na paksa ay...', 'type' => 'textarea', 'required' => true, 'options' => []],
          ['label' => 'Banggitin ang iyong suhesyon para mapabuti pa ang pagsasagawa ng gawain.', 'type' => 'textarea', 'required' => true, 'options' => []],
          ['label' => 'Banggitin ang pagsasanay/paksa na gustong talakayin sa susunod.', 'type' => 'textarea', 'required' => true, 'options' => []],
          ['label' => 'a. Magtuturo/Gurong nakaatas sa pagbibigay impormasyon', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'b. Nakamit ang layunin ng mga Gawain', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'c. Kaangkopan ng paksa', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'd. Mga kaparaanan o estilo na ginamit sa pagtuturo o pagtatalakay', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'e. Pantulong sa pagtuturo (presentasyon/babasahin)', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'f. Takbo ng pangangasiwa ng pagsasanay', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'g. Pakikiisa ng mga dumalo sa aktibidad', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'h. Kasapatan ng itinakdang oras para sa mga Gawain', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'i. Nagsimula at natapos ang mga Gawain sa itinakdang oras', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'j. Lugar ng pagsasanay', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
          ['label' => 'k. Pagkain', 'type' => 'radio', 'required' => true, 'options' => $ratingScale],
      ];
  }
  $dateMin  = max(
      $editTraining->date_start?->format('Y-m-d') ?? now()->format('Y-m-d'),
      now()->format('Y-m-d')
  );
  $dateMax  = $editTraining->date_end?->format('Y-m-d') ?? '';
  $usedJson = json_encode($usedDates ?? []);
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <a href="{{ route('ec.evaluations') }}" style="color:var(--blue-primary)">Evaluations</a> <i class="fas fa-chevron-right"></i> <span>Form Builder</span></div>
    <h1>{{ $editForm ? 'Edit' : 'Create' }} Evaluation Form</h1>
    <p>For: <strong>{{ $editTraining->title }}</strong> &mdash; {{ $editTraining->date_start?->format('M d') ?? '?' }} – {{ $editTraining->date_end?->format('M d, Y') ?? '?' }}</p>
  </div>
  <a href="{{ route('ec.evaluations') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-body">
    <form method="POST" action="{{ route('ec.evaluations.store') }}" id="formBuilder">
      @csrf
      <input type="hidden" name="action" value="save_form"/>
      <input type="hidden" name="training_id" value="{{ $editTraining->id }}"/>
      @if($editForm)
      <input type="hidden" name="form_id" value="{{ $editForm->id }}"/>
      @endif

      <div class="form-row" style="margin-bottom:20px">
        <div class="form-group">
          <label class="form-label">Form Title</label>
          <input type="text" name="form_title" class="form-control" value="{{ $editForm->title ?? 'Activity Evaluation Form' }}" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Send Date <span style="color:var(--red)">*</span></label>
          <input type="date" name="send_date" id="sendDatePicker" class="form-control"
            value="{{ $editForm?->send_date?->format('Y-m-d') }}"
            min="{{ $dateMin }}"
            @if($dateMax) max="{{ $dateMax }}" @endif
            required/>
          <div class="form-hint">
            <i class="fas fa-circle-info"></i>
            Must be within the activity's dates ({{ $editTraining->date_start?->format('M d') }} – {{ $editTraining->date_end?->format('M d, Y') ?? 'no end date' }}) and not in the past. The form will be sent automatically on this date.
          </div>
        </div>
      </div>

      <div id="fieldsContainer">
        @foreach($existingFields as $fi => $field)
          @php $ftype = $field['type'] ?? 'text'; $fopts = implode("\n", $field['options'] ?? []); @endphp
          <div class="field-row" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
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
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save &amp; Schedule</button>
      </div>
    </form>
  </div>
</div>

<script>
const usedDates = {!! $usedJson !!};
let fieldCount  = {{ count($existingFields) }};

// Grey out already-used dates in the send_date picker
document.getElementById('sendDatePicker').addEventListener('input', function () {
  if (usedDates.includes(this.value)) {
    this.setCustomValidity('This date is already used by another form for this activity.');
    this.reportValidity();
  } else {
    this.setCustomValidity('');
  }
});

function addField() {
  const i = fieldCount++;
  const html = `
  <div class="field-row" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
    <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
      <div class="form-group" style="flex:2;min-width:200px;margin:0">
        <label class="form-label">Question / Label</label>
        <input type="text" name="field_label[]" class="form-control" placeholder="e.g. Your question here" required/>
      </div>
      <div class="form-group" style="min-width:150px;margin:0">
        <label class="form-label">Field Type</label>
        <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
          <option value="text">Text</option><option value="textarea">Textarea</option>
          <option value="radio">Radio</option><option value="select">Select</option><option value="rating">Rating</option>
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

function removeField(btn) { btn.closest('.field-row').remove(); }

function toggleOptions(sel) {
  const row      = sel.closest('.field-row');
  const optWrap  = row.querySelector('.options-wrap');
  const hiddenOpt = row.querySelector('.hidden-opts');
  const needsOpts = ['radio','select'].includes(sel.value);
  if (optWrap)   optWrap.style.display   = needsOpts ? '' : 'none';
  if (hiddenOpt) hiddenOpt.style.display = 'none';
}
document.querySelectorAll('.field-type-sel').forEach(s => toggleOptions(s));
</script>

@else
{{-- ═══════════════════ ACTIVITIES TABLE ═══════════════════ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluations</span></div>
    <h1>Evaluations &amp; Feedback</h1>
    <p>Manage evaluation forms — forms are sent automatically on the scheduled date.</p>
  </div>
  <a href="{{ route('ec.evaluation') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Activities — Evaluation Status</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Activity</th>
          <th>Dates</th>
          <th>Participants</th>
          <th>Responses</th>
          <th>Forms &amp; Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      @forelse($trainings as $t)
        @php
          $totalRc = $t->evalForms->sum('responses_count');
          $today   = now()->startOfDay();
        @endphp
        <tr>
          <td><strong>{{ $t->title }}</strong></td>
          <td style="font-size:12px;color:var(--gray-400)">
            {{ $t->date_start?->format('M d') ?? '—' }}@if($t->date_end) – {{ $t->date_end->format('M d, Y') }}@endif
          </td>
          <td><strong>{{ (int) $t->total_pax }}</strong></td>
          <td>
            <span style="font-weight:700;color:{{ $totalRc > 0 ? 'var(--green)' : 'var(--gray-400)' }}">{{ $totalRc }}/{{ (int) $t->total_pax }}</span>
          </td>
          <td style="min-width:220px">
            @forelse($t->evalForms as $ef)
              @php
                $rc = $ef->responses_count;
                $pax = (int) $t->total_pax;
                if (! $ef->sent_at && $ef->send_date && $ef->send_date->startOfDay()->lt($today)) {
                    $fStatus = 'Expired'; $fBadge = 'badge-danger'; $fIcon = 'fa-ban';
                } elseif (! $ef->sent_at) {
                    $fStatus = 'Scheduled'; $fBadge = 'badge-pending'; $fIcon = 'fa-clock';
                } elseif ($pax > 0 && $rc >= $pax) {
                    $fStatus = 'All Responded'; $fBadge = 'badge-completed'; $fIcon = 'fa-circle-check';
                } else {
                    $fStatus = 'Awaiting'; $fBadge = 'badge-ongoing'; $fIcon = 'fa-hourglass-half';
                }
              @endphp
              <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;flex-wrap:nowrap;white-space:nowrap">
                <span style="font-size:10px;color:var(--gray-400)">{{ $ef->send_date?->format('M d') ?? '—' }}</span>
                <span class="badge {{ $fBadge }}" style="font-size:10px;white-space:nowrap"><i class="fas {{ $fIcon }}"></i> {{ $fStatus }}</span>
                @if($ef->sent_at)
                  {{-- Already sent — locked. Editing now would silently change
                       questions beneficiaries may already be answering, so
                       the form can only be viewed, not edited. --}}
                  <a href="{{ route('ec.evaluations') }}?responses={{ $t->id }}&rform={{ $ef->id }}" class="btn btn-sm btn-ghost" style="padding:2px 7px;font-size:11px" title="View Responses"><i class="fas fa-eye"></i></a>
                  <span class="btn btn-sm btn-outline" style="padding:2px 7px;font-size:11px;opacity:.4;cursor:not-allowed" title="Already sent — cannot be edited"><i class="fas fa-lock"></i></span>
                @else
                  <a href="{{ route('ec.evaluations') }}?edit_form={{ $t->id }}&form_id={{ $ef->id }}" class="btn btn-sm btn-outline" style="padding:2px 7px;font-size:11px" title="Edit"><i class="fas fa-pen"></i></a>
                @endif
                <form method="POST" action="{{ route('ec.evaluations.store') }}" style="display:inline" onsubmit="return confirm('Delete this evaluation form?')">
                  @csrf
                  <input type="hidden" name="action" value="delete_form"/>
                  <input type="hidden" name="form_id" value="{{ $ef->id }}"/>
                  <button type="submit" class="btn btn-sm btn-danger" style="padding:2px 7px;font-size:11px" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            @empty
              <span style="font-size:12px;color:var(--gray-400)">No forms yet</span>
            @endforelse
          </td>
          <td>
            <a href="{{ route('ec.evaluations') }}?edit_form={{ $t->id }}" class="btn btn-sm btn-outline">
              <i class="fas fa-plus"></i> Add
            </a>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">No activity data available.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endif
@endsection
