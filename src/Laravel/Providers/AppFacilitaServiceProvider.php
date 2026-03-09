<?php

declare(strict_types=1);

namespace R2Soft\AppFacilita\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use R2Soft\AppFacilita\Clients\AppFacilitaClient;
use R2Soft\AppFacilita\Core\AppFacilitaConfig;

class AppFacilitaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/app-facilita.php', 'app-facilita');

        $this->app->singleton(AppFacilitaConfig::class, function ($app) {
            $config = $app['config']->get('app-facilita', []);

            return new AppFacilitaConfig(
                (string) ($config['base_url'] ?? ''),
                (string) ($config['api_instance'] ?? ''),
                (string) ($config['api_key'] ?? ''),
                (string) ($config['token_user'] ?? ''),
                (int) ($config['timeout'] ?? 10),
                isset($config['connect_timeout']) ? (int) $config['connect_timeout'] : null,
                $config['user_agent'] ?? null,
                $config['proxy'] ?? null,
                (int) ($config['retry_max'] ?? 3),
                (int) ($config['retry_base_ms'] ?? 250)
            );
        });

        $this->app->singleton(AppFacilitaClient::class, function ($app) {
            return new AppFacilitaClient($app->make(AppFacilitaConfig::class));
        });

        $this->app->alias(AppFacilitaClient::class, 'app-facilita');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config/app-facilita.php' => config_path('app-facilita.php'),
        ], 'config');
    }
}
