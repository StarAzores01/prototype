@extends('layouts.trainer')

@section('content')
@php $statusColors = ['Proposed' => '#F59E0B', 'Approved' => '#1A56DB', 'Ongoing' => '#10B981', 'Completed' => '#6B7280']; @endphp

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Dashboard</span></div>
    <h1>Welcome, {{ $userFirstName }}!</h1>
    <p>Here's an overview of your training programs.</p>
  </div>
  <a href="{{ route('trainer.documents') }}" class="btn btn-primary"><i class="fas fa-arrow-up"></i> Upload Document</a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-book"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $activeTrainings }}</div><div class="stat-label">Active Trainings</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy"><i class="fas fa-users"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $totalTrainees }}</div><div class="stat-label">Total Trainees</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-arrow-up"></i></div>
    <div class="stat-body"><div class="stat-value">{{ $docsUploaded }}</div><div class="stat-label">Documents Uploaded</div></div>
  </div>
</div>

<!-- Training Activities (read-only, expanded table) -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div><div class="card-title">Training Activities</div><div class="card-subtitle">Your assigned extension trainings</div></div>
    <a href="{{ route('trainer.trainings') }}" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Training Name</th><th>Area</th><th>Schedule</th><th>Enrolled</th><th>Target</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      @forelse($myTrainings as $t)
        @php $col = $statusColors[$t->status] ?? '#6B7280'; @endphp
        <tr>
          <td>
            <strong>{{ $t->title }}</strong>
            @if($t->description)
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">{{ \Illuminate\Support\Str::limit($t->description, 60, '…') }}</div>
            @endif
          </td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $t->area }}</td>
          <td style="font-size:12px;color:var(--gray-400)">{{ $t->date_start?->format('Y-m-d') ?? '—' }}</td>
          <td><strong>{{ (int) $t->enrolled }}</strong></td>
          <td style="font-size:12px;color:var(--gray-500)">{{ (int) $t->target_participants }}</td>
          <td><span style="font-size:12px;font-weight:600;color:{{ $col }}">{{ $t->status }}</span></td>
          <td><a href="{{ route('trainer.trainings') }}?view={{ $t->id }}" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a></td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-400)">No trainings assigned yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Bottom panels -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Latest Documents</div>
      <a href="{{ route('trainer.documents') }}" class="btn btn-ghost btn-sm">See all</a>
    </div>
    <div class="card-body" style="padding-top:12px">
      @forelse($latestDocs as $d)
      <div class="upload-item">
        <div class="upload-item-body">
          <div class="upload-item-name">{{ $d->original_name }}</div>
          <div class="upload-item-meta">{{ $d->training->title ?? 'General' }} &middot; {{ $d->created_at?->format('M d, Y') }}</div>
        </div>
      </div>
      @empty
      <div class="empty-state" style="padding:20px"><i class="fas fa-folder-open"></i><p>No documents yet.</p></div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Quick Links</div></div>
    <div class="card-body">
      <div class="quick-links">
        <a href="{{ route('trainer.trainings') }}" class="quick-link-item">My Trainings</a>
        <a href="{{ route('trainer.participants') }}" class="quick-link-item">Participants</a>
        <a href="{{ route('trainer.evaluations') }}" class="quick-link-item">Evaluations</a>
        <a href="{{ route('trainer.documents') }}" class="quick-link-item">Upload Document</a>
        <a href="{{ route('trainer.skills') }}" class="quick-link-item">Skills Utilization</a>
      </div>
    </div>
  </div>
</div>
@endsection
