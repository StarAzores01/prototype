{{--
  Budget Breakdown modal — shared by ec/trainings and trainer/trainings.
  Expects:
    $storeRoute  — the POST route (e.g. route('ec.trainings.store'))
    $viewTraining — the Training model
    $progress     — the progress array from the controller
    $budgetItems  — collection of BudgetItem rows for this training
--}}
@php
  $categories = \App\Models\BudgetItem::CATEGORIES;
  $existingItems = $budgetItems ?? collect();
@endphp

<div class="modal-overlay" id="modal-updateBudget">
  <div class="modal" style="max-width:760px">
    <div class="modal-header">
      <h2><i class="fas fa-sack-dollar"></i> Log Budget Usage</h2>
      <button class="modal-close" onclick="closeModal('updateBudget')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ $storeRoute }}" id="budgetBreakdownForm">
      @csrf
      <input type="hidden" name="action" value="update_budget"/>
      <input type="hidden" name="training_id" value="{{ $viewTraining->id }}"/>
      <div class="modal-body">

        {{-- Budget summary --}}
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
          <div style="flex:1;min-width:160px;background:var(--gray-50);border-radius:8px;padding:10px 14px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px">Allocated</div>
            <div style="font-size:15px;font-weight:700;color:var(--gray-800);margin-top:2px">
              &#8369;{{ number_format((float) $viewTraining->budget_allocated, 2) }}
            </div>
          </div>
          <div style="flex:1;min-width:160px;background:var(--gray-50);border-radius:8px;padding:10px 14px">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px">Used (current total)</div>
            <div style="font-size:15px;font-weight:700;color:var(--gray-800);margin-top:2px" id="budgetGrandTotal">
              &#8369;{{ number_format((float) $viewTraining->budget_used, 2) }}
            </div>
          </div>
        </div>

        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i>
          Add one row per expense. The total will be computed automatically and saved as the budget used.
        </div>

        @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom:16px">
          <i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}
        </div>
        @endif

        {{-- Line items table --}}
        <div style="overflow-x:auto">
          <table style="width:100%;border-collapse:collapse;font-size:13px" id="budgetItemsTable">
            <thead>
              <tr style="background:var(--gray-50);border-bottom:1px solid var(--gray-200)">
                <th style="padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;min-width:140px">Category</th>
                <th style="padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;min-width:160px">Description</th>
                <th style="padding:8px 10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;width:90px">Qty</th>
                <th style="padding:8px 10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;width:110px">Unit Cost (₱)</th>
                <th style="padding:8px 10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--gray-400);letter-spacing:.4px;width:110px">Total (₱)</th>
                <th style="padding:8px 10px;width:36px"></th>
              </tr>
            </thead>
            <tbody id="budgetItemsBody">
              @forelse($existingItems as $item)
                @php
                  $isCat = in_array($item->category, $categories) ? $item->category : 'Other';
                  $otherVal = str_starts_with($item->category, 'Other: ') ? substr($item->category, 7) : '';
                @endphp
                <tr class="budget-row" style="border-bottom:1px solid var(--gray-100)">
                  <td style="padding:6px 8px">
                    <select name="items[{{ $loop->index }}][category]" class="form-control form-control-sm budget-cat" onchange="toggleOther(this)" required>
                      @foreach($categories as $cat)
                      <option value="{{ $cat }}" {{ $isCat === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                      @endforeach
                    </select>
                    <input type="text" name="items[{{ $loop->index }}][other_specify]" class="form-control form-control-sm other-specify" placeholder="Please specify…"
                      style="margin-top:4px;{{ $isCat === 'Other' ? '' : 'display:none' }}"
                      value="{{ $otherVal }}"/>
                  </td>
                  <td style="padding:6px 8px">
                    <input type="text" name="items[{{ $loop->index }}][description]" class="form-control form-control-sm" value="{{ $item->description }}" placeholder="e.g. Snacks for 30 pax"/>
                  </td>
                  <td style="padding:6px 8px">
                    <input type="number" name="items[{{ $loop->index }}][quantity]" class="form-control form-control-sm budget-qty" value="{{ $item->quantity }}" min="0.01" step="0.01" required style="text-align:right" oninput="recalcRow(this)"/>
                  </td>
                  <td style="padding:6px 8px">
                    <input type="number" name="items[{{ $loop->index }}][unit_cost]" class="form-control form-control-sm budget-uc" value="{{ $item->unit_cost }}" min="0" step="0.01" required style="text-align:right" oninput="recalcRow(this)"/>
                  </td>
                  <td style="padding:6px 8px;text-align:right;font-weight:600;color:var(--gray-800)" class="budget-total">
                    {{ number_format((float)$item->quantity * (float)$item->unit_cost, 2) }}
                  </td>
                  <td style="padding:6px 8px">
                    <button type="button" class="btn btn-sm btn-danger" style="padding:3px 7px" onclick="removeBudgetRow(this)"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              @empty
                {{-- at least one blank row when no items exist yet --}}
                <tr class="budget-row" style="border-bottom:1px solid var(--gray-100)">
                  <td style="padding:6px 8px">
                    <select name="items[0][category]" class="form-control form-control-sm budget-cat" onchange="toggleOther(this)" required>
                      @foreach($categories as $cat)
                      <option value="{{ $cat }}">{{ $cat }}</option>
                      @endforeach
                    </select>
                    <input type="text" name="items[0][other_specify]" class="form-control form-control-sm other-specify" placeholder="Please specify…" style="margin-top:4px;display:none"/>
                  </td>
                  <td style="padding:6px 8px">
                    <input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="e.g. Snacks for 30 pax"/>
                  </td>
                  <td style="padding:6px 8px">
                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm budget-qty" value="1" min="0.01" step="0.01" required style="text-align:right" oninput="recalcRow(this)"/>
                  </td>
                  <td style="padding:6px 8px">
                    <input type="number" name="items[0][unit_cost]" class="form-control form-control-sm budget-uc" value="0" min="0" step="0.01" required style="text-align:right" oninput="recalcRow(this)"/>
                  </td>
                  <td style="padding:6px 8px;text-align:right;font-weight:600;color:var(--gray-800)" class="budget-total">0.00</td>
                  <td style="padding:6px 8px">
                    <button type="button" class="btn btn-sm btn-danger" style="padding:3px 7px" onclick="removeBudgetRow(this)"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <button type="button" class="btn btn-outline btn-sm" style="margin-top:10px" onclick="addBudgetRow()">
          <i class="fas fa-plus"></i> Add Row
        </button>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('updateBudget')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Breakdown</button>
      </div>
    </form>
  </div>
</div>

<script>
// Category options for dynamically added rows
const budgetCategories = @json($categories);

function addBudgetRow() {
  const opts = budgetCategories.map(c => `<option value="${c}">${c}</option>`).join('');
  const rowIndex = document.querySelectorAll('#budgetItemsBody .budget-row').length;
  const row = `
  <tr class="budget-row" style="border-bottom:1px solid var(--gray-100)">
    <td style="padding:6px 8px">
      <select name="items[${rowIndex}][category]" class="form-control form-control-sm budget-cat" onchange="toggleOther(this)" required>${opts}</select>
      <input type="text" name="items[${rowIndex}][other_specify]" class="form-control form-control-sm other-specify" placeholder="Please specify…" style="margin-top:4px;display:none"/>
    </td>
    <td style="padding:6px 8px"><input type="text" name="items[${rowIndex}][description]" class="form-control form-control-sm" placeholder="e.g. Snacks for 30 pax"/></td>
    <td style="padding:6px 8px"><input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm budget-qty" value="1" min="0.01" step="0.01" required style="text-align:right" oninput="recalcRow(this)"/></td>
    <td style="padding:6px 8px"><input type="number" name="items[${rowIndex}][unit_cost]" class="form-control form-control-sm budget-uc" value="0" min="0" step="0.01" required style="text-align:right" oninput="recalcRow(this)"/></td>
    <td style="padding:6px 8px;text-align:right;font-weight:600;color:var(--gray-800)" class="budget-total">0.00</td>
    <td style="padding:6px 8px"><button type="button" class="btn btn-sm btn-danger" style="padding:3px 7px" onclick="removeBudgetRow(this)"><i class="fas fa-trash"></i></button></td>
  </tr>`;
  document.getElementById('budgetItemsBody').insertAdjacentHTML('beforeend', row);
  updateGrandTotal();
}

function removeBudgetRow(btn) {
  const rows = document.querySelectorAll('#budgetItemsBody .budget-row');
  if (rows.length <= 1) { alert('At least one item is required.'); return; }
  btn.closest('tr').remove();
  updateGrandTotal();
}

function toggleOther(sel) {
  const other = sel.closest('td').querySelector('.other-specify');
  if (other) other.style.display = sel.value === 'Other' ? '' : 'none';
}

function recalcRow(input) {
  const row   = input.closest('tr');
  const qty   = parseFloat(row.querySelector('.budget-qty').value) || 0;
  const uc    = parseFloat(row.querySelector('.budget-uc').value)  || 0;
  const total = (qty * uc).toFixed(2);
  row.querySelector('.budget-total').textContent = parseFloat(total).toLocaleString('en-PH', {minimumFractionDigits:2});
  updateGrandTotal();
}

function updateGrandTotal() {
  let sum = 0;
  document.querySelectorAll('#budgetItemsBody .budget-total').forEach(cell => {
    sum += parseFloat(cell.textContent.replace(/,/g,'')) || 0;
  });
  const el = document.getElementById('budgetGrandTotal');
  if (el) el.textContent = '₱' + sum.toLocaleString('en-PH', {minimumFractionDigits:2});
}

// Init on open
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.budget-cat').forEach(sel => toggleOther(sel));
  updateGrandTotal();
});
</script>
