<?php

namespace Bale\BaleSidulur\Services;

use Bale\BaleSidulur\Models\Bidang;
use Bale\BaleSidulur\Models\Citizen;
use Bale\BaleSidulur\Models\PelayananBidang;
use Bale\BaleSidulur\Models\Pendaftaran;
use Bale\BaleSidulur\Models\Service;
use Carbon\CarbonImmutable;

class DashboardMetrics
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @return array<string, mixed>
     */
    public function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = [$from, $to];
        $length = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->subDay()->endOfDay();
        $prevFrom = $prevTo->subDays($length - 1)->startOfDay();
        $previousRange = [$prevFrom, $prevTo];

        $period = fn () => Pendaftaran::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', $range);

        $bidangDone = fn () => PelayananBidang::query()
            ->whereNull('deleted_at')
            ->where('status', 'selesai')
            ->whereBetween('completed_at', $range);

        $deskDone = fn () => Pendaftaran::query()
            ->whereNull('deleted_at')
            ->where('status', 'selesai')
            ->whereNull('forwarded_at')
            ->whereBetween('completed_at', $range);

        $total = $period()->count();
        $selesai = $bidangDone()->count() + $deskDone()->count();

        $previousTotal = Pendaftaran::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', $previousRange)
            ->count();

        $previousSelesai = PelayananBidang::query()
            ->whereNull('deleted_at')
            ->where('status', 'selesai')
            ->whereBetween('completed_at', $previousRange)
            ->count()
            + Pendaftaran::query()
                ->whereNull('deleted_at')
                ->where('status', 'selesai')
                ->whereNull('forwarded_at')
                ->whereBetween('completed_at', $previousRange)
                ->count();

        $bidangStats = $bidangDone()
            ->selectRaw('COUNT(*) as total, AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at)) as avg_seconds')
            ->first();

        $deskStats = $deskDone()
            ->selectRaw('COUNT(*) as total, AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at)) as avg_seconds')
            ->first();

        $doneCount = (int) ($bidangStats->total ?? 0) + (int) ($deskStats->total ?? 0);
        $seconds = (float) ($bidangStats->avg_seconds ?? 0) * (int) ($bidangStats->total ?? 0)
            + (float) ($deskStats->avg_seconds ?? 0) * (int) ($deskStats->total ?? 0);
        $avgDays = $doneCount > 0 ? $seconds / $doneCount / 86400 : null;

        $statusRows = Pendaftaran::query()
            ->leftJoin('sidulur_pelayanan_bidangs as pb', function ($join) {
                $join->on('pb.pendaftaran_id', '=', 'sidulur_pendaftarans.id')
                    ->whereNull('pb.deleted_at');
            })
            ->whereNull('sidulur_pendaftarans.deleted_at')
            ->whereBetween('sidulur_pendaftarans.created_at', $range)
            ->selectRaw('sidulur_pendaftarans.status as p_status, pb.status as pb_status, count(*) as total')
            ->groupBy('sidulur_pendaftarans.status', 'pb.status')
            ->get();

        $status = ['selesai' => 0, 'diproses' => 0, 'verlap' => 0, 'baru' => 0];

        foreach ($statusRows as $row) {
            $pb = $row->pb_status;

            if ($pb === 'selesai' || ($pb === null && $row->p_status === 'selesai')) {
                $status['selesai'] += (int) $row->total;
            } elseif ($pb === 'dijadwalkan') {
                $status['verlap'] += (int) $row->total;
            } elseif ($pb === null) {
                $status['baru'] += (int) $row->total;
            } else {
                $status['diproses'] += (int) $row->total;
            }
        }

        $topServices = $period()
            ->selectRaw('service_id, count(*) as total')
            ->with('service')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'nama' => $row->service?->nama ?? 'Layanan terhapus',
                'value' => (int) $row->total,
                'percent' => $total > 0 ? round((int) $row->total / $total * 100) : 0,
            ])
            ->all();

        $bidangRows = PelayananBidang::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', $range)
            ->selectRaw('bidang_id, status, count(*) as total')
            ->with('bidang')
            ->groupBy('bidang_id', 'status')
            ->get()
            ->groupBy(fn ($row) => $row->bidang?->nama ?? 'Lainnya')
            ->map(function ($rows, $nama) {
                $selesai = $rows->where('status', 'selesai')->sum('total');
                $total = $rows->sum('total');

                return [
                    'nama' => $nama,
                    'total' => (int) $total,
                    'selesai' => (int) $selesai,
                    'berjalan' => (int) ($total - $selesai),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values()
            ->all();

        $belumDiteruskan = $period()
            ->whereDoesntHave('pelayananBidang', fn ($query) => $query->whereNull('deleted_at'))
            ->count();

        $hourly = $period()
            ->selectRaw('HOUR(created_at) as hour, count(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        $hours = array_fill(0, 12, 0);
        foreach ($hourly as $hour => $count) {
            $hours[intdiv((int) $hour, 2)] += (int) $count;
        }

        $trend = $this->trend($from, $to, $range);
        $sparkline = $this->sparkline();

        // Sebaran wilayah: agregat per kecamatan, tanpa identitas warga.
        $districts = Pendaftaran::query()
            ->whereNull('sidulur_pendaftarans.deleted_at')
            ->whereBetween('sidulur_pendaftarans.created_at', $range)
            ->join('sidulur_citizens as c', 'c.id', '=', 'sidulur_pendaftarans.citizen_id')
            ->join('sidulur_family_cards as f', 'f.id', '=', 'c.family_card_id')
            ->whereNull('c.deleted_at')
            ->whereNull('f.deleted_at')
            ->selectRaw('f.district_name as nama, count(*) as total')
            ->groupBy('f.district_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'nama' => $row->nama,
                'value' => (int) $row->total,
                'percent' => $total > 0 ? (int) round((int) $row->total / $total * 100) : 0,
            ])
            ->all();

        // Pengajuan yang diteruskan selesai di bidang; completed_at pendaftaran hanya tanggal teruskan.
        $doneAt = 'CASE WHEN sidulur_pendaftarans.forwarded_at IS NULL THEN sidulur_pendaftarans.completed_at ELSE pb.completed_at END';
        $elapsed = "TIMESTAMPDIFF(SECOND, sidulur_pendaftarans.created_at, {$doneAt})";
        $target = "CASE s.sla_unit WHEN 'jam' THEN s.sla_value * 3600 WHEN 'minggu' THEN s.sla_value * 604800 ELSE s.sla_value * 86400 END";

        // SLA hanya bisa dinilai untuk pengajuan yang sudah selesai.
        $slaRows = Pendaftaran::query()
            ->leftJoin('sidulur_pelayanan_bidangs as pb', function ($join) {
                $join->on('pb.pendaftaran_id', '=', 'sidulur_pendaftarans.id')
                    ->whereNull('pb.deleted_at');
            })
            ->join('sidulur_services as s', 's.id', '=', 'sidulur_pendaftarans.service_id')
            ->whereNull('sidulur_pendaftarans.deleted_at')
            ->where('s.sla_value', '>', 0)
            ->whereRaw("{$doneAt} BETWEEN ? AND ?", $range)
            ->selectRaw("s.id as id, s.nama as nama, COUNT(*) as total, AVG({$elapsed}) as avg_seconds, SUM(CASE WHEN {$elapsed} <= {$target} THEN 1 ELSE 0 END) as ontime")
            ->groupBy('s.id', 's.nama')
            ->get();

        $slaTotal = (int) $slaRows->sum('total');
        $slaOntime = (int) $slaRows->sum('ontime');

        $sla = [
            'total' => $slaTotal,
            'ontime' => $slaOntime,
            'percent' => $slaTotal > 0 ? (int) round($slaOntime / $slaTotal * 100) : 0,
            'rows' => $slaRows
                ->sortByDesc('total')
                ->take(6)
                ->map(fn ($row) => [
                    'nama' => $row->nama,
                    'total' => (int) $row->total,
                    'ontime' => (int) $row->ontime,
                    'percent' => (int) ($row->total > 0 ? round((int) $row->ontime / (int) $row->total * 100) : 0),
                    'avg' => $this->duration((int) round((float) $row->avg_seconds)),
                ])
                ->values()
                ->all(),
        ];

        // Hasil verlap tidak disimpan sebagai keputusan, jadi yang diukur tahapannya.
        $verlapRow = PelayananBidang::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', $range)
            ->selectRaw('COUNT(*) as masuk,
                SUM(CASE WHEN scheduled_at IS NOT NULL THEN 1 ELSE 0 END) as dijadwalkan,
                SUM(CASE WHEN verlap_at IS NOT NULL THEN 1 ELSE 0 END) as terlaksana,
                SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as selesai,
                AVG(CASE WHEN verlap_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, verlap_at) END) as to_verlap,
                AVG(CASE WHEN completed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, COALESCE(verlap_at, created_at), completed_at) END) as to_done')
            ->first();

        $verlapStages = [
            ['key' => 'masuk', 'label' => 'Diteruskan ke bidang', 'value' => (int) ($verlapRow->masuk ?? 0)],
            ['key' => 'dijadwalkan', 'label' => 'Dijadwalkan verifikasi lapangan', 'value' => (int) ($verlapRow->dijadwalkan ?? 0)],
            ['key' => 'terlaksana', 'label' => 'Verifikasi lapangan terlaksana', 'value' => (int) ($verlapRow->terlaksana ?? 0)],
            ['key' => 'selesai', 'label' => 'Selesai oleh bidang', 'value' => (int) ($verlapRow->selesai ?? 0)],
        ];

        $verlapBase = max(1, $verlapStages[0]['value']);
        foreach ($verlapStages as &$stage) {
            $stage['percent'] = (int) round($stage['value'] / $verlapBase * 100);
        }
        unset($stage);

        $verlap = [
            'stages' => $verlapStages,
            'avg_verlap' => $verlapRow?->to_verlap === null ? null : $this->duration((int) round((float) $verlapRow->to_verlap)),
            'avg_done' => $verlapRow?->to_done === null ? null : $this->duration((int) round((float) $verlapRow->to_done)),
        ];

        $firstYear = Pendaftaran::query()->whereNull('deleted_at')->min('created_at');
        $firstYear = $firstYear ? CarbonImmutable::parse($firstYear)->year : now()->year;

        return [
            'total_all' => Pendaftaran::query()->whereNull('deleted_at')->count(),
            'total_period' => $total,
            'previous_total' => $previousTotal,
            'selesai' => $selesai,
            'previous_selesai' => $previousSelesai,
            'belum_selesai' => max(0, $total - $status['selesai']),
            'avg' => $avgDays === null ? null : $this->duration((int) round($avgDays * 86400)),
            'citizens' => Citizen::query()->whereNull('deleted_at')->count(),
            'services' => Service::query()->whereNull('deleted_at')->where('is_active', true)->count(),
            'bidang_active' => Bidang::query()->whereNull('deleted_at')->where('is_active', true)->count(),
            'antrean_bidang' => PelayananBidang::query()
                ->whereNull('deleted_at')
                ->whereIn('status', ['baru', 'verlap_selesai'])
                ->count(),
            'antrean_verlap' => PelayananBidang::query()
                ->whereNull('deleted_at')
                ->where('status', 'dijadwalkan')
                ->count(),
            'years' => range(now()->year, $firstYear),
            'first_year' => $firstYear,
            'period_label' => $this->periodLabel($from, $to),
            'trend' => $trend,
            'sparkline' => $sparkline,
            'status' => [
                ['key' => 'selesai', 'label' => 'Selesai', 'value' => $status['selesai']],
                ['key' => 'diproses', 'label' => 'Diproses bidang', 'value' => $status['diproses']],
                ['key' => 'verlap', 'label' => 'Verifikasi lapangan', 'value' => $status['verlap']],
                ['key' => 'baru', 'label' => 'Baru di pendaftaran', 'value' => $status['baru']],
            ],
            'top_services' => $topServices,
            'bidang' => $bidangRows,
            'districts' => $districts,
            'sla' => $sla,
            'verlap' => $verlap,
            'belum_diteruskan' => $belumDiteruskan,
            'hours' => [
                'labels' => array_map(fn ($i) => sprintf('%02d–%02d', $i * 2, $i * 2 + 2), array_keys($hours)),
                'values' => array_values($hours),
            ],
            'recent' => Pendaftaran::query()
                ->whereNull('deleted_at')
                ->with(['service', 'pelayananBidang.bidang'])
                ->whereBetween('created_at', $range)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'ticket' => $row->ticket_number,
                    'service' => $row->service?->nama ?? '—',
                    'bidang' => $row->pelayananBidang?->bidang?->nama ?? '—',
                    'status' => $row->publicStatus(),
                    'tone' => match (true) {
                        $row->pelayananBidang?->status === 'selesai',
                        $row->pelayananBidang === null && $row->status === 'selesai' => 'done',
                        $row->pelayananBidang?->status === 'dijadwalkan' => 'wait',
                        $row->pelayananBidang === null => 'neutral',
                        default => 'process',
                    },
                    'created_at' => $row->created_at?->format('d M Y H:i'),
                ])
                ->all(),
        ];
    }

    /**
     * Harian untuk rentang pendek, bulanan untuk rentang panjang.
     *
     * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $range
     * @return array{labels: array<int, string>, values: array<int, int>, unit: string}
     */
    private function trend(CarbonImmutable $from, CarbonImmutable $to, array $range): array
    {
        if ((int) $from->diffInDays($to) + 1 > 62) {
            $rows = Pendaftaran::query()
                ->whereNull('deleted_at')
                ->whereBetween('created_at', $range)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, count(*) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');

            $labels = [];
            $values = [];
            $cursor = $from->startOfMonth();
            $end = $to->startOfMonth();

            while ($cursor->lessThanOrEqualTo($end)) {
                $labels[] = self::MONTHS[$cursor->month - 1].' '.$cursor->format('Y');
                $values[] = (int) ($rows[$cursor->format('Y-m')] ?? 0);
                $cursor = $cursor->addMonth();
            }

            return ['labels' => $labels, 'values' => $values, 'unit' => 'bulan'];
        }

        $rows = Pendaftaran::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', $range)
            ->selectRaw('DATE(created_at) as bucket, count(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $values = [];
        $cursor = $from->startOfDay();

        while ($cursor->lessThanOrEqualTo($to)) {
            $labels[] = $cursor->format('j').' '.self::MONTHS[$cursor->month - 1];
            $values[] = (int) ($rows[$cursor->toDateString()] ?? 0);
            $cursor = $cursor->addDay();
        }

        return ['labels' => $labels, 'values' => $values, 'unit' => 'hari'];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function sparkline(): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $rows = Pendaftaran::query()
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, now()->endOfMonth()])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, count(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $labels = [];
        $values = [];
        $cursor = CarbonImmutable::parse($start);

        for ($i = 0; $i < 12; $i++) {
            $labels[] = self::MONTHS[$cursor->month - 1];
            $values[] = (int) ($rows[$cursor->format('Y-m')] ?? 0);
            $cursor = $cursor->addMonth();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Durasi dalam satuan yang masuk akal: menit, jam, atau hari.
     *
     * @return array{value: string, unit: string}
     */
    private function duration(int $seconds): array
    {
        return match (true) {
            $seconds < 60 => ['value' => '<1', 'unit' => 'menit'],
            $seconds < 3600 => ['value' => number_format($seconds / 60, 0, ',', '.'), 'unit' => 'menit'],
            $seconds < 86400 => ['value' => number_format($seconds / 3600, 1, ',', '.'), 'unit' => 'jam'],
            default => ['value' => number_format($seconds / 86400, 1, ',', '.'), 'unit' => 'hari'],
        };
    }

    private function periodLabel(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $short = fn (CarbonImmutable $date) => $date->format('j').' '.self::MONTHS[$date->month - 1];

        if ($from->isSameDay($to)) {
            return $short($to).' '.$to->format('Y');
        }

        $left = $short($from).($from->year === $to->year ? '' : ' '.$from->year);

        return $left.' – '.$short($to).' '.$to->format('Y');
    }
}
