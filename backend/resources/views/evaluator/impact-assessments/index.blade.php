<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessments Awaiting Review') }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">PAThrive &rsaquo; <span>Impact Assessments</span></div>
            <h1>Impact Assessments Awaiting Review</h1>
            <p>Submitted by beneficiaries across all trainings</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Training</th><th>Beneficiary</th><th>Submitted</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($assessments as $assessment)
                        <tr>
                            <td><strong>{{ $assessment->training->title }}</strong></td>
                            <td>{{ $assessment->user->name }}</td>
                            <td style="font-size:12px;color:var(--gray-400)">{{ $assessment->submitted_at?->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('evaluator.impact-assessments.show', $assessment) }}" class="btn btn-sm btn-primary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">Nothing awaiting review right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
