@php
    $waNumber = preg_replace('/\D+/', '', (string) config('sidulur.whatsapp_center'));
    $waLink = $waNumber
        ? 'https://wa.me/' . $waNumber . '?text=' . rawurlencode('Halo, saya ingin bertanya tentang layanan Sidulur.')
        : null;

    $instansi = config('sidulur.instansi.nama');
    $alamat = config('sidulur.instansi.alamat');
    $jamLayanan = config('sidulur.jam_layanan');

    $serviceItems = $services->map(fn($service) => [
        'nama' => $service->nama,
        'bidang' => $service->bidang?->nama,
        'persyaratan' => $service->requirements(),
    ]);
@endphp

<div>
    <section class="sd-hero">
        <div class="sd-container sd-hero__inner">
            <div class="sd-hero__copy">
                <h1>Sidulur, layanan sosial Ponorogo.</h1>
                <p class="sd-lead">
                    Sidulur adalah Sistem Informasi Digital untuk Layanan Urusan Sosial yang Responsif dari
                    {{ $instansi }}. Ajukan layanan di kantor atau lewat WhatsApp, lalu pantau tiketnya dari rumah.
                </p>

                <div class="sd-actions">
                    <a class="sd-btn sd-btn--primary sd-btn--lg" href="{{ route('sidulur.ticket.check') }}">
                        <x-lucide-search class="sd-icon" />
                        Cek tiket
                    </a>
                    @if ($waLink)
                        <a class="sd-btn sd-btn--secondary sd-btn--lg" href="{{ $waLink }}" target="_blank"
                            rel="noopener noreferrer">
                            <x-lucide-message-circle class="sd-icon" />
                            Chat petugas
                        </a>
                    @else
                        <a class="sd-btn sd-btn--secondary sd-btn--lg" href="#layanan">Lihat layanan</a>
                    @endif
                </div>

                <p class="sd-facts">Gratis · Tanpa antre berulang · Status dapat dilacak</p>
                <p class="sd-hero__note">
                    <x-lucide-info class="sd-icon" />
                    Nomor tiket diberikan petugas setelah pengajuan dicatat.
                </p>
            </div>

            <aside class="sd-hero__aside">
                <div class="sd-ticket">
                    <div class="sd-ticket__top">
                        <span class="sd-ticket__label">Tiket pelayanan</span>
                        <span class="sd-ticket__badge">Contoh</span>
                    </div>
                    <p class="sd-ticket__number">SDLR-{{ now()->format('Ymd') }}-A2B7</p>

                    <dl class="sd-ticket__meta">
                        <div>
                            <dt>Layanan</dt>
                            <dd>Surat Keterangan Tidak Mampu</dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd><span class="sd-status">Sedang diproses</span></dd>
                        </div>
                    </dl>

                    <div class="sd-ticket__foot">
                        <x-lucide-shield-check class="sd-icon" />
                        Cek status dengan nomor tiket dan NIK Anda.
                    </div>
                </div>

                <p class="sd-ticket__caption">
                    Contoh tampilan tiket. Nomor dan data di atas bukan tiket sungguhan.
                </p>
            </aside>
        </div>
    </section>

    <section class="sd-section sd-section--surface" id="layanan">
        <div class="sd-container">
            <div class="sd-section__head">
                <h2>Layanan yang tersedia</h2>
                <p>Jenis layanan berikut dilayani oleh {{ $instansi }}. Persyaratan tiap layanan tercantum di bawah.</p>
            </div>

            @if ($servicesUnavailable)
                <div class="sd-channel" role="status">
                    <h3>Daftar layanan belum dapat dimuat</h3>
                    <p>Sambungan ke data layanan sedang terganggu. Anda tetap dapat bertanya langsung ke petugas.</p>
                    <div class="sd-actions">
                        @if ($waLink)
                            <a class="sd-btn sd-btn--primary" href="{{ $waLink }}" target="_blank"
                                rel="noopener noreferrer">
                                <x-lucide-message-circle class="sd-icon" />
                                Chat petugas
                            </a>
                        @endif
                        <a class="sd-btn sd-btn--secondary" href="{{ route('sidulur.ticket.check') }}">Cek tiket</a>
                    </div>
                </div>
            @elseif ($serviceItems->isEmpty())
                <div class="sd-channel" role="status">
                    <h3>Belum ada layanan aktif</h3>
                    <p>Belum ada jenis layanan yang dibuka. Hubungi petugas untuk informasi terbaru.</p>
                </div>
            @else
                <ul class="sd-services">
                    @foreach ($serviceItems as $service)
                        <li wire:key="layanan-{{ $loop->index }}">
                            <p class="sd-services__name">{{ $service['nama'] }}</p>
                            @if ($service['bidang'])
                                <p class="sd-services__bidang">{{ $service['bidang'] }}</p>
                            @endif
                            <p class="sd-services__syarat-label">Persyaratan</p>
                            <ul class="sd-services__syarat">
                                @foreach ($service['persyaratan'] as $syarat)
                                    <li>
                                        {{ $syarat['nama'] }}
                                        @unless ($syarat['wajib'])
                                            <span class="sd-services__opsional">opsional</span>
                                        @endunless
                                        @if ($syarat['keterangan'])
                                            <span class="sd-services__ket">{{ $syarat['keterangan'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section class="sd-section" id="mengajukan">
        <div class="sd-container">
            <div class="sd-section__head">
                <h2>{{ $waLink ? 'Dua cara mengajukan' : 'Cara mengajukan' }}</h2>
                <p>{{ $waLink ? 'Pilih yang paling mudah. Keduanya dicatat sebagai tiket dengan nomor yang sama.' : 'Ajukan langsung ke loket pelayanan. Pengajuan dicatat sebagai tiket dengan nomor yang dapat dilacak.' }}</p>
            </div>

            <div class="sd-channels {{ $waLink ? '' : 'sd-channels--single' }}">
                <div class="sd-channel">
                    <h3>Datang ke kantor</h3>
                    <p>Bawa KTP dan KK ke loket pelayanan. Petugas mencatat pengajuan Anda dan memberikan nomor tiket.</p>
                    <ul class="sd-channel__meta">
                        <li><x-lucide-clock class="sd-icon" /> {{ $jamLayanan }}</li>
                        <li><x-lucide-map-pin class="sd-icon" /> {{ $instansi }}, {{ $alamat }}</li>
                    </ul>
                </div>

                @if ($waLink)
                    <div class="sd-channel">
                        <h3>Lewat WhatsApp</h3>
                        <p>Chat petugas dengan menyebutkan nama lengkap, NIK, dan layanan yang dibutuhkan. Petugas
                            mengarahkan dokumen yang perlu dikirim.</p>
                        <a class="sd-btn sd-btn--secondary" href="{{ $waLink }}" target="_blank"
                            rel="noopener noreferrer">
                            <x-lucide-message-circle class="sd-icon" />
                            Mulai chat
                        </a>
                    </div>
                @endif
            </div>

            <p class="sd-note">Dokumen pendukung seperti surat pengantar RT/RW atau foto kondisi rumah diminta sesuai
                jenis layanan.</p>
        </div>
    </section>

    <section class="sd-section sd-section--alt" id="alur">
        <div class="sd-container">
            <div class="sd-section__head">
                <h2>Dari pengajuan sampai selesai</h2>
                <p>Tahapnya sama untuk pengajuan di kantor maupun lewat WhatsApp.</p>
            </div>

            <ol class="sd-flow">
                <li>
                    <span class="sd-flow__num" aria-hidden="true">1</span>
                    <h3>Ajukan dan terima nomor tiket</h3>
                    <p>Pengajuan dicatat petugas. Nomor berawalan SDLR menjadi kunci pelacakan Anda.</p>
                </li>
                <li>
                    <span class="sd-flow__num" aria-hidden="true">2</span>
                    <h3>Diproses bidang terkait</h3>
                    <p>Bidang yang menangani memeriksa berkas. Bila perlu, petugas menjadwalkan verifikasi lapangan.</p>
                </li>
                <li>
                    <span class="sd-flow__num" aria-hidden="true">3</span>
                    <h3>Pantau sampai selesai</h3>
                    <p>Buka halaman Cek Tiket dengan nomor tiket dan NIK. Status beserta riwayatnya tampil di sana.</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="sd-section" id="faq">
        <div class="sd-container sd-faq__layout">
            <div class="sd-section__head">
                <h2>Hal yang sering ditanyakan</h2>
                <p>Belum terjawab? <a href="#kontak">Hubungi petugas</a> pada jam layanan.</p>
            </div>

            <div class="sd-faq">
                <details>
                    <summary>Di mana saya mendapatkan nomor tiket?</summary>
                    <p>Nomor tiket diberikan petugas setelah pengajuan Anda dicatat, baik di kantor maupun lewat
                        WhatsApp. Nomor ini menjadi kunci untuk mengecek status layanan.</p>
                </details>
                <details>
                    <summary>Bagaimana cara mengecek tiket?</summary>
                    <p>Buka halaman Cek Tiket, masukkan nomor tiket dan NIK pemilik tiket, lalu tekan tombol lihat
                        status.</p>
                </details>
                <details>
                    <summary>Berapa lama nomor tiket bisa digunakan?</summary>
                    <p>Secara bawaan tiket dapat dicek selama 30 hari sejak pengajuan. Jika sudah kedaluwarsa,
                        hubungi petugas untuk bantuan.</p>
                </details>
                <details>
                    <summary>Apakah data pribadi saya aman?</summary>
                    <p>NIK, nomor KK, alamat, dan nomor telepon disimpan dalam bentuk terenkripsi. Data hanya dapat
                        diakses petugas yang berwenang.</p>
                </details>
                <details>
                    <summary>Nomor tiket saya hilang, apa yang harus dilakukan?</summary>
                    <p>Hubungi petugas melalui WhatsApp dengan menyebutkan nama lengkap dan NIK. Petugas akan membantu
                        mencarikan data pengajuan Anda.</p>
                </details>
                <details>
                    <summary>Apa arti status pada tiket?</summary>
                    <p>Status menunjukkan tahap pengajuan, mulai dari diterima, diproses bidang, dijadwalkan
                        verifikasi lapangan, hingga selesai.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="sd-section" id="kontak">
        <div class="sd-container">
            <div class="sd-contact">
                <div>
                    <h2>Butuh bantuan petugas?</h2>
                    <p>Hubungi kami pada jam layanan. Sertakan nama lengkap dan NIK agar petugas dapat membantu lebih
                        cepat.</p>

                    <ul class="sd-contact__meta">
                        <li><x-lucide-clock class="sd-icon" /> {{ $jamLayanan }}</li>
                        <li><x-lucide-map-pin class="sd-icon" /> {{ $instansi }}, {{ $alamat }}</li>
                    </ul>
                </div>

                <div class="sd-contact__action">
                    @if ($waLink)
                        <a class="sd-btn sd-btn--cream sd-btn--lg" href="{{ $waLink }}" target="_blank"
                            rel="noopener noreferrer">
                            <x-lucide-message-circle class="sd-icon" />
                            Chat via WhatsApp
                        </a>
                        <p class="sd-contact__hint">Balasan diberikan pada jam kerja.</p>
                    @else
                        <a class="sd-btn sd-btn--cream sd-btn--lg" href="{{ route('sidulur.ticket.check') }}">
                            <x-lucide-search class="sd-icon" />
                            Cek tiket
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
