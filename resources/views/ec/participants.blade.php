@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Participants</span></div>
    <h1>Participants</h1>
    <p>Manage training beneficiaries and enrollments</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addParticipant')">&#43; Add Participant</button>
</div>

<div class="card">
  <div class="card-header">
    <div><div class="card-title">All Participants</div><div class="card-subtitle">{{ $participants->count() }} records</div></div>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" action="{{ route('ec.participants') }}" class="filter-row">
      <div class="search-box">
        &#128269;
        <input type="text" name="q" value="{{ $q }}" placeholder="Search by name or ID…"/>
      </div>
      <select name="training" class="filter-select" onchange="this.form.submit()">
        <option value="">All Trainings</option>
        @foreach($trainings as $t)
        <option value="{{ $t->id }}" {{ $filterTraining === $t->id ? 'selected' : '' }}>{{ $t->title }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
      @if($q || $filterTraining)<a href="{{ route('ec.participants') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>User ID</th><th>Phone</th><th>Age / Sex</th><th>Training</th><th>Account</th><th>Evaluation</th><th>Actions</th></tr></thead>
      <tbody>
      @forelse($participants as $p)
        @php
          $evalStatus = $p->evaluations->first()->status ?? 'Pending';
          $evalBadge  = $evalStatus === 'Submitted' ? 'badge-submitted' : 'badge-pending';
        @endphp
        <tr>
          <td style="color:var(--gray-400)">{{ $loop->iteration }}</td>
          <td>
            <strong>{{ $p->full_name }}</strong>
            @if($p->address)
            <div style="font-size:11px;color:var(--gray-400)">&#128205; {{ $p->address }}</div>
            @endif
          </td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->id_number ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->phone ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->age ? $p->age.' yrs' : '—' }}{{ ($p->age && $p->sex) ? ' · ' : '' }}{{ $p->sex ?? '' }}</td>
          <td style="font-size:12px">{{ $p->training->title ?? '—' }}</td>
          <td>
            @if($p->beneficiary_id)
              <span class="badge badge-active">Registered</span>
              <div style="font-size:11px;color:var(--gray-400);margin-top:2px">&#64;{{ $p->beneficiary->username }}</div>
            @else
              <span class="badge badge-pending">Not yet registered</span>
            @endif
          </td>
          <td><span class="badge {{ $evalBadge }}">{{ $evalStatus }}</span></td>
          <td>
            <div class="action-btns">
              <button class="btn btn-sm btn-outline" onclick="openEditModal({{ $p->toJson() }})">&#9998; Edit</button>
              <form method="POST" action="{{ route('ec.participants.store') }}" style="display:inline">
                @csrf
                <input type="hidden" name="action" value="toggle"/>
                <input type="hidden" name="participant_id" value="{{ $p->id }}"/>
                <button type="submit" class="btn btn-sm {{ $p->is_active ? 'btn-danger' : 'btn-outline' }}"
                        onclick="return confirm('{{ $p->is_active ? 'Disable' : 'Enable' }} access for {{ $p->full_name }}?')">
                  {!! $p->is_active ? '&#128683; Disable' : '&#9989; Enable' !!}
                </button>
              </form>
              <form method="POST" action="{{ route('ec.participants.store') }}" style="display:inline" onsubmit="return confirm('Remove this participant?')">
                @csrf
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="participant_id" value="{{ $p->id }}"/>
                <button type="submit" class="btn btn-sm btn-danger">&#128465;</button>
              </form>
            </div>
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
      <h2>&#43; Add Participant</h2>
      <button class="modal-close" onclick="closeModal('addParticipant')">&#10005;</button>
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
            <label class="form-label">Assigned Training *</label>
            <select name="training_id" class="form-control" required>
              <option value="">— Select Training —</option>
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
        <button type="submit" class="btn btn-primary">&#10003; Register</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="modal-editParticipant">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2>&#9998; Edit Participant</h2>
      <button class="modal-close" onclick="closeModal('editParticipant')">&#10005;</button>
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
            <label class="form-label">Assigned Training *</label>
            <select name="training_id" id="edit_training" class="form-control" required>
              <option value="">— Select Training —</option>
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
        <button type="submit" class="btn btn-primary">&#10003; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
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
