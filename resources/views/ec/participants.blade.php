@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Participants</span></div>
    <h1>Participants</h1>
    <p>Manage activity beneficiaries and enrollments</p>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <button class="btn btn-outline" onclick="openModal('addExisting')"><i class="fas fa-user-check"></i> Add Existing Account</button>
    <button class="btn btn-primary" onclick="openModal('addParticipant')"><i class="fas fa-plus"></i> Add Participant</button>
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

<div class="card">
  <div class="card-header">
    <div><div class="card-title">All Participants</div><div class="card-subtitle">{{ $groups->count() }} {{ \Illuminate\Support\Str::plural('participant', $groups->count()) }}</div></div>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" action="{{ route('ec.participants') }}" class="filter-row">
      <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" name="q" value="{{ $q }}" placeholder="Search by name or ID…"/>
      </div>
      <select name="training" class="filter-select" onchange="this.form.submit()">
        <option value="">All Activities</option>
        @foreach($trainings as $t)
        <option value="{{ $t->id }}" {{ $filterTraining === $t->id ? 'selected' : '' }}>{{ $t->title }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
      @if($q || $filterTraining)<a href="{{ route('ec.participants') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
    </form>
  </div>
  <style>
    /* One line per activity; the Activity, Evaluation and Actions columns use the
       same line height so each enrollment lines up across the row. */
    .enr-line { min-height: 44px; display: flex; align-items: center; }
    .enr-line + .enr-line { border-top: 1px dashed var(--gray-200); }
  </style>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>User ID</th><th>Phone</th><th>Age / Sex</th><th>Activity</th><th>Account</th><th>Evaluation</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse($groups as $enr)
        @php $p = $enr->first(); @endphp
        <tr>
          <td style="color:var(--gray-400)">{{ $loop->iteration }}</td>
          <td>
            <strong>{{ $p->full_name }}</strong>
          </td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->id_number ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->phone ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->age ? $p->age.' yrs' : '—' }}{{ ($p->age && $p->sex) ? ' · ' : '' }}{{ $p->sex ?? '' }}</td>
          <td style="font-size:12px">
            @foreach($enr as $e)
              <div class="enr-line">{{ $e->training->title ?? '—' }}</div>
            @endforeach
          </td>
          <td>
            @if($p->beneficiary_id)
              <span class="badge badge-active">Registered</span>
              <div style="font-size:11px;color:var(--gray-400);margin-top:2px">&#64;{{ $p->beneficiary->username }}</div>
            @else
              <span class="badge badge-pending">Not yet registered</span>
            @endif
          </td>
          <td>
            @foreach($enr as $e)
              @php
                $evalStatus = $e->evaluations->first()->status ?? 'Pending';
                $evalBadge  = $evalStatus === 'Submitted' ? 'badge-submitted' : 'badge-pending';
              @endphp
              <div class="enr-line"><span class="badge {{ $evalBadge }}">{{ $evalStatus }}</span></div>
            @endforeach
          </td>
          <td>
            @foreach($enr as $e)
            <div class="enr-line">
            <div class="action-btns" style="align-items:stretch">
              <button class="btn btn-sm btn-outline" onclick="openEditModal({{ $e->toJson() }})"><i class="fas fa-pen"></i> Edit</button>
              <form method="POST" action="{{ route('ec.participants.store') }}" style="display:inline-flex">
                @csrf
                <input type="hidden" name="action" value="toggle"/>
                <input type="hidden" name="participant_id" value="{{ $e->id }}"/>
                <button type="submit" class="btn btn-sm {{ $e->is_active ? 'btn-danger' : 'btn-outline' }}"
                        onclick="return confirm('{{ $e->is_active ? 'Disable' : 'Enable' }} access for {{ addslashes($e->full_name) }} in {{ addslashes($e->training->title ?? 'this activity') }}?')">
                  {!! $e->is_active ? '<i class="fas fa-ban"></i> Disable' : '<i class="fas fa-square-check"></i> Enable' !!}
                </button>
              </form>
              <form method="POST" action="{{ route('ec.participants.store') }}" style="display:inline-flex" onsubmit="return confirm('Remove this participant from {{ addslashes($e->training->title ?? 'this activity') }}?')">
                @csrf
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="participant_id" value="{{ $e->id }}"/>
                <button type="submit" class="btn btn-sm btn-danger" style="align-self:stretch;padding-left:12px;padding-right:12px"><i class="fas fa-trash"></i></button>
              </form>
            </div>
            </div>
            @endforeach
          </td>
        </tr>
      @empty
        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--gray-400)">No participants found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="modal-addParticipant">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Add Participant</h2>
      <button class="modal-close" onclick="closeModal('addParticipant')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.participants.store') }}">
      @csrf
      <input type="hidden" name="action" value="add"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group" style="flex:1">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" placeholder="Juan Dela Cruz" required/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="0912 345 6789" maxlength="13" oninput="fmtPhone(this)"/>
          </div>
          <div class="form-group">
            <label class="form-label">Age</label>
            <input type="number" name="age" class="form-control" placeholder="25" min="1" max="120"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Sex</label>
            <select name="sex" class="form-control">
              <option value="">— Select —</option>
              <option>Male</option><option>Female</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned Activity *</label>
            <select name="training_id" class="form-control" required>
              <option value="" disabled selected>— Select Activity —</option>
              @foreach($trainings as $t)
              <option value="{{ $t->id }}">{{ $t->title }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control" placeholder="Brgy., Municipality, Province"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addParticipant')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Register</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD EXISTING ACCOUNT MODAL -->
<div class="modal-overlay" id="modal-addExisting">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2><i class="fas fa-user-check"></i> Add Existing Account</h2>
      <button class="modal-close" onclick="closeModal('addExisting')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.participants.store') }}" onsubmit="return checkExisting()">
      @csrf
      <input type="hidden" name="action" value="enroll_existing"/>
      <input type="hidden" name="beneficiary_id" id="existing_bid"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Assigned Activity *</label>
          <select name="training_id" id="existing_training" class="form-control" required onchange="renderAccounts()">
            <option value="" disabled selected>— Select Activity —</option>
            @foreach($trainings as $t)
            <option value="{{ $t->id }}">{{ $t->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Find Account *</label>
          <input type="text" id="existing_search" class="form-control" placeholder="Search by name, username or email…" oninput="renderAccounts()"/>
          <div id="existing_list" style="margin-top:8px;max-height:240px;overflow-y:auto;border:1px solid var(--gray-200);border-radius:10px"></div>
          <div class="form-hint" id="existing_picked" style="margin-top:6px">No account selected.</div>
        </div>
        <div class="form-hint"><i class="fas fa-circle-info"></i> This only enrolls the account in the activity. The account itself is not changed.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addExisting')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Enroll</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="modal-editParticipant">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2><i class="fas fa-pen"></i> Edit Participant</h2>
      <button class="modal-close" onclick="closeModal('editParticipant')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.participants.store') }}">
      @csrf
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="participant_id" id="edit_pid"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" id="edit_fullname" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">User ID / ID Number</label>
            <input type="text" name="id_number" id="edit_idnum" class="form-control"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" id="edit_phone" class="form-control" placeholder="0912 345 6789" maxlength="13" oninput="fmtPhone(this)"/>
          </div>
          <div class="form-group">
            <label class="form-label">Age</label>
            <input type="number" name="age" id="edit_age" class="form-control" min="1" max="120"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Sex</label>
            <select name="sex" id="edit_sex" class="form-control">
              <option value="">— Select —</option>
              <option>Male</option><option>Female</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned Activity *</label>
            <select name="training_id" id="edit_training" class="form-control" required>
              <option value="" disabled selected>— Select Activity —</option>
              @foreach($trainings as $t)
              <option value="{{ $t->id }}">{{ $t->title }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="address" id="edit_address" class="form-control"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editParticipant')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

@php
  $accountsJs = $accounts->map(function ($a) {
      return ['id' => $a->id, 'name' => $a->full_name, 'username' => $a->username, 'email' => $a->email];
  })->values()->all();
@endphp
<script>
var ACCOUNTS = @json($accountsJs);
var ENROLLED = @json($enrolledMap);
function renderAccounts() {
  var tid = document.getElementById('existing_training').value;
  var q = document.getElementById('existing_search').value.toLowerCase().trim();
  var list = document.getElementById('existing_list');
  var picked = document.getElementById('existing_bid').value;
  list.innerHTML = '';
  var shown = 0;
  ACCOUNTS.forEach(function (a) {
    var hay = (a.name + ' ' + a.username + ' ' + (a.email || '')).toLowerCase();
    if (q && hay.indexOf(q) === -1) return;
    if (shown >= 50) return;
    shown++;
    var enrolled = tid && (ENROLLED[a.id] || []).indexOf(parseInt(tid, 10)) !== -1;
    var row = document.createElement('div');
    row.style.cssText = 'padding:8px 12px;border-bottom:1px solid var(--gray-100);font-size:13px;' +
      (enrolled ? 'opacity:.45;cursor:not-allowed;' : 'cursor:pointer;') +
      (String(a.id) === picked ? 'background:var(--blue-light,#DBEAFE);' : '');
    row.textContent = a.name + ' (@' + a.username + ')' + (enrolled ? ' — already enrolled' : '');
    if (!enrolled) row.onclick = function () {
      document.getElementById('existing_bid').value = a.id;
      document.getElementById('existing_picked').textContent = 'Selected: ' + a.name;
      renderAccounts();
    };
    list.appendChild(row);
  });
  if (!shown) list.innerHTML = '<div style="padding:12px;font-size:13px;color:var(--gray-400)">No accounts found.</div>';
}
function checkExisting() {
  if (!document.getElementById('existing_bid').value) {
    document.getElementById('existing_picked').textContent = 'Please pick an account first.';
    return false;
  }
  return true;
}
document.addEventListener('DOMContentLoaded', renderAccounts);
function openEditModal(p) {
  document.getElementById('edit_pid').value      = p.id;
  document.getElementById('edit_fullname').value = p.full_name;
  document.getElementById('edit_idnum').value    = p.id_number || '';
  document.getElementById('edit_phone').value    = p.phone || '';
  document.getElementById('edit_address').value  = p.address || '';
  document.getElementById('edit_age').value      = p.age || '';
  document.getElementById('edit_sex').value      = p.sex || '';
  document.getElementById('edit_training').value = p.training_id || '';
  openModal('editParticipant');
}
function fmtPhone(el) {
  let v = el.value.replace(/\D/g,'').slice(0,11);
  if (v.length > 7) v = v.slice(0,4)+' '+v.slice(4,7)+' '+v.slice(7);
  else if (v.length > 4) v = v.slice(0,4)+' '+v.slice(4);
  el.value = v;
}
</script>
@endsection
