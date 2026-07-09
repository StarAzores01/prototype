@if (session('status'))
    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
