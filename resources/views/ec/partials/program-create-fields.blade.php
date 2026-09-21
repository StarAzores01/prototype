{{--
  Shared "create Program" field set — used both on ec/programs.blade.php's
  own "Create Program" modal and embedded on the EC dashboard's "Create
  Project" shortcut (same modal, same fields, same ec.programs.store
  route — "Project" is just this app's UI label for a Program, the same
  way "Project Leader" is the UI label for a Trainer).

  Expects: $trainers.
--}}
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Program Title *</label>
    <input type="text" name="title" class="form-control" placeholder="e.g. Community Livelihood Initiative 2026" required/>
  </div>
  <div class="form-group">
    <label class="form-label">Area / Specialization *</label>
    <input type="text" name="area" class="form-control" placeholder="e.g. Culinary Technology" maxlength="120" required/>
  </div>
</div>
<div class="form-group">
  <label class="form-label">Description (optional)</label>
  <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the program…"></textarea>
</div>
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Timeline Start *</label>
    <input type="date" name="timeline_start" id="programTimelineStart" data-range-end="programTimelineEnd" class="form-control" required/>
  </div>
  <div class="form-group">
    <label class="form-label">Timeline End *</label>
    <input type="date" name="timeline_end" id="programTimelineEnd" class="form-control" required/>
  </div>
</div>
<div class="form-group">
  <label class="form-label">Budget Allocated (₱) *</label>
  <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 200000" min="0" step="0.01" required/>
</div>

<hr style="border:none;border-top:1px solid var(--gray-100);margin:8px 0 16px">
<div class="form-group">
  <label class="form-label">Project Lead * <span style="font-weight:400;color:var(--gray-400)">(exactly one, from Project Leaders)</span></label>
  <select name="lead_id" id="programLeadSelect" class="form-control" required onchange="syncMemberDropdowns()">
    <option value="" disabled selected>— Select Project Lead —</option>
    @foreach($trainers as $tr)
    <option value="{{ $tr->id }}">{{ $tr->first_name }} {{ $tr->last_name }}</option>
    @endforeach
  </select>
</div>
<div class="form-group">
  <label class="form-label">Team Members <span style="font-weight:400;color:var(--gray-400)">(optional, up to 3)</span></label>
  <div class="form-row" style="grid-template-columns:1fr 1fr 1fr" id="memberDropdownsWrap">
    @for($i = 0; $i < 3; $i++)
    <select name="member_ids[]" class="form-control member-select" onchange="syncMemberDropdowns()">
      <option value="">— None —</option>
      @foreach($trainers as $tr)
      <option value="{{ $tr->id }}">{{ $tr->first_name }} {{ $tr->last_name }}</option>
      @endforeach
    </select>
    @endfor
  </div>
  <div style="font-size:11px;color:var(--gray-400);margin-top:4px">A team member can't also be the Project Lead, and can't be selected twice.</div>
</div>

<script>
// Keeps the 3 Team Member dropdowns in this modal from offering the same
// person twice, and from offering whoever is picked as Project Lead. Runs
// on the Lead select's onchange (already wired) and, now, on each Team
// Member select's own onchange too — picking someone in dropdown 1 removes
// them from dropdowns 2 and 3's option lists (and vice versa), instead of
// only the server-side `distinct` validation catching it after submit.
function syncMemberDropdowns() {
  const leadId  = document.getElementById('programLeadSelect').value;
  const selects = Array.from(document.querySelectorAll('.member-select'));
  const chosenElsewhere = function (sel) {
    return selects.filter(function (s) { return s !== sel; }).map(function (s) { return s.value; }).filter(Boolean);
  };
  selects.forEach(function (sel) {
    const current = sel.value;
    const takenByOtherSelects = chosenElsewhere(sel);
    Array.from(sel.options).forEach(function (opt) {
      if (opt.value === '') return; // keep the "— None —" option
      const isLead = leadId && opt.value === leadId;
      const takenElsewhere = opt.value !== current && takenByOtherSelects.includes(opt.value);
      opt.hidden   = isLead;
      opt.disabled = isLead || takenElsewhere;
    });
    // If the currently selected member is now the lead, reset it
    if (current && current === leadId) sel.value = '';
  });
}
document.addEventListener('DOMContentLoaded', syncMemberDropdowns);
</script>
