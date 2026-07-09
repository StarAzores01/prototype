<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Training') }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route('extension-coordinator.trainings.index') }}" style="color:var(--blue-primary)">Trainings</a>
                &rsaquo; <span>New</span>
            </div>
            <h1>Create New Training</h1>
        </div>
    </div>

    <div class="card" style="max-width:640px">
        <div class="card-body">
            <form method="POST" action="{{ route('extension-coordinator.trainings.store') }}">
                @csrf

                @include('ec.trainings._form', ['projectLeaders' => $projectLeaders])

                <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:16px">
                    <a href="{{ route('extension-coordinator.trainings.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">&#10003; {{ __('Save Training') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
