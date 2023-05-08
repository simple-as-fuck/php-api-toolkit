<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\RequestFactoryInterface;
use SimpleAsFuck\ApiToolkit\Service\Client\ApiClient;
use SimpleAsFuck\ApiToolkit\Service\Client\Config;
use SimpleAsFuck\ApiToolkit\Service\Client\DeprecationsLogger;
use SimpleAsFuck\ApiToolkit\Service\Client\LaravelConfig;
use SimpleAsFuck\ApiToolkit\Service\Config\LaravelAdapter;
use SimpleAsFuck\ApiToolkit\Service\Config\Repository;
use SimpleAsFuck\ApiToolkit\Service\Webhook\Client as WebhookClient;
use SimpleAsFuck\ApiToolkit\Service\Webhook\Config as WebhookConfig;
use SimpleAsFuck\ApiToolkit\Service\Webhook\LaravelClient;
use SimpleAsFuck\ApiToolkit\Service\Webhook\LaravelMysqlRepository;

class LaravelProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Config::class, LaravelConfig::class);
        $this->app->singleton(Repository::class, LaravelAdapter::class);
        $this->app->singleton(RequestFactoryInterface::class, HttpFactory::class);
        $this->app->singleton(ApiClient::class, function (): ApiClient {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $this->app->make(\Illuminate\Contracts\Config\Repository::class);
            /** @var Config $apiConfig */
            $apiConfig = $this->app->make(Config::class);
            /** @var RequestFactoryInterface $requestFactory */
            $requestFactory = $this->app->make(RequestFactoryInterface::class);

            $deprecationLogger = $config->get('logging.deprecations');
            if (is_string($deprecationLogger)) {
                /** @var LogManager $logManager */
                $logManager = $this->app->make(LogManager::class);
                $deprecationLogger = new DeprecationsLogger($apiConfig, $logManager->channel($deprecationLogger), $requestFactory);
            } else {
                $deprecationLogger = null;
            }

            /** @var Client $client */
            $client = $this->app->make(Client::class);

            return new ApiClient($apiConfig, $client, $requestFactory, $deprecationLogger);
        });

        $this->app->singleton(WebhookClient::class, function (): WebhookClient {
            /** @var WebhookConfig $webhookConfig */
            $webhookConfig = $this->app->make(WebhookConfig::class);
            /** @var LaravelAdapter $configAdapter */
            $configAdapter = $this->app->make(LaravelAdapter::class);
            /** @var Client $httpClient */
            $httpClient = $this->app->make(Client::class);

            /** @var LogManager $logManager */
            $logManager = $this->app->make(LogManager::class);
            $logger = $logManager->channel($configAdapter->get('webhook.dispatch.log-channel')->string()->notEmpty()->nullable());

            /** @var Dispatcher $dispatcher */
            $dispatcher = $this->app->make(Dispatcher::class);

            return new LaravelClient($webhookConfig, $httpClient, $logger, $dispatcher, $configAdapter);
        });
        $this->app->singleton(WebhookConfig::class, \SimpleAsFuck\ApiToolkit\Service\Webhook\LaravelConfig::class);
        $this->app->singleton(\SimpleAsFuck\ApiToolkit\Service\Webhook\Repository::class, LaravelMysqlRepository::class);
        $this->mergeConfigFrom(__DIR__.'/../../config/laravel/webhook.php', 'webhook');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/laravel/webhook.php' => $this->app->configPath('webhook.php'),
        ], 'api-toolkit-config');

        $this->publishes([
            __DIR__.'/../../src/Database/Migration/LaravelMysqlWebhookTables.php' => $this->app->databasePath('migrations/2022_10_15_163600_WebhookTables.php')
        ], 'api-toolkit-migration');
    }
}
