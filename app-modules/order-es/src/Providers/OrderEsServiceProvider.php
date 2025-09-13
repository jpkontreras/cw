<?php

namespace Colame\OrderEs\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Spatie\EventSourcing\Facades\Projectionist;
use Colame\OrderEs\Projectors\OrderProjector;
use Colame\OrderEs\Projectors\OrderItemProjector;
use Colame\OrderEs\Projectors\OrderStatusHistoryProjector;
use Colame\OrderEs\Projectors\OrderSessionProjector;
use Colame\OrderEs\Projectors\OrderPromotionProjector;
use Colame\OrderEs\Projectors\OrderAnalyticsProjector;
use Colame\OrderEs\ProcessManagers\OrderProcessManager;
use Colame\OrderEs\CommandHandlers\OrderCommandHandler;
use Colame\OrderEs\QueryHandlers\OrderQueryHandler;
use Colame\OrderEs\Contracts\OrderRepositoryInterface;
use Colame\OrderEs\Contracts\OrderItemRepositoryInterface;
use Colame\OrderEs\Repositories\OrderRepository;
use Colame\OrderEs\Repositories\OrderItemRepository;

class OrderEsServiceProvider extends ServiceProvider
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
		$this->loadRoutesFrom(__DIR__ . '/../../routes/order-es-routes.php');
	}
}
