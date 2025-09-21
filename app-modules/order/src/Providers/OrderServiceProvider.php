<?php

namespace Colame\Order\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Spatie\EventSourcing\Facades\Projectionist;
use Colame\Order\Projectors\OrderProjector;
use Colame\Order\Projectors\OrderItemProjector;
use Colame\Order\Projectors\OrderStatusHistoryProjector;
use Colame\Order\Projectors\OrderSessionProjector;
use Colame\Order\Projectors\OrderPromotionProjector;
use Colame\Order\Projectors\OrderAnalyticsProjector;
use Colame\Order\ProcessManagers\OrderProcessManager;
use Colame\Order\CommandHandlers\OrderCommandHandler;
use Colame\Order\QueryHandlers\OrderQueryHandler;
use Colame\Order\Contracts\OrderRepositoryInterface;
use Colame\Order\Contracts\OrderItemRepositoryInterface;
use Colame\Order\Repositories\OrderRepository;
use Colame\Order\Repositories\OrderItemRepository;

class OrderServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		// Register repository bindings
		$this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
		$this->app->bind(OrderItemRepositoryInterface::class, OrderItemRepository::class);
		
		// Register command and query handlers as singletons
		$this->app->singleton(OrderCommandHandler::class);
		$this->app->singleton(OrderQueryHandler::class);
		
		// Register process manager
		$this->app->singleton(OrderProcessManager::class);
	}
	
	public function boot(): void
	{
		// Register all projectors for event sourcing
		Projectionist::addProjector(OrderProjector::class);
		Projectionist::addProjector(OrderItemProjector::class);
		Projectionist::addProjector(OrderStatusHistoryProjector::class);
		Projectionist::addProjector(OrderSessionProjector::class);
		Projectionist::addProjector(OrderPromotionProjector::class);
		Projectionist::addProjector(OrderAnalyticsProjector::class);
		
		// Register process manager
		Projectionist::addProjector(OrderProcessManager::class);
		
		// Load migrations
		$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
		
		// Load routes
		$this->loadRoutesFrom(__DIR__ . '/../../routes/order-routes.php');
		
		// Register console commands
		if ($this->app->runningInConsole()) {
			$this->commands([
				\Colame\Order\Console\Commands\FixOrderSessionIds::class,
				\Colame\Order\Console\Commands\SyncCustomerDataCommand::class,
			]);
		}
	}
}
