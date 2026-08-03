<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>PAThrive</h1>
    <p>Extension Training Management &amp; Impact Assessment Tracking System</p>

    @if ($errors->any())
      <div class="alert" style="background:#FDE8E8;color:#9B1C1C;margin-bottom:16px">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf
      <div class="form-group">
        <label class="form-label">Username or Email</label>
        <input type="text" name="login" class="form-control" value="{{ old('login') }}" required autofocus/>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required/>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">Log In</button>
    </form>

    <div class="auth-links">
      Not registered yet?
      <a href="{{ route('trainer.signup') }}">Project Leader</a> ·
      <a href="{{ route('evaluator.signup') }}">Evaluator</a> ·
      <a href="{{ route('beneficiary.signup') }}">Beneficiary</a>
    </div>
  </div>
</div>
</body>
</html>
