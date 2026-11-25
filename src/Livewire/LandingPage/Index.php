<?php

namespace Bale\BaleSidulur\Livewire\LandingPage;

use Bale\BaleSidulur\Models\Service;
use Bale\Umpak\Livewire\UmpakComponent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

class Index extends UmpakComponent
{
    #[Layout('bale-sidulur::layouts.app')]
    #[Title('Sidulur — Pelayanan Warga')]
    public function render()
    {
        try {
            $services = Service::query()
                ->with(['bidang', 'form'])
                ->where('is_active', true)
                ->orderBy('sort')
                ->orderBy('nama')
                ->get();
            $servicesUnavailable = false;
        } catch (\Throwable $e) {
            report($e);
            $services = collect();
            $servicesUnavailable = true;
        }

        return view('bale-sidulur::livewire.landing-page.index', [
            'services' => $services,
            'servicesUnavailable' => $servicesUnavailable,
        ]);
    }
}
