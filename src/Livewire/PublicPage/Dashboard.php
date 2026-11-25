<?php

namespace Bale\BaleSidulur\Livewire\PublicPage;

use Bale\BaleSidulur\Models\Setting;
use Bale\BaleSidulur\Services\DashboardMetrics;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('bale-sidulur::layouts.guest')]
#[Title('Dashboard Eksekutif — Sidulur')]
class Dashboard extends Component
{
    public string $password = '';

    public bool $unlocked = false;

    public ?string $error = null;

    public string $preset = 'this_month';

    public int $month = 1;

    public int $year = 2000;

    public ?string $from = null;

    public ?string $to = null;

    /** @var array<string, mixed> */
    public array $metrics = [];

    public function mount(): void
    {
        $now = CarbonImmutable::now();
        $this->month = $now->month;
        $this->year = $now->year;
        $this->from = $now->startOfMonth()->toDateString();
        $this->to = $now->toDateString();
    }

    public function unlock(DashboardMetrics $dashboard): void
    {
        $this->validate(['password' => ['required', 'string']], ['password.required' => 'Masukkan password dashboard.']);

        try {
            if (! Setting::checkPassword($this->password)) {
                $this->error = 'Password tidak sesuai.';
                $this->password = '';

                return;
            }

            $this->metrics = $this->load($dashboard);
        } catch (Throwable $e) {
            report($e);
            $this->error = 'Layanan sedang tidak dapat diakses. Silakan coba lagi nanti.';
            $this->password = '';

            return;
        }

        $this->password = '';
        $this->error = null;
        $this->unlocked = true;
    }

    public function applyPreset(string $preset, DashboardMetrics $dashboard): void
    {
        if (! in_array($preset, ['this_month', 'last_month', 'quarter', 'this_year'], true)) {
            return;
        }

        $this->preset = $preset;
        $this->syncPresetRange();
        $this->refresh($dashboard);
    }

    public function applyMonth(DashboardMetrics $dashboard): void
    {
        $this->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,'.max(now()->year, 2000)],
        ], [
            'month.required' => 'Pilih bulan.',
            'month.integer' => 'Bulan tidak valid.',
            'month.between' => 'Bulan harus antara Januari dan Desember.',
            'year.required' => 'Pilih tahun.',
            'year.integer' => 'Tahun tidak valid.',
            'year.between' => 'Tahun di luar jangkauan data.',
        ]);

        $start = CarbonImmutable::create($this->year, $this->month, 1)->startOfMonth();
        $now = CarbonImmutable::now();

        if ($start->greaterThan($now)) {
            $this->addError('month', 'Bulan yang dipilih belum berjalan.');

            return;
        }

        $end = $start->endOfMonth();

        $this->preset = 'month';
        $this->from = $start->toDateString();
        $this->to = ($end->greaterThan($now) ? $now : $end)->toDateString();
        $this->refresh($dashboard);
    }

    public function applyCustom(DashboardMetrics $dashboard): void
    {
        $this->validate([
            'from' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
            'to' => ['required', 'date', 'after_or_equal:from', 'before_or_equal:today'],
        ], [
            'from.required' => 'Isi tanggal mulai.',
            'from.date' => 'Tanggal mulai tidak valid.',
            'from.after_or_equal' => 'Tanggal mulai paling awal 1 Januari 2000.',
            'from.before_or_equal' => 'Tanggal mulai tidak boleh melewati hari ini.',
            'to.required' => 'Isi tanggal akhir.',
            'to.date' => 'Tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            'to.before_or_equal' => 'Tanggal akhir tidak boleh melewati hari ini.',
        ]);

        $this->preset = 'custom';
        $this->refresh($dashboard);
    }

    public function lock(): void
    {
        $this->unlocked = false;
        $this->metrics = [];
        $this->password = '';
        $this->error = null;
        $this->preset = 'this_month';

        $this->mount();
        $this->resetValidation();
    }

    public function render()
    {
        return view('bale-sidulur::livewire.public-page.dashboard');
    }

    private function syncPresetRange(): void
    {
        $now = CarbonImmutable::now();

        [$from, $to] = match ($this->preset) {
            'last_month' => [
                $now->startOfMonth()->subMonth()->startOfMonth(),
                $now->startOfMonth()->subMonth()->endOfMonth(),
            ],
            'quarter' => [$now->subMonths(2)->startOfMonth(), $now],
            'this_year' => [$now->startOfYear(), $now],
            default => [$now->startOfMonth(), $now],
        };

        $this->from = $from->toDateString();
        $this->to = $to->toDateString();

        // Selector bulan/tahun ikut periode supaya tidak menampilkan bulan yang salah.
        $this->month = $from->month;
        $this->year = $from->year;
    }

    /**
     * @return array<string, mixed>
     */
    private function load(DashboardMetrics $dashboard): array
    {
        return $dashboard->summary(
            CarbonImmutable::parse((string) $this->from)->startOfDay(),
            CarbonImmutable::parse((string) $this->to)->endOfDay(),
        );
    }

    private function refresh(DashboardMetrics $dashboard): void
    {
        if (! $this->unlocked) {
            return;
        }

        try {
            $this->metrics = $this->load($dashboard);
            $this->error = null;
        } catch (Throwable $e) {
            report($e);
            $this->error = 'Data periode ini gagal dimuat. Silakan coba lagi.';
        }
    }
}
