<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'impact_assessment';

$uid   = (int)$_SESSION['user_id'];
$flash = null;

// ── POST actions ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'submit') {
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $training_id = (int)($_POST['training_id'] ?? 0) ?: null;

        if (empty($title)) { setFlash('error','Title is required.'); redirect(BASE_URL.'/evaluator/impact_assessment.php'); }

        $fileName     = null;
        $originalName = null;
        $fileType     = null;
        $fileSize     = 0;

        if (!empty($_FILES['assessment_file']['name'])) {
            $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
            $ext     = strtolower(pathinfo($_FILES['assessment_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) { setFlash('error','Only PDF, DOC, DOCX, JPG, PNG files are allowed.'); redirect(BASE_URL.'/evaluator/impact_assessment.php'); }
            if ($_FILES['assessment_file']['size'] > 10 * 1024 * 1024) { setFlash('error','File must be under 10 MB.'); redirect(BASE_URL.'/evaluator/impact_assessment.php'); }

            $fileName     = 'ia_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $originalName = basename($_FILES['assessment_file']['name']);
            $fileType     = $ext;
            $fileSize     = (int)$_FILES['assessment_file']['size'];
            move_uploaded_file($_FILES['assessment_file']['tmp_name'], __DIR__ . '/../uploads/' . $fileName);
        }

        $pdo->prepare('INSERT INTO impact_assessments (evaluator_id,training_id,title,description,file_name,original_name,file_type,file_size,status,submitted_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())')
            ->execute([$uid, $training_id, $title, $description, $fileName, $originalName, $fileType, $fileSize, 'Submitted']);

        setFlash('success','Impact assessment submitted successfully.');
        redirect(BASE_URL.'/evaluator/impact_assessment.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['assessment_id'];
        $row = $pdo->prepare('SELECT * FROM impact_assessments WHERE id=? AND evaluator_id=?');
        $row->execute([$id, $uid]); $row = $row->fetch();
        if ($row) {
            if ($row['file_name'] && file_exists(__DIR__.'/../uploads/'.$row['file_name'])) {
                unlink(__DIR__.'/../uploads/'.$row['file_name']);
            }
            $pdo->prepare('DELETE FROM impact_assessments WHERE id=? AND evaluator_id=?')->execute([$id, $uid]);
            setFlash('success','Assessment deleted.');
        }
        redirect(BASE_URL.'/evaluator/impact_assessment.php');
    }
}

// ── Fetch data ────────────────────────────────────────────────────────────
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT ia.*, t.title AS training_title FROM impact_assessments ia LEFT JOIN trainings t ON ia.training_id=t.id WHERE ia.evaluator_id=?';
$params = [$uid];
if ($q) { $sql .= ' AND ia.title LIKE ?'; $params[] = "%$q%"; }
$sql .= ' ORDER BY ia.submitted_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$assessments = $stmt->fetchAll();

$trainings = $pdo->query('SELECT id, title FROM trainings WHERE status="Completed" ORDER BY title')->fetchAll();
$flash = getFlash();

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Impact Assessment</span></div>
    <h1>Impact Assessment</h1>
    <p>Submit assessment forms to the Extension Coordinator</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('submitAssessment')">&#43; Submit Assessment</button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?>" style="margin-bottom:20px">
  <?= $flash['type']==='success'?'&#9989;':'&#9888;' ?> <?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Search -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:10px;align-items:center">
      <input type="text" name="q" class="form-control" placeholder="Search assessments..." value="<?= e($q) ?>" style="max-width:320px"/>
      <button type="submit" class="btn btn-outline btn-sm">&#128269; Search</button>
      <?php if ($q): ?><a href="?" class="btn btn-outline btn-sm">&#10005; Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($assessments)): ?>
    <div style="padding:40px;text-align:center;color:var(--gray-400)">
      No assessments yet. Click <strong>Submit Assessment</strong> to get started.
    </div>
    <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Training</th>
          <th>File</th>
          <th>Status</th>
          <th>EC Notes</th>
          <th>Submitted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assessments as $a): ?>
        <tr>
          <td style="font-weight:600"><?= e($a['title']) ?></td>
          <td><?= e($a['training_title'] ?? '—') ?></td>
          <td>
            <?php if ($a['file_name']): ?>
            <a href="<?= BASE_URL ?>/uploads/<?= e($a['file_name']) ?>" target="_blank" class="btn btn-outline btn-sm">&#128196; <?= e($a['original_name'] ?? 'View') ?></a>
            <?php else: ?>
            <span style="color:var(--gray-400)">No file</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $a['status']==='Reviewed'?'badge-success':($a['status']==='Submitted'?'badge-info':'badge-warning') ?>"><?= e($a['status']) ?></span></td>
          <td style="font-size:12px;color:var(--gray-600)"><?= $a['ec_notes'] ? e($a['ec_notes']) : '<span style="color:var(--gray-300)">—</span>' ?></td>
          <td><?= $a['submitted_at'] ? date('M d, Y', strtotime($a['submitted_at'])) : '—' ?></td>
          <td>
            <?php if ($a['status'] !== 'Reviewed'): ?>
            <form method="POST" onsubmit="return confirm('Delete this assessment?')" style="display:inline">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="assessment_id" value="<?= $a['id'] ?>"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465; Delete</button>
            </form>
            <?php else: ?>
            <span style="font-size:12px;color:var(--gray-400)">Reviewed</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Submit Modal -->
<div class="modal-overlay" id="modal-submitAssessment">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <div class="modal-title">&#128203; Submit Impact Assessment</div>
      <button class="modal-close" onclick="closeModal('submitAssessment')">&#10005;</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="submit"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title <span style="color:var(--red)">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Post-Training Impact Assessment – Q1 2026" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Related Training (optional)</label>
          <select name="training_id" class="form-control">
            <option value="">— Select a completed training —</option>
            <?php foreach ($trainings as $t): ?>
            <option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description / Notes</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the assessment..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Upload Form / Document (PDF, DOC, DOCX, JPG, PNG – max 10 MB)</label>
          <input type="file" name="assessment_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('submitAssessment')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#128203; Submit Assessment</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
