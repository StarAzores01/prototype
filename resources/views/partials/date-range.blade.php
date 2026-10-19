{{-- Compact date range for program / activity cards.
     Expects $start and $end (Carbon or null).
       same month:  Oct 1–31, 2026     different month: Oct 5 – Nov 4, 2026
       different year: Dec 20, 2026 – Jan 10, 2027 --}}
@php
  $ds = $start ?? null;
  $de = $end ?? null;
  if ($ds && $de && $de->lt($ds)) { [$ds, $de] = [$de, $ds]; }
  $label = null;
  if ($ds && $de) {
      if ($ds->isSameDay($de)) {
          $label = $ds->format('M j, Y');
      } elseif ($ds->year === $de->year && $ds->month === $de->month) {
          $label = $ds->format('M j').'–'.$de->format('j, Y');
      } elseif ($ds->year === $de->year) {
          $label = $ds->format('M j').' – '.$de->format('M j, Y');
      } else {
          $label = $ds->format('M j, Y').' – '.$de->format('M j, Y');
      }
  } elseif ($ds) {
      $label = 'Starts '.$ds->format('M j, Y');
  } elseif ($de) {
      $label = 'Until '.$de->format('M j, Y');
  }
@endphp
@if($label)
<span style="font-size:11.5px;color:var(--gray-400);display:inline-flex;align-items:center;gap:5px"><i class="far fa-calendar"></i> {{ $label }}</span>
@endif
