@extends('layouts.evaluator')

@section('content')
<style>
.field-readonly { background:var(--gray-50) !important; color:var(--gray-500) !important; cursor:default !important; }
.pwd-wrap { position:relative; }
.pwd-wrap input { padding-right:40px; }
.pwd-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--gray-400); font-size:14px; }
.pwd-eye:hover { color:var(--blue-primary); }
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>My Profile</span></div>
    <h1>My Profile</h1>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">

    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-user"></i> Personal Information</div>
        <button type="button" class="btn btn-outline btn-sm" id="editBtn" onclick="enableEdit()"><i class="fas fa-pen"></i> Edit</button>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('evaluator.profile.update') }}">
          @csrf
          <input type="hidden" name="action" value="update_profile"/>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" id="f_first" class="form-control field-readonly" value="{{ $evaluator->first_name }}" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" id="f_last" class="form-control field-readonly" value="{{ $evaluator->last_name }}" readonly/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="f_email" class="form-control field-readonly" value="{{ $evaluator->email }}" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Evaluator ID <span style="font-size:10px;color:var(--gray-400)">(system-assigned)</span></label>
              <input type="text" class="form-control field-readonly" value="{{ $evaluator->id_number }}" readonly/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" name="username" id="f_username" class="form-control field-readonly" value="{{ $evaluator->username }}" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Department</label>
              <input type="text" class="form-control field-readonly" value="{{ $evaluator->department }}" readonly/>
            </div>
          </div>
          <div id="updateBtnWrap" style="display:none;justify-content:flex-end;gap:10px;margin-top:8px">
            <button type="button" class="btn btn-outline" onclick="cancelEdit()">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Update</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-key"></i> Change Password</div></div>
      <div class="card-body">
        <form method="POST" action="{{ route('evaluator.profile.update') }}">
          @csrf
          <input type="hidden" name="action" value="change_password"/>
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <div class="pwd-wrap">
              <input type="password" name="current_password" id="p1" class="form-control" required/>
              <span class="pwd-eye" onclick="tp('p1',this)"><i class="fas fa-eye"></i></span>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <div class="pwd-wrap">
                <input type="password" name="new_password" id="p2" class="form-control" required/>
                <span class="pwd-eye" onclick="tp('p2',this)"><i class="fas fa-eye"></i></span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <div class="pwd-wrap">
                <input type="password" name="confirm_password" id="p3" class="form-control" required/>
                <span class="pwd-eye" onclick="tp('p3',this)"><i class="fas fa-eye"></i></span>
              </div>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:8px">
            <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="dash-side">
    <div class="card">
      <div class="card-body" style="text-align:center;padding:32px 24px">
        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#8B5CF6,#7C3AED);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;margin:0 auto 16px">{{ $initials }}</div>
        <div style="font-size:18px;font-weight:800;color:var(--text-heading)">{{ $evaluator->first_name }} {{ $evaluator->last_name }}</div>
        <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Evaluator</div>
        <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px;text-align:left">
          @foreach([
            ['fa-envelope', $evaluator->email ?? '—'],
            ['fa-id-card',  $evaluator->id_number ?? '—'],
            ['fa-building', $evaluator->department ?? '—'],
          ] as [$icon, $val])
          <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--gray-700)">
            <i class="fas {{ $icon }}" style="width:16px;color:#8B5CF6"></i>{{ $val }}
          </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Account Info</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        @foreach([
          ['Role', 'Evaluator'],
          ['Status', $evaluator->is_active ? 'Active' : 'Inactive'],
          ['Member Since', $evaluator->created_at ? $evaluator->created_at->format('M d, Y') : '—'],
        ] as [$label, $val])
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span style="color:var(--gray-400)">{{ $label }}</span>
          <span style="font-weight:600;color:var(--gray-800)">{{ $val }}</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

<script>
const ids = ['f_first','f_last','f_email','f_username'];
function enableEdit() {
  ids.forEach(id => { const el = document.getElementById(id); el.removeAttribute('readonly'); el.classList.remove('field-readonly'); });
  document.getElementById('updateBtnWrap').style.display = 'flex';
  document.getElementById('editBtn').style.display = 'none';
}
function cancelEdit() {
  ids.forEach(id => { const el = document.getElementById(id); el.setAttribute('readonly', true); el.classList.add('field-readonly'); });
  document.getElementById('updateBtnWrap').style.display = 'none';
  document.getElementById('editBtn').style.display = '';
}
function tp(id, icon) {
  const f = document.getElementById(id);
  f.type = f.type === 'password' ? 'text' : 'password';
  const i = icon.querySelector('i');
  if (i) i.className = f.type === 'password' ? 'fas fa-eye-slash' : 'fas fa-eye';
}
</script>
@endsection
