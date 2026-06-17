<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'attendance';

$tid = $_SESSION['user_id'];

$pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  training_id    INT NOT NULL,
  participant_id INT NOT NULL,
  session_date   DATE NOT NULL,
  status         ENUM('Present','Absent','Late') NOT NULL DEFAULT 'Absent',
  time_in        TIME NULL,
  time_out       TIME NULL,
  recorded_by    INT NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_att (training_id, participant_id, session_date),
  FOREIGN KEY (training_id)    REFERENCES trainings(id)    ON DELETE CASCADE,
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
) ENGINE=InnoDB");

// ── Add a new day: insert Absent rows for all participants so the day persists ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_day') {
    $trainingId = (int)$_POST['training_id'];
    $newDate    = $_POST['new_date'] ?? '';

    $chk = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
    $chk->execute([$trainingId, $tid]);
    if ($chk->fetch() && $newDate) {
        // Check not duplicate
        $dup = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE training_id=? AND session_date=?');
        $dup->execute([$trainingId, $newDate]);
        if ((int)$dup->fetchColumn() === 0) {
            $pids = $pdo->prepare('SELECT id FROM participants WHERE training_id=?');
            $pids->execute([$trainingId]);
            foreach ($pids->fetchAll(PDO::FETCH_COLUMN) as $pid) {
                $pdo->prepare('INSERT IGNORE INTO attendance (training_id,participant_id,session_date,status,recorded_by) VALUES (?,?,?,?,?)')
                    ->execute([$trainingId, $pid, $newDate, 'Absent', $tid]);
            }
            setFlash('success', 'Day added.');
        } else {
            setFlash('error', 'That date already exists.');
        }
    }
    redirect(BASE_URL . '/trainer/attendance.php?training=' . $trainingId);
}

// ── Save attendance ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $trainingId = (int)$_POST['training_id'];
    $days       = $_POST['days']    ?? [];
    $checked    = $_POST['present'] ?? [];

    $chk = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
    $chk->execute([$trainingId, $tid]);
    if ($chk->fetch()) {
        $pids = $pdo->prepare('SELECT id FROM participants WHERE training_id=?');
        $pids->execute([$trainingId]);
        $allPids = $pids->fetchAll(PDO::FETCH_COLUMN);

        foreach ($days as $di => $sessionDate) {
            if (!$sessionDate) continue;
            foreach ($allPids as $pid) {
                $status = isset($checked[$di][$pid]) ? 'Present' : 'Absent';
                $pdo->prepare('INSERT INTO attendance (training_id,participant_id,session_date,status,recorded_by)
                               VALUES (?,?,?,?,?)
                               ON DUPLICATE KEY UPDATE status=VALUES(status),recorded_by=VALUES(recorded_by)')
                    ->execute([$trainingId, $pid, $sessionDate, $status, $tid]);
            }
        }
        setFlash('success', 'Attendance saved.');
    }
    redirect(BASE_URL . '/trainer/attendance.php?training=' . $trainingId);
}

// ── Load data ─────────────────────────────────────────────────────────────
$myTrainings = $pdo->prepare('SELECT id, title FROM trainings WHERE trainer_id=? ORDER BY date_start DESC');
$myTrainings->execute([$tid]);
$myTrainings = $myTrainings->fetchAll();

$selectedTraining = (int)($_GET['training'] ?? ($myTrainings[0]['id'] ?? 0));

$trainingInfo = null;
$participants = [];
$days         = [];
$attGrid      = [];

if ($selectedTraining) {
    $ts = $pdo->prepare('SELECT * FROM trainings WHERE id=? AND trainer_id=?');
    $ts->execute([$selectedTraining, $tid]);
    $trainingInfo = $ts->fetch();

    if ($trainingInfo) {
        $ps = $pdo->prepare('SELECT * FROM participants WHERE training_id=? ORDER BY full_name');
        $ps->execute([$selectedTraining]);
        $participants = $ps->fetchAll();

        $ds = $pdo->prepare('SELECT DISTINCT session_date FROM attendance WHERE training_id=? ORDER BY session_date ASC');
        $ds->execute([$selectedTraining]);
        $days = $ds->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($days)) {
            $as = $pdo->prepare('SELECT participant_id, session_date, status FROM attendance WHERE training_id=?');
            $as->execute([$selectedTraining]);
            foreach ($as->fetchAll() as $a) {
                $attGrid[$a['session_date']][$a['participant_id']] = $a['status'];
            }
        }
    }
}

require __DIR__ . '/layout.php';
?>

<style>
@media print {
  @page { size: landscape; margin: 10mm; }

  * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

  .no-print { display:none !important; }
  .sidebar, .topbar, .main-wrap > *:not(.page-content) { display:none !important; }
  .main-wrap { margin-left:0 !important; }
  .page-content { padding:0 !important; }

  /* strip card chrome */
  .card { box-shadow:none !important; border:none !important; margin:0 !important; padding:0 !important; }
  .card-header { display:none !important; }

  /* show print-only header */
  .print-header-block { display:block !important; }

  /* table fills the page */
  .att-wrap { overflow:visible !important; }
  .att-grid {
    width: 100% !important;
    font-size: 8pt !important;
    border-collapse: collapse !important;
    table-layout: fixed !important;
  }
  .att-grid th {
    background: #09182F !important;
    color: #fff !important;
    padding: 4pt 5pt !important;
    font-size: 7pt !important;
    white-space: nowrap;
    border: 1px solid #334155 !important;
  }
  .att-grid td {
    padding: 4pt 5pt !important;
    border: 1px solid #CBD5E1 !important;
    font-size: 8pt !important;
    vertical-align: middle !important;
  }
  .att-grid th.name-col,
  .att-grid td.name-col { width: 28% !important; text-align: left !important; }
  .att-grid th:first-child,
  .att-grid td:first-child { width: 22pt !important; text-align: center !important; }

  /* hide interactive elements, show print symbols */
  .att-grid input[type=checkbox] { display:none !important; }
  .att-grid .print-status { display:inline !important; font-size:10pt !important; }
  .att-grid .id-number-cell { display:none !important; }
  .day-present-count { display:none !important; }

  /* alternate row shading */
  .att-grid tbody tr:nth-child(even) td { background: #F8FAFF !important; }
}

/* ── Screen styles (unchanged) ── */
.att-wrap { overflow-x: auto; }
.att-grid { border-collapse: collapse; min-width: 100%; font-size: 13px; }
.att-grid th {
  background: var(--navy); color: #fff;
  padding: 10px 16px; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .5px;
  white-space: nowrap; text-align: center;
}
.att-grid th.name-col { text-align: left; min-width: 200px; }
.att-grid td {
  padding: 10px 16px; border-bottom: 1px solid var(--gray-100);
  text-align: center; vertical-align: middle;
}
.att-grid td.name-col { text-align: left; }
.att-grid tbody tr:hover td { background: var(--blue-xsoft); }
.att-grid tbody tr:nth-child(even) td { background: #FAFBFF; }
.att-grid tbody tr:nth-child(even):hover td { background: var(--blue-xsoft); }
.day-check { width: 20px; height: 20px; accent-color: var(--blue-primary); cursor: pointer; }
.day-num { display: block; font-size: 12px; font-weight: 700; }
.day-date-sub { display: block; font-size: 10px; color: rgba(255,255,255,.5); margin-top: 1px; }
.day-present-count { display: block; font-size: 10px; color: #6EE7B7; margin-top: 3px; font-weight: 600; }
</style>

<div class="page-header no-print">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Attendance</span></div>
    <h1>Attendance</h1>
    <p>Check the box if the participant attended that day</p>
  </div>
</div>

<!-- Training selector -->
<div class="card no-print" style="margin-bottom:20px">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="flex:2;min-width:220px;margin:0">
        <label class="form-label">Training</label>
        <select name="training" class="form-control" onchange="this.form.submit()">
          <option value="">— Select Training —</option>
          <?php foreach ($myTrainings as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $selectedTraining == $t['id'] ? 'selected' : '' ?>>
            <?= e($t['title']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if (!$selectedTraining || !$trainingInfo): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128203;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">Select a training to begin</div>
    <p style="font-size:13px;color:var(--gray-400)">Choose a training from the dropdown above.</p>
  </div>
</div>

<?php elseif (empty($participants)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128101;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No participants enrolled</div>
    <p style="font-size:13px;color:var(--gray-400)">Add participants to this training first.</p>
  </div>
</div>

<?php else: ?>

<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title"><?= e($trainingInfo['title']) ?></div>
      <div class="card-subtitle">
        <?= count($participants) ?> participant<?= count($participants) !== 1 ? 's' : '' ?>
        &nbsp;·&nbsp; <?= count($days) ?> day<?= count($days) !== 1 ? 's' : '' ?>
      </div>
    </div>
    <?php if (!empty($days) && !empty($participants)): ?>
    <div style="display:flex;gap:8px" class="no-print">
      <button type="button" class="btn btn-outline btn-sm" onclick="exportCSV()">&#128190; Export CSV</button>
      <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">&#128438; Print / Export</button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Print-only report header -->
  <div style="display:none" class="print-header-block">
    <div style="text-align:center;padding:8px 0 10px">
      <div style="font-size:8pt;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748B;margin-bottom:2px">PAThrive · CIT-SLSU Extension Program</div>
      <div style="font-size:14pt;font-weight:800;color:#09182F;margin-bottom:2px">Attendance Sheet</div>
      <div style="font-size:11pt;font-weight:600;color:#1A56DB;margin-bottom:4px"><?= e($trainingInfo['title']) ?></div>
      <div style="font-size:8pt;color:#64748B">
        <?= e($trainingInfo['area'] ?? '') ?>
        &nbsp;·&nbsp; <?= e($trainingInfo['date_start'] ?? '—') ?>
        &nbsp;·&nbsp; <?= count($participants) ?> participant<?= count($participants) !== 1 ? 's' : '' ?>
        &nbsp;·&nbsp; <?= count($days) ?> day<?= count($days) !== 1 ? 's' : '' ?>
        &nbsp;·&nbsp; Generated: <?= date('F d, Y') ?>
      </div>
    </div>
  </div>

  <?php if (empty($days)): ?>
  <div class="card-body" style="text-align:center;padding:48px 24px;color:var(--gray-400)">
    &#128197; No days yet — add Day 1 below.
  </div>
  <?php else: ?>

  <form method="POST" id="attForm">
    <input type="hidden" name="action" value="save"/>
    <input type="hidden" name="training_id" value="<?= $selectedTraining ?>"/>
    <?php foreach ($days as $di => $day): ?>
    <input type="hidden" name="days[<?= $di ?>]" value="<?= e($day) ?>"/>
    <?php endforeach; ?>

    <div class="att-wrap">
      <table class="att-grid">
        <thead>
          <tr>
            <th class="name-col" style="width:32px;min-width:32px">#</th>
            <th class="name-col">Participant</th>
            <?php foreach ($days as $di => $day):
              $cnt = 0;
              foreach ($participants as $p) {
                  if (($attGrid[$day][$p['id']] ?? '') === 'Present') $cnt++;
              }
            ?>
            <th style="min-width:80px">
              <span class="day-num">Day <?= $di + 1 ?></span>
              <span class="day-date-sub"><?= date('M d', strtotime($day)) ?></span>
              <span class="day-present-count" id="cnt-<?= $di ?>"><?= $cnt ?>/<?= count($participants) ?></span>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($participants as $i => $p): ?>
          <tr>
            <td class="name-col" style="color:var(--gray-400);font-size:12px;width:32px"><?= $i + 1 ?></td>
            <td class="name-col">
              <strong><?= e($p['full_name']) ?></strong>
              <?php if ($p['id_number']): ?>
              <div class="id-number-cell" style="font-size:11px;color:var(--gray-400)"><?= e($p['id_number']) ?></div>
              <?php endif; ?>
            </td>
            <?php foreach ($days as $di => $day):
              $present = ($attGrid[$day][$p['id']] ?? '') === 'Present';
            ?>
            <td>
              <input type="checkbox"
                     class="day-check"
                     name="present[<?= $di ?>][<?= $p['id'] ?>]"
                     value="1"
                     <?= $present ? 'checked' : '' ?>
                     onchange="updateCount(<?= $di ?>)"/>
              <span class="print-status" style="display:none;font-weight:700;font-size:14px;color:<?= $present ? '#059669' : '#CBD5E1' ?>"><?= $present ? '✔' : '—' ?></span>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);display:flex;justify-content:flex-end" class="no-print">
      <button type="submit" class="btn btn-primary">&#10003; Save Attendance</button>
    </div>
  </form>

  <?php endif; ?>

  <!-- Add Day -->
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);background:var(--gray-50);display:flex;align-items:center;gap:12px;flex-wrap:wrap" class="no-print">
    <form method="POST" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap" id="addDayForm">
      <input type="hidden" name="action" value="add_day"/>
      <input type="hidden" name="training_id" value="<?= $selectedTraining ?>"/>
      <span style="font-size:13px;font-weight:600;color:var(--navy)">Add Day <?= count($days) + 1 ?>:</span>
      <input type="date" name="new_date" class="form-control" style="width:170px" value="<?= date('Y-m-d') ?>" required/>
      <button type="submit" class="btn btn-outline">&#43; Add Day</button>
    </form>
  </div>
</div>

<script>
function updateCount(di) {
  const boxes   = document.querySelectorAll(`input.day-check[name^="present[${di}]"]`);
  const present = [...boxes].filter(b => b.checked).length;
  const el = document.getElementById('cnt-' + di);
  if (el) el.textContent = present + '/' + boxes.length;
}

function exportCSV() {
  const table = document.querySelector('.att-grid');
  if (!table) return;
  const rows = [];
  const ths = table.querySelectorAll('thead th');
  const header = [];
  ths.forEach((th, i) => {
    if (i === 0) { header.push('Participant'); header.push('ID Number'); }
    else {
      const dayNum = th.querySelector('.day-num')?.textContent?.trim() ?? '';
      const daySub = th.querySelector('.day-date-sub')?.textContent?.trim() ?? '';
      header.push(`${dayNum} (${daySub})`);
    }
  });
  rows.push(header);
  table.querySelectorAll('tbody tr').forEach(tr => {
    const tds = tr.querySelectorAll('td');
    const row = [];
    tds.forEach((td, i) => {
      if (i === 0) {
        row.push(csvEsc(td.querySelector('strong')?.textContent?.trim() ?? ''));
        row.push(csvEsc(td.querySelector('div')?.textContent?.trim() ?? ''));
      } else {
        const cb = td.querySelector('input[type=checkbox]');
        row.push(cb ? (cb.checked ? 'Present' : 'Absent') : '');
      }
    });
    rows.push(row);
  });
  const csv = rows.map(r => r.join(',')).join('\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
  a.download = <?= json_encode(preg_replace('/[^a-z0-9]+/i', '_', $trainingInfo['title'] ?? 'attendance') . '_attendance.csv') ?>;
  a.click();
}
function csvEsc(v) { v = String(v).replace(/"/g,'""'); return /[,"\n]/.test(v)?`"${v}"`:v; }
</script>

<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
