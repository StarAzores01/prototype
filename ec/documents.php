<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'documents';

// ── Upload ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    if (!empty($_FILES['file']['name'])) {
        $orig = basename($_FILES['file']['name']);
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_TYPES)) {
            setFlash('error', 'File type not allowed.');
        } elseif ($_FILES['file']['size'] > MAX_FILE_SIZE) {
            setFlash('error', 'File exceeds 20 MB limit.');
        } else {
            $stored = uniqid('doc_') . '.' . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . $stored);
            $visibility = in_array($_POST['visibility'] ?? '', ['private','ec_trainer','public']) ? $_POST['visibility'] : 'public';
            $stmt = $pdo->prepare('INSERT INTO documents (original_name,file_name,file_type,file_size,training_id,uploaded_by,visibility) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$orig, $stored, $ext, $_FILES['file']['size'], $_POST['training_id'] ?: null, $_SESSION['user_id'], $visibility]);
            setFlash('success', 'File uploaded successfully.');
        }
    }
    redirect(BASE_URL . '/ec/documents.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_visibility') {
    $allowed = ['private','ec_trainer','public'];
    $vis = $_POST['visibility'] ?? 'public';
    if (in_array($vis, $allowed)) {
        $pdo->prepare('UPDATE documents SET visibility=? WHERE id=?')->execute([$vis, (int)$_POST['doc_id']]);
    }
    redirect(BASE_URL . '/ec/documents.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $doc = $pdo->prepare('SELECT stored_name FROM documents WHERE id = ?');
    $doc->execute([(int)$_POST['doc_id']]);
    $row = $doc->fetch();
    if ($row) {
        @unlink(UPLOAD_DIR . $row['file_name']);
        $pdo->prepare('DELETE FROM documents WHERE id = ?')->execute([(int)$_POST['doc_id']]);
        setFlash('success', 'Document deleted.');
    }
    redirect(BASE_URL . '/ec/documents.php');
}

// ── List ───────────────────────────────────────────────────────────────────
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT d.*, t.title AS training_title, CONCAT(u.first_name," ",u.last_name) AS uploader
        FROM documents d LEFT JOIN trainings t ON d.training_id = t.id LEFT JOIN users u ON d.uploaded_by = u.id WHERE 1=1';
$params = [];
if ($q) { $sql .= ' AND (d.original_name LIKE ? OR t.title LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= ' ORDER BY d.created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$docs = $stmt->fetchAll();

$trainings = $pdo->query('SELECT id, title FROM trainings ORDER BY title')->fetchAll();

$typeIcon = ['pdf'=>['fa-file-pdf','pdf'],'doc'=>['fa-file-word','doc'],'docx'=>['fa-file-word','doc'],
             'xls'=>['fa-file-excel','doc'],'xlsx'=>['fa-file-excel','doc'],'ppt'=>['fa-file-powerpoint','doc'],
             'pptx'=>['fa-file-powerpoint','doc'],'jpg'=>['fa-image','img'],'jpeg'=>['fa-image','img'],
             'png'=>['fa-image','img'],'mp4'=>['fa-file-video','doc']];

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Documents</span></div>
    <h1>Document Repository</h1>
    <p>Store and manage training materials, reports, and media files</p>
  </div>
</div>

<!-- Upload Zone -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">&#8679;Upload File</div></div>
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
            <option value="">— General / No Training —</option>
            <?php foreach ($trainings as $t): ?>
            <option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="min-width:180px;margin:0">
          <label class="form-label">Visibility</label>
          <select name="visibility" class="form-control">
            <option value="public">&#127760; Public (All users)</option>
            <option value="ec_trainer">&#128101; EC &amp; Project Leaders only</option>
            <option value="private">&#128274; Private (EC only)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:40px">&#8679; Upload File</button>
        <button type="button" class="btn btn-outline" style="height:40px" onclick="document.getElementById('fileInput').click()">&#128193; Browse Files</button>
      </div>
    </form>
  </div>
</div>

<!-- Documents Table -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title">All Documents</div><div class="card-subtitle"><?= count($docs) ?> files</div></div>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-row">
      <div class="search-box">
        &#128269;
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search documents…"/>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
      <?php if ($q): ?><a href="<?= BASE_URL ?>/ec/documents.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>File Name</th><th>Type</th><th>Training</th><th>Visibility</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($docs as $d):
        $ext = strtolower($d['file_type'] ?? '');
        $vis = $d['visibility'] ?? 'public';
        $visLabel = ['private'=>'&#128274; Private','ec_trainer'=>'&#128101; EC &amp; Project Leaders','public'=>'&#127760; Public'];
        $visColor = ['private'=>'var(--red)','ec_trainer'=>'var(--blue-primary)','public'=>'var(--green)'];
      ?>
      <tr>
        <td><span style="font-size:13px;font-weight:600"><?= e($d['original_name']) ?></span></td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)"><?= e(strtoupper($ext)) ?></span></td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($d['training_title'] ?? 'General') ?></td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="set_visibility"/>
            <input type="hidden" name="doc_id" value="<?= $d['id'] ?>"/>
            <select name="visibility" class="filter-select" style="font-size:12px;padding:4px 8px;border-radius:6px;color:<?= $visColor[$vis] ?>" onchange="this.form.submit()">
              <option value="private"   <?= $vis==='private'   ?'selected':'' ?>>Private (EC only)</option>
              <option value="ec_trainer"<?= $vis==='ec_trainer'?'selected':'' ?>>EC &amp; Project Leaders</option>
              <option value="public"    <?= $vis==='public'    ?'selected':'' ?>>Public (All users)</option>
            </select>
          </form>
        </td>
        <td style="font-size:12px"><?= e($d['uploader'] ?? '—') ?></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= isset($d['created_at']) ? date('M d, Y', strtotime($d['created_at'])) : '—' ?></td>
        <td>
          <div class="action-btns">
            <a href="<?= UPLOAD_URL . e($d['file_name']) ?>" download="<?= e($d['original_name']) ?>" class="btn btn-sm btn-outline">&#8681;</a>
            <a href="<?= UPLOAD_URL . e($d['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline">&#128065;</a>
            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $d['id'] ?>, '<?= e($d['original_name']) ?>')">&#128465;</button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($docs)): ?>
      <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400)">No documents uploaded yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Delete confirm form (hidden) -->
<form method="POST" id="deleteDocForm" style="display:none">
  <input type="hidden" name="action" value="delete"/>
  <input type="hidden" name="doc_id" id="deleteDocId"/>
</form>

<script>
// Drag & drop
const dz = document.getElementById('dropZone');
const fi = document.getElementById('fileInput');
const fc = document.getElementById('fileChosen');

fi.addEventListener('change', () => { fc.textContent = fi.files[0]?.name || ''; });
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('dragover');
  fi.files = e.dataTransfer.files;
  fc.textContent = fi.files[0]?.name || '';
});

function confirmDelete(id, name) {
  if (confirm('Delete "' + name + '"? This cannot be undone.')) {
    document.getElementById('deleteDocId').value = id;
    document.getElementById('deleteDocForm').submit();
  }
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
