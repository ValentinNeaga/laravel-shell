<?php

namespace Jakyeru\LaravelShell;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LaravelShellServiceProvider extends ServiceProvider
{
    /**
     * The current version of Laravel Shell.
     */
    const string VERSION = '4.0.0';

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfig();
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->defineGate();

        if (config('laravel-shell.enabled')) {
            $this->registerRoutes();
            $this->registerViews();
            $this->registerLivewireComponents();
        }
    }

    /**
     * Register the package config.
     */
    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/laravel-shell.php', 'laravel-shell');
    }

    /**
     * Publish the package config.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/config/laravel-shell.php' => config_path('laravel-shell.php'),
        ], 'config');
    }

    /**
     * Define the default authorization gate.
     *
     * The gate grants access in the local environment only. It is defined
     * after the application has booted and only when the application has not
     * defined its own "viewLaravelShell" gate.
     */
    protected function defineGate(): void
    {
        $this->app->booted(function () {
            if (! Gate::has('viewLaravelShell')) {
                Gate::define('viewLaravelShell', fn ($user = null) => $this->app->environment('local'));
            }
        });
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    /**
     * Register the package views.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'laravel-shell');
    }

    /**
     * Register the livewire components.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('laravel-shell.terminal', \Jakyeru\LaravelShell\Http\Livewire\Terminal::class);
    }
}
