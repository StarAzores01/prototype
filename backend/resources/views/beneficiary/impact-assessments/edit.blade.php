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
                <a href="{{ route('beneficiary.impact-assessments.index') }}" style="color:var(--blue-primary)">Impact Assessments</a>
                &rsaquo; <span>Fill In</span>
            </div>
            <h1>{{ $assessment->training->title }}</h1>
        </div>
    </div>

    <div class="card" style="max-width:560px">
        <div class="card-body">
            <p style="font-size:13px;color:var(--gray-600);margin-bottom:16px">
                {{ __('Tell us how you have applied what you learned from this training.') }}
            </p>

            <form method="POST" action="{{ route('beneficiary.impact-assessments.update', $assessment) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label" for="notes">{{ __('Your Impact Assessment') }}</label>
                    <textarea id="notes" name="notes" class="form-control" rows="6">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:16px">
                    <a href="{{ route('beneficiary.impact-assessments.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">&#10003; {{ __('Submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
