@extends('layouts.ec')

@section('content')
@if($mode === 'detail')
{{-- ═══════════════════════════════════════════════════════ DETAIL VIEW ═══ --}}
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
  <div style="display:flex;gap:10px">
    <a href="{{ route('ec.programs') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <button class="btn btn-outline" onclick="openModal('manageTeam')"><i class="fas fa-users-gear"></i> Manage Team</button>
    <button class="btn btn-primary" onclick="openModal('unlockProgram')"><i class="fas fa-lock-open"></i> Request Unlock &amp; Amend</button>
  </div>
</div>

<!-- Info Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon {{ $viewProgram->status === 'Completed' ? 'green' : ($viewProgram->status === 'Ongoing' ? 'blue' : 'yellow') }}"><i class="fas fa-circle-info"></i></div>
    <div class="stat-body">
      <form method="POST" action="{{ route('ec.programs.store') }}" style="margin:0 0 2px">
        @csrf
        <input type="hidden" name="action" value="update_status"/>
        <input type="hidden" name="program_id" value="{{ $viewProgram->id }}"/>
        <select name="status" onchange="this.form.submit()"
          style="font-size:16px;font-weight:700;color:var(--gray-800);border:none;background:transparent;padding:0;cursor:pointer">
          @foreach(['Proposed', 'Approved', 'Ongoing', 'Completed'] as $s)
          <option value="{{ $s }}" {{ $viewProgram->status === $s ? 'selected' : '' }}>{{ $s }}</option>
          @endforeach
        </select>
      </form>
      <div class="stat-label">Status</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-lock"></i></div>
    <div class="stat-body"><div class="stat-value" style="font-size:16px">{{ $viewProgram->is_locked ? 'Locked' : 'Unlocked' }}</div><div class="stat-label">Lock Status</div></div>
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

<!-- Progress Card -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><i class="fas fa-chart-line"></i> Program Progress</div>
      <div class="card-subtitle">Activity completion and budget utilization across the whole program</div>
    </div>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:28px">

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
              <td>&#8369;{{ number_format((float) $a->budget_used, 2) }}</td>
              <td><a href="{{ route('ec.trainings') }}?view={{ $a->id }}" class="btn btn-sm btn-outline">View</a></td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">No activities assigned to this program yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Amendment History -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Amendment History</div></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Date</th><th>Field</th><th>Old Value</th><th>New Value</th><th>Remark</th><th>By</th></tr></thead>
          <tbody>
          @forelse($viewAmendments as $am)
            <tr>
              <td style="font-size:12px;color:var(--gray-500)">{{ $am->created_at->format('M d, Y g:i A') }}</td>
              <td><span class="badge badge-approved">{{ ucfirst($am->field_changed) }}</span></td>
              <td style="font-size:12px">{{ $am->old_value }}</td>
              <td style="font-size:12px;font-weight:600">{{ $am->new_value }}</td>
              <td style="font-size:12px;color:var(--gray-600)">{{ $am->remark }}</td>
              <td style="font-size:12px">{{ $am->amendedBy->full_name ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">No amendments have been made to this program.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="dash-side">
    <!-- Program Details (read-only — locked fields, see Request Unlock above) -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Program Details</div>
        <span title="Locked — Budget is permanently fixed; use Request Unlock &amp; Amend to change the timeline" style="color:var(--gray-400)"><i class="fas fa-lock"></i></span>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        @php
          $details = [
            ['Area / Specialization', $viewProgram->area ?? '—'],
            ['Timeline Start', $viewProgram->timeline_start?->format('Y-m-d') ?? '—'],
            ['Timeline End', $viewProgram->timeline_end?->format('Y-m-d') ?? '—'],
            ['Budget Allocated', '₱'.number_format((float) $viewProgram->budget_allocated, 2)],
            ['Created By', $viewProgram->creator->full_name ?? '—'],
            ['Created', $viewProgram->created_at?->format('M d, Y') ?? '—'],
          ];
        @endphp
        @foreach($details as [$label, $val])
        <div style="display:flex;align-items:flex-start;gap:12px">
          <div><div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ $label }}</div><div style="font-size:13px;color:var(--gray-800);font-weight:500;margin-top:2px">{{ $val }}</div></div>
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

    <!-- Documents: this Program's own general repository, scoped via program_id -->
    <div class="card">
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
  </div>
</div>

<!-- MODAL: REQUEST UNLOCK & AMEND -->
<div class="modal-overlay" id="modal-unlockProgram">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h2><i class="fas fa-lock-open"></i> Request Unlock &amp; Amend</h2>
      <button class="modal-close" onclick="closeModal('unlockProgram')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.programs.store') }}">
      @csrf
      <input type="hidden" name="action" value="request_unlock"/>
      <input type="hidden" name="program_id" value="{{ $viewProgram->id }}"/>
      <input type="hidden" name="field_changed" value="timeline"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          <i class="fas fa-circle-info"></i> This program is locked. Timeline is the only field that can still be amended — budget is permanently fixed at creation and cannot be changed, ever.
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">New Start Date *</label>
            <input type="date" name="new_timeline_start" class="form-control" value="{{ $viewProgram->timeline_start?->format('Y-m-d') }}" required/>
          </div>
          <div class="form-group">
            <label class="form-label">New End Date *</label>
            <input type="date" name="new_timeline_end" class="form-control" value="{{ $viewProgram->timeline_end?->format('Y-m-d') }}" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Remark (required) *</label>
          <textarea name="remark" class="form-control" rows="3" placeholder="Explain why this change is needed…" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('unlockProgram')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Amendment</button>
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
          <i class="fas fa-circle-info"></i> This replaces the program's current team. It is not a protected field — no remark needed, no amendment logged.
        </div>
        <div class="form-group">
          <label class="form-label">Project Lead * <span style="font-weight:400;color:var(--gray-400)">(exactly one, from Project Leaders)</span></label>
          <select name="lead_id" class="form-control" required>
            <option value="">— Select Project Lead —</option>
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
              <option value="">— None —</option>
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

@else
{{-- ═══════════════════════════════════════════════════════ CARD GRID VIEW ════ --}}
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Programs</span></div>
    <h1>Programs</h1>
    <p>The programs each extension activity is organized under</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addProgram')"><i class="fas fa-plus"></i> Create Program</button>
</div>

@if($programs->isEmpty())
<div class="empty-state"><i class="fas fa-diagram-project"></i><p>No programs yet. <a href="#" onclick="openModal('addProgram')">Create one.</a></p></div>
@else
<div class="home-grid">
  @foreach($programs as $p)
    @php
      $pr = \App\Http\Controllers\Ec\ProgramController::rollup($p);
      $icon = \App\Support\TrainingCategoryIcon::icon($p->area);
    @endphp
    <div class="training-card">
      <div class="training-card-img" style="background:linear-gradient(135deg,var(--navy),var(--blue-primary))">
        <div class="training-card-cat">{{ $p->area }}</div>
        <i class="fas {{ $icon }}" style="z-index:1;position:relative;font-size:40px"></i>
      </div>
      <div class="training-card-body">
        <div class="training-card-title">{{ $p->title }}</div>
        <div class="training-card-desc">{{ \Illuminate\Support\Str::limit($p->description ?? '', 100, '…') }}</div>
        <div class="training-card-meta">
          <span><i class="fas fa-user-tie"></i> {{ optional($p->lead->first())->full_name ?? 'No lead' }}</span>
          <span><i class="fas fa-book"></i> {{ $pr['completed'] }}/{{ $pr['total'] }} done</span>
        </div>
        <div style="background:var(--gray-100);border-radius:8px;height:8px;overflow:hidden;margin-bottom:10px">
          <div style="height:100%;border-radius:8px;width:{{ $pr['progressPct'] }}%;background:{{ $pr['progressPct'] >= 100 ? '#10B981' : '#1A56DB' }}"></div>
        </div>
        <div style="font-size:11.5px;color:var(--gray-500);margin-bottom:12px">
          Remaining: <strong style="color:{{ $pr['budgetRemain'] < 0 ? '#EF4444' : 'var(--gray-800)' }}">&#8369;{{ number_format($pr['budgetRemain'], 2) }}</strong>
          of &#8369;{{ number_format($pr['budgetAlloc'], 2) }}
        </div>
        <div class="training-card-footer">
          <span class="badge badge-{{ strtolower($p->status) }}">{{ $p->status }}</span>
          <a href="{{ route('ec.programs') }}?view={{ $p->id }}" class="btn btn-sm btn-primary">View Details</a>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endif

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
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Program Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Community Livelihood Initiative 2026" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <input type="text" name="area" class="form-control" placeholder="e.g. Culinary Technology" maxlength="120" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description (optional)</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the program…"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Timeline Start *</label>
            <input type="date" name="timeline_start" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Timeline End *</label>
            <input type="date" name="timeline_end" class="form-control" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Budget Allocated (₱) *</label>
          <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 200000" min="0" step="0.01" required/>
        </div>

        <hr style="border:none;border-top:1px solid var(--gray-100);margin:8px 0 16px">
        <div class="form-group">
          <label class="form-label">Project Lead * <span style="font-weight:400;color:var(--gray-400)">(exactly one, from Project Leaders)</span></label>
          <select name="lead_id" class="form-control" required>
            <option value="">— Select Project Lead —</option>
            @foreach($trainers as $tr)
            <option value="{{ $tr->id }}">{{ $tr->first_name }} {{ $tr->last_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Team Members <span style="font-weight:400;color:var(--gray-400)">(optional, up to 3)</span></label>
          <div class="form-row" style="grid-template-columns:1fr 1fr 1fr">
            @for($i = 0; $i < 3; $i++)
            <select name="member_ids[]" class="form-control">
              <option value="">— None —</option>
              @foreach($trainers as $tr)
              <option value="{{ $tr->id }}">{{ $tr->first_name }} {{ $tr->last_name }}</option>
              @endforeach
            </select>
            @endfor
          </div>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">A team member can't also be the Project Lead, and can't be selected twice.</div>
        </div>
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
