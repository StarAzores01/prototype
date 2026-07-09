<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Evaluation Form') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route($routePrefix.'.evaluation-forms.index', $training) }}" style="color:var(--blue-primary)">Evaluation Forms</a>
                &rsaquo; <span>New</span>
            </div>
            <h1>Create Evaluation Form</h1>
        </div>
    </div>

    <div class="card" style="max-width:560px">
        <div class="card-body">
            <form method="POST" action="{{ route($routePrefix.'.evaluation-forms.store', $training) }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="title">{{ __('Form Title') }}</label>
                    <input id="title" name="title" type="text" class="form-control"
                           value="{{ old('title', 'Training Evaluation Form') }}" required autofocus>
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:16px">
                    <a href="{{ route($routePrefix.'.evaluation-forms.index', $training) }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">&#43; {{ __('Create Form') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
