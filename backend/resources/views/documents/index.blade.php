<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Documents') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; {{ __('Back to Trainings') }}
                </a>

                <h3 class="font-semibold text-gray-800 mt-4 mb-4">{{ __('Upload Document') }}</h3>

                <form method="POST" action="{{ route($routePrefix.'.documents.store', $training) }}" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <x-input-label for="title" value="{{ __('Title') }}" />
                        <x-text-input id="title" name="title" type="text" class="block mt-1 w-full"
                                      value="{{ old('title') }}" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="visibility" value="{{ __('Visibility') }}" />
                        <select id="visibility" name="visibility" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="public" @selected(old('visibility', 'public') === 'public')>{{ __('Public') }}</option>
                            <option value="private" @selected(old('visibility') === 'private')>{{ __('Private') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="file" value="{{ __('File') }}" />
                        <input id="file" name="file" type="file" class="block mt-1 w-full text-sm" required>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ __('Allowed: pdf, doc, docx, xls, xlsx, png, jpg, jpeg. Maximum 5MB.') }}
                        </p>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div class="flex justify-end mt-4">
                        <x-primary-button>{{ __('Upload') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="border-b">
                        <tr>
                            <th class="px-4 py-2">{{ __('Title') }}</th>
                            <th class="px-4 py-2">{{ __('Type') }}</th>
                            <th class="px-4 py-2">{{ __('Visibility') }}</th>
                            <th class="px-4 py-2">{{ __('Uploaded By') }}</th>
                            <th class="px-4 py-2">{{ __('Uploaded') }}</th>
                            <th class="px-4 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($training->documents as $document)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $document->title }}</td>
                                <td class="px-4 py-2">{{ strtoupper($document->file_type) }}</td>
                                <td class="px-4 py-2">{{ ucfirst($document->visibility) }}</td>
                                <td class="px-4 py-2">{{ $document->uploadedBy?->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $document->created_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 space-x-2">
                                    <a href="{{ route($routePrefix.'.documents.download', [$training, $document]) }}" class="text-indigo-600 hover:underline">
                                        {{ __('Download') }}
                                    </a>
                                    <form method="POST" action="{{ route($routePrefix.'.documents.destroy', [$training, $document]) }}"
                                          class="inline" onsubmit="return confirm('{{ __('Delete this document?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2" colspan="6">{{ __('No documents uploaded yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
