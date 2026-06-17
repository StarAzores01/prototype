<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'reports';

// ── Summary stats ─────────────────────────────────────────────────────────
$totalTrainings = (int)$pdo->query('SELECT COUNT(*) FROM trainings')->fetchColumn();
$completedCount = (int)$pdo->query('SELECT COUNT(*) FROM trainings WHERE status="Completed"')->fetchColumn();
$ongoingCount   = (int)$pdo->query('SELECT COUNT(*) FROM trainings WHERE status="Ongoing"')->fetchColumn();
$proposedCount  = (int)$pdo->query('SELECT COUNT(*) FROM trainings WHERE status="Proposed"')->fetchColumn();
$totalPart      = (int)$pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn();
$totalBen       = (int)$pdo->query('SELECT COUNT(*) FROM beneficiaries')->fetchColumn();

// ── 1. Training Accomplishments ───────────────────────────────────────────
$trainings = $pdo->query(
    'SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name,
            COUNT(DISTINCT p.id) AS pax_count
     FROM trainings t
     LEFT JOIN users u ON u.id = t.trainer_id
     LEFT JOIN participants p ON p.training_id = t.id
     GROUP BY t.id ORDER BY t.date_start DESC'
)->fetchAll();

// Trainings by area (for chart)
$byArea = $pdo->query(
    'SELECT area, COUNT(*) AS cnt FROM trainings GROUP BY area ORDER BY cnt DESC'
)->fetchAll();

// Trainings by status (for chart)
$byStatus = $pdo->query(
    'SELECT status, COUNT(*) AS cnt FROM trainings GROUP BY status'
)->fetchAll();

// ── 2. Beneficiary Report ─────────────────────────────────────────────────
$beneficiaries = $pdo->query(
    'SELECT b.id, CONCAT(b.first_name," ",b.last_name) AS full_name,
            b.email, b.address, b.created_at,
            COUNT(DISTINCT p.training_id) AS enrolled_count,
            GROUP_CONCAT(DISTINCT t.title ORDER BY t.title SEPARATOR ", ") AS trainings_list
     FROM beneficiaries b
     LEFT JOIN participants p ON p.beneficiary_id = b.id
     LEFT JOIN trainings t ON t.id = p.training_id
     GROUP BY b.id ORDER BY b.created_at DESC'
)->fetchAll();

// Participants per training (for chart)
$partPerTraining = $pdo->query(
    'SELECT t.title, COUNT(p.id) AS cnt
     FROM trainings t
     LEFT JOIN participants p ON p.training_id = t.id
     GROUP BY t.id ORDER BY cnt DESC'
)->fetchAll();
// Aggregated pct from skills_utilization table (per training)
$skillsUtilData = [];
try {
    $skillsUtilData = $pdo->query(
        'SELECT t.title AS training_title,
                ROUND(AVG(su.personal_use_pct), 1) AS personal_use,
                ROUND(AVG(su.income_gen_pct),   1) AS income_gen,
                ROUND(AVG(su.employment_pct),   1) AS employment
         FROM skills_utilization su
         JOIN trainings t ON t.id = su.training_id
         GROUP BY su.training_id ORDER BY t.title'
    )->fetchAll();
} catch (\Throwable $e) { $skillsUtilData = []; }

// Overall averages — kept for potential future use
$skillsOverall = ['personal_use' => 0, 'income_gen' => 0, 'employment' => 0];

$skillsForms = [];
try {
    $skillsForms = $pdo->query(
        'SELECT sf.id, sf.title, sf.fields, sf.sent_at,
                t.title AS training_title, t.area,
                COUNT(DISTINCT sr.id) AS responded,
                COUNT(DISTINCT p.id)  AS total_pax
         FROM skills_forms sf
         JOIN trainings t ON t.id = sf.training_id
         LEFT JOIN participants p ON p.training_id = t.id
         LEFT JOIN skills_responses sr ON sr.form_id = sf.id
         GROUP BY sf.id ORDER BY sf.sent_at DESC'
    )->fetchAll();
} catch (\Throwable $e) { $skillsForms = []; }

// Aggregate all skills responses per form
$skillsSummary = [];
foreach ($skillsForms as $sf) {
    $fields = json_decode($sf['fields'], true) ?? [];
    try {
        $rows = $pdo->prepare('SELECT responses FROM skills_responses WHERE form_id=?');
        $rows->execute([$sf['id']]);
        $allResp = $rows->fetchAll();
    } catch (\Throwable $e) { $allResp = []; }

    $tally = [];
    foreach ($fields as $fi => $field) {
        $tally[$fi] = ['label' => $field['label'], 'type' => $field['type'], 'counts' => []];
        foreach ($allResp as $r) {
            $ans = json_decode($r['responses'], true) ?? [];
            $val = trim($ans[$fi] ?? '');
            if ($val !== '') $tally[$fi]['counts'][$val] = ($tally[$fi]['counts'][$val] ?? 0) + 1;
        }
        arsort($tally[$fi]['counts']);
    }
    $skillsSummary[$sf['id']] = ['form' => $sf, 'fields' => $fields, 'tally' => $tally, 'total' => count($allResp)];
}

// ── 4. Evaluation Summary ─────────────────────────────────────────────────
$evalForms = [];
try {
    $evalForms = $pdo->query(
        'SELECT ef.id, ef.title, ef.fields, ef.sent_at,
                t.title AS training_title, t.area,
                COUNT(DISTINCT er.id) AS responded,
                COUNT(DISTINCT p.id)  AS total_pax
         FROM eval_forms ef
         JOIN trainings t ON t.id = ef.training_id
         LEFT JOIN participants p ON p.training_id = t.id
         LEFT JOIN eval_responses er ON er.form_id = ef.id
         GROUP BY ef.id ORDER BY ef.sent_at DESC'
    )->fetchAll();
} catch (\Throwable $e) { $evalForms = []; }

$evalSummary = [];
foreach ($evalForms as $ef) {
    $fields = json_decode($ef['fields'], true) ?? [];
    try {
        $rows = $pdo->prepare('SELECT responses FROM eval_responses WHERE form_id=?');
        $rows->execute([$ef['id']]);
        $allResp = $rows->fetchAll();
    } catch (\Throwable $e) { $allResp = []; }

    $tally = [];
    foreach ($fields as $fi => $field) {
        $tally[$fi] = ['label' => $field['label'], 'type' => $field['type'], 'counts' => []];
        foreach ($allResp as $r) {
            $ans = json_decode($r['responses'], true) ?? [];
            $val = trim($ans[$fi] ?? '');
            if ($val !== '') $tally[$fi]['counts'][$val] = ($tally[$fi]['counts'][$val] ?? 0) + 1;
        }
        arsort($tally[$fi]['counts']);
    }
    $evalSummary[$ef['id']] = ['form' => $ef, 'fields' => $fields, 'tally' => $tally, 'total' => count($allResp)];
}

require __DIR__ . '/layout.php';
?>

<style>
.rpt-section { margin-bottom: 40px; }
.rpt-section-header {
  display: flex; align-items: center; gap: 12px;
  padding: 0 0 14px; margin-bottom: 20px;
  border-bottom: 2px solid var(--gray-100);
}
.rpt-section-ico {
  width: 40px; height: 40px; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}
.rpt-section-ico.blue   { background: rgba(26,86,219,.1); }
.rpt-section-ico.green  { background: rgba(16,185,129,.1); }
.rpt-section-ico.purple { background: rgba(139,92,246,.1); }
.rpt-section-ico.orange { background: rgba(245,158,11,.1); }
.rpt-section-title { font-size: 17px; font-weight: 800; color: var(--navy); }
.rpt-section-sub   { font-size: 12.5px; color: var(--gray-400); margin-top: 2px; }

/* mini stat row */
.mini-stats { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; }
.mini-stat {
  background: #fff; border: 1px solid var(--gray-200); border-radius: 12px;
  padding: 14px 20px; min-width: 120px; flex: 1;
}
.mini-stat-val { font-size: 26px; font-weight: 800; color: var(--navy); line-height: 1; }
.mini-stat-lbl { font-size: 11.5px; color: var(--gray-400); margin-top: 4px; }
.mini-stat.blue   .mini-stat-val { color: var(--blue-primary); }
.mini-stat.green  .mini-stat-val { color: var(--green); }
.mini-stat.orange .mini-stat-val { color: var(--yellow); }
.mini-stat.purple .mini-stat-val { color: #8B5CF6; }

/* chart row */
.chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:860px){ .chart-row { grid-template-columns: 1fr; } }
.chart-card {
  background: #fff; border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px;
}
.chart-card-title { font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 16px; }
canvas { max-height: 220px; }

/* bar summary */
.bar-summary { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: flex; align-items: center; gap: 10px; }
.bar-label { min-width: 160px; font-size: 12.5px; color: var(--gray-700); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bar-track { flex: 1; background: var(--gray-100); border-radius: 4px; height: 8px; }
.bar-fill  { height: 8px; border-radius: 4px; }
.bar-count { min-width: 52px; font-size: 12px; color: var(--gray-500); text-align: right; }

/* skills/eval accordion */
.survey-block {
  background: #fff; border: 1px solid var(--gray-200); border-radius: 14px;
  margin-bottom: 14px; overflow: hidden;
}
.survey-block-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; cursor: pointer; user-select: none;
  transition: background .18s;
}
.survey-block-header:hover { background: var(--gray-50); }
.survey-block-title { font-size: 14px; font-weight: 700; color: var(--navy); }
.survey-block-meta  { font-size: 12px; color: var(--gray-400); margin-top: 2px; }
.survey-block-body  { padding: 0 20px 20px; border-top: 1px solid var(--gray-100); display: none; }
.survey-block-body.open { display: block; }
.survey-q { margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid var(--gray-100); }
.survey-q:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.survey-q-label { font-size: 13px; font-weight: 700; color: var(--navy); margin-bottom: 10px; }
.survey-q-type  { font-size: 11px; color: var(--gray-400); font-weight: 400; margin-left: 6px; }
.text-answers   { display: flex; flex-direction: column; gap: 6px; }
.text-answer    { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: 8px 12px; font-size: 13px; color: var(--gray-700); }
.no-resp        { font-size: 13px; color: var(--gray-300); font-style: italic; }

/* response rate pill */
.rate-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
}
.rate-pill.high   { background: rgba(16,185,129,.1); color: #065F46; }
.rate-pill.medium { background: rgba(245,158,11,.1);  color: #92400E; }
.rate-pill.low    { background: rgba(239,68,68,.1);   color: #991B1B; }
</style>

<div class="page-header">
  <div>
    <div class="page-title">Reports</div>
    <div class="page-sub">Comprehensive overview of training accomplishments, beneficiaries, skills utilization, and evaluations</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TOP SUMMARY STATS
════════════════════════════════════════════════════════════ -->
<div class="mini-stats" style="margin-bottom:36px">
  <div class="mini-stat blue">
    <div class="mini-stat-val"><?= $totalTrainings ?></div>
    <div class="mini-stat-lbl">Total Trainings</div>
  </div>
  <div class="mini-stat green">
    <div class="mini-stat-val"><?= $completedCount ?></div>
    <div class="mini-stat-lbl">Completed</div>
  </div>
  <div class="mini-stat orange">
    <div class="mini-stat-val"><?= $ongoingCount ?></div>
    <div class="mini-stat-lbl">Ongoing</div>
  </div>
  <div class="mini-stat" style="">
    <div class="mini-stat-val" style="color:#8B5CF6"><?= $proposedCount ?></div>
    <div class="mini-stat-lbl">Proposed</div>
  </div>
  <div class="mini-stat">
    <div class="mini-stat-val"><?= $totalPart ?></div>
    <div class="mini-stat-lbl">Total Participants</div>
  </div>
  <div class="mini-stat">
    <div class="mini-stat-val"><?= $totalBen ?></div>
    <div class="mini-stat-lbl">Registered Beneficiaries</div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 1 — TRAINING ACCOMPLISHMENTS
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico blue">&#128218;</div>
    <div>
      <div class="rpt-section-title">Training Accomplishments</div>
      <div class="rpt-section-sub">All training programs and their current status</div>
    </div>
  </div>

  <div class="chart-row">
    <div class="chart-card">
      <div class="chart-card-title">&#128202; Trainings by Status</div>
      <canvas id="chartStatus"></canvas>
    </div>
    <div class="chart-card">
      <div class="chart-card-title">&#128218; Trainings by Technology Area</div>
      <div style="position:relative;height:<?= max(180, count($byArea) * 36) ?>px">
        <canvas id="chartArea"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 2 — BENEFICIARY REPORT
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico green">&#128101;</div>
    <div>
      <div class="rpt-section-title">Beneficiary Report</div>
      <div class="rpt-section-sub">All registered beneficiaries and their training enrollment</div>
    </div>
  </div>

  <div class="chart-row" style="grid-template-columns:1fr">
    <div class="chart-card">
      <div class="chart-card-title">&#128218; Participants per Training</div>
      <?php if (empty($partPerTraining)): ?>
      <div style="text-align:center;padding:40px;color:var(--gray-400);font-size:13px">No data yet.</div>
      <?php else: ?>
      <div style="position:relative;height:<?= max(180, count($partPerTraining) * 36) ?>px">
        <canvas id="chartPartPerTraining"></canvas>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Address</th>
          <th>Trainings Enrolled</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($beneficiaries)): ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400)">No beneficiaries found.</td></tr>
        <?php else: foreach ($beneficiaries as $i => $b): ?>
        <tr>
          <td style="color:var(--gray-400);font-size:12px"><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($b['full_name']) ?></strong></td>
          <td style="font-size:12.5px;color:var(--gray-500)"><?= htmlspecialchars($b['email']) ?></td>
          <td style="font-size:12.5px;color:var(--gray-600)"><?= htmlspecialchars($b['address'] ?? '—') ?></td>
          <td>
            <strong><?= (int)$b['enrolled_count'] ?></strong>
            <?php if ($b['trainings_list']): ?>
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px"><?= htmlspecialchars(mb_strimwidth($b['trainings_list'], 0, 80, '…')) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--gray-400)"><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 3 — SKILLS UTILIZATION
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico purple">&#128200;</div>
    <div>
      <div class="rpt-section-title">Skills Utilization</div>
      <div class="rpt-section-sub">Aggregated beneficiary responses to post-training skills surveys</div>
    </div>
  </div>

  <!-- Skills Utilization Charts removed -->

  <?php if (empty($skillsSummary)): ?>
  <div class="card"><div style="text-align:center;padding:48px;color:var(--gray-400)">No skills surveys have been sent yet.</div></div>
  <?php else: foreach ($skillsSummary as $sid => $s):
    $sf   = $s['form'];
    $rate = $sf['total_pax'] > 0 ? round($sf['responded'] / $sf['total_pax'] * 100) : 0;
    $rateClass = $rate >= 70 ? 'high' : ($rate >= 40 ? 'medium' : 'low');
  ?>
  <div class="survey-block">
    <div class="survey-block-header" onclick="toggleSurvey('sk-<?= $sid ?>')">
      <div>
        <div class="survey-block-title"><?= htmlspecialchars($sf['title']) ?></div>
        <div class="survey-block-meta">
          &#128218; <?= htmlspecialchars($sf['training_title']) ?>
          &nbsp;·&nbsp; <?= (int)$sf['responded'] ?> / <?= (int)$sf['total_pax'] ?> responded
          &nbsp;·&nbsp; <span class="rate-pill <?= $rateClass ?>"><?= $rate ?>% response rate</span>
        </div>
      </div>
      <span style="font-size:18px;color:var(--gray-400)" id="sk-<?= $sid ?>-arrow">&#9660;</span>
    </div>
    <div class="survey-block-body" id="sk-<?= $sid ?>">
      <?php if ($s['total'] === 0): ?>
      <div class="no-resp" style="padding:16px 0">No responses submitted yet.</div>
      <?php else: foreach ($s['tally'] as $fi => $q): ?>
      <div class="survey-q">
        <div class="survey-q-label">
          <?= $fi+1 ?>. <?= htmlspecialchars($q['label']) ?>
          <span class="survey-q-type">(<?= ucfirst($q['type']) ?>)</span>
        </div>
        <?php if (in_array($q['type'], ['radio','select']) && !empty($q['counts'])): ?>
        <div class="bar-summary">
          <?php $maxC = max($q['counts']);
          foreach ($q['counts'] as $opt => $cnt):
            $pct = round($cnt / $s['total'] * 100);
          ?>
          <div class="bar-row">
            <div class="bar-label" title="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;background:#8B5CF6"></div></div>
            <div class="bar-count"><?= $cnt ?> (<?= $pct ?>%)</div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php elseif (!empty($q['counts'])): ?>
        <div class="text-answers">
          <?php foreach (array_keys($q['counts']) as $ans): ?>
          <div class="text-answer"><?= htmlspecialchars($ans) ?></div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-resp">No answers recorded.</div>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 4 — EVALUATION SUMMARY
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico orange">&#11088;</div>
    <div>
      <div class="rpt-section-title">Evaluation Summary</div>
      <div class="rpt-section-sub">Aggregated participant feedback and satisfaction ratings per training</div>
    </div>
  </div>

  <?php if (empty($evalSummary)): ?>
  <div class="card"><div style="text-align:center;padding:48px;color:var(--gray-400)">No evaluation forms have been sent yet.</div></div>
  <?php else: foreach ($evalSummary as $eid => $s):
    $ef   = $s['form'];
    $rate = $ef['total_pax'] > 0 ? round($ef['responded'] / $ef['total_pax'] * 100) : 0;
    $rateClass = $rate >= 70 ? 'high' : ($rate >= 40 ? 'medium' : 'low');
  ?>
  <div class="survey-block">
    <div class="survey-block-header" onclick="toggleSurvey('ev-<?= $eid ?>')">
      <div>
        <div class="survey-block-title"><?= htmlspecialchars($ef['title']) ?></div>
        <div class="survey-block-meta">
          &#128218; <?= htmlspecialchars($ef['training_title']) ?>
          &nbsp;·&nbsp; <?= (int)$ef['responded'] ?> / <?= (int)$ef['total_pax'] ?> responded
          &nbsp;·&nbsp; <span class="rate-pill <?= $rateClass ?>"><?= $rate ?>% response rate</span>
        </div>
      </div>
      <span style="font-size:18px;color:var(--gray-400)" id="ev-<?= $eid ?>-arrow">&#9660;</span>
    </div>
    <div class="survey-block-body" id="ev-<?= $eid ?>">
      <?php if ($s['total'] === 0): ?>
      <div class="no-resp" style="padding:16px 0">No responses submitted yet.</div>
      <?php else: foreach ($s['tally'] as $fi => $q): ?>
      <div class="survey-q">
        <div class="survey-q-label">
          <?= $fi+1 ?>. <?= htmlspecialchars($q['label']) ?>
          <span class="survey-q-type">(<?= ucfirst($q['type']) ?>)</span>
        </div>
        <?php if (in_array($q['type'], ['radio','select','rating']) && !empty($q['counts'])): ?>
        <div class="bar-summary">
          <?php foreach ($q['counts'] as $opt => $cnt):
            $pct = round($cnt / $s['total'] * 100);
          ?>
          <div class="bar-row">
            <div class="bar-label" title="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;background:var(--yellow)"></div></div>
            <div class="bar-count"><?= $cnt ?> (<?= $pct ?>%)</div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php elseif (!empty($q['counts'])): ?>
        <div class="text-answers">
          <?php foreach (array_keys($q['counts']) as $ans): ?>
          <div class="text-answer"><?= htmlspecialchars($ans) ?></div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-resp">No answers recorded.</div>
        <?php endif; ?>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Status doughnut ───────────────────────────────────────────────────────
const statusData = <?= json_encode(array_column($byStatus, 'cnt')) ?>;
const statusLabels = <?= json_encode(array_column($byStatus, 'status')) ?>;
new Chart(document.getElementById('chartStatus'), {
  type: 'doughnut',
  data: {
    labels: statusLabels,
    datasets: [{ data: statusData,
      backgroundColor: ['#1A56DB','#10B981','#F59E0B','#8B5CF6'],
      borderWidth: 2, borderColor: '#fff' }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } }, cutout: '62%' }
});

// ── Area bar ──────────────────────────────────────────────────────────────
const areaLabels = <?= json_encode(array_column($byArea, 'area')) ?>;
const areaData   = <?= json_encode(array_column($byArea, 'cnt')) ?>;
new Chart(document.getElementById('chartArea'), {
  type: 'bar',
  data: {
    labels: areaLabels,
    datasets: [{ label: 'Trainings', data: areaData,
      backgroundColor: 'rgba(26,86,219,.75)', borderRadius: 6, borderSkipped: false }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false }, ticks: { font: { size: 11 } } },
              y: { grid: { display: false }, ticks: { font: { size: 11 } } } }
  }
});


// ── Participants per training (horizontal bar) ────────────────────────────
<?php if (!empty($partPerTraining)): ?>
new Chart(document.getElementById('chartPartPerTraining'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($r) => mb_strimwidth($r['title'], 0, 30, '…'), $partPerTraining)) ?>,
    datasets: [{
      label: 'Participants',
      data: <?= json_encode(array_column($partPerTraining, 'cnt')) ?>,
      backgroundColor: Array.from({length: <?= count($partPerTraining) ?>}, (_, i) => `hsl(${210 + i * 18}, 70%, 55%)`),
      borderRadius: 5,
      borderSkipped: false
    }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.raw} participant${ctx.raw !== 1 ? 's' : ''}` } } },
    scales: {
      x: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } }, beginAtZero: true },
      y: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }
  }
});
<?php endif; ?>

// ── Accordion toggle ──────────────────────────────────────────────────────
function toggleSurvey(id) {
  const body  = document.getElementById(id);
  const arrow = document.getElementById(id + '-arrow');
  const open  = body.classList.toggle('open');
  if (arrow) arrow.innerHTML = open ? '&#9650;' : '&#9660;';
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
