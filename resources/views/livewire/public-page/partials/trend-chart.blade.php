@php
    $values = $trend['values'] ?? [];
    $labels = $trend['labels'] ?? [];
    $count = count($values);
    $sum = array_sum($values);
    $peak = $count > 0 ? max($values) : 0;
    $peakIndex = $count > 0 ? array_search($peak, $values, true) : false;

    $width = 720;
    $height = 240;
    $padLeft = 42;
    $padRight = 14;
    $padTop = 16;
    $padBottom = 34;
    $plotWidth = $width - $padLeft - $padRight;
    $plotHeight = $height - $padTop - $padBottom;
    $max = max(1, $peak);
    $step = $count > 1 ? $plotWidth / ($count - 1) : 0;

    $points = [];
    foreach ($values as $index => $value) {
        $x = $count > 1 ? $padLeft + $index * $step : $padLeft + $plotWidth / 2;
        $y = $padTop + $plotHeight - ($value / $max) * $plotHeight;
        $points[] = ['x' => $x, 'y' => $y, 'value' => $value, 'label' => $labels[$index] ?? ''];
    }

    $line = implode(' ', array_map(fn ($point) => round($point['x'], 2) . ',' . round($point['y'], 2), $points));
    $firstX = $points[0]['x'] ?? $padLeft;
    $lastX = $points[$count - 1]['x'] ?? $padLeft + $plotWidth;
    $baseline = $padTop + $plotHeight;
    $area = round($firstX, 2) . ',' . $baseline . ' ' . $line . ' ' . round($lastX, 2) . ',' . $baseline;

    $tickCount = min(6, $count);
    $tickIndexes = [];
    for ($i = 0; $i < $tickCount; $i++) {
        $tickIndexes[] = (int) round($i * ($count - 1) / max(1, $tickCount - 1));
    }
    $tickIndexes = array_values(array_unique($tickIndexes));

    $summary = 'Tren pengajuan ' . ($trend['unit'] ?? 'hari') . ': total ' . number_format($sum, 0, ',', '.') . ' pengajuan';
    if ($peak > 0) {
        $summary .= ', tertinggi ' . number_format($peak, 0, ',', '.') . ' pada ' . ($peakIndex !== false ? ($labels[$peakIndex] ?? '') : '');
    }
    $summary .= '.';
@endphp

@if ($count === 0 || $sum === 0)
    <p class="sd-empty">Belum ada pengajuan pada periode ini.</p>
@else
    <figure class="sd-chart">
        <svg class="sd-chart__svg" viewBox="0 0 {{ $width }} {{ $height }}" role="img"
            aria-label="{{ $summary }}">
            @for ($i = 0; $i <= 3; $i++)
                @php
                    $gridY = $padTop + $plotHeight * ($i / 3);
                    $gridValue = $max * (1 - $i / 3);
                @endphp
                <line x1="{{ $padLeft }}" y1="{{ round($gridY, 2) }}" x2="{{ $width - $padRight }}"
                    y2="{{ round($gridY, 2) }}" class="sd-chart__grid" />
                <text x="{{ $padLeft - 10 }}" y="{{ round($gridY + 4, 2) }}" text-anchor="end"
                    class="sd-chart__tick">{{ number_format((int) round($gridValue), 0, ',', '.') }}</text>
            @endfor

            <polygon points="{{ $area }}" class="sd-chart__area" />
            <polyline points="{{ $line }}" class="sd-chart__line" />

            @if ($count <= 31)
                @foreach ($points as $point)
                    <circle cx="{{ round($point['x'], 2) }}" cy="{{ round($point['y'], 2) }}" r="3.5"
                        class="sd-chart__dot">
                        <title>{{ $point['label'] }}: {{ number_format($point['value'], 0, ',', '.') }} pengajuan</title>
                    </circle>
                @endforeach
            @endif

            @foreach ($tickIndexes as $index)
                @if (isset($points[$index]))
                    <text x="{{ round($points[$index]['x'], 2) }}" y="{{ $height - 10 }}" text-anchor="middle"
                        class="sd-chart__tick">{{ $points[$index]['label'] }}</text>
                @endif
            @endforeach
        </svg>

        <figcaption class="sd-chart__caption">
            Puncak {{ number_format($peak, 0, ',', '.') }} pengajuan
            @if ($peakIndex !== false) pada {{ $labels[$peakIndex] ?? '' }} @endif
            · total {{ number_format($sum, 0, ',', '.') }} pengajuan.
        </figcaption>
    </figure>
@endif
