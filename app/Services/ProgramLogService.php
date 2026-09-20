<?php

namespace App\Services;

use App\Models\Document;
use App\Models\ProgramLog;
use App\Models\Training;
use Illuminate\Support\Facades\Auth;

/**
 * Centralised write path for all Program Activity Log entries.
 *
 * Controllers must NEVER call ProgramLog::create() directly.
 * All log writes go through ProgramLogService::record().
 *
 * Security rules enforced here
 * ────────────────────────────
 * • Actor is always derived from the authenticated web-guard user —
 *   never supplied from request input.
 * • program_id is always derived from the Document model itself —
 *   never from request input, preventing cross-program log pollution.
 * • activity / training is resolved from the Document's own activity_id
 *   FK — never from caller-supplied IDs.
 * • Records are only created AFTER a successful operation; callers must
 *   not invoke this service when an operation has failed.
 * • details JSONB must never contain passwords, tokens, raw file-system
 *   paths, secrets, or credentials. Only call-site-chosen safe fields.
 *
 * Action constants
 * ────────────────
 * Use these when calling record() to keep action strings consistent.
 */
class ProgramLogService
{
    // ── Action constants ──────────────────────────────────────────────
    const ACTION_UPLOADED_FILE  = 'uploaded_file';
    const ACTION_ADDED_LINK     = 'added_link';
    const ACTION_ARCHIVED       = 'archived';
    const ACTION_RESTORED       = 'restored';
    const ACTION_DELETED        = 'deleted';
    const ACTION_BUDGET_LOGGED  = 'budget_logged';

    // ── Role display labels ───────────────────────────────────────────
    private static array $roleLabels = [
        'extension_coordinator' => 'Extension Coordinator',
        'trainer'               => 'Project Leader',
        'evaluator'             => 'Evaluator',
    ];

    /**
     * Record a document action in the Program Activity Log.
     *
     * IMPORTANT: Only call this after a successful operation.
     * Derives program context entirely from the $document model — the
     * caller never supplies program_id, training_id, or actor data.
     *
     * @param  Document    $document  The document that was acted on.
     *                                Must be a fresh (or re-fetched) model
     *                                instance so program_id / activity_id
     *                                reflect the saved state.
     * @param  string      $action    One of the ACTION_* constants above.
     * @param  array|null  $details   Optional safe supplementary metadata.
     *                                Must NOT contain passwords, tokens,
     *                                filesystem paths, or secrets.
     */
    public static function recordDocument(
        Document $document,
        string $action,
        ?array $details = null
    ): void {
        // Resolve program_id / activity scope from the document itself —
        // never from input. Priority: program_id, then activity_id, then
        // the legacy training_id. Dashboard/general documents (none of the
        // three set) have no program context — per spec, do NOT log those.
        [$programId, $trainingId, $locationName] = static::resolveScope($document);

        if ($programId === null) {
            return;
        }

        // Derive actor from the authenticated web-guard user — never from input.
        $actor     = Auth::guard('web')->user();
        $userId    = $actor?->id;
        $actorName = $actor ? trim($actor->first_name . ' ' . $actor->last_name) : 'Unknown';
        $actorRole = static::$roleLabels[$actor?->role ?? ''] ?? ucfirst($actor?->role ?? 'Unknown');

        // item_type: file extension for uploaded files; link_type for links.
        $itemType = $document->isLink()
            ? ($document->link_type ?? 'external')
            : strtolower($document->file_type ?? '');

        // item_name: the display name (original_name already stored as a
        // snapshot on the document row — safe to use even post-delete).
        $itemName = $document->original_name;

        ProgramLog::create([
            'program_id'    => $programId,
            'training_id'   => $trainingId,
            'user_id'       => $userId,
            'actor_name'    => $actorName,
            'actor_role'    => $actorRole,
            'action'        => $action,
            'entity_type'   => 'Document',
            'entity_id'     => $document->id,
            'item_name'     => $itemName,
            'item_type'     => $itemType,
            'location_name' => $locationName,
            'details'       => $details,
        ]);
    }

    /**
     * Record a budget action (set at creation, or updated later) in the
     * Program Activity Log.
     *
     * IMPORTANT: Only call this after a successful operation.
     * Derives program context directly from the $training model itself —
     * the caller never supplies program_id or actor data. Unlike
     * resolveScope() for Document, Training already carries its own
     * program_id, so no further lookup is needed.
     *
     * @param  Training    $training  The training/activity whose budget was
     *                                 acted on. Must be a fresh (or
     *                                 re-fetched) model instance so
     *                                 program_id reflects the saved state.
     * @param  string      $action    One of the ACTION_* constants above.
     * @param  array|null  $details   Optional safe supplementary metadata
     *                                (e.g. total amount, category count).
     *                                Must NOT contain passwords, tokens,
     *                                filesystem paths, or secrets.
     */
    public static function recordBudget(
        Training $training,
        string $action,
        ?array $details = null
    ): void {
        if (! $training->program_id) {
            return;
        }

        // Derive actor from the authenticated web-guard user — never from input.
        $actor     = Auth::guard('web')->user();
        $userId    = $actor?->id;
        $actorName = $actor ? trim($actor->first_name . ' ' . $actor->last_name) : 'Unknown';
        $actorRole = static::$roleLabels[$actor?->role ?? ''] ?? ucfirst($actor?->role ?? 'Unknown');

        ProgramLog::create([
            'program_id'    => (int) $training->program_id,
            'training_id'   => (int) $training->id,
            'user_id'       => $userId,
            'actor_name'    => $actorName,
            'actor_role'    => $actorRole,
            'action'        => $action,
            'entity_type'   => 'Budget',
            'entity_id'     => $training->id,
            'item_name'     => $training->title,
            'item_type'     => 'budget',
            'location_name' => $training->title ?? 'Unknown Activity',
            'details'       => $details,
        ]);
    }

    /**
     * Resolves the owning Program, the Activity (if any), and the
     * human-readable location for a document, in priority order:
     *
     *   1. document.program_id set       → [program_id, null, "Program Repository"]
     *   2. document.activity_id set      → load Training(activity_id), use its
     *                                       program_id, location = activity title
     *   3. document.training_id set      → same, via the legacy training_id column
     *   4. none of the above             → [null, null, ''] (general document,
     *                                       caller must not log this)
     *
     * @return array{0: ?int, 1: ?int, 2: string}
     */
    private static function resolveScope(Document $document): array
    {
        if ($document->program_id) {
            return [(int) $document->program_id, null, 'Program Repository'];
        }

        $activityId = $document->activity_id ?: $document->training_id;

        if ($activityId) {
            $activity = Training::where('id', $activityId)->first(['program_id', 'title']);

            if ($activity && $activity->program_id) {
                return [
                    (int) $activity->program_id,
                    (int) $activityId,
                    $activity->title ?? 'Unknown Activity',
                ];
            }
        }

        return [null, null, ''];
    }
}