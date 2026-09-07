<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Privacy Policy</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
</head>
<body class="auth-body" style="display:block;padding:40px 24px;background:var(--gray-50)">

<div style="max-width:760px;margin:0 auto">
  <!-- Header -->
  <div style="text-align:center;margin-bottom:32px">
    <div class="brand-logo" style="margin:0 auto 14px;width:56px;height:56px">
      <img src="{{ \App\Models\PageContent::get('global', 'site_logo', asset('imgs/logofinalpt.png')) }}" alt="CIT" style="width:52px;height:52px;object-fit:contain" onerror="this.style.display='none'"/>
    </div>
    <h1 style="font-size:26px;font-weight:800;color:var(--navy)">{{ \App\Models\PageContent::get('privacy', 'page_heading', 'Privacy Policy') }}</h1>
    <p style="color:var(--gray-400);font-size:13px;margin-top:4px">PAThrive &middot; CIT-SLSU &middot; Last updated: {{ now()->format('F Y') }}</p>
  </div>

  <div class="card">
    <div class="card-body" style="line-height:1.9;font-size:14px;color:var(--gray-700);display:flex;flex-direction:column;gap:24px">
      @php
        $privacyBodyDefault = <<<'HTML'
          <div>
            <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">1. Information We Collect</div>
            <p>PAThrive collects the following to operate the extension training management system:</p>
            <ul style="margin-top:8px;padding-left:20px;display:flex;flex-direction:column;gap:6px">
              <li>User account information (name, email, ID number, role)</li>
              <li>Training program data (title, schedule, area, status)</li>
              <li>Participant information (name, ID number, enrollment, evaluation status)</li>
              <li>Uploaded documents and files</li>
            </ul>
          </div>
          <div>
            <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">2. How We Use Your Information</div>
            <p>Collected data is used solely for managing CIT-SLSU extension training programs, generating reports for CHED compliance, and tracking beneficiary outcomes. Data is not shared with third parties.</p>
          </div>
          <div>
            <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">3. Data Storage</div>
            <p>All data is stored on CIT-SLSU institutional servers. Uploaded files are stored in a secure directory accessible only through the system. Passwords are hashed and never stored in plain text.</p>
          </div>
          <div>
            <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">4. Data Retention</div>
            <p>Training records and participant data are retained for the duration required by CHED regulations and SLSU institutional policies. Users may request data correction through the Extension Office.</p>
          </div>
          <div>
            <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">5. Cookies &amp; Sessions</div>
            <p>PAThrive uses PHP sessions to maintain your login state. No third-party tracking cookies are used.</p>
          </div>
          <div>
            <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">6. Your Rights</div>
            <p>Authorized users may view and update their personal information through the My Profile page. For data deletion requests or concerns, contact the CIT Extension Office.</p>
          </div>
          <div style="padding:16px;background:var(--blue-xsoft);border-radius:var(--radius);border:1px solid var(--blue-soft)">
            <p style="font-size:13px;color:var(--navy-mid)"><i class="fas fa-shield-halved"></i> For privacy concerns, contact <strong>cit.extension@slsu.edu.ph</strong> or visit the CIT Extension Office at SLSU Main Campus, Lucban, Quezon.</p>
          </div>
          HTML;
      @endphp
      {!! \App\Models\PageContent::get('privacy', 'body_html', $privacyBodyDefault) !!}
    </div>
  </div>

  <div style="text-align:center;margin-top:24px;font-size:13px;color:var(--gray-400)">
    <a href="{{ route('terms') }}" style="color:var(--blue-primary)">Terms of Use</a> &middot;
    <a href="javascript:history.back()" style="color:var(--blue-primary)"><i class="fas fa-arrow-left"></i> Go Back</a>
  </div>
</div>
</body>
</html>
