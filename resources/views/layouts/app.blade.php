<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>
            @yield('title', 'Amber') - {{ config('app.name', 'Amber') }}
        </title>

        {{-- Sets data-bs-theme before the page paints, from the saved choice or else the OS preference --}}
        <script>
            (function () {
                var amberTheme = null;

                try {
                    amberTheme = localStorage.getItem('amber-theme');
                }
                catch (e) {
                    // Storage can be unavailable, so the OS preference applies
                }

                if (amberTheme !== 'light' && amberTheme !== 'dark') {
                    amberTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }

                document.documentElement.setAttribute('data-bs-theme', amberTheme);
            })();
        </script>

        {{-- No build step: assets load from a CDN and the published vendor/larajax files. --}}
        <script src="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="{{ asset('vendor/larajax/ui/ui.css') }}" rel="stylesheet">
        <link href="{{ asset('css/amber.css') }}" rel="stylesheet">
        <script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="{{ asset('vendor/larajax/framework-bundle.js') }}"></script>
        <script type="module" src="{{ asset('vendor/larajax/ui/ui.js') }}"></script>
        <script src="{{ asset('js/amber.js') }}" defer></script>

        {{-- Assets added by custom widgets; the page content renders before this layout, so its widgets are included --}}
        {{ Ui::renderAssets() }}
    </head>
    <body class="amber-layout">
        <div class="app-shell">
            <button
                class="sidebar-scrim"
                type="button"
                aria-label="Close navigation"
                data-amber-menu-close></button>

            @include('partials.site.sidebar')

            @include('partials.site.topbar')

            <main class="app-main">
                @yield('content')
            </main>
        </div>
    </body>
</html>
