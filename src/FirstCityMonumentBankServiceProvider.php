<?php

namespace GloCurrency\FirstCityMonumentBank;

use Illuminate\Support\ServiceProvider;
use GloCurrency\FirstCityMonumentBank\Console\FetchTransactionsUpdateCommand;
use GloCurrency\FirstCityMonumentBank\Config;
use BrokeYourBike\FirstCityMonumentBank\Interfaces\ConfigInterface;

class FirstCityMonumentBankServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMigrations();
        $this->registerPublishing();
        $this->registerCommands();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();
        $this->bindConfig();
    }

    /**
     * Setup the configuration for FirstCityMonumentBank.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/first_city_monument_bank.php', 'services.first_city_monument_bank'
        );
    }

    /**
     * Bind the FirstCityMonumentBank logger interface to the FirstCityMonumentBank logger.
     *
     * @return void
     */
    protected function bindConfig()
    {
        $this->app->bind(ConfigInterface::class, Config::class);
    }

    /**
     * Register the package migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (FirstCityMonumentBank::$runsMigrations && $this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/first_city_monument_bank.php' => $this->app->configPath('first_city_monument_bank.php'),
            ], 'first-city-monument-bank-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'first-city-monument-bank-migrations');
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchTransactionsUpdateCommand::class,
            ]);
        }
    }
}
