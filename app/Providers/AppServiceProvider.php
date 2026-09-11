<?php

namespace App\Providers;

use App\Models\Portfolio;
use App\Policies\PortfolioPolicy;
use App\Livewire\Portfolio\CapstoneForm;
use App\Livewire\Portfolio\OjtRecordForm;
use App\Livewire\Portfolio\ReflectionForm;
use App\Services\PloAttainmentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One shared attainment service per request: several views ask it for
        // the same numbers, and it does non-trivial work.
        $this->app->singleton(PloAttainmentService::class);
    }

    public function boot(): void
    {
        Gate::policy(Portfolio::class, PortfolioPolicy::class);

        // Chairs and admins see everything without a per-model rule.
        Gate::before(fn ($user) => $user->hasAnyRole(['chair', 'admin']) ? true : null);

        // Three components are named after their form but referenced in
        // config/portfolio.php by their section name. Alias them so the config
        // stays readable.
        Livewire::component('portfolio.ojt-record', OjtRecordForm::class);
        Livewire::component('portfolio.capstone', CapstoneForm::class);
        Livewire::component('portfolio.reflection', ReflectionForm::class);
    }
}
