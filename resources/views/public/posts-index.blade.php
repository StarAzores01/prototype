<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Posts &amp; Announcements</title>
  <link rel="icon" href="{{ asset('imgs/favicon.png') }}" type="image/png"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
  <style>
    body { font-family:'Poppins',sans-serif; margin:0; background:#F0F6FF; color:#334155; overflow-x:hidden; }
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
    .lp-nav-actions { display:flex;align-items:center;gap:10px;flex-wrap:nowrap; }
    .lp-btn-login { padding:8px 18px;border-radius:8px;font-size:13.5px;font-weight:600;color:rgba(255,255,255,.85);background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);transition:all .24s;text-decoration:none;display:inline-flex;align-items:center;gap:7px; }
    .lp-btn-login:hover { background:rgba(255,255,255,.15);color:#fff; }
    .lp-btn-signup { padding:8px 20px;border-radius:8px;font-size:13.5px;font-weight:700;background:linear-gradient(135deg,#1A56DB,#2E6BF0);color:#fff;box-shadow:0 2px 10px rgba(26,86,219,.45);transition:all .24s;text-decoration:none;display:inline-flex;align-items:center;gap:7px; }
    .lp-btn-signup:hover { transform:translateY(-1px);box-shadow:0 4px 18px rgba(26,86,219,.6); }
    .lp-nav-collapse { display:contents; }
    .lp-hamburger { display:none;flex-direction:column;justify-content:center;align-items:center;gap:5px;width:40px;height:40px;border-radius:8px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);cursor:pointer;flex-shrink:0; }
    .lp-hamburger span { display:block;width:20px;height:2px;border-radius:2px;background:#fff;transition:all .24s; }
    .lp-hamburger.lp-open span:nth-child(1) { transform:translateY(7px) rotate(45deg); }
    .lp-hamburger.lp-open span:nth-child(2) { opacity:0; }
    .lp-hamburger.lp-open span:nth-child(3) { transform:translateY(-7px) rotate(-45deg); }
    .page-hero { padding:120px 0 48px;background:linear-gradient(135deg,#09182F,#102545,#1A3A72);text-align:center; }
    .eyebrow { display:inline-flex;align-items:center;gap:8px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);color:#38BDF8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.4px;padding:5px 14px;border-radius:40px;margin-bottom:18px; }
    .lp-container { max-width:1200px;margin:0 auto;padding:0 28px; }

    /* ── Posts index grid ── */
    .posts-index-wrap { max-width:1100px;margin:0 auto;padding:56px 24px 80px;position:relative;z-index:1; }
    .posts-index-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:22px; }
    .posts-index-card {
      background:#fff;border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;
      box-shadow:0 4px 18px rgba(9,24,47,.08);border:1px solid #E7EDF6;
      display:flex;flex-direction:column;transition:all .24s;
    }
    .posts-index-card:hover { transform:translateY(-5px);box-shadow:0 14px 40px rgba(9,24,47,.16); }
    .posts-index-thumb {
      height:160px;position:relative;flex-shrink:0;
      display:flex;align-items:center;justify-content:center;font-size:32px;color:#fff;
      background:linear-gradient(135deg,#1A56DB,#38BDF8);
    }
    .posts-index-thumb img { position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block; }
    .posts-index-body { padding:18px 20px 20px;flex:1;display:flex;flex-direction:column;gap:9px; }
    .posts-index-title { font-size:15.5px;font-weight:700;color:#0F2A4A;line-height:1.4; }
    .posts-index-desc { font-size:13px;color:#5B6B82;line-height:1.65;flex:1; }
    .posts-index-meta { display:flex;align-items:center;gap:14px;font-size:11.5px;color:#8695A8;flex-wrap:wrap; }
    .posts-index-meta span { display:inline-flex;align-items:center;gap:5px; }
    .posts-index-empty { text-align:center;padding:60px 20px;color:#5B6B82;font-size:14px; }

    .lp-footer { background:#05101F;padding:52px 0 28px; }
    .lp-footer-grid { display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px; }
    .lp-footer-logo { display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .lp-footer-logo-box { width:44px;height:44px;border-radius:9px;overflow:hidden;background:transparent;display:flex;align-items:center;justify-content:center; }
    .lp-footer-logo-box img { width:40px;height:40px;object-fit:contain; }
    .lp-footer-logo-name { font-size:18px;font-weight:800;color:#fff; }
    .lp-footer-tagline { font-size:13px;color:rgba(255,255,255,.4);line-height:1.7;max-width:280px;margin-bottom:16px; }
    .lp-footer-contact { display:flex;flex-direction:column;gap:8px; }
    .lp-footer-ci { display:flex;align-items:flex-start;gap:8px;font-size:12.5px;color:rgba(255,255,255,.45); }
    .lp-footer-ci i { font-size:11px;color:#38BDF8;margin-top:2px;flex-shrink:0;width:14px;text-align:center; }
    .lp-footer-col-title { font-size:13px;font-weight:700;color:rgba(255,255,255,.7);margin-bottom:16px;text-transform:uppercase;letter-spacing:.6px; }
    .lp-footer-links { display:flex;flex-direction:column;gap:9px; }
    .lp-footer-link { font-size:13px;color:rgba(255,255,255,.4);transition:all .24s;text-decoration:none;display:block; }
    .lp-footer-link:hover { color:rgba(255,255,255,.8); }
    .lp-footer-bottom { padding-top:24px;border-top:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px; }
    .lp-footer-copy { font-size:12px;color:rgba(255,255,255,.3); }
    .lp-footer-badges { display:flex;gap:8px; }
    .lp-footer-badge { font-size:10px;font-weight:600;color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.12);padding:3px 9px;border-radius:20px; }
    @media(max-width:640px){
      .lp-footer-grid{grid-template-columns:1fr 1fr;}
      .lp-hamburger{display:flex;}
      .lp-nav-collapse{display:flex;flex-direction:column;position:absolute;top:100%;left:0;right:0;background:rgba(9,24,47,.98);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.08);padding:8px 20px 20px;max-height:0;overflow:hidden;opacity:0;visibility:hidden;transition:max-height .3s ease,opacity .25s ease;}
      .lp-nav-collapse.lp-open{max-height:480px;opacity:1;visibility:visible;}
      .lp-links{flex-direction:column;align-items:stretch;gap:2px;width:100%;}
      .lp-link{padding:12px 14px;}
      .lp-nav-actions{flex-direction:column;align-items:stretch;gap:8px;width:100%;padding-top:12px;margin-top:8px;border-top:1px solid rgba(255,255,255,.08);}
      .lp-btn-login,.lp-btn-signup{justify-content:center;width:100%;}
      .posts-index-grid { grid-template-columns:1fr; }
    }
    @media(max-width:600px){ .lp-footer-grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="lp-nav" id="lpNav">
  <div class="lp-nav-inner">
    <div class="lp-brand">
      <div class="lp-brand-logo">
        <img src="{{ \App\Models\PageContent::get('global', 'site_logo', asset('imgs/logofinalpt.png')) }}" alt="CIT" onerror="this.style.display='none'"/>
      </div>
      <div>
        <div class="lp-brand-name">PAThrive</div>
        <div class="lp-brand-sub">{{ \App\Models\PageContent::get('global', 'site_tagline', 'College of Industrial Technology') }}</div>
      </div>
    </div>
    <div class="lp-nav-collapse" id="lpNavCollapse">
      <div class="lp-links">
        <a href="{{ route('home') }}"            class="lp-link">Home</a>
        <a href="{{ route('about') }}"            class="lp-link">About</a>
        <a href="{{ route('trainings-public') }}" class="lp-link">Trainings</a>
        <a href="{{ route('public.posts.index') }}" class="lp-link active">Announcements</a>
        <a href="{{ route('contact') }}"          class="lp-link">Contact</a>
      </div>
      @include('public.partials.nav', ['navPrefix' => 'lp-'])
    </div>
    <button type="button" class="lp-hamburger" id="lpHamburger" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="lpNavCollapse">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- PAGE HERO -->
<div class="page-hero">
  <div style="max-width:760px;margin:0 auto;padding:0 24px">
    <div class="eyebrow"><i class="fas fa-bullhorn"></i> Posts &amp; Announcements</div>
    <h1 style="font-size:clamp(26px,4vw,38px);font-weight:800;color:#fff;margin-bottom:10px;line-height:1.25">All Posts &amp; Announcements</h1>
    <p style="color:rgba(255,255,255,.6);font-size:14.5px;max-width:600px;margin:0 auto;line-height:1.7">
      Every update from the College of Industrial Technology Extension Office, newest first.
    </p>
  </div>
</div>

<!-- POSTS GRID -->
<div class="posts-index-wrap">
  @forelse($posts as $post)
    @if($loop->first)
    <div class="posts-index-grid">
    @endif
      <a href="{{ route('public.posts.show', $post) }}" class="posts-index-card">
        <div class="posts-index-thumb">
          @if($post->image)
            <img src="{{ $post->image }}" alt="{{ $post->title }}"/>
          @else
            <i class="fas fa-bullhorn"></i>
          @endif
        </div>
        <div class="posts-index-body">
          <div class="posts-index-title">{{ $post->title }}</div>
          <p class="posts-index-desc">{{ $post->excerpt() }}</p>
          <div class="posts-index-meta">
            <span><i class="fas fa-calendar"></i> {{ ($post->published_at ?? $post->created_at)?->format('M d, Y') }}</span>
            <span><i class="fas fa-user"></i> {{ $post->author->full_name ?? 'Extension Coordinator' }}</span>
          </div>
        </div>
      </a>
    @if($loop->last)
    </div>
    @endif
  @empty
    <div class="posts-index-empty">No posts or announcements have been published yet — check back soon.</div>
  @endforelse
</div>

<!-- FOOTER -->
<footer class="lp-footer">
  <div class="lp-container">
    <div class="lp-footer-grid">
      <div>
        <div class="lp-footer-logo">
          <div class="lp-footer-logo-box"><img src="{{ \App\Models\PageContent::get('global', 'site_logo', asset('imgs/logofinalpt.png')) }}" alt="CIT" onerror="this.style.display='none'"/></div>
          <div class="lp-footer-logo-name">PAThrive</div>
        </div>
        <p class="lp-footer-tagline">{{ \App\Models\PageContent::get('global', 'footer_tagline', 'Extension Training Management & Impact Assessment Tracking System — College of Industrial Technology') }}</p>
        <div class="lp-footer-contact">
          <div class="lp-footer-ci"><i class="fas fa-location-dot"></i> {{ \App\Models\PageContent::get('global', 'contact_address', 'SLSU Main Campus, Lucban, Quezon, Philippines') }}</div>
          <div class="lp-footer-ci"><i class="fas fa-envelope"></i> {{ \App\Models\PageContent::get('global', 'contact_email', 'cit.extension@slsu.edu.ph') }}</div>
          <div class="lp-footer-ci"><i class="fas fa-phone"></i> {{ \App\Models\PageContent::get('global', 'contact_phone', '(042) 540-XXXX') }}</div>
        </div>
      </div>
      <div>
        <div class="lp-footer-col-title">Quick Links</div>
        <div class="lp-footer-links">
          <a href="{{ route('home') }}"             class="lp-footer-link">Home</a>
          <a href="{{ route('about') }}"             class="lp-footer-link">About PAThrive</a>
          <a href="{{ route('trainings-public') }}"  class="lp-footer-link">Training Programs</a>
          <a href="{{ route('public.posts.index') }}" class="lp-footer-link">Announcements</a>
          <a href="{{ route('contact') }}"           class="lp-footer-link">Contact Us</a>
        </div>
      </div>
      <div>
        <div class="lp-footer-col-title">Training Areas</div>
        <div class="lp-footer-links">
          <span class="lp-footer-link">Explore all featured trainings on our Trainings page</span>
        </div>
      </div>
      <div>
        <div class="lp-footer-col-title">Access</div>
        <div class="lp-footer-links">
          @if($loggedIn)
            <a href="{{ $dashboardUrl }}" class="lp-footer-link">Go to Dashboard</a>
          @else
            <a href="{{ route('login') }}"       class="lp-footer-link">Log In to System</a>
            <a href="{{ route('choose-role') }}" class="lp-footer-link">Register as Participant</a>
          @endif
          <a href="{{ route('terms') }}"       class="lp-footer-link">Terms of Use</a>
          <a href="{{ route('privacy') }}"     class="lp-footer-link">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="lp-footer-bottom">
      <div class="lp-footer-copy">{!! \App\Models\PageContent::get('global', 'footer_copyright', '&copy; ' . now()->year . ' PAThrive – SLSU College of Industrial Technology. All rights reserved.') !!}</div>
      <div class="lp-footer-badges">
        <div class="lp-footer-badge">ISO/IEC 25010:2023</div>
      </div>
    </div>
  </div>
</footer>

<script>
window.addEventListener('scroll', () => {
  document.getElementById('lpNav').classList.toggle('scrolled', window.scrollY > 60);
});

(function () {
  const btn = document.getElementById('lpHamburger');
  const menu = document.getElementById('lpNavCollapse');
  if (!btn || !menu) return;
  const setOpen = (open) => {
    menu.classList.toggle('lp-open', open);
    btn.classList.toggle('lp-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  };
  btn.addEventListener('click', () => setOpen(!menu.classList.contains('lp-open')));
  menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setOpen(false)));
  window.addEventListener('resize', () => { if (window.innerWidth > 640) setOpen(false); });
})();
</script>
</body>
</html>
