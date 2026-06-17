<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'dashboard';

// ── Recent trainings ───────────────────────────────────────────────────────
$recentTrainings = $pdo->query(
    'SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name,
            (SELECT COUNT(*) FROM participants p WHERE p.training_id = t.id) AS enrolled
     FROM trainings t LEFT JOIN users u ON t.trainer_id = u.id
     ORDER BY t.created_at DESC'
)->fetchAll();

// ── Latest documents ──────────────────────────────────────────────────────
$latestDocs = $pdo->query(
    'SELECT d.*, t.title AS training_title
     FROM documents d
     LEFT JOIN trainings t ON d.training_id = t.id
     LEFT JOIN users u ON d.uploaded_by = u.id
     ORDER BY d.created_at DESC LIMIT 5'
)->fetchAll();

// ── Skills utilization ────────────────────────────────────────────────────
$skills = $pdo->query(
    'SELECT AVG(personal_use_pct) AS personal, AVG(income_gen_pct) AS income,
            AVG(employment_pct) AS employment
     FROM skills_utilization'
)->fetch();
$skills = array_map(fn($v) => round((float)$v, 1), $skills ?: ['personal'=>72,'income'=>55,'employment'=>38]);

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Dashboard</span></div>
    <h1>Dashboard Overview</h1>
    <p>Welcome back, <?= e($_SESSION['user_first']) ?>! Here's what's happening in CIT extension programs.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addTraining')">&#43; Create Training</button>
</div>

<!-- Training Activities Table (full width, expanded) -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Training Activities</div>
      <div class="card-subtitle">All extension trainings — change status directly in the table</div>
    </div>
    <a href="<?= BASE_URL ?>/ec/trainings.php" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Training Name</th>
          <th>Area</th>
          <th>Project Leader</th>
          <th>Schedule</th>
          <th>Enrolled</th>
          <th>Target</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($recentTrainings as $t):
        $sc = ['Proposed'=>'#F59E0B','Approved'=>'#1A56DB','Ongoing'=>'#10B981','Completed'=>'#6B7280'];
        $col = $sc[$t['status']] ?? '#6B7280';
      ?>
      <tr>
        <td>
          <strong><?= e($t['title']) ?></strong>
          <?php if ($t['description']): ?>
          <div style="font-size:11px;color:var(--gray-400);margin-top:2px"><?= e(mb_strimwidth($t['description'], 0, 70, '…')) ?></div>
          <?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($t['area']) ?></td>
        <td style="font-size:12px;color:var(--gray-700)"><?= e($t['trainer_name'] ?? 'TBA') ?></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= e($t['date_start'] ?? '—') ?></td>
        <td><strong><?= (int)$t['enrolled'] ?></strong></td>
        <td style="font-size:12px;color:var(--gray-500)"><?= (int)$t['target_participants'] ?></td>
        <td>
          <form method="POST" action="<?= BASE_URL ?>/ec/trainings.php" style="display:inline">
            <input type="hidden" name="action" value="update_status"/>
            <input type="hidden" name="training_id" value="<?= $t['id'] ?>"/>
            <select name="status" class="filter-select"
              style="font-size:12px;padding:4px 8px;border-radius:6px;color:<?= $col ?>;font-weight:600;border-color:<?= $col ?>"
              onchange="this.form.submit()">
              <?php foreach (['Proposed','Approved','Ongoing','Completed'] as $s): ?>
              <option <?= $t['status']===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td>
          <a href="<?= BASE_URL ?>/ec/trainings.php?view=<?= $t['id'] ?>" class="btn btn-sm btn-outline">Manage</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($recentTrainings)): ?>
      <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400)">No trainings yet. <a href="#" onclick="openModal('addTraining')">Create one.</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bottom panels -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

  <!-- Latest Uploads -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">Latest Uploads</div>
      <a href="<?= BASE_URL ?>/ec/documents.php" class="btn btn-ghost btn-sm">See all</a>
    </div>
    <div class="card-body" style="padding-top:12px">
      <?php if (empty($latestDocs)): ?>
      <div class="empty-state" style="padding:20px">&#128193;<p>No documents yet.</p></div>
      <?php else: foreach ($latestDocs as $d): ?>
      <div class="upload-item">
        <div class="upload-item-body">
          <div class="upload-item-name"><?= e($d['original_name'] ?? $d['file_name'] ?? '') ?></div>
          <div class="upload-item-meta"><?= e($d['training_title'] ?? 'General') ?> · <?= date('M d, Y', strtotime($d['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Skills Utilization -->
  <div class="card">
    <div class="card-header"><div class="card-title">Skills Utilization</div></div>
    <div class="card-body">
      <?php foreach ([
        ['Personal Use',      $skills['personal']   ?? 72],
        ['Income-Generating', $skills['income']     ?? 55],
        ['Employment',        $skills['employment'] ?? 38],
      ] as [$label, $pct]): ?>
      <div class="skills-row">
        <div class="skills-label"><?= $label ?></div>
        <div style="flex:1"><div class="progress-bar-wrap"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div></div>
        <div class="skills-pct"><?= $pct ?>%</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Quick Links -->
  <div class="card">
    <div class="card-header"><div class="card-title">Quick Links</div></div>
    <div class="card-body">
      <div class="quick-links">
        <div class="quick-link-item" onclick="openModal('addTraining')">Create New Training</div>
        <a href="<?= BASE_URL ?>/ec/trainings.php"   class="quick-link-item">All Trainings</a>
        <a href="<?= BASE_URL ?>/ec/evaluations.php" class="quick-link-item">Evaluations</a>
        <a href="<?= BASE_URL ?>/ec/reports.php"     class="quick-link-item">enerate Reports</a>
        <a href="<?= BASE_URL ?>/ec/documents.php"   class="quick-link-item">pload Documents</a>
        <a href="<?= BASE_URL ?>/ec/participants.php" class="quick-link-item">Register Participant</a>
      </div>
    </div>
  </div>

</div>

<!-- MODAL: CREATE TRAINING -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2>&#43; Create New Training</h2>
      <button class="modal-close" onclick="closeModal('addTraining')">&#10005;</button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>/ec/trainings.php">
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Training Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Basic Pastry Making" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <select name="area" class="form-control" required>
              <option>Mechanical Technology</option>
              <option>Automotive Technology</option>
              <option>Computer Technology</option>
              <option>Electronics Technology</option>
              <option>Culinary Technology</option>
              <option>Apparel and Fashion Technology</option>
              <option>Print Media Technology</option>
              <option>Information Technology</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the training…"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="date_start" class="form-control"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end" class="form-control"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget Allocated (₱)</label>
            <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 50000" min="0" step="0.01"/>
          </div>
          <div class="form-group">
            <label class="form-label">Budget Used (₱)</label>
            <input type="number" name="budget_used" class="form-control" placeholder="0" min="0" step="0.01" value="0"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option>Proposed</option><option>Approved</option><option>Ongoing</option><option>Completed</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Participants</label>
            <input type="number" name="target_participants" class="form-control" placeholder="e.g. 30" min="1"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Project Leader</label>
          <select name="trainer_id" class="form-control">
            <option value="">— Select Project Leader —</option>
            <?php
            $trainers = $pdo->query('SELECT id, first_name, last_name FROM users WHERE role="trainer" AND is_active=1')->fetchAll();
            foreach ($trainers as $tr): ?>
            <option value="<?= $tr['id'] ?>"><?= e($tr['first_name'] . ' ' . $tr['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Training</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
