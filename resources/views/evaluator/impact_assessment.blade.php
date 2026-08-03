@extends('layouts.evaluator')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Impact Assessment</span></div>
    <h1>Impact Assessment</h1>
    <p>Submit assessment forms to the Extension Coordinator</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('submitAssessment')">&#43; Submit Assessment</button>
</div>

<!-- Search -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('evaluator.impact_assessment') }}" style="display:flex;gap:10px;align-items:center">
      <input type="text" name="q" class="form-control" placeholder="Search assessments..." value="{{ $q }}" style="max-width:320px"/>
      <button type="submit" class="btn btn-outline btn-sm">&#128269; Search</button>
      @if($q)<a href="{{ route('evaluator.impact_assessment') }}" class="btn btn-outline btn-sm">&#10005; Clear</a>@endif
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    @if($assessments->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--gray-400)">
      No assessments yet. Click <strong>Submit Assessment</strong> to get started.
    </div>
    @else
    <table class="data-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Training</th>
          <th>File</th>
          <th>Status</th>
          <th>EC Notes</th>
          <th>Submitted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($assessments as $a)
        <tr>
          <td style="font-weight:600">{{ $a->title }}</td>
          <td>{{ $a->training->title ?? '—' }}</td>
          <td>
            @if($a->file_name)
            <a href="{{ asset('storage/uploads/'.$a->file_name) }}" target="_blank" class="btn btn-outline btn-sm">&#128196; {{ $a->original_name ?? 'View' }}</a>
            @else
            <span style="color:var(--gray-400)">No file</span>
            @endif
          </td>
          <td><span class="badge {{ $a->status === 'Reviewed' ? 'badge-success' : ($a->status === 'Submitted' ? 'badge-info' : 'badge-warning') }}">{{ $a->status }}</span></td>
          <td style="font-size:12px;color:var(--gray-600)">{{ $a->ec_notes ?: '—' }}</td>
          <td>{{ $a->submitted_at?->format('M d, Y') ?? '—' }}</td>
          <td>
            @if($a->status !== 'Reviewed')
            <form method="POST" action="{{ route('evaluator.impact_assessment.store') }}" onsubmit="return confirm('Delete this assessment?')" style="display:inline">
              @csrf
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="assessment_id" value="{{ $a->id }}"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465; Delete</button>
            </form>
            @else
            <span style="font-size:12px;color:var(--gray-400)">Reviewed</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>

<!-- Submit Modal -->
<div class="modal-overlay" id="modal-submitAssessment">
  <div class="modal" style="max-width:540px">
    <div class="modal-header">
      <div class="modal-title">&#128203; Submit Impact Assessment</div>
      <button class="modal-close" onclick="closeModal('submitAssessment')">&#10005;</button>
    </div>
    <form method="POST" action="{{ route('evaluator.impact_assessment.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="action" value="submit"/>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title <span style="color:var(--red)">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Post-Training Impact Assessment – Q1 2026" required/>
        </div>
        <div class="form-group">
          <label class="form-label">Related Training (optional)</label>
          <select name="training_id" class="form-control">
            <option value="">— Select a completed training —</option>
            @foreach($trainings as $t)
            <option value="{{ $t->id }}">{{ $t->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description / Notes</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the assessment..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Upload Form / Document (PDF, DOC, DOCX, JPG, PNG – max 10 MB)</label>
          <input type="file" name="assessment_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('submitAssessment')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#128203; Submit Assessment</button>
      </div>
    </form>
  </div>
</div>
@endsection
