<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluation Forms') }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">PAThrive &rsaquo; <span>Evaluation Forms</span></div>
            <h1>Evaluation Forms</h1>
            <p>Forms available for your enrolled trainings</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Training</th><th>Form</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($forms as $form)
                        <tr>
                            <td>{{ $form->training->title }}</td>
                            <td><strong>{{ $form->title }}</strong></td>
                            <td>
                                @if ($answeredFormIds->contains($form->id))
                                    <span class="badge badge-completed">Submitted</span>
                                @else
                                    <span class="badge badge-pending">Awaiting your response</span>
                                @endif
                            </td>
                            <td>
                                @unless ($answeredFormIds->contains($form->id))
                                    <a href="{{ route('beneficiary.evaluation-forms.show', $form) }}" class="btn btn-sm btn-primary">Answer</a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">No evaluation forms available right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
