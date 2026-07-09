<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessments') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ Route::has($routePrefix.'.show') ? route($routePrefix.'.show', $training) : route($routePrefix.'.index') }}" style="color:var(--blue-primary)">{{ $training->title }}</a>
                &rsaquo; <span>Impact Assessments</span>
            </div>
            <h1>Impact Assessments</h1>
        </div>
        <a href="{{ route($routePrefix.'.impact-assessments.create', $training) }}" class="btn btn-primary">&#43; Request Assessment</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Beneficiary</th><th>Status</th><th>Submitted</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    @forelse ($training->impactAssessments as $assessment)
                        <tr>
                            <td><strong>{{ $assessment->user->name }}</strong></td>
                            <td>
                                <span class="badge {{ $assessment->status === 'reviewed' ? 'badge-completed' : ($assessment->status === 'submitted' ? 'badge-submitted' : 'badge-pending') }}">
                                    {{ ucfirst($assessment->status) }}
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--gray-400)">{{ $assessment->submitted_at?->format('Y-m-d') ?? '—' }}</td>
                            <td style="font-size:12px;color:var(--gray-600)">{{ \Illuminate\Support\Str::limit($assessment->notes, 60) ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">No impact assessments requested yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
