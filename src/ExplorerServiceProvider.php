<?php

declare(strict_types=1);

namespace JeroenG\Explorer;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use JeroenG\Explorer\Application\DocumentAdapterInterface;
use JeroenG\Explorer\Application\IndexAdapterInterface;
use JeroenG\Explorer\Application\IndexChangedCheckerInterface;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationRepositoryInterface;
use JeroenG\Explorer\Infrastructure\Console\ElasticSearch;
use JeroenG\Explorer\Infrastructure\Console\ElasticUpdate;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticAdapter;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticClientFactory;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticClientBuilder;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticDocumentAdapter;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticIndexAdapter;
use JeroenG\Explorer\Infrastructure\IndexManagement\ElasticIndexChangedChecker;
use JeroenG\Explorer\Infrastructure\IndexManagement\ElasticIndexConfigurationRepository;
use JeroenG\Explorer\Infrastructure\Scout\Builder;
use JeroenG\Explorer\Infrastructure\Scout\ElasticEngine;
use Laravel\Scout\EngineManager;

class ExplorerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->app->bind(ElasticClientFactory::class, function () {
            return new ElasticClientFactory(ElasticClientBuilder::fromConfig(config())->build());
        });

        $this->app->bind(IndexAdapterInterface::class, ElasticIndexAdapter::class);
        $this->app->bind(DocumentAdapterInterface::class, ElasticDocumentAdapter::class);
        $this->app->bind(IndexChangedCheckerInterface::class, ElasticIndexChangedChecker::class);

        $this->app->bind(IndexConfigurationRepositoryInterface::class, function () {
            return new ElasticIndexConfigurationRepository(
                config('explorer.indexes') ?? [],
                config('explorer.prune_old_aliases'),
            );
        });

        $this->app->bind(\Laravel\Scout\Builder::class, Builder::class);

        resolve(EngineManager::class)->extend('elastic', function (Application $app) {
            return new ElasticEngine(
                $app->make(IndexAdapterInterface::class),
                $app->make(DocumentAdapterInterface::class),
                $app->make(IndexConfigurationRepositoryInterface::class)
            );
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/explorer.php', 'explorer');
    }

    public function provides(): array
    {
        return ['explorer'];
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__ . '/../config/explorer.php' => config_path('explorer.php'),
        ], 'explorer.config');

        $this->commands([
             ElasticSearch::class,
             ElasticUpdate::class,
         ]);
    }
}
