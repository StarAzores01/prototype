<x-guest-layout>
    @if (session('status'))
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

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

        <div class="form-group" style="display:flex;align-items:center;justify-content:space-between">
            <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--gray-600);font-weight:500;cursor:pointer">
                <input type="checkbox" name="remember">
                {{ __('Remember me') }}
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="font-size:12.5px;color:var(--blue-primary);font-weight:600">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            {{ __('Log in') }}
        </button>
    </form>

    <div class="auth-divider"><span style="background:var(--surface);padding:0 10px;position:relative;z-index:1">{{ __('or') }}</span></div>

    <p style="text-align:center;font-size:12.5px;color:var(--gray-600)">
        {{ __("Don't have an account?") }} <a href="{{ route('register') }}" style="color:var(--blue-primary);font-weight:600">{{ __('Sign up') }}</a>
    </p>
</x-guest-layout>
