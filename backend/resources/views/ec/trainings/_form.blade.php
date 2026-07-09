@php
    $training = $training ?? null;
@endphp

<div class="form-group">
    <label class="form-label" for="title">{{ __('Training Title') }} *</label>
    <input id="title" name="title" type="text" class="form-control"
           value="{{ old('title', $training->title ?? '') }}" placeholder="e.g. Basic Pastry Making" required autofocus>
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="form-group">
    <label class="form-label" for="description">{{ __('Description') }}</label>
    <textarea id="description" name="description" class="form-control" rows="3"
              placeholder="Briefly describe the training...">{{ old('description', $training->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="form-group">
    <label class="form-label" for="location">{{ __('Location') }}</label>
    <input id="location" name="location" type="text" class="form-control"
           value="{{ old('location', $training->location ?? '') }}">
    <x-input-error :messages="$errors->get('location')" class="mt-2" />
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
        <input id="start_date" name="start_date" type="date" class="form-control"
               value="{{ old('start_date', optional($training->start_date ?? null)->format('Y-m-d')) }}">
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>
    <div class="form-group">
        <label class="form-label" for="end_date">{{ __('End Date') }}</label>
        <input id="end_date" name="end_date" type="date" class="form-control"
               value="{{ old('end_date', optional($training->end_date ?? null)->format('Y-m-d')) }}">
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label class="form-label" for="status">{{ __('Status') }}</label>
        <select id="status" name="status" class="form-control">
            @foreach (['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'] as $option)
                <option value="{{ $option }}" @selected(old('status', $training->status ?? 'draft') === $option)>
                    {{ ucfirst($option) }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
    <div class="form-group">
        <label class="form-label" for="project_leader_id">{{ __('Project Leader') }}</label>
        <select id="project_leader_id" name="project_leader_id" class="form-control">
            <option value="">{{ __('— Unassigned —') }}</option>
            @foreach ($projectLeaders as $leader)
                <option value="{{ $leader->id }}" @selected((string) old('project_leader_id', $training->project_leader_id ?? '') === (string) $leader->id)>
                    {{ $leader->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('project_leader_id')" class="mt-2" />
    </div>
</div>
