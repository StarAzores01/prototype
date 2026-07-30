<x-guest-layout>
    <p style="font-size:12.5px;color:var(--gray-600);margin-bottom:16px;line-height:1.6">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="form-group">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <div class="password-wrap">
                <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
                <span class="password-toggle" onclick="const i=document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password';">
                    <i class="fa-solid fa-eye"></i>
                </span>
            </div>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            {{ __('Confirm') }}
        </button>
    </form>
</x-guest-layout>
