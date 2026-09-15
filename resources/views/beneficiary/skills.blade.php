@extends('layouts.beneficiary')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Skills Utilization</span></div>
    <h1>Skills Utilization Progress</h1>
    <p>Record how you are applying the skills you learned and track your progress over time.</p>
  </div>
  <button type="button" class="btn btn-primary" onclick="openAddEntry()">
    <i class="fas fa-plus"></i> Add Progress Entry
  </button>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:16px"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:16px"><i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}</div>
@endif

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-chart-line"></i> Progress Entries</div>
  </div>

  @if($entries->isEmpty())
  <div class="empty-state" style="padding:48px 24px;text-align:center">
    <i class="fas fa-chart-line" style="font-size:28px;color:var(--gray-300)"></i>
    <p style="margin-top:10px;color:var(--gray-400)">No progress entries yet. Add your first entry to start tracking how you're applying your skills.</p>
  </div>
  @else
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Activity / Skill Applied</th>
          <th>Description</th>
          <th>Date</th>
          <th>Progress / Outcome</th>
          <th>Service Fee / Earnings</th>
          <th>Remarks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($entries as $e)
        <tr>
          <td><strong>{{ $e->activity_name }}</strong></td>
          <td style="max-width:220px;white-space:normal;color:var(--gray-600)">{{ $e->description ?: '—' }}</td>
          <td style="white-space:nowrap">{{ $e->activity_date->format('M d, Y') }}</td>
          <td><span class="badge badge-active">{{ $e->outcome_type }}</span></td>
          <td style="white-space:nowrap;font-weight:600">&#8369;{{ number_format((float) $e->service_fee, 2) }}</td>
          <td style="max-width:200px;white-space:normal;color:var(--gray-600)">{{ $e->remarks ?: '—' }}</td>
          <td>
            <div class="action-btns">
              <button type="button" class="btn btn-sm btn-outline" onclick='openEditEntry(@json($e))'>
                <i class="fas fa-pen"></i> Edit
              </button>
              <form method="POST" action="{{ route('beneficiary.skills.store') }}" style="display:inline"
                    onsubmit="return confirm('Delete this progress entry? This cannot be undone.')">
                @csrf
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="entry_id" value="{{ $e->id }}"/>
                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>

{{-- ═══════════════ MODAL — ADD / EDIT PROGRESS ENTRY ═══════════════ --}}
<div class="modal-overlay" id="modal-progressEntry">
  <div class="modal">
    <div class="modal-header">
      <h2 id="progressEntryTitle"><i class="fas fa-plus"></i> Add Progress Entry</h2>
      <button type="button" class="modal-close" onclick="closeModal('progressEntry')"><i class="fas fa-xmark"></i></button>
    </div>

    <form method="POST" action="{{ route('beneficiary.skills.store') }}" id="progressEntryForm">
      @csrf
      <input type="hidden" name="action" id="pe_action" value="create"/>
      <input type="hidden" name="entry_id" id="pe_entry_id" value=""/>

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Activity / Skill Applied *</label>
          <input type="text" name="activity_name" id="pe_activity_name" class="form-control" placeholder="e.g. Logo Design" maxlength="255" required/>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="pe_description" class="form-control" rows="2" placeholder="e.g. Designed a logo for a small local business"></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="activity_date" id="pe_activity_date" class="form-control" max="{{ now()->format('Y-m-d') }}" required/>
          </div>

          <div class="form-group">
            <label class="form-label">Progress / Outcome Type *</label>
            <select name="outcome_type" id="pe_outcome_type" class="form-control" required>
              <option value="">— Select —</option>
              @foreach($outcomeTypes as $type)
                <option value="{{ $type }}">{{ $type }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Service Fee / Earnings (&#8369;)</label>
            <input type="number" name="service_fee" id="pe_service_fee" class="form-control" step="0.01" min="0" placeholder="0.00"/>
          </div>

          @if($myTrainings->isNotEmpty())
          <div class="form-group">
            <label class="form-label">Related Activity (optional)</label>
            <select name="training_id" id="pe_training_id" class="form-control">
              <option value="">— None —</option>
              @foreach($myTrainings as $t)
                <option value="{{ $t->id }}">{{ $t->title }}</option>
              @endforeach
            </select>
          </div>
          @endif
        </div>

        <div class="form-group">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" id="pe_remarks" class="form-control" rows="2" placeholder="e.g. First paid client"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('progressEntry')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Entry</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddEntry() {
  document.getElementById('progressEntryTitle').innerHTML = '<i class="fas fa-plus"></i> Add Progress Entry';
  document.getElementById('pe_action').value = 'create';
  document.getElementById('pe_entry_id').value = '';
  document.getElementById('progressEntryForm').reset();
  openModal('progressEntry');
}

function openEditEntry(entry) {
  document.getElementById('progressEntryTitle').innerHTML = '<i class="fas fa-pen"></i> Edit Progress Entry';
  document.getElementById('pe_action').value = 'update';
  document.getElementById('pe_entry_id').value = entry.id;
  document.getElementById('pe_activity_name').value = entry.activity_name || '';
  document.getElementById('pe_description').value = entry.description || '';
  document.getElementById('pe_activity_date').value = entry.activity_date ? String(entry.activity_date).substring(0, 10) : '';
  document.getElementById('pe_outcome_type').value = entry.outcome_type || '';
  document.getElementById('pe_service_fee').value = entry.service_fee || 0;
  document.getElementById('pe_remarks').value = entry.remarks || '';
  var trainingSelect = document.getElementById('pe_training_id');
  if (trainingSelect) trainingSelect.value = entry.training_id || '';
  openModal('progressEntry');
}

@if($errors->any() && old('action') === 'create')
document.addEventListener('DOMContentLoaded', function () { openAddEntry(); });
@elseif($errors->any() && old('action') === 'update')
document.addEventListener('DOMContentLoaded', function () {
  openEditEntry({
    id: '{{ old('entry_id') }}',
    activity_name: @json(old('activity_name')),
    description: @json(old('description')),
    activity_date: @json(old('activity_date')),
    outcome_type: @json(old('outcome_type')),
    service_fee: @json(old('service_fee')),
    remarks: @json(old('remarks')),
    training_id: @json(old('training_id')),
  });
});
@endif
</script>
@endsection
