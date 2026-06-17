<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'skills';

// ── All skills forms with response counts ─────────────────────────────────
try {
    $forms = $pdo->query(
        'SELECT sf.id, sf.title, sf.sent_at, sf.fields,
                t.id AS training_id, t.title AS training_title, t.date_start,
                CONCAT(u.first_name," ",u.last_name) AS trainer_name,
                COUNT(DISTINCT p.id)  AS total_pax,
                COUNT(DISTINCT sr.id) AS responded
         FROM skills_forms sf
         JOIN trainings t  ON t.id  = sf.training_id
         LEFT JOIN users u ON u.id  = t.trainer_id
         LEFT JOIN participants p   ON p.training_id = t.id
         LEFT JOIN skills_responses sr ON sr.form_id = sf.id
         GROUP BY sf.id
         ORDER BY sf.sent_at DESC'
    )->fetchAll();
} catch (\Throwable $e) { $forms = []; }

// ── View a specific form's responses ──────────────────────────────────────
$viewId   = (int)($_GET['form'] ?? 0);
$viewForm = null;
$responses = [];
if ($viewId) {
    foreach ($forms as $f) {
        if ($f['id'] == $viewId) { $viewForm = $f; break; }
    }
    if ($viewForm) {
        try {
            $rs = $pdo->prepare(
                'SELECT sr.responses, sr.submitted_at,
                        b.first_name, b.last_name
                 FROM skills_responses sr
                 JOIN beneficiaries b ON b.id = sr.beneficiary_id
                 WHERE sr.form_id = ?
                 ORDER BY sr.submitted_at DESC'
            );
            $rs->execute([$viewId]);
            $responses = $rs->fetchAll();
        } catch (\Throwable $e) { $responses = []; }
    }
}

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Skills Utilization</span></div>
    <h1>Skills Utilization</h1>
    <p>View beneficiary responses to skills surveys across all trainings</p>
  </div>
  <?php if ($viewForm): ?>
  <a href="<?= BASE_URL ?>/ec/skills.php" class="btn btn-outline">&#8592; Back to All Forms</a>
  <?php endif; ?>
</div>

<?php if ($viewForm): ?>
<!-- ═══════════════ RESPONSES VIEW ═══════════════ -->
<?php
$fields = json_decode($viewForm['fields'], true) ?? [];
$rate   = $viewForm['total_pax'] > 0 ? round($viewForm['responded'] / $viewForm['total_pax'] * 100) : 0;
?>

<!-- Summary bar -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px">
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--blue-primary)"><?= (int)$viewForm['total_pax'] ?></div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Total Participants</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--green)"><?= (int)$viewForm['responded'] ?></div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Responded</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--navy)"><?= $rate ?>%</div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Response Rate</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--gray-600)"><?= count($fields) ?></div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Questions</div>
  </div>
</div>

<!-- Form info -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><?= e($viewForm['title']) ?></div>
      <div class="card-subtitle">
        &#128218; <?= e($viewForm['training_title']) ?>
        &nbsp;·&nbsp; &#128100; <?= e($viewForm['trainer_name'] ?? 'N/A') ?>
        <?php if ($viewForm['sent_at']): ?>
        &nbsp;·&nbsp; &#128276; Sent <?= date('M d, Y', strtotime($viewForm['sent_at'])) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if (empty($responses)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128200;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No responses yet</div>
    <p style="font-size:13px;color:var(--gray-400)">Beneficiaries haven't submitted this survey yet.</p>
  </div>
</div>
<?php else: ?>

<!-- Per-question summary -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">&#128202; Response Summary by Question</div></div>
  <div class="card-body">
    <?php foreach ($fields as $fi => $field):
      // Tally answers for radio/select
      $tally = [];
      foreach ($responses as $r) {
          $ans = json_decode($r['responses'], true) ?? [];
          $val = trim($ans[$fi] ?? '');
          if ($val !== '') $tally[$val] = ($tally[$val] ?? 0) + 1;
      }
      arsort($tally);
    ?>
    <div style="margin-bottom:28px;padding-bottom:24px;border-bottom:1px solid var(--gray-100)">
      <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:10px">
        <?= $fi+1 ?>. <?= e($field['label']) ?>
        <span style="font-size:11px;font-weight:400;color:var(--gray-400);margin-left:6px">(<?= ucfirst($field['type']) ?>)</span>
      </div>

      <?php if (in_array($field['type'], ['radio','select']) && !empty($tally)): ?>
        <div style="display:flex;flex-direction:column;gap:6px">
          <?php foreach ($tally as $opt => $cnt):
            $pct = count($responses) > 0 ? round($cnt / count($responses) * 100) : 0;
          ?>
          <div style="display:flex;justify-content:space-between;align-items:center;background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:8px 12px">
            <span style="font-size:13px;color:var(--gray-700)"><?= e($opt) ?></span>
            <span style="font-size:12px;font-weight:700;color:var(--blue-primary)"><?= $cnt ?> response<?= $cnt !== 1 ? 's' : '' ?> (<?= $pct ?>%)</span>
          </div>
          <?php endforeach; ?>

      <?php elseif (!empty($tally)): ?>
        <!-- text/textarea: list unique answers -->
        <div style="display:flex;flex-direction:column;gap:6px">
          <?php foreach (array_keys($tally) as $ans): ?>
          <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:8px 12px;font-size:13px;color:var(--gray-700)">
            <?= e($ans) ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="font-size:13px;color:var(--gray-400)">No answers yet.</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Individual responses table -->
<div class="card">
  <div class="card-header"><div class="card-title">&#128101; Individual Responses</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Beneficiary</th>
          <th>Submitted</th>
          <?php foreach ($fields as $fi => $field): ?>
          <th style="min-width:160px"><?= e(mb_strimwidth($field['label'], 0, 40, '…')) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($responses as $i => $r):
          $ans = json_decode($r['responses'], true) ?? [];
        ?>
        <tr>
          <td style="color:var(--gray-400);font-size:12px"><?= $i+1 ?></td>
          <td><strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong></td>
          <td style="font-size:12px;color:var(--gray-400)"><?= date('M d, Y g:i A', strtotime($r['submitted_at'])) ?></td>
          <?php foreach ($fields as $fi => $field): ?>
          <td style="font-size:13px;color:var(--gray-700)"><?= e($ans[$fi] ?? '—') ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<?php else: ?>
<!-- ═══════════════ FORMS LIST ═══════════════ -->

<?php if (empty($forms)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128200;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No skills surveys found</div>
    <p style="font-size:13px;color:var(--gray-400)">Trainers create and send skills surveys to beneficiaries. They will appear here once created.</p>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-header">
    <div class="card-title">All Skills Surveys</div>
    <div class="card-subtitle"><?= count($forms) ?> form<?= count($forms) !== 1 ? 's' : '' ?> found</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Form Title</th>
          <th>Training</th>
          <th>Project Leader</th>
          <th>Sent</th>
          <th>Participants</th>
          <th>Responded</th>
          <th>Response Rate</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($forms as $f):
          $rate = $f['total_pax'] > 0 ? round($f['responded'] / $f['total_pax'] * 100) : 0;
        ?>
        <tr>
          <td><strong><?= e($f['title']) ?></strong></td>
          <td style="font-size:12px;color:var(--gray-700)"><?= e($f['training_title']) ?></td>
          <td style="font-size:12px;color:var(--gray-400)"><?= e($f['trainer_name'] ?? 'N/A') ?></td>
          <td style="font-size:12px;color:var(--gray-400)">
            <?= $f['sent_at'] ? date('M d, Y', strtotime($f['sent_at'])) : '<span style="color:var(--gray-300)">Not sent</span>' ?>
          </td>
          <td><strong><?= (int)$f['total_pax'] ?></strong></td>
          <td style="color:var(--green);font-weight:700"><?= (int)$f['responded'] ?></td>
          <td>
            <span style="font-size:13px;font-weight:700;color:var(--gray-700)"><?= $rate ?>%</span>
          </td>
          <td>
            <a href="?form=<?= $f['id'] ?>" class="btn btn-sm btn-outline">&#128065; View Responses</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
