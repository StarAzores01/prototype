{{--
  Reusable profile avatar card section.

  Required variables (pass via @include or component):
    $avatarUrl   — route('files.avatar') or null
    $initials    — e.g. "JD"
    $uploadRoute — named route for the POST upload action
    $colorClass  — optional extra class on .profile-pic-initials (e.g. 'evaluator')

  Usage:
    @include('partials.profile-avatar', [
        'avatarUrl'   => $ecUser->avatar ? route('files.avatar') : null,
        'initials'    => $initials,
        'uploadRoute' => route('ec.profile.update'),
        'colorClass'  => '',
    ])
--}}
@php $colorClass = $colorClass ?? ''; @endphp

<div class="profile-pic-wrap" id="profilePicWrap">
  @if($avatarUrl)
    <img
      src="{{ $avatarUrl }}"
      alt="Profile picture"
      class="profile-pic-img"
      id="profilePicPreview"
      onerror="this.style.display='none';document.getElementById('profilePicInitials').style.display='flex';"
    />
    <div class="profile-pic-initials {{ $colorClass }}" id="profilePicInitials" style="display:none">{{ $initials }}</div>
  @else
    <img src="" alt="" class="profile-pic-img" id="profilePicPreview" style="display:none"/>
    <div class="profile-pic-initials {{ $colorClass }}" id="profilePicInitials">{{ $initials }}</div>
  @endif

  {{-- Camera/pencil overlay button triggers the hidden file input --}}
  <label class="profile-pic-change-btn" title="Change profile picture" aria-label="Change profile picture" for="avatarFileInput">
    <i class="fas fa-camera"></i>
  </label>
</div>

{{-- Hidden upload form — submitted once the user confirms in the
     "Upload this image?" panel triggered on file selection --}}
<form
  method="POST"
  action="{{ $uploadRoute }}"
  enctype="multipart/form-data"
  id="avatarUploadForm"
  style="display:none"
>
  @csrf
  <input type="hidden" name="action" value="upload_avatar"/>
  <input
    type="file"
    name="avatar"
    id="avatarFileInput"
    accept="image/jpeg,image/png,image/webp,image/gif"
    style="display:none"
    onchange="previewAndUploadAvatar(this)"
  />
</form>

<p style="font-size:11px;color:var(--gray-400);margin-top:6px;text-align:center">
  JPG, PNG, WEBP, GIF &bull; max 2 MB
</p>

<script>
(function () {
  function previewAndUploadAvatar(input) {
    if (! input.files || ! input.files[0]) return;

    var file = input.files[0];

    /* Client-side size guard before even sending */
    if (file.size > 2 * 1024 * 1024) {
      alert('The image must be smaller than 2 MB.');
      input.value = '';
      return;
    }

    /* Custom "Upload this image?" confirmation (see partials.layout-
       scripts / partials.upload-confirm-modal) instead of uploading
       immediately. The page's own avatar preview is left untouched
       until the upload is actually confirmed and the page reloads with
       the new picture — canceling just clears the file input so the
       same file can be reselected. */
    window.pathriveConfirmImageUpload(
      document.getElementById('avatarUploadForm'),
      file,
      { title: 'Upload this image?', onCancel: function () { input.value = ''; } }
    );
  }

  /* Expose to inline onchange handler */
  window.previewAndUploadAvatar = previewAndUploadAvatar;
})();
</script>
