{{--
  Shared nav "auth actions" block (Log In / Sign Up ↔ user + Go to Dashboard).

  The rest of the nav (brand, page links) stays in each page since it differs
  per page (active link, link set). This partial only owns the part that
  actually depends on auth state.

  Expects: $loggedIn, $userName, $roleLabel, $dashboardUrl — every page that
  includes this must have its controller pass those (see
  App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData).

  Optional: $navPrefix — pages built from the "lp-" prefixed CSS family
  (landing.blade.php, about.blade.php) pass navPrefix="lp-"; pages built from
  the unprefixed family (contact.blade.php, trainings-public.blade.php) omit
  it. Either way this reuses each page's own .{prefix}nav-actions /
  .{prefix}btn-login / .{prefix}btn-signup styles, so it looks native on both.
--}}
@php $navPrefix = $navPrefix ?? ''; @endphp
@if($loggedIn)
  <div class="{{ $navPrefix }}nav-actions">
    <span style="font-size:13px;font-weight:600;color:rgba(255,255,255,.85);display:inline-flex;align-items:center;gap:6px;white-space:nowrap">
      <i class="fas fa-circle-user"></i> {{ $userName }}
      <span style="color:rgba(255,255,255,.5);font-weight:500">({{ $roleLabel }})</span>
    </span>
    <a href="{{ $dashboardUrl }}" class="{{ $navPrefix }}btn-signup">
      <i class="fas fa-gauge"></i> Go to Dashboard
    </a>
  </div>
@else
  <div class="{{ $navPrefix }}nav-actions">
    <a href="{{ route('login') }}" class="{{ $navPrefix }}btn-login">
      <i class="fas fa-arrow-right"></i> Log In
    </a>
    <a href="{{ route('choose-role') }}" class="{{ $navPrefix }}btn-signup">
      <i class="fas fa-plus"></i> Sign Up
    </a>
  </div>
@endif
