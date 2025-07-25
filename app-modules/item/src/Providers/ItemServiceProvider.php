<?php

namespace Colame\Item\Providers;

use Colame\Item\Contracts\ItemRepositoryInterface;
use Colame\Item\Contracts\ItemServiceInterface;
use Colame\Item\Contracts\ItemVariantRepositoryInterface;
use Colame\Item\Contracts\ItemModifierRepositoryInterface;
use Colame\Item\Contracts\ItemPricingRepositoryInterface;
use Colame\Item\Models\Item;
use Colame\Item\Repositories\ItemRepository;
use Colame\Item\Repositories\ItemVariantRepository;
use Colame\Item\Repositories\ItemModifierRepository;
use Colame\Item\Repositories\ItemPricingRepository;
use Colame\Item\Services\ItemService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ItemServiceProvider extends ServiceProvider
{
	/**
	 * Register services
	 */
	public function register(): void
	{
		// Register repository bindings
		$this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);
		$this->app->bind(ItemVariantRepositoryInterface::class, ItemVariantRepository::class);
		$this->app->bind(ItemModifierRepositoryInterface::class, ItemModifierRepository::class);
		$this->app->bind(ItemPricingRepositoryInterface::class, ItemPricingRepository::class);
		
		// Register service bindings
		$this->app->bind(ItemServiceInterface::class, ItemService::class);
		
		// Merge config
		$this->mergeConfigFrom(
			__DIR__ . '/../../config/features.php',
			'features'
		);
	}
	
	/**
	 * Bootstrap services
	 */
	public function boot(): void
	{
		// Register route model bindings
		Route::model('item', Item::class);
		
		// Load migrations
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		
		// Load routes
		$this->loadRoutesFrom(__DIR__ . '/../../routes/item-routes.php');
		
		// Register event listeners
		$this->registerEventListeners();
		
		// Publish config
		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__ . '/../../config/features.php' => config_path('features/item.php'),
			], 'item-config');
		}
	}
	
	/**
	 * Register event listeners
	 */
	protected function registerEventListeners(): void
	{
		// Register item event listeners here when we create them
		// For example:
		// Event::listen(ItemCreated::class, [UpdateInventory::class, 'handle']);
	}
}
