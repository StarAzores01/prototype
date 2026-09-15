<?php $__env->startSection('content'); ?>
<?php
  $statusColors = ['Proposed' => '#F59E0B', 'Approved' => '#1A56DB', 'Ongoing' => '#10B981', 'Completed' => '#6B7280'];
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Dashboard</span></div>
    <h1>Dashboard Overview</h1>
    <p>Welcome back, <?php echo e($userFirstName); ?>! Here's what's happening in CIT extension programs.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addProgram')"><i class="fas fa-plus"></i> Create Project</button>
</div>

<!-- Activities Table -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Activities</div>
      <div class="card-subtitle">All extension activities — change status directly in the table</div>
    </div>
    <a href="<?php echo e(route('ec.trainings')); ?>" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Activity Name</th>
          <th>Project</th>
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
      <?php $__empty_1 = true; $__currentLoopData = $recentTrainings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $col = $statusColors[$t->status] ?? '#6B7280'; ?>
        <tr>
          <td style="font-size:12px">
            <strong style="font-size:12px"><?php echo e($t->title); ?></strong>
            <?php if($t->description): ?>
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px"><?php echo e(\Illuminate\Support\Str::limit($t->description, 70, '…')); ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--gray-700)"><?php echo e($t->program?->title ?? '—'); ?></td>
          <td style="font-size:12px;color:var(--gray-600)"><?php echo e($t->area); ?></td>
          <td style="font-size:12px;color:var(--gray-700)"><?php echo e($t->trainer?->full_name ?? 'TBA'); ?></td>
          <td style="font-size:12px;color:var(--gray-400)"><?php echo e($t->date_start?->format('Y-m-d') ?? '—'); ?></td>
          <td style="font-size:12px"><strong style="font-size:12px"><?php echo e($t->enrolled); ?></strong></td>
          <td style="font-size:12px;color:var(--gray-500)"><?php echo e($t->target_participants); ?></td>
          <td style="font-size:12px">
            <form method="POST" action="<?php echo e(route('ec.trainings.store')); ?>" style="display:inline">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="update_status"/>
              <input type="hidden" name="training_id" value="<?php echo e($t->id); ?>"/>
              <select name="status" class="filter-select"
                style="font-size:12px;padding:4px 8px;border-radius:6px;color:<?php echo e($col); ?>;font-weight:600;border-color:<?php echo e($col); ?>"
                onchange="this.form.submit()">
                <?php $__currentLoopData = ['Proposed','Approved','Ongoing','Completed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option <?php echo e($t->status === $s ? 'selected' : ''); ?>><?php echo e($s); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </form>
          </td>
          <td style="font-size:12px">
            <a href="<?php echo e(route('ec.trainings')); ?>?view=<?php echo e($t->id); ?>" class="btn btn-sm btn-outline">Manage</a>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--gray-400)">No activities yet. <a href="#" onclick="openModal('addTraining')">Create one.</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bottom panels -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

  <div class="card">
    <div class="card-header">
      <div class="card-title">Latest Uploads</div>
      <a href="<?php echo e(route('ec.documents')); ?>" class="btn btn-ghost btn-sm">See all</a>
    </div>
    <div class="card-body" style="padding-top:12px">
      <?php $__empty_1 = true; $__currentLoopData = $latestDocs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div class="upload-item">
        <div class="upload-item-body">
          <div class="upload-item-name"><?php echo e($d->original_name ?? $d->file_name); ?></div>
          <div class="upload-item-meta"><?php echo e($d->training->title ?? 'General'); ?> · <?php echo e($d->created_at->format('M d, Y')); ?></div>
        </div>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="empty-state" style="padding:20px"><i class="fas fa-folder-open"></i><p>No documents yet.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Skills Utilization</div></div>
    <div class="card-body">
      <?php $__currentLoopData = [
        ['Personal Use', $skills['personal']],
        ['Income-Generating', $skills['income']],
        ['Employment', $skills['employment']],
      ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $pct]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="skills-row">
        <div class="skills-label"><?php echo e($label); ?></div>
        <div style="flex:1"><div class="progress-bar-wrap"><div class="progress-bar" style="width:<?php echo e($pct); ?>%"></div></div></div>
        <div class="skills-pct"><?php echo e($pct); ?>%</div>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Quick Links</div></div>
    <div class="card-body">
      <div class="quick-links">
        <div class="quick-link-item" onclick="openModal('addTraining')">Create New Activity</div>
        <a href="<?php echo e(route('ec.trainings')); ?>" class="quick-link-item">All Activities</a>
        <a href="<?php echo e(route('ec.evaluations')); ?>" class="quick-link-item">Evaluations</a>
        <a href="<?php echo e(route('ec.reports')); ?>" class="quick-link-item">Generate Reports</a>
        <a href="<?php echo e(route('ec.documents')); ?>" class="quick-link-item">Upload Documents</a>
        <a href="<?php echo e(route('ec.participants')); ?>" class="quick-link-item">Register Participant</a>
      </div>
    </div>
  </div>

</div>

<!-- Homepage Video Montage — site-wide, shown on the public landing page's
     "Course Highlights" section. EC-only (this whole page sits behind
     role:extension_coordinator), unlike the per-program video which was
     removed from the Programs section in favor of this single spot. -->
<div class="card" style="margin-top:20px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-film"></i> Homepage Video Montage</div>
      <div class="card-subtitle">The video shown to visitors on the public homepage's Course Highlights section</div>
    </div>
    <button class="btn btn-sm btn-outline" onclick="openModal('uploadHomepageVideo')">
      <i class="fas fa-film"></i> <?php echo e($homepageVideo?->video_url ? 'Update Video' : 'Add Video Montage'); ?>

    </button>
  </div>
  <div class="card-body">
    <?php if($homepageVideo?->video_url): ?>
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:var(--gray-50);border-radius:var(--radius-sm)">
      <span style="background:rgba(56,189,248,.15);color:var(--blue-primary);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;padding:3px 9px;border-radius:20px;flex-shrink:0">Live</span>
      <a href="<?php echo e($homepageVideo->video_url); ?>" target="_blank" rel="noopener" style="font-size:13px;font-weight:600;color:var(--text-heading);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">
        <?php echo e($homepageVideo->video_title ?: $homepageVideo->video_url); ?>

      </a>
      <a href="<?php echo e($homepageVideo->video_url); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline">
        <i class="fas fa-play"></i> Watch
      </a>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:20px"><i class="fas fa-film"></i><p>No video montage yet — visitors will see an empty placeholder until you add one.</p></div>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL: CREATE ACTIVITY -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Create New Activity</h2>
      <button class="modal-close" onclick="closeModal('addTraining')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.trainings.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <?php echo $__env->make('ec.partials.activity-create-fields', ['trainers' => $trainers, 'programs' => $programs], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Activity</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: CREATE PROJECT (Program) — the dashboard's quick-create shortcut;
     same fields/route as ec/programs.blade.php's own "Create Program" modal,
     see ec.partials.program-create-fields. -->
<div class="modal-overlay" id="modal-addProgram">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Create New Project</h2>
      <button class="modal-close" onclick="closeModal('addProgram')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.programs.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <?php echo $__env->make('ec.partials.program-create-fields', ['trainers' => $trainers], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addProgram')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Project</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: ADD / UPDATE HOMEPAGE VIDEO MONTAGE -->
<div class="modal-overlay" id="modal-uploadHomepageVideo">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h2><i class="fas fa-film"></i> <?php echo e($homepageVideo?->video_url ? 'Update' : 'Add'); ?> Video Montage</h2>
      <button class="modal-close" onclick="closeModal('uploadHomepageVideo')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.dashboard.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="upload_homepage_video"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> Paste an external video link (YouTube, Google Drive, Vimeo, etc.). The system stores the link — no file is uploaded. This replaces the video every visitor sees on the public homepage.
        </div>
        <div class="form-group">
          <label class="form-label">Video Title (optional)</label>
          <input type="text" name="video_title" class="form-control" placeholder="e.g. CIT Extension Training — 2026 Highlights" value="<?php echo e($homepageVideo?->video_title); ?>"/>
        </div>
        <div class="form-group">
          <label class="form-label">Video URL <span style="color:var(--red)">*</span></label>
          <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=…" value="<?php echo e($homepageVideo?->video_url); ?>" required/>
          <div class="form-hint">YouTube, Google Drive, Vimeo, or any public video URL</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('uploadHomepageVideo')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Link</button>
      </div>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.ec', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/dashboard.blade.php ENDPATH**/ ?>