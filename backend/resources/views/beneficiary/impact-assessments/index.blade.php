<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessments') }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">PAThrive &rsaquo; <span>Impact Assessments</span></div>
            <h1>Impact Assessments</h1>
            <p>Requests from your Extension Coordinator / Project Leader</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Training</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($assessments as $assessment)
                        <tr>
                            <td><strong>{{ $assessment->training->title }}</strong></td>
                            <td>
                                <span class="badge {{ $assessment->status === 'reviewed' ? 'badge-completed' : ($assessment->status === 'submitted' ? 'badge-submitted' : 'badge-pending') }}">
                                    {{ ucfirst($assessment->status) }}
                                </span>
                            </td>
                            <td>
                                @if ($assessment->status === 'pending')
                                    <a href="{{ route('beneficiary.impact-assessments.edit', $assessment) }}" class="btn btn-sm btn-primary">Fill In</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;padding:32px;color:var(--gray-400)">No impact assessments requested from you yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
