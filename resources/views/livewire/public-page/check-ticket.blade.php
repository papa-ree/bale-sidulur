<div class="sd-page">
    <div class="sd-container sd-page__inner">
        <div class="sd-page-head">
            <h1>Cek status tiket</h1>
            <p class="sd-lead">
                Masukkan nomor tiket dan NIK pemilik tiket. Demi keamanan, data hanya ditampilkan bila keduanya cocok.
            </p>
        </div>

        <div class="sd-stack">
            <form wire:submit="check" class="sd-panel">
                <div class="sd-field">
                    <label class="sd-label" for="ticket">Nomor tiket</label>
                    <input id="ticket" type="text" wire:model="ticket" class="sd-input sd-mono"
                        placeholder="SDLR-20260101-ABCD" autocomplete="off" spellcheck="false" required>
                    @error('ticket') <p class="sd-error">{{ $message }}</p> @enderror
                </div>

                <div class="sd-field">
                    <label class="sd-label" for="nik">NIK pemilik tiket</label>
                    <input id="nik" type="text" inputmode="numeric" maxlength="16" wire:model="nik"
                        class="sd-input sd-mono" placeholder="16 digit NIK" autocomplete="off" required>
                    @error('nik') <p class="sd-error">{{ $message }}</p> @enderror
                </div>

                <p class="sd-help">
                    Tiket dapat dicek selama masa berlakunya. Jika lupa nomor tiket, hubungi petugas pelayanan.
                </p>

                <div class="sd-form-actions">
                    <button type="submit" class="sd-btn sd-btn--primary sd-btn--block" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="check">Lihat status</span>
                        <span wire:loading wire:target="check">Memeriksa…</span>
                    </button>
                </div>
            </form>

            @if ($verified && $result)
                <div class="sd-panel">
                    <div class="sd-result-head">
                        <div>
                            <p class="sd-result-number">{{ $result['ticket'] }}</p>
                            <p class="sd-result-service">{{ $result['service'] }}</p>
                        </div>
                        <span class="sd-status">{{ $result['status'] }}</span>
                    </div>

                    <dl class="sd-meta">
                        <div>
                            <dt>Bidang</dt>
                            <dd>{{ $result['bidang'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Diajukan</dt>
                            <dd>{{ $result['created_at'] ?? '—' }}</dd>
                        </div>
                        @if ($result['scheduled_at'])
                            <div>
                                <dt>Jadwal verifikasi lapangan</dt>
                                <dd>{{ $result['scheduled_at'] }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if (! empty($result['timeline']))
                        <div class="sd-docs">
                            <p class="sd-eyebrow">Riwayat</p>
                            <ul class="sd-timeline">
                                @foreach ($result['timeline'] as $line)
                                    <li>
                                        <span class="sd-timeline__dot" aria-hidden="true"></span>
                                        <div>
                                            <p class="sd-timeline__status">{{ $line['stage'] }} — {{ $line['status'] }}</p>
                                            <p class="sd-timeline__time">{{ $line['time'] }}</p>
                                            @if ($line['note'])
                                                <p class="sd-timeline__note">{{ $line['note'] }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($documents))
                        <div class="sd-docs">
                            <p class="sd-eyebrow">Dokumen</p>
                            @foreach ($documents as $document)
                                <a class="sd-doc" href="{{ $document['url'] }}" target="_blank"
                                    rel="noopener noreferrer">
                                    <span><x-lucide-file-text class="sd-icon" /> {{ $document['name'] }}</span>
                                    <span class="sd-doc__name">Unduh</span>
                                </a>
                            @endforeach
                            <p class="sd-help">Tautan dokumen berlaku terbatas. Jangan bagikan tautan ini kepada orang
                                lain.</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
