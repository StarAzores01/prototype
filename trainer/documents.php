<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'documents';

$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload' && !empty($_FILES['file']['name'])) {
        $orig = basename($_FILES['file']['name']);
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_TYPES)) {
            setFlash('error', 'File type not allowed.');
        } elseif ($_FILES['file']['size'] > MAX_FILE_SIZE) {
            setFlash('error', 'File exceeds 20 MB limit.');
        } else {
            $stored = uniqid('doc_') . '.' . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . $stored);
            $vis = in_array($_POST['visibility'] ?? '', ['private','ec_trainer','public']) ? $_POST['visibility'] : 'public';
            $pdo->prepare('INSERT INTO documents (original_name,file_name,file_type,file_size,training_id,uploaded_by,visibility) VALUES (?,?,?,?,?,?,?)')
                ->execute([$orig, $stored, $ext, $_FILES['file']['size'], $_POST['training_id'] ?: null, $tid, $vis]);
            setFlash('success', 'Document uploaded.');
        }
        redirect(BASE_URL . '/trainer/documents.php');
    }

    if ($action === 'set_visibility') {
        $allowed = ['private','ec_trainer','public'];
        $vis = $_POST['visibility'] ?? 'public';
        if (in_array($vis, $allowed)) {
            // Only allow changing own documents
            $pdo->prepare('UPDATE documents SET visibility=? WHERE id=? AND uploaded_by=?')
                ->execute([$vis, (int)$_POST['doc_id'], $tid]);
        }
        redirect(BASE_URL . '/trainer/documents.php');
    }

    if ($action === 'delete') {
        $doc = $pdo->prepare('SELECT file_name FROM documents WHERE id=? AND uploaded_by=?');
        $doc->execute([(int)$_POST['doc_id'], $tid]); $row = $doc->fetch();
        if ($row) {
            @unlink(UPLOAD_DIR . $row['file_name']);
            $pdo->prepare('DELETE FROM documents WHERE id=?')->execute([(int)$_POST['doc_id']]);
            setFlash('success', 'Document deleted.');
        }
        redirect(BASE_URL . '/trainer/documents.php');
    }
}

// My uploaded docs
$myDocs = $pdo->prepare(
    'SELECT d.*, t.title AS training_title FROM documents d
     LEFT JOIN trainings t ON d.training_id=t.id
     WHERE d.uploaded_by=? ORDER BY d.created_at DESC'
);
$myDocs->execute([$tid]); $myDocs = $myDocs->fetchAll();

// Docs shared with trainer (public or ec_trainer, not uploaded by this trainer)
$sharedDocs = $pdo->prepare(
    "SELECT d.*, t.title AS training_title, CONCAT(u.first_name,' ',u.last_name) AS uploader_name
     FROM documents d
     LEFT JOIN trainings t ON d.training_id=t.id
     LEFT JOIN users u ON d.uploaded_by=u.id
     WHERE d.uploaded_by != ? AND d.visibility IN('public','ec_trainer')
     ORDER BY d.created_at DESC"
);
$sharedDocs->execute([$tid]); $sharedDocs = $sharedDocs->fetchAll();

$myTrainings = $pdo->prepare('SELECT id, title FROM trainings WHERE trainer_id=? ORDER BY title');
$myTrainings->execute([$tid]); $myTrainings = $myTrainings->fetchAll();

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Documents</span></div>
    <h1>Documents</h1>
    <p>Upload and manage training materials and resources</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">&#8679; Upload Document</div></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload"/>
      <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
        &#8679;
        <p style="font-size:14px;color:var(--gray-600);font-weight:600">Drag &amp; drop files here</p>
        <p style="font-size:12px;color:var(--gray-400);margin-top:4px">PDF, DOCX, XLSX, JPG, MP4 — max 20 MB</p>
        <input type="file" id="fileInput" name="file" style="display:none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4"/>
        <div id="fileChosen" style="margin-top:12px;font-size:13px;color:var(--blue-primary);font-weight:600"></div>
      </div>
      <div style="display:flex;gap:12px;margin-top:16px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="flex:1;min-width:200px;margin:0">
          <label class="form-label">Link to Training (optional)</label>
          <select name="training_id" class="form-control">
            <option value="">— General —</option>
            <?php foreach ($myTrainings as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="min-width:180px;margin:0">
          <label class="form-label">Visibility</label>
          <select name="visibility" class="form-control">
            <option value="public">Public (All users)</option>
            <option value="ec_trainer">EC &amp; Project Leaders only</option>
            <option value="private">Private (me only)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:40px">&#8679; Upload</button>
      </div>
    </form>
  </div>
</div>

<!-- My Documents -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">My Documents</div><div class="card-subtitle"><?= count($myDocs) ?> files</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Visibility</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($myDocs as $d):
        $ext = strtolower($d['file_type'] ?? '');
        $vis = $d['visibility'] ?? 'public';
        $visColor = ['private'=>'var(--red)','ec_trainer'=>'var(--blue-primary)','public'=>'var(--green)'];
      ?>
      <tr>
        <td><span style="font-size:13px;font-weight:600"><?= e($d['original_name']) ?></span></td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)"><?= strtoupper($ext) ?></span></td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($d['training_title'] ?? 'General') ?></td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="set_visibility"/>
            <input type="hidden" name="doc_id" value="<?= $d['id'] ?>"/>
            <select name="visibility" class="filter-select" style="font-size:12px;padding:4px 8px;border-radius:6px;color:<?= $visColor[$vis] ?>;font-weight:600;border-color:<?= $visColor[$vis] ?>" onchange="this.form.submit()">
              <option value="private"    <?= $vis==='private'   ?'selected':'' ?>>Private</option>
              <option value="ec_trainer" <?= $vis==='ec_trainer'?'selected':'' ?>>EC &amp; Trainers</option>
              <option value="public"     <?= $vis==='public'    ?'selected':'' ?>>Public</option>
            </select>
          </form>
        </td>
        <td style="font-size:12px;color:var(--gray-400)"><?= isset($d['created_at'])?date('M d, Y',strtotime($d['created_at'])):'—' ?></td>
        <td>
          <div class="action-btns">
            <a href="<?= UPLOAD_URL.e($d['file_name']) ?>" download="<?= e($d['original_name']) ?>" class="btn btn-sm btn-outline">&#8681;</a>
            <a href="<?= UPLOAD_URL.e($d['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline">&#128065;</a>
            <button class="btn btn-sm btn-danger" onclick="if(confirm('Delete this document?')){document.getElementById('del<?= $d['id'] ?>').submit()}">&#128465;</button>
            <form id="del<?= $d['id'] ?>" method="POST" style="display:none"><input type="hidden" name="action" value="delete"/><input type="hidden" name="doc_id" value="<?= $d['id'] ?>"/></form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($myDocs)): ?><tr><td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400)">No documents uploaded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Shared Documents -->
<div class="card">
  <div class="card-header"><div class="card-title">Shared Documents</div><div class="card-subtitle">Documents shared by the EC or other trainers</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Shared By</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($sharedDocs as $d):
        $ext = strtolower($d['file_type'] ?? '');
      ?>
      <tr>
        <td><span style="font-size:13px;font-weight:600"><?= e($d['original_name']) ?></span></td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)"><?= strtoupper($ext) ?></span></td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($d['training_title'] ?? 'General') ?></td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($d['uploader_name'] ?? '—') ?></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= isset($d['created_at'])?date('M d, Y',strtotime($d['created_at'])):'—' ?></td>
        <td>
          <div class="action-btns">
            <a href="<?= UPLOAD_URL.e($d['file_name']) ?>" download="<?= e($d['original_name']) ?>" class="btn btn-sm btn-outline">&#8681;</a>
            <a href="<?= UPLOAD_URL.e($d['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline">&#128065;</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($sharedDocs)): ?><tr><td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400)">No shared documents yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const dz=document.getElementById('dropZone'),fi=document.getElementById('fileInput'),fc=document.getElementById('fileChosen');
fi.addEventListener('change',()=>{fc.textContent=fi.files[0]?.name||'';});
dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('dragover');});
dz.addEventListener('dragleave',()=>dz.classList.remove('dragover'));
dz.addEventListener('drop',e=>{e.preventDefault();dz.classList.remove('dragover');fi.files=e.dataTransfer.files;fc.textContent=fi.files[0]?.name||'';});
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
