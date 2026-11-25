<?php

namespace Bale\BaleSidulur;

use Bale\BaleSidulur\Services\SidulurConnection;
use Bale\Umpak\Concerns\HasLandingPageGuard;
use Bale\Umpak\Concerns\HasLivewireComponents;
use Bale\Umpak\Umpak;
use Illuminate\Support\ServiceProvider;

class BaleSidulurServiceProvider extends ServiceProvider
{
    use HasLandingPageGuard, HasLivewireComponents;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sidulur.php', 'sidulur');

        SidulurConnection::register();

        $this->app->resolving(Umpak::class, function (Umpak $umpak) {
            $umpak->registerLandingPage('sidulur', 'Sidulur');
        });
    }

    protected function landingPageSlug(): string
    {
        return config('sidulur.landing_slug', 'sidulur');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bale-sidulur');

        if ($this->isActiveLandingPage()) {
            $this->app->booted(function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });

            $this->app['view']->prependLocation(__DIR__.'/../resources/views');

            $this->registerLivewireComponents();
        }
    }

    protected function registerLivewireComponents(): void
    {
        $this->discoverLivewireComponents(
            __DIR__.'/Livewire',
            'Bale\\BaleSidulur\\Livewire',
            'bale-sidulur'
        );
    }
}
