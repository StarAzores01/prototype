@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Privacy Policy</span></div>
    <h1>Privacy Policy</h1>
    <p>Last updated: {{ date('F Y') }}</p>
  </div>
</div>

<div class="card" style="max-width:860px">
  <div class="card-body" style="line-height:1.9;font-size:14px;color:var(--gray-700)">

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">1. Information We Collect</div>
      <p>PAThrive collects the following information to operate the extension training management system:</p>
      <ul style="margin-top:8px;padding-left:20px;display:flex;flex-direction:column;gap:6px">
        <li>User account information (name, email, ID number, role)</li>
        <li>Training program data (title, schedule, area, status)</li>
        <li>Participant information (name, ID number, enrollment, evaluation status)</li>
        <li>Uploaded documents and files</li>
      </ul>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">2. How We Use Your Information</div>
      <p>Collected data is used solely for managing CIT-SLSU extension training programs, generating reports for CHED compliance, and tracking beneficiary outcomes. Data is not shared with third parties.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">3. Data Storage</div>
      <p>All data is stored on CIT-SLSU institutional servers. Uploaded files are stored in a secure directory accessible only through the system. Passwords are hashed and never stored in plain text.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">4. Data Retention</div>
      <p>Training records and participant data are retained for the duration required by CHED regulations and SLSU institutional policies. Users may request data correction through the Extension Office.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">5. Cookies &amp; Sessions</div>
      <p>PAThrive uses PHP sessions to maintain your login state. No third-party tracking cookies are used.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">6. Your Rights</div>
      <p>Authorized users may view and update their personal information through the My Profile page. For data deletion requests or concerns, contact the CIT Extension Office.</p>
    </div>

    <div style="margin-top:32px;padding:16px;background:var(--blue-xsoft);border-radius:var(--radius);border:1px solid var(--blue-soft)">
      <p style="font-size:13px;color:var(--navy-mid)">&#128737;For privacy concerns, contact <strong>cit.extension@slsu.edu.ph</strong> or visit the CIT Extension Office at SLSU Main Campus, Lucban, Quezon.</p>
    </div>
  </div>
</div>
@endsection
