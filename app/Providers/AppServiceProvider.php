<?php

namespace App\Providers;

use App\Models\User;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('write-business', fn (User $user) => $user->canWrite());
        FilamentAsset::register([Css::make('solvet-theme', public_path('css/solvet.css'))]);
    }
}
