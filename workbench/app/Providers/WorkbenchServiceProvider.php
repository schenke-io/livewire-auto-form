<?php

namespace Workbench\App\Providers;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Workbench\App\Console\Commands\MakeReadmeCommand;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            MakeReadmeCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! App::environment('testing')) {
            $this->app['config']->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => realpath(__DIR__.'/../../database').'/database.sqlite',
                'prefix' => '',
            ]);
            $this->app['config']->set('database.default', 'testbench');
        }
        // Ensure our Workbench routes are the ones being served during `composer serve`.
        $this->loadRoutesFrom(\dirname(__DIR__, 2).'/routes/web.php');

        // Make Workbench views available both with a namespace and as regular views.
        $viewsPath = \dirname(__DIR__, 2).'/resources/views';
        $this->loadViewsFrom($viewsPath, 'workbench');
        View::addLocation($viewsPath);

        // Optional: set a friendly default app name for the demo UI if not set.
        config()->set('app.name', config('app.name', 'Livewire Auto Form Workbench'));

        // Ensure Livewire is available and components are registered when rendering views in tests/workbench.
        $this->bootLivewireComponents();

        // Configure Vite for the workbench
        $this->configureVite();
    }

    /**
     * Configure Vite to work with the Workbench.
     */
    protected function configureVite(): void
    {
        $skeletonPublicPath = public_path();
        $workbenchPublicPath = realpath(__DIR__.'/../../public');

        if ($workbenchPublicPath) {
            // symlink build directory
            $this->ensureSymlink($workbenchPublicPath.'/build', $skeletonPublicPath.'/build');
            // symlink hot file
            $this->ensureSymlink($workbenchPublicPath.'/hot', $skeletonPublicPath.'/hot');
        }
    }

    /**
     * Ensure a symlink exists.
     */
    protected function ensureSymlink(string $target, string $link): void
    {
        if (file_exists($target) && ! file_exists($link)) {
            @symlink($target, $link);
        }
    }

    /**
     * Register Livewire and auto-register all Workbench Livewire components.
     */
    protected function bootLivewireComponents(): void
    {
        // If Livewire isn't installed, quietly skip.
        if (! class_exists(\Livewire\LivewireManager::class) && ! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        // Make sure the Livewire service provider is registered (useful during tests when provider list is overridden).
        try {
            if (! $this->app->providerIsLoaded(\Livewire\LivewireServiceProvider::class)) {
                $this->app->register(\Livewire\LivewireServiceProvider::class);
            }
            if (class_exists(\Flux\FluxServiceProvider::class) && ! $this->app->providerIsLoaded(\Flux\FluxServiceProvider::class)) {
                $this->app->register(\Flux\FluxServiceProvider::class);
            }
        } catch (\Throwable $e) {
            // fallback: attempt to register and ignore failures
            try {
                $this->app->register(\Livewire\LivewireServiceProvider::class);
            } catch (\Throwable $ignored) {
            }
            try {
                if (class_exists(\Flux\FluxServiceProvider::class)) {
                    $this->app->register(\Flux\FluxServiceProvider::class);
                }
            } catch (\Throwable $ignored) {
            }
        }

        // Resolve Livewire facade/class
        $livewire = class_exists(\Livewire\Livewire::class) ? \Livewire\Livewire::class : null;
        if ($livewire === null) {
            return; // Can't proceed without the registrar
        }

        // Auto-discover and register components inside workbench/app/Livewire
        $componentsPath = \dirname(__DIR__, 2).'/app/Livewire';
        if (! is_dir($componentsPath)) {
            return;
        }

        $namespace = 'Workbench\\App\\Livewire';
        foreach (glob($componentsPath.'/*.php') as $file) {
            $class = pathinfo($file, PATHINFO_FILENAME);
            $fqcn = $namespace.'\\'.$class;
            if (! class_exists($fqcn)) {
                // Attempt to include the file to load the class
                try {
                    require_once $file;
                } catch (\Throwable $e) {
                    continue;
                }
                if (! class_exists($fqcn)) {
                    continue;
                }
            }

            // Only register classes that extend the Livewire Component base class
            if (is_subclass_of($fqcn, \Livewire\Component::class)) {
                $alias = Str::kebab($class); // e.g., CityShowEditor -> city-show-editor
                try {
                    $livewire::component($alias, $fqcn);
                } catch (\Throwable $e) {
                    // ignore duplicate registration, etc.
                }
            }
        }
    }
}
