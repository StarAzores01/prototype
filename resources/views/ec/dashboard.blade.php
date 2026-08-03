@extends('layouts.ec')

@section('content')
@php
  $statusColors = ['Proposed' => '#F59E0B', 'Approved' => '#1A56DB', 'Ongoing' => '#10B981', 'Completed' => '#6B7280'];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Dashboard</span></div>
    <h1>Dashboard Overview</h1>
    <p>Welcome back, {{ $userFirstName }}! Here's what's happening in CIT extension programs.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addTraining')">&#43; Create Training</button>
</div>

<!-- Training Activities Table -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">Training Activities</div>
      <div class="card-subtitle">All extension trainings — change status directly in the table</div>
    </div>
    <a href="{{ route('ec.trainings') }}" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Training Name</th>
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
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">{{ \Illuminate\Support\Str::limit($t->description, 70) }}</div>
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
        <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400)">No trainings yet. <a href="#" onclick="openModal('addTraining')">Create one.</a></td></tr>
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
      <div class="empty-state" style="padding:20px">&#128193;<p>No documents yet.</p></div>
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
        <div class="quick-link-item" onclick="openModal('addTraining')">Create New Training</div>
        <a href="{{ route('ec.trainings') }}" class="quick-link-item">All Trainings</a>
        <a href="{{ route('ec.evaluations') }}" class="quick-link-item">Evaluations</a>
        <a href="{{ route('ec.reports') }}" class="quick-link-item">Generate Reports</a>
        <a href="{{ route('ec.documents') }}" class="quick-link-item">Upload Documents</a>
        <a href="{{ route('ec.participants') }}" class="quick-link-item">Register Participant</a>
      </div>
    </div>
  </div>

</div>

<!-- MODAL: CREATE TRAINING -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2>&#43; Create New Training</h2>
      <button class="modal-close" onclick="closeModal('addTraining')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('ec.trainings.store') }}">
      @csrf
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Training Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Basic Pastry Making" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <select name="area" class="form-control" required>
              <option>Mechanical Technology</option>
              <option>Automotive Technology</option>
              <option>Computer Technology</option>
              <option>Electronics Technology</option>
              <option>Culinary Technology</option>
              <option>Apparel and Fashion Technology</option>
              <option>Print Media Technology</option>
              <option>Information Technology</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the training…"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="date_start" class="form-control"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end" class="form-control"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget Allocated (₱)</label>
            <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 50000" min="0" step="0.01"/>
          </div>
          <div class="form-group">
            <label class="form-label">Budget Used (₱)</label>
            <input type="number" name="budget_used" class="form-control" placeholder="0" min="0" step="0.01" value="0"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option>Proposed</option><option>Approved</option><option>Ongoing</option><option>Completed</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Participants</label>
            <input type="number" name="target_participants" class="form-control" placeholder="e.g. 30" min="1"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Project Leader</label>
          <select name="trainer_id" class="form-control">
            <option value="">— Select Project Leader —</option>
            @foreach($trainers as $tr)
            <option value="{{ $tr->id }}">{{ $tr->first_name }} {{ $tr->last_name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Training</button>
      </div>
    </form>
  </div>
</div>
@endsection
