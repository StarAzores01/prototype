@php
    $training = $training ?? null;
@endphp

<div>
    <x-input-label for="title" value="{{ __('Title') }}" />
    <x-text-input id="title" name="title" type="text" class="block mt-1 w-full"
                  value="{{ old('title', $training->title ?? '') }}" required autofocus />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="{{ __('Description') }}" />
    <textarea id="description" name="description" rows="4"
              class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $training->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="location" value="{{ __('Location') }}" />
    <x-text-input id="location" name="location" type="text" class="block mt-1 w-full"
                  value="{{ old('location', $training->location ?? '') }}" />
    <x-input-error :messages="$errors->get('location')" class="mt-2" />
</div>

<div class="mt-4 grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="start_date" value="{{ __('Start Date') }}" />
        <x-text-input id="start_date" name="start_date" type="date" class="block mt-1 w-full"
                      value="{{ old('start_date', optional($training->start_date ?? null)->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="end_date" value="{{ __('End Date') }}" />
        <x-text-input id="end_date" name="end_date" type="date" class="block mt-1 w-full"
                      value="{{ old('end_date', optional($training->end_date ?? null)->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="status" value="{{ __('Status') }}" />
    <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        @foreach (['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'] as $option)
            <option value="{{ $option }}" @selected(old('status', $training->status ?? 'draft') === $option)>
                {{ ucfirst($option) }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('status')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="project_leader_id" value="{{ __('Project Leader') }}" />
    <select id="project_leader_id" name="project_leader_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        <option value="">{{ __('— Unassigned —') }}</option>
        @foreach ($projectLeaders as $leader)
            <option value="{{ $leader->id }}" @selected((string) old('project_leader_id', $training->project_leader_id ?? '') === (string) $leader->id)>
                {{ $leader->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('project_leader_id')" class="mt-2" />
</div>
