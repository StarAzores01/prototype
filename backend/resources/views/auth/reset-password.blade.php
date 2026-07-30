<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <div class="password-wrap">
                <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">
                <span class="password-toggle" onclick="const i=document.getElementById('password'); i.type = i.type === 'password' ? 'text' : 'password';">
                    <i class="fa-solid fa-eye"></i>
                </span>
            </div>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
            <div class="password-wrap">
                <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
                <span class="password-toggle" onclick="const i=document.getElementById('password_confirmation'); i.type = i.type === 'password' ? 'text' : 'password';">
                    <i class="fa-solid fa-eye"></i>
                </span>
            </div>
            @error('password_confirmation')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            {{ __('Reset Password') }}
        </button>
    </form>
</x-guest-layout>
