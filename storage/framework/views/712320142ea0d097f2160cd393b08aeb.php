
<?php
  $storeRoute    = $storeRoute ?? '';
  $archived      = $archived ?? false;
  $showUploader  = $showUploader ?? true;
  $canManage     = $canManage ?? true;
  $showLinkedTo  = $showLinkedTo ?? false;

  $visColor = ['private' => 'var(--red)', 'ec_trainer' => 'var(--blue-primary)', 'public' => 'var(--green)'];
  $linkIcon = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square'];

  $today     = now()->startOfDay();
  $yesterday = $today->copy()->subDay();
  $dateGrouped = $documents
    ->groupBy(function ($d) use ($today, $yesterday) {
      $ts = $d->created_at;
      if (! $ts) return 'Unknown Date';
      // ->copy() first — Carbon's startOfDay() mutates in place, and $ts
      // is the same object as $d->created_at; without the copy, grouping
      // silently zeroed every document's stored upload time to midnight,
      // which then showed as "12:00 AM" in the Time column below.
      $day = $ts->copy()->startOfDay();
      if ($day->eq($today))     return 'Today';
      if ($day->eq($yesterday)) return 'Yesterday';
      return $ts->format('F j, Y');
    })
    // Order the date groups themselves newest-first, by each group's most
    // recent document — guarantees Today, then Yesterday, then older dates
    // regardless of the order $documents arrived in, rather than relying
    // on it already being pre-sorted by the caller.
    ->sortByDesc(fn ($group) => optional($group->max('created_at'))->timestamp ?? 0);
?>

<?php $__currentLoopData = $dateGrouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dateLabel => $dateGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <div class="doc-date-group-header" style="font-size:11px;opacity:.85">
    <i class="fas fa-calendar-alt" style="margin-right:6px;opacity:.6"></i><?php echo e($dateLabel); ?>

    <span style="font-weight:400;margin-left:8px;opacity:.7">(<?php echo e($dateGroup->count()); ?> file<?php echo e($dateGroup->count() === 1 ? '' : 's'); ?>)</span>
  </div>
  <div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Document</th>
        <th>Type</th>
        <?php if($showLinkedTo): ?><th class="doc-table-linked-to">Linked To</th><?php endif; ?>
        <?php if($canManage): ?><th>Visibility</th><?php endif; ?>
        <?php if($showUploader): ?><th class="doc-table-uploaded-by">Uploaded By</th><?php endif; ?>
        <th>Time</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $dateGroup; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
        $vis = $d->visibility ?? 'public';
        $openUrl = $d->isLink() ? $d->link_url : route('files.document', $d);
      ?>
      <tr>
        <td>
          <a href="<?php echo e($openUrl); ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;min-width:0;text-decoration:none;color:inherit">
            <?php if($d->isLink()): ?>
              <span style="width:32px;height:32px;border-radius:8px;background:var(--blue-soft);color:var(--blue-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                <i class="<?php echo e($linkIcon[$d->link_type] ?? 'fa-solid fa-link'); ?>"></i>
              </span>
            <?php else: ?>
              <?php
                $ico = match(strtolower($d->file_type ?? '')) {
                  'pdf'  => ['fa-file-pdf',  '#FEE2E2', '#EF4444'],
                  'doc','docx' => ['fa-file-word', '#DBEAFE', '#1A56DB'],
                  'xls','xlsx' => ['fa-file-excel','#D1FAE5','#10B981'],
                  'jpg','jpeg','png','gif','webp' => ['fa-file-image','#E0E7FF','#6366F1'],
                  'mp4' => ['fa-file-video','#FEF3C7','#F59E0B'],
                  default => ['fa-file','#F1F5F9','#64748B'],
                };
              ?>
              <span style="width:32px;height:32px;border-radius:8px;background:<?php echo e($ico[1]); ?>;color:<?php echo e($ico[2]); ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                <i class="fas <?php echo e($ico[0]); ?>"></i>
              </span>
            <?php endif; ?>
            <div style="min-width:0">
              <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px" title="<?php echo e($d->original_name); ?>"><?php echo e($d->original_name); ?></div>
              <?php if($archived): ?>
                <div style="font-size:11px;color:var(--gray-400)">Archived by <?php echo e($d->archivedBy->full_name ?? '—'); ?> &middot; <?php echo e($d->archived_at?->format('M d, Y') ?? '—'); ?></div>
              <?php endif; ?>
            </div>
          </a>
        </td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)"><?php echo e($d->isLink() ? $d->link_type : $d->file_type); ?></span></td>
        <?php if($showLinkedTo): ?>
        <td class="doc-table-linked-to" style="font-size:12px;color:var(--gray-600)">
          <?php if($d->program): ?>
            <i class="fas fa-diagram-project" style="color:var(--gray-400)"></i> <?php echo e(Str::limit($d->program->title, 28)); ?>

          <?php elseif($d->activity): ?>
            <i class="fas fa-book" style="color:var(--gray-400)"></i> <?php echo e(Str::limit($d->activity->title, 28)); ?>

          <?php else: ?>
            <?php echo e(Str::limit($d->training->title ?? 'General', 28)); ?>

          <?php endif; ?>
        </td>
        <?php endif; ?>
        <?php if($canManage): ?>
        <td>
          <form method="POST" action="<?php echo e($storeRoute); ?>" style="display:inline">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="set_visibility"/>
            <input type="hidden" name="doc_id" value="<?php echo e($d->id); ?>"/>
            <select name="visibility" class="filter-select" style="font-size:12px;padding:4px 8px;border-radius:6px;color:<?php echo e($visColor[$vis]); ?>" onchange="this.form.submit()">
              <option value="private" <?php echo e($vis === 'private' ? 'selected' : ''); ?>>Private</option>
              <option value="ec_trainer" <?php echo e($vis === 'ec_trainer' ? 'selected' : ''); ?>>EC &amp; Project Leaders</option>
              <option value="public" <?php echo e($vis === 'public' ? 'selected' : ''); ?>>Public</option>
            </select>
          </form>
        </td>
        <?php endif; ?>
        <?php if($showUploader): ?>
        <td class="doc-table-uploaded-by" style="font-size:12px"><?php echo e($d->uploader?->full_name ?? '—'); ?></td>
        <?php endif; ?>
        <td style="font-size:12px;color:var(--gray-400);white-space:nowrap"><?php echo e($d->created_at?->format('g:i A') ?? '—'); ?></td>
        <td>
          <div class="action-btns">
            <?php if($d->isLink()): ?>
            <a href="<?php echo e($d->link_url); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline" title="Open link"><i class="fas fa-arrow-up-right-from-square"></i></a>
            <?php else: ?>
            <a href="<?php echo e(route('files.document', $d)); ?>?download=1" class="btn btn-sm btn-outline" title="Download"><i class="fas fa-arrow-down"></i></a>
            <a href="<?php echo e(route('files.document', $d)); ?>" target="_blank" class="btn btn-sm btn-outline" title="Preview"><i class="fas fa-eye"></i></a>
            <?php endif; ?>
            <?php if($canManage): ?>
              <?php if($archived): ?>
              <button type="button" class="btn btn-sm btn-outline" onclick="submitDocAction('unarchive', <?php echo e($d->id); ?>, '', false)" title="Restore"><i class="fas fa-rotate-left"></i></button>
              <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline" onclick="submitDocAction('archive', <?php echo e($d->id); ?>, '', false)" title="Archive"><i class="fas fa-box-archive"></i></button>
              <?php endif; ?>
              <button type="button" class="btn btn-sm btn-danger" onclick="submitDocAction('delete', <?php echo e($d->id); ?>, '<?php echo e(addslashes($d->original_name)); ?>', true)" title="Delete"><i class="fas fa-trash"></i></button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
  </table>
  </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/partials/document-rows-table.blade.php ENDPATH**/ ?>