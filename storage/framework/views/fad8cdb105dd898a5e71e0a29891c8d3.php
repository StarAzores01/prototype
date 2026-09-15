<?php $__env->startSection('content'); ?>
<?php if($mode === 'detail'): ?>

<?php $r = \App\Http\Controllers\Ec\ProgramController::rollup($viewProgram); ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i>
      <a href="<?php echo e(route('ec.programs')); ?>" style="color:var(--blue-primary)">Programs</a>
      <i class="fas fa-chevron-right"></i> <span><?php echo e($viewProgram->title); ?></span>
    </div>
    <h1><?php echo e($viewProgram->title); ?></h1>
    <p><?php echo e($viewProgram->area); ?></p>
  </div>
  <div class="page-header-actions">
    <a href="<?php echo e(route('ec.programs')); ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <button class="btn btn-outline" onclick="openModal('manageTeam')"><i class="fas fa-users-gear"></i> Manage Team</button>
    <button class="btn btn-primary" onclick="openModal('extendTimeline')"><i class="fas fa-calendar-plus"></i> Extend Timeline</button>
  </div>
</div>

<!-- Program Cover Image -->
<div class="card" style="margin-bottom:24px;overflow:hidden;padding:0">
  <div style="position:relative;height:220px">
    <?php if($viewProgram->cover_image): ?>
      <img src="<?php echo e(route('files.program-cover', $viewProgram)); ?>" alt="<?php echo e($viewProgram->title); ?>" style="width:100%;height:100%;object-fit:cover;display:block"/>
    <?php else: ?>
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
        <i class="fas <?php echo e(\App\Support\TrainingCategoryIcon::icon($viewProgram->area)); ?>" style="font-size:64px;color:rgba(255,255,255,.85)"></i>
      </div>
    <?php endif; ?>
    <div style="position:absolute;bottom:12px;right:12px;display:flex;gap:8px">
      <button class="btn btn-sm btn-outline" style="background:rgba(255,255,255,.94)" onclick="openModal('uploadProgramCover')">
        <i class="fas fa-camera"></i> Change Cover
      </button>
    </div>
  </div>
</div>

<!-- Info Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
    <div class="stat-body">
      <form method="POST" action="<?php echo e(route('ec.programs.store')); ?>" style="margin:0 0 2px">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_status"/>
        <input type="hidden" name="program_id" value="<?php echo e($viewProgram->id); ?>"/>
        <select name="status" onchange="this.form.submit()"
          style="font-size:16px;font-weight:700;color:var(--gray-800);border:none;background:transparent;padding:0;cursor:pointer">
          <?php $__currentLoopData = ['Proposed', 'Approved', 'Ongoing', 'Completed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($s); ?>" <?php echo e($viewProgram->status === $s ? 'selected' : ''); ?>><?php echo e($s); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </form>
      <div class="stat-label">Status</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($r['completed']); ?> / <?php echo e($r['total']); ?></div><div class="stat-label">Activities Completed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon <?php echo e($r['budgetRemain'] < 0 ? 'red' : 'green'); ?>"><i class="fas fa-sack-dollar"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:15px">&#8369;<?php echo e(number_format($r['budgetRemain'], 2)); ?></div><div class="stat-label">Budget Remaining</div></div>
  </div>
</div>

<!-- Documents: this Program's own general repository, scoped via program_id — moved up front so it's visible without scrolling -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title">Documents</div>
    <button class="btn btn-ghost btn-sm" onclick="openModal('uploadProgramDoc')"><i class="fas fa-upload"></i> Upload</button>
  </div>
  <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
    <?php $linkIcon = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square']; ?>
    <?php $__empty_1 = true; $__currentLoopData = $viewDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <a href="<?php echo e($d->isLink() ? $d->link_url : route('files.document', $d)); ?>" target="_blank" rel="noopener" class="upload-item" style="text-decoration:none;color:inherit">
      <div class="upload-item-icon <?php echo e($d->isLink() ? 'img' : 'doc'); ?>">
        <i class="<?php echo e($d->isLink() ? ($linkIcon[$d->link_type] ?? 'fa-solid fa-link') : 'fa-solid fa-file'); ?>"></i>
      </div>
      <div class="upload-item-body">
        <div class="upload-item-name"><?php echo e($d->original_name); ?></div>
        <div class="upload-item-meta"><?php echo e($d->uploader->full_name ?? ''); ?> &middot; <?php echo e($d->created_at?->format('M d, Y')); ?></div>
      </div>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="empty-state" style="padding:16px"><i class="fas fa-folder-open"></i><p>No documents in this program's repository yet.</p></div>
    <?php endif; ?>
  </div>
</div>

<!-- Progress Card -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-chart-line"></i> Program Progress</div>
      <div class="card-subtitle">Activity completion and budget utilization across the whole program</div>
    </div>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:28px">

    <!-- Activity completion -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--text-heading)"><i class="fas fa-list-check"></i> Activities Completed</span>
        <span style="font-size:13px;font-weight:700;color:<?php echo e($r['progressPct'] >= 100 ? '#10B981' : 'var(--blue-primary)'); ?>"><?php echo e($r['progressPct']); ?>%</span>
      </div>
      <?php if($r['total'] > 0): ?>
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:<?php echo e($r['progressPct']); ?>%;background:<?php echo e($r['progressPct'] >= 100 ? '#10B981' : '#1A56DB'); ?>;transition:width .4s"></div>
      </div>
      <div style="font-size:12px;color:var(--gray-500)"><?php echo e($r['completed']); ?> of <?php echo e($r['total']); ?> activities marked Completed</div>
      <?php else: ?>
      <div style="color:var(--gray-400);font-size:13px;padding:12px 0">No activities under this program yet.</div>
      <?php endif; ?>
    </div>

    <!-- Budget -->
    <div>
      <?php $budgetPct = $r['budgetAlloc'] > 0 ? min(100, round($r['budgetUsed'] / $r['budgetAlloc'] * 100, 1)) : 0; ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--text-heading)"><i class="fas fa-sack-dollar"></i> Budget Utilization</span>
        <span style="font-size:13px;font-weight:700;color:<?php echo e($budgetPct >= 100 ? '#EF4444' : ($budgetPct >= 80 ? '#F59E0B' : '#10B981')); ?>"><?php echo e($budgetPct); ?>%</span>
      </div>
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:<?php echo e($budgetPct); ?>%;background:<?php echo e($budgetPct >= 100 ? '#EF4444' : ($budgetPct >= 80 ? '#F59E0B' : '#10B981')); ?>;transition:width .4s"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Used: <strong style="color:var(--gray-800)">&#8369;<?php echo e(number_format($r['budgetUsed'], 2)); ?></strong></span>
        <span>Allocated: <strong style="color:var(--gray-800)">&#8369;<?php echo e(number_format($r['budgetAlloc'], 2)); ?></strong></span>
      </div>
      <div style="margin-top:8px;font-size:12px;color:<?php echo e($r['budgetRemain'] < 0 ? '#EF4444' : 'var(--gray-500)'); ?>">
        <?php if($r['budgetRemain'] >= 0): ?>
          Remaining: <strong>&#8369;<?php echo e(number_format($r['budgetRemain'], 2)); ?></strong>
        <?php else: ?>
          <strong>Over by &#8369;<?php echo e(number_format(abs($r['budgetRemain']), 2)); ?></strong>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <!-- Description -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-circle" style="font-size:6px"></i> Description</div></div>
      <div class="card-body">
        <?php if($viewProgram->description): ?>
          <p style="font-size:14px;color:var(--gray-700);line-height:1.8"><?php echo nl2br(e($viewProgram->description)); ?></p>
        <?php else: ?>
          <p style="color:var(--gray-400);font-size:13px">No description provided.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Activities -->
    <div class="card">
      <div class="card-header">
        <div><div class="card-title"><i class="fas fa-book"></i> Activities</div></div>
        <button class="btn btn-sm btn-primary" onclick="openModal('addActivity')"><i class="fas fa-plus"></i> Add Activity</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th></th><th>#</th><th>Title</th><th>Status</th><th>Budget Used</th><th></th></tr></thead>
          <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $viewActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td>
                <div style="width:44px;height:44px;border-radius:8px;overflow:hidden;flex-shrink:0">
                  <?php if($a->cover_image): ?>
                    <img src="<?php echo e(route('files.activity-cover', $a)); ?>" alt="<?php echo e($a->title); ?>" style="width:100%;height:100%;object-fit:cover;display:block"/>
                  <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
                      <i class="fas <?php echo e(\App\Support\TrainingCategoryIcon::icon($a->area)); ?>" style="font-size:16px;color:rgba(255,255,255,.85)"></i>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
              <td style="color:var(--gray-400)"><?php echo e($loop->iteration); ?></td>
              <td><strong><?php echo e($a->title); ?></strong></td>
              <td><span class="badge badge-<?php echo e(strtolower($a->status)); ?>"><?php echo e($a->status); ?></span></td>
              <td>&#8369;<?php echo e(number_format((float) $a->budget_used, 2)); ?></td>
              <td><a href="<?php echo e(route('ec.trainings')); ?>?view=<?php echo e($a->id); ?>" class="btn btn-sm btn-outline">View</a></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">No activities assigned to this program yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Extension History -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Extension History</div></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Field</th><th>Old Value</th><th>New Value</th><th>Remark</th><th>By</th></tr></thead>
          <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $viewAmendments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $am): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td style="font-size:12px;color:var(--gray-500)"><?php echo e($am->created_at->format('M d, Y g:i A')); ?></td>
              <td><span class="badge badge-approved"><?php echo e(ucfirst($am->field_changed)); ?></span></td>
              <td style="font-size:12px"><?php echo e($am->old_value); ?></td>
              <td style="font-size:12px;font-weight:600"><?php echo e($am->new_value); ?></td>
              <td style="font-size:12px;color:var(--gray-600)"><?php echo e($am->remark); ?></td>
              <td style="font-size:12px"><?php echo e($am->amendedBy->full_name ?? '—'); ?></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">No amendments have been made to this program.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="dash-side">
    <!-- Program Details (read-only — budget and the original timeline are permanently fixed at creation, see Extend Timeline above) -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Program Details</div>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        <?php
          $effectiveEnd = $viewProgram->effective_end_date;
          $timelineEndVal = e($effectiveEnd?->format('Y-m-d') ?? '—');
          if ($viewProgram->extended_end_date) {
            $timelineEndVal .= ' <span style="color:var(--gray-400);font-weight:400">(originally '.e($viewProgram->timeline_end->format('M d, Y')).')</span>';
          }
          $details = [
            ['Area / Specialization', e($viewProgram->area ?? '—')],
            ['Timeline Start', e($viewProgram->timeline_start?->format('Y-m-d') ?? '—')],
            ['Timeline End', $timelineEndVal],
            ['Budget Allocated', '₱'.number_format((float) $viewProgram->budget_allocated, 2)],
            ['Created By', e($viewProgram->creator->full_name ?? '—')],
            ['Created', e($viewProgram->created_at?->format('M d, Y') ?? '—')],
          ];
        ?>
        <?php $__currentLoopData = $details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $val]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;align-items:flex-start;gap:12px">
          <div><div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px"><?php echo e($label); ?></div><div style="font-size:13px;color:var(--gray-800);font-weight:500;margin-top:2px"><?php echo $val; ?></div></div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

    <!-- Team -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Program Team</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <?php $__empty_1 = true; $__currentLoopData = $viewProgram->lead; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lead): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="upload-item">
          <div class="upload-item-icon doc"><i class="fas fa-user-tie"></i></div>
          <div class="upload-item-body">
            <div class="upload-item-name"><?php echo e($lead->full_name); ?></div>
            <div class="upload-item-meta">Project Lead</div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty-state" style="padding:16px"><i class="fas fa-user-tie"></i><p>No lead assigned.</p></div>
        <?php endif; ?>
        <?php $__currentLoopData = $viewProgram->members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="upload-item">
          <div class="upload-item-icon img"><i class="fas fa-user"></i></div>
          <div class="upload-item-body">
            <div class="upload-item-name"><?php echo e($member->full_name); ?></div>
            <div class="upload-item-meta">Team Member</div>
          </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: EXTEND TIMELINE -->
<div class="modal-overlay" id="modal-extendTimeline">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h2><i class="fas fa-calendar-plus"></i> Extend Timeline</h2>
      <button class="modal-close" onclick="closeModal('extendTimeline')"><i class="fas fa-xmark"></i></button>
    </div>
    <?php $effectiveEnd = $viewProgram->effective_end_date; ?>
    <form method="POST" action="<?php echo e(route('ec.programs.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="extend_timeline"/>
      <input type="hidden" name="program_id" value="<?php echo e($viewProgram->id); ?>"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> The program's original timeline and budget are permanently fixed. This only pushes the effective end date further out — it never edits or replaces the original.
        </div>
        <div class="form-group">
          <label class="form-label">Current Effective End Date</label>
          <div style="padding:9px 12px;border-radius:var(--radius-sm);background:var(--gray-50);font-size:13px;font-weight:600;color:var(--gray-700)"><?php echo e($effectiveEnd?->format('M d, Y') ?? '—'); ?></div>
        </div>
        <div class="form-group">
          <label class="form-label">New End Date *</label>
          <input type="date" name="new_end_date" class="form-control"
                 min="<?php echo e($effectiveEnd ? $effectiveEnd->copy()->addDay()->format('Y-m-d') : ''); ?>" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Remark (required) *</label>
          <textarea name="remark" class="form-control" rows="3" placeholder="Explain why this extension is needed…" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('extendTimeline')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Extension</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: MANAGE TEAM -->
<?php
  $currentLeadId = optional($viewProgram->lead->first())->id;
  $currentMembers = $viewProgram->members->values();
?>
<div class="modal-overlay" id="modal-manageTeam">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-users-gear"></i> Manage Team</h2>
      <button class="modal-close" onclick="closeModal('manageTeam')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.programs.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="update_team"/>
      <input type="hidden" name="program_id" value="<?php echo e($viewProgram->id); ?>"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> This replaces the program's current team. It is not a protected field — no remark needed, no amendment logged.
        </div>
        <div class="form-group">
          <label class="form-label">Project Lead * <span style="font-weight:400;color:var(--gray-400)">(exactly one, from Project Leaders)</span></label>
          <select name="lead_id" class="form-control" required>
            <option value="">— Select Project Lead —</option>
            <?php $__currentLoopData = $trainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($tr->id); ?>" <?php echo e((int) $currentLeadId === $tr->id ? 'selected' : ''); ?>><?php echo e($tr->first_name); ?> <?php echo e($tr->last_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Team Members <span style="font-weight:400;color:var(--gray-400)">(optional, up to 3)</span></label>
          <div class="form-row" style="grid-template-columns:1fr 1fr 1fr">
            <?php for($i = 0; $i < 3; $i++): ?>
            <?php $cur = $currentMembers->get($i); ?>
            <select name="member_ids[]" class="form-control">
              <option value="">— None —</option>
              <?php $__currentLoopData = $trainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($tr->id); ?>" <?php echo e($cur && $cur->id === $tr->id ? 'selected' : ''); ?>><?php echo e($tr->first_name); ?> <?php echo e($tr->last_name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php endfor; ?>
          </div>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">A team member can't also be the Project Lead, and can't be selected twice.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('manageTeam')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Team</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: ADD ACTIVITY (scoped to this program) -->
<div class="modal-overlay" id="modal-addActivity">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Add Activity</h2>
      <button class="modal-close" onclick="closeModal('addActivity')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.trainings.store')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <?php echo $__env->make('ec.partials.activity-create-fields', ['trainers' => $trainers, 'scopedProgram' => $viewProgram], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addActivity')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Activity</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: UPLOAD PROGRAM DOCUMENT -->
<div class="modal-overlay" id="modal-uploadProgramDoc">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-upload"></i> Upload Document</h2>
      <button class="modal-close" onclick="closeModal('uploadProgramDoc')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <?php echo $__env->make('partials.document-upload-form', [
        'actionRoute' => route('ec.programs.store'),
        'idSuffix'    => 'Program',
        'scopeField'  => ['name' => 'program_id', 'value' => $viewProgram->id],
        'submitLabel' => 'Save Document',
      ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
  </div>
</div>

<!-- MODAL: CHANGE PROGRAM COVER IMAGE -->
<div class="modal-overlay" id="modal-uploadProgramCover">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2><i class="fas fa-camera"></i> Change Program Cover</h2>
      <button class="modal-close" onclick="closeModal('uploadProgramCover')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?php echo e(route('ec.programs.store')); ?>" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="upload_cover"/>
      <input type="hidden" name="program_id" value="<?php echo e($viewProgram->id); ?>"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Cover Image <span style="color:var(--red)">*</span></label>
          <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" required/>
          <div class="form-hint">JPG, PNG, GIF, or WEBP — max 5 MB</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('uploadProgramCover')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="pathriveRequestImageUpload(this.form, { title: 'Upload this cover image?' })"><i class="fas fa-check"></i> Save Cover</button>
      </div>
    </form>
  </div>
</div>

<?php else: ?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Programs</span></div>
    <h1>Programs</h1>
    <p>The programs each extension activity is organized under</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addProgram')"><i class="fas fa-plus"></i> Create Program</button>
</div>

<?php if($programs->isEmpty()): ?>
<div class="empty-state"><i class="fas fa-diagram-project"></i><p>No programs yet. <a href="#" onclick="openModal('addProgram')">Create one.</a></p></div>
<?php else: ?>
<?php $__currentLoopData = $programsByArea; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area => $areaPrograms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="card-group">
    <div class="card-group-header">
      <div class="card-group-title"><i class="fas fa-layer-group"></i> <?php echo e($area); ?></div>
      <span class="card-group-count"><?php echo e($areaPrograms->count()); ?> <?php echo e(\Illuminate\Support\Str::plural('project', $areaPrograms->count())); ?></span>
    </div>
    <div class="home-grid home-grid-grouped">
      <?php $__currentLoopData = $areaPrograms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $pr = \App\Http\Controllers\Ec\ProgramController::rollup($p);
          $icon = \App\Support\TrainingCategoryIcon::icon($p->area);
        ?>
        <div class="training-card">
          <div class="training-card-img" style="background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
            <?php if($p->cover_image): ?>
              <img src="<?php echo e(route('files.program-cover', $p)); ?>" alt="<?php echo e($p->title); ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;z-index:0"/>
            <?php endif; ?>
            <div class="training-card-cat" style="z-index:2;position:relative"><?php echo e($p->area); ?></div>
            <?php if(!$p->cover_image): ?>
              <i class="fas <?php echo e($icon); ?>" style="z-index:1;position:relative;font-size:40px"></i>
            <?php endif; ?>
          </div>
          <div class="training-card-body">
            <div class="training-card-title"><?php echo e($p->title); ?></div>
            <div class="training-card-desc"><?php echo e(\Illuminate\Support\Str::limit($p->description ?? '', 100, '…')); ?></div>
            <div class="training-card-meta">
              <span><i class="fas fa-user-tie"></i> <?php echo e(optional($p->lead->first())->full_name ?? 'No lead'); ?></span>
              <span><i class="fas fa-book"></i> <?php echo e($pr['completed']); ?>/<?php echo e($pr['total']); ?> done</span>
            </div>
            <div style="background:var(--gray-100);border-radius:8px;height:8px;overflow:hidden;margin-bottom:10px">
              <div style="height:100%;border-radius:8px;width:<?php echo e($pr['progressPct']); ?>%;background:<?php echo e($pr['progressPct'] >= 100 ? '#10B981' : '#1A56DB'); ?>"></div>
            </div>
            <div style="font-size:11.5px;color:var(--gray-500);margin-bottom:12px">
              Remaining: <strong style="color:<?php echo e($pr['budgetRemain'] < 0 ? '#EF4444' : 'var(--gray-800)'); ?>">&#8369;<?php echo e(number_format($pr['budgetRemain'], 2)); ?></strong>
              of &#8369;<?php echo e(number_format($pr['budgetAlloc'], 2)); ?>

            </div>
            <div class="training-card-footer">
              <span class="badge badge-<?php echo e(strtolower($p->status)); ?>"><?php echo e($p->status); ?></span>
              <a href="<?php echo e(route('ec.programs')); ?>?view=<?php echo e($p->id); ?>" class="btn btn-sm btn-primary">View Details</a>
            </div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>

<!-- MODAL: CREATE PROGRAM -->
<div class="modal-overlay" id="modal-addProgram">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Create New Program</h2>
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
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Program</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.ec', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/programs.blade.php ENDPATH**/ ?>