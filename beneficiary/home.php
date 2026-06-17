<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'home';

$bid = $_SESSION['user_id'];

// ── Stats ─────────────────────────────────────────────────────────────────
$totalTrainings = (int)$pdo->prepare('SELECT COUNT(DISTINCT training_id) FROM participants WHERE beneficiary_id=?')
    ->execute([$bid]) ? $pdo->prepare('SELECT COUNT(DISTINCT training_id) FROM participants WHERE beneficiary_id=?')
    ->execute([$bid]) && ($s = $pdo->prepare('SELECT COUNT(DISTINCT training_id) FROM participants WHERE beneficiary_id=?')) && $s->execute([$bid]) ? (int)$s->fetchColumn() : 0 : 0;

// cleaner queries
$stTotal = $pdo->prepare('SELECT COUNT(DISTINCT t.id) FROM trainings t JOIN participants p ON p.training_id=t.id WHERE p.beneficiary_id=?');
$stTotal->execute([$bid]); $totalTrainings = (int)$stTotal->fetchColumn();

$stCompleted = $pdo->prepare('SELECT COUNT(DISTINCT t.id) FROM trainings t JOIN participants p ON p.training_id=t.id WHERE p.beneficiary_id=? AND t.status="Completed"');
$stCompleted->execute([$bid]); $completedTrainings = (int)$stCompleted->fetchColumn();

$stPending = $pdo->prepare('SELECT COUNT(*) FROM eval_responses er JOIN eval_forms ef ON ef.id=er.form_id JOIN participants p ON p.training_id=ef.training_id WHERE p.beneficiary_id=? AND er.beneficiary_id=?');
// pending evals = forms sent but not yet answered
$stPendingEval = $pdo->prepare(
    'SELECT COUNT(*) FROM eval_forms ef
     JOIN participants p ON p.training_id=ef.training_id
     LEFT JOIN eval_responses er ON er.form_id=ef.id AND er.beneficiary_id=?
     WHERE p.beneficiary_id=? AND ef.sent_at IS NOT NULL AND er.id IS NULL'
);
$stPendingEval->execute([$bid, $bid]); $pendingEvals = (int)$stPendingEval->fetchColumn();

$stPendingSkills = $pdo->prepare(
    'SELECT COUNT(*) FROM skills_forms sf
     JOIN participants p ON p.training_id=sf.training_id
     LEFT JOIN skills_responses sr ON sr.form_id=sf.id AND sr.beneficiary_id=?
     WHERE p.beneficiary_id=? AND sf.sent_at IS NOT NULL AND sr.id IS NULL'
);
$stPendingSkills->execute([$bid, $bid]); $pendingSkills = (int)$stPendingSkills->fetchColumn();

// ── Recent trainings ──────────────────────────────────────────────────────
$recentStmt = $pdo->prepare(
    'SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name
     FROM trainings t
     JOIN participants p ON p.training_id=t.id
     LEFT JOIN users u ON u.id=t.trainer_id
     WHERE p.beneficiary_id=?
     ORDER BY t.date_start DESC LIMIT 3'
);
$recentStmt->execute([$bid]);
$recentTrainings = $recentStmt->fetchAll();

// ── Notifications (unread) ────────────────────────────────────────────────
$notifStmt = $pdo->prepare(
    'SELECT * FROM notifications WHERE is_read=0 AND role="beneficiary" AND user_id=? ORDER BY created_at DESC LIMIT 5'
);
$notifStmt->execute([$bid]);
$notifications = $notifStmt->fetchAll();

$catEmoji = [
    'Mechanical Technology'          => '⚙️',
    'Automotive Technology'          => '🚗',
    'Computer Technology'            => '💻',
    'Electronics Technology'         => '🔌',
    'Culinary Technology'            => '🍳',
    'Apparel and Fashion Technology' => '🧵',
    'Print Media Technology'         => '🖨️',
    'Information Technology'         => '🖥️',
];

require __DIR__ . '/layout.php';
?>

<style>
/* ── Hero banner ── */
.b-hero {
  background: linear-gradient(135deg, #09182F 0%, #0D2348 55%, #152F65 100%);
  border-radius: 20px;
  padding: 40px 40px 36px;
  position: relative;
  overflow: hidden;
  margin-bottom: 24px;
}
.b-hero::before {
  content: '';
  position: absolute; top: -60px; right: -60px;
  width: 320px; height: 320px; border-radius: 50%;
  background: radial-gradient(circle, rgba(56,189,248,.12) 0%, transparent 65%);
  pointer-events: none;
}
.b-hero::after {
  content: '';
  position: absolute; bottom: -40px; left: -40px;
  width: 200px; height: 200px; border-radius: 50%;
  background: radial-gradient(circle, rgba(26,86,219,.15) 0%, transparent 65%);
  pointer-events: none;
}
.b-hero-inner { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
.b-hero-eyebrow {
  display: inline-flex; align-items: center; gap: 7px;
  background: rgba(56,189,248,.12); border: 1px solid rgba(56,189,248,.25);
  color: #38BDF8; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1.2px;
  padding: 4px 12px; border-radius: 40px; margin-bottom: 10px;
}
.b-hero-dot { width: 6px; height: 6px; border-radius: 50%; background: #38BDF8; animation: bpulse 2s ease-in-out infinite; }
@keyframes bpulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }
.b-hero-title { font-size: clamp(22px,3vw,32px); font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 8px; }
.b-hero-title span { color: #38BDF8; }
.b-hero-sub { font-size: 13.5px; color: rgba(255,255,255,.55); line-height: 1.65; max-width: 420px; }
.b-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
.b-hero-btn-primary {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 22px; border-radius: 9px;
  background: linear-gradient(135deg,#1A56DB,#2E6BF0);
  color: #fff; font-size: 13px; font-weight: 700;
  text-decoration: none; transition: all .22s;
  box-shadow: 0 3px 12px rgba(26,86,219,.4);
}
.b-hero-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 18px rgba(26,86,219,.55); }
.b-hero-btn-ghost {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 20px; border-radius: 9px;
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18);
  color: rgba(255,255,255,.8); font-size: 13px; font-weight: 600;
  text-decoration: none; transition: all .22s;
}
.b-hero-btn-ghost:hover { background: rgba(255,255,255,.15); color: #fff; }

/* ── Stat cards ── */
.b-stats { display: grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap: 16px; margin-bottom: 24px; }
.b-stat {
  background: #fff; border-radius: 16px; padding: 20px;
  border: 1px solid #E8EEF8; text-align: center;
  transition: all .22s;
}
.b-stat:hover { box-shadow: 0 8px 28px rgba(9,24,47,.09); transform: translateY(-2px); }
.b-stat-val { font-size: 30px; font-weight: 800; line-height: 1; margin-bottom: 5px; }
.b-stat-lbl { font-size: 12px; color: var(--gray-400); }

/* ── Section title ── */
.b-sec-title { font-size: 15px; font-weight: 700; color: var(--navy); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.b-sec-title a { font-size: 12px; font-weight: 600; color: var(--blue-primary); text-decoration: none; margin-left: auto; }

/* ── Training mini cards ── */
.b-training-list { display: flex; flex-direction: column; gap: 12px; }
.b-tc {
  display: flex; align-items: center; gap: 14px;
  background: #fff; border-radius: 14px; padding: 14px 16px;
  border: 1px solid #E8EEF8; transition: all .22s;
  text-decoration: none;
}
.b-tc:hover { box-shadow: 0 6px 20px rgba(9,24,47,.09); border-color: #C7D9F5; transform: translateX(3px); }
.b-tc-ico { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; background: #EFF6FF; }
.b-tc-title { font-size: 13.5px; font-weight: 700; color: var(--navy); margin-bottom: 3px; }
.b-tc-meta { font-size: 11.5px; color: var(--gray-400); }
.b-tc-badge { margin-left: auto; flex-shrink: 0; }

/* ── Notification items ── */
.b-notif-list { display: flex; flex-direction: column; gap: 10px; }
.b-notif {
  display: flex; align-items: flex-start; gap: 12px;
  background: #EFF6FF; border-radius: 12px; padding: 12px 14px;
  border: 1px solid #DBEAFE;
}
.b-notif-ico { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.b-notif-msg { font-size: 13px; font-weight: 600; color: var(--navy); line-height: 1.45; }
.b-notif-time { font-size: 11px; color: var(--gray-400); margin-top: 3px; }

/* ── Quick links ── */
.b-quick { display: grid; grid-template-columns: repeat(auto-fit,minmax(130px,1fr)); gap: 12px; }
.b-ql {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  background: #fff; border-radius: 14px; padding: 18px 12px;
  border: 1px solid #E8EEF8; text-decoration: none;
  transition: all .22s; text-align: center;
}
.b-ql:hover { box-shadow: 0 6px 20px rgba(9,24,47,.09); border-color: #C7D9F5; transform: translateY(-2px); }
.b-ql-ico { font-size: 26px; }
.b-ql-lbl { font-size: 12px; font-weight: 600; color: var(--navy); }

@media (max-width: 640px) {
  .b-hero { padding: 28px 20px; }
  .b-hero-title { font-size: 22px; }
}
</style>

<!-- Hero -->
<div class="b-hero">
  <div class="b-hero-inner">
    <div>
      <div class="b-hero-eyebrow"><span class="b-hero-dot"></span> Participant Portal</div>
      <div class="b-hero-title">
        Welcome back,<br/><span><?= e($_SESSION['user_first'] ?? 'Participant') ?></span>
      </div>
      <div class="b-hero-sub">
        Track your training programs, submit evaluations, and respond to skills surveys — all in one place.
      </div>
      <div class="b-hero-actions">
        <a href="<?= BASE_URL ?>/beneficiary/trainings.php" class="b-hero-btn-primary">&#128218; My Trainings</a>
        <?php if ($pendingEvals + $pendingSkills > 0): ?>
        <a href="<?= BASE_URL ?>/beneficiary/evaluations.php" class="b-hero-btn-ghost">
          &#9888; <?= $pendingEvals + $pendingSkills ?> Pending
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="b-stats">
  <div class="b-stat">
    <div class="b-stat-val" style="color:var(--blue-primary)"><?= $totalTrainings ?></div>
    <div class="b-stat-lbl">Trainings Enrolled</div>
  </div>
  <div class="b-stat">
    <div class="b-stat-val" style="color:var(--green)"><?= $completedTrainings ?></div>
    <div class="b-stat-lbl">Completed</div>
  </div>
  <div class="b-stat">
    <div class="b-stat-val" style="color:<?= $pendingEvals > 0 ? '#F59E0B' : 'var(--gray-400)' ?>"><?= $pendingEvals ?></div>
    <div class="b-stat-lbl">Pending Evaluations</div>
  </div>
  <div class="b-stat">
    <div class="b-stat-val" style="color:<?= $pendingSkills > 0 ? '#F59E0B' : 'var(--gray-400)' ?>"><?= $pendingSkills ?></div>
    <div class="b-stat-lbl">Pending Surveys</div>
  </div>
</div>

<!-- Main grid -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

  <!-- Left: recent trainings + quick links -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Recent Trainings -->
    <div class="card">
      <div class="card-body" style="padding-bottom:0">
        <div class="b-sec-title">
          &#128218; Recent Trainings
          <a href="<?= BASE_URL ?>/beneficiary/trainings.php">View all &#8594;</a>
        </div>
      </div>
      <?php if (empty($recentTrainings)): ?>
      <div class="card-body" style="text-align:center;padding:40px;color:var(--gray-400)">
        You haven't been enrolled in any trainings yet.
      </div>
      <?php else: ?>
      <div class="card-body" style="padding-top:0">
        <div class="b-training-list">
          <?php foreach ($recentTrainings as $t):
            $em = $catEmoji[$t['area']] ?? '📚';
            $sc = ['Proposed'=>'badge-proposed','Approved'=>'badge-approved','Ongoing'=>'badge-ongoing','Completed'=>'badge-completed'];
          ?>
          <a href="<?= BASE_URL ?>/beneficiary/trainings.php?view=<?= $t['id'] ?>" class="b-tc">
            <div class="b-tc-ico"><?= $em ?></div>
            <div style="flex:1;min-width:0">
              <div class="b-tc-title"><?= e($t['title']) ?></div>
              <div class="b-tc-meta">
                &#128197; <?= e($t['date_start'] ?? '—') ?>
                &nbsp;·&nbsp; &#128100; <?= e($t['trainer_name'] ?? 'TBA') ?>
              </div>
            </div>
            <div class="b-tc-badge">
              <span class="badge <?= $sc[$t['status']] ?? 'badge-proposed' ?>"><?= e($t['status']) ?></span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-body">
        <div class="b-sec-title">&#9889; Quick Access</div>
        <div class="b-quick">
          <a href="<?= BASE_URL ?>/beneficiary/trainings.php" class="b-ql">
            <span class="b-ql-ico">&#128218;</span>
            <span class="b-ql-lbl">Trainings</span>
          </a>
          <a href="<?= BASE_URL ?>/beneficiary/evaluations.php" class="b-ql">
            <span class="b-ql-ico">&#11088;</span>
            <span class="b-ql-lbl">Evaluations</span>
            <?php if ($pendingEvals > 0): ?>
            <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700"><?= $pendingEvals ?></span>
            <?php endif; ?>
          </a>
          <a href="<?= BASE_URL ?>/beneficiary/skills.php" class="b-ql">
            <span class="b-ql-ico">&#128200;</span>
            <span class="b-ql-lbl">Skills Survey</span>
            <?php if ($pendingSkills > 0): ?>
            <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700"><?= $pendingSkills ?></span>
            <?php endif; ?>
          </a>
          <a href="<?= BASE_URL ?>/beneficiary/profile.php" class="b-ql">
            <span class="b-ql-ico">&#128100;</span>
            <span class="b-ql-lbl">My Profile</span>
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- Right: notifications -->
  <div class="card">
    <div class="card-body">
      <div class="b-sec-title">
        &#128276; Notifications
        <?php if (!empty($notifications)): ?>
        <a href="<?= BASE_URL ?>/beneficiary/notifications.php?mark_all_read=1">Mark all read</a>
        <?php endif; ?>
      </div>
      <?php if (empty($notifications)): ?>
      <div style="text-align:center;padding:32px 16px;color:var(--gray-400)">
        <div style="font-size:28px;margin-bottom:8px">&#128276;</div>
        <div style="font-size:13px">You're all caught up!</div>
      </div>
      <?php else: ?>
      <div class="b-notif-list">
        <?php foreach ($notifications as $n): ?>
        <a href="<?= e($n['link'] ?? '#') ?>" style="text-decoration:none">
          <div class="b-notif">
            <div class="b-notif-ico">&#128276;</div>
            <div>
              <div class="b-notif-msg"><?= e($n['message']) ?></div>
              <div class="b-notif-time"><?= date('M d, g:i A', strtotime($n['created_at'])) ?></div>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <a href="<?= BASE_URL ?>/beneficiary/notifications.php"
         style="display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--blue-primary);text-decoration:none;font-weight:600">
        View all notifications &#8594;
      </a>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require __DIR__ . '/layout_end.php'; ?>
