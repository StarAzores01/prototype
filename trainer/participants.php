<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'participants';

$tid = $_SESSION['user_id'];

$q       = trim($_GET['q'] ?? '');
$filterT = (int)($_GET['training'] ?? 0);

$sql = 'SELECT p.*, t.title AS training_title,
               (SELECT ev.status FROM evaluations ev WHERE ev.participant_id=p.id LIMIT 1) AS eval_status
        FROM participants p JOIN trainings t ON p.training_id=t.id
        WHERE t.trainer_id=?';
$params = [$tid];
if ($q)       { $sql .= ' AND (p.full_name LIKE ? OR p.id_number LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($filterT) { $sql .= ' AND p.training_id=?'; $params[] = $filterT; }
$sql .= ' ORDER BY p.full_name';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$participants = $stmt->fetchAll();

$myTrainings = $pdo->prepare('SELECT id, title FROM trainings WHERE trainer_id=? ORDER BY title');
$myTrainings->execute([$tid]); $myTrainings = $myTrainings->fetchAll();

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Participants</span></div>
    <h1>Participants</h1>
    <p>Trainees enrolled in your training programs</p>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Participants</div><div class="card-subtitle"><?= count($participants) ?> records</div></div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-row">
      <div class="search-box">&#128269;<input type="text" name="q" value="<?= e($q) ?>" placeholder="Search participant…"/></div>
      <select name="training" class="filter-select" onchange="this.form.submit()">
        <option value="">All Trainings</option>
        <?php foreach ($myTrainings as $t): ?>
        <option value="<?= $t['id'] ?>" <?= $filterT==$t['id']?'selected':'' ?>><?= e($t['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
      <?php if ($q||$filterT): ?><a href="<?= BASE_URL ?>/trainer/participants.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Participant Name</th><th>User ID</th><th>Phone</th><th>Age / Sex</th><th>Training Enrolled</th><th>Evaluation</th></tr></thead>
      <tbody>
      <?php foreach ($participants as $i => $p):
        $es = $p['eval_status'] ?? 'Pending';
        $eb = $es === 'Submitted' ? 'badge-submitted' : 'badge-pending';
      ?>
      <tr>
        <td style="color:var(--gray-400)"><?= $i+1 ?></td>
        <td>
          <strong><?= e($p['full_name']) ?></strong>
          <?php if ($p['address']??''): ?><div style="font-size:11px;color:var(--gray-400)">&#128205; <?= e($p['address']) ?></div><?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--gray-500)"><?= e($p['id_number']??'—') ?></td>
        <td style="font-size:12px;color:var(--gray-500)"><?= e($p['phone']??'—') ?></td>
        <td style="font-size:12px;color:var(--gray-500)"><?= ($p['age']??'')?$p['age'].' yrs':'—' ?><?= (($p['age']??'')&&($p['sex']??''))?' · ':'' ?><?= e($p['sex']??'') ?></td>
        <td style="font-size:12px"><?= e($p['training_title']??'—') ?></td>
        <td><span class="badge <?= $eb ?>"><?= $es ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($participants)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">No participants found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
