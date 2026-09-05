@extends('layouts.ec')

@section('content')
@php
  $statusColors = ['Proposed' => '#F59E0B', 'Approved' => '#1A56DB', 'Ongoing' => '#10B981', 'Completed' => '#6B7280'];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Dashboard</span></div>
    <h1>Dashboard Overview</h1>
    <p>Welcome back, {{ $userFirstName }}! Here's what's happening in CIT extension programs.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addTraining')"><i class="fas fa-plus"></i> Create Activity</button>
</div>

<!-- Activities Table -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Activities</div>
      <div class="card-subtitle">All extension activities — change status directly in the table</div>
    </div>
    <a href="{{ route('ec.trainings') }}" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Activity Name</th>
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
      @forelse($recentTrainings as $t)
        @php $col = $statusColors[$t->status] ?? '#6B7280'; @endphp
        <tr>
          <td>
            <strong>{{ $t->title }}</strong>
            @if($t->description)
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">{{ \Illuminate\Support\Str::limit($t->description, 70, '…') }}</div>
            @endif
          </td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $t->area }}</td>
          <td style="font-size:12px;color:var(--gray-700)">{{ $t->trainer?->full_name ?? 'TBA' }}</td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $t->date_start?->format('Y-m-d') ?? '—' }}</td>
          <td><strong>{{ $t->enrolled }}</strong></td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $t->target_participants }}</td>
          <td>
            <form method="POST" action="{{ route('ec.trainings.store') }}" style="display:inline">
              @csrf
              <input type="hidden" name="action" value="update_status"/>
              <input type="hidden" name="training_id" value="{{ $t->id }}"/>
              <select name="status" class="filter-select"
                style="font-size:12px;padding:4px 8px;border-radius:6px;color:{{ $col }};font-weight:600;border-color:{{ $col }}"
                onchange="this.form.submit()">
                @foreach(['Proposed','Approved','Ongoing','Completed'] as $s)
                <option {{ $t->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
              </select>
            </form>
          </td>
          <td>
            <a href="{{ route('ec.trainings') }}?view={{ $t->id }}" class="btn btn-sm btn-outline">Manage</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400)">No activities yet. <a href="#" onclick="openModal('addTraining')">Create one.</a></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Bottom panels -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

  <div class="card">
    <div class="card-header">
      <div class="card-title">Latest Uploads</div>
      <a href="{{ route('ec.documents') }}" class="btn btn-ghost btn-sm">See all</a>
    </div>
    <div class="card-body" style="padding-top:12px">
      @forelse($latestDocs as $d)
      <div class="upload-item">
        <div class="upload-item-body">
          <div class="upload-item-name">{{ $d->original_name ?? $d->file_name }}</div>
          <div class="upload-item-meta">{{ $d->training->title ?? 'General' }} · {{ $d->created_at->format('M d, Y') }}</div>
        </div>
      </div>
      @empty
      <div class="empty-state" style="padding:20px"><i class="fas fa-folder-open"></i><p>No documents yet.</p></div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Skills Utilization</div></div>
    <div class="card-body">
      @foreach([
        ['Personal Use', $skills['personal']],
        ['Income-Generating', $skills['income']],
        ['Employment', $skills['employment']],
      ] as [$label, $pct])
      <div class="skills-row">
        <div class="skills-label">{{ $label }}</div>
        <div style="flex:1"><div class="progress-bar-wrap"><div class="progress-bar" style="width:{{ $pct }}%"></div></div></div>
        <div class="skills-pct">{{ $pct }}%</div>
      </div>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Quick Links</div></div>
    <div class="card-body">
      <div class="quick-links">
        <div class="quick-link-item" onclick="openModal('addTraining')">Create New Activity</div>
        <a href="{{ route('ec.trainings') }}" class="quick-link-item">All Activities</a>
        <a href="{{ route('ec.evaluations') }}" class="quick-link-item">Evaluations</a>
        <a href="{{ route('ec.reports') }}" class="quick-link-item">Generate Reports</a>
        <a href="{{ route('ec.documents') }}" class="quick-link-item">Upload Documents</a>
        <a href="{{ route('ec.participants') }}" class="quick-link-item">Register Participant</a>
      </div>
    </div>
  </div>

</div>

<!-- MODAL: CREATE ACTIVITY -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-plus"></i> Create New Activity</h2>
      <button class="modal-close" onclick="closeModal('addTraining')"><i class="fas fa-xmark"></i></button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        @include('ec.partials.activity-create-fields', ['trainers' => $trainers, 'programs' => $programs])
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Activity</button>
      </div>
    </form>
  </div>
</div>
@endsection
