<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$activePage = 'messages';

// Mark a single message as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $pdo->prepare('UPDATE contact_messages SET is_read=1 WHERE id=?')->execute([(int)$_GET['read']]);
    header('Location: ' . BASE_URL . '/ec/messages.php');
    exit;
}

// Mark all as read
if (isset($_GET['mark_all'])) {
    $pdo->exec('UPDATE contact_messages SET is_read=1');
    header('Location: ' . BASE_URL . '/ec/messages.php');
    exit;
}

// Delete a message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare('DELETE FROM contact_messages WHERE id=?')->execute([(int)$_GET['delete']]);
    header('Location: ' . BASE_URL . '/ec/messages.php');
    exit;
}

// Fetch messages (newest first)
$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
$unread   = array_filter($messages, fn($m) => !$m['is_read']);

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Contact Messages</div>
    <div class="page-sub">Messages sent by the public via the Contact page</div>
  </div>
  <?php if (count($unread) > 0): ?>
  <a href="?mark_all=1" class="btn btn-outline" onclick="return confirm('Mark all messages as read?')">
    &#10003; Mark All Read
  </a>
  <?php endif; ?>
</div>

<?php if (empty($messages)): ?>
<div class="empty-state">
  <div class="empty-icon">&#9993;</div>
  <div class="empty-title">No messages yet</div>
  <div class="empty-sub">When someone sends a message through the contact form, it will appear here.</div>
</div>
<?php else: ?>

<div class="card" style="padding:0;overflow:hidden">
  <table class="data-table">
    <thead>
      <tr>
        <th style="width:32px"></th>
        <th>Name</th>
        <th>Email</th>
        <th>Subject</th>
        <th>Date</th>
        <th style="width:120px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($messages as $msg): ?>
      <tr style="<?= !$msg['is_read'] ? 'background:#EFF6FF;font-weight:600' : '' ?>">
        <td style="text-align:center">
          <?php if (!$msg['is_read']): ?>
          <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#1A56DB"></span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($msg['name']) ?></td>
        <td><a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color:var(--blue-primary)"><?= htmlspecialchars($msg['email']) ?></a></td>
        <td>
          <span style="cursor:pointer;color:var(--navy)" onclick="toggleMsg(<?= $msg['id'] ?>)">
            <?= htmlspecialchars($msg['subject']) ?>
          </span>
          <div id="msg-<?= $msg['id'] ?>" style="display:none;margin-top:8px;padding:12px;background:#F8FAFF;border-radius:8px;font-size:13px;color:#334155;font-weight:400;white-space:pre-wrap;border:1px solid #E2E8F0">
            <?= htmlspecialchars($msg['message']) ?>
          </div>
        </td>
        <td style="white-space:nowrap;font-size:12px;color:var(--gray-500)"><?= date('M d, Y g:i A', strtotime($msg['created_at'])) ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <?php if (!$msg['is_read']): ?>
            <a href="?read=<?= $msg['id'] ?>" class="btn btn-sm btn-outline" title="Mark as read">&#10003;</a>
            <?php endif; ?>
            <a href="?delete=<?= $msg['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this message?')" title="Delete">&#128465;</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<script>
function toggleMsg(id) {
  const el = document.getElementById('msg-' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
