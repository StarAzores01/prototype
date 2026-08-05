@extends('layouts.trainer')

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive <i class="fas fa-chevron-right"></i> <span>Participants</span></div>
    <h1>Participants</h1>
    <p>Trainees enrolled in your training programs</p>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Participants</div><div class="card-subtitle">{{ $participants->count() }} records</div></div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" action="{{ route('trainer.participants') }}" class="filter-row">
      <div class="search-box"><i class="fas fa-magnifying-glass"></i><input type="text" name="q" value="{{ $q }}" placeholder="Search participant…"/></div>
      <select name="training" class="filter-select" onchange="this.form.submit()">
        <option value="">All Trainings</option>
        @foreach($myTrainings as $t)
        <option value="{{ $t->id }}" {{ $filterT == $t->id ? 'selected' : '' }}>{{ $t->title }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search</button>
      @if($q || $filterT)<a href="{{ route('trainer.participants') }}" class="btn btn-ghost btn-sm">Clear</a>@endif
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Participant Name</th><th>User ID</th><th>Phone</th><th>Age / Sex</th><th>Training Enrolled</th><th>Evaluation</th></tr></thead>
      <tbody>
      @forelse($participants as $p)
        @php
          $es = $p->evaluations->first()->status ?? 'Pending';
          $eb = $es === 'Submitted' ? 'badge-submitted' : 'badge-pending';
        @endphp
        <tr>
          <td style="color:var(--gray-400)">{{ $loop->iteration }}</td>
          <td>
            <strong>{{ $p->full_name }}</strong>
            @if($p->address)
            <div style="font-size:11px;color:var(--gray-400)"><i class="fas fa-location-dot"></i> {{ $p->address }}</div>
            @endif
          </td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->id_number ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->phone ?? '—' }}</td>
          <td style="font-size:12px;color:var(--gray-500)">{{ $p->age ? $p->age.' yrs' : '—' }}{{ ($p->age && $p->sex) ? ' · ' : '' }}{{ $p->sex ?? '' }}</td>
          <td style="font-size:12px">{{ $p->training->title ?? '—' }}</td>
          <td><span class="badge {{ $eb }}">{{ $es }}</span></td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">No participants found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
