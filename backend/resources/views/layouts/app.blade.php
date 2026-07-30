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

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- PAThrive prototype design -->
        <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/prototype.css') }}" rel="stylesheet">
    </head>
    <body class="font-sans antialiased">
        @include('partials.navbar')
        @include('partials.sidebar')

        <main class="main-wrap">
            <div class="page-content">
                @isset($header)
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endisset

                @include('partials.alerts')

                {{ $slot }}
            </div>
        </main>

        <div class="toast-container" id="toastContainer"></div>

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
            function toggleDropdown() {
                document.getElementById('profileDropdown')?.classList.toggle('open');
            }
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.profile-btn')) {
                    document.getElementById('profileDropdown')?.classList.remove('open');
                }
            });
            function openModal(id) {
                document.getElementById('modal-' + id)?.classList.add('open');
            }
            function closeModal(id) {
                document.getElementById('modal-' + id)?.classList.remove('open');
            }
            document.querySelectorAll('.modal-overlay').forEach((o) => {
                o.addEventListener('click', (e) => { if (e.target === o) o.classList.remove('open'); });
            });
        </script>
    </body>
</html>
