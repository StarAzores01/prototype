{{--
  Shared "create Activity" (Training) field set — used both standalone
  (ec/trainings.blade.php's "Create Activity" modal) and embedded, scoped to
  one program (ec/programs.blade.php's "Add Activity" modal).

  No Project Leader / Team Members fields here on purpose — an activity
  doesn't get its own team, it inherits the Program's (see
  TrainingController::teamFromProgram(), called from create()/update()).
  Reassign a program's team from the Program page and every activity under
  it picks that up the next time it's created or re-pointed at a program.

  Optional: $scopedProgram — a Program model. When given, the Program field
  is a locked hidden input instead of a dropdown, and $programs isn't needed.
  Optional: $programs — full Program list, required when $scopedProgram isn't set.
--}}
@php $scopedProgram = $scopedProgram ?? null; @endphp
@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:14px">
    <ul style="margin:0;padding-left:18px">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
<div class="form-group">
  <label class="form-label">Activity Title *</label>
  <input type="text" name="title" class="form-control" placeholder="e.g. Basic Pastry Making" required/>
</div>
@include('partials.activity-type-fields', ['idPrefix' => 'caArea'])
<div class="form-group">
  <label class="form-label">Description (optional)</label>
  <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the activity…"></textarea>
</div>

@if($scopedProgram)
  <input type="hidden" name="program_id" value="{{ $scopedProgram->id }}"/>
  <div class="form-group">
    <label class="form-label">Program</label>
    <div style="font-size:13px;color:var(--gray-700);background:var(--gray-50);border-radius:8px;padding:9px 12px">
      <i class="fas fa-diagram-project" style="color:var(--gray-400)"></i> {{ $scopedProgram->title }}
    </div>
  </div>
  @php $scopedLead = $scopedProgram->lead->first(); $scopedMembers = $scopedProgram->members; @endphp
  <div class="form-group">
    <label class="form-label">Project Leader &amp; Team <span style="font-weight:400;color:var(--gray-400)">(inherited from this program)</span></label>
    <div style="font-size:13px;color:var(--gray-700);background:var(--gray-50);border-radius:8px;padding:9px 12px">
      <i class="fas fa-user-tie" style="color:var(--gray-400)"></i>
      {{ $scopedLead?->full_name ?? 'No Project Leader assigned yet' }}
      @if($scopedMembers->isNotEmpty())
        &middot; {{ $scopedMembers->pluck('full_name')->implode(', ') }}
      @endif
    </div>
  </div>
@else
  <div class="form-group">
    <label class="form-label">Program *</label>
    <select name="program_id" id="activityProgramSelect" class="form-control" required onchange="syncActivityDateBounds(this)">
      <option value="" disabled selected>— Select Program —</option>
      @foreach($programs as $prog)
      <option value="{{ $prog->id }}"
        data-start="{{ $prog->timeline_start?->format('Y-m-d') }}"
        data-end="{{ $prog->effective_end_date?->format('Y-m-d') }}">
        {{ $prog->title }}
      </option>
      @endforeach
    </select>
    <div class="form-hint"><i class="fas fa-circle-info"></i> The activity's Project Leader and Team Members are inherited from whoever is assigned to this program — they're not set per activity.</div>
  </div>
@endif

<div class="form-row">
  <div class="form-group">
    <label class="form-label">Start Date</label>
    <input type="date" name="date_start" id="activityDateStart" data-range-end="activityDateEnd" class="form-control"
      oninput="clampActivityDate(this)" onchange="clampActivityDate(this)"
      @if($scopedProgram)
        min="{{ $scopedProgram->timeline_start?->format('Y-m-d') }}"
        max="{{ $scopedProgram->effective_end_date?->format('Y-m-d') }}"
      @endif
    />
  </div>
  <div class="form-group">
    <label class="form-label">End Date</label>
    <input type="date" name="date_end" id="activityDateEnd" class="form-control"
      oninput="clampActivityDate(this)" onchange="clampActivityDate(this)"
      @if($scopedProgram)
        min="{{ $scopedProgram->timeline_start?->format('Y-m-d') }}"
        max="{{ $scopedProgram->effective_end_date?->format('Y-m-d') }}"
      @endif
    />
  </div>
</div>
@if($scopedProgram)
<div class="form-hint" style="margin-top:-8px;margin-bottom:12px">
  <i class="fas fa-circle-info"></i> Dates must fall within the program's timeline: <strong>{{ $scopedProgram->timeline_start?->format('M d, Y') }}</strong> – <strong>{{ $scopedProgram->effective_end_date?->format('M d, Y') }}</strong>.
</div>
@endif
<div class="form-row">
  <div class="form-group">
    <label class="form-label">Budget Allocated (₱)</label>
    <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 50000" min="0" step="0.01"/>
  </div>
</div>

<div class="form-group">
  <label class="form-label">Budget Breakdown <span style="font-weight:400;color:var(--gray-400)">(optional — add expense line items)</span></label>
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:600px" id="createBudgetTable">
      <thead>
        <tr style="background:var(--gray-50);border-bottom:1px solid var(--gray-200)">
          <th style="padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;width:22%">Category</th>
          <th style="padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px">Description</th>
          <th style="padding:8px 10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;width:90px">Qty</th>
          <th style="padding:8px 10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;width:120px">Unit Cost (₱)</th>
          <th style="padding:8px 10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;width:110px">Total (₱)</th>
          <th style="padding:8px 10px;width:36px"></th>
        </tr>
      </thead>
      <tbody id="createBudgetBody">
        <tr class="create-budget-row" style="border-bottom:1px solid var(--gray-100)">
          <td style="padding:8px 10px">
            @php $cbCats = \App\Models\BudgetItem::CATEGORIES; @endphp
            <select name="budget_items[0][category]" class="form-control form-control-sm cb-cat" onchange="cbToggleOther(this)">
              @foreach($cbCats as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
            </select>
            <input type="text" name="budget_items[0][other_specify]" class="form-control form-control-sm cb-other" placeholder="Please specify…" style="margin-top:4px;display:none"/>
          </td>
          <td style="padding:8px 10px"><input type="text" name="budget_items[0][description]" class="form-control form-control-sm" placeholder="e.g. Snacks for 30 pax"/></td>
          <td style="padding:8px 10px"><input type="number" name="budget_items[0][quantity]" class="form-control form-control-sm cb-qty" value="1" min="0.01" step="0.01" style="text-align:right" oninput="cbRecalc(this)"/></td>
          <td style="padding:8px 10px"><input type="number" name="budget_items[0][unit_cost]" class="form-control form-control-sm cb-uc" value="0" min="0" step="0.01" style="text-align:right" oninput="cbRecalc(this)"/></td>
          <td style="padding:8px 10px;text-align:right;font-weight:600;color:var(--gray-800)" class="cb-total">0.00</td>
          <td style="padding:8px 10px"><button type="button" class="btn btn-sm btn-danger" style="padding:4px 8px" onclick="cbRemoveRow(this)"><i class="fas fa-trash"></i></button></td>
        </tr>
      </tbody>
    </table>
  </div>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;flex-wrap:wrap;gap:8px">
    <button type="button" class="btn btn-outline btn-sm" onclick="cbAddRow()"><i class="fas fa-plus"></i> Add</button>
    <div style="font-size:13px;color:var(--gray-600);text-align:right">
      Total: <strong id="cbGrandTotal" style="color:var(--gray-800)">₱0.00</strong>
      <span style="font-size:11px;color:var(--gray-400);display:block">saved as Budget Used</span>
    </div>
  </div>
</div>

<script>
(function () {
  const cbCategories = @json(\App\Models\BudgetItem::CATEGORIES);

  window.cbAddRow = function () {
    const opts = cbCategories.map(c => `<option value="${c}">${c}</option>`).join('');
    const rowIndex = document.querySelectorAll('#createBudgetBody .create-budget-row').length;
    const row = `<tr class="create-budget-row" style="border-bottom:1px solid var(--gray-100)">
      <td style="padding:8px 10px">
        <select name="budget_items[${rowIndex}][category]" class="form-control form-control-sm cb-cat" onchange="cbToggleOther(this)">${opts}</select>
        <input type="text" name="budget_items[${rowIndex}][other_specify]" class="form-control form-control-sm cb-other" placeholder="Please specify…" style="margin-top:4px;display:none"/>
      </td>
      <td style="padding:8px 10px"><input type="text" name="budget_items[${rowIndex}][description]" class="form-control form-control-sm" placeholder="e.g. Snacks for 30 pax"/></td>
      <td style="padding:8px 10px"><input type="number" name="budget_items[${rowIndex}][quantity]" class="form-control form-control-sm cb-qty" value="1" min="0.01" step="0.01" style="text-align:right" oninput="cbRecalc(this)"/></td>
      <td style="padding:8px 10px"><input type="number" name="budget_items[${rowIndex}][unit_cost]" class="form-control form-control-sm cb-uc" value="0" min="0" step="0.01" style="text-align:right" oninput="cbRecalc(this)"/></td>
      <td style="padding:8px 10px;text-align:right;font-weight:600;color:var(--gray-800)" class="cb-total">0.00</td>
      <td style="padding:8px 10px"><button type="button" class="btn btn-sm btn-danger" style="padding:4px 8px" onclick="cbRemoveRow(this)"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    document.getElementById('createBudgetBody').insertAdjacentHTML('beforeend', row);
    cbUpdateTotal();
  };

  window.cbRemoveRow = function (btn) {
    btn.closest('tr').remove();
    cbUpdateTotal();
  };

  window.cbToggleOther = function (sel) {
    const other = sel.closest('td').querySelector('.cb-other');
    if (other) other.style.display = sel.value === 'Other' ? '' : 'none';
  };

  window.cbRecalc = function (input) {
    const row   = input.closest('tr');
    const qty   = parseFloat(row.querySelector('.cb-qty').value) || 0;
    const uc    = parseFloat(row.querySelector('.cb-uc').value)  || 0;
    row.querySelector('.cb-total').textContent = (qty * uc).toFixed(2);
    cbUpdateTotal();
  };

  function cbUpdateTotal() {
    let sum = 0;
    document.querySelectorAll('#createBudgetBody .cb-total').forEach(c => {
      sum += parseFloat(c.textContent.replace(/,/g,'')) || 0;
    });
    const el = document.getElementById('cbGrandTotal');
    if (el) el.textContent = '₱' + sum.toLocaleString('en-PH', {minimumFractionDigits:2});
  }

  window.cbUpdateTotal = cbUpdateTotal;
}());
</script>
<div class="form-row">
  <div class="form-group">
    <label class="form-label">No. of Participants (target)</label>
    <input type="number" name="target_participants" class="form-control" placeholder="e.g. 30" min="1"/>
  </div>
</div>

<script>
function syncActivityDateBounds(select) {
  var opt = select.options[select.selectedIndex];
  var start = opt ? (opt.dataset.start || '') : '';
  var end   = opt ? (opt.dataset.end   || '') : '';
  var ds = document.getElementById('activityDateStart');
  var de = document.getElementById('activityDateEnd');
  if (ds) { ds.min = start; ds.max = end; ds.value = ''; }
  if (de) { de.min = start; de.max = end; de.value = ''; }
}

// The min/max attributes on a <input type="date"> only gray out
// out-of-range days in the native calendar popup and block form
// submission (via the browser's constraint validation) — they do NOT stop
// someone from typing an out-of-range date with the keyboard, so without
// this, a date outside the chosen program's timeline could still be typed
// in directly. Clamp back into range as soon as a value outside min/max is
// entered, whether from typing or the picker.
function clampActivityDate(input) {
  if (!input.value) return;
  if (input.min && input.value < input.min) input.value = input.min;
  if (input.max && input.value > input.max) input.value = input.max;
}
</script>
