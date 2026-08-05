<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Create Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
  <style>
    body.auth-body {
      background:
        radial-gradient(ellipse 60% 50% at 15% 60%, rgba(13, 63, 171, 0.45) 0%, transparent 60%),
        radial-gradient(ellipse 50% 40% at 85% 20%, rgba(13, 63, 171, 0.45) 0%, transparent 55%),
        linear-gradient(150deg, #010E1F 0%, #051828 35%, #07213A 65%, #040F1C 100%) !important;
      position: relative; overflow: hidden;
    }
    body.auth-body::before {
      content: '';
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
      background-image:
        linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
      background-size: 52px 52px;
    }
    body.auth-body::after {
      content: '';
      position: fixed; top: -100px; right: -100px;
      width: 460px; height: 460px; border-radius: 50%; z-index: 0;
      background: radial-gradient(circle, rgba(13, 63, 171, 0.45) 0%, transparent 65%);
      pointer-events: none;
      animation: liPulse 9s ease-in-out infinite;
    }
    @keyframes liPulse {
      0%,100% { transform: scale(1) translateY(0); }
      50%      { transform: scale(1.07) translateY(-24px); }
    }
    .li-orb-bl {
      position: fixed; bottom: -80px; left: -80px;
      width: 360px; height: 360px; border-radius: 50%; z-index: 0;
      background: radial-gradient(circle, rgba(13, 63, 171, 0.45) 0%, transparent 65%);
      pointer-events: none;
      animation: liPulse 12s ease-in-out infinite reverse;
    }
    body.auth-body .auth-card {
      position: relative; z-index: 10; background: #fff !important;
      border-radius: 22px !important; overflow: hidden;
      box-shadow: 0 0 0 1px rgba(255,255,255,.08), 0 28px 72px rgba(0,0,0,.6), 0 8px 28px rgba(0,0,0,.4) !important;
    }
    body.auth-body .auth-card-header {
      background: linear-gradient(140deg, #122B57 0%, #071428 55%, #0B1E3D 100%) !important;
      padding: 30px 32px 26px !important; text-align: center; position: relative; overflow: hidden;
    }
    body.auth-body .auth-card-header::before {
      content: ''; position: absolute; top: -48px; right: -48px;
      width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,.07); pointer-events: none;
    }
    body.auth-body .auth-card-header::after {
      content: ''; position: absolute; bottom: -32px; left: -32px;
      width: 130px; height: 130px; border-radius: 50%; background: rgba(255,255,255,.05); pointer-events: none;
    }
    body.auth-body .auth-card-header .brand-logo {
      width: 58px !important; height: 58px !important; border-radius: 15px !important;
      background: rgba(255,255,255,.14) !important; border: 1.5px solid rgba(255,255,255,.28) !important;
      box-shadow: 0 4px 18px rgba(0,0,0,.28) !important;
      display: flex !important; align-items: center !important; justify-content: center !important;
      margin: 0 auto 14px !important; position: relative; z-index: 1;
    }
    body.auth-body .auth-card-header .brand-logo img { width: 40px !important; height: 40px !important; object-fit: contain !important; }
    body.auth-body .auth-card-header h1 { color: #fff !important; font-size: 20px !important; font-weight: 800 !important; margin-bottom: 5px !important; position: relative; z-index: 1; letter-spacing: -.3px; }
    body.auth-body .auth-card-header p  { color: rgba(255,255,255,.72) !important; font-size: 12.5px !important; position: relative; z-index: 1; }
    body.auth-body .auth-card-body { padding: 26px 32px 22px !important; background: #fff !important; }
    body.auth-body .alert { border-radius: 10px !important; font-size: 13px !important; padding: 11px 14px !important; margin-bottom: 18px !important; display: flex !important; align-items: flex-start !important; gap: 9px !important; }
    body.auth-body .alert-danger  { background: #FFF1F2 !important; color: #9F1239 !important; border: 1.5px solid #FECDD3 !important; }
    body.auth-body .alert-success { background: #F0FDF4 !important; color: #166534 !important; border: 1.5px solid #BBF7D0 !important; }
    body.auth-body .alert-info    { background: #EFF6FF !important; color: #122B57 !important; border: 1.5px solid #BFDBFE !important; }
    body.auth-body .form-label { font-size: 12.5px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 7px !important; }
    body.auth-body .form-control { padding: 11px 13px !important; font-size: 13.5px !important; border: 1.5px solid #E2E8F0 !important; border-radius: 9px !important; color: #1E293B !important; background: #F8FAFC !important; transition: border-color .2s, box-shadow .2s, background .2s !important; }
    body.auth-body .form-control:focus { border-color: #122B57 !important; background: #fff !important; box-shadow: 0 0 0 3px rgba(26,86,219,.12) !important; }
    body.auth-body .form-control::placeholder { color: #94A3B8 !important; }
    body.auth-body .password-wrap .form-control { padding-right: 44px !important; }
    body.auth-body .password-toggle { position: absolute !important; right: 13px !important; top: 50% !important; transform: translateY(-50%) !important; cursor: pointer !important; font-size: 16px !important; color: #94A3B8 !important; line-height: 1 !important; transition: color .2s !important; }
    body.auth-body .password-toggle:hover { color: #122B57 !important; }
    body.auth-body .btn-primary { background: linear-gradient(135deg, #1A56DB 0%, #1D4ED8 100%) !important; box-shadow: 0 4px 16px rgba(26,86,219,.44) !important; border-radius: 10px !important; font-size: 14.5px !important; font-weight: 700 !important; transition: transform .2s, box-shadow .2s !important; }
    body.auth-body .btn-primary:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 26px rgba(26,86,219,.56) !important; }
    body.auth-body .auth-footer { background: #F7FAFF !important; border-top: 1px solid #E8EFF7 !important; padding: 15px 32px !important; font-size: 13px !important; color: #475569 !important; }
    body.auth-body .auth-footer a { color: #1A56DB !important; font-weight: 700 !important; }
    body.auth-body .auth-footer a:hover { text-decoration: underline !important; }
  </style>
</head>
<body class="auth-body">
<div class="li-orb-bl"></div>

<div class="auth-card" style="max-width:520px">

  <div class="auth-card-header">
    <a href="{{ route('home') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:#fff;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);padding:6px 14px;border-radius:20px;margin-bottom:16px;text-decoration:none">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    <div class="brand-logo" style="margin:0 auto 14px">
      <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT" style="width:44px;height:44px;object-fit:contain" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
    </div>
    <h1>Create Your Account</h1>
    <p>Extension Coordinator &middot; CIT-SLSU</p>
  </div>

  <div class="auth-card-body">
    @if ($errors->any())
    <div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i> Please fix the errors below.</div>
    @endif

    <form method="POST" action="{{ route('ec.signup') }}" novalidate>
      @csrf
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" placeholder="Juan" required/>
          @error('first_name')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" placeholder="Dela Cruz" required/>
          @error('last_name')<div class="form-error">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Username *</label>
        <input type="text" name="username" class="form-control" value="{{ old('username') }}" placeholder="juandelacruz" required/>
        @error('username')<div class="form-error">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label">Institutional Email Address *</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="juan.delacruz@slsu.edu.ph" required/>
        @error('email')<div class="form-error">{{ $message }}</div>@enderror
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Position *</label>
          <select name="position" class="form-control" required>
            <option value="">Select position…</option>
            @foreach ($positions as $pos)
            <option value="{{ $pos }}" {{ old('position') === $pos ? 'selected' : '' }}>{{ $pos }}</option>
            @endforeach
          </select>
          @error('position')<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">ID Number *</label>
          <input type="text" name="id_number" class="form-control" value="{{ old('id_number') }}" placeholder="EC-2024-001" required/>
          @error('id_number')<div class="form-error">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="password-wrap">
          <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required/>
          <span class="password-toggle" onclick="togglePwd('password','eyeIcon1')" id="eyeIcon1"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
        <div class="strength-bar" id="strengthBar">
          <span id="s1"></span><span id="s2"></span><span id="s3"></span><span id="s4"></span>
        </div>
        @error('password')<div class="form-error">{{ $message }}</div>@enderror
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <div class="password-wrap">
          <input type="password" name="password2" id="password2" class="form-control" placeholder="Repeat your password" required/>
          <span class="password-toggle" onclick="togglePwd('password2','eyeIcon2')" id="eyeIcon2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
        @error('password2')<div class="form-error">{{ $message }}</div>@enderror
      </div>

      <div class="form-group" style="display:flex;align-items:flex-start;gap:10px">
        <input type="checkbox" name="agree" value="1" id="agree" {{ old('agree') ? 'checked' : '' }} style="margin-top:3px;width:16px;height:16px;flex-shrink:0"/>
        <label for="agree" style="font-size:12.5px;color:var(--gray-600);cursor:pointer">
          I agree to the <a href="{{ url('/terms.php') }}" target="_blank" style="color:var(--blue-primary);font-weight:600">Terms of Use</a> and <a href="{{ url('/privacy.php') }}" target="_blank" style="color:var(--blue-primary);font-weight:600">Privacy Policy</a>
        </label>
      </div>
      @error('agree')<div class="form-error" style="margin-top:-10px;margin-bottom:12px">{{ $message }}</div>@enderror

      <button type="submit" class="btn btn-primary" style="width:100%;padding:11px;font-size:14px;justify-content:center">
        <i class="fas fa-arrow-right"></i> Create Account
      </button>
    </form>
  </div>

  <div class="auth-footer">
    Already have an account? <a href="{{ route('login') }}">Log in here</a>
  </div>
</div>

<script>
function togglePwd(id, iconId) {
  const f = document.getElementById(id);
  const i = document.getElementById(iconId);
  if (f.type === 'password') { f.type = 'text'; i.innerHTML = "<svg xmlns=\"http:\/\/www.w3.org\/2000\/svg\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24\"\/><line x1=\"1\" y1=\"1\" x2=\"23\" y2=\"23\"\/><\/svg>"; }
  else { f.type = 'password'; i.innerHTML = "<svg xmlns=\"http:\/\/www.w3.org\/2000\/svg\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z\"\/><circle cx=\"12\" cy=\"12\" r=\"3\"\/><\/svg>"; }
}
document.getElementById('password').addEventListener('input', function () {
  const v = this.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[\W_]/.test(v)) score++;
  const bars = ['s1','s2','s3','s4'];
  const cls  = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
  bars.forEach((b, i) => {
    const el = document.getElementById(b);
    el.className = i < score ? cls : '';
  });
});
</script>
</body>
</html>
