<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – About</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
  <style>
    body { font-family:'Poppins',sans-serif; margin:0; background:#F0F6FF; color:#334155; }
    .lp-nav { position:fixed;top:0;left:0;right:0;z-index:500;height:68px;background:rgba(9,24,47,.96);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;transition:all .24s; }
    .lp-nav.scrolled { background:rgba(9,24,47,.99);box-shadow:0 4px 24px rgba(0,0,0,.3); }
    .lp-nav-inner { display:flex;align-items:center;justify-content:space-between;width:100%;max-width:1200px;margin:0 auto;padding:0 28px; }
    .lp-brand { display:flex;align-items:center;gap:12px; }
    .lp-brand-logo { width:48px;height:48px;border-radius:10px;overflow:hidden;background:transparent;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
    .lp-brand-logo img { width:44px;height:44px;object-fit:contain; }
    .lp-brand-name { font-size:18px;font-weight:800;color:#fff;letter-spacing:-.4px; }
    .lp-brand-sub  { font-size:10px;color:rgba(255,255,255,.45);letter-spacing:.4px; }
    .lp-links { display:flex;align-items:center;gap:4px; }
    .lp-link { padding:7px 14px;border-radius:8px;font-size:13.5px;font-weight:500;color:rgba(255,255,255,.7);transition:all .24s;text-decoration:none; }
    .lp-link:hover,.lp-link.active { background:rgba(255,255,255,.08);color:#fff; }
    .lp-nav-actions { display:flex;align-items:center;gap:10px; }
    .lp-btn-login { padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;color:rgba(255,255,255,.85);background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);transition:all .24s;text-decoration:none;display:inline-flex;align-items:center;gap:7px; }
    .lp-btn-login:hover { background:rgba(255,255,255,.15);color:#fff; }
    .lp-btn-signup { padding:8px 20px;border-radius:8px;font-size:13.5px;font-weight:700;background:linear-gradient(135deg,#1A56DB,#2E6BF0);color:#fff;box-shadow:0 2px 10px rgba(26,86,219,.45);transition:all .24s;text-decoration:none;display:inline-flex;align-items:center;gap:7px; }
    .lp-btn-signup:hover { transform:translateY(-1px);box-shadow:0 4px 18px rgba(26,86,219,.6); }
    .page-hero { padding:120px 0 60px;background:linear-gradient(135deg,#09182F,#102545,#1A3A72);text-align:center; }
    .eyebrow { display:inline-flex;align-items:center;gap:8px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);color:#38BDF8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;padding:5px 14px;border-radius:40px;margin-bottom:20px; }
    .sec-badge { display:inline-flex;align-items:center;gap:8px;background:rgba(26,86,219,.08);color:#1A56DB;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;padding:5px 14px;border-radius:40px;border:1px solid rgba(26,86,219,.15);margin-bottom:14px; }
    .feature-row { display:flex;flex-direction:column;gap:16px; }
    .feature-item { display:flex;align-items:flex-start;gap:14px;background:#fff;border:1px solid #EEF2F7;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(9,24,47,.06); }
    .feature-ico { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
    .card-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px; }
    .info-card { background:#fff;border-radius:14px;padding:24px;border:1px solid #EEF2F7;box-shadow:0 2px 8px rgba(9,24,47,.06); }
    .lp-footer { background:#05101F;padding:52px 0 28px; }
    .lp-container { max-width:1200px;margin:0 auto;padding:0 28px; }
    .lp-footer-grid { display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px; }
    .lp-footer-logo { display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .lp-footer-logo-box { width:44px;height:44px;border-radius:9px;overflow:hidden;background:transparent;display:flex;align-items:center;justify-content:center; }
    .lp-footer-logo-box img { width:40px;height:40px;object-fit:contain; }
    .lp-footer-logo-name { font-size:18px;font-weight:800;color:#fff; }
    .lp-footer-tagline { font-size:13px;color:rgba(255,255,255,.4);line-height:1.7;max-width:280px;margin-bottom:16px; }
    .lp-footer-contact { display:flex;flex-direction:column;gap:8px; }
    .lp-footer-ci { display:flex;align-items:flex-start;gap:8px;font-size:12.5px;color:rgba(255,255,255,.45); }
    .lp-footer-ci i { font-size:11px;color:#38BDF8;margin-top:2px;flex-shrink:0; }
    .lp-footer-col-title { font-size:13px;font-weight:700;color:rgba(255,255,255,.7);margin-bottom:16px;text-transform:uppercase;letter-spacing:.6px; }
    .lp-footer-links { display:flex;flex-direction:column;gap:9px; }
    .lp-footer-link { font-size:13px;color:rgba(255,255,255,.4);transition:all .24s;text-decoration:none;display:block; }
    .lp-footer-link:hover { color:rgba(255,255,255,.8); }
    .lp-footer-bottom { padding-top:24px;border-top:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px; }
    .lp-footer-copy { font-size:12px;color:rgba(255,255,255,.3); }
    .lp-footer-badges { display:flex;gap:8px; }
    .lp-footer-badge { font-size:10px;font-weight:600;color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.12);padding:3px 9px;border-radius:20px; }
    @media(max-width:960px){ .lp-footer-grid{grid-template-columns:1fr 1fr;} .lp-links{display:none;} }
    @media(max-width:600px){ .lp-footer-grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="lp-nav" id="lpNav">
  <div class="lp-nav-inner">
    <div class="lp-brand">
      <div class="lp-brand-logo">
        <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT" onerror="this.style.display='none'"/>
      </div>
      <div>
        <div class="lp-brand-name">PAThrive</div>
        <div class="lp-brand-sub">CIT &middot; SLSU</div>
      </div>
    </div>
    <div class="lp-links">
      <a href="{{ route('home') }}"            class="lp-link">Home</a>
      <a href="{{ route('about') }}"            class="lp-link active">About</a>
      <a href="{{ route('trainings-public') }}" class="lp-link">Trainings</a>
      <a href="{{ route('contact') }}"          class="lp-link">Contact</a>
    </div>
    @include('public.partials.nav', ['navPrefix' => 'lp-'])
  </div>
</nav>

<!-- PAGE HERO -->
<div class="page-hero">
  <div style="max-width:700px;margin:0 auto;padding:0 24px">
    <div class="eyebrow">About PAThrive</div>
    <h1 style="font-size:clamp(28px,4vw,44px);font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2">About the System &amp; CIT Extension Programs</h1>
    <p style="font-size:15px;color:rgba(255,255,255,.65);line-height:1.7">Learn about PAThrive and the College of Industrial Technology's commitment to community development through extension services.</p>
  </div>
</div>

<!-- WHAT IS PATHRIVE -->
<section style="padding:80px 0;background:#fff">
  <div style="max-width:1100px;margin:0 auto;padding:0 28px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center">
    <div>
      <div class="sec-badge">The System</div>
      <h2 style="font-size:clamp(22px,3vw,34px);font-weight:800;color:#09182F;margin-bottom:16px;line-height:1.2">What is PAThrive?</h2>
      <p style="font-size:15px;color:#64748B;line-height:1.75;margin-bottom:14px">
        <strong>PAThrive</strong> is a web-based Extension Training Management and Impact Assessment Tracking System developed for the College of Industrial Technology (CIT) of Southern Luzon State University – Main Campus.
      </p>
      <p style="font-size:15px;color:#64748B;line-height:1.75;margin-bottom:14px">
        The system centralizes the management of CIT extension training programs — from planning and scheduling to participant registration, evaluation, and post-training skills utilization tracking.
      </p>
      <p style="font-size:15px;color:#64748B;line-height:1.75">
        PAThrive supports CHED compliance reporting and enables the Extension Coordinator, Project Leaders, and beneficiaries to collaborate efficiently within a single platform.
      </p>
    </div>
    <div class="feature-row">
      @foreach ([
        ['<i class="fas fa-book"></i>','Training Management','Create, schedule, and monitor extension training programs across all CIT specializations.','rgba(26,86,219,.08)','#1A56DB'],
        ['<i class="fas fa-users"></i>','Beneficiary Tracking','Register and track participants, attendance, and post-training outcomes in one place.','rgba(16,185,129,.08)','#10B981'],
        ['<i class="fas fa-square-check"></i>','Evaluation & Feedback','Collect and analyze participant evaluations to continuously improve training quality.','rgba(245,158,11,.08)','#F59E0B'],
        ['<i class="fas fa-chart-line"></i>','Skills Utilization','Track how beneficiaries apply their skills — personal use, income generation, and employment.','rgba(99,102,241,.08)','#6366F1'],
        ['<i class="fas fa-file"></i>','Automated Reports','Generate CHED-compliant accomplishment reports and training summaries with ease.','rgba(239,68,68,.08)','#EF4444'],
      ] as [$icon, $title, $desc, $bg, $color])
      <div class="feature-item">
        <div class="feature-ico" style="background:{{ $bg }};color:{{ $color }};font-size:20px">{!! $icon !!}</div>
        <div>
          <div style="font-weight:700;font-size:14px;color:#09182F;margin-bottom:4px">{{ $title }}</div>
          <div style="font-size:13px;color:#64748B;line-height:1.6">{{ $desc }}</div>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- ABOUT CIT -->
<section style="padding:80px 0;background:#F0F6FF">
  <div style="max-width:1100px;margin:0 auto;padding:0 28px">
    <div style="text-align:center;margin-bottom:48px">
      <div class="sec-badge">About the College</div>
      <h2 style="font-size:clamp(22px,3vw,34px);font-weight:800;color:#09182F;margin-bottom:12px">College of Industrial Technology (CIT)</h2>
      <p style="font-size:15px;color:#64748B;max-width:640px;margin:0 auto;line-height:1.7">
        The CIT of Southern Luzon State University provides technical and vocational education that prepares individuals for industry, employment, and entrepreneurship.
      </p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start">
      <div>
        <p style="font-size:15px;color:#64748B;line-height:1.75;margin-bottom:14px">
          The <strong>College of Industrial Technology (CIT)</strong> offers various specialization areas such as Culinary Technology, Apparel and Fashion Technology, Computer Technology, Information Technology, Electronics Technology, Automotive Technology, Mechanical Technology, and Print Media Technology.
        </p>
        <p style="font-size:15px;color:#64748B;line-height:1.75;margin-bottom:14px">
          CIT plays a vital role in developing skilled and competent individuals by combining theoretical knowledge with hands-on training, equipping students and community members with industry-relevant competencies.
        </p>
        <p style="font-size:15px;color:#64748B;line-height:1.75">
          In addition to its academic functions, CIT actively participates in community development through extension services, contributing to the university's mission of promoting inclusive growth and sustainable development.
        </p>
      </div>
      <div class="card-grid" style="grid-template-columns:1fr 1fr">
        @foreach ([
          ['fa-graduation-cap','Technical & Vocational Education','Offering 8 specialization areas aligned with industry needs.'],
          ['fa-screwdriver-wrench','Hands-On Training','Combining theory with practical skills for workforce readiness.'],
          ['fa-seedling','Community Development','Contributing to inclusive growth through extension service programs.'],
          ['fa-chart-column','CHED Compliance','All programs documented and reported per CHED requirements.'],
        ] as [$em, $title, $desc])
        <div class="info-card">
          <div style="font-size:28px;margin-bottom:10px"><i class="fas {{ $em }}" style="color:#1A56DB"></i></div>
          <div style="font-weight:700;font-size:14px;color:#09182F;margin-bottom:6px">{{ $title }}</div>
          <div style="font-size:13px;color:#64748B;line-height:1.6">{{ $desc }}</div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</section>

<!-- CIT EXTENSION PROGRAMS -->
<section style="padding:80px 0;background:#fff">
  <div style="max-width:1100px;margin:0 auto;padding:0 28px">
    <div style="text-align:center;margin-bottom:48px">
      <div class="sec-badge">Extension Programs</div>
      <h2 style="font-size:clamp(22px,3vw,34px);font-weight:800;color:#09182F;margin-bottom:12px">CIT Extension Programs</h2>
      <p style="font-size:15px;color:#64748B;max-width:600px;margin:0 auto;line-height:1.7">The extension programs of CIT are part of SLSU's initiatives to deliver knowledge, skills, and technical expertise to communities.</p>
    </div>
    <div class="card-grid">
      @foreach ([
        ['fa-handshake','LGU & Partner Collaboration','Conducted in partnership with local government units, NGOs, and community stakeholders for broader community reach.'],
        ['fa-users','Inclusive Beneficiary Coverage','Reaching out-of-school youth, displaced workers, solo parents, farmers, retirees, and other qualified community members.'],
        ['fa-clipboard-list','Structured Implementation','From needs assessment and proposal preparation to Project Leader assignment, approval, and post-training evaluation — managed end to end.'],
        ['fa-seedling','Community Development','Actively contributing to inclusive growth and sustainable development through CIT extension service programs.'],
        ['fa-graduation-cap','Skills & Livelihood','Empowering participants to apply their learning for personal development, employment, or livelihood generation.'],
        ['fa-chart-column','CHED Compliance','All programs are documented and reported in compliance with CHED extension service requirements and standards.'],
      ] as [$em, $title, $desc])
      <div class="info-card">
        <div style="font-size:32px;margin-bottom:12px"><i class="fas {{ $em }}" style="color:#1A56DB"></i></div>
        <div style="font-weight:700;font-size:15px;color:#09182F;margin-bottom:8px">{{ $title }}</div>
        <div style="font-size:13px;color:#64748B;line-height:1.65">{{ $desc }}</div>
      </div>
      @endforeach
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="lp-footer">
  <div class="lp-container">
    <div class="lp-footer-grid">
      <div>
        <div class="lp-footer-logo">
          <div class="lp-footer-logo-box"><img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT" onerror="this.style.display='none'"/></div>
          <div class="lp-footer-logo-name">PAThrive</div>
        </div>
        <p class="lp-footer-tagline">Extension Training Management &amp; Impact Assessment Tracking System — College of Industrial Technology, SLSU</p>
        <div class="lp-footer-contact">
          <div class="lp-footer-ci"><i class="fas fa-location-dot"></i> SLSU Main Campus, Lucban, Quezon</div>
          <div class="lp-footer-ci"><i class="fas fa-envelope"></i> cit.extension@slsu.edu.ph</div>
          <div class="lp-footer-ci"><i class="fas fa-phone"></i> (042) 540-XXXX</div>
        </div>
      </div>
      <div>
        <div class="lp-footer-col-title">Pages</div>
        <div class="lp-footer-links">
          <a href="{{ route('home') }}"            class="lp-footer-link">Home</a>
          <a href="{{ route('about') }}"            class="lp-footer-link">About</a>
          <a href="{{ route('trainings-public') }}" class="lp-footer-link">Training Programs</a>
          <a href="{{ route('contact') }}"          class="lp-footer-link">Contact Us</a>
        </div>
      </div>
      <div>
        <div class="lp-footer-col-title">Training Areas</div>
        <div class="lp-footer-links">
          <span class="lp-footer-link">Mechanical Technology</span>
          <span class="lp-footer-link">Automotive Technology</span>
          <span class="lp-footer-link">Computer Technology</span>
          <span class="lp-footer-link">Electronics Technology</span>
          <span class="lp-footer-link">Culinary Technology</span>
          <span class="lp-footer-link">Apparel and Fashion Technology</span>
          <span class="lp-footer-link">Print Media Technology</span>
          <span class="lp-footer-link">Information Technology</span>
        </div>
      </div>
      <div>
        <div class="lp-footer-col-title">Access</div>
        <div class="lp-footer-links">
          @if($loggedIn)
            <a href="{{ $dashboardUrl }}" class="lp-footer-link">Go to Dashboard</a>
          @else
            <a href="{{ route('login') }}"       class="lp-footer-link">Log In to System</a>
            <a href="{{ route('choose-role') }}" class="lp-footer-link">Sign Up</a>
          @endif
          <a href="{{ route('terms') }}"       class="lp-footer-link">Terms of Use</a>
          <a href="{{ route('privacy') }}"     class="lp-footer-link">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="lp-footer-bottom">
      <div class="lp-footer-copy">&copy; {{ now()->year }} PAThrive – SLSU College of Industrial Technology. All rights reserved.</div>
      <div class="lp-footer-badges">
        <div class="lp-footer-badge">CHED Compliant</div>
        <div class="lp-footer-badge">SDG Aligned</div>
      </div>
    </div>
  </div>
</footer>

<script>
window.addEventListener('scroll', () => {
  document.getElementById('lpNav').classList.toggle('scrolled', window.scrollY > 60);
});
</script>
</body>
</html>
