{{--
  Shared "Upload this image?" confirmation panel — used for every
  image/banner/cover upload (profile picture, program cover image,
  activity display picture) instead of uploading immediately on file
  selection. One instance per page, reused by whichever form triggers it.

  Markup reuses the existing .modal-overlay/.modal/.modal-header/
  .modal-body/.modal-footer classes (see assets/css/style.css) so it
  matches every other modal in the app instead of introducing a new
  visual pattern.

  Driven by the helpers defined in partials.layout-scripts:
    pathriveConfirmImageUpload(form, file, opts)
    pathriveRequestImageUpload(form, opts)   — for explicit Save buttons
--}}
<div class="modal-overlay" id="modal-imageUploadConfirm">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h2 id="imageUploadConfirmTitle"><i class="fas fa-image"></i> Upload this image?</h2>
      <button type="button" class="modal-close" id="imageUploadCancelXBtn" aria-label="Cancel"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body" style="text-align:center">
      <img
        id="imageUploadConfirmPreview"
        alt="Selected image preview"
        style="max-width:100%;max-height:260px;border-radius:10px;box-shadow:var(--shadow-lg);object-fit:contain"
      />
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" id="imageUploadCancelBtn">Cancel</button>
      <button type="button" class="btn btn-primary" id="imageUploadConfirmBtn"><i class="fas fa-check"></i> Confirm</button>
    </div>
  </div>
</div>
