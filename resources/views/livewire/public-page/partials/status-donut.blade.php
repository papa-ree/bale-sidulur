@php
    $rows = $status ?? [];
    $total = array_sum(array_column($rows, 'value'));
    $circumference = 2 * M_PI * 54;
    $offset = 0;
    $segments = [];

    foreach ($rows as $row) {
        $length = $total > 0 ? $circumference * ($row['value'] / $total) : 0;
        $segments[] = [
            'key' => $row['key'],
            'label' => $row['label'],
            'value' => (int) $row['value'],
            'percent' => $total > 0 ? round($row['value'] / $total * 100) : 0,
            'length' => max(0, $length - ($length > 8 ? 4 : 0)),
            'offset' => $offset,
        ];
        $offset += $length;
    }

    $summary = collect($segments)
        ->map(fn ($row) => $row['value'] . ' ' . strtolower($row['label']))
        ->implode(', ');
@endphp

@if ($total === 0)
    <p class="sd-empty">Belum ada pengajuan pada periode ini.</p>
@else
    <div class="sd-donut">
        <div class="sd-donut__chart">
            <svg viewBox="0 0 140 140" role="img" aria-label="Komposisi status: {{ $summary }}.">
                <circle cx="70" cy="70" r="54" class="sd-donut__track" />
                @foreach ($segments as $segment)
                    @if ($segment['value'] > 0)
                        <circle cx="70" cy="70" r="54" transform="rotate(-90 70 70)"
                            class="sd-donut__seg sd-donut__seg--{{ $segment['key'] }}"
                            style="--i: {{ $loop->index }}; --gap: {{ round($circumference, 2) }}"
                            stroke-dasharray="{{ round($segment['length'], 2) }} {{ round($circumference, 2) }}"
                            stroke-dashoffset="{{ round(-$segment['offset'], 2) }}">
                            <title>{{ $segment['label'] }}: {{ number_format($segment['value'], 0, ',', '.') }}</title>
                        </circle>
                    @endif
                @endforeach
            </svg>
            <div class="sd-donut__center">
                <strong>{{ number_format($total, 0, ',', '.') }}</strong>
                <span>pengajuan</span>
            </div>
        </div>

        <ul class="sd-donut__legend">
            @foreach ($segments as $segment)
                <li>
                    <i class="sd-legend__swatch sd-legend__swatch--{{ $segment['key'] }}" aria-hidden="true"></i>
                    <span class="sd-donut__name">{{ $segment['label'] }}</span>
                    <span class="sd-donut__value">{{ number_format($segment['value'], 0, ',', '.') }}</span>
                    <span class="sd-donut__pct">{{ $segment['percent'] }}%</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
