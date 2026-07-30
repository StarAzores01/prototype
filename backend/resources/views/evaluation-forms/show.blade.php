<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $form->title }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route($routePrefix.'.evaluation-forms.index', $training) }}" style="color:var(--blue-primary)">Evaluation Forms</a>
                &rsaquo; <span>{{ $form->title }}</span>
            </div>
            <h1>{{ $form->title }}</h1>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <span class="badge {{ $form->status === 'published' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($form->status) }}</span>
            @if ($form->status === 'draft')
                <form method="POST" action="{{ route($routePrefix.'.evaluation-forms.publish', [$training, $form]) }}"
                      onsubmit="return confirm('{{ __('Publish this form? You will not be able to add more questions afterward.') }}');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Publish</button>
                </form>
            @endif
        </div>
    </div>

    @if ($form->status === 'draft')
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><div class="card-title">Add Question</div></div>
            <div class="card-body">
                <form method="POST" action="{{ route($routePrefix.'.evaluation-forms.questions.store', [$training, $form]) }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="question_text">{{ __('Question') }}</label>
                        <textarea id="question_text" name="question_text" class="form-control" rows="2">{{ old('question_text') }}</textarea>
                        <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="question_type">{{ __('Answer Type') }}</label>
                        <select id="question_type" name="question_type" class="form-control">
                            <option value="rating" @selected(old('question_type') === 'rating')>{{ __('Rating (1-5)') }}</option>
                            <option value="yes_no" @selected(old('question_type') === 'yes_no')>{{ __('Yes / No') }}</option>
                            <option value="text" @selected(old('question_type', 'text') === 'text')>{{ __('Text') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('question_type')" class="mt-2" />
                    </div>

                    <div style="display:flex;justify-content:flex-end">
                        <button type="submit" class="btn btn-primary">&#43; {{ __('Add Question') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><div class="card-title">Questions &amp; Responses</div></div>
        <div class="card-body">
            @forelse ($form->questions as $question)
                <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-100)">
                    <div style="font-weight:600;color:var(--text-primary)">{{ $question->question_text }}</div>
                    <div style="font-size:12px;color:var(--gray-400);margin-bottom:8px">Type: {{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</div>

                    @if ($question->responses->isEmpty())
                        <div style="font-size:13px;color:var(--gray-400)">No responses yet.</div>
                    @else
                        <ul style="font-size:13px;color:var(--gray-700);list-style:disc;padding-left:20px">
                            @foreach ($question->responses as $response)
                                <li>{{ $response->user->name }}: {{ $response->response_text }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <div style="font-size:13px;color:var(--gray-400)">No questions added yet.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
