{{-- Read-only view of an evaluator-built impact assessment form
     (the questions; older submissions may also show the evaluator's answers). Expects $a (ImpactAssessment with
     form_data). Open with openModal('viewForm{id}'). --}}
<div class="modal-overlay" id="modal-viewForm{{ $a->id }}">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <div class="modal-title"><i class="fas fa-list-check"></i> {{ $a->title }}</div>
      <button class="modal-close" onclick="closeModal('viewForm{{ $a->id }}')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="max-height:60vh;overflow-y:auto">
      @if($a->description)
      <p style="font-size:13px;color:var(--gray-600);margin-bottom:16px">{{ $a->description }}</p>
      @endif
      @foreach($a->form_data ?? [] as $i => $q)
      <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:12.5px;font-weight:700;color:var(--gray-500);margin-bottom:4px">{{ $i + 1 }}. {{ $q['label'] }}@if(! empty($q['required']))<span style="color:var(--red)"> *</span>@endif</div>
        @if(($q['answer'] ?? '') !== '')
        <div style="font-size:13.5px;color:var(--text-heading);white-space:pre-line">{{ $q['answer'] }}</div>
        @else
        <div style="font-size:12px;color:var(--gray-400)">{{ ucfirst($q['type'] ?? 'text') }} question</div>
        @endif
      </div>
      @endforeach
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="closeModal('viewForm{{ $a->id }}')">Close</button>
    </div>
  </div>
</div>
