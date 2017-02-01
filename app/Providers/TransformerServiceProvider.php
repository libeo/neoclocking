<?php

namespace NeoClocking\Providers;

use Illuminate\Support\ServiceProvider;

class TransformerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $transformers = config('transformers.aliases', []);

        foreach ($transformers as $contract => $transformer) {
            $this->app->bind($contract, $transformer);
        }
    }
}
