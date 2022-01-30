<?php

declare(strict_types=1);

namespace SimpleAsFuck\ApiToolkit\Provider;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\RequestFactoryInterface;
use SimpleAsFuck\ApiToolkit\Service\Config\LaravelAdapter;
use SimpleAsFuck\ApiToolkit\Service\Config\Repository;

class LaravelProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Repository::class, LaravelAdapter::class);
        $this->app->singleton(RequestFactoryInterface::class, HttpFactory::class);
    }
}
