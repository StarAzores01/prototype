<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in as EC, go straight to dashboard
if (isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'extension_coordinator') {
    redirect(BASE_URL . '/ec/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Extension Training Management System | CIT-SLSU</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet"/>

  <style>
    /* ============================================================
       LANDING PAGE OVERRIDE STYLES
       (scoped to .lp-* classes – does NOT affect any other page)
    ============================================================ */

    /* ── TOKENS (mirror pathrive-public.html) ── */
    :root {
      --lp-navy:       #09182F;
      --lp-navy-mid:   #102545;
      --lp-navy-soft:  #1B3A6B;
      --lp-blue:       #1A56DB;
      --lp-blue-b:     #2E6BF0;
      --lp-blue-l:     #3B82F6;
      --lp-accent:     #38BDF8;
      --lp-accent-s:   #BAE6FD;
      --lp-em:         #10B981;
      --lp-am:         #F59E0B;
      --lp-ro:         #EF4444;
      --lp-white:      #FFFFFF;
      --lp-off:        #F0F6FF;
      --lp-g1:         #EEF2F7;
      --lp-g2:         #CBD5E1;
      --lp-g4:         #94A3B8;
      --lp-g5:         #64748B;
      --lp-g7:         #334155;
      --lp-r:          14px;
      --lp-rs:         8px;
      --lp-s:          0 4px 24px rgba(9,24,47,.12);
      --lp-sl:         0 12px 48px rgba(9,24,47,.22);
      --lp-t:          all .24s cubic-bezier(.4,0,.2,1);
    }

    /* ── RESET only for landing body ── */
    body.landing-body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(150deg, #010E1F 0%, #051828 35%, #07213A 65%, #040F1C 100%);
      color: var(--lp-g7);
      margin: 0;
    }
    body.landing-body h1,
    body.landing-body h2,
    body.landing-body h3,
    body.landing-body h4 {
      font-family: 'Poppins', sans-serif;
    }

    .lp-orb, .lp-grid-lines, .lp-hero-photo, .lp-ticker-wrap::before, .lp-ticker-wrap::after {
      pointer-events: none !important;
    }
    a, button { position: relative; z-index: 10; }

    /* ── NAVBAR ── */
    .lp-nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
      height: 68px;
      background: rgba(9,24,47,.96);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(255,255,255,.07);
      display: flex; align-items: center;
      pointer-events: all;
      transition: var(--lp-t);
    }
    .lp-nav.scrolled {
      background: rgba(9,24,47,.99);
      box-shadow: 0 4px 24px rgba(0,0,0,.3);
    }
    .lp-nav-inner {
      display: flex; align-items: center; justify-content: space-between;
      width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 28px;
    }
    .lp-brand { display: flex; align-items: center; gap: 12px; }

    /* ── LOGO CONTAINER — nav ── */
    .lp-brand-logo {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      overflow: hidden;
      /* Layered ring: accent colour outer → semi-transparent mid → transparent centre */
      background: rgba(255,255,255,0.12);
      border: 1.5px solid rgba(56,189,248,0.55);
      box-shadow:
        0 0 0 3px rgba(56,189,248,0.12),   /* accent halo */
        0 4px 14px rgba(0,0,0,0.40);        /* depth */
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      padding: 3px;                          /* keeps image from touching the border */
    }
    .lp-brand-logo img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
      /* gentle brightness boost so a dark-toned logo pops */
      filter: brightness(1.15) drop-shadow(0 1px 3px rgba(0,0,0,0.5));
    }

    .lp-brand-name { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: #fff; letter-spacing: -.4px; }
    .lp-brand-sub  { font-size: 10px; color: rgba(255,255,255,.45); letter-spacing: .4px; }

    .lp-links { display: flex; align-items: center; gap: 4px; }
    .lp-link {
      padding: 7px 14px; border-radius: var(--lp-rs);
      font-size: 13.5px; font-weight: 500; color: rgba(255,255,255,.7);
      transition: var(--lp-t); cursor: pointer; text-decoration: none;
    }
    .lp-link:hover, .lp-link.active {
      background: rgba(255,255,255,.08); color: #fff;
    }

    .lp-nav-actions { display: flex; align-items: center; gap: 10px; }
    .lp-btn-login {
      padding: 8px 18px; border-radius: var(--lp-rs);
      font-size: 13.5px; font-weight: 600; color: rgba(255,255,255,.85);
      background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
      transition: var(--lp-t); text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
      font-family: 'Sora', sans-serif;
    }
    .lp-btn-login:hover { background: rgba(255,255,255,.15); color: #fff; }
    .lp-btn-signup {
      padding: 8px 20px; border-radius: var(--lp-rs);
      font-size: 13.5px; font-weight: 700;
      background: linear-gradient(135deg, var(--lp-blue), var(--lp-blue-b));
      color: #fff; box-shadow: 0 2px 10px rgba(26,86,219,.45);
      transition: var(--lp-t); text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
      font-family: 'Sora', sans-serif;
    }
    .lp-btn-signup:hover { transform: translateY(-1px); box-shadow: 0 4px 18px rgba(26,86,219,.6); }

    /* ── HERO ── */
    .lp-hero {
      position: relative; overflow: hidden; min-height: 100vh;
      background: linear-gradient(145deg, var(--lp-navy) 0%, var(--lp-navy-mid) 45%, #1A3A72 100%);
      display: flex; align-items: center; padding: 100px 0 80px;
    }
    .lp-hero-bg { position: absolute; inset: 0; pointer-events: none !important; overflow: hidden; }

    /* ── PHOTO LAYER — subtle, blended, behind everything ── */
    .lp-hero-photo {
      position: absolute; inset: 0;
      background-image: url('<?= BASE_URL ?>/imgs/landingpage.jpg');
      background-size: cover;
      background-position: center center;
      background-repeat: no-repeat;
      opacity: 0.11;
      mix-blend-mode: luminosity;
      z-index: 0;
    }
    /* Vignette on top of photo to keep edges dark */
    .lp-hero-photo::after {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse at center, transparent 30%, rgba(9,24,47,.72) 100%);
    }

    .lp-hero-bg::before {
      content: ''; position: absolute; top: -120px; right: -120px;
      width: 600px; height: 600px; border-radius: 50%;
      background: radial-gradient(circle, rgba(56,189,248,.12) 0%, transparent 70%);
      z-index: 1;
    }
    .lp-hero-bg::after {
      content: ''; position: absolute; bottom: -80px; left: -80px;
      width: 500px; height: 500px; border-radius: 50%;
      background: radial-gradient(circle, rgba(26,86,219,.15) 0%, transparent 70%);
      z-index: 1;
    }
    .lp-grid-lines {
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
      background-size: 60px 60px;
      z-index: 1;
    }
    .lp-orb {
      position: absolute; border-radius: 50%;
      filter: blur(60px); opacity: .18; pointer-events: none;
      animation: lpOrb 8s ease-in-out infinite;
      z-index: 2;
    }
    .lp-orb-1 { width: 320px; height: 320px; background: var(--lp-blue);   top: 10%; right: 8%;   animation-delay: 0s; }
    .lp-orb-2 { width: 200px; height: 200px; background: var(--lp-accent); top: 60%; left: 5%;    animation-delay: -3s; }
    .lp-orb-3 { width: 160px; height: 160px; background: #6366F1;           bottom: 15%; right: 30%; animation-delay: -5s; }
    @keyframes lpOrb {
      0%, 100% { transform: translateY(0) scale(1); }
      50%       { transform: translateY(-24px) scale(1.04); }
    }

    .lp-hero-inner {
      position: relative; z-index: 100;
      display: grid; grid-template-columns: 1fr 1fr;
      pointer-events: all;
      gap: 60px; align-items: center;
      max-width: 1200px; margin: 0 auto; padding: 0 28px;
    }

    /* Hero left text */
    .lp-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(56,189,248,.12); border: 1px solid rgba(56,189,248,.25);
      color: var(--lp-accent); font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1.4px;
      padding: 5px 14px; border-radius: 40px; margin-bottom: 20px;
      font-family: 'Sora', sans-serif;
    }
    .lp-hero-h1 {
      font-size: clamp(30px, 4vw, 52px); font-weight: 800;
      color: #fff; line-height: 1.15; letter-spacing: -.8px; margin-bottom: 20px;
    }
    .lp-hero-h1 .lp-hl {
      background: linear-gradient(90deg, var(--lp-accent), #7DD3FC);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .lp-hero-p {
      font-size: 15.5px; color: rgba(255,255,255,.65); line-height: 1.7;
      margin-bottom: 32px; max-width: 480px;
    }
    .lp-hero-btns { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 40px; }
    .lp-btn-hero-primary {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 14px 32px; border-radius: 10px;
      font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700;
      background: #fff; color: var(--lp-navy);
      box-shadow: 0 4px 14px rgba(0,0,0,.15); transition: var(--lp-t);
      text-decoration: none;
    }
    .lp-btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
    .lp-btn-hero-ghost {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 14px 32px; border-radius: 10px;
      font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700;
      background: rgba(255,255,255,.12); color: #fff;
      border: 1.5px solid rgba(255,255,255,.25); transition: var(--lp-t);
      text-decoration: none;
    }
    .lp-btn-hero-ghost:hover { background: rgba(255,255,255,.22); }

    /* Hero stats */
    .lp-hero-stats { display: flex; gap: 28px; flex-wrap: wrap; padding-top: 28px; border-top: 1px solid rgba(255,255,255,.1); }
    .lp-stat-num   { font-family: 'Sora', sans-serif; font-size: 26px; font-weight: 800; color: #fff; line-height: 1; }
    .lp-stat-lbl   { font-size: 12px; color: rgba(255,255,255,.5); margin-top: 3px; }

    /* Hero right card */
    .lp-hero-visual { position: relative; display: flex; justify-content: center; align-items: center; }
    .lp-hero-card {
      background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12);
      border-radius: 20px; padding: 24px;
      backdrop-filter: blur(20px); box-shadow: var(--lp-sl);
      width: 100%; max-width: 420px;
      animation: lpFadeUp .7s ease .2s both;
    }
    @keyframes lpFadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: none; }
    }
    .lp-card-hd { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
    .lp-card-ico {
      width: 42px; height: 42px; border-radius: 10px;
      background: linear-gradient(135deg, var(--lp-blue), var(--lp-accent));
      display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .lp-card-ttl { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: #fff; }
    .lp-card-sub { font-size: 11px; color: rgba(255,255,255,.5); margin-top: 2px; }
    .lp-card-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-bottom: 18px; }
    .lp-cs { background: rgba(255,255,255,.06); border-radius: 10px; padding: 12px 10px; text-align: center; }
    .lp-cs-val { font-family: 'Sora', sans-serif; font-size: 20px; font-weight: 800; color: #fff; }
    .lp-cs-lbl { font-size: 10px; color: rgba(255,255,255,.45); margin-top: 2px; }
    .lp-card-items { display: flex; flex-direction: column; gap: 8px; }
    .lp-ci {
      display: flex; align-items: center; gap: 10px;
      background: rgba(255,255,255,.05); border-radius: 10px;
      padding: 10px 12px; border: 1px solid rgba(255,255,255,.06);
    }
    .lp-ci-em { font-size: 18px; }
    .lp-ci-name { font-size: 12px; font-weight: 600; color: #fff; }
    .lp-ci-meta { font-size: 10.5px; color: rgba(255,255,255,.45); margin-top: 1px; }

    /* Float cards on hero */
    .lp-float {
      position: absolute; background: rgba(255,255,255,.9);
      border-radius: 12px; padding: 10px 14px;
      box-shadow: 0 8px 32px rgba(0,0,0,.18);
      animation: lpFloat 5s ease-in-out infinite;
    }
    .lp-float-1 { top: -30px; right: -20px; animation-delay: 0s; }
    .lp-float-2 { bottom: 30px; left: -30px; animation-delay: -2s; }
    @keyframes lpFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    .lp-fl-lbl { font-size: 10px; font-weight: 600; color: var(--lp-g5); text-transform: uppercase; letter-spacing: .5px; }
    .lp-fl-val { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: var(--lp-navy); }
    .lp-fl-sub { font-size: 10px; font-weight: 600; }
    .lp-fl-sub.grn { color: var(--lp-em); }
    .lp-fl-sub.bl  { color: var(--lp-blue); }

    /* ── TICKER ── */
    .lp-ticker {
      background: linear-gradient(90deg, var(--lp-blue), var(--lp-blue-b));
      padding: 10px 0; overflow: hidden;
    }
    .lp-ticker-inner { display: flex; align-items: center; gap: 0; max-width: 1200px; margin: 0 auto; padding: 0 28px; }
    .lp-ticker-lbl {
      flex-shrink: 0; background: rgba(0,0,0,.2); color: #fff;
      font-size: 11px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1px; padding: 4px 16px; margin-right: 16px; border-radius: 4px;
      font-family: 'Sora', sans-serif;
    }
    .lp-ticker-track {
      display: flex; gap: 48px; white-space: nowrap;
      animation: lpTick 30s linear infinite;
    }
    .lp-ticker-track:hover { animation-play-state: paused; }
    @keyframes lpTick { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    .lp-ticker-item { font-size: 13px; color: rgba(255,255,255,.9); display: flex; align-items: center; gap: 8px; }
    .lp-ticker-dot  { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,.5); }

    /* ── SECTION SHARED ── */
    .lp-section { padding: 88px 0; }
    .lp-section-alt  { background: rgba(255,255,255,.04); }
    .lp-section-dark { background: var(--lp-navy); }
    .lp-section-gray { background: rgba(255,255,255,.03); }
    .lp-container { max-width: 1200px; margin: 0 auto; padding: 0 28px; }
    .lp-sec-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(26,86,219,.08); color: var(--lp-blue);
      font-size: 11.5px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1.2px; padding: 5px 14px; border-radius: 40px;
      border: 1px solid rgba(26,86,219,.15); margin-bottom: 14px;
      font-family: 'Sora', sans-serif;
    }
    .lp-sec-title {
      font-size: clamp(26px, 3.5vw, 40px); font-weight: 800;
      color: var(--lp-navy); line-height: 1.2; letter-spacing: -.5px;
      margin-bottom: 14px;
    }
    .lp-sec-desc { font-size: 15.5px; color: var(--lp-g5); max-width: 560px; line-height: 1.7; }
    .lp-sec-header { text-align: center; margin-bottom: 56px; }
    .lp-sec-header .lp-sec-desc { margin: 0 auto; }

    /* ── ABOUT ── */
    .lp-about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; }
    .lp-about-visual {
      border-radius: 20px; overflow: hidden;
      background: linear-gradient(135deg, var(--lp-navy-mid), var(--lp-navy-soft));
      aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center;
      box-shadow: var(--lp-sl);
    }
    .lp-about-mock {
      width: 85%; background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1); border-radius: 14px;
      padding: 20px; backdrop-filter: blur(10px);
    }
    .lp-mock-bar { display: flex; gap: 6px; margin-bottom: 14px; }
    .lp-mock-dot { width: 10px; height: 10px; border-radius: 50%; }
    .lp-mock-row { height: 8px; border-radius: 4px; margin-bottom: 8px; background: rgba(255,255,255,.1); }
    .lp-mock-row.fil  { background: linear-gradient(90deg, var(--lp-blue), var(--lp-accent)); }
    .lp-mock-row.half { width: 65%; }
    .lp-mock-row.thrd { width: 40%; }
    .lp-mock-cards { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 16px; }
    .lp-mock-card  { background: rgba(255,255,255,.08); border-radius: 8px; padding: 10px 8px; text-align: center; }
    .lp-mc-val { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 800; color: #fff; }
    .lp-mc-lbl { font-size: 9px; color: rgba(255,255,255,.45); }
    .lp-mock-rows { margin-top: 14px; display: flex; flex-direction: column; gap: 8px; }
    .lp-mock-item { height: 36px; border-radius: 8px; background: rgba(255,255,255,.05); display: flex; align-items: center; padding: 0 12px; gap: 8px; }
    .lp-mock-item-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .lp-mock-item-bar { height: 7px; border-radius: 4px; flex: 1; }

    .lp-about-points { display: flex; flex-direction: column; gap: 16px; margin-top: 8px; }
    .lp-about-pt { display: flex; align-items: flex-start; gap: 12px; }
    .lp-about-pt-ico {
      width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
      background: var(--lp-off); border: 1px solid var(--lp-g1);
      display: flex; align-items: center; justify-content: center; font-size: 16px;
    }
    .lp-about-pt-txt strong { font-size: 13.5px; font-weight: 700; color: var(--lp-navy); display: block; }
    .lp-about-pt-txt span   { font-size: 12.5px; color: var(--lp-g5); line-height: 1.6; }

    /* ── FEATURES ── */
    .lp-feat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 24px; }
    .lp-feat {
      background: #fff; border-radius: 18px; padding: 28px 24px;
      border: 1px solid var(--lp-g1); box-shadow: 0 2px 12px rgba(9,24,47,.06);
      transition: var(--lp-t);
    }
    .lp-feat:hover { transform: translateY(-4px); box-shadow: var(--lp-s); border-color: rgba(26,86,219,.15); }
    .lp-feat-ico {
      width: 52px; height: 52px; border-radius: 14px; margin-bottom: 18px;
      display: flex; align-items: center; justify-content: center; font-size: 22px;
    }
    .fi-b  { background: rgba(26,86,219,.08); }
    .fi-t  { background: rgba(16,185,129,.08); }
    .fi-a  { background: rgba(245,158,11,.08); }
    .fi-i  { background: rgba(99,102,241,.08); }
    .fi-c  { background: rgba(56,189,248,.1); }
    .fi-r  { background: rgba(239,68,68,.08); }
    .lp-feat-title { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: var(--lp-navy); margin-bottom: 8px; }
    .lp-feat-desc  { font-size: 13.5px; color: var(--lp-g5); line-height: 1.65; }

    /* ── TRAININGS FEED ── */
    .lp-filter-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 32px; justify-content: center; }
    .lp-pill {
      padding: 8px 18px; border-radius: 40px; font-size: 13px; font-weight: 600;
      border: 1.5px solid var(--lp-g2); color: var(--lp-g5);
      background: #fff; cursor: pointer; transition: var(--lp-t);
    }
    .lp-pill.active, .lp-pill:hover {
      background: var(--lp-blue); color: #fff; border-color: var(--lp-blue);
      box-shadow: 0 3px 10px rgba(26,86,219,.3);
    }

    /* Training cards (public feed) */
    .lp-train-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(320px,1fr)); gap: 24px; }
    .lp-tc {
      background: #fff; border-radius: 18px; box-shadow: 0 2px 12px rgba(9,24,47,.07);
      border: 1px solid var(--lp-g1); overflow: hidden; transition: var(--lp-t);
      display: flex; flex-direction: column;
    }
    .lp-tc:hover { transform: translateY(-5px); box-shadow: var(--lp-sl); }
    .lp-tc-img {
      height: 160px; position: relative;
      display: flex; align-items: center; justify-content: center; font-size: 56px; overflow: hidden;
    }
    .lp-tc-em { position: relative; z-index: 1; filter: drop-shadow(0 4px 8px rgba(0,0,0,.3)); }
    .lp-tc-cat {
      position: absolute; top: 12px; left: 12px; z-index: 2;
      background: rgba(255,255,255,.92); color: var(--lp-navy);
      font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px;
      letter-spacing: .3px;
    }
    .lp-tc-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
    .lp-tc-title { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: var(--lp-navy); margin-bottom: 8px; line-height: 1.35; }
    .lp-tc-desc  { font-size: 13px; color: var(--lp-g5); line-height: 1.65; margin-bottom: 14px; flex: 1; }
    .lp-tc-meta  { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
    .lp-tc-mi    { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--lp-g4); }
    .lp-tc-mi i  { font-size: 11px; }
    .lp-tc-foot  { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--lp-g1); }

    /* Status badges (public style) */
    .lp-bdg { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .lp-bdg::before { content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .lp-bdg-upcoming  { background: #FEF3C7; color: #854D0E; } .lp-bdg-upcoming::before  { background: var(--lp-am); }
    .lp-bdg-ongoing   { background: #DBEAFE; color: #1E3A8A; } .lp-bdg-ongoing::before   { background: var(--lp-blue); }
    .lp-bdg-completed { background: #D1FAE5; color: #065F46; } .lp-bdg-completed::before { background: var(--lp-em); }

    /* ── SDG SECTION ── */
    .lp-sdg-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; max-width: 820px; margin: 0 auto; }
    .lp-sdg { border-radius: 16px; padding: 28px; text-align: center; transition: var(--lp-t); }
    .lp-sdg:hover { transform: translateY(-4px) scale(1.02); }
    .lp-sdg-4  { background: linear-gradient(135deg,#FEF3C7,#FDE68A); border: 2px solid var(--lp-am); }
    .lp-sdg-9  { background: linear-gradient(135deg,#FEE2E2,#FECACA); border: 2px solid var(--lp-ro); }
    .lp-sdg-17 { background: linear-gradient(135deg,#DBEAFE,#BFDBFE); border: 2px solid var(--lp-blue-l); }
    .lp-sdg-num  { font-family: 'Sora', sans-serif; font-size: 36px; font-weight: 900; margin-bottom: 8px; }
    .lp-sdg-4  .lp-sdg-num { color: #92400E; }
    .lp-sdg-9  .lp-sdg-num { color: #991B1B; }
    .lp-sdg-17 .lp-sdg-num { color: #1E3A8A; }
    .lp-sdg-name { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; margin-bottom: 6px; color: var(--lp-navy); }
    .lp-sdg-desc { font-size: 12px; color: var(--lp-g5); line-height: 1.6; }

    /* ── CONTACT ── */
    .lp-contact-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 20px; max-width: 860px; margin: 0 auto; }
    .lp-contact-card {
      background: #fff; border-radius: 18px; padding: 28px 22px;
      border: 1px solid var(--lp-g1); box-shadow: 0 2px 12px rgba(9,24,47,.06);
      display: flex; align-items: flex-start; gap: 16px; transition: var(--lp-t);
    }
    .lp-contact-card:hover { box-shadow: var(--lp-s); transform: translateY(-3px); }
    .lp-contact-ico {
      width: 48px; height: 48px; border-radius: 13px; flex-shrink: 0;
      background: rgba(26,86,219,.08); color: var(--lp-blue);
      display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .lp-contact-lbl { font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 700; color: var(--lp-navy); margin-bottom: 5px; }
    .lp-contact-val { font-size: 13px; color: var(--lp-g5); line-height: 1.6; }

    /* ── CTA ── */
    .lp-cta {
      background: linear-gradient(135deg, var(--lp-navy) 0%, var(--lp-navy-soft) 60%, #1E4A8A 100%);
      position: relative; overflow: hidden;
    }
    .lp-cta::before {
      content: ''; position: absolute; top: -100px; right: -100px;
      width: 500px; height: 500px; border-radius: 50%;
      background: radial-gradient(circle, rgba(56,189,248,.1) 0%, transparent 70%);
    }
    .lp-cta-inner { text-align: center; position: relative; z-index: 1; }
    .lp-cta-title {
      font-size: clamp(28px,4vw,46px); font-weight: 800; color: #fff;
      line-height: 1.2; letter-spacing: -.6px; margin-bottom: 16px;
    }
    .lp-cta-desc { font-size: 16px; color: rgba(255,255,255,.6); margin-bottom: 36px; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.7; }
    .lp-cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

    /* ── FOOTER ── */
    .lp-footer { background: #05101F; padding: 52px 0 28px; }
    .lp-footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }

    /* ── FOOTER BRAND LOGO ── */
    .lp-footer-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .lp-footer-logo-box {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      overflow: hidden;
      background: rgba(255,255,255,0.10);
      border: 1.5px solid rgba(56,189,248,0.50);
      box-shadow:
        0 0 0 3px rgba(56,189,248,0.10),
        0 4px 12px rgba(0,0,0,0.40);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3px;
      flex-shrink: 0;
    }
    .lp-footer-logo-box img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
      filter: brightness(1.15) drop-shadow(0 1px 3px rgba(0,0,0,0.5));
    }

    .lp-footer-logo-name { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: #fff; }
    .lp-footer-tagline { font-size: 13px; color: rgba(255,255,255,.4); line-height: 1.7; max-width: 280px; margin-bottom: 16px; }
    .lp-footer-contact { display: flex; flex-direction: column; gap: 8px; }
    .lp-footer-ci { display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; color: rgba(255,255,255,.45); }
    .lp-footer-ci i { font-size: 11px; color: var(--lp-accent); margin-top: 2px; flex-shrink: 0; }
    .lp-footer-col-title { font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 700; color: rgba(255,255,255,.7); margin-bottom: 16px; text-transform: uppercase; letter-spacing: .6px; }
    .lp-footer-links { display: flex; flex-direction: column; gap: 9px; }
    .lp-footer-link { font-size: 13px; color: rgba(255,255,255,.4); transition: var(--lp-t); text-decoration: none; display: block; }
    .lp-footer-link:hover { color: rgba(255,255,255,.8); }
    .lp-footer-bottom {
      padding-top: 24px; border-top: 1px solid rgba(255,255,255,.06);
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .lp-footer-copy { font-size: 12px; color: rgba(255,255,255,.3); }
    .lp-footer-badges { display: flex; gap: 8px; flex-wrap: wrap; }
    .lp-footer-badge { font-size: 10px; font-weight: 600; color: rgba(255,255,255,.4); border: 1px solid rgba(255,255,255,.12); padding: 3px 9px; border-radius: 20px; }

    /* ── VIDEO SHOWCASE ── */
    .lp-vid-layout {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: start;
    }
    .lp-vid-main { display: flex; flex-direction: column; gap: 0; }
    .lp-vid-frame {
      position: relative;
      border-radius: 18px;
      overflow: hidden;
      background: #000;
      aspect-ratio: 16/9;
      box-shadow: 0 12px 48px rgba(0,0,0,.5);
    }
    .lp-vid-player {
      width: 100%; height: 100%;
      display: block;
      object-fit: cover;
      border-radius: 18px;
    }
    /* Placeholder overlay — shown when no real video src is set */
    .lp-vid-placeholder {
      position: absolute; inset: 0;
      display: flex; align-items: center; justify-content: center;
      background: linear-gradient(145deg, var(--lp-navy-mid) 0%, var(--lp-navy-soft) 100%);
      border-radius: 18px;
      cursor: pointer;
      transition: var(--lp-t);
    }
    .lp-vid-placeholder:hover { background: linear-gradient(145deg, #102545 0%, #1f4a85 100%); }
    .lp-vid-placeholder.hidden { display: none; }
    .lp-vid-ph-inner { text-align: center; padding: 32px; }
    .lp-vid-play-ring {
      width: 80px; height: 80px; border-radius: 50%;
      background: rgba(255,255,255,.12);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 18px;
      transition: var(--lp-t);
      border: 2px solid rgba(255,255,255,.25);
    }
    .lp-vid-placeholder:hover .lp-vid-play-ring {
      background: rgba(56,189,248,.25);
      border-color: var(--lp-accent);
      transform: scale(1.08);
    }
    .lp-vid-ph-label {
      font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 700;
      color: #fff; margin: 0 0 6px;
    }
    .lp-vid-ph-sub {
      font-size: 12px; color: rgba(255,255,255,.45); margin: 0;
    }
    .lp-vid-ph-sub code {
      background: rgba(255,255,255,.1); padding: 2px 7px; border-radius: 4px;
      font-family: monospace; color: var(--lp-accent);
    }
    /* Caption bar under main video */
    .lp-vid-caption {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      border-top: none;
      border-radius: 0 0 18px 18px;
    }
    .lp-vid-cap-tag {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: .8px; color: var(--lp-accent);
      background: rgba(56,189,248,.12); padding: 3px 9px; border-radius: 20px;
      flex-shrink: 0;
    }
    .lp-vid-cap-title {
      font-size: 13px; font-weight: 600; color: rgba(255,255,255,.85);
    }
    /* Playlist sidebar */
    .lp-vid-playlist {
      display: flex; flex-direction: column; gap: 10px;
      max-height: calc(9 / 16 * (100vw - 28px * 2 - 340px - 24px));
      overflow-y: auto;
      padding-right: 4px;
    }
    .lp-vid-playlist::-webkit-scrollbar { width: 4px; }
    .lp-vid-playlist::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
    .lp-vid-thumb {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,.07);
      background: rgba(255,255,255,.04);
      cursor: pointer;
      transition: var(--lp-t);
      outline: none;
    }
    .lp-vid-thumb:hover, .lp-vid-thumb:focus-visible {
      background: rgba(255,255,255,.09);
      border-color: rgba(56,189,248,.3);
    }
    .lp-vid-thumb.active {
      background: rgba(56,189,248,.1);
      border-color: var(--lp-accent);
    }
    .lp-vt-img {
      width: 72px; height: 48px; border-radius: 8px;
      flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      position: relative; overflow: hidden;
    }
    .lp-vt-play-ico {
      position: absolute; inset: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; color: rgba(255,255,255,.8);
      background: rgba(0,0,0,.25);
    }
    .lp-vt-emoji {
      font-size: 28px; position: relative; z-index: 1;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,.3));
    }
    .lp-vt-info { flex: 1; min-width: 0; }
    .lp-vt-title {
      font-size: 12.5px; font-weight: 700; color: #fff;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .lp-vt-meta { font-size: 11px; color: rgba(255,255,255,.45); margin-top: 2px; }

    /* ── COURSE CARDS ── */
    .lp-course-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }
    .lp-course-card {
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 18px; overflow: hidden;
      transition: var(--lp-t);
      display: flex; flex-direction: column;
    }
    .lp-course-card:hover {
      transform: translateY(-5px);
      border-color: rgba(56,189,248,.3);
      box-shadow: 0 12px 40px rgba(0,0,0,.35);
    }
    .lp-cc-thumb {
      height: 120px;
      display: flex; align-items: center; justify-content: center;
      font-size: 52px;
      filter: drop-shadow(0 4px 8px rgba(0,0,0,.25));
    }
    .lp-cc-body { padding: 18px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .lp-cc-area {
      font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #fff;
    }
    .lp-cc-desc { font-size: 12.5px; color: rgba(255,255,255,.55); line-height: 1.65; flex: 1; }
    .lp-cc-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
    .lp-cc-tags span {
      font-size: 10.5px; font-weight: 600;
      background: rgba(56,189,248,.1); color: var(--lp-accent);
      border: 1px solid rgba(56,189,248,.2);
      padding: 3px 9px; border-radius: 20px;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 960px) {
      .lp-hero-inner  { grid-template-columns: 1fr; }
      .lp-hero-visual { display: none; }
      .lp-about-grid  { grid-template-columns: 1fr; }
      .lp-about-visual { display: none; }
      .lp-footer-grid { grid-template-columns: 1fr 1fr; }
      .lp-sdg-grid    { grid-template-columns: 1fr; max-width: 360px; }
      .lp-links       { display: none; }
      .lp-vid-layout  { grid-template-columns: 1fr; }
      .lp-vid-playlist {
        max-height: none;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        flex-direction: unset;
      }
    }
    @media (max-width: 600px) {
      .lp-train-grid  { grid-template-columns: 1fr; }
      .lp-feat-grid   { grid-template-columns: 1fr; }
      .lp-footer-grid { grid-template-columns: 1fr; }
      .lp-contact-grid{ grid-template-columns: 1fr; }
      .lp-course-grid { grid-template-columns: 1fr; }
      .lp-vid-playlist { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body class="landing-body">

<!-- ════════════════════════════════════════
     NAVBAR
════════════════════════════════════════ -->
<nav class="lp-nav" id="lpNav">
  <div class="lp-nav-inner">
    <!-- Brand -->
    <div class="lp-brand">
      <div class="lp-brand-logo">
        <img src="<?= BASE_URL ?>/imgs/logofinalpt.png" alt="PAThrive Logo"/>
      </div>
      <div>
        <div class="lp-brand-name">PAThrive</div>
        <div class="lp-brand-sub">CIT · SLSU</div>
      </div>
    </div>

    <!-- Nav links -->
    <div class="lp-links">
      <a href="<?= BASE_URL ?>/eclandingpage.php"    class="lp-link active">Home</a>
      <a href="<?= BASE_URL ?>/about.php"            class="lp-link">About</a>
      <a href="#courses"                             class="lp-link">Courses</a>
      <a href="<?= BASE_URL ?>/trainings-public.php" class="lp-link">Trainings</a>
      <a href="<?= BASE_URL ?>/contact.php"          class="lp-link">Contact</a>
    </div>

    <!-- Auth buttons -->
    <div class="lp-nav-actions">
      <a href="<?= BASE_URL ?>/login.php"  class="lp-btn-login">
        &#10148; Log In
      </a>
      <a href="<?= BASE_URL ?>/choose-role.php" class="lp-btn-signup">
        &#43; Sign Up
      </a>
    </div>
  </div>
</nav>

<!-- ════════════════════════════════════════
     HERO
════════════════════════════════════════ -->
<section class="lp-hero" id="home">
  <div class="lp-hero-bg">
    <div class="lp-hero-photo"></div>
    <div class="lp-grid-lines"></div>
    <div class="lp-orb lp-orb-1"></div>
    <div class="lp-orb lp-orb-2"></div>
    <div class="lp-orb lp-orb-3"></div>
  </div>

  <div class="lp-hero-inner" style="grid-template-columns:1fr;text-align:center;justify-items:center">
    <div style="max-width:720px">
      <div class="lp-eyebrow">
         Southern Luzon State University · CIT
      </div>
      <h1 class="lp-hero-h1">
        Empowering Communities<br>Through <span class="lp-hl">Skilled Training</span>
      </h1>
      <p class="lp-hero-p" style="margin-left:auto;margin-right:auto">
        PAThrive is the official Extension Training Management &amp; Impact Assessment Tracking System of the College of Industrial Technology — connecting communities with quality technical-vocational programs.
      </p>
      <div class="lp-hero-btns" style="justify-content:center">
        <a href="<?= BASE_URL ?>/login.php" class="lp-btn-hero-primary">
          &#10148; Log In
        </a>
        <a href="<?= BASE_URL ?>/choose-role.php" class="lp-btn-hero-ghost">
          &#43; Create Account
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════
     TICKER STRIP (scrolling news)
════════════════════════════════════════ -->
<div class="lp-ticker">
  <div class="lp-ticker-inner">
    <span class="lp-ticker-lbl">Latest</span>
    <div class="lp-ticker-wrap" style="overflow:hidden;flex:1">
      <div class="lp-ticker-track" id="lpTicker"></div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     VIDEO SHOWCASE — Course Highlights
════════════════════════════════════════ -->
<section class="lp-section lp-section-alt" id="courses" style="background:rgba(255,255,255,.04)">
  <div class="lp-container">

    <!-- Section header -->
    <div class="lp-sec-header" style="color:#fff">
      <div class="lp-sec-badge" style="background:rgba(56,189,248,.1);color:var(--lp-accent);border-color:rgba(56,189,248,.2)">
        🎬 Course Highlights
      </div>
      <h2 class="lp-sec-title" style="color:#fff">See Our Training Programs in Action</h2>
      <p class="lp-sec-desc" style="color:rgba(255,255,255,.6)">
        Watch real participants and trainers from CIT extension programs — from culinary arts to automotive technology.
      </p>
    </div>

    <!-- Featured video + side thumbnails -->
    <div class="lp-vid-layout">

      <!-- Main featured video -->
      <div class="lp-vid-main">
        <div class="lp-vid-frame" id="lpMainVid">
          <!-- Placeholder: swap src with actual video file or YouTube embed URL -->
          <video
            id="lpFeaturedVideo"
            class="lp-vid-player"
            controls
            poster="<?= BASE_URL ?>/imgs/landingpage.jpg"
            preload="none"
            aria-label="Featured training program highlight video"
          >
            <!-- Drop your video file here, e.g.:
            <source src="<?= BASE_URL ?>/assets/videos/highlight-reel.mp4" type="video/mp4"/>
            -->
            <p style="color:rgba(255,255,255,.5);padding:24px;text-align:center">
              Your browser does not support HTML5 video.
            </p>
          </video>
          <!-- Play overlay shown when no src is set yet -->
          <div class="lp-vid-placeholder" id="lpVidPlaceholder">
            <div class="lp-vid-ph-inner">
              <div class="lp-vid-play-ring">
                <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg" width="60" height="60" aria-hidden="true">
                  <circle cx="30" cy="30" r="29" stroke="rgba(255,255,255,.35)" stroke-width="2"/>
                  <polygon points="23,18 47,30 23,42" fill="#fff"/>
                </svg>
              </div>
              <p class="lp-vid-ph-label">Training Highlight Reel</p>
              <p class="lp-vid-ph-sub">Add your video file to <code>/assets/videos/</code></p>
            </div>
          </div>
        </div>
        <div class="lp-vid-caption" id="lpVidCaption">
          <span class="lp-vid-cap-tag">Featured</span>
          <span class="lp-vid-cap-title" id="lpCapTitle">CIT Extension Training Program — 2025 Highlights</span>
        </div>
      </div>

      <!-- Thumbnail playlist -->
      <div class="lp-vid-playlist" id="lpPlaylist">

        <div class="lp-vid-thumb active" data-title="CIT Extension Training — 2025 Highlights"
          data-src="" data-poster="<?= BASE_URL ?>/imgs/landingpage.jpg"
          onclick="lpSwitchVideo(this)" role="button" tabindex="0"
          onkeydown="if(event.key==='Enter')lpSwitchVideo(this)"
          aria-label="Play: CIT Extension Training 2025 Highlights">
          <div class="lp-vt-img" style="background:linear-gradient(135deg,#1A56DB,#38BDF8)">
            <span class="lp-vt-play-ico" aria-hidden="true">&#9654;</span>
            <span class="lp-vt-emoji" aria-hidden="true">🏆</span>
          </div>
          <div class="lp-vt-info">
            <div class="lp-vt-title">2025 Training Highlights</div>
            <div class="lp-vt-meta">All programs · Full reel</div>
          </div>
        </div>

        <div class="lp-vid-thumb" data-title="Culinary Technology — Pastry Making Batch 2"
          data-src="" data-poster="<?= BASE_URL ?>/imgs/landingpage.jpg"
          onclick="lpSwitchVideo(this)" role="button" tabindex="0"
          onkeydown="if(event.key==='Enter')lpSwitchVideo(this)"
          aria-label="Play: Culinary Technology Pastry Making">
          <div class="lp-vt-img" style="background:linear-gradient(135deg,#F59E0B,#FDE68A)">
            <span class="lp-vt-play-ico" aria-hidden="true">&#9654;</span>
            <span class="lp-vt-emoji" aria-hidden="true">🍳</span>
          </div>
          <div class="lp-vt-info">
            <div class="lp-vt-title">Culinary Technology</div>
            <div class="lp-vt-meta">Pastry Making · Batch 2</div>
          </div>
        </div>

        <div class="lp-vid-thumb" data-title="Computer Technology — Digital Literacy Program"
          data-src="" data-poster="<?= BASE_URL ?>/imgs/landingpage.jpg"
          onclick="lpSwitchVideo(this)" role="button" tabindex="0"
          onkeydown="if(event.key==='Enter')lpSwitchVideo(this)"
          aria-label="Play: Computer Technology Digital Literacy">
          <div class="lp-vt-img" style="background:linear-gradient(135deg,#6366F1,#A5B4FC)">
            <span class="lp-vt-play-ico" aria-hidden="true">&#9654;</span>
            <span class="lp-vt-emoji" aria-hidden="true">💻</span>
          </div>
          <div class="lp-vt-info">
            <div class="lp-vt-title">Computer Technology</div>
            <div class="lp-vt-meta">Digital Literacy Program</div>
          </div>
        </div>

        <div class="lp-vid-thumb" data-title="Automotive Technology — Basic Engine Overhaul"
          data-src="" data-poster="<?= BASE_URL ?>/imgs/landingpage.jpg"
          onclick="lpSwitchVideo(this)" role="button" tabindex="0"
          onkeydown="if(event.key==='Enter')lpSwitchVideo(this)"
          aria-label="Play: Automotive Technology Engine Overhaul">
          <div class="lp-vt-img" style="background:linear-gradient(135deg,#64748B,#CBD5E1)">
            <span class="lp-vt-play-ico" aria-hidden="true">&#9654;</span>
            <span class="lp-vt-emoji" aria-hidden="true">🔧</span>
          </div>
          <div class="lp-vt-info">
            <div class="lp-vt-title">Automotive Technology</div>
            <div class="lp-vt-meta">Basic Engine Overhaul</div>
          </div>
        </div>

        <div class="lp-vid-thumb" data-title="Apparel & Fashion Technology — Dressmaking Basics"
          data-src="" data-poster="<?= BASE_URL ?>/imgs/landingpage.jpg"
          onclick="lpSwitchVideo(this)" role="button" tabindex="0"
          onkeydown="if(event.key==='Enter')lpSwitchVideo(this)"
          aria-label="Play: Apparel Fashion Technology Dressmaking">
          <div class="lp-vt-img" style="background:linear-gradient(135deg,#EC4899,#FBD5E8)">
            <span class="lp-vt-play-ico" aria-hidden="true">&#9654;</span>
            <span class="lp-vt-emoji" aria-hidden="true">🪡</span>
          </div>
          <div class="lp-vt-info">
            <div class="lp-vt-title">Apparel &amp; Fashion</div>
            <div class="lp-vt-meta">Dressmaking Basics</div>
          </div>
        </div>

        <div class="lp-vid-thumb" data-title="Electronics Technology — Solar Panel Installation"
          data-src="" data-poster="<?= BASE_URL ?>/imgs/landingpage.jpg"
          onclick="lpSwitchVideo(this)" role="button" tabindex="0"
          onkeydown="if(event.key==='Enter')lpSwitchVideo(this)"
          aria-label="Play: Electronics Technology Solar Panel Installation">
          <div class="lp-vt-img" style="background:linear-gradient(135deg,#10B981,#A7F3D0)">
            <span class="lp-vt-play-ico" aria-hidden="true">&#9654;</span>
            <span class="lp-vt-emoji" aria-hidden="true">⚡</span>
          </div>
          <div class="lp-vt-info">
            <div class="lp-vt-title">Electronics Technology</div>
            <div class="lp-vt-meta">Solar Panel Installation</div>
          </div>
        </div>

      </div><!-- /playlist -->
    </div><!-- /lp-vid-layout -->

    <!-- CTA row under video -->
    <div style="text-align:center;margin-top:48px">
      <a href="<?= BASE_URL ?>/trainings-public.php" class="lp-btn-hero-ghost" style="display:inline-flex">
        View All Training Programs &rarr;
      </a>
    </div>

  </div>
</section>

<!-- ════════════════════════════════════════
     COURSE CARDS GRID
════════════════════════════════════════ -->
<section class="lp-section" id="programs" style="background:rgba(255,255,255,.02)">
  <div class="lp-container">
    <div class="lp-sec-header" style="color:#fff">
      <div class="lp-sec-badge" style="background:rgba(26,86,219,.1);color:var(--lp-accent);border-color:rgba(26,86,219,.2)">
        📚 Training Areas
      </div>
      <h2 class="lp-sec-title" style="color:#fff">Courses Offered</h2>
      <p class="lp-sec-desc" style="color:rgba(255,255,255,.6)">
        CIT offers community-based technical-vocational training across 8 technology areas — all free for qualified beneficiaries.
      </p>
    </div>

    <div class="lp-course-grid">

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#F59E0B,#FBBF24)">🍳</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Culinary Technology</div>
          <p class="lp-cc-desc">Learn professional food preparation, bakery, pastry arts, and kitchen management for employment or entrepreneurship.</p>
          <div class="lp-cc-tags"><span>Baking</span><span>Pastry Arts</span><span>Food Safety</span></div>
        </div>
      </div>

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#6366F1,#818CF8)">💻</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Computer Technology</div>
          <p class="lp-cc-desc">Digital literacy, basic programming, office productivity tools, and computer hardware servicing.</p>
          <div class="lp-cc-tags"><span>Digital Literacy</span><span>Hardware</span><span>MS Office</span></div>
        </div>
      </div>

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#64748B,#94A3B8)">🔧</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Automotive Technology</div>
          <p class="lp-cc-desc">Engine diagnostics, brake systems, preventive maintenance, and electrical repair for vehicles.</p>
          <div class="lp-cc-tags"><span>Engine Repair</span><span>Brakes</span><span>Diagnostics</span></div>
        </div>
      </div>

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#10B981,#34D399)">⚡</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Electronics Technology</div>
          <p class="lp-cc-desc">Solar panel installation, consumer electronics repair, and electrical wiring for homes and small businesses.</p>
          <div class="lp-cc-tags"><span>Solar Energy</span><span>Wiring</span><span>Repair</span></div>
        </div>
      </div>

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#EC4899,#F472B6)">🪡</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Apparel &amp; Fashion Technology</div>
          <p class="lp-cc-desc">Dressmaking, pattern-making, garment construction, and fashion design for local industry.</p>
          <div class="lp-cc-tags"><span>Dressmaking</span><span>Pattern-Making</span><span>Design</span></div>
        </div>
      </div>

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#1A56DB,#3B82F6)">⚙️</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Mechanical Technology</div>
          <p class="lp-cc-desc">Machining, welding, metal fabrication, and mechanical systems maintenance for industrial applications.</p>
          <div class="lp-cc-tags"><span>Welding</span><span>Machining</span><span>Fabrication</span></div>
        </div>
      </div>

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#7C3AED,#A78BFA)">🖨️</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Print Media Technology</div>
          <p class="lp-cc-desc">Graphic design, desktop publishing, digital printing, and print production for media and business.</p>
          <div class="lp-cc-tags"><span>Graphic Design</span><span>Publishing</span><span>Printing</span></div>
        </div>
      </div>

      <div class="lp-course-card">
        <div class="lp-cc-thumb" style="background:linear-gradient(135deg,#0EA5E9,#38BDF8)">🌐</div>
        <div class="lp-cc-body">
          <div class="lp-cc-area">Information Technology</div>
          <p class="lp-cc-desc">Web development, systems analysis, network fundamentals, and IT support for the digital economy.</p>
          <div class="lp-cc-tags"><span>Web Dev</span><span>Networking</span><span>IT Support</span></div>
        </div>
      </div>

    </div><!-- /lp-course-grid -->
  </div>
</section>

<!-- ════════════════════════════════════════
     FOOTER
════════════════════════════════════════ -->
<footer class="lp-footer">
  <div class="lp-container">
    <div class="lp-footer-grid">
      <!-- Brand -->
      <div>
        <div class="lp-footer-logo">
          <div class="lp-footer-logo-box">
            <img src="<?= BASE_URL ?>/imgs/logofinalpt.png" alt="PAThrive Logo"/>
          </div>
          <div class="lp-footer-logo-name">PAThrive</div>
        </div>
        <p class="lp-footer-tagline">Extension Training Management &amp; Impact Assessment Tracking System — College of Industrial Technology, Southern Luzon State University</p>
        <div class="lp-footer-contact">
          <div class="lp-footer-ci">&#128205; SLSU Main Campus, Lucban, Quezon, Philippines</div>
          <div class="lp-footer-ci">&#9993; cit.extension@slsu.edu.ph</div>
          <div class="lp-footer-ci">&#128222; (042) 540-XXXX</div>
        </div>
      </div>

      <!-- Quick links -->
      <div>
        <div class="lp-footer-col-title">Quick Links</div>
        <div class="lp-footer-links">
          <a href="<?= BASE_URL ?>/eclandingpage.php"    class="lp-footer-link">Home</a>
          <a href="<?= BASE_URL ?>/about.php"            class="lp-footer-link">About PAThrive</a>
          <a href="<?= BASE_URL ?>/trainings-public.php" class="lp-footer-link">Training Programs</a>
          <a href="<?= BASE_URL ?>/contact.php"          class="lp-footer-link">Contact Us</a>
        </div>
      </div>

      <!-- Training areas -->
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
          <span class="lp-footer-link">Apparel &amp; Fashion</span>
          <span class="lp-footer-link">General Programs</span>
        </div>
      </div>

      <!-- Access -->
      <div>
        <div class="lp-footer-col-title">Access</div>
        <div class="lp-footer-links">
          <a href="<?= BASE_URL ?>/login.php"       class="lp-footer-link">Log In to System</a>
          <a href="<?= BASE_URL ?>/choose-role.php" class="lp-footer-link">Register as Participant</a>
          <a href="<?= BASE_URL ?>/terms.php"       class="lp-footer-link">Terms of Use</a>
          <a href="<?= BASE_URL ?>/privacy.php"     class="lp-footer-link">Privacy Policy</a>
        </div>
      </div>
    </div>

    <!-- Footer bottom -->
    <div class="lp-footer-bottom">
      <div class="lp-footer-copy">
        © <?= date('Y') ?> PAThrive – SLSU College of Industrial Technology. All rights reserved.
      </div>
      <div class="lp-footer-badges">
        <div class="lp-footer-badge">ISO/IEC 25010:2023</div>
        <div class="lp-footer-badge">CHED Compliant</div>
        <div class="lp-footer-badge">SDG Aligned</div>
      </div>
    </div>
  </div>
</footer>

<!-- ════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════ -->
<script>
/* ── Video playlist switcher ── */
function lpSwitchVideo(thumb) {
  // Update active state in playlist
  document.querySelectorAll('.lp-vid-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');

  // Update caption
  const title = thumb.dataset.title || '';
  document.getElementById('lpCapTitle').textContent = title;

  // Update video src / poster
  const video   = document.getElementById('lpFeaturedVideo');
  const ph      = document.getElementById('lpVidPlaceholder');
  const src     = thumb.dataset.src  || '';
  const poster  = thumb.dataset.poster || '';

  if (src) {
    // Real video file: hide placeholder, update video
    video.poster = poster;
    // Replace source and reload
    const existing = video.querySelector('source');
    if (existing) { existing.src = src; } else {
      const s = document.createElement('source');
      s.src = src; s.type = 'video/mp4';
      video.appendChild(s);
    }
    video.load();
    ph?.classList.add('hidden');
    video.style.display = 'block';
  } else {
    // No video yet — just update placeholder label and poster
    video.poster = poster;
    video.style.display = poster ? 'block' : 'none';
    if (ph) {
      ph.classList.remove('hidden');
      const lbl = ph.querySelector('.lp-vid-ph-label');
      if (lbl) lbl.textContent = title || 'Training Highlight Reel';
    }
  }
}

/* ── Hide video tag when placeholder is shown (no src) ── */
(function initVideoSection() {
  const video  = document.getElementById('lpFeaturedVideo');
  const ph     = document.getElementById('lpVidPlaceholder');
  if (!video || !ph) return;
  // Check if any <source> has a real src
  const hasSrc = [...video.querySelectorAll('source')].some(s => s.src && s.src !== window.location.href);
  if (!hasSrc) {
    video.style.display = 'none'; // hide broken video element, show placeholder
    ph.classList.remove('hidden');
  }
})();

/* ── Navbar scroll effect ── */
window.addEventListener('scroll', () => {
  document.getElementById('lpNav').classList.toggle('scrolled', window.scrollY > 60);
});

/* ── Smooth scroll for nav links ── */
document.querySelectorAll('.lp-link, .lp-footer-link').forEach(a => {
  a.addEventListener('click', e => {
    const href = a.getAttribute('href');
    if (href && href.startsWith('#')) {
      e.preventDefault();
      document.querySelector(href)?.scrollIntoView({ behavior: 'smooth' });
      document.querySelectorAll('.lp-link').forEach(x => x.classList.remove('active'));
      if (a.classList.contains('lp-link')) a.classList.add('active');
    }
  });
});

/* ── Ticker ── */
(function buildTicker() {
  const items = [
    '📣 Registration for Dressmaking & Sewing Basics opens April 2, 2026 — Candelaria, Quezon',
    '🍳 Basic Pastry Making Batch 2 now accepting applicants — contact CIT Extension Office',
    '⚡ Solar Panel Installation Basics scheduled May 10 — Mauban, Quezon — limited slots',
    '💻 Computer Literacy Program ongoing — 40 participants enrolled — Module 3 released',
    '📋 Q1 2026 Accomplishment Report submitted to CHED Extension Office',
  ];
  const doubled = [...items, ...items];
  const track = document.getElementById('lpTicker');
  if (track) {
    track.innerHTML = doubled.map(t =>
      `<span class="lp-ticker-item"><span class="lp-ticker-dot"></span>${t}</span>`
    ).join('');
  }
})();

/* ── Animated counters ── */
(function animateCounters() {
  const targets = { cntTrain:8, cntBenef:232, cntLGU:14, cntAreas:5 };
  Object.entries(targets).forEach(([id, target]) => {
    const el = document.getElementById(id);
    if (!el) return;
    let cur = 0;
    const step = Math.ceil(target / 40);
    const timer = setInterval(() => {
      cur = Math.min(cur + step, target);
      el.textContent = cur + (id === 'cntBenef' ? '+' : '');
      if (cur >= target) clearInterval(timer);
    }, 35);
  });
})();

/* ── Training filter pills ── */
function lpFilter(el, status) {
  document.querySelectorAll('.lp-pill').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#lpTrainGrid .lp-tc').forEach(card => {
    const cardStatus = card.dataset.status || '';
    card.style.display = (!status || cardStatus === status) ? '' : 'none';
  });
}
</script>

</body>
</html>