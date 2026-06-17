<?php
require_once __DIR__ . '/includes/config.php';
$activePage = 'contact';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Contact Us</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Poppins', sans-serif; background: #F0F6FF; color: #334155; }

    /* ── NAV ── */
    .nav { position: fixed; top: 0; left: 0; right: 0; z-index: 500; height: 68px;
           background: rgba(9,24,47,.96); backdrop-filter: blur(16px);
           border-bottom: 1px solid rgba(255,255,255,.07); display: flex; align-items: center; transition: all .24s; }
    .nav.scrolled { background: rgba(9,24,47,.99); box-shadow: 0 4px 24px rgba(0,0,0,.3); }
    .nav-inner { display: flex; align-items: center; justify-content: space-between;
                 width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 28px; }
    .brand { display: flex; align-items: center; gap: 12px; }
    .brand-logo { width: 54px; height: 54px; border-radius: 12px; overflow: hidden;
                  background: #DBEAFE; border: 1.5px solid #93C5FD;
                  display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .brand-logo img { width: 46px; height: 46px; object-fit: contain; }
    .brand-name { font-size: 18px; font-weight: 800; color: #fff; letter-spacing: -.4px; }
    .brand-sub { font-size: 10px; color: rgba(255,255,255,.45); letter-spacing: .4px; }
    .nav-links { display: flex; align-items: center; gap: 4px; }
    .nav-link { padding: 7px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 500;
                color: rgba(255,255,255,.7); transition: all .24s; text-decoration: none; }
    .nav-link:hover, .nav-link.active { background: rgba(255,255,255,.08); color: #fff; }
    .nav-actions { display: flex; align-items: center; gap: 10px; }
    .btn-login { padding: 8px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600;
                 color: rgba(255,255,255,.85); background: rgba(255,255,255,.08);
                 border: 1px solid rgba(255,255,255,.15); transition: all .24s;
                 text-decoration: none; display: inline-flex; align-items: center; gap: 7px; }
    .btn-login:hover { background: rgba(255,255,255,.15); color: #fff; }
    .btn-signup { padding: 8px 20px; border-radius: 8px; font-size: 13.5px; font-weight: 700;
                  background: linear-gradient(135deg,#1A56DB,#2E6BF0); color: #fff;
                  box-shadow: 0 2px 10px rgba(26,86,219,.45); transition: all .24s;
                  text-decoration: none; display: inline-flex; align-items: center; gap: 7px; }
    .btn-signup:hover { transform: translateY(-1px); box-shadow: 0 4px 18px rgba(26,86,219,.6); }

    /* ── HERO ── */
    .hero {
      padding: 100px 0 0;
      background: linear-gradient(160deg, #07152A 0%, #0D2348 55%, #152F65 100%);
      position: relative;
      overflow: hidden;
      min-height: unset;
    }
    .hero::before {
      content: '';
      position: absolute;
      top: -60px; right: -100px;
      width: 600px; height: 600px;
      background: radial-gradient(circle, rgba(56,189,248,.10) 0%, transparent 65%);
      pointer-events: none;
    }
    .hero::after {
      content: '';
      position: absolute;
      bottom: 60px; left: -80px;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(26,86,219,.12) 0%, transparent 65%);
      pointer-events: none;
    }
    .hero-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 28px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding-bottom: 40px;
      position: relative;
      z-index: 1;
    }
    .hero-left {}
    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(56,189,248,.12); border: 1px solid rgba(56,189,248,.25);
      color: #38BDF8; font-size: 11px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1.4px; padding: 5px 14px; border-radius: 40px; margin-bottom: 20px;
    }
    .eyebrow-dot {
      width: 6px; height: 6px; border-radius: 50%; background: #38BDF8;
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: .5; transform: scale(0.8); }
    }
    .hero-title {
      font-size: clamp(30px, 4vw, 46px);
      font-weight: 800;
      color: #fff;
      line-height: 1.15;
      letter-spacing: -.5px;
      margin-bottom: 18px;
    }
    .hero-title span { color: #38BDF8; }
    .hero-desc {
      font-size: 15px;
      color: rgba(255,255,255,.60);
      line-height: 1.75;
      margin-bottom: 32px;
      max-width: 420px;
      margin-left: auto;
      margin-right: auto;
    }
    .hero-quick-links {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .hero-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.13);
      color: rgba(255,255,255,.75);
      font-size: 12.5px;
      font-weight: 500;
      padding: 7px 14px;
      border-radius: 40px;
      transition: all .24s;
    }
    .hero-pill:hover {
      background: rgba(255,255,255,.13);
      color: #fff;
    }
    /* Hero right — compact info card */
    .hero-card {
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 20px;
      padding: 32px;
      backdrop-filter: blur(8px);
    }
    .hero-card-title {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: rgba(255,255,255,.4);
      margin-bottom: 20px;
    }
    .hero-card-row {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 13px 0;
      border-bottom: 1px solid rgba(255,255,255,.07);
    }
    .hero-card-row:last-child { border-bottom: none; }
    .hero-card-ico {
      width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
    }
    .ico-blue   { background: rgba(26,86,219,.25); }
    .ico-sky    { background: rgba(56,189,248,.20); }
    .ico-green  { background: rgba(16,185,129,.20); }
    .ico-purple { background: rgba(99,102,241,.20); }
    .ico-orange { background: rgba(245,158,11,.20); }
    .ico-pink   { background: rgba(236,72,153,.20); }
    .hero-card-lbl {
      font-size: 11px;
      color: rgba(255,255,255,.4);
      margin-bottom: 2px;
      text-transform: uppercase;
      letter-spacing: .6px;
    }
    .hero-card-val {
      font-size: 13.5px;
      font-weight: 500;
      color: rgba(255,255,255,.88);
      line-height: 1.45;
    }

    /* ── WAVE ── */
    .hero-wave {
      display: block;
      width: 100%;
      height: 60px;
      margin-bottom: -2px;
    }

    /* ── MAIN CONTENT ── */
    .main-section { padding: 64px 0 88px; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 28px; }

    /* ── SECTION HEADING ── */
    .section-label {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    .section-label-line {
      width: 32px; height: 2px;
      background: linear-gradient(90deg, #1A56DB, #38BDF8);
      border-radius: 2px;
    }
    .section-label-text {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.4px;
      color: #1A56DB;
    }
    .section-heading {
      font-size: clamp(22px, 3vw, 32px);
      font-weight: 800;
      color: #09182F;
      letter-spacing: -.4px;
      margin-bottom: 8px;
    }
    .section-sub {
      font-size: 14px;
      color: #64748B;
      line-height: 1.65;
      max-width: 500px;
      margin-bottom: 48px;
    }

    /* ── CONTACT CARDS GRID ── */
    .contact-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 56px;
    }
    .contact-card {
      background: #fff;
      border-radius: 18px;
      padding: 24px 22px;
      border: 1px solid #E8EEF8;
      position: relative;
      overflow: hidden;
      transition: all .26s;
    }
    .contact-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      border-radius: 18px 18px 0 0;
      opacity: 0;
      transition: opacity .26s;
    }
    .contact-card:hover {
      box-shadow: 0 12px 40px rgba(9,24,47,.10);
      transform: translateY(-4px);
      border-color: #D0DEF5;
    }
    .contact-card:hover::before { opacity: 1; }
    .contact-card.blue::before   { background: linear-gradient(90deg,#1A56DB,#38BDF8); }
    .contact-card.green::before  { background: linear-gradient(90deg,#10B981,#34D399); }
    .contact-card.purple::before { background: linear-gradient(90deg,#8B5CF6,#A78BFA); }
    .contact-card.orange::before { background: linear-gradient(90deg,#F59E0B,#FCD34D); }
    .contact-card.sky::before    { background: linear-gradient(90deg,#0EA5E9,#7DD3FC); }
    .contact-card.pink::before   { background: linear-gradient(90deg,#EC4899,#F9A8D4); }

    .contact-card-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }
    .contact-ico {
      width: 48px; height: 48px; border-radius: 13px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 21px;
    }
    .ico-bg-blue   { background: rgba(26,86,219,.09); }
    .ico-bg-green  { background: rgba(16,185,129,.09); }
    .ico-bg-purple { background: rgba(139,92,246,.09); }
    .ico-bg-orange { background: rgba(245,158,11,.09); }
    .ico-bg-sky    { background: rgba(14,165,233,.09); }
    .ico-bg-pink   { background: rgba(236,72,153,.09); }

    .contact-arrow {
      width: 28px; height: 28px; border-radius: 8px;
      background: #F1F5F9;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; color: #94A3B8;
      transition: all .24s;
    }
    .contact-card:hover .contact-arrow {
      background: #EEF2FF;
      color: #1A56DB;
    }
    .contact-lbl {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .8px;
      color: #94A3B8;
      margin-bottom: 5px;
    }
    .contact-val {
      font-size: 14px;
      font-weight: 500;
      color: #0F2350;
      line-height: 1.55;
    }
    .contact-val.small { font-size: 13px; color: #334155; font-weight: 400; }

    /* ── MAP SECTION ── */
    .map-section {
      background: #fff;
      border-radius: 22px;
      border: 1px solid #E8EEF8;
      overflow: hidden;
    }
    .map-header {
      padding: 20px 24px;
      border-bottom: 1px solid #F1F5F9;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .map-header-left {
      display: flex; align-items: center; gap: 12px;
    }
    .map-header-ico {
      width: 40px; height: 40px; border-radius: 11px;
      background: rgba(26,86,219,.09);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
    }
    .map-title { font-size: 15px; font-weight: 700; color: #09182F; }
    .map-subtitle { font-size: 12px; color: #94A3B8; margin-top: 1px; }
    .map-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(16,185,129,.1); color: #065F46;
      font-size: 11px; font-weight: 600;
      padding: 4px 12px; border-radius: 20px;
      border: 1px solid rgba(16,185,129,.2);
    }
    .map-badge-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: #10B981;
      animation: pulse 2s ease-in-out infinite;
    }
    .map-body {
      background: linear-gradient(160deg, #07152A 0%, #0D2348 60%, #152F65 100%);
      height: 280px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    /* decorative grid overlay */
    .map-body::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(56,189,248,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56,189,248,.06) 1px, transparent 1px);
      background-size: 40px 40px;
    }
    /* glowing pin */
    .map-pin-wrap {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
    }
    .map-pin-glow {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      width: 120px; height: 120px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(26,86,219,.35) 0%, transparent 70%);
      pointer-events: none;
    }
    .map-pin-outer {
      width: 64px; height: 64px; border-radius: 50%;
      background: rgba(26,86,219,.20);
      border: 1.5px solid rgba(56,189,248,.35);
      display: flex; align-items: center; justify-content: center;
      position: relative;
    }
    .map-pin-inner {
      width: 42px; height: 42px; border-radius: 50%;
      background: linear-gradient(135deg, #1A56DB, #38BDF8);
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
      box-shadow: 0 4px 18px rgba(26,86,219,.45);
    }
    .map-location-text {
      text-align: center;
    }
    .map-location-name {
      font-size: 16px; font-weight: 700; color: #fff;
      margin-bottom: 4px;
    }
    .map-location-addr {
      font-size: 13px; color: rgba(255,255,255,.5);
    }
    .map-footer {
      padding: 16px 24px;
      background: #FAFBFF;
      border-top: 1px solid #F1F5F9;
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
    }
    .map-footer-item {
      display: flex; align-items: center; gap: 8px;
      font-size: 12.5px; color: #64748B;
    }
    .map-footer-item span { font-size: 14px; }

    /* ── CTA BANNER ── */
    .cta-banner {
      margin-top: 56px;
      background: linear-gradient(135deg, #09182F 0%, #0D2348 60%, #152F65 100%);
      border-radius: 22px;
      padding: 44px 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 32px;
      position: relative;
      overflow: hidden;
    }
    .cta-banner::before {
      content: '';
      position: absolute; top: -40px; right: 80px;
      width: 300px; height: 300px;
      background: radial-gradient(circle, rgba(56,189,248,.10) 0%, transparent 65%);
      pointer-events: none;
    }
    .cta-banner-left { position: relative; z-index: 1; }
    .cta-banner-label {
      font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px;
      color: #38BDF8; margin-bottom: 10px;
    }
    .cta-banner-title {
      font-size: 24px; font-weight: 800; color: #fff;
      line-height: 1.25; letter-spacing: -.3px; margin-bottom: 10px;
    }
    .cta-banner-sub {
      font-size: 13.5px; color: rgba(255,255,255,.55); line-height: 1.65;
    }
    .cta-banner-actions {
      display: flex; gap: 12px; flex-wrap: wrap;
      position: relative; z-index: 1; flex-shrink: 0;
    }
    .cta-btn-primary {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 11px 24px; border-radius: 10px;
      background: linear-gradient(135deg,#1A56DB,#2E6BF0);
      color: #fff; font-size: 13.5px; font-weight: 700;
      box-shadow: 0 3px 12px rgba(26,86,219,.45);
      text-decoration: none; transition: all .24s;
    }
    .cta-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 20px rgba(26,86,219,.6); }
    .cta-btn-ghost {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 22px; border-radius: 10px;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.15);
      color: rgba(255,255,255,.8); font-size: 13.5px; font-weight: 600;
      text-decoration: none; transition: all .24s;
    }
    .cta-btn-ghost:hover { background: rgba(255,255,255,.15); color: #fff; }

    /* ── FOOTER ── */
    .footer { background: #05101F; padding: 52px 0 28px; }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
    .footer-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .footer-logo-box { width: 50px; height: 50px; border-radius: 12px; overflow: hidden;
                       background: #DBEAFE; border: 1.5px solid #93C5FD;
                       display: flex; align-items: center; justify-content: center; }
    .footer-logo-box img { width: 44px; height: 44px; object-fit: contain; }
    .footer-logo-name { font-size: 18px; font-weight: 800; color: #fff; }
    .footer-tagline { font-size: 13px; color: rgba(255,255,255,.4); line-height: 1.7; max-width: 280px; margin-bottom: 16px; }
    .footer-ci { display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; color: rgba(255,255,255,.45); margin-bottom: 6px; }
    .footer-col-title { font-size: 13px; font-weight: 700; color: rgba(255,255,255,.7);
                        margin-bottom: 16px; text-transform: uppercase; letter-spacing: .6px; }
    .footer-links { display: flex; flex-direction: column; gap: 9px; }
    .footer-link { font-size: 13px; color: rgba(255,255,255,.4); text-decoration: none; display: block; transition: color .2s; }
    .footer-link:hover { color: rgba(255,255,255,.8); }
    .footer-bottom { padding-top: 24px; border-top: 1px solid rgba(255,255,255,.06);
                     display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .footer-copy { font-size: 12px; color: rgba(255,255,255,.3); }
    .footer-badges { display: flex; gap: 8px; }
    .footer-badge { font-size: 10px; font-weight: 600; color: rgba(255,255,255,.4);
                    border: 1px solid rgba(255,255,255,.12); padding: 3px 9px; border-radius: 20px; }

    /* ── CONTACT FORM ── */
    .contact-form {
      background: #fff;
      border-radius: 20px;
      border: 1px solid #E8EEF8;
      padding: 36px 40px;
      max-width: 760px;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 20px;
    }
    .form-group label {
      font-size: 12.5px;
      font-weight: 600;
      color: #334155;
      text-transform: uppercase;
      letter-spacing: .5px;
    }
    .form-group input,
    .form-group textarea {
      padding: 10px 14px;
      border: 1.5px solid #E2E8F0;
      border-radius: 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 13.5px;
      color: #0F2350;
      background: #FAFBFF;
      transition: border-color .2s, box-shadow .2s;
      outline: none;
      resize: vertical;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      border-color: #1A56DB;
      box-shadow: 0 0 0 3px rgba(26,86,219,.1);
      background: #fff;
    }
    .form-submit {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 28px;
      border-radius: 10px;
      background: linear-gradient(135deg,#1A56DB,#2E6BF0);
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 13.5px;
      font-weight: 700;
      border: none;
      cursor: pointer;
      box-shadow: 0 3px 12px rgba(26,86,219,.4);
      transition: all .24s;
    }
    .form-submit:hover { transform: translateY(-1px); box-shadow: 0 5px 20px rgba(26,86,219,.55); }
    .form-alert {
      padding: 13px 18px;
      border-radius: 10px;
      font-size: 13.5px;
      font-weight: 500;
      margin-bottom: 24px;
      max-width: 760px;
    }
    .form-alert.success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
    .form-alert.error   { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

    @media (max-width: 960px) {
      .footer-grid { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
      .contact-grid { grid-template-columns: repeat(2, 1fr); }
      .cta-banner { flex-direction: column; padding: 32px; }
      .form-row { grid-template-columns: 1fr; }
      .contact-form { padding: 24px 20px; }
    }
    @media (max-width: 600px) {
      .footer-grid { grid-template-columns: 1fr; }
      .contact-grid { grid-template-columns: 1fr; }
      .cta-banner { padding: 24px; }
      .cta-banner-title { font-size: 20px; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="mainNav">
  <div class="nav-inner">
    <div class="brand">
      <div class="brand-logo">
        <img src="<?= BASE_URL ?>/imgs/logofinalpt.png" alt="CIT" onerror="this.style.display='none'"/>
      </div>
      <div>
        <div class="brand-name">PAThrive</div>
        <div class="brand-sub">CIT · SLSU</div>
      </div>
    </div>
    <div class="nav-links">
      <a href="<?= BASE_URL ?>/eclandingpage.php"    class="nav-link">Home</a>
      <a href="<?= BASE_URL ?>/about.php"            class="nav-link">About</a>
      <a href="<?= BASE_URL ?>/trainings-public.php" class="nav-link">Trainings</a>
      <a href="<?= BASE_URL ?>/contact.php"          class="nav-link active">Contact</a>
    </div>
    <div class="nav-actions">
      <a href="<?= BASE_URL ?>/login.php"       class="btn-login">&#10148; Log In</a>
      <a href="<?= BASE_URL ?>/choose-role.php" class="btn-signup">&#43; Sign Up</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div class="hero-inner">
    <!-- Left -->
    <div class="hero-left">
      <div class="eyebrow">
        <span class="eyebrow-dot"></span>
        Contact Us
      </div>
      <h1 class="hero-title">
        Get in Touch<br/>
        with <span>CIT Extension</span>
      </h1>
      <p class="hero-desc">
        Have questions about our training programs, registration, or partnerships?
        We're here to help — reach out through any of the channels below.
      </p>

    </div>
  </div>

  <!-- Wave separator -->
  <svg class="hero-wave" viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
    <path d="M0,60 L0,30 Q360,0 720,30 Q1080,60 1440,20 L1440,60 Z" fill="#F0F6FF"/>
  </svg>
</div>

<!-- MAIN CONTENT -->
<section class="main-section">
  <div class="container">

    <div class="section-label">
      <span class="section-label-line"></span>
      <span class="section-label-text">How to Reach Us</span>
    </div>
    <h2 class="section-heading">Contact Information</h2>
    <p class="section-sub">All the ways you can connect with the CIT Extension Office at SLSU.</p>

    <?php
    // Handle form submission
    $formSuccess = false;
    $formError   = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
        require_once __DIR__ . '/includes/db.php';
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if (!$name || !$email || !$subject || !$message) {
            $formError = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $formError = 'Please enter a valid email address.';
        } else {
            try {
                // Save message
                $pdo->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)')
                    ->execute([$name, $email, $subject, $message]);
                $msgId = $pdo->lastInsertId();
                // Notify all ECs
                $ecs = $pdo->query("SELECT id FROM users WHERE role='extension_coordinator' AND is_active=1")->fetchAll();
                $notifMsg  = "New contact message from {$name}: " . mb_strimwidth($subject, 0, 60, '…');
                $notifLink = BASE_URL . '/ec/messages.php';
                foreach ($ecs as $ec) {
                    $pdo->prepare('INSERT INTO notifications (user_id,role,message,link) VALUES (?,?,?,?)')
                        ->execute([$ec['id'], 'all', $notifMsg, $notifLink]);
                }
                $formSuccess = true;
            } catch (\Throwable $e) {
                $formError = 'Something went wrong. Please try again.';
            }
        }
    }
    ?>

    <!-- Contact Cards -->
    <div class="contact-grid">
      <?php
        $contacts = [
          ['&#128205;', 'Address',      'College of Industrial Technology, Southern Luzon State University – Main Campus, Lucban, Quezon', 'small', 'blue',   'ico-bg-blue'],
          ['&#9993;',   'Email',        'cit.extension@slsu.edu.ph',  '',      'sky',    'ico-bg-sky'],
          ['&#128222;', 'Phone',        '(042) 540-XXXX',             '',      'green',  'ico-bg-green'],
          ['&#128336;', 'Office Hours', 'Monday – Friday, 8:00 AM – 5:00 PM', '', 'orange', 'ico-bg-orange'],
          ['&#127760;', 'Website',      'www.slsu.edu.ph',            '',      'purple', 'ico-bg-purple'],
          ['&#128241;', 'Facebook',     'facebook.com/slsu.official', '',      'pink',   'ico-bg-pink'],
        ];
        foreach ($contacts as [$icon, $label, $val, $valClass, $color, $icoClass]):
      ?>
      <div class="contact-card <?= $color ?>">
        <div class="contact-card-top">
          <div class="contact-ico <?= $icoClass ?>"><?= $icon ?></div>
          <div class="contact-arrow">&#8599;</div>
        </div>
        <div class="contact-lbl"><?= htmlspecialchars($label) ?></div>
        <div class="contact-val <?= $valClass ?>"><?= htmlspecialchars($val) ?></div>
      </div>
      <?php endforeach; ?>
    </div>



    <!-- Send a Message Form -->
    <div class="section-label" style="margin-top:56px">
      <span class="section-label-line"></span>
      <span class="section-label-text">Send a Message</span>
    </div>
    <h2 class="section-heading">Get in Touch</h2>
    <p class="section-sub">Fill out the form below and the Extension Coordinator will get back to you.</p>

    <?php if ($formSuccess): ?>
    <div class="form-alert success">
      &#10003; Your message has been sent. We'll get back to you soon.
    </div>
    <?php elseif ($formError): ?>
    <div class="form-alert error">
      &#9888; <?= htmlspecialchars($formError) ?>
    </div>
    <?php endif; ?>

    <form class="contact-form" method="POST" action="<?= BASE_URL ?>/contact.php#contact-form" id="contact-form">
      <input type="hidden" name="contact_submit" value="1"/>
      <div class="form-row">
        <div class="form-group">
          <label for="cf-name">Full Name</label>
          <input type="text" id="cf-name" name="name" placeholder="Your full name" required
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"/>
        </div>
        <div class="form-group">
          <label for="cf-email">Email Address</label>
          <input type="email" id="cf-email" name="email" placeholder="your@email.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label for="cf-subject">Subject</label>
        <input type="text" id="cf-subject" name="subject" placeholder="What is this about?" required
               value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"/>
      </div>
      <div class="form-group">
        <label for="cf-message">Message</label>
        <textarea id="cf-message" name="message" rows="5" placeholder="Write your message here…" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="form-submit">&#9993; Send Message</button>
    </form>

  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">
          <div class="footer-logo-box"><img src="<?= BASE_URL ?>/imgs/logofinalpt.png" alt="CIT" onerror="this.style.display='none'"/></div>
          <div class="footer-logo-name">PAThrive</div>
        </div>
        <p class="footer-tagline">Extension Training Management &amp; Impact Assessment Tracking System — College of Industrial Technology, SLSU</p>
        <div class="footer-ci">&#128205; SLSU Main Campus, Lucban, Quezon</div>
        <div class="footer-ci">&#9993; cit.extension@slsu.edu.ph</div>
        <div class="footer-ci">&#128222; (042) 540-XXXX</div>
      </div>
      <div>
        <div class="footer-col-title">Pages</div>
        <div class="footer-links">
          <a href="<?= BASE_URL ?>/eclandingpage.php"    class="footer-link">Home</a>
          <a href="<?= BASE_URL ?>/about.php"            class="footer-link">About</a>
          <a href="<?= BASE_URL ?>/trainings-public.php" class="footer-link">Training Programs</a>
          <a href="<?= BASE_URL ?>/contact.php"          class="footer-link">Contact Us</a>
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
          <a href="<?= BASE_URL ?>/login.php"       class="footer-link">Log In to System</a>
          <a href="<?= BASE_URL ?>/choose-role.php" class="footer-link">Sign Up</a>
          <a href="<?= BASE_URL ?>/terms.php"       class="footer-link">Terms of Use</a>
          <a href="<?= BASE_URL ?>/privacy.php"     class="footer-link">Privacy Policy</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-copy">© <?= date('Y') ?> PAThrive – SLSU College of Industrial Technology. All rights reserved.</div>
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
</script>
</body>
</html>