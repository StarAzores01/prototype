<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Shared by every controller that can create a Document — the two
 * dashboard-level pages (Ec\DocumentController, Trainer\DocumentController)
 * plus the Program- and Activity-scoped upload actions
 * (Ec\ProgramController, Ec\TrainingController). A document is either an
 * uploaded file or a link (Google Drive/YouTube/external) — never both,
 * never neither; see Document::booted() for the model-level backstop.
 */
trait HandlesDocumentUploads
{
    private array $allowedDocumentFileTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4'];

    private int $maxDocumentFileSize = 20 * 1024 * 1024; // 20 MB, same as the original MAX_FILE_SIZE

    /**
     * Validates and creates a Document from the request. $scope carries the
     * caller-specific foreign key(s) to stamp on the row — e.g.
     * ['program_id' => $id] or ['activity_id' => $id] — or is left empty
     * for a dashboard-level "general" upload (both stay null, alongside the
     * legacy $request->training_id, unaffected by any of this).
     *
     * Returns [true, null, $document] on success, or [false, $errorMessage,
     * null] on failure. The 3rd element (the exact Document row just
     * created) is additive — existing callers destructuring only
     * [$ok, $error] are unaffected.
     */
    private function storeDocumentUpload(Request $request, int $uploadedBy, array $scope = []): array
    {
        $linkType = $request->input('link_type') ?: null;

        $rules = [
            'visibility' => 'nullable|in:private,ec_trainer,public',
        ];

        if ($linkType) {
            $rules['link_type'] = ['required', Rule::in(['gdrive', 'youtube', 'external'])];
            $rules['link_url'] = 'required|url|max:2048';
            $rules['link_title'] = 'nullable|string|max:255';
        } else {
            // mimes: content-sniffs the actual bytes (via fileinfo), not just the
            // claimed filename extension — a renamed .php/.html can't pass this.
            $rules['file'] = 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,mp4';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return [false, $validator->errors()->first(), null];
        }
        $data = $validator->validated();

        $visibility = in_array($data['visibility'] ?? null, ['private', 'ec_trainer', 'public'], true)
            ? $data['visibility']
            : 'public';

        $attrs = array_merge([
            'training_id' => null,
            'program_id'  => null,
            'activity_id' => null,
            'uploaded_by' => $uploadedBy,
            'visibility'  => $visibility,
        ], $scope);

        if ($linkType) {
            $attrs['link_type'] = $data['link_type'];
            $attrs['link_url'] = $data['link_url'];
            $attrs['original_name'] = $data['link_title'] ?: $data['link_url'];
        } else {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());

            // If the extension is missing (e.g. Windows hiding it), derive it
            // from the actual MIME type so the upload still works.
            if (empty($ext)) {
                $mimeMap = [
                    'application/pdf'                                                        => 'pdf',
                    'application/msword'                                                     => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/vnd.ms-excel'                                               => 'xls',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'      => 'xlsx',
                    'image/jpeg'                                                             => 'jpg',
                    'image/png'                                                              => 'png',
                    'video/mp4'                                                              => 'mp4',
                ];
                $ext = $mimeMap[$file->getMimeType()] ?? '';
            }

            if (! in_array($ext, $this->allowedDocumentFileTypes, true)) {
                return [false, 'File type not allowed.', null];
            }
            if ($file->getSize() > $this->maxDocumentFileSize) {
                return [false, 'File exceeds 20 MB limit.', null];
            }

            // random_bytes instead of uniqid() — uniqid() is time-based and
            // guessable, which mattered once file URLs were made access-checked
            // rather than fully public (see FileDownloadController).
            $stored = 'doc_'.bin2hex(random_bytes(8)).'.'.$ext;
            $file->storeAs('uploads', $stored, 'local');

            $attrs['original_name'] = $file->getClientOriginalName();
            $attrs['file_name'] = $stored;
            $attrs['file_type'] = $ext;
            $attrs['file_size'] = $file->getSize();
        }

        $document = Document::create($attrs);

        return [true, null, $document];
    }
}
