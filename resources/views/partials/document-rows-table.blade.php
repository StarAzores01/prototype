{{--
  Shared document-row table — used for every group rendered on the
  Documents pages (a Program's own documents, an Activity's sub-group under
  a Program, and the trailing "General" bucket), on both ec/documents.blade
  and trainer/documents.blade. Extracted so this ~60-line row markup isn't
  duplicated once per group per page.

  Within whatever group it's given, documents are further sub-grouped by
  upload date (Today / Yesterday / a specific date) — the app's original
  date-grouping scheme, now nested one level under Program/Activity/General
  instead of replacing it, so uploads stay organized both by what they
  belong to and by when they were added.

  Expects (all optional except $documents):
    $documents     Collection of Document (with training/program/activity/
                    uploader/archivedBy eager-loaded by the controller)
    $storeRoute    route() for this role's documents.store endpoint
    $archived      bool — true renders "Archived by/on" + a Restore button
                   instead of an Archive button (default false)
    $showUploader  bool — show the Uploaded/Shared By column (default true)
    $canManage     bool — show the visibility select + Archive/Restore +
                   Delete actions at all; false renders a fully read-only
                   row (view/download/open-link only) — used for a
                   trainer's "Shared Documents" (default true)
    $showLinkedTo  bool — show a "Linked To" column; only useful for the
                   General bucket, since Program/Activity groups already
                   convey this via their own section header (default false)

  Calls page-level JS the host page must define once:
    submitDocAction(action, id, name, needsConfirm) — routes to the page's
    own single shared hidden form (mirrors the pattern ec/documents.blade
    already used for delete, generalized to also cover archive/unarchive).
--}}
@php
  $storeRoute    = $storeRoute ?? '';
  $archived      = $archived ?? false;
  $showUploader  = $showUploader ?? true;
  $canManage     = $canManage ?? true;
  $showLinkedTo  = $showLinkedTo ?? false;

  $visColor = ['private' => 'var(--red)', 'ec_trainer' => 'var(--blue-primary)', 'public' => 'var(--green)'];
  $linkIcon = ['gdrive' => 'fa-brands fa-google-drive', 'youtube' => 'fa-brands fa-youtube', 'external' => 'fa-solid fa-arrow-up-right-from-square'];

  $today     = now()->startOfDay();
  $yesterday = $today->copy()->subDay();
  $dateGrouped = $documents
    ->groupBy(function ($d) use ($today, $yesterday) {
      $ts = $d->created_at;
      if (! $ts) return 'Unknown Date';
      // ->copy() first — Carbon's startOfDay() mutates in place, and $ts
      // is the same object as $d->created_at; without the copy, grouping
      // silently zeroed every document's stored upload time to midnight,
      // which then showed as "12:00 AM" in the Time column below.
      $day = $ts->copy()->startOfDay();
      if ($day->eq($today))     return 'Today';
      if ($day->eq($yesterday)) return 'Yesterday';
      return $ts->format('F j, Y');
    })
    // Order the date groups themselves newest-first, by each group's most
    // recent document — guarantees Today, then Yesterday, then older dates
    // regardless of the order $documents arrived in, rather than relying
    // on it already being pre-sorted by the caller.
    ->sortByDesc(fn ($group) => optional($group->max('created_at'))->timestamp ?? 0);
@endphp

{{--
  Every group on the page (a Program's documents, an Activity's sub-group,
  the General bucket, each further split by upload date) renders its OWN
  <table> — there's no single table spanning the whole page. Left to their
  own devices, plain HTML tables each auto-size their columns from just
  their own rows, so the same "Document"/"Type"/"Time" column doesn't line
  up between one group's table and the next whenever the row content
  happens to differ in length (a long link URL vs. a short filename, etc.)
  — the ragged look from stacking several independently-sized tables.
  Fixing that needs every table on the page to agree on the same column
  widths regardless of its own content, which is what the .doc-table rule
  + <colgroup> below do: table-layout:fixed makes each table obey the
  widths in its <colgroup> instead of measuring its own content, and since
  every table's <colgroup> is generated from the same conditionals as its
  <thead>, the columns land in the same place table after table.
--}}
@once
<style>
table.doc-table { table-layout: fixed; }
table.doc-table th, table.doc-table td { overflow: hidden; text-overflow: ellipsis; }
</style>
@endonce

@foreach($dateGrouped as $dateLabel => $dateGroup)
  <div class="doc-date-group-header" style="font-size:11px;opacity:.85">
    <i class="fas fa-calendar-alt" style="margin-right:6px;opacity:.6"></i>{{ $dateLabel }}
    <span style="font-weight:400;margin-left:8px;opacity:.7">({{ $dateGroup->count() }} file{{ $dateGroup->count() === 1 ? '' : 's' }})</span>
  </div>
  <div class="table-wrap">
  <table class="data-table doc-table">
    <colgroup>
      {{--
        Document is the one column that isn't a fixed pixel width, but it
        still needs a cap — left fully open, it soaks up 100% of whatever
        the fixed columns below don't use, which on a wide screen with few
        other columns (e.g. the read-only Trainer view, no Visibility
        column) stretched it out into a huge gap between the filename and
        the next column. 45% keeps it generous for long names/links
        without ballooning past what the content actually needs.
      --}}
      <col style="width:45%"/> {{-- Document --}}
      <col style="width:90px"/> {{-- Type --}}
      @if($showLinkedTo)<col style="width:190px"/>@endif {{-- Linked To --}}
      @if($canManage)<col style="width:170px"/>@endif {{-- Visibility --}}
      @if($showUploader)<col style="width:150px"/>@endif {{-- Uploaded By --}}
      <col style="width:90px"/> {{-- Time --}}
      <col style="width:{{ $canManage ? 180 : 110 }}px"/> {{-- Actions --}}
    </colgroup>
    <thead>
      <tr>
        <th>Document</th>
        <th>Type</th>
        @if($showLinkedTo)<th class="doc-table-linked-to">Linked To</th>@endif
        @if($canManage)<th>Visibility</th>@endif
        @if($showUploader)<th class="doc-table-uploaded-by">Uploaded By</th>@endif
        <th>Time</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    @foreach($dateGroup as $d)
      @php
        $vis = $d->visibility ?? 'public';
        $openUrl = $d->isLink() ? $d->link_url : route('files.document', $d);
      @endphp
      <tr>
        <td>
          <a href="{{ $openUrl }}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;min-width:0;text-decoration:none;color:inherit">
            @if($d->isLink())
              <span style="width:32px;height:32px;border-radius:8px;background:var(--blue-soft);color:var(--blue-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                <i class="{{ $linkIcon[$d->link_type] ?? 'fa-solid fa-link' }}"></i>
              </span>
            @else
              @php
                $ico = match(strtolower($d->file_type ?? '')) {
                  'pdf'  => ['fa-file-pdf',  '#FEE2E2', '#EF4444'],
                  'doc','docx' => ['fa-file-word', '#DBEAFE', '#1A56DB'],
                  'xls','xlsx' => ['fa-file-excel','#D1FAE5','#10B981'],
                  'jpg','jpeg','png','gif','webp' => ['fa-file-image','#E0E7FF','#6366F1'],
                  'mp4' => ['fa-file-video','#FEF3C7','#F59E0B'],
                  default => ['fa-file','#F1F5F9','#64748B'],
                };
              @endphp
              <span style="width:32px;height:32px;border-radius:8px;background:{{ $ico[1] }};color:{{ $ico[2] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
                <i class="fas {{ $ico[0] }}"></i>
              </span>
            @endif
            <div style="min-width:0">
              <div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px" title="{{ $d->original_name }}">{{ $d->original_name }}</div>
              @if($archived)
                <div style="font-size:11px;color:var(--gray-400)">Archived by {{ $d->archivedBy->full_name ?? '—' }} &middot; {{ $d->archived_at?->format('M d, Y') ?? '—' }}</div>
              @endif
            </div>
          </a>
        </td>
        <td><span style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-500)">{{ $d->isLink() ? $d->link_type : $d->file_type }}</span></td>
        @if($showLinkedTo)
        <td class="doc-table-linked-to" style="font-size:12px;color:var(--gray-600)">
          @if($d->program)
            <i class="fas fa-diagram-project" style="color:var(--gray-400)"></i> {{ Str::limit($d->program->title, 28) }}
          @elseif($d->activity)
            <i class="fas fa-book" style="color:var(--gray-400)"></i> {{ Str::limit($d->activity->title, 28) }}
          @else
            {{ Str::limit($d->training->title ?? 'General', 28) }}
          @endif
        </td>
        @endif
        @if($canManage)
        <td>
          <form method="POST" action="{{ $storeRoute }}" style="display:inline">
            @csrf
            <input type="hidden" name="action" value="set_visibility"/>
            <input type="hidden" name="doc_id" value="{{ $d->id }}"/>
            <select name="visibility" class="filter-select" style="font-size:12px;padding:4px 8px;border-radius:6px;color:{{ $visColor[$vis] }};max-width:100%;box-sizing:border-box" onchange="this.form.submit()">
              <option value="private" {{ $vis === 'private' ? 'selected' : '' }}>Private</option>
              <option value="ec_trainer" {{ $vis === 'ec_trainer' ? 'selected' : '' }}>EC &amp; Project Leaders</option>
              <option value="public" {{ $vis === 'public' ? 'selected' : '' }}>Public</option>
            </select>
          </form>
        </td>
        @endif
        @if($showUploader)
        <td class="doc-table-uploaded-by" style="font-size:12px">{{ $d->uploader?->full_name ?? '—' }}</td>
        @endif
        <td style="font-size:12px;color:var(--gray-400);white-space:nowrap">{{ $d->created_at?->format('g:i A') ?? '—' }}</td>
        <td>
          <div class="action-btns" style="flex-wrap:wrap">
            @if($d->isLink())
            <a href="{{ $d->link_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline" title="Open link"><i class="fas fa-arrow-up-right-from-square"></i></a>
            @else
            <a href="{{ route('files.document', $d) }}?download=1" class="btn btn-sm btn-outline" title="Download"><i class="fas fa-arrow-down"></i></a>
            <a href="{{ route('files.document', $d) }}" target="_blank" class="btn btn-sm btn-outline" title="Preview"><i class="fas fa-eye"></i></a>
            @endif
            @if($canManage)
              @if($archived)
              <button type="button" class="btn btn-sm btn-outline" onclick="submitDocAction('unarchive', {{ $d->id }}, '', false)" title="Restore"><i class="fas fa-rotate-left"></i></button>
              @else
              <button type="button" class="btn btn-sm btn-outline" onclick="submitDocAction('archive', {{ $d->id }}, '', false)" title="Archive"><i class="fas fa-box-archive"></i></button>
              @endif
              <button type="button" class="btn btn-sm btn-danger" onclick="submitDocAction('delete', {{ $d->id }}, '{{ addslashes($d->original_name) }}', true)" title="Delete"><i class="fas fa-trash"></i></button>
            @endif
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  </div>
@endforeach
