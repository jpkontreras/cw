<?php

namespace Colame\Offer\Providers;

use Illuminate\Support\ServiceProvider;
use Colame\Offer\Contracts\OfferRepositoryInterface;
use Colame\Offer\Contracts\OfferServiceInterface;
use Colame\Offer\Contracts\OfferCalculatorInterface;
use Colame\Offer\Contracts\OfferValidatorInterface;
use Colame\Offer\Repositories\OfferRepository;
use Colame\Offer\Services\OfferService;
use Colame\Offer\Services\OfferCalculatorService;
use Colame\Offer\Services\OfferValidatorService;

class OfferServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		// Register interface bindings
		$this->app->bind(OfferRepositoryInterface::class, OfferRepository::class);
		$this->app->bind(OfferServiceInterface::class, OfferService::class);
		$this->app->bind(OfferCalculatorInterface::class, OfferCalculatorService::class);
		$this->app->bind(OfferValidatorInterface::class, OfferValidatorService::class);
	}
	
	public function boot(): void
	{
		// Load migrations
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		
		// Load API routes
		$apiRoutesPath = __DIR__ . '/../../routes/api.php';
		if (file_exists($apiRoutesPath)) {
			$this->loadRoutesFrom($apiRoutesPath);
		}
		
		// Load web routes
		$webRoutesPath = __DIR__ . '/../../routes/web.php';
		if (file_exists($webRoutesPath)) {
			$this->loadRoutesFrom($webRoutesPath);
		}
		
		// Load config
		$this->mergeConfigFrom(
			__DIR__ . '/../../config/features.php',
			'features.offer'
		);
		
		// Publish config
		$this->publishes([
			__DIR__ . '/../../config/features.php' => config_path('features/offer.php'),
		], 'offer-config');
		
		// Publish migrations
		$this->publishes([
			__DIR__ . '/../../database/migrations' => database_path('migrations'),
		], 'offer-migrations');
	}
}
