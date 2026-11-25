<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#153F33">
    <meta name="description"
        content="Sidulur, layanan e-ticketing Dinas Sosial Kabupaten Ponorogo. Ajukan layanan di kantor atau lewat WhatsApp, lalu cek status tiket secara online.">

    <x-seo::seo-meta :model="$seoModel ?? $post ?? $page ?? null" :defaults="[
        'title' => $title ?? 'Sidulur — Pelayanan Warga',
        'image' => asset('img/og-default.png')
    ]" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-umpak::analytics />

    @livewireStyles

    {{-- Dipasang sebelum paint supaya tiket tidak berkedip; tanpa JS halaman tetap tampil penuh. --}}
    <script>
        try {
            if ( !matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
                document.documentElement.classList.add( 'sd-motion' );
                if ( !sessionStorage.getItem( 'sidulur:ticket-motion' ) ) {
                    document.documentElement.classList.add( 'sd-ticket-motion' );
                }
            }
        } catch ( e ) { }
    </script>
</head>

<body class="sidulur-theme">
    <a class="sd-skip" href="#main">Langsung ke konten utama</a>

    <header class="sd-header">
        <x-gupa::honeypot fieldName="website_url" action="{{ route('index') }}" />

        <div class="sd-container sd-header__inner">
            {{-- <a href="{{ route('index') }}" class="flex items-center gap-3">
                <img src="{{ asset('img/logo-png.png') }}" class="md:h-12 md:w-12 h-8 w-8 object-contain"
                    loading="lazy" alt="logo ponorogo"
                    referrerpolicy="{{ app()->isLocal() ? 'no-referrer' : 'strict-origin-when-cross-origin' }}">
                <div class="flex flex-col">
                    <span class="text-base md:text-lg font-bold text-gray-900 dark:text-white">Sidulur Dinas
                        Sosial</span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Kabupaten Ponorogo</span>
                </div>
            </a> --}}
            <a class="sd-brand" href="{{ route('index') }}" aria-label="Sidulur, kembali ke halaman depan">
                <img src="{{ asset('img/logo-png.png') }}" class="md:h-12 md:w-12 h-8 w-8 object-contain"
                    loading="lazy" alt="logo ponorogo"
                    referrerpolicy="{{ app()->isLocal() ? 'no-referrer' : 'strict-origin-when-cross-origin' }}">
                <span class="sd-brand__text">
                    <strong>Sidulur</strong>
                    <span>{{ config('sidulur.instansi.nama') }}</span>
                </span>
            </a>

            <nav class="sd-nav" aria-label="Navigasi utama">
                <a href="#layanan">Layanan</a>
                <a href="#mengajukan">Cara mengajukan</a>
                <a href="#alur">Alur</a>
                <a href="#faq">Pertanyaan</a>
            </nav>

            <a class="sd-btn sd-btn--primary" href="{{ route('sidulur.ticket.check') }}">
                <x-lucide-search class="sd-icon" />
                Cek tiket
            </a>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="sd-footer">
        <div class="sd-container sd-footer__inner">
            <div>
                <p class="sd-footer__brand">Sidulur</p>
                <p class="sd-footer__sub">{{ config('sidulur.instansi.nama') }}</p>
            </div>
            <nav aria-label="Navigasi footer">
                <a href="#layanan">Layanan</a>
                <a href="#mengajukan">Cara mengajukan</a>
                <a href="#faq">Pertanyaan</a>
                <a href="{{ route('sidulur.ticket.check') }}">Cek tiket</a>
            </nav>
            <p class="sd-footer__copy">&copy; {{ date('Y') }} Bale CMS. Desain oleh Dinas Kominfo Kabupaten Ponorogo.
            </p>
        </div>
    </footer>

    @livewireScripts

    <script>
        ( function ()
        {
            const html = document.documentElement;
            const reveals = document.querySelectorAll(
                '.sd-section__head, .sd-services, .sd-flow, .sd-channels, .sd-faq, .sd-contact'
            );

            // Tanpa `sd-motion` (reduce motion / JS mati) semua konten tampil biasa.
            if ( html.classList.contains( 'sd-motion' ) ) {
                if ( 'IntersectionObserver' in window ) {
                    const spy = new IntersectionObserver( ( entries, self ) =>
                    {
                        entries.forEach( ( entry ) =>
                        {
                            if ( !entry.isIntersecting ) return;
                            entry.target.classList.add( 'sd-in' );
                            self.unobserve( entry.target );
                        } );
                    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 } );

                    reveals.forEach( ( el ) => spy.observe( el ) );
                } else {
                    reveals.forEach( ( el ) => el.classList.add( 'sd-in' ) );
                }
            }

            // Animasi tiket hanya sekali per sesi; reveal tetap jalan tiap muat.
            if ( !html.classList.contains( 'sd-ticket-motion' ) ) return;

            const ticket = document.querySelector( '.sd-ticket' );
            const number = ticket?.querySelector( '.sd-ticket__number' );
            if ( !ticket || !number ) return;

            const final = number.textContent.trim();
            const head = final.slice( 0, final.lastIndexOf( '-' ) + 1 );
            const tail = final.slice( head.length );
            const glyphs = '0123456789ABCDEF';
            const duration = 360;

            // shortcut: sekali per sesi (sessionStorage); pakai localStorage bila harus sekali selamanya.
            const tally = () =>
            {
                const start = performance.now() + 380;
                const settle = ( now ) =>
                {
                    if ( now < start ) return requestAnimationFrame( settle );
                    if ( now < start + duration ) {
                        const done = Math.floor( ( ( now - start ) / duration ) * tail.length );
                        number.textContent = head + [ ...tail ]
                            .map( ( c, i ) => i < done ? c : glyphs[ Math.floor( Math.random() * glyphs.length ) ] )
                            .join( '' );
                        return requestAnimationFrame( settle );
                    }
                    number.textContent = final;
                    try { sessionStorage.setItem( 'sidulur:ticket-motion', '1' ); } catch ( e ) { }
                };
                requestAnimationFrame( settle );
            };

            const replay = () =>
            {
                html.classList.remove( 'sd-ticket-motion' );
                void ticket.offsetWidth;
                html.classList.add( 'sd-ticket-motion' );
                tally();
            };

            // Di layar kecil tiket berada di bawah lipatan: mainkan saat benar-benar terlihat.
            const box = ticket.getBoundingClientRect();
            if ( box.top + box.height / 2 > window.innerHeight ) {
                const io = new IntersectionObserver( ( entries ) =>
                {
                    if ( !entries[ 0 ].isIntersecting ) return;
                    io.disconnect();
                    replay();
                }, { threshold: 0.5 } );
                io.observe( ticket );
            } else {
                tally();
            }
        } )();
    </script>
</body>

</html>