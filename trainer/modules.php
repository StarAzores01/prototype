<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'modules';

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
            $stored = uniqid('mod_') . '.' . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . $stored);
            $pdo->prepare('INSERT INTO documents (original_name,file_name,file_type,file_size,training_id,uploaded_by) VALUES (?,?,?,?,?,?)')
                ->execute([$orig, $stored, $ext, $_FILES['file']['size'], $_POST['training_id'] ?: null, $tid]);
            setFlash('success', 'Module uploaded successfully.');
        }
        redirect(BASE_URL . '/trainer/modules.php');
    }
    if ($action === 'delete') {
        $doc = $pdo->prepare('SELECT file_name FROM documents WHERE id=? AND uploaded_by=?');
        $doc->execute([(int)$_POST['doc_id'], $tid]); $row = $doc->fetch();
        if ($row) { @unlink(UPLOAD_DIR . $row['file_name']); $pdo->prepare('DELETE FROM documents WHERE id=?')->execute([(int)$_POST['doc_id']]); setFlash('success','Module deleted.'); }
        redirect(BASE_URL . '/trainer/modules.php');
    }
}

$docs = $pdo->prepare('SELECT d.*, t.title AS training_title FROM documents d LEFT JOIN trainings t ON d.training_id=t.id WHERE d.uploaded_by=? ORDER BY d.created_at DESC');
$docs->execute([$tid]); $docs = $docs->fetchAll();

$myTrainings = $pdo->prepare('SELECT id, title FROM trainings WHERE trainer_id=? ORDER BY title');
$myTrainings->execute([$tid]); $myTrainings = $myTrainings->fetchAll();

// Storage stats
$totalSize = array_sum(array_column($docs, 'file_size'));
$pdfCount  = count(array_filter($docs, fn($d) => $d['file_type'] === 'pdf'));
$vidCount  = count(array_filter($docs, fn($d) => $d['file_type'] === 'mp4'));
$imgCount  = count(array_filter($docs, fn($d) => in_array($d['file_type'], ['jpg','jpeg','png'])));

$typeIcon = ['pdf'=>['fa-file-pdf','pdf'],'doc'=>['fa-file-word','doc'],'docx'=>['fa-file-word','doc'],
             'xls'=>['fa-file-excel','doc'],'xlsx'=>['fa-file-excel','doc'],
             'jpg'=>['fa-image','img'],'jpeg'=>['fa-image','img'],'png'=>['fa-image','img'],'mp4'=>['fa-file-video','doc']];

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Learning Modules</span></div>
    <h1>Learning Modules</h1>
    <p>Upload and manage training materials and resources</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">&#8679;Upload Module</div></div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data" id="uploadForm">
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
        <button type="submit" class="btn btn-primary" style="height:40px">&#8679; Upload</button>
        <button type="button" class="btn btn-outline" style="height:40px" onclick="document.getElementById('fileInput').click()">&#128193; Browse Files</button>
      </div>
    </form>
  </div>
</div>

<!-- Storage Overview -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon blue">&#128190;</div><div class="stat-body"><div class="stat-value" style="font-size:18px"><?= round($totalSize/1024/1024,1) ?> MB</div><div class="stat-label">Used Storage</div></div></div>
  <div class="stat-card"><div class="stat-icon red">&#128196;</div><div class="stat-body"><div class="stat-value"><?= $pdfCount ?></div><div class="stat-label">PDFs Uploaded</div></div></div>
  <div class="stat-card"><div class="stat-icon navy">&#127909;</div><div class="stat-body"><div class="stat-value"><?= $vidCount ?></div><div class="stat-label">Videos Uploaded</div></div></div>
  <div class="stat-card"><div class="stat-icon green">&#128247;</div><div class="stat-body"><div class="stat-value"><?= $imgCount ?></div><div class="stat-label">Images Uploaded</div></div></div>
</div>

<!-- All Modules -->
<div class="card">
  <div class="card-header"><div class="card-title">All Uploaded Modules</div><div class="card-subtitle"><?= count($docs) ?> files</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Date Uploaded</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($docs as $d):
        $ext = strtolower($d['file_type'] ?? '');
        [$fi,$ic] = $typeIcon[$ext] ?? ['fa-file','doc'];
      ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="upload-item-icon <?= $ic ?>" style="width:32px;height:32px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:14px"><i class="fas <?= $fi ?>"></i></div>
            <span style="font-size:13px;font-weight:600"><?= e($d['original_name']) ?></span>
          </div>
        </td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)"><?= strtoupper($ext) ?></span></td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($d['training_title'] ?? 'General') ?></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= isset($d['created_at'])?date('M d, Y',strtotime($d['created_at'])):'—' ?></td>
        <td>
          <div class="action-btns">
            <a href="<?= UPLOAD_URL.e($d['file_name']) ?>" download="<?= e($d['original_name']) ?>" class="btn btn-sm btn-outline">&#8681;</a>
            <a href="<?= UPLOAD_URL.e($d['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline">&#128065;</a>
            <button class="btn btn-sm btn-danger" onclick="if(confirm('Delete this module?')){document.getElementById('del<?= $d['id'] ?>').submit()}">&#128465;</button>
            <form id="del<?= $d['id'] ?>" method="POST" style="display:none"><input type="hidden" name="action" value="delete"/><input type="hidden" name="doc_id" value="<?= $d['id'] ?>"/></form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($docs)): ?><tr><td colspan="5" style="text-align:center;padding:40px;color:var(--gray-400)">No modules uploaded yet.</td></tr><?php endif; ?>
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
