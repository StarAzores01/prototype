<?php $__env->startSection('content'); ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Contact Messages</span></div>
    <h1>Contact Messages</h1>
    <p>Messages sent by the public via the Contact page</p>
  </div>
  <?php if($unread->count() > 0): ?>
  <a href="<?php echo e(route('ec.messages')); ?>?mark_all=1" class="btn btn-outline" onclick="return confirm('Mark all messages as read?')">
    <i class="fas fa-check"></i> Mark All Read
  </a>
  <?php endif; ?>
</div>

<?php if($messages->isEmpty()): ?>
<div class="empty-state">
  <div class="empty-icon"><i class="fas fa-envelope"></i></div>
  <div class="empty-title">No messages yet</div>
  <div class="empty-sub">When someone sends a message through the contact form, it will appear here.</div>
</div>
<?php else: ?>
<div class="card" style="padding:0;overflow:hidden">
  <div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th class="col-indicator" style="width:32px"></th>
        <th>Name</th>
        <th>Email</th>
        <th style="padding-right:28px">Subject</th>
        <th>Date</th>
        <th style="width:120px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr style="<?php echo e(!$msg->is_read ? 'background:rgba(59,130,246,.12);font-weight:600' : ''); ?>">
        <td class="col-indicator" style="text-align:center">
          <?php if(!$msg->is_read): ?>
          <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#1A56DB"></span>
          <?php endif; ?>
        </td>
        <td><?php echo e($msg->name); ?></td>
        <td><a href="mailto:<?php echo e($msg->email); ?>" style="color:var(--blue-primary)"><?php echo e($msg->email); ?></a></td>
        <td style="padding-right:28px">
          <span style="cursor:pointer;color:var(--text-heading)" onclick="toggleMsg(<?php echo e($msg->id); ?>)">
            <?php echo e($msg->subject); ?>

          </span>
          <div id="msg-<?php echo e($msg->id); ?>" style="display:none;margin-top:8px;padding:12px;background:var(--gray-50);border-radius:8px;font-size:13px;color:var(--gray-700);font-weight:400;white-space:pre-wrap;border:1px solid var(--gray-200)">
            <?php echo e($msg->message); ?>

          </div>
        </td>
        <td style="white-space:nowrap;font-size:12px;color:var(--gray-500)"><?php echo e($msg->created_at->format('M d, Y g:i A')); ?></td>
        <td>
          <div style="display:flex;gap:6px">
            <?php if(!$msg->is_read): ?>
            <a href="<?php echo e(route('ec.messages')); ?>?read=<?php echo e($msg->id); ?>" class="btn btn-sm btn-outline" title="Mark as read"><i class="fas fa-check"></i></a>
            <?php endif; ?>
            <a href="<?php echo e(route('ec.messages')); ?>?delete=<?php echo e($msg->id); ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this message?')" title="Delete"><i class="fas fa-trash"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<script>
function toggleMsg(id) {
  const el = document.getElementById('msg-' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.ec', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/messages.blade.php ENDPATH**/ ?>