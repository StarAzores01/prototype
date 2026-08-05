<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Terms of Use</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
</head>
<body class="auth-body" style="display:block;padding:40px 24px;background:var(--gray-50)">

<div style="max-width:760px;margin:0 auto">
  <!-- Header -->
  <div style="text-align:center;margin-bottom:32px">
    <div class="brand-logo" style="margin:0 auto 14px;width:56px;height:56px">
      <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT" style="width:52px;height:52px;object-fit:contain" onerror="this.style.display='none'"/>
    </div>
    <h1 style="font-size:26px;font-weight:800;color:var(--navy)">Terms of Use</h1>
    <p style="color:var(--gray-400);font-size:13px;margin-top:4px">PAThrive &middot; CIT-SLSU &middot; Last updated: {{ now()->format('F Y') }}</p>
  </div>

  <div class="card">
    <div class="card-body" style="line-height:1.9;font-size:14px;color:var(--gray-700);display:flex;flex-direction:column;gap:24px">

      <div>
        <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">1. Acceptance of Terms</div>
        <p>By accessing and using PAThrive, you agree to be bound by these Terms of Use. This system is intended exclusively for authorized personnel of the College of Industrial Technology (CIT), Southern Luzon State University (SLSU).</p>
      </div>

      <div>
        <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">2. Authorized Use</div>
        <p>PAThrive is a web-based extension training management system. Access is granted only to Extension Coordinators and Faculty Trainers of CIT-SLSU. Unauthorized access or sharing of credentials is strictly prohibited.</p>
      </div>

      <div>
        <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">3. Data Accuracy</div>
        <p>Users are responsible for the accuracy of data entered into the system, including training records, participant information, and evaluation results. Falsification of records is a violation of university policy.</p>
      </div>

      <div>
        <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">4. Confidentiality</div>
        <p>All data within PAThrive, including participant personal information and training records, is confidential. Users must not disclose system data to unauthorized parties.</p>
      </div>

      <div>
        <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">5. System Availability</div>
        <p>CIT-SLSU does not guarantee uninterrupted access to PAThrive. Scheduled maintenance or technical issues may temporarily affect availability.</p>
      </div>

      <div>
        <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">6. Modifications</div>
        <p>These terms may be updated at any time. Continued use of the system after changes constitutes acceptance of the revised terms.</p>
      </div>

      <div style="padding:16px;background:var(--blue-xsoft);border-radius:var(--radius);border:1px solid var(--blue-soft)">
        <p style="font-size:13px;color:var(--navy-mid)"><i class="fas fa-circle-info"></i> For questions, contact the CIT Extension Office at <strong>cit.extension@slsu.edu.ph</strong>.</p>
      </div>
    </div>
  </div>

  <div style="text-align:center;margin-top:24px;font-size:13px;color:var(--gray-400)">
    <a href="{{ route('privacy') }}" style="color:var(--blue-primary)">Privacy Policy</a> &middot;
    <a href="javascript:history.back()" style="color:var(--blue-primary)"><i class="fas fa-arrow-left"></i> Go Back</a>
  </div>
</div>
</body>
</html>
