@extends('layouts.ec')

@section('content')
@if($mode === 'detail')
{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• DETAIL VIEW â•â•â• --}}
@php $r = \App\Http\Controllers\Ec\ProgramController::rollup($viewProgram); @endphp
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i>
      <a href="{{ route('ec.programs') }}" style="color:var(--blue-primary)">Programs</a>
      <i class="fas fa-chevron-right"></i> <span>{{ $viewProgram->title }}</span>
    </div>
    <h1>{{ $viewProgram->title }}</h1>
    <p>{{ $viewProgram->area }}</p>
  </div>
  <div class="page-header-actions">
    <a href="{{ route('ec.programs') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <button class="btn btn-outline" onclick="openModal('manageTeam')"><i class="fas fa-users-gear"></i> Manage Team</button>
    <button class="btn btn-primary" onclick="openModal('extendTimeline')"><i class="fas fa-calendar-plus"></i> Extend Timeline</button>
  </div>
</div>

<!-- Program Cover Image -->
<div class="card" style="margin-bottom:24px;overflow:hidden;padding:0">
  <div style="position:relative;height:220px">
    @if($viewProgram->cover_image)
      <img src="{{ route('files.program-cover', $viewProgram) }}" alt="{{ $viewProgram->title }}" style="width:100%;height:100%;object-fit:cover;display:block"/>
    @else
      <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
        <i class="fas {{ \App\Support\TrainingCategoryIcon::icon($viewProgram->area) }}" style="font-size:64px;color:rgba(255,255,255,.85)"></i>
      </div>
    @endif
    <div style="position:absolute;bottom:12px;right:12px;display:flex;gap:8px">
      <button class="btn btn-sm btn-outline" style="background:rgba(255,255,255,.94)" onclick="openModal('uploadProgramCover')">
        <i class="fas fa-camera"></i> Change Cover
      </button>
    </div>
  </div>
</div>

<!-- Info Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
    <div class="stat-card">
      <div class="stat-icon {{ $viewProgram->status === 'Completed' ? 'green' : ($viewProgram->status === 'Ongoing' ? 'blue' : 'yellow') }}"><i class="fas fa-circle-info"></i></div>
      <div class="stat-body">
        <div style="font-size:16px;font-weight:700;color:var(--gray-800)">{{ $viewProgram->status }}</div>
        <div class="stat-label">Status</div>
      </div>
    </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $r['completed'] }} / {{ $r['total'] }}</div><div class="stat-label">Activities Completed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon {{ $r['budgetRemain'] < 0 ? 'red' : 'green' }}"><i class="fas fa-sack-dollar"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:15px">&#8369;{{ number_format($r['budgetRemain'], 2) }}</div><div class="stat-label">Budget Remaining</div></div>
  </div>
</div>

<!-- Documents: this Program's own general repository, scoped via program_id  -  moved up front so it's visible without scrolling -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title">Documents</div>
    <button class="btn btn-ghost btn-sm" onclick="openModal('uploadProgramDoc')"><i class="fas fa-upload"></i> Upload</button>
  </div>
  <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
    @php $linkIcon = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square']; @endphp
    @forelse($viewDocuments as $d)
    <a href="{{ $d->isLink() ? $d->link_url : route('files.document', $d) }}" target="_blank" rel="noopener" class="upload-item" style="text-decoration:none;color:inherit">
      <div class="upload-item-icon {{ $d->isLink() ? 'img' : 'doc' }}">
        <i class="{{ $d->isLink() ? ($linkIcon[$d->link_type] ?? 'fa-solid fa-link') : 'fa-solid fa-file' }}"></i>
      </div>
      <div class="upload-item-body">
        <div class="upload-item-name">{{ $d->original_name }}</div>
        <div class="upload-item-meta">{{ $d->uploader->full_name ?? '' }} &middot; {{ $d->created_at?->format('M d, Y') }}</div>
      </div>
    </a>
    @empty
    <div class="empty-state" style="padding:16px"><i class="fas fa-folder-open"></i><p>No documents in this program's repository yet.</p></div>
    @endforelse
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
        <span style="font-size:13px;font-weight:700;color:{{ $r['progressPct'] >= 100 ? '#10B981' : 'var(--blue-primary)' }}">{{ $r['progressPct'] }}%</span>
      </div>
      @if($r['total'] > 0)
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:{{ $r['progressPct'] }}%;background:{{ $r['progressPct'] >= 100 ? '#10B981' : '#1A56DB' }};transition:width .4s"></div>
      </div>
      <div style="font-size:12px;color:var(--gray-500)">{{ $r['completed'] }} of {{ $r['total'] }} activities marked Completed</div>
      @else
      <div style="color:var(--gray-400);font-size:13px;padding:12px 0">No activities under this program yet.</div>
      @endif
    </div>

    <!-- Budget -->
    <div>
      @php $budgetPct = $r['budgetAlloc'] > 0 ? min(100, round($r['budgetUsed'] / $r['budgetAlloc'] * 100, 1)) : 0; @endphp
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--text-heading)"><i class="fas fa-sack-dollar"></i> Budget Utilization</span>
        <span style="font-size:13px;font-weight:700;color:{{ $budgetPct >= 100 ? '#EF4444' : ($budgetPct >= 80 ? '#F59E0B' : '#10B981') }}">{{ $budgetPct }}%</span>
      </div>
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:{{ $budgetPct }}%;background:{{ $budgetPct >= 100 ? '#EF4444' : ($budgetPct >= 80 ? '#F59E0B' : '#10B981') }};transition:width .4s"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Used: <strong style="color:var(--gray-800)">&#8369;{{ number_format($r['budgetUsed'], 2) }}</strong></span>
        <span>Allocated: <strong style="color:var(--gray-800)">&#8369;{{ number_format($r['budgetAlloc'], 2) }}</strong></span>
      </div>
      <div style="margin-top:8px;font-size:12px;color:{{ $r['budgetRemain'] < 0 ? '#EF4444' : 'var(--gray-500)' }}">
        @if($r['budgetRemain'] >= 0)
          Remaining: <strong>&#8369;{{ number_format($r['budgetRemain'], 2) }}</strong>
        @else
          <strong>Over by &#8369;{{ number_format(abs($r['budgetRemain']), 2) }}</strong>
        @endif
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
        @if($viewProgram->description)
          <p style="font-size:14px;color:var(--gray-700);line-height:1.8">{!! nl2br(e($viewProgram->description)) !!}</p>
        @else
          <p style="color:var(--gray-400);font-size:13px">No description provided.</p>
        @endif
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
          @forelse($viewActivities as $a)
            @php $aBudgetItems = $allBudgetItems[$a->id] ?? collect(); @endphp
            <tr>
              <td>
                <div style="width:44px;height:44px;border-radius:8px;overflow:hidden;flex-shrink:0">
                  @if($a->cover_image)
                    <img src="{{ route('files.activity-cover', $a) }}" alt="{{ $a->title }}" style="width:100%;height:100%;object-fit:cover;display:block"/>
                  @else
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
                      <i class="fas {{ \App\Support\TrainingCategoryIcon::icon($a->area) }}" style="font-size:16px;color:rgba(255,255,255,.85)"></i>
                    </div>
                  @endif
                </div>
              </td>
              <td style="color:var(--gray-400)">{{ $loop->iteration }}</td>
              <td><strong>{{ $a->title }}</strong></td>
              <td><span class="badge badge-{{ strtolower($a->status) }}">{{ $a->status }}</span></td>
              <td>
                <div style="font-weight:600">&#8369;{{ number_format((float) $a->budget_used, 2) }}</div>
                @if($aBudgetItems->isNotEmpty())
                  <button type="button" class="btn btn-ghost btn-sm" style="font-size:11px;padding:2px 6px;margin-top:2px"
                    onclick="toggleBreakdown('breakdown-{{ $a->id }}')">
                    <i class="fas fa-list-ul"></i> Breakdown
                  </button>
                @endif
              </td>
              <td><a href="{{ route('ec.trainings') }}?view={{ $a->id }}" class="btn btn-sm btn-outline">View</a></td>
            </tr>
            @if($aBudgetItems->isNotEmpty())
            <tr id="breakdown-{{ $a->id }}" style="display:none;background:var(--gray-50)">
              <td colspan="6" style="padding:0 16px 12px 72px">
                <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px">
                  <thead>
                    <tr style="border-bottom:1px solid var(--gray-200)">
                      <th style="padding:5px 8px;text-align:left;color:var(--gray-400);font-weight:600;font-size:11px;text-transform:uppercase">Category</th>
                      <th style="padding:5px 8px;text-align:left;color:var(--gray-400);font-weight:600;font-size:11px;text-transform:uppercase">Description</th>
                      <th style="padding:5px 8px;text-align:right;color:var(--gray-400);font-weight:600;font-size:11px;text-transform:uppercase">Qty</th>
                      <th style="padding:5px 8px;text-align:right;color:var(--gray-400);font-weight:600;font-size:11px;text-transform:uppercase">Unit Cost</th>
                      <th style="padding:5px 8px;text-align:right;color:var(--gray-400);font-weight:600;font-size:11px;text-transform:uppercase">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                  @foreach($aBudgetItems as $bi)
                    <tr style="border-bottom:1px solid var(--gray-100)">
                      <td style="padding:5px 8px;font-weight:600;color:var(--gray-800)">{{ $bi->category }}</td>
                      <td style="padding:5px 8px;color:var(--gray-600)">{{ $bi->description ?: '—' }}</td>
                      <td style="padding:5px 8px;text-align:right;color:var(--gray-600)">{{ number_format((float)$bi->quantity, 2) }}</td>
                      <td style="padding:5px 8px;text-align:right;color:var(--gray-600)">&#8369;{{ number_format((float)$bi->unit_cost, 2) }}</td>
                      <td style="padding:5px 8px;text-align:right;font-weight:700;color:var(--gray-800)">&#8369;{{ number_format((float)$bi->total, 2) }}</td>
                    </tr>
                  @endforeach
                  </tbody>
                  <tfoot>
                    <tr style="border-top:2px solid var(--gray-200)">
                      <td colspan="4" style="padding:6px 8px;font-weight:700;color:var(--gray-800)">Total</td>
                      <td style="padding:6px 8px;text-align:right;font-weight:700;color:var(--gray-800)">&#8369;{{ number_format($aBudgetItems->sum(fn($i)=>(float)$i->total), 2) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </td>
            </tr>
            @endif
          @empty
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">No activities assigned to this program yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

<script>
function toggleBreakdown(id) {
  var row = document.getElementById(id);
  if (row) row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>

    <!-- Program Activity Log -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-clock-rotate-left"></i> Program Activity Log</div>
      </div>
      @php
        $logActionLabels = [
          'uploaded_file' => 'Uploaded',
          'added_link'    => 'Added external link',
          'archived'      => 'Archived',
          'restored'      => 'Restored',
          'deleted'       => 'Deleted',
        ];
      @endphp
      @if($viewLogs->isEmpty())
        <div class="empty-state" style="padding:24px"><i class="fas fa-clock-rotate-left"></i><p>No program activity has been recorded yet.</p></div>
      @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Date &amp; Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Item</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody>
          @foreach($viewLogs as $log)
            <tr>
              <td style="white-space:nowrap;color:var(--gray-500)">{{ $log->created_at->format('M d, Y g:i A') }}</td>
              <td>
                <div style="font-weight:600">{{ $log->actor_name }}</div>
                <div style="font-size:11px;color:var(--gray-400)">{{ $log->actor_role }}</div>
              </td>
              <td>{{ $logActionLabels[$log->action] ?? ucfirst($log->action) }}</td>
              <td>
                <div>{{ $log->item_name ?? '—' }}</div>
                @if($log->item_type)
                <div style="font-size:11px;color:var(--gray-400)">{{ strtoupper($log->item_type) }}</div>
                @endif
              </td>
              <td>
                @if($log->training_id)
                  Activity: {{ $log->location_name }}
                @else
                  {{ $log->location_name }}
                @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      @endif
    </div>
  </div>

  <div class="dash-side">
    <!-- Program Details (read-only  -  budget and the original timeline are permanently fixed at creation, see Extend Timeline above) -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Program Details</div>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        @php
          $effectiveEnd = $viewProgram->effective_end_date;
          $timelineEndVal = e($effectiveEnd?->format('Y-m-d') ?? ' - ');
          if ($viewProgram->extended_end_date) {
            $timelineEndVal .= ' <span style="color:var(--gray-400);font-weight:400">(originally '.e($viewProgram->timeline_end->format('M d, Y')).')</span>';
          }
          $details = [
            ['Area / Specialization', e($viewProgram->area ?? ' - ')],
            ['Timeline Start', e($viewProgram->timeline_start?->format('Y-m-d') ?? ' - ')],
            ['Timeline End', $timelineEndVal],
            ['Budget Allocated', ''.number_format((float) $viewProgram->budget_allocated, 2)],
            ['Created By', e($viewProgram->creator->full_name ?? ' - ')],
            ['Created', e($viewProgram->created_at?->format('M d, Y') ?? ' - ')],
          ];
        @endphp
        @foreach($details as [$label, $val])
        <div style="display:flex;align-items:flex-start;gap:12px">
          <div><div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ $label }}</div><div style="font-size:13px;color:var(--gray-800);font-weight:500;margin-top:2px">{!! $val !!}</div></div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Team -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Program Team</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        @forelse($viewProgram->lead as $lead)
        <div class="upload-item">
          <div class="upload-item-icon doc"><i class="fas fa-user-tie"></i></div>
          <div class="upload-item-body">
            <div class="upload-item-name">{{ $lead->full_name }}</div>
            <div class="upload-item-meta">Project Lead</div>
          </div>
        </div>
        @empty
        <div class="empty-state" style="padding:16px"><i class="fas fa-user-tie"></i><p>No lead assigned.</p></div>
        @endforelse
        @foreach($viewProgram->members as $member)
        <div class="upload-item">
          <div class="upload-item-icon img"><i class="fas fa-user"></i></div>
          <div class="upload-item-body">
            <div class="upload-item-name">{{ $member->full_name }}</div>
            <div class="upload-item-meta">Team Member</div>
          </div>
        </div>
        @endforeach
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
    @php $effectiveEnd = $viewProgram->effective_end_date; @endphp
    <form method="POST" action="{{ route('ec.programs.store') }}">
      @csrf
      <input type="hidden" name="action" value="extend_timeline"/>
      <input type="hidden" name="program_id" value="{{ $viewProgram->id }}"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> The program's original timeline and budget are permanently fixed. This only pushes the effective end date further out  -  it never edits or replaces the original.
        </div>
        <div class="form-group">
          <label class="form-label">Current Effective End Date</label>
          <div style="padding:9px 12px;border-radius:var(--radius-sm);background:var(--gray-50);font-size:13px;font-weight:600;color:var(--gray-700)">{{ $effectiveEnd?->format('M d, Y') ?? ' - ' }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">New End Date *</label>
          <input type="date" name="new_end_date" class="form-control"
                 min="{{ $effectiveEnd ? $effectiveEnd->copy()->addDay()->format('Y-m-d') : '' }}" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Remark (required) *</label>
          <textarea name="remark" class="form-control" rows="3" placeholder="Explain why this extension is needed..." required></textarea>
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
@php
  $currentLeadId = optional($viewProgram->lead->first())->id;
  $currentMembers = $viewProgram->members->values();
@endphp
<div class="modal-overlay" id="modal-manageTeam">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-users-gear"></i> Manage Team</h2>
      <button class="modal-close" onclick="closeModal('manageTeam')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.programs.store') }}">
      @csrf
      <input type="hidden" name="action" value="update_team"/>
      <input type="hidden" name="program_id" value="{{ $viewProgram->id }}"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> This replaces the program's current team. It is not a protected field  -  no remark needed, no amendment logged.
        </div>
        <div class="form-group">
          <label class="form-label">Project Lead * <span style="font-weight:400;color:var(--gray-400)">(exactly one, from Project Leaders)</span></label>
          <select name="lead_id" class="form-control" required>
            <option value="" disabled selected> -  Select Project Lead  - </option>
            @foreach($trainers as $tr)
            <option value="{{ $tr->id }}" {{ (int) $currentLeadId === $tr->id ? 'selected' : '' }}>{{ $tr->first_name }} {{ $tr->last_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Team Members <span style="font-weight:400;color:var(--gray-400)">(optional, up to 3)</span></label>
          <div class="form-row" style="grid-template-columns:1fr 1fr 1fr">
            @for($i = 0; $i < 3; $i++)
            @php $cur = $currentMembers->get($i); @endphp
            <select name="member_ids[]" class="form-control">
              <option value=""> -  None  - </option>
              @foreach($trainers as $tr)
              <option value="{{ $tr->id }}" {{ $cur && $cur->id === $tr->id ? 'selected' : '' }}>{{ $tr->first_name }} {{ $tr->last_name }}</option>
              @endforeach
            </select>
            @endfor
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
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        @include('ec.partials.activity-create-fields', ['trainers' => $trainers, 'scopedProgram' => $viewProgram])
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
      @include('partials.document-upload-form', [
        'actionRoute' => route('ec.programs.store'),
        'idSuffix'    => 'Program',
        'scopeField'  => ['name' => 'program_id', 'value' => $viewProgram->id],
        'submitLabel' => 'Save Document',
      ])
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
    <form method="POST" action="{{ route('ec.programs.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="action" value="upload_cover"/>
      <input type="hidden" name="program_id" value="{{ $viewProgram->id }}"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Cover Image <span style="color:var(--red)">*</span></label>
          <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp" required/>
          <div class="form-hint">JPG, PNG, GIF, or WEBP  -  max 5 MB</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('uploadProgramCover')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="pathriveRequestImageUpload(this.form, { title: 'Upload this cover image?' })"><i class="fas fa-check"></i> Save Cover</button>
      </div>
    </form>
  </div>
</div>

@else
{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• CARD GRID VIEW â•â•â•â• --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Programs</span></div>
    <h1>Programs</h1>
    <p>The programs each extension activity is organized under</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addProgram')"><i class="fas fa-plus"></i> Create Program</button>
</div>

{{-- Search bar --}}
<div style="margin-bottom:20px">
  <div style="position:relative;max-width:400px">
    <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none"></i>
    <input type="text" id="programSearch" placeholder="Search programs..." oninput="filterPrograms()"
      style="width:100%;padding:9px 12px 9px 36px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);font-size:13px;color:var(--gray-800);background:var(--white);outline:none"
      onfocus="this.style.borderColor='var(--blue-primary)'" onblur="this.style.borderColor='var(--gray-200)'"/>
  </div>
</div>

@if($programs->isEmpty())
<div class="empty-state"><i class="fas fa-diagram-project"></i><p>No programs yet. <a href="#" onclick="openModal('addProgram')">Create one.</a></p></div>
@else
<div class="home-grid" id="programsGrid">
  @foreach($programs as $p)
    @php $icon = \App\Support\TrainingCategoryIcon::icon($p->area); @endphp
    <div class="training-card program-card-item" data-title="{{ strtolower($p->title) }}">
      <div class="training-card-img" style="background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
        @if($p->cover_image)
          <img src="{{ route('files.program-cover', $p) }}" alt="{{ $p->title }}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;z-index:0"/>
        @endif
        @if(!$p->cover_image)
          <i class="fas {{ $icon }}" style="z-index:1;position:relative;font-size:40px"></i>
        @endif
      </div>
      <div class="training-card-body">
        <div class="training-card-title">{{ $p->title }}</div>
        <div class="training-card-footer" style="margin-top:auto;padding-top:12px">
          <span class="badge badge-{{ strtolower($p->status) }}">{{ $p->status }}</span>
          <div style="display:flex;gap:5px;align-items:center">
            <button class="btn btn-outline" style="padding:7px 12px;font-size:13px;line-height:1"
              onclick="openEditProgramModal({{ $p->id }}, '{{ addslashes($p->title) }}', '{{ addslashes($p->area) }}', '{{ addslashes($p->description ?? '') }}')">
              <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-danger" style="padding:7px 12px;font-size:13px;line-height:1"
              onclick="openDeleteProgramModal({{ $p->id }}, '{{ addslashes($p->title) }}')">
              <i class="fas fa-trash"></i>
            </button>
            <a href="{{ route('ec.programs') }}?view={{ $p->id }}" class="btn btn-primary" style="padding:7px 14px;font-size:13px;line-height:1">View</a>
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>
<div id="programsEmpty" style="display:none" class="empty-state"><i class="fas fa-magnifying-glass"></i><p>No programs match your search.</p></div>
@endif

<script>
function filterPrograms() {
  const q = document.getElementById('programSearch').value.toLowerCase().trim();
  const cards = document.querySelectorAll('.program-card-item');
  let visible = 0;
  cards.forEach(card => {
    const match = !q || card.dataset.title.includes(q);
    card.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const emptyMsg = document.getElementById('programsEmpty');
  if (emptyMsg) emptyMsg.style.display = visible === 0 ? '' : 'none';
}
function openEditProgramModal(id, title, area, description) {
  document.getElementById('editProgramId').value = id;
  document.getElementById('editProgramTitle').value = title;
  document.getElementById('editProgramArea').value = area;
  document.getElementById('editProgramDesc').value = description;
  openModal('editProgram');
}
function openDeleteProgramModal(id, title) {
  document.getElementById('deleteProgramId').value = id;
  document.getElementById('deleteProgramName').textContent = title;
  openModal('deleteProgram');
}
</script>

<!-- MODAL: EDIT PROGRAM -->
<div class="modal-overlay" id="modal-editProgram">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <h2><i class="fas fa-pen"></i> Edit Program</h2>
      <button class="modal-close" onclick="closeModal('editProgram')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.programs.store') }}">
      @csrf
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="program_id" id="editProgramId"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-lock"></i> Budget and timeline are permanently fixed and cannot be changed here. Use <strong>Extend Timeline</strong> inside the program if you need to push the end date.
        </div>
        <div class="form-group">
          <label class="form-label">Program Title *</label>
          <input type="text" name="title" id="editProgramTitle" class="form-control" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Area / Specialization *</label>
          <input type="text" name="area" id="editProgramArea" class="form-control" maxlength="120" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="editProgramDesc" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editProgram')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: DELETE PROGRAM -->
<div class="modal-overlay" id="modal-deleteProgram">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2><i class="fas fa-trash"></i> Delete Program</h2>
      <button class="modal-close" onclick="closeModal('deleteProgram')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.programs.store') }}">
      @csrf
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="program_id" id="deleteProgramId"/>
      <div class="modal-body">
        <p style="font-size:14px;color:var(--gray-700)">Are you sure you want to delete <strong id="deleteProgramName"></strong>? This cannot be undone. All activities under this program will be unlinked.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('deleteProgram')">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: CREATE PROGRAM -->
<div class="modal-overlay" id="modal-addProgram">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Create New Program</h2>
      <button class="modal-close" onclick="closeModal('addProgram')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.programs.store') }}">
      @csrf
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        @include('ec.partials.program-create-fields', ['trainers' => $trainers])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addProgram')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Program</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection

