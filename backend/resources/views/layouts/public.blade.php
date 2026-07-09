<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'PAThrive') }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/prototype.css') }}" rel="stylesheet">

        @vite(['resources/css/app.css'])
    </head>
    <body style="font-family:'Poppins',sans-serif;background:#F0F6FF;color:#334155;margin:0">

        <nav class="nav" style="position:sticky;top:0">
            <div class="nav-inner">
                <a href="{{ url('/') }}" class="brand" style="text-decoration:none">
                    <div class="brand-logo">
                        <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
                    </div>
                    <div>
                        <div class="brand-name">PAThrive</div>
                        <div class="brand-sub">CIT &middot; SLSU</div>
                    </div>
                </a>

                <div class="nav-links">
                    <a href="{{ route('public.home') }}" class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a>
                    <a href="{{ route('public.trainings') }}" class="nav-link {{ request()->routeIs('public.trainings') ? 'active' : '' }}">Trainings</a>
                    <a href="{{ route('public.about') }}" class="nav-link {{ request()->routeIs('public.about') ? 'active' : '' }}">About</a>
                    <a href="{{ route('public.contact') }}" class="nav-link {{ request()->routeIs('public.contact') ? 'active' : '' }}">Contact</a>
                </div>

                <div class="nav-actions">
                    @auth
                        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="btn-signup">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-login">Log In</a>
                        <a href="{{ route('register') }}" class="btn-signup">Sign Up</a>
                    @endauth
                </div>
            </div>
        </nav>

        <main>
            {{ $slot }}
        </main>

        <footer style="text-align:center;padding:24px;font-size:11.5px;color:var(--gray-400)">
            {{-- terms.php / privacy.php aren't ported to routes yet - replace href="#" with route('public.terms') / route('public.privacy') once they exist. --}}
            <a href="#" style="color:var(--gray-400);margin:0 8px">Terms of Use</a> &middot;
            <a href="#" style="color:var(--gray-400);margin:0 8px">Privacy Policy</a>
            &middot; PAThrive &copy; {{ date('Y') }} CIT-SLSU
        </footer>
    </body>
</html>
