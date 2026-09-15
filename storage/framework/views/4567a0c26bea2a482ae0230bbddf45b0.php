<?php $__env->startSection('content'); ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluation</span></div>
    <h1>Evaluation</h1>
    <p>Evaluations, Impact Assessment, and Skills Utilization at a glance</p>
  </div>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon <?php echo e($pendingEvaluations > 0 ? 'red' : 'green'); ?>"><i class="fas fa-star-half-stroke"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($pendingEvaluations); ?></div><div class="stat-label">Pending Evaluations</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-paper-plane"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($evalFormsSent); ?></div><div class="stat-label">Evaluation Forms Sent</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon <?php echo e($pendingAssessments > 0 ? 'yellow' : 'green'); ?>"><i class="fas fa-clipboard-list"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($pendingAssessments); ?></div><div class="stat-label">Impact Assessments Awaiting Review</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-chart-line"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($skillsResponses); ?></div><div class="stat-label">Skills Survey Responses</div></div>
  </div>
</div>

<!-- Go to sub-features -->
<div class="home-grid" style="margin-bottom:24px">
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-star" style="color:var(--blue-primary)"></i> Evaluations</div>
      <div class="training-card-desc">Build and send per-activity evaluation forms, and review submitted responses.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="<?php echo e(route('ec.evaluations')); ?>" class="btn btn-sm btn-primary">Open Evaluations <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-clipboard-list" style="color:var(--blue-primary)"></i> Impact Assessment</div>
      <div class="training-card-desc">Review assessments submitted by Evaluators and manage participant survey forms.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="<?php echo e(route('ec.impact_assessment')); ?>" class="btn btn-sm btn-primary">Open Impact Assessment <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
  <div class="training-card">
    <div class="training-card-body">
      <div class="training-card-title"><i class="fas fa-chart-line" style="color:var(--blue-primary)"></i> Skills Utilization</div>
      <div class="training-card-desc">Track skills-utilization survey forms and responses across all activities.</div>
      <div class="training-card-footer" style="justify-content:flex-end">
        <a href="<?php echo e(route('ec.skills')); ?>" class="btn btn-sm btn-primary">Open Skills Utilization <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Impact Assessments -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Impact Assessments</div></div>
    <a href="<?php echo e(route('ec.impact_assessment')); ?>" class="btn btn-ghost btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Title</th><th>Evaluator</th><th>Activity</th><th>Status</th><th>Submitted</th></tr></thead>
      <tbody>
      <?php $__empty_1 = true; $__currentLoopData = $recentAssessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td><strong><?php echo e($a->title); ?></strong></td>
          <td style="font-size:12px"><?php echo e($a->evaluator->full_name ?? '—'); ?></td>
          <td style="font-size:12px;color:var(--gray-600)"><?php echo e($a->training->title ?? '—'); ?></td>
          <td><span class="badge <?php echo e($a->status === 'Reviewed' ? 'badge-success' : 'badge-info'); ?>"><?php echo e($a->status); ?></span></td>
          <td style="font-size:12px;color:var(--gray-400)"><?php echo e($a->submitted_at?->format('M d, Y') ?? '—'); ?></td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-400)">No impact assessments submitted yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.ec', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/evaluation.blade.php ENDPATH**/ ?>