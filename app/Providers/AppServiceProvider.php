<?php

namespace NeoClocking\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Blade;
use Illuminate\Support\ServiceProvider;
use Laracasts\Generators\GeneratorsServiceProvider;
use NeoClocking\Services\FractalService;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('allowDateForUser', 'NeoClocking\Validators\Rules\AllowDateForUserValidator@validate');

        Blade::directive('title', function ($expression) {
            preg_match('/^\((?P<key>[^,]+)(?P<default>[^\)]+)\)$/', $expression, $matches);

            return "<?php echo collect(array(\$__env->yieldContent({$matches['key']}){$matches['default']}))->filter()->implode(' - '); ?>";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() === 'local') {
            $this->app->register(IdeHelperServiceProvider::class);
            $this->app->register(GeneratorsServiceProvider::class);
        }

        $this->app->bind('fractal', FractalService::class);
    }
}
