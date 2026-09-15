{{--
  One PageContent field, namespaced sections[{pageKey}][{key}] so
  Ec\PageContentController::update() can process fields spanning more than
  one page_key from a single modal's submit (e.g. the Contact page modal
  also carries the shared 'global' contact_address/email/phone).

  Expects: $pageKey, $key, $label, $type ('text'|'image'), $fieldValue,
  and optional $hint / $rows (textarea row count, default 3).

  $fieldValue is named distinctly (not $value) to avoid being silently
  overwritten by any $value variable already in the parent Blade scope —
  Blade @include merges the parent scope, so a common name like $value
  could be shadowed by an outer @foreach or a previous @include that left
  a $value variable behind, causing fields to appear blank even when a
  saved value exists.
--}}
@php $rows = $rows ?? 3; $fieldValue = $fieldValue ?? null; $extraClass = $extraClass ?? ''; @endphp
<div class="form-group">
  <label class="form-label">{{ $label }}</label>

  @if($type === 'image')
    @if($fieldValue)
      <div style="margin-bottom:10px">
        <img src="{{ $fieldValue }}" alt="{{ $label }}" style="max-width:240px;max-height:140px;border-radius:8px;border:1px solid var(--gray-200);object-fit:cover;display:block"/>
        <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:12px;color:var(--gray-500);cursor:pointer">
          <input type="checkbox" name="sections[{{ $pageKey }}][{{ $key }}_clear]" value="1"/> Remove this image (revert to default)
        </label>
      </div>
    @else
      <div class="form-hint" style="margin-bottom:8px">No custom image set — showing the default.</div>
    @endif
    <input type="file" name="sections[{{ $pageKey }}][{{ $key }}]" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp"/>
    <div class="form-hint">{{ $hint ?? 'JPG, PNG, GIF, or WEBP — max 5 MB. Leave empty to keep the current image.' }}</div>
  @else
    <textarea name="sections[{{ $pageKey }}][{{ $key }}]" class="form-control {{ $extraClass }}" rows="{{ $rows }}"
      placeholder="{{ ($fieldValue !== null && $fieldValue !== '') ? '' : 'Leave blank to use the default text' }}">{{ $fieldValue !== null ? $fieldValue : '' }}</textarea>
    @if(!empty($hint))
      <div class="form-hint">{{ $hint }}</div>
    @endif
  @endif
</div>
