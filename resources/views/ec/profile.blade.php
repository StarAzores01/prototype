@extends('layouts.ec')

@section('content')
<style>
.profile-field { position:relative; }
.field-readonly { background:var(--gray-50) !important; color:var(--gray-500) !important; cursor:default !important; }
.pwd-wrap { position:relative; }
.pwd-wrap input { padding-right:40px; }
.pwd-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--gray-400); font-size:14px; }
.pwd-eye:hover { color:var(--blue-primary); }
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>My Profile</span></div>
    <h1>My Profile</h1>
    <p>Manage your personal information and account settings</p>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">

    <div class="card">
      <div class="card-header">
        <div class="card-title">&#128100;Personal Information</div>
        <button type="button" class="btn btn-outline btn-sm" id="editBtn" onclick="enableEdit()">&#9998; Edit</button>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('ec.profile.update') }}" id="profileForm">
          @csrf
          <input type="hidden" name="action" value="update_profile"/>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" id="f_first" class="form-control field-readonly" value="{{ $ecUser->first_name }}" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" id="f_last" class="form-control field-readonly" value="{{ $ecUser->last_name }}" readonly/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" id="f_email" class="form-control field-readonly" value="{{ $ecUser->email }}" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">ID Number <span style="font-size:10px;color:var(--gray-400);font-weight:400">(system-assigned)</span></label>
              <input type="text" class="form-control field-readonly" value="{{ $ecUser->id_number }}" readonly/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Position</label>
              <input type="text" class="form-control field-readonly" value="{{ $ecUser->position }}" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" name="username" id="f_username" class="form-control field-readonly" value="{{ $ecUser->username }}" readonly/>
            </div>
          </div>
          <div id="updateBtnWrap" style="display:none;justify-content:flex-end;gap:10px;margin-top:8px">
            <button type="button" class="btn btn-outline" onclick="cancelEdit()">Cancel</button>
            <button type="submit" class="btn btn-primary">&#10003; Update</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">&#128273;Change Password</div></div>
      <div class="card-body">
        <form method="POST" action="{{ route('ec.profile.update') }}">
          @csrf
          <input type="hidden" name="action" value="change_password"/>
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <div class="pwd-wrap">
              <input type="password" name="current_password" id="pwd_current" class="form-control" required/>
              <span class="pwd-eye" onclick="togglePwd('pwd_current', this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <div class="pwd-wrap">
                <input type="password" name="new_password" id="pwd_new" class="form-control" required/>
                <span class="pwd-eye" onclick="togglePwd('pwd_new', this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
              </div>
              <div class="form-hint">Minimum 6 characters</div>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <div class="pwd-wrap">
                <input type="password" name="confirm_password" id="pwd_confirm" class="form-control" required/>
                <span class="pwd-eye" onclick="togglePwd('pwd_confirm', this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
              </div>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:8px">
            <button type="submit" class="btn btn-primary">&#128274; Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="dash-side">
    <div class="card">
      <div class="card-body" style="text-align:center;padding:32px 24px">
        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--blue-primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;margin:0 auto 16px">
          {{ $initials }}
        </div>
        <div style="font-size:18px;font-weight:800;color:var(--navy)">{{ $ecUser->first_name }} {{ $ecUser->last_name }}</div>
        <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Extension Coordinator</div>
        <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px;text-align:left">
          @foreach([
            ['fa-envelope', $ecUser->email ?? '—'],
            ['fa-id-card',  $ecUser->id_number ?? '—'],
            ['fa-briefcase',$ecUser->position ?? '—'],
            ['fa-user',     $ecUser->username ?? '—'],
          ] as [$icon, $val])
          <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--gray-700)">
            <i class="fas {{ $icon }}" style="width:16px;color:var(--blue-primary)"></i> {{ $val }}
          </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Account Info</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        @foreach([
          ['Role', 'Extension Coordinator'],
          ['Status', $ecUser->is_active ? 'Active' : 'Inactive'],
          ['Last Login', $ecUser->last_login ? $ecUser->last_login->format('M d, Y g:i A') : 'N/A'],
          ['Member Since', $ecUser->created_at ? $ecUser->created_at->format('M d, Y') : '—'],
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
const editableIds = ['f_first','f_last','f_email','f_username'];

function enableEdit() {
  editableIds.forEach(id => {
    const el = document.getElementById(id);
    el.removeAttribute('readonly');
    el.classList.remove('field-readonly');
  });
  document.getElementById('updateBtnWrap').style.display = 'flex';
  document.getElementById('editBtn').style.display = 'none';
}

function cancelEdit() {
  editableIds.forEach(id => {
    const el = document.getElementById(id);
    el.setAttribute('readonly', true);
    el.classList.add('field-readonly');
  });
  document.getElementById('updateBtnWrap').style.display = 'none';
  document.getElementById('editBtn').style.display = '';
}

function togglePwd(inputId, icon) {
  const input = document.getElementById(inputId);
  const i = icon.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
  } else {
    input.type = 'password';
  }
}
</script>
@endsection
