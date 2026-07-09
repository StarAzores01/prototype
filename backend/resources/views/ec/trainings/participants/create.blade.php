<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Participant') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" style="color:var(--blue-primary)">Participants</a>
                &rsaquo; <span>Add</span>
            </div>
            <h1>Add Participant</h1>
        </div>
    </div>

    <div class="card" style="max-width:520px">
        <div class="card-body">
            <form method="POST" action="{{ route('extension-coordinator.trainings.participants.store', $training) }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="user_id">{{ __('Beneficiary') }}</label>
                    <select id="user_id" name="user_id" class="form-control">
                        <option value="">{{ __('— Select a beneficiary —') }}</option>
                        @foreach ($availableBeneficiaries as $beneficiary)
                            <option value="{{ $beneficiary->id }}" @selected((string) old('user_id') === (string) $beneficiary->id)>
                                {{ $beneficiary->name }} ({{ $beneficiary->email }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />

                    @if ($availableBeneficiaries->isEmpty())
                        <div style="font-size:12px;color:var(--gray-400);margin-top:6px">
                            All beneficiary accounts are already enrolled in this training.
                        </div>
                    @endif
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:16px">
                    <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">&#43; {{ __('Add Participant') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
