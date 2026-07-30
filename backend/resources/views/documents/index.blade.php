<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Documents') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ Route::has($routePrefix.'.show') ? route($routePrefix.'.show', $training) : route($routePrefix.'.index') }}" style="color:var(--blue-primary)">{{ $training->title }}</a>
                &rsaquo; <span>Documents</span>
            </div>
            <h1>Documents</h1>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px">
        <div class="card-header"><div class="card-title">Upload Document</div></div>
        <div class="card-body">
            <form method="POST" action="{{ route($routePrefix.'.documents.store', $training) }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="title">{{ __('Title') }}</label>
                    <input id="title" name="title" type="text" class="form-control" value="{{ old('title') }}" required>
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="visibility">{{ __('Visibility') }}</label>
                    <select id="visibility" name="visibility" class="form-control">
                        <option value="public" @selected(old('visibility', 'public') === 'public')>{{ __('Public') }}</option>
                        <option value="private" @selected(old('visibility') === 'private')>{{ __('Private') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
                </div>

                <div class="form-group">
                    <label class="form-label" for="file">{{ __('File') }}</label>
                    <input id="file" name="file" type="file" class="form-control" required>
                    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">
                        {{ __('Allowed: pdf, doc, docx, xls, xlsx, png, jpg, jpeg. Maximum 5MB.') }}
                    </div>
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> {{ __('Upload') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">Uploaded Documents</div></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Title</th><th>Type</th><th>Visibility</th><th>Uploaded By</th><th>Uploaded</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($training->documents as $document)
                        <tr>
                            <td><strong>{{ $document->title }}</strong></td>
                            <td style="font-size:12px;color:var(--gray-600)">{{ strtoupper($document->file_type) }}</td>
                            <td><span class="badge {{ $document->visibility === 'public' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($document->visibility) }}</span></td>
                            <td style="font-size:12px;color:var(--gray-600)">{{ $document->uploadedBy?->name ?? '—' }}</td>
                            <td style="font-size:12px;color:var(--gray-400)">{{ $document->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route($routePrefix.'.documents.download', [$training, $document]) }}" class="btn btn-sm btn-outline">Download</a>
                                <form method="POST" action="{{ route($routePrefix.'.documents.destroy', [$training, $document]) }}"
                                      style="display:inline" onsubmit="return confirm('{{ __('Delete this document?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400)">No documents uploaded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
