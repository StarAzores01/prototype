@extends('layouts.ec')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">
      PAThrive <i class="fas fa-chevron-right"></i>
      <a href="{{ route('ec.page-content') }}">Manage Public Site Content</a> <i class="fas fa-chevron-right"></i>
      <span>{{ $pageLabel }}</span>
    </div>
    <h1>{{ $pageLabel }}</h1>
    <p>Leave a field blank to fall back to its default content. Only text and images change here — forms, buttons, and links stay as they are.</p>
  </div>
  <a href="{{ route('ec.page-content') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('ec.page-content.update', $pageKey) }}" enctype="multipart/form-data">
  @csrf
  <div class="card">
    <div class="card-body" style="display:flex;flex-direction:column;gap:24px">
      @foreach($sections as $section)
      @php $inputName = str_replace('-', '_', $section['key']); @endphp
      <div class="form-group" style="border-bottom:1px solid var(--gray-100);padding-bottom:20px">
        <label class="form-label">{{ $section['label'] }}</label>

        @if($section['type'] === 'text')
          <textarea name="{{ $inputName }}" class="form-control" rows="3" placeholder="Leave blank to use the default text">{{ old($inputName, $section['value']) }}</textarea>

        @elseif($section['type'] === 'video_link')
          <input type="url" name="{{ $inputName }}" class="form-control" placeholder="https://... (leave blank to use the default)" value="{{ old($inputName, $section['value']) }}"/>

        @elseif($section['type'] === 'image')
          @if($section['value'])
            <div style="margin-bottom:10px">
              <img src="{{ $section['value'] }}" alt="{{ $section['label'] }}" style="max-width:280px;max-height:160px;border-radius:8px;border:1px solid var(--gray-200);object-fit:cover;display:block"/>
              <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:12.5px;color:var(--gray-500);cursor:pointer">
                <input type="checkbox" name="{{ $inputName }}_clear" value="1"/> Remove this image (revert to default)
              </label>
            </div>
          @else
            <div class="form-hint" style="margin-bottom:8px">No custom image set — showing the default.</div>
          @endif
          <input type="file" name="{{ $inputName }}" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp"/>
          <div class="form-hint">JPG, PNG, GIF, or WEBP — max 5 MB. Leave empty to keep the current image.</div>
        @endif
      </div>
      @endforeach
    </div>
    <div class="modal-footer">
      <a href="{{ route('ec.page-content') }}" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Save Changes</button>
    </div>
  </div>
</form>
@endsection
