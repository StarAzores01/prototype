<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Create Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh; font-family: 'Poppins', sans-serif;
      background: linear-gradient(150deg, #010E1F 0%, #051828 35%, #07213A 65%, #040F1C 100%);
      display: flex; align-items: center; justify-content: center;
    }
    .page {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; padding: 40px 24px; width: 100%;
    }
    .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .brand-name { color: #fff; font-size: 20px; font-weight: 800; }
    .brand-sub  { color: rgba(255,255,255,.5); font-size: 12px; }
    h1 { font-size: 26px; font-weight: 800; color: #fff; margin: 0 0 8px; text-align: center; }
    .sub { color: rgba(255,255,255,.6); font-size: 14px; margin-bottom: 36px; text-align: center; }
    .cards { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
    .card {
      background: #fff; border-radius: 18px; padding: 28px 20px;
      width: 160px; text-align: center; text-decoration: none; color: inherit;
      display: flex; flex-direction: column; align-items: center; gap: 12px;
      box-shadow: 0 20px 56px rgba(0,0,0,.5);
      transition: transform .2s;
    }
    .card:hover { transform: translateY(-6px); }
    .card-icon {
      width: 64px; height: 64px; border-radius: 18px;
      display: flex; align-items: center; justify-content: center; font-size: 28px;
    }
    .card-label { font-size: 13px; font-weight: 800; color: #09182F; line-height: 1.3; }
    .card-btn {
      display: block; width: 100%; padding: 9px 0;
      border-radius: 10px; font-size: 13px; font-weight: 700; color: #fff;
      text-align: center;
    }
    .footer-links { margin-top: 32px; text-align: center; font-size: 13px; color: rgba(255,255,255,.55); }
    .footer-links a { color: #60A5FA; font-weight: 600; text-decoration: none; }
    .back-btn {
      display: inline-flex; align-items: center; gap: 6px; margin-top: 14px;
      font-size: 12.5px; color: #fff; background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.3); padding: 6px 16px;
      border-radius: 20px; text-decoration: none;
    }
  </style>
</head>
<body>
<div class="page">

  <div class="brand">
    <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT" style="width:44px;height:44px;object-fit:contain" onerror="this.style.display='none'"/>
    <div>
      <div class="brand-name">PAThrive</div>
      <div class="brand-sub">CIT &middot; SLSU</div>
    </div>
  </div>

  <h1>Create Your Account</h1>
  <p class="sub">Choose your role to get started</p>

  <div class="cards">

    <a href="{{ route('trainer.signup') }}" class="card">
      <div class="card-icon" style="background:#D1FAE5;color:#10B981"><i class="fas fa-book"></i></div>
      <div class="card-label">Project Leader</div>
      <span class="card-btn" style="background:#10B981">Sign Up <i class="fas fa-arrow-right"></i></span>
    </a>

    <a href="{{ route('beneficiary.signup') }}" class="card">
      <div class="card-icon" style="background:#FEF3C7;color:#F59E0B"><i class="fas fa-graduation-cap"></i></div>
      <div class="card-label">Participant</div>
      <span class="card-btn" style="background:#F59E0B">Sign Up <i class="fas fa-arrow-right"></i></span>
    </a>

    <a href="{{ route('evaluator.signup') }}" class="card">
      <div class="card-icon" style="background:#EDE9FE;color:#8B5CF6"><i class="fas fa-clipboard-list"></i></div>
      <div class="card-label">Evaluator</div>
      <span class="card-btn" style="background:#8B5CF6">Sign Up <i class="fas fa-arrow-right"></i></span>
    </a>

  </div>

  <div class="footer-links">
    @if($loggedIn)
      Logged in as {{ $userName }}? <a href="{{ $dashboardUrl }}">Go to your dashboard</a>
    @else
      Already have an account? <a href="{{ route('login') }}">Log in here</a>
    @endif
  </div>
  <a href="{{ route('home') }}" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>

</div>
</body>
</html>
