<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $form->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route('beneficiary.evaluation-forms.index') }}" style="color:var(--blue-primary)">Evaluation Forms</a>
                &rsaquo; <span>{{ $form->title }}</span>
            </div>
            <h1>{{ $form->title }}</h1>
        </div>
    </div>

    <div class="card" style="max-width:640px">
        <div class="card-body">
            <form method="POST" action="{{ route('beneficiary.evaluation-forms.store', $form) }}">
                @csrf

                @foreach ($form->questions as $question)
                    <div class="form-group" style="padding-bottom:16px;border-bottom:1px solid var(--gray-100);margin-bottom:16px">
                        <label class="form-label">{{ $question->question_text }}</label>

                        @if ($question->question_type === 'rating')
                            <div style="display:flex;gap:16px;margin-top:8px">
                                @foreach ([1, 2, 3, 4, 5] as $value)
                                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-700)">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $value }}"
                                               @checked(old('answers.'.$question->id) == $value) required>
                                        {{ $value }}
                                    </label>
                                @endforeach
                            </div>
                        @elseif ($question->question_type === 'yes_no')
                            <div style="display:flex;gap:16px;margin-top:8px">
                                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-700)">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="yes"
                                           @checked(old('answers.'.$question->id) === 'yes') required>
                                    {{ __('Yes') }}
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-700)">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="no"
                                           @checked(old('answers.'.$question->id) === 'no') required>
                                    {{ __('No') }}
                                </label>
                            </div>
                        @else
                            <textarea name="answers[{{ $question->id }}]" class="form-control" rows="3" required>{{ old('answers.'.$question->id) }}</textarea>
                        @endif

                        <x-input-error :messages="$errors->get('answers.'.$question->id)" class="mt-2" />
                    </div>
                @endforeach

                <div style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> {{ __('Submit Responses') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
