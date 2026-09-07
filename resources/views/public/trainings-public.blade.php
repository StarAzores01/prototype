@php
  // Static sample programs, matching the original trainings-public.php exactly
  // (this public marketing page was never wired to the real trainings table).
  $programs = [
    ['emoji'=>'fa-utensils','cat'=>'Culinary Technology',           'title'=>'Basic Pastry Making',          'desc'=>'Hands-on baking and pastry workshop covering bread-making, cake decoration, and basic pastry techniques for livelihood development.','date'=>'Mar 10, 2026','venue'=>'Lucban, Quezon',      'trainer'=>'Aurita A. Laguador',   'pax'=>28,'status'=>'Ongoing'],
    ['emoji'=>'fa-plug','cat'=>'Electronics Technology',        'title'=>'Basic Electronics Servicing',  'desc'=>'Fundamentals of electronics, circuit troubleshooting, and component identification for displaced workers and out-of-school youth.','date'=>'Feb 18, 2026','venue'=>'Tayabas City',       'trainer'=>'Jose D. Sanvictores',  'pax'=>35,'status'=>'Completed'],
    ['emoji'=>'fa-laptop','cat'=>'Computer Technology',           'title'=>'Computer Literacy Program',    'desc'=>'MS Office, internet literacy, and digital tools for solo parents and community members seeking employment opportunities.','date'=>'Mar 5, 2026', 'venue'=>'SLSU Main Campus',   'trainer'=>'Reynaldo V. Danganan', 'pax'=>40,'status'=>'Ongoing'],
    ['emoji'=>'fa-scissors','cat'=>'Apparel and Fashion Technology','title'=>'Dressmaking & Sewing Basics',  'desc'=>'Introduction to garment construction, pattern-making, and basic sewing techniques for women beneficiaries and out-of-school youth.','date'=>'Apr 2, 2026', 'venue'=>'Candelaria, Quezon', 'trainer'=>'Maricel O. Lingatong', 'pax'=>25,'status'=>'Upcoming'],
    ['emoji'=>'fa-desktop','cat'=>'Information Technology',       'title'=>'Web Design Fundamentals',      'desc'=>'HTML, CSS, and basic web design principles for young adults seeking digital livelihood opportunities.','date'=>'Mar 28, 2026','venue'=>'Lucena City',        'trainer'=>'Devie S. Bello',       'pax'=>50,'status'=>'Upcoming'],
    ['emoji'=>'fa-car','cat'=>'Automotive Technology',         'title'=>'Basic Automotive Servicing',   'desc'=>'Introduction to vehicle maintenance, engine basics, and automotive safety for displaced workers and youth.','date'=>'May 10, 2026','venue'=>'Mauban, Quezon',     'trainer'=>'Angelito L. Mangubat', 'pax'=>20,'status'=>'Upcoming'],
  ];

  $catColors = [
    'Culinary Technology'            => ['#7C3AED','#4F46E5'],
    'Electronics Technology'         => ['#0F766E','#0891B2'],
    'Computer Technology'            => ['#1D4ED8','#0284C7'],
    'Apparel and Fashion Technology' => ['#BE185D','#9333EA'],
    'Information Technology'         => ['#065F46','#059669'],
    'Automotive Technology'          => ['#D97706','#F59E0B'],
    'Mechanical Technology'          => ['#374151','#6B7280'],
    'Print Media Technology'         => ['#7C2D12','#C2410C'],
  ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Training Programs</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Poppins',sans-serif;background:#F0F6FF;color:#334155}
    .nav{position:fixed;top:0;left:0;right:0;z-index:500;height:68px;
         background:rgba(9,24,47,.96);backdrop-filter:blur(16px);
         border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;transition:all .24s}
    .nav.scrolled{background:rgba(9,24,47,.99);box-shadow:0 4px 24px rgba(0,0,0,.3)}
    .nav-inner{display:flex;align-items:center;justify-content:space-between;
               width:100%;max-width:1200px;margin:0 auto;padding:0 28px}
    .brand{display:flex;align-items:center;gap:12px}
    .brand-logo{width:54px;height:54px;border-radius:12px;overflow:hidden;
                background:#DBEAFE;
                border:1.5px solid #93C5FD;
                display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .brand-logo img{width:46px;height:46px;object-fit:contain}
    .brand-name{font-size:18px;font-weight:800;color:#fff;letter-spacing:-.4px}
    .brand-sub{font-size:10px;color:rgba(255,255,255,.45);letter-spacing:.4px}
    .nav-links{display:flex;align-items:center;gap:4px}
    .nav-link{padding:7px 14px;border-radius:8px;font-size:13.5px;font-weight:500;
              color:rgba(255,255,255,.7);transition:all .24s;text-decoration:none}
    .nav-link:hover,.nav-link.active{background:rgba(255,255,255,.08);color:#fff}
    .nav-actions{display:flex;align-items:center;gap:10px}
    .btn-login{padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;
               color:rgba(255,255,255,.85);background:rgba(255,255,255,.08);
               border:1px solid rgba(255,255,255,.15);transition:all .24s;
               text-decoration:none;display:inline-flex;align-items:center;gap:7px}
    .btn-login:hover{background:rgba(255,255,255,.15);color:#fff}
    .btn-signup{padding:8px 20px;border-radius:8px;font-size:13.5px;font-weight:700;
                background:linear-gradient(135deg,#1A56DB,#2E6BF0);color:#fff;
                box-shadow:0 2px 10px rgba(26,86,219,.45);transition:all .24s;
                text-decoration:none;display:inline-flex;align-items:center;gap:7px}
    .btn-signup:hover{transform:translateY(-1px);box-shadow:0 4px 18px rgba(26,86,219,.6)}
    .hero{padding:120px 0 64px;
          background:linear-gradient(135deg,#09182F 0%,#102545 50%,#1A3A72 100%);
          text-align:center}
    .eyebrow{display:inline-flex;align-items:center;gap:8px;
             background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);
             color:#38BDF8;font-size:11px;font-weight:700;text-transform:uppercase;
             letter-spacing:1.4px;padding:5px 14px;border-radius:40px;margin-bottom:20px}
    .filter-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;
                justify-content:center;margin-bottom:40px}
    .pill{padding:9px 22px;border-radius:40px;font-size:13px;font-weight:600;
          border:1.5px solid #CBD5E1;color:#64748B;background:#fff;
          cursor:pointer;transition:all .22s;user-select:none}
    .pill.active,.pill:hover{background:#1A56DB;color:#fff;border-color:#1A56DB;
                              box-shadow:0 3px 12px rgba(26,86,219,.3)}
    .stats-row{display:flex;gap:20px;justify-content:center;flex-wrap:wrap;margin-bottom:48px}
    .stat-box{background:#fff;border-radius:14px;padding:20px 28px;text-align:center;
              border:1px solid #E2E8F0;box-shadow:0 2px 8px rgba(9,24,47,.06);min-width:140px}
    .stat-num{font-size:28px;font-weight:800;color:#09182F;line-height:1}
    .stat-lbl{font-size:12px;color:#94A3B8;margin-top:4px}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px}
    .card{background:#fff;border-radius:18px;overflow:hidden;
          box-shadow:0 2px 12px rgba(9,24,47,.07);border:1px solid #EEF2F7;
          display:flex;flex-direction:column;transition:all .24s}
    .card:hover{transform:translateY(-6px);box-shadow:0 16px 48px rgba(9,24,47,.18)}
    .card-img{height:160px;display:flex;align-items:center;justify-content:center;
              font-size:56px;position:relative;overflow:hidden}
    .card-img::after{content:'';position:absolute;inset:0;
                     background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,.25))}
    .card-cat{position:absolute;top:12px;left:12px;z-index:1;
              background:rgba(255,255,255,.92);color:#09182F;
              font-size:10px;font-weight:700;padding:4px 10px;border-radius:20px;letter-spacing:.3px}
    .card-em{position:relative;z-index:1;filter:drop-shadow(0 4px 8px rgba(0,0,0,.3))}
    .card-body{padding:20px;flex:1;display:flex;flex-direction:column;gap:10px}
    .card-title{font-size:16px;font-weight:700;color:#09182F;line-height:1.35}
    .card-desc{font-size:13px;color:#64748B;line-height:1.65;flex:1}
    .card-meta{display:flex;flex-wrap:wrap;gap:10px}
    .meta-item{display:flex;align-items:center;gap:5px;font-size:12px;color:#94A3B8}
    .card-foot{display:flex;align-items:center;justify-content:space-between;
               padding-top:12px;border-top:1px solid #EEF2F7;margin-top:auto}
    .badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;
           border-radius:20px;font-size:11px;font-weight:700}
    .badge::before{content:'';width:6px;height:6px;border-radius:50%;flex-shrink:0}
    .badge-ongoing  {background:#DBEAFE;color:#1E3A8A} .badge-ongoing::before  {background:#1A56DB}
    .badge-completed{background:#D1FAE5;color:#065F46} .badge-completed::before{background:#10B981}
    .badge-upcoming {background:#FEF3C7;color:#854D0E} .badge-upcoming::before {background:#F59E0B}
    .card-link{font-size:13px;font-weight:700;color:#1A56DB;text-decoration:none;
               display:flex;align-items:center;gap:6px;transition:gap .2s}
    .card-link:hover{gap:10px}
    .empty{text-align:center;padding:80px 24px;color:#94A3B8}
    .empty i{font-size:48px;margin-bottom:16px;display:block}
    .footer{background:#05101F;padding:52px 0 28px}
    .container{max-width:1200px;margin:0 auto;padding:0 28px}
    .footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px}
    .footer-logo{display:flex;align-items:center;gap:10px;margin-bottom:14px}
    .footer-logo-box{width:50px;height:50px;border-radius:12px;overflow:hidden;
                     background:#DBEAFE;
                     border:1.5px solid #93C5FD;
                     display:flex;align-items:center;justify-content:center}
    .footer-logo-box img{width:44px;height:44px;object-fit:contain}
    .footer-logo-name{font-size:18px;font-weight:800;color:#fff}
    .footer-tagline{font-size:13px;color:rgba(255,255,255,.4);line-height:1.7;max-width:280px;margin-bottom:16px}
    .footer-ci{display:flex;align-items:flex-start;gap:8px;font-size:12.5px;color:rgba(255,255,255,.45);margin-bottom:6px}
    .footer-ci i{font-size:11px;color:#38BDF8;margin-top:2px;flex-shrink:0}
    .footer-col-title{font-size:13px;font-weight:700;color:rgba(255,255,255,.7);
                      margin-bottom:16px;text-transform:uppercase;letter-spacing:.6px}
    .footer-links{display:flex;flex-direction:column;gap:9px}
    .footer-link{font-size:13px;color:rgba(255,255,255,.4);text-decoration:none;display:block;transition:color .2s}
    .footer-link:hover{color:rgba(255,255,255,.8)}
    .footer-bottom{padding-top:24px;border-top:1px solid rgba(255,255,255,.06);
                   display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
    .footer-copy{font-size:12px;color:rgba(255,255,255,.3)}
    .footer-badges{display:flex;gap:8px}
    .footer-badge{font-size:10px;font-weight:600;color:rgba(255,255,255,.4);
                  border:1px solid rgba(255,255,255,.12);padding:3px 9px;border-radius:20px}
    @media(max-width:960px){.footer-grid{grid-template-columns:1fr 1fr}.nav-links{display:none}}
    @media(max-width:600px){.footer-grid{grid-template-columns:1fr}.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="mainNav">
  <div class="nav-inner">
    <div class="brand">
      <div class="brand-logo">
        <img src="{{ \App\Models\PageContent::get('global', 'site_logo', asset('imgs/logofinalpt.png')) }}" alt="CIT" onerror="this.style.display='none'"/>
      </div>
      <div>
        <div class="brand-name">PAThrive</div>
        <div class="brand-sub">CIT &middot; SLSU</div>
      </div>
    </div>
    <div class="nav-links">
      <a href="{{ route('home') }}"            class="nav-link">Home</a>
      <a href="{{ route('about') }}"            class="nav-link">About</a>
      <a href="{{ route('trainings-public') }}" class="nav-link active">Trainings</a>
      <a href="{{ route('contact') }}"          class="nav-link">Contact</a>
    </div>
    @include('public.partials.nav')
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div style="max-width:680px;margin:0 auto;padding:0 24px">
    <div class="eyebrow">{{ \App\Models\PageContent::get('trainings-public', 'hero_eyebrow', 'Extension Trainings') }}</div>
    <h1 style="font-size:clamp(28px,4vw,44px);font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2">
      {{ \App\Models\PageContent::get('trainings-public', 'hero_heading', 'Training Programs') }}
    </h1>
    <p style="font-size:15px;color:rgba(255,255,255,.65);line-height:1.7">
      {{ \App\Models\PageContent::get('trainings-public', 'hero_desc', 'Browse current and upcoming extension training activities conducted by the College of Industrial Technology, SLSU.') }}
    </p>
  </div>
</div>

<!-- MAIN CONTENT -->
<section style="padding:60px 0 80px">
  <div class="container">

    <!-- Filter pills -->
    <div class="filter-bar">
      <div class="pill active" onclick="filter(this,'')">All</div>
      <div class="pill" onclick="filter(this,'Ongoing')">Ongoing</div>
      <div class="pill" onclick="filter(this,'Upcoming')">Upcoming</div>
      <div class="pill" onclick="filter(this,'Completed')">Completed</div>
    </div>

    <!-- Cards -->
    <div class="grid" id="trainGrid">
      @foreach ($programs as $p)
      @php
        $c = $catColors[$p['cat']] ?? ['#1A56DB','#2E6BF0'];
        $grad = "linear-gradient(135deg,{$c[0]},{$c[1]})";
        $badgeClass = 'badge-'.strtolower($p['status']);
      @endphp
      <div class="card" data-status="{{ $p['status'] }}">
        <div class="card-img" style="background:{{ $grad }}">
          <div class="card-cat">{{ $p['cat'] }}</div>
          <div class="card-em"><i class="fas {{ $p['emoji'] }}" style="color:#fff"></i></div>
        </div>
        <div class="card-body">
          <div class="card-title">{{ $p['title'] }}</div>
          <div class="card-desc">{{ $p['desc'] }}</div>
          <div class="card-meta">
            <div class="meta-item"><i class="fas fa-calendar"></i> {{ $p['date'] }}</div>
            <div class="meta-item"><i class="fas fa-user"></i> {{ $p['trainer'] }}</div>
          </div>
          <div class="card-foot">
            <span class="badge {{ $badgeClass }}">{{ $p['status'] }}</span>
            <a href="{{ route('choose-role') }}" class="card-link"></a>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <!-- Empty state (hidden by default) -->
    <div class="empty" id="emptyState" style="display:none">
      <i class="fas fa-magnifying-glass"></i>
      <p>No trainings found for this filter.</p>
    </div>

  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">
          <div class="footer-logo-box"><img src="{{ \App\Models\PageContent::get('global', 'site_logo', asset('imgs/logofinalpt.png')) }}" alt="CIT" onerror="this.style.display='none'"/></div>
          <div class="footer-logo-name">PAThrive</div>
        </div>
        <p class="footer-tagline">{{ \App\Models\PageContent::get('global', 'footer_tagline', 'Extension Training Management & Impact Assessment Tracking System — College of Industrial Technology, Southern Luzon State University') }}</p>
        <div class="footer-ci"><i class="fas fa-location-dot"></i>{{ \App\Models\PageContent::get('global', 'contact_address', 'SLSU Main Campus, Lucban, Quezon, Philippines') }}</div>
        <div class="footer-ci"><i class="fas fa-envelope"></i>{{ \App\Models\PageContent::get('global', 'contact_email', 'cit.extension@slsu.edu.ph') }}</div>
        <div class="footer-ci"><i class="fas fa-phone"></i>{{ \App\Models\PageContent::get('global', 'contact_phone', '(042) 540-XXXX') }}</div>
      </div>
      <div>
        <div class="footer-col-title">Pages</div>
        <div class="footer-links">
          <a href="{{ route('home') }}"            class="footer-link">Home</a>
          <a href="{{ route('about') }}"            class="footer-link">About</a>
          <a href="{{ route('trainings-public') }}" class="footer-link">Training Programs</a>
          <a href="{{ route('contact') }}"          class="footer-link">Contact Us</a>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Training Areas</div>
        <div class="footer-links">
          <span class="footer-link">Mechanical Technology</span>
          <span class="footer-link">Automotive Technology</span>
          <span class="footer-link">Computer Technology</span>
          <span class="footer-link">Electronics Technology</span>
          <span class="footer-link">Culinary Technology</span>
          <span class="footer-link">Apparel and Fashion Technology</span>
          <span class="footer-link">Print Media Technology</span>
          <span class="footer-link">Information Technology</span>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Access</div>
        <div class="footer-links">
          @if($loggedIn)
            <a href="{{ $dashboardUrl }}" class="footer-link">Go to Dashboard</a>
          @else
            <a href="{{ route('login') }}"       class="footer-link">Log In to System</a>
            <a href="{{ route('choose-role') }}" class="footer-link">Sign Up</a>
          @endif
          <a href="{{ route('terms') }}"       class="footer-link">Terms of Use</a>
          <a href="{{ route('privacy') }}"     class="footer-link">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-copy">&copy; {{ now()->year }} PAThrive – SLSU College of Industrial Technology. All rights reserved.</div>
      <div class="footer-badges">
        <div class="footer-badge">CHED Compliant</div>
        <div class="footer-badge">SDG Aligned</div>
      </div>
    </div>
  </div>
</footer>

<script>
window.addEventListener('scroll', () => {
  document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 60);
});

function filter(el, status) {
  document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');

  const cards = document.querySelectorAll('#trainGrid .card');
  let visible = 0;
  cards.forEach(c => {
    const show = !status || c.dataset.status === status;
    c.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  document.getElementById('emptyState').style.display = visible === 0 ? 'block' : 'none';
}
</script>
</body>
</html>
