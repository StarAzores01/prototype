<x-guest-layout>
    <p style="font-size:12.5px;color:var(--gray-600);margin-bottom:16px;line-height:1.6">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:8px">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-link">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
