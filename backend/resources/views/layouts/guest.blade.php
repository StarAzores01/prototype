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

        <title>{{ config('app.name', 'PAThrive') }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/prototype.css') }}" rel="stylesheet">

        @vite(['resources/css/app.css'])
    </head>
    <body class="auth-body">
        <div class="auth-card">
            <div class="auth-card-header">
                <a href="{{ url('/') }}" style="display:inline-flex;justify-content:center;text-decoration:none">
                    <div class="brand-logo">
                        <img src="{{ asset('imgs/logofinalpt.png') }}" alt="PAThrive Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
                    </div>
                </a>
                <h1>PAThrive</h1>
                <p>Extension Training Management &amp; Impact Assessment Tracking System</p>
                <button type="button" class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle dark mode" aria-label="Toggle dark mode" style="position:absolute;top:16px;right:16px">
                    <i class="fa-solid fa-moon"></i>
                    <i class="fa-solid fa-sun"></i>
                </button>
            </div>
            <div class="auth-card-body" style="position:relative">
                {{ $slot }}
            </div>
        </div>

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
