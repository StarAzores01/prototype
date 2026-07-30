<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <script>
            (function () {
                if (localStorage.getItem('pathrive-theme') === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            })();
        </script>

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'PAThrive') }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/prototype.css') }}" rel="stylesheet">

        @vite(['resources/css/app.css'])
    </head>
    <body class="landing-body">

        <nav class="landing-nav">
            <a href="{{ url('/') }}" class="landing-nav-brand" style="text-decoration:none">
                <div class="brand-logo">
                    <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
                </div>
                <div class="brand-text">
                    <div class="brand-name">PAThrive</div>
                    <div class="brand-sub">CIT &middot; SLSU</div>
                </div>
            </a>

            <button type="button" class="landing-nav-toggle" onclick="document.getElementById('landingNavLinks').classList.toggle('open')" aria-label="Toggle navigation menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="landing-nav-links" id="landingNavLinks">
                <a href="{{ route('public.home') }}" class="{{ request()->routeIs('public.home') ? 'active' : '' }}">Home</a>
                <a href="{{ route('public.trainings') }}" class="{{ request()->routeIs('public.trainings') ? 'active' : '' }}">Trainings</a>
                <a href="{{ route('public.about') }}" class="{{ request()->routeIs('public.about') ? 'active' : '' }}">About</a>
                <a href="{{ route('public.contact') }}" class="{{ request()->routeIs('public.contact') ? 'active' : '' }}">Contact</a>
            </div>

            <div class="landing-nav-right">
                <button type="button" class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle dark mode" aria-label="Toggle dark mode">
                    <i class="fa-solid fa-moon"></i>
                    <i class="fa-solid fa-sun"></i>
                </button>
                @auth
                    <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Log In</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
                @endauth
            </div>
        </nav>

        <main>
            {{ $slot }}
        </main>

        <footer class="landing-footer">
            {{-- terms.php / privacy.php aren't ported to routes yet - replace href="#" with route('public.terms') / route('public.privacy') once they exist. --}}
            <a href="#" style="color:rgba(255,255,255,.5);margin:0 8px">Terms of Use</a> &middot;
            <a href="#" style="color:rgba(255,255,255,.5);margin:0 8px">Privacy Policy</a>
            &middot; <strong>PAThrive</strong> &copy; {{ date('Y') }} CIT-SLSU
        </footer>

        <script>
            function toggleTheme() {
                var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('pathrive-theme', 'light');
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('pathrive-theme', 'dark');
                }
            }
        </script>
    </body>
</html>
