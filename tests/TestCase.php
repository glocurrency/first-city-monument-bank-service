<?php

namespace GloCurrency\FirstCityMonumentBank\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use GloCurrency\FirstCityMonumentBank\Tests\Fixtures\TransactionFixture;
use GloCurrency\FirstCityMonumentBank\Tests\Fixtures\ProcessingItemFixture;
use GloCurrency\FirstCityMonumentBank\FirstCityMonumentBankServiceProvider;
use GloCurrency\FirstCityMonumentBank\FirstCityMonumentBank;

abstract class TestCase extends OrchestraTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        FirstCityMonumentBank::useTransactionModel(TransactionFixture::class);
        FirstCityMonumentBank::useProcessingItemModel(ProcessingItemFixture::class);
    }

    protected function getPackageProviders($app)
    {
        return [FirstCityMonumentBankServiceProvider::class];
    }

    /**
     * Create the HTTP mock for API.
     *
     * @return array<\GuzzleHttp\Handler\MockHandler|\GuzzleHttp\HandlerStack> [$httpMock, $handlerStack]
     */
    protected function mockApiFor(string $class): array
    {
        $httpMock = new \GuzzleHttp\Handler\MockHandler();
        $handlerStack = \GuzzleHttp\HandlerStack::create($httpMock);

        $this->app->when($class)
            ->needs(\GuzzleHttp\ClientInterface::class)
            ->give(function () use ($handlerStack) {
                return new \GuzzleHttp\Client(['handler' => $handlerStack]);
            });

        return [$httpMock, $handlerStack];
    }
}
