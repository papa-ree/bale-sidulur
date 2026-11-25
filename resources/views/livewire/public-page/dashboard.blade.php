<div class="sd-page sd-dashboard">
    <div class="sd-container">
        @if (! $unlocked)
            <section class="sd-lock" aria-labelledby="lock-title">
                <div class="sd-lock__copy">
                    <p class="sd-eyebrow">Dashboard eksekutif</p>
                    <h1 id="lock-title">Ringkasan layanan untuk pimpinan.</h1>
                    <p class="sd-lead">
                        Volume pengajuan, penyelesaian, dan beban bidang dalam satu halaman. Data agregat,
                        tanpa NIK atau alamat warga.
                    </p>
                    <ul class="sd-lock__facts">
                        <li>Password diminta setiap kali halaman dibuka.</li>
                        <li>Angka dihitung langsung dari data pelayanan.</li>
                    </ul>
                </div>

                <form wire:submit="unlock" class="sd-panel sd-panel--narrow sd-lock__form">
                    <h2 class="sd-panel__title">Masuk dashboard</h2>
                    <div class="sd-field">
                        <label class="sd-label" for="password">Password dashboard</label>
                        <input id="password" type="password" wire:model="password" class="sd-input"
                            autocomplete="current-password" placeholder="Masukkan password" required>
                        @error('password') <p class="sd-error">{{ $message }}</p> @enderror
                        @if ($error) <p class="sd-error">{{ $error }}</p> @endif
                    </div>

                    <div class="sd-form-actions">
                        <button type="submit" class="sd-btn sd-btn--primary sd-btn--block" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="unlock">Buka dashboard</span>
                            <span wire:loading wire:target="unlock">Memeriksa…</span>
                        </button>
                    </div>
                </form>
            </section>
        @else
            @php
                $fmt = fn ($value) => number_format((int) $value, 0, ',', '.');
                $delta = function (int $current, int $previous): array {
                    if ($previous === 0) {
                        return $current > 0
                            ? ['label' => 'baru', 'tone' => 'up']
                            : ['label' => '—', 'tone' => 'flat'];
                    }

                    // Basis terlalu kecil untuk persentase yang berarti; tampilkan selisih apa adanya.
                    if ($previous < 5) {
                        $diff = $current - $previous;

                        return [
                            'label' => ($diff > 0 ? '+' : '') . $diff,
                            'tone' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat'),
                        ];
                    }

                    $value = (int) round((($current - $previous) / $previous) * 100);

                    return [
                        'label' => ($value > 0 ? '+' : '') . $value . '%',
                        'tone' => $value > 0 ? 'up' : ($value < 0 ? 'down' : 'flat'),
                    ];
                };
                $totalDelta = $delta((int) ($metrics['total_period'] ?? 0), (int) ($metrics['previous_total'] ?? 0));
                $doneDelta = $delta((int) ($metrics['selesai'] ?? 0), (int) ($metrics['previous_selesai'] ?? 0));
                $sparkMax = max([1, ...($metrics['sparkline']['values'] ?? [])]);
                $avg = $metrics['avg'] ?? null;
                $belumSelesai = (int) ($metrics['belum_selesai'] ?? 0);
                $periodTotal = (int) ($metrics['total_period'] ?? 0);
                $belumPercent = $periodTotal > 0 ? round($belumSelesai / $periodTotal * 100) : 0;
            @endphp

            <header class="sd-dash-head">
                <div class="sd-dash-head__copy">
                    <p class="sd-eyebrow">Dashboard eksekutif</p>
                    <h1>Layanan sosial dalam angka</h1>
                    <p class="sd-dash-summary">{{ $metrics['period_label'] }}: {{ $fmt($periodTotal) }} pengajuan masuk, {{ $fmt($metrics['selesai'] ?? 0) }} selesai{{ $avg !== null ? ', rata-rata penyelesaian '.$avg['value'].' '.$avg['unit'] : '' }}.</p>
                </div>
                <div class="sd-dash-head__actions">
                    <button type="button" class="sd-btn sd-btn--secondary" onclick="window.print()">
                        <x-lucide-printer class="sd-icon" />
                        Cetak
                    </button>
                    <button type="button" class="sd-btn sd-btn--secondary" wire:click="lock">
                        <x-lucide-lock class="sd-icon" />
                        Kunci
                    </button>
                </div>
            </header>

            @if ($error)
                <p class="sd-alert" role="alert">{{ $error }}</p>
            @endif

            {{-- Total keseluruhan: angka all-time, bukan angka periode. --}}
            <section class="sd-hero-stats" aria-label="Rekap pengajuan">
                <article class="sd-total">
                    <p class="sd-total__label">Total pengajuan tercatat</p>
                    <p class="sd-total__value">{{ $fmt($metrics['total_all'] ?? 0) }}</p>
                    <p class="sd-total__meta">
                        Sejak {{ $metrics['first_year'] ?? now()->year }} · {{ $fmt($metrics['citizens'] ?? 0) }} warga terdaftar
                    </p>

                    <div class="sd-spark" role="img"
                        aria-label="Pengajuan 12 bulan terakhir: {{ implode(', ', $metrics['sparkline']['values'] ?? []) }}">
                        @foreach (($metrics['sparkline']['values'] ?? []) as $index => $value)
                            @php $height = $value > 0 ? max(6, round($value / $sparkMax * 100)) : 2; @endphp
                            <span class="sd-spark__bar" style="height: {{ $height }}%"
                                title="{{ ($metrics['sparkline']['labels'][$index] ?? '') }}: {{ $fmt($value) }} pengajuan"></span>
                        @endforeach
                    </div>
                    <p class="sd-spark__caption">12 bulan terakhir</p>
                </article>

                <div class="sd-kpi-grid">
                    <article class="sd-kpi">
                        <p class="sd-kpi__label">Pengajuan periode</p>
                        <p class="sd-kpi__value">{{ $fmt($periodTotal) }}</p>
                        <p class="sd-kpi__note">
                            <span class="sd-delta sd-delta--{{ $totalDelta['tone'] }}">{{ $totalDelta['label'] }}</span>
                            · dibanding periode sebelumnya
                        </p>
                    </article>

                    <article class="sd-kpi">
                        <p class="sd-kpi__label">Selesai</p>
                        <p class="sd-kpi__value">{{ $fmt($metrics['selesai'] ?? 0) }}</p>
                        <p class="sd-kpi__note">
                            <span class="sd-delta sd-delta--{{ $doneDelta['tone'] }}">{{ $doneDelta['label'] }}</span>
                            · menurut tanggal penyelesaian
                        </p>
                    </article>

                    <article class="sd-kpi">
                        <p class="sd-kpi__label">Rata-rata penyelesaian</p>
                        <p class="sd-kpi__value">
                            {{ $avg['value'] ?? '—' }}
                            @if ($avg !== null)<span class="sd-kpi__unit">{{ $avg['unit'] }}</span>@endif
                        </p>
                        <p class="sd-kpi__note">Dari pengajuan sampai selesai</p>
                    </article>

                    <article class="sd-kpi">
                        <p class="sd-kpi__label">Belum selesai</p>
                        <p class="sd-kpi__value">{{ $fmt($belumSelesai) }}</p>
                        <p class="sd-kpi__note">{{ $belumPercent }}% dari pengajuan periode ini</p>
                    </article>
                </div>
            </section>

            <section class="sd-filter" aria-labelledby="filter-title">
                <div class="sd-filter__top">
                    <h2 id="filter-title" class="sd-filter__title">Periode</h2>
                    <p class="sd-filter__period" aria-live="polite">{{ $metrics['period_label'] }}</p>
                </div>

                <div class="sd-filter__row">
                    <div class="sd-seg" role="group" aria-label="Pilihan periode cepat">
                        @foreach ([
                            'this_month' => 'Bulan ini',
                            'last_month' => 'Bulan lalu',
                            'quarter' => '3 bulan',
                            'this_year' => 'Tahun ini',
                        ] as $key => $label)
                            <button type="button" wire:click="applyPreset('{{ $key }}')"
                                class="sd-seg__btn {{ $preset === $key ? 'is-active' : '' }}"
                                aria-pressed="{{ $preset === $key ? 'true' : 'false' }}"
                                wire:loading.attr="disabled" wire:target="applyPreset">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="sd-filter__row sd-filter__row--split">
                    <form wire:submit="applyMonth" class="sd-filter__group">
                        <div class="sd-filter__field">
                            <label class="sd-label" for="filter-month">Bulan</label>
                            <select id="filter-month" wire:model.number="month" class="sd-select">
                                @foreach (['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $index => $label)
                                    <option value="{{ $index + 1 }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sd-filter__field">
                            <label class="sd-label" for="filter-year">Tahun</label>
                            <select id="filter-year" wire:model.number="year" class="sd-select">
                                @foreach (($metrics['years'] ?? [now()->year]) as $yearOption)
                                    <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="sd-btn sd-btn--primary sd-btn--sm" wire:loading.attr="disabled"
                            wire:target="applyMonth">
                            Terapkan
                        </button>
                    </form>

                    <details class="sd-filter__custom"
                        @if ($preset === 'custom' || $errors->hasAny(['from', 'to'])) open @endif>
                        <summary>Rentang tanggal</summary>
                        <form wire:submit="applyCustom" class="sd-filter__group">
                            <div class="sd-filter__field">
                                <label class="sd-label" for="filter-from">Dari</label>
                                <input id="filter-from" type="date" wire:model="from" class="sd-input"
                                    max="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="sd-filter__field">
                                <label class="sd-label" for="filter-to">Sampai</label>
                                <input id="filter-to" type="date" wire:model="to" class="sd-input"
                                    max="{{ now()->toDateString() }}" required>
                            </div>
                            <button type="submit" class="sd-btn sd-btn--primary sd-btn--sm"
                                wire:loading.attr="disabled" wire:target="applyCustom">
                                Terapkan
                            </button>
                        </form>
                    </details>
                </div>

                @error('month') <p class="sd-error">{{ $message }}</p> @enderror
                @error('year') <p class="sd-error">{{ $message }}</p> @enderror
                @error('from') <p class="sd-error">{{ $message }}</p> @enderror
                @error('to') <p class="sd-error">{{ $message }}</p> @enderror

                <div class="sd-filter__loading" wire:loading wire:target="applyPreset,applyMonth,applyCustom">
                    <span class="sd-filter__loading-bar" aria-hidden="true"></span>
                    <span>Memperbarui data…</span>
                </div>
            </section>

            {{-- Key mengikuti periode supaya animasi chart diputar ulang setelah filter berubah. --}}
            <div class="sd-dash-body" wire:key="dash-{{ $preset }}-{{ $from }}-{{ $to }}"
                wire:loading.class="is-busy" wire:target="applyPreset,applyMonth,applyCustom">
                <div class="sd-chart-grid">
                    <article class="sd-card sd-card--wide">
                        <header class="sd-card__head">
                            <div>
                                <h2>Tren pengajuan</h2>
                                <p>Jumlah pengajuan masuk per
                                    {{ ($metrics['trend']['unit'] ?? 'hari') === 'bulan' ? 'bulan' : 'hari' }}.</p>
                            </div>
                            <span class="sd-chip">{{ $metrics['period_label'] }}</span>
                        </header>
                        @include('bale-sidulur::livewire.public-page.partials.trend-chart', ['trend' => $metrics['trend'] ?? []])
                    </article>

                    <article class="sd-card">
                        <header class="sd-card__head">
                            <div>
                                <h2>Status pengajuan</h2>
                                <p>Posisi terkini pengajuan yang masuk pada periode ini.</p>
                            </div>
                        </header>
                        @include('bale-sidulur::livewire.public-page.partials.status-donut', ['status' => $metrics['status'] ?? []])
                    </article>
                </div>

                <div class="sd-split-grid">
                    <article class="sd-card">
                        <header class="sd-card__head">
                            <div>
                                <h2>Layanan terbanyak</h2>
                                <p>Lima layanan teratas pada periode ini.</p>
                            </div>
                        </header>

                        @if (empty($metrics['top_services']))
                            <p class="sd-empty">Belum ada pengajuan pada periode ini.</p>
                        @else
                            <ul class="sd-bars">
                                @foreach ($metrics['top_services'] as $row)
                                    <li class="sd-bar">
                                        <div class="sd-bar__row">
                                            <span class="sd-bar__label">{{ $row['nama'] }}</span>
                                            <span class="sd-bar__value">{{ $fmt($row['value']) }}
                                                <span class="sd-bar__pct">{{ $row['percent'] }}%</span></span>
                                        </div>
                                        <div class="sd-bar__track" aria-hidden="true">
                                            <span class="sd-bar__fill" style="width: {{ max(2, $row['percent']) }}%"></span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>

                    <article class="sd-card">
                        <header class="sd-card__head">
                            <div>
                                <h2>Beban per bidang</h2>
                                <p>Pengajuan yang diteruskan ke bidang pada periode ini.</p>
                            </div>
                            <span class="sd-legend">
                                <span class="sd-legend__item"><i class="sd-legend__swatch sd-legend__swatch--done"></i>Selesai</span>
                                <span class="sd-legend__item"><i class="sd-legend__swatch sd-legend__swatch--run"></i>Berjalan</span>
                            </span>
                        </header>

                        @if (empty($metrics['bidang']))
                            <p class="sd-empty">Belum ada pengajuan yang diteruskan ke bidang.</p>
                        @else
                            <ul class="sd-bars">
                                @foreach ($metrics['bidang'] as $row)
                                    <li class="sd-bar">
                                        <div class="sd-bar__row">
                                            <span class="sd-bar__label">{{ $row['nama'] }}</span>
                                            <span class="sd-bar__value">{{ $fmt($row['total']) }}</span>
                                        </div>
                                        <div class="sd-progress"
                                            role="img"
                                            aria-label="{{ $row['nama'] }}: {{ $fmt($row['selesai']) }} selesai, {{ $fmt($row['berjalan']) }} berjalan">
                                            @if ($row['selesai'] > 0)
                                                <span class="sd-progress__done"
                                                    style="width: {{ round($row['selesai'] / max(1, $row['total']) * 100) }}%"></span>
                                            @endif
                                            @if ($row['berjalan'] > 0)
                                                <span class="sd-progress__run"
                                                    style="width: {{ round($row['berjalan'] / max(1, $row['total']) * 100) }}%"></span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if (($metrics['belum_diteruskan'] ?? 0) > 0)
                            <p class="sd-card__footnote">
                                {{ $fmt($metrics['belum_diteruskan']) }} pengajuan belum diteruskan ke bidang.
                            </p>
                        @endif
                    </article>
                </div>

                @php $sla = $metrics['sla'] ?? null; @endphp

                <div class="sd-split-grid">
                    <article class="sd-card">
                        <header class="sd-card__head">
                            <div>
                                <h2>Pengajuan per kecamatan</h2>
                                <p>Kecamatan dengan pengajuan terbanyak pada periode ini.</p>
                            </div>
                        </header>

                        @if (empty($metrics['districts']))
                            <p class="sd-empty">Belum ada data wilayah pada periode ini.</p>
                        @else
                            <ul class="sd-bars">
                                @foreach ($metrics['districts'] as $row)
                                    <li class="sd-bar">
                                        <div class="sd-bar__row">
                                            <span class="sd-bar__label">{{ $row['nama'] }}</span>
                                            <span class="sd-bar__value">{{ $fmt($row['value']) }}
                                                <span class="sd-bar__pct">{{ $row['percent'] }}%</span></span>
                                        </div>
                                        <div class="sd-bar__track" aria-hidden="true">
                                            <span class="sd-bar__fill"
                                                style="width: {{ max(2, $row['percent']) }}%"></span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>

                    <article class="sd-card">
                        <header class="sd-card__head">
                            <div>
                                <h2>Ketepatan SLA</h2>
                                <p>Pengajuan selesai dibanding target layanan, dihitung dari tanggal masuk.</p>
                            </div>
                        </header>

                        @if (($sla['total'] ?? 0) === 0)
                            <p class="sd-empty">Belum ada pengajuan selesai yang punya target SLA pada periode ini.</p>
                        @else
                            <div class="sd-table-wrap">
                                <table class="sd-table sd-table--sla">
                                    <thead>
                                        <tr>
                                            <th scope="col">Layanan</th>
                                            <th scope="col">Rata-rata</th>
                                            <th scope="col">Tepat waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sla['rows'] as $row)
                                            <tr>
                                                <td>{{ $row['nama'] }}</td>
                                                <td class="sd-mono">{{ $row['avg']['value'] }} {{ $row['avg']['unit'] }}</td>
                                                <td>{{ $row['percent'] }}%
                                                    <span class="sd-bar__pct">({{ $fmt($row['ontime']) }}/{{ $fmt($row['total']) }})</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="sd-card__footnote">
                                {{ $fmt($sla['ontime']) }} dari {{ $fmt($sla['total']) }} pengajuan selesai tepat waktu
                                ({{ $sla['percent'] }}%). Hanya pengajuan selesai yang dinilai.
                            </p>
                        @endif
                    </article>
                </div>

                <div class="sd-split-grid">
                    <article class="sd-card">
                        <header class="sd-card__head">
                            <div>
                                <h2>Jam sibuk pengajuan</h2>
                                <p>Pengajuan masuk per dua jam pada periode ini.</p>
                            </div>
                        </header>
                        @include('bale-sidulur::livewire.public-page.partials.hours-chart', ['hours' => $metrics['hours'] ?? []])
                    </article>

                    <article class="sd-card">
                        <header class="sd-card__head">
                            <div>
                                <h2>Antrean hari ini</h2>
                                <p>Kondisi saat halaman dibuka, tidak mengikuti filter periode.</p>
                            </div>
                        </header>

                        <dl class="sd-mini-stats">
                            <div>
                                <dt>Antrean bidang</dt>
                                <dd>{{ $fmt($metrics['antrean_bidang'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>Verifikasi lapangan</dt>
                                <dd>{{ $fmt($metrics['antrean_verlap'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>Layanan aktif</dt>
                                <dd>{{ $fmt($metrics['services'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>Bidang aktif</dt>
                                <dd>{{ $fmt($metrics['bidang_active'] ?? 0) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>

                @php $verlap = $metrics['verlap'] ?? null; @endphp

                <article class="sd-card">
                    <header class="sd-card__head">
                        <div>
                            <h2>Alur verifikasi lapangan</h2>
                            <p>Pengajuan yang diteruskan ke bidang pada periode ini, sampai tuntas.</p>
                        </div>
                    </header>

                    @if (($verlap['stages'][0]['value'] ?? 0) === 0)
                        <p class="sd-empty">Belum ada pengajuan yang diteruskan ke bidang pada periode ini.</p>
                    @else
                        <ul class="sd-bars">
                            @foreach ($verlap['stages'] as $stage)
                                <li class="sd-bar">
                                    <div class="sd-bar__row">
                                        <span class="sd-bar__label">{{ $stage['label'] }}</span>
                                        <span class="sd-bar__value">{{ $fmt($stage['value']) }}
                                            <span class="sd-bar__pct">{{ $stage['percent'] }}%</span></span>
                                    </div>
                                    <div class="sd-bar__track" aria-hidden="true">
                                        <span class="sd-bar__fill" style="width: {{ max(2, $stage['percent']) }}%"></span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <p class="sd-card__footnote">
                            @if ($verlap['avg_verlap'] !== null)
                                Rata-rata {{ $verlap['avg_verlap']['value'] }} {{ $verlap['avg_verlap']['unit'] }} dari diteruskan sampai verifikasi lapangan terlaksana.
                            @endif
                            @if ($verlap['avg_done'] !== null)
                                Dari verifikasi sampai selesai: {{ $verlap['avg_done']['value'] }} {{ $verlap['avg_done']['unit'] }}.
                            @endif
                        </p>
                    @endif
                </article>

                <article class="sd-card">
                    <header class="sd-card__head">
                        <div>
                            <h2>Pengajuan terbaru</h2>
                            <p>Sepuluh tiket terakhir pada periode ini. Tanpa data pribadi.</p>
                        </div>
                    </header>

                    @if (empty($metrics['recent']))
                        <p class="sd-empty">Belum ada pengajuan pada periode ini.</p>
                    @else
                        <div class="sd-table-wrap">
                            <table class="sd-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Tiket</th>
                                        <th scope="col">Layanan</th>
                                        <th scope="col">Bidang</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Masuk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($metrics['recent'] as $row)
                                        <tr>
                                            <td class="sd-mono">{{ $row['ticket'] }}</td>
                                            <td>{{ $row['service'] }}</td>
                                            <td>{{ $row['bidang'] }}</td>
                                            <td><span class="sd-badge sd-badge--{{ $row['tone'] }}">{{ $row['status'] }}</span></td>
                                            <td class="sd-mono">{{ $row['created_at'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>
            </div>
        @endif
    </div>
</div>
