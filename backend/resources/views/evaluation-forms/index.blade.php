<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluation Forms') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ Route::has($routePrefix.'.show') ? route($routePrefix.'.show', $training) : route($routePrefix.'.index') }}" style="color:var(--blue-primary)">{{ $training->title }}</a>
                &rsaquo; <span>Evaluation Forms</span>
            </div>
            <h1>Evaluation Forms</h1>
        </div>
        <a href="{{ route($routePrefix.'.evaluation-forms.create', $training) }}" class="btn btn-primary">&#43; New Evaluation Form</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Title</th><th>Status</th><th>Questions</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($training->evaluationForms as $form)
                        <tr>
                            <td><strong>{{ $form->title }}</strong></td>
                            <td><span class="badge {{ $form->status === 'published' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($form->status) }}</span></td>
                            <td>{{ $form->questions()->count() }}</td>
                            <td>
                                <a href="{{ route($routePrefix.'.evaluation-forms.show', [$training, $form]) }}" class="btn btn-sm btn-outline">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">No evaluation forms yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
