<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'terms';
require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Terms of Use</span></div>
    <h1>Terms of Use</h1>
    <p>Last updated: <?= date('F Y') ?></p>
  </div>
</div>

<div class="card" style="max-width:860px">
  <div class="card-body" style="line-height:1.9;font-size:14px;color:var(--gray-700)">

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">1. Acceptance of Terms</div>
      <p>By accessing and using PAThrive, you agree to be bound by these Terms of Use. This system is intended exclusively for authorized personnel of the College of Industrial Technology (CIT), Southern Luzon State University (SLSU).</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">2. Authorized Use</div>
      <p>PAThrive is a web-based extension training management system. Access is granted only to Extension Coordinators and Faculty Trainers of CIT-SLSU. Unauthorized access or sharing of credentials is strictly prohibited.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">3. Data Accuracy</div>
      <p>Users are responsible for the accuracy of data entered into the system, including training records, participant information, and evaluation results. Falsification of records is a violation of university policy.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">4. Confidentiality</div>
      <p>All data within PAThrive, including participant personal information and training records, is confidential. Users must not disclose system data to unauthorized parties.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">5. System Availability</div>
      <p>CIT-SLSU does not guarantee uninterrupted access to PAThrive. Scheduled maintenance or technical issues may temporarily affect availability.</p>
    </div>

    <div style="margin-bottom:24px">
      <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:8px">6. Modifications</div>
      <p>These terms may be updated at any time. Continued use of the system after changes constitutes acceptance of the revised terms.</p>
    </div>

    <div style="margin-top:32px;padding:16px;background:var(--blue-xsoft);border-radius:var(--radius);border:1px solid var(--blue-soft)">
      <p style="font-size:13px;color:var(--navy-mid)">&#8505;For questions regarding these terms, contact the CIT Extension Office at <strong>cit.extension@slsu.edu.ph</strong>.</p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
