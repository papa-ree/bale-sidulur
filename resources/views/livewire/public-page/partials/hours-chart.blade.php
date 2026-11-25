@php
    $values = $hours['values'] ?? [];
    $labels = $hours['labels'] ?? [];
    $sum = array_sum($values);
    $max = max(1, $values ? max($values) : 0);
    $peak = $values ? max($values) : 0;
    $peakIndex = $values ? array_search($peak, $values, true) : false;
    $summary = 'Pengajuan per dua jam: total ' . number_format($sum, 0, ',', '.');
    if ($peakIndex !== false) {
        $summary .= ', terbanyak ' . number_format($peak, 0, ',', '.') . ' pada ' . ($labels[$peakIndex] ?? '');
    }
    $summary .= '.';
@endphp

@if ($sum === 0)
    <p class="sd-empty">Belum ada pengajuan pada periode ini.</p>
@else
    <div class="sd-hours" role="img" aria-label="{{ $summary }}">
        @foreach ($values as $index => $value)
            @php $height = $value > 0 ? max(3, round($value / $max * 100)) : 0; @endphp
            <div class="sd-hours__col">
                <div class="sd-hours__track">
                    <span class="sd-hours__bar" style="height: {{ $height }}%"
                        title="{{ $labels[$index] ?? '' }}: {{ number_format($value, 0, ',', '.') }} pengajuan"></span>
                </div>
                <span class="sd-hours__label">{{ $labels[$index] ?? '' }}</span>
            </div>
        @endforeach
    </div>

    <p class="sd-chart__caption">
        Puncak {{ number_format($peak, 0, ',', '.') }} pengajuan pada {{ $labels[$peakIndex] ?? '' }}.
    </p>
@endif
