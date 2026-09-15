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
        // Resolve program_id from the document itself — never from input.
        $programId = static::resolveProgramId($document);

        // Dashboard / general documents have no program context.
        // Per spec: do NOT create a log entry for these.
        if ($programId === null) {
            return;
        }

        // Resolve the activity (Training) this document belongs to, if any.
        $trainingId = $document->activity_id ?? null;

        // location_name: the activity title if activity-scoped,
        // or "Program Repository" for program-level documents.
        $locationName = static::resolveLocation($document, $trainingId);

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
     * Resolves the owning Program ID for a document.
     *
     * A document "belongs" to a program either:
     *   1. Directly — program_id is set on the document itself.
     *   2. Indirectly — activity_id is set, and that Training has a program_id.
     *
     * Returns null if neither path resolves (dashboard/general document).
     */
    private static function resolveProgramId(Document $document): ?int
    {
        if ($document->program_id) {
            return (int) $document->program_id;
        }

        if ($document->activity_id) {
            // Load only what we need — avoid a full eager-load here.
            $programId = Training::where('id', $document->activity_id)
                ->value('program_id');
            return $programId ? (int) $programId : null;
        }

        return null;
    }

    /**
     * Resolves the human-readable location for the log entry.
     *
     * Activity-scoped → the Activity's title (fetched fresh from DB,
     *   since the Training model may not be loaded on $document yet).
     * Program-scoped  → "Program Repository".
     */
    private static function resolveLocation(Document $document, ?int $trainingId): string
    {
        if ($trainingId) {
            $title = Training::where('id', $trainingId)->value('title');
            return $title ?? 'Unknown Activity';
        }

        return 'Program Repository';
    }
}