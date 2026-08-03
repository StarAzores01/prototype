@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Evaluators</span></div>
    <h1>Evaluators</h1>
    <p>Manage approved evaluators and their accounts</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addEvaluator')">&#43; Add Evaluator</button>
</div>

<!-- Registered Evaluators -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title">&#128100; Registered Evaluators</div>
    <form method="GET" action="{{ route('ec.evaluators') }}" style="display:flex;gap:8px">
      <input type="text" name="q" class="form-control" placeholder="Search..." value="{{ $q }}" style="width:220px"/>
      <button type="submit" class="btn btn-outline btn-sm">&#128269;</button>
      @if($q)<a href="{{ route('ec.evaluators') }}" class="btn btn-outline btn-sm">&#10005;</a>@endif
    </form>
  </div>
  <div class="card-body" style="padding:0">
    @if($evaluators->isEmpty())
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No registered evaluators yet.</div>
    @else
    <table class="data-table">
      <thead><tr><th>Name</th><th>ID</th><th>Department</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach($evaluators as $ev)
        <tr>
          <td style="font-weight:600">{{ $ev->first_name }} {{ $ev->last_name }}</td>
          <td><code>{{ $ev->id_number }}</code></td>
          <td>{{ $ev->position }}</td>
          <td>{{ $ev->email }}</td>
          <td>
            <span class="badge {{ $ev->is_active ? 'badge-success' : 'badge-danger' }}">
              {{ $ev->is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td style="display:flex;gap:6px">
            <button class="btn btn-outline btn-sm" onclick="openEditModal({{ $ev->toJson() }})">&#9998; Edit</button>
            <form method="POST" action="{{ route('ec.evaluators.store') }}" style="display:inline">
              @csrf
              <input type="hidden" name="action" value="toggle"/>
              <input type="hidden" name="user_id" value="{{ $ev->id }}"/>
              <button type="submit" class="btn btn-sm {{ $ev->is_active ? '' : 'btn-outline' }}" style="{{ $ev->is_active ? 'background:#FEE2E2;color:#991B1B;border:none' : '' }}">
                {{ $ev->is_active ? '🚫 Disable' : '✅ Enable' }}
              </button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>

<!-- Whitelist -->
<div class="card">
  <div class="card-header">
    <div class="card-title">&#128203; Approved Evaluators List (Pre-registration)</div>
  </div>
  <div class="card-body" style="padding:0">
    @if($whitelist->isEmpty())
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No evaluators on the approved list yet.</div>
    @else
    <table class="data-table">
      <thead><tr><th>Name</th><th>Assigned ID</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach($whitelist as $w)
        <tr>
          <td>{{ $w->first_name }} {{ $w->last_name }}</td>
          <td><code>{{ $w->id_number ?? '—' }}</code></td>
          <td>{{ $w->department }}</td>
          <td><span class="badge {{ $w->is_registered ? 'badge-success' : 'badge-warning' }}">{{ $w->is_registered ? 'Registered' : 'Pending' }}</span></td>
          <td>
            @if(!$w->is_registered)
            <form method="POST" action="{{ route('ec.evaluators.store') }}" onsubmit="return confirm('Remove from approved list?')" style="display:inline">
              @csrf
              <input type="hidden" name="action" value="remove_whitelist"/>
              <input type="hidden" name="whitelist_id" value="{{ $w->id }}"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465; Remove</button>
            </form>
            @else
            <span style="font-size:12px;color:var(--gray-400)">Registered</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>

<!-- Add Evaluator Modal -->
<div class="modal-overlay" id="modal-addEvaluator">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">&#43; Add Evaluator to Approved List</div>
      <button class="modal-close" onclick="closeModal('addEvaluator')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.evaluators.store') }}">
      @csrf
      <input type="hidden" name="action" value="add_whitelist"/>
      <div class="modal-body">
        <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px">The evaluator will use their assigned ID to register an account.</p>
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name <span style="color:var(--red)">*</span></label><input type="text" name="first_name" class="form-control" required/></div>
          <div class="form-group"><label class="form-label">Last Name <span style="color:var(--red)">*</span></label><input type="text" name="last_name" class="form-control" required/></div>
        </div>
        <div class="form-group"><label class="form-label">Department <span style="color:var(--red)">*</span></label>
          <select name="department" class="form-control" required>
            <option value="">— Select Department —</option>
            @foreach($departments as $d)
            <option>{{ $d }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addEvaluator')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#43; Add &amp; Assign ID</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Evaluator Modal -->
<div class="modal-overlay" id="modal-editEvaluator">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">&#9998; Edit Evaluator</div>
      <button class="modal-close" onclick="closeModal('editEvaluator')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.evaluators.store') }}">
      @csrf
      <input type="hidden" name="action" value="edit_evaluator"/>
      <input type="hidden" name="user_id" id="edit_user_id"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" id="edit_first" class="form-control" required/></div>
          <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" id="edit_last" class="form-control" required/></div>
        </div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Department</label>
          <select name="department" id="edit_dept" class="form-control">
            <option value="">— Select Department —</option>
            @foreach($departments as $d)
            <option>{{ $d }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group"><label class="form-label">ID Number</label><input type="text" name="id_number" id="edit_id" class="form-control"/></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editEvaluator')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(ev) {
  document.getElementById('edit_user_id').value = ev.id;
  document.getElementById('edit_first').value   = ev.first_name;
  document.getElementById('edit_last').value    = ev.last_name;
  document.getElementById('edit_email').value   = ev.email;
  document.getElementById('edit_id').value      = ev.id_number;
  const deptSel = document.getElementById('edit_dept');
  for (let i = 0; i < deptSel.options.length; i++) {
    deptSel.options[i].selected = deptSel.options[i].value === ev.position;
  }
  openModal('editEvaluator');
}
</script>
@endsection
