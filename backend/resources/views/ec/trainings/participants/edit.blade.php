<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Participant Status') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" style="color:var(--blue-primary)">Participants</a>
                &rsaquo; <span>Edit Status</span>
            </div>
            <h1>{{ $participant->user->name }}</h1>
            <p>{{ $participant->user->email }}</p>
        </div>
    </div>

    <div class="card" style="max-width:520px">
        <div class="card-body">
            <form method="POST" action="{{ route('extension-coordinator.trainings.participants.update', [$training, $participant]) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label" for="status">{{ __('Status') }}</label>
                    <select id="status" name="status" class="form-control">
                        @foreach (['enrolled', 'completed', 'dropped'] as $option)
                            <option value="{{ $option }}" @selected(old('status', $participant->status) === $option)>
                                {{ ucfirst($option) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:16px">
                    <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">&#10003; {{ __('Save Status') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
