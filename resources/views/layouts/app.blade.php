<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoMeta['site_title'] }}</title>
    <meta name="description" content="{{ $seoMeta['description'] }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoMeta['share_title'] }}">
    <meta property="og:description" content="{{ $seoMeta['share_description'] }}">
    <meta property="og:image" content="{{ $seoMeta['image_url'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoMeta['share_title'] }}">
    <meta name="twitter:description" content="{{ $seoMeta['share_description'] }}">
    <meta name="twitter:image" content="{{ $seoMeta['image_url'] }}">
    <link rel="icon" href="{{ asset('favicon/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicon/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div id="panel-notifications" class="pointer-events-none fixed inset-x-4 top-4 z-[100] mx-auto flex max-w-md flex-col gap-3" aria-live="polite" aria-atomic="true">
        @if (session('status'))
            <div data-notification data-notification-type="success" class="pointer-events-auto rounded-2xl border border-emerald-400/35 bg-[#073522]/95 px-4 py-3 text-sm font-bold text-emerald-50 shadow-2xl shadow-black/35 backdrop-blur">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div data-notification data-notification-type="error" class="pointer-events-auto rounded-2xl border border-rose-400/40 bg-[#4a1017]/95 px-4 py-3 text-sm font-bold text-rose-50 shadow-2xl shadow-black/35 backdrop-blur">
                {{ $errors->first() }}
            </div>
        @endif
    </div>

    {{ $slot ?? '' }}@yield('content')

    <script>
        document.querySelectorAll('[data-notification]').forEach((notification) => {
            window.setTimeout(() => {
                notification.classList.add('opacity-0', '-translate-y-2');
                window.setTimeout(() => notification.remove(), 300);
            }, 5000);
        });
    </script>

    @livewireScripts
</body>
</html>
