{{--
  Shared "Activity Type" picker for Trainings/Activities. Replaces the old
  free-text "Area / Specification" input with a fixed list (Training,
  Seminar, Workshop, Orientation, Forum, Conference, Consultation) plus an
  "Other" option that reveals a text box when chosen. Programs keep their
  own separate free-text area field (ec/trainer partials/program-create-fields.blade.php)
  — untouched, this only covers Trainings.

  Included from: ec.partials.activity-create-fields (both the standalone
  Create Activity forms and the per-Program "Add Activity" forms, EC and
  Trainer), and directly from ec/trainings.blade.php + trainer/trainings.blade.php's
  Edit Activity forms.

  The radios use a grouped name scoped to $idPrefix (so multiple instances
  of this partial on one page  -  e.g. a Create modal and an Edit modal  -
  don't fight over the same radio group) and never submit as `area`
  themselves; a single hidden input this partial's JS keeps in sync is
  what actually carries the `area` value the server reads. That hidden
  input's value already reflects $selected on first render, so no JS needs
  to run on page load for a form that renders with a known value (e.g. the
  EC detail-page inline edit form); forms that only learn the current value
  later via JS (the card-grid Edit Activity modal, filled in through
  openEditTrainingModal()) should call setActivityTypeValue($idPrefix, area)
  at that point instead.

  Params:
    $idPrefix  -  required, unique per instance on the page (e.g. 'caArea', 'editArea').
    $selected  -  optional current area value (for edit forms). If it isn't
                  one of the 7 fixed options, "Other" is pre-selected and
                  the value is placed in the text box.
--}}
@php
  $activityTypeOptions = ['Training', 'Seminar', 'Workshop', 'Orientation', 'Forum', 'Conference', 'Consultation'];
  $atCurrent = $selected ?? null;
  $atIsOther = $atCurrent && ! in_array($atCurrent, $activityTypeOptions, true);
@endphp
<div class="form-group">
  <label class="form-label">Activity Type *</label>
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px 16px;margin-top:6px">
    @foreach($activityTypeOptions as $opt)
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
      <input type="radio" name="{{ $idPrefix }}_choice" value="{{ $opt }}"
        {{ $atCurrent === $opt ? 'checked' : '' }} required
        onchange="syncActivityTypeField('{{ $idPrefix }}')"
        style="width:16px;height:16px;accent-color:var(--blue-primary)"/>
      {{ $opt }}
    </label>
    @endforeach
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
      <input type="radio" name="{{ $idPrefix }}_choice" value="__other__"
        {{ $atIsOther ? 'checked' : '' }} required
        onchange="syncActivityTypeField('{{ $idPrefix }}')"
        style="width:16px;height:16px;accent-color:var(--blue-primary)"/>
      Other:
    </label>
  </div>
  <input type="text" id="{{ $idPrefix }}OtherInput" class="form-control" placeholder="Please specify…" maxlength="120"
    oninput="syncActivityTypeField('{{ $idPrefix }}')"
    value="{{ $atIsOther ? $atCurrent : '' }}"
    style="margin-top:8px;{{ $atIsOther ? '' : 'display:none' }}"/>
  <input type="hidden" name="area" id="{{ $idPrefix }}HiddenArea" value="{{ $atCurrent ?? '' }}"/>
</div>

<script>
// Keeps the hidden `area` input (the actual submitted value) in sync with
// whichever radio is picked, or with the typed text when "Other" is picked.
function syncActivityTypeField(prefix) {
  const checked = document.querySelector('input[name="' + prefix + '_choice"]:checked');
  const otherInput = document.getElementById(prefix + 'OtherInput');
  const hidden = document.getElementById(prefix + 'HiddenArea');
  if (!checked || !otherInput || !hidden) return;
  if (checked.value === '__other__') {
    otherInput.style.display = '';
    hidden.value = otherInput.value.trim();
  } else {
    otherInput.style.display = 'none';
    hidden.value = checked.value;
  }
}

// For forms that learn the current area value later via JS (the card-grid
// Edit Activity modal's openEditTrainingModal()) rather than at render
// time  -  selects the matching radio, or falls to "Other" with the value
// filled in, and updates the hidden input to match.
function setActivityTypeValue(prefix, area) {
  const fixedOptions = ['Training', 'Seminar', 'Workshop', 'Orientation', 'Forum', 'Conference', 'Consultation'];
  const radios = document.querySelectorAll('input[name="' + prefix + '_choice"]');
  const otherInput = document.getElementById(prefix + 'OtherInput');
  const hidden = document.getElementById(prefix + 'HiddenArea');
  const isOther = !!area && fixedOptions.indexOf(area) === -1;
  radios.forEach(function (r) {
    r.checked = isOther ? r.value === '__other__' : r.value === area;
  });
  if (otherInput) {
    otherInput.style.display = isOther ? '' : 'none';
    otherInput.value = isOther ? (area || '') : '';
  }
  if (hidden) hidden.value = area || '';
}
</script>
