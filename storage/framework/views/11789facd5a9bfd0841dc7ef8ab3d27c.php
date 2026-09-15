<?php $__env->startSection('content'); ?>
<style>
.rpt-section { margin-bottom: 40px; }
.rpt-section-header {
  display: flex; align-items: center; gap: 12px;
  padding: 0 0 14px; margin-bottom: 20px;
  border-bottom: 2px solid var(--gray-100);
}
.rpt-section-ico {
  width: 40px; height: 40px; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}
.rpt-section-ico.blue   { background: rgba(26,86,219,.1); }
.rpt-section-ico.green  { background: rgba(16,185,129,.1); }
.rpt-section-ico.purple { background: rgba(139,92,246,.1); }
.rpt-section-ico.orange { background: rgba(245,158,11,.1); }
.rpt-section-title { font-size: 17px; font-weight: 800; color: var(--text-heading); }
.rpt-section-sub   { font-size: 12.5px; color: var(--gray-400); margin-top: 2px; }

/* chart row */
.chart-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:860px){ .chart-row { grid-template-columns: 1fr; } }
.chart-card {
  background: var(--surface); border: 1px solid var(--gray-200); border-radius: 14px; padding: 20px;
}
.chart-card-title { font-size: 13px; font-weight: 700; color: var(--text-heading); margin-bottom: 16px; }
canvas { max-height: 220px; }

/* bar summary */
.bar-summary { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: flex; align-items: center; gap: 10px; }
.bar-label { min-width: 160px; font-size: 12.5px; color: var(--gray-700); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bar-track { flex: 1; background: var(--gray-100); border-radius: 4px; height: 8px; }
.bar-fill  { height: 8px; border-radius: 4px; }
.bar-count { min-width: 52px; font-size: 12px; color: var(--gray-500); text-align: right; }

/* skills/eval accordion */
.survey-block {
  background: var(--surface); border: 1px solid var(--gray-200); border-radius: 14px;
  margin-bottom: 14px; overflow: hidden;
}
.survey-block-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; cursor: pointer; user-select: none;
  transition: background .18s;
}
.survey-block-header:hover { background: var(--gray-50); }
.survey-block-title { font-size: 14px; font-weight: 700; color: var(--text-heading); }
.survey-block-meta  { font-size: 12px; color: var(--gray-400); margin-top: 2px; }
.survey-block-body  { padding: 0 20px 20px; border-top: 1px solid var(--gray-100); display: none; }
.survey-block-body.open { display: block; }
.survey-q { margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid var(--gray-100); }
.survey-q:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.survey-q-label { font-size: 13px; font-weight: 700; color: var(--text-heading); margin-bottom: 10px; }
.survey-q-type  { font-size: 11px; color: var(--gray-400); font-weight: 400; margin-left: 6px; }
.text-answers   { display: flex; flex-direction: column; gap: 6px; }
.text-answer    { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: 8px 12px; font-size: 13px; color: var(--gray-700); }
.no-resp        { font-size: 13px; color: var(--gray-400); font-style: italic; }

/* response rate pill */
.rate-pill {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;
}
.rate-pill.high   { background: rgba(16,185,129,.1); color: #065F46; }
.rate-pill.medium { background: rgba(245,158,11,.1);  color: #92400E; }
.rate-pill.low    { background: rgba(239,68,68,.1);   color: #991B1B; }
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Reports</span></div>
    <h1>Reports</h1>
    <p>Comprehensive overview of activity accomplishments, beneficiaries, skills utilization, and evaluations</p>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     TOP SUMMARY STATS
════════════════════════════════════════════════════════════ -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:36px">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($totalTrainings); ?></div><div class="stat-label">Total Activities</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($completedCount); ?></div><div class="stat-label">Completed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($ongoingCount); ?></div><div class="stat-label">Ongoing</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-lightbulb"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($proposedCount); ?></div><div class="stat-label">Proposed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($totalPart); ?></div><div class="stat-label">Total Participants</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
    <div class="stat-body"><div class="stat-value"><?php echo e($totalBen); ?></div><div class="stat-label">Registered Beneficiaries</div></div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 1 — ACTIVITY ACCOMPLISHMENTS
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico blue"><i class="fas fa-book"></i></div>
    <div>
      <div class="rpt-section-title">Activity Accomplishments</div>
      <div class="rpt-section-sub">All activities and their current status</div>
    </div>
  </div>

  <div class="chart-row">
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-chart-column"></i> Activities by Status</div>
      <canvas id="chartStatus"></canvas>
    </div>
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-book"></i> Activities by Technology Area</div>
      <div style="position:relative;height:<?php echo e(max(180, count($byArea) * 36)); ?>px">
        <canvas id="chartArea"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 2 — BENEFICIARY REPORT
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico green"><i class="fas fa-users"></i></div>
    <div>
      <div class="rpt-section-title">Beneficiary Report</div>
      <div class="rpt-section-sub">All registered beneficiaries and their activity enrollment</div>
    </div>
  </div>

  <div class="chart-row" style="grid-template-columns:1fr">
    <div class="chart-card">
      <div class="chart-card-title"><i class="fas fa-book"></i> Participants per Activity</div>
      <?php if($partPerTraining->isEmpty()): ?>
      <div style="text-align:center;padding:40px;color:var(--gray-400);font-size:13px">No data yet.</div>
      <?php else: ?>
      <div style="position:relative;height:<?php echo e(max(180, count($partPerTraining) * 36)); ?>px">
        <canvas id="chartPartPerTraining"></canvas>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Address</th>
          <th>Activities Enrolled</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $beneficiaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td style="color:var(--gray-400);font-size:12px"><?php echo e($loop->iteration); ?></td>
          <td><strong><?php echo e($b->full_name); ?></strong></td>
          <td style="font-size:12.5px;color:var(--gray-500)"><?php echo e($b->email); ?></td>
          <td style="font-size:12.5px;color:var(--gray-600)"><?php echo e($b->address ?? '—'); ?></td>
          <td>
            <strong><?php echo e((int) $b->enrolled_count); ?></strong>
            <?php if($b->trainings_list): ?>
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px"><?php echo e(\Illuminate\Support\Str::limit($b->trainings_list, 80, '…')); ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--gray-400)"><?php echo e($b->created_at->format('M d, Y')); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gray-400)">No beneficiaries found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 3 — SKILLS UTILIZATION
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico purple"><i class="fas fa-chart-line"></i></div>
    <div>
      <div class="rpt-section-title">Skills Utilization</div>
      <div class="rpt-section-sub">Aggregated beneficiary responses to post-activity skills surveys</div>
    </div>
  </div>

  <!-- Skills Utilization Charts removed -->

  <?php if($skillsForms->isEmpty()): ?>
  <div class="card"><div style="text-align:center;padding:48px;color:var(--gray-400)">No skills surveys have been sent yet.</div></div>
  <?php else: ?>
  <?php $__currentLoopData = $skillsForms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
      $sfFields = $sf->fields ?? [];
      $sfTotal  = $sf->responses->count();
      $sfRate   = $sf->total_pax > 0 ? round($sf->responses->count() / $sf->total_pax * 100) : 0;
      $sfRateClass = $sfRate >= 70 ? 'high' : ($sfRate >= 40 ? 'medium' : 'low');
    ?>
  <div class="survey-block">
    <div class="survey-block-header" onclick="toggleSurvey('sk-<?php echo e($sf->id); ?>')">
      <div>
        <div class="survey-block-title"><?php echo e($sf->title); ?></div>
        <div class="survey-block-meta">
          <i class="fas fa-book"></i> <?php echo e($sf->training->title ?? ''); ?>

          &nbsp;·&nbsp; <?php echo e($sfTotal); ?> / <?php echo e($sf->total_pax); ?> responded
          &nbsp;·&nbsp; <span class="rate-pill <?php echo e($sfRateClass); ?>"><?php echo e($sfRate); ?>% response rate</span>
        </div>
      </div>
      <span style="font-size:18px;color:var(--gray-400)" id="sk-<?php echo e($sf->id); ?>-arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="survey-block-body" id="sk-<?php echo e($sf->id); ?>">
      <?php if($sfTotal === 0): ?>
      <div class="no-resp" style="padding:16px 0">No responses submitted yet.</div>
      <?php else: ?>
      <?php $__currentLoopData = $sfFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fi => $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $tally = [];
          foreach ($sf->responses as $r) {
              $val = trim($r->responses[$fi] ?? '');
              if ($val !== '') $tally[$val] = ($tally[$val] ?? 0) + 1;
          }
          arsort($tally);
        ?>
      <div class="survey-q">
        <div class="survey-q-label">
          <?php echo e($fi + 1); ?>. <?php echo e($field['label']); ?>

          <span class="survey-q-type">(<?php echo e(ucfirst($field['type'])); ?>)</span>
        </div>
        <?php if(in_array($field['type'], ['radio','select']) && !empty($tally)): ?>
        <div class="bar-summary">
          <?php $__currentLoopData = $tally; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt => $cnt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $pct = round($cnt / $sfTotal * 100); ?>
          <div class="bar-row">
            <div class="bar-label" title="<?php echo e($opt); ?>"><?php echo e($opt); ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?php echo e($pct); ?>%;background:#8B5CF6"></div></div>
            <div class="bar-count"><?php echo e($cnt); ?> (<?php echo e($pct); ?>%)</div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php elseif(!empty($tally)): ?>
        <div class="text-answers">
          <?php $__currentLoopData = array_keys($tally); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ans): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="text-answer"><?php echo e($ans); ?></div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
        <div class="no-resp">No answers recorded.</div>
        <?php endif; ?>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SECTION 4 — EVALUATION SUMMARY
════════════════════════════════════════════════════════════ -->
<div class="rpt-section">
  <div class="rpt-section-header">
    <div class="rpt-section-ico orange"><i class="fas fa-star"></i></div>
    <div>
      <div class="rpt-section-title">Evaluation Summary</div>
      <div class="rpt-section-sub">Aggregated participant feedback and satisfaction ratings per activity</div>
    </div>
  </div>

  <?php if($evalForms->isEmpty()): ?>
  <div class="card"><div style="text-align:center;padding:48px;color:var(--gray-400)">No evaluation forms have been sent yet.</div></div>
  <?php else: ?>
  <?php $__currentLoopData = $evalForms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
      $efFields = $ef->fields ?? [];
      $efTotal  = $ef->responses->count();
      $efRate   = $ef->total_pax > 0 ? round($ef->responses->count() / $ef->total_pax * 100) : 0;
      $efRateClass = $efRate >= 70 ? 'high' : ($efRate >= 40 ? 'medium' : 'low');
    ?>
  <div class="survey-block">
    <div class="survey-block-header" onclick="toggleSurvey('ev-<?php echo e($ef->id); ?>')">
      <div>
        <div class="survey-block-title"><?php echo e($ef->title); ?></div>
        <div class="survey-block-meta">
          <i class="fas fa-book"></i> <?php echo e($ef->training->title ?? ''); ?>

          &nbsp;·&nbsp; <?php echo e($efTotal); ?> / <?php echo e($ef->total_pax); ?> responded
          &nbsp;·&nbsp; <span class="rate-pill <?php echo e($efRateClass); ?>"><?php echo e($efRate); ?>% response rate</span>
        </div>
      </div>
      <span style="font-size:18px;color:var(--gray-400)" id="ev-<?php echo e($ef->id); ?>-arrow"><i class="fas fa-chevron-down"></i></span>
    </div>
    <div class="survey-block-body" id="ev-<?php echo e($ef->id); ?>">
      <?php if($efTotal === 0): ?>
      <div class="no-resp" style="padding:16px 0">No responses submitted yet.</div>
      <?php else: ?>
      <?php $__currentLoopData = $efFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fi => $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $tally = [];
          foreach ($ef->responses as $r) {
              $val = trim($r->responses[$fi] ?? '');
              if ($val !== '') $tally[$val] = ($tally[$val] ?? 0) + 1;
          }
          arsort($tally);
        ?>
      <div class="survey-q">
        <div class="survey-q-label">
          <?php echo e($fi + 1); ?>. <?php echo e($field['label']); ?>

          <span class="survey-q-type">(<?php echo e(ucfirst($field['type'])); ?>)</span>
        </div>
        <?php if(in_array($field['type'], ['radio','select','rating']) && !empty($tally)): ?>
        <div class="bar-summary">
          <?php $__currentLoopData = $tally; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt => $cnt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $pct = round($cnt / $efTotal * 100); ?>
          <div class="bar-row">
            <div class="bar-label" title="<?php echo e($opt); ?>"><?php echo e($opt); ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?php echo e($pct); ?>%;background:var(--yellow)"></div></div>
            <div class="bar-count"><?php echo e($cnt); ?> (<?php echo e($pct); ?>%)</div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php elseif(!empty($tally)): ?>
        <div class="text-answers">
          <?php $__currentLoopData = array_keys($tally); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ans): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="text-answer"><?php echo e($ans); ?></div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
        <div class="no-resp">No answers recorded.</div>
        <?php endif; ?>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  <?php endif; ?>
</div>

<!-- Chart.js is loaded once in layouts.ec, shared by every EC page's charts -->
<script>
// ── Status doughnut ───────────────────────────────────────────────────────
const statusData = <?php echo json_encode($byStatus->pluck('cnt')->values()); ?>;
const statusLabels = <?php echo json_encode($byStatus->pluck('status')->values()); ?>;
new Chart(document.getElementById('chartStatus'), {
  type: 'doughnut',
  data: {
    labels: statusLabels,
    datasets: [{ data: statusData,
      backgroundColor: ['#1A56DB','#10B981','#F59E0B','#8B5CF6'],
      borderWidth: 2, borderColor: '#fff' }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } }, cutout: '62%' }
});

// ── Area bar ──────────────────────────────────────────────────────────────
const areaLabels = <?php echo json_encode($byArea->pluck('area')->values()); ?>;
const areaData   = <?php echo json_encode($byArea->pluck('cnt')->values()); ?>;
new Chart(document.getElementById('chartArea'), {
  type: 'bar',
  data: {
    labels: areaLabels,
    datasets: [{ label: 'Activities', data: areaData,
      backgroundColor: 'rgba(26,86,219,.75)', borderRadius: 6, borderSkipped: false }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false }, ticks: { font: { size: 11 } } },
              y: { grid: { display: false }, ticks: { font: { size: 11 } } } }
  }
});


// ── Participants per activity (horizontal bar) ────────────────────────────
<?php if($partPerTraining->isNotEmpty()): ?>
new Chart(document.getElementById('chartPartPerTraining'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($partPerTraining->map(fn($r) => \Illuminate\Support\Str::limit($r->title, 30, '…'))->values()); ?>,
    datasets: [{
      label: 'Participants',
      data: <?php echo json_encode($partPerTraining->pluck('cnt')->values()); ?>,
      backgroundColor: Array.from({length: <?php echo e(count($partPerTraining)); ?>}, (_, i) => `hsl(${210 + i * 18}, 70%, 55%)`),
      borderRadius: 5,
      borderSkipped: false
    }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.raw} participant${ctx.raw !== 1 ? 's' : ''}` } } },
    scales: {
      x: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } }, beginAtZero: true },
      y: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }
  }
});
<?php endif; ?>

// ── Accordion toggle ──────────────────────────────────────────────────────
function toggleSurvey(id) {
  const body  = document.getElementById(id);
  const arrow = document.getElementById(id + '-arrow');
  const open  = body.classList.toggle('open');
  if (arrow) arrow.innerHTML = open ? '<i class="fas fa-chevron-up"></i>' : '<i class="fas fa-chevron-down"></i>';
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.ec', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/ec/reports.blade.php ENDPATH**/ ?>