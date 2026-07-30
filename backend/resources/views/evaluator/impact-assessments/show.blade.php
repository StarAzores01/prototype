<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessment') }} &mdash; {{ $assessment->training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route('evaluator.impact-assessments.index') }}" style="color:var(--blue-primary)">Impact Assessments</a>
                &rsaquo; <span>{{ $assessment->training->title }}</span>
            </div>
            <h1>{{ $assessment->training->title }}</h1>
        </div>
        <span class="badge {{ $assessment->status === 'reviewed' ? 'badge-completed' : 'badge-submitted' }}">
            {{ ucfirst($assessment->status) }}
        </span>
    </div>

    <div class="card" style="max-width:640px">
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
            <div>
                <div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Beneficiary</div>
                <div style="font-size:14px;color:var(--gray-800);font-weight:500;margin-top:2px">{{ $assessment->user->name }}</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Submitted</div>
                <div style="font-size:14px;color:var(--gray-800);font-weight:500;margin-top:2px">{{ $assessment->submitted_at?->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Notes</div>
                <p style="font-size:14px;color:var(--gray-700);line-height:1.8;white-space:pre-line;margin-top:4px">{{ $assessment->notes }}</p>
            </div>

            @if ($assessment->status === 'submitted')
                <form method="POST" action="{{ route('evaluator.impact-assessments.review', $assessment) }}" style="padding-top:8px">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> {{ __('Mark as Reviewed') }}</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
