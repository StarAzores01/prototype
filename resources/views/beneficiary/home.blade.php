@extends('layouts.beneficiary')

@section('content')

<style>
/* ── Hero banner ── */
.b-hero {
  background: linear-gradient(135deg, #09182F 0%, #0D2348 55%, #152F65 100%);
  border-radius: 20px;
  padding: 40px 40px 36px;
  position: relative;
  overflow: hidden;
  margin-bottom: 24px;
}
.b-hero::before {
  content: '';
  position: absolute; top: -60px; right: -60px;
  width: 320px; height: 320px; border-radius: 50%;
  background: radial-gradient(circle, rgba(56,189,248,.12) 0%, transparent 65%);
  pointer-events: none;
}
.b-hero::after {
  content: '';
  position: absolute; bottom: -40px; left: -40px;
  width: 200px; height: 200px; border-radius: 50%;
  background: radial-gradient(circle, rgba(26,86,219,.15) 0%, transparent 65%);
  pointer-events: none;
}
.b-hero-inner { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
.b-hero-eyebrow {
  display: inline-flex; align-items: center; gap: 7px;
  background: rgba(56,189,248,.12); border: 1px solid rgba(56,189,248,.25);
  color: #38BDF8; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1.2px;
  padding: 4px 12px; border-radius: 40px; margin-bottom: 10px;
}
.b-hero-dot { width: 6px; height: 6px; border-radius: 50%; background: #38BDF8; animation: bpulse 2s ease-in-out infinite; }
@keyframes bpulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }
.b-hero-title { font-size: clamp(22px,3vw,32px); font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 8px; }
.b-hero-title span { color: #38BDF8; }
.b-hero-sub { font-size: 13.5px; color: rgba(255,255,255,.55); line-height: 1.65; max-width: 420px; }
.b-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
.b-hero-btn-primary {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 22px; border-radius: 9px;
  background: linear-gradient(135deg,#1A56DB,#2E6BF0);
  color: #fff; font-size: 13px; font-weight: 700;
  text-decoration: none; transition: all .22s;
  box-shadow: 0 3px 12px rgba(26,86,219,.4);
}
.b-hero-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 18px rgba(26,86,219,.55); }
.b-hero-btn-ghost {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 20px; border-radius: 9px;
  background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.18);
  color: rgba(255,255,255,.8); font-size: 13px; font-weight: 600;
  text-decoration: none; transition: all .22s;
}
.b-hero-btn-ghost:hover { background: rgba(255,255,255,.15); color: #fff; }

/* ── Stat cards ── */
.b-stats { display: grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap: 16px; margin-bottom: 24px; }
.b-stat {
  background: var(--surface); border-radius: 16px; padding: 20px;
  border: 1px solid var(--gray-200); text-align: center;
  transition: all .22s;
}
.b-stat:hover { box-shadow: 0 8px 28px rgba(9,24,47,.09); transform: translateY(-2px); }
.b-stat-val { font-size: 30px; font-weight: 800; line-height: 1; margin-bottom: 5px; }
.b-stat-lbl { font-size: 12px; color: var(--gray-400); }

/* ── Section title ── */
.b-sec-title { font-size: 15px; font-weight: 700; color: var(--text-heading); margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.b-sec-title a { font-size: 12px; font-weight: 600; color: var(--blue-primary); text-decoration: none; margin-left: auto; }

/* ── Activity mini cards ── */
.b-training-list { display: flex; flex-direction: column; gap: 12px; }
.b-tc {
  display: flex; align-items: center; gap: 14px;
  background: var(--surface); border-radius: 14px; padding: 14px 16px;
  border: 1px solid var(--gray-200); transition: all .22s;
  text-decoration: none;
}
.b-tc:hover { box-shadow: 0 6px 20px rgba(9,24,47,.09); border-color: var(--blue-light); transform: translateX(3px); }
.b-tc-ico { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; background: #EFF6FF; }
.b-tc-title { font-size: 13.5px; font-weight: 700; color: var(--text-heading); margin-bottom: 3px; }
.b-tc-meta { font-size: 11.5px; color: var(--gray-400); }
.b-tc-badge { margin-left: auto; flex-shrink: 0; }

/* ── Notification items ── */
.b-notif-list { display: flex; flex-direction: column; gap: 10px; }
.b-notif {
  display: flex; align-items: flex-start; gap: 12px;
  background: #EFF6FF; border-radius: 12px; padding: 12px 14px;
  border: 1px solid #DBEAFE;
}
.b-notif-ico { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.b-notif-msg { font-size: 13px; font-weight: 600; color: var(--navy); line-height: 1.45; }
.b-notif-time { font-size: 11px; color: var(--gray-400); margin-top: 3px; }

/* ── Quick links ── */
.b-quick { display: grid; grid-template-columns: repeat(auto-fit,minmax(130px,1fr)); gap: 12px; }
.b-ql {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  background: var(--surface); border-radius: 14px; padding: 18px 12px;
  border: 1px solid var(--gray-200); text-decoration: none;
  transition: all .22s; text-align: center;
}
.b-ql:hover { box-shadow: 0 6px 20px rgba(9,24,47,.09); border-color: var(--blue-light); transform: translateY(-2px); }
.b-ql-ico { font-size: 26px; color: var(--blue-primary); }
.b-ql-lbl { font-size: 12px; font-weight: 600; color: var(--text-heading); }

@media (max-width: 640px) {
  .b-hero { padding: 28px 20px; }
  .b-hero-title { font-size: 22px; }
}
</style>

<!-- Hero -->
<div class="b-hero">
  <div class="b-hero-inner">
    <div>
      <div class="b-hero-eyebrow"><span class="b-hero-dot"></span> Participant Portal</div>
      <div class="b-hero-title">
        Welcome back,<br/><span>{{ $userFirstName }}</span>
      </div>
      <div class="b-hero-sub">
        Track your activities, submit evaluations, and respond to skills surveys — all in one place.
      </div>
      <div class="b-hero-actions">
        <a href="{{ route('beneficiary.trainings') }}" class="b-hero-btn-primary"><i class="fas fa-book"></i> My Activities</a>
        @if($pendingEvals + $pendingSkills > 0)
        <a href="{{ route('beneficiary.evaluations') }}" class="b-hero-btn-ghost">
          <i class="fas fa-triangle-exclamation"></i> {{ $pendingEvals + $pendingSkills }} Pending
        </a>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="b-stats">
  <div class="b-stat">
    <div class="b-stat-val" style="color:var(--blue-primary)">{{ $totalTrainings }}</div>
    <div class="b-stat-lbl">Activities Enrolled</div>
  </div>
  <div class="b-stat">
    <div class="b-stat-val" style="color:var(--green)">{{ $completedTrainings }}</div>
    <div class="b-stat-lbl">Completed</div>
  </div>
  <div class="b-stat">
    <div class="b-stat-val" style="color:{{ $pendingEvals > 0 ? '#F59E0B' : 'var(--gray-400)' }}">{{ $pendingEvals }}</div>
    <div class="b-stat-lbl">Pending Evaluations</div>
  </div>
  <div class="b-stat">
    <div class="b-stat-val" style="color:{{ $pendingSkills > 0 ? '#F59E0B' : 'var(--gray-400)' }}">{{ $pendingSkills }}</div>
    <div class="b-stat-lbl">Pending Surveys</div>
  </div>
</div>

<!-- Main grid -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

  <!-- Left: recent activities + quick links -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Recent Activities -->
    <div class="card">
      <div class="card-body" style="padding-bottom:0">
        <div class="b-sec-title">
          <i class="fas fa-book"></i> Recent Activities
          <a href="{{ route('beneficiary.trainings') }}">View all <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
      @if($recentTrainings->isEmpty())
      <div class="card-body" style="text-align:center;padding:40px;color:var(--gray-400)">
        You haven't been enrolled in any activities yet.
      </div>
      @else
      <div class="card-body" style="padding-top:0">
        <div class="b-training-list">
          @php $sc = ['Proposed' => 'badge-proposed', 'Approved' => 'badge-approved', 'Ongoing' => 'badge-ongoing', 'Completed' => 'badge-completed']; @endphp
          @foreach($recentTrainings as $t)
            @php $icon = \App\Support\TrainingCategoryIcon::icon($t->area); @endphp
            <a href="{{ route('beneficiary.trainings') }}?view={{ $t->id }}" class="b-tc">
              <div class="b-tc-ico"><i class="fas {{ $icon }}" style="color:var(--blue-primary)"></i></div>
              <div style="flex:1;min-width:0">
                <div class="b-tc-title">{{ $t->title }}</div>
                <div class="b-tc-meta">
                  <i class="fas fa-calendar"></i> {{ $t->date_start?->format('Y-m-d') ?? '—' }}
                  &nbsp;&middot;&nbsp; <i class="fas fa-user"></i> {{ $t->trainer->full_name ?? 'TBA' }}
                </div>
              </div>
              <div class="b-tc-badge">
                <span class="badge {{ $sc[$t->status] ?? 'badge-proposed' }}">{{ $t->status }}</span>
              </div>
            </a>
          @endforeach
        </div>
      </div>
      @endif
    </div>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-body">
        <div class="b-sec-title"><i class="fas fa-bolt"></i> Quick Access</div>
        <div class="b-quick">
          <a href="{{ route('beneficiary.trainings') }}" class="b-ql">
            <span class="b-ql-ico"><i class="fas fa-book"></i></span>
            <span class="b-ql-lbl">Activities</span>
          </a>
          <a href="{{ route('beneficiary.evaluations') }}" class="b-ql">
            <span class="b-ql-ico"><i class="fas fa-star"></i></span>
            <span class="b-ql-lbl">Evaluations</span>
            @if($pendingEvals > 0)
            <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700">{{ $pendingEvals }}</span>
            @endif
          </a>
          <a href="{{ route('beneficiary.skills') }}" class="b-ql">
            <span class="b-ql-ico"><i class="fas fa-chart-line"></i></span>
            <span class="b-ql-lbl">Skills Survey</span>
            @if($pendingSkills > 0)
            <span style="background:var(--red);color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700">{{ $pendingSkills }}</span>
            @endif
          </a>
          <a href="{{ route('beneficiary.profile') }}" class="b-ql">
            <span class="b-ql-ico"><i class="fas fa-user"></i></span>
            <span class="b-ql-lbl">My Profile</span>
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- Right: notifications -->
  <div class="card">
    <div class="card-body">
      <div class="b-sec-title">
        <i class="fas fa-bell"></i> Notifications
        @if($notifications->isNotEmpty())
        <a href="{{ route('beneficiary.notifications', ['mark_all_read' => 1]) }}">Mark all read</a>
        @endif
      </div>
      @if($notifications->isEmpty())
      <div style="text-align:center;padding:32px 16px;color:var(--gray-400)">
        <div style="font-size:28px;margin-bottom:8px"><i class="fas fa-bell"></i></div>
        <div style="font-size:13px">You're all caught up!</div>
      </div>
      @else
      <div class="b-notif-list">
        @foreach($notifications as $n)
        <a href="{{ $n->link ?? '#' }}" style="text-decoration:none">
          <div class="b-notif">
            <div class="b-notif-ico"><i class="fas fa-bell"></i></div>
            <div>
              <div class="b-notif-msg">{{ $n->message }}</div>
              <div class="b-notif-time">{{ $n->created_at->format('M d, g:i A') }}</div>
            </div>
          </div>
        </a>
        @endforeach
      </div>
      <a href="{{ route('beneficiary.notifications') }}"
         style="display:block;text-align:center;margin-top:14px;font-size:12.5px;color:var(--blue-primary);text-decoration:none;font-weight:600">
        View all notifications <i class="fas fa-arrow-right"></i>
      </a>
      @endif
    </div>
  </div>

</div>
@endsection
