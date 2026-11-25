<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#153F33">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ?? 'Sidulur' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="sidulur-theme">
    <a class="sd-skip" href="#main">Langsung ke konten utama</a>

    <header class="sd-header">
        <div class="sd-container sd-header__inner">
            <a class="sd-brand" href="{{ route('index') }}" aria-label="Sidulur, kembali ke halaman depan">
                <span class="sd-brand__mark" aria-hidden="true">
                    <x-lucide-ticket class="sd-icon" />
                </span>
                <span class="sd-brand__text">
                    <strong>Sidulur</strong>
                    <span>{{ config('sidulur.instansi.nama') }}</span>
                </span>
            </a>

            <a class="sd-btn sd-btn--secondary" href="{{ route('index') }}">
                <x-lucide-arrow-left class="sd-icon" />
                Beranda
            </a>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="sd-footer">
        <div class="sd-container sd-footer__inner">
            <p class="sd-footer__brand">Sidulur</p>
            <p class="sd-footer__copy">&copy; {{ date('Y') }} Bale CMS. Desain oleh Dinas Kominfo Kabupaten Ponorogo.
            </p>
        </div>
    </footer>

    @livewireScripts
</body>

</html>