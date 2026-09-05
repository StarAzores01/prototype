@extends('layouts.beneficiary')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Evaluations</span></div>
    <h1>Evaluations</h1>
    <p>Answer evaluation forms sent by the Extension Coordinator</p>
  </div>
  @if($viewForm)
  <a href="{{ route('beneficiary.evaluations') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
  @endif
</div>

@if($viewForm)
{{-- ═══════════════ FORM ANSWER VIEW ═══════════════ --}}
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title">{{ $viewForm->title }}</div>
      <div class="card-subtitle">{{ $viewForm->training->title }} &middot; {{ $viewForm->training->date_start?->format('Y-m-d') }}</div>
    </div>
    @if($viewForm->myResponse)
    <span class="badge badge-completed"><i class="fas fa-square-check"></i> Submitted</span>
    @endif
  </div>
  <div class="card-body">
    @if($viewForm->myResponse)
    <!-- ── READ-ONLY VIEW after submission ── -->
    <div style="background:#F0FDF4;border:1.5px solid #BBF7D0;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px;color:#166534">
      <i class="fas fa-square-check"></i> <strong>You have already submitted this evaluation.</strong> Responses can no longer be edited.
    </div>
    @php
      $fields = $viewForm->fields ?? [];
      $existing = $viewForm->myResponse->responses ?? [];
    @endphp
    @foreach($fields as $fi => $field)
      @php $val = $existing[$fi] ?? ''; @endphp
    <div class="form-group" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-100)">
      <label class="form-label" style="font-size:14px;font-weight:700;color:var(--text-heading)">
        {{ $fi + 1 }}. {{ $field['label'] }}
      </label>
      <div style="margin-top:8px;padding:10px 13px;background:var(--gray-50);border:1.5px solid var(--gray-200);border-radius:9px;font-size:13.5px;color:var(--gray-800);min-height:40px">
        @if($val !== '')
          {{ is_array($val) ? implode(', ', $val) : $val }}
        @else
          <span style="color:var(--gray-400)">—</span>
        @endif
      </div>
    </div>
    @endforeach
    <div style="display:flex;justify-content:flex-end;margin-top:8px">
      <a href="{{ route('beneficiary.evaluations') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    @else
    <!-- ── ANSWER FORM (not yet submitted) ── -->
    <form method="POST" action="{{ route('beneficiary.evaluations.store') }}">
      @csrf
      <input type="hidden" name="action" value="submit"/>
      <input type="hidden" name="form_id" value="{{ $viewForm->id }}"/>
      <input type="hidden" name="training_id" value="{{ $viewForm->training_id }}"/>

      @php $fields = $viewForm->fields ?? []; @endphp
      @foreach($fields as $fi => $field)
      <div class="form-group" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-100)">
        <label class="form-label" style="font-size:14px;font-weight:700;color:var(--text-heading)">
          {{ $fi + 1 }}. {{ $field['label'] }}
          @if(!empty($field['required']))<span style="color:var(--red)"> *</span>@endif
        </label>

        @if($field['type'] === 'textarea')
        <textarea name="answer[{{ $fi }}]" class="form-control" rows="3"
                  {{ !empty($field['required']) ? 'required' : '' }}></textarea>

        @elseif($field['type'] === 'radio')
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px">
          @foreach($field['options'] as $opt)
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px">
            <input type="radio" name="answer[{{ $fi }}]" value="{{ $opt }}"
                   {{ !empty($field['required']) ? 'required' : '' }}
                   style="width:16px;height:16px;accent-color:var(--blue-primary)"/>
            {{ $opt }}
          </label>
          @endforeach
        </div>

        @elseif($field['type'] === 'select')
        <select name="answer[{{ $fi }}]" class="form-control" {{ !empty($field['required']) ? 'required' : '' }}>
          <option value="">— Select —</option>
          @foreach($field['options'] as $opt)
          <option value="{{ $opt }}">{{ $opt }}</option>
          @endforeach
        </select>

        @else
        <input type="text" name="answer[{{ $fi }}]" class="form-control"
               {{ !empty($field['required']) ? 'required' : '' }}/>
        @endif
      </div>
      @endforeach

      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px">
        <a href="{{ route('beneficiary.evaluations') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Evaluation</button>
      </div>
    </form>
    @endif
  </div>
</div>

@else
{{-- ═══════════════ FORMS LIST ═══════════════ --}}
@if($forms->isEmpty())
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    <i class="fas fa-star"></i>
    <div style="font-size:15px;font-weight:700;color:var(--text-heading);margin:12px 0 6px">No evaluation forms yet</div>
    <p style="font-size:13px;color:var(--gray-400)">The Extension Coordinator will send evaluation forms after your activity.</p>
  </div>
</div>
@else
<div style="display:flex;flex-direction:column;gap:14px">
  @foreach($forms as $f)
  <div class="card" style="border-left:4px solid {{ $f->myResponse ? 'var(--green)' : 'var(--blue-primary)' }}">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <div style="font-size:15px;font-weight:700;color:var(--text-heading)">{{ $f->title }}</div>
        <div style="font-size:12px;color:var(--gray-400);margin-top:3px">
          <i class="fas fa-book"></i> {{ $f->training->title }}
          @if($f->training->date_start) &middot; <i class="fas fa-calendar"></i> {{ $f->training->date_start->format('Y-m-d') }}@endif
        </div>
        @if($f->myResponse)
        <div style="font-size:11px;color:var(--green);margin-top:4px"><i class="fas fa-square-check"></i> Submitted {{ $f->myResponse->submitted_at->format('M d, Y') }}</div>
        @endif
      </div>
      @if($f->myResponse)
      <a href="?form={{ $f->id }}" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View Response</a>
      @else
      <a href="?form={{ $f->id }}" class="btn btn-primary btn-sm"><i class="fas fa-file-lines"></i> Answer Form</a>
      @endif
    </div>
  </div>
  @endforeach
</div>
@endif
@endif
@endsection
