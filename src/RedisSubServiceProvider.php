<?php

namespace Iankibet\RedisSub;

use Illuminate\Support\ServiceProvider;

class RedisSubServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register()
    {
        // Register any bindings or services here
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Iankibet\RedisSub\Console\RedisSubscriberCommand::class,
            ]);
            $this->publishes([
                __DIR__ . '/config/redis-sub.php' => config_path('redis-sub.php'),
            ], 'redis-sub');
        }
    }
}
