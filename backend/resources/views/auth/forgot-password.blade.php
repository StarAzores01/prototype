<x-guest-layout>
    <p style="font-size:12.5px;color:var(--gray-600);margin-bottom:16px;line-height:1.6">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </p>

    @if (session('status'))
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
            {{ __('Email Password Reset Link') }}
        </button>
    </form>
</x-guest-layout>
