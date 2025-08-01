# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Commands](#commands)
3. [Architecture](#architecture)
4. [Core Concepts](#core-concepts)
   - [Interface-Based Module Architecture](#interface-based-module-architecture)
   - [Module Relationship Management](#module-relationship-management)
   - [Service Layer Patterns](#service-layer-patterns)
5. [Implementation Guidelines](#implementation-guidelines)
6. [Advanced Patterns](#advanced-patterns)
   - [Event-Driven Communication](#event-driven-communication)
   - [Error Handling](#error-handling)
   - [Feature Flags](#feature-flags)
7. [Development Workflow](#development-workflow)
8. [Frontend View Organization](#frontend-view-organization)
9. [Web/API Best Practices](#webapi-best-practices)

## Project Overview

This is a **Laravel Sail-managed** web/API project with a modular architecture. All features MUST be implemented in a way that allows both web (via Inertia) and API endpoints to access the same business logic without duplication.

**Key Points:**
- Uses Laravel Sail for containerized development
- Runs on localhost:80 (or just localhost)
- Strict module boundaries with interface-based communication
- Repository pattern for domain operations
- Service layer for cross-module orchestration

## Commands

### Development
```bash
# Start full development environment (Laravel server, Vite, queue worker, logs)
composer dev

# Or run services individually:
sail artisan serve      # Laravel development server (localhost:80)
npm run dev             # Vite dev server with HMR
sail artisan queue:work # Queue worker for background jobs
```

### Build & Deployment
```bash
npm run build           # Build frontend assets for production
sail artisan optimize   # Optimize Laravel for production
```

### Testing & Quality
```bash
# Backend
sail artisan test       # Run Pest PHP tests
sail artisan test --filter TestName  # Run specific test

# Frontend
npm run lint            # Run ESLint
npm run format          # Format code with Prettier
npm run types           # TypeScript type checking
```

### Database
```bash
sail artisan migrate              # Run migrations
sail artisan migrate:fresh --seed # Reset database with seeders
sail artisan db:seed              # Run seeders only
```

## Architecture

### Tech Stack
- **Backend**: Laravel 12.x with PHP 8.2+, SQLite (dev), Inertia.js for SPA
- **Frontend**: React 19 + TypeScript 5.7, Vite, Tailwind CSS 4.0
- **UI Components**: Custom components following shadcn/ui patterns with Radix UI
- **Module Decoupling**: Interface-based architecture with dependency injection
- **Feature Flags**: Runtime feature management with granular control

### Key Directories
- `app/Core/` - Core infrastructure (feature flags, base classes, contracts)
  - `Contracts/` - Core system interfaces
    - `BaseRepositoryInterface` - Base repository with pagination support
    - `FilterableRepositoryInterface` - Advanced filtering and pagination
    - `ResourceMetadataInterface` - Resource metadata generation
    - `FeatureFlagInterface` - Feature flag management
  - `Services/` - Core services (FeatureFlagService, BaseService)
  - `Data/` - Base data transfer objects
    - `PaginatedResourceData` - Paginated response DTO
    - `ResourceMetadata` - Resource metadata configuration
    - `ColumnMetadata` - Column definition with filter support
    - `FilterMetadata` - Filter configuration
  - `Exceptions/` - Core exceptions
  - `Traits/` - Reusable traits
    - `ValidatesPagination` - Pagination validation for repositories
    - `HandlesPaginationBounds` - Out-of-bounds page handling for controllers
- `app/Http/Controllers/` - Laravel controllers handling HTTP requests
- `app/Http/Controllers/Web/` - Web-specific controllers (Inertia responses)
- `app/Http/Controllers/Api/` - API-specific controllers (JSON responses)
- `app/Services/` - Business logic services used by both web and API
- `app/Models/` - Eloquent models representing database entities
- `app/Data/` - Data Transfer Objects (using spatie/laravel-data)
- `app-modules/` - Feature modules (when using InterNACHI/modular)
  - `{module}/src/Contracts/` - Module's public interfaces
  - `{module}/src/Data/` - Module's data transfer objects
  - `{module}/src/Repositories/` - Interface implementations
  - `{module}/config/features.php` - Module feature configuration
- `resources/js/pages/` - Inertia page components (React)
- `resources/js/components/` - Reusable React components
- `resources/js/components/ui/` - Base UI components (Button, Card, etc.)
- `resources/js/layouts/` - Layout components (AppLayout, AuthLayout)
- `routes/web.php` - Web application routes
- `routes/api.php` - API routes

### Inertia.js Flow
1. Routes in `routes/web.php` point to Laravel controllers
2. Controllers return `Inertia::render('PageName', $data)`
3. Page components in `resources/js/pages/` receive props from controller
4. Use `router.visit()` or `<Link>` for navigation without full page reload

### Component Patterns
- UI components use forwardRef pattern for ref forwarding
- Components accept className prop and merge with cn() utility
- Use TypeScript interfaces for prop types
- Components are in resources/js/components/ui/ following shadcn/ui conventions

### Authentication
- Session-based auth with Laravel's built-in system
- Auth pages in `resources/js/pages/auth/`
- Protected routes use `auth` middleware
- User data available via `usePage().props.auth.user`

### Styling
- Tailwind CSS with custom configuration
- CSS variables for theming (--background, --foreground, etc.)
- Dark mode support via class-based switching
- Use cn() utility for conditional classes

### TypeScript Configuration
- Strict mode enabled
- Path alias: `@/` maps to `resources/js/`
- React JSX automatic runtime (no React import needed)

## Core Concepts

### Interface-Based Module Architecture

#### Overview
This application uses an interface-based architecture to decouple modules, allowing them to communicate through well-defined contracts rather than direct model dependencies. This approach eliminates coupling between modules while maintaining type safety and IDE support.

#### Core Principles

1. **No Direct Cross-Module Model Imports**: Modules should never directly import models from other modules
2. **Interface Contracts**: Each module exposes its functionality through interfaces
3. **Data Transfer Objects**: Use DTOs for data exchange between modules
4. **Repository Pattern**: Implement repositories that fulfill interface contracts
5. **Dependency Injection**: Laravel's container automatically injects implementations
6. **Strict Module Boundaries**: Models store only foreign keys, no cross-module relationships

### Architecture Components

#### 1. Module Contracts
Each module defines interfaces for its public API:

```php
// app-modules/item/src/Contracts/ItemRepositoryInterface.php
interface ItemRepositoryInterface 
{
    public function find(int $id): ?ItemData;
    public function checkAvailability(int $id, int $quantity): bool;
    public function getCurrentPrice(int $id, ?int $locationId): float;
}
```

#### 2. Data Transfer Objects (DTOs)
Modules exchange data using DTOs instead of Eloquent models:

```php
// app-modules/item/src/Data/ItemData.php
class ItemData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $basePrice,
        public readonly bool $isAvailable,
    ) {}
}
```

#### 3. Repository Implementation
Repositories implement the interface and handle data access:

```php
// app-modules/item/src/Repositories/ItemRepository.php
class ItemRepository implements ItemRepositoryInterface
{
    public function find(int $id): ?ItemData
    {
        $item = Item::find($id);
        return $item ? ItemData::from($item) : null;
    }
}
```

#### 4. Service Provider Bindings
Bindings are registered in module service providers:

```php
// app-modules/item/src/Providers/ItemServiceProvider.php
public function register(): void
{
    $this->app->bind(
        ItemRepositoryInterface::class,
        ItemRepository::class
    );
}
```

### Using the Architecture

#### Injecting Dependencies
Services declare dependencies through constructor injection:

```php
class OrderService
{
    public function __construct(
        private ItemRepositoryInterface $items,
        private LocationRepositoryInterface $locations,
        private OfferCalculatorInterface $offers,
    ) {}
    
    public function createOrder(array $data): Order
    {
        // Use interfaces instead of models
        $item = $this->items->find($data['item_id']);
        if (!$this->items->checkAvailability($item->id, $data['quantity'])) {
            throw new ItemNotAvailableException();
        }
    }
}
```

#### Feature Flags
The architecture includes feature flag support for granular control:

```php
if ($this->features->isEnabled('order.split_bill')) {
    // Feature-specific logic
}

if ($this->features->isEnabledForLocation('offer.happy_hour', $locationId)) {
    // Location-specific features
}
```

#### Benefits

1. **Zero Coupling**: Modules don't directly depend on each other
2. **Type Safety**: Full IDE support with interfaces and DTOs
3. **Testability**: Easy to mock interfaces for unit testing
4. **Flexibility**: Can swap implementations without changing consumers
5. **Feature Control**: Granular feature management per module/location/user

### Module Relationship Management

#### Repository Layer (Domain-Focused)
Repositories handle single-module operations exclusively:

```php
class OrderRepository implements OrderRepositoryInterface
{
    public function find(int $id): ?OrderData
    {
        $order = Order::find($id);
        return $order ? OrderData::from($order) : null;
    }
    
    public function create(array $data): OrderData
    {
        $order = Order::create($data);
        return OrderData::from($order);
    }
}
```

#### Service Layer (Cross-Module Orchestration)
Services coordinate between modules through interface dependencies:

```php
class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private UserRepositoryInterface $users,
        private LocationRepositoryInterface $locations,
    ) {}
    
    public function getOrderWithDetails(int $id): ?OrderWithRelationsData
    {
        $order = $this->orders->find($id);
        if (!$order) return null;
        
        $user = $this->users->find($order->userId);
        $location = $this->locations->find($order->locationId);
        
        return new OrderWithRelationsData($order, $user, $location);
    }
}
```

#### Query Service Layer (Performance)
For complex queries requiring joins, use dedicated query services:

```php
class OrderQueryService
{
    public function getOrdersWithRelations(): Collection
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->select(['orders.*', 'users.name as user_name', 'locations.name as location_name'])
            ->get()
            ->map(fn($row) => OrderWithRelationsData::fromRawQuery($row));
    }
}
```

#### Strict Module Boundaries

**Models Store Only Foreign Keys:**

```php
// ✅ CORRECT: Models never import cross-module dependencies
class Order extends Model
{
    protected $fillable = [
        'user_id',      // Just the ID
        'location_id',  // Just the ID
        'status',
        'total_amount',
    ];
    
    // No cross-module relationships
}
```

**Cross-Module Communication Rules:**
1. **Models**: Store foreign keys only, no cross-module imports
2. **Repositories**: Single domain focus, return DTOs exclusively
3. **Services**: Orchestrate cross-module operations via interfaces
4. **DTOs**: Immutable data containers for inter-module exchange

### Service Layer Patterns

All business logic MUST be implemented in service classes to ensure code reusability between web and API:

```php
// app/Services/UserService.php
class UserService
{
    public function createUser(array $data): User
    {
        // Business logic here
        return User::create($data);
    }
}

// Web Controller
class UserController
{
    public function store(UserData $data, UserService $service)
    {
        $user = $service->createUser($data);
        return Inertia::render('Users/Show', ['user' => $user]);
    }
}

// API Controller
class UserController
{
    public function store(UserData $data, UserService $service)
    {
        $user = $service->createUser($data);
        return response()->json($user);
    }
}
```

### Resource Pagination and Metadata

#### Overview
Resources now support full Laravel pagination with automatic filter metadata generation. This provides a standardized way to handle paginated responses with dynamic filter configuration.

#### Implementation Pattern

##### 1. Repository Implementation
Repositories must implement `FilterableRepositoryInterface` to support pagination and filtering:

```php
class OrderRepository implements OrderRepositoryInterface // extends FilterableRepositoryInterface
{
    public function paginateWithFilters(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ): LengthAwarePaginator {
        $query = Order::query();
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage, $columns, $pageName, $page);
    }
    
    public function getFilterOptions(string $field): array
    {
        switch ($field) {
            case 'status':
                return [
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'placed', 'label' => 'Placed'],
                    // ...
                ];
        }
    }
}
```

##### 2. Service Implementation
Services implement `ResourceMetadataInterface` to provide metadata:

```php
class OrderService implements OrderServiceInterface, ResourceMetadataInterface
{
    public function getPaginatedOrders(array $filters, int $perPage = 20): PaginatedResourceData
    {
        $paginator = $this->orderRepository->paginateWithFilters($filters, $perPage);
        $metadata = $this->getResourceMetadata()->toArray();
        
        return PaginatedResourceData::fromPaginator(
            $paginator,
            OrderData::class,
            $metadata
        );
    }
    
    public function getResourceMetadata(array $context = []): ResourceMetadata
    {
        $columns = [];
        
        // Define columns with filter configuration
        $columns['status'] = ColumnMetadata::enum('status', 'Status', $this->orderRepository->getFilterOptions('status'))
            ->withFilter(FilterMetadata::multiSelect(
                'status',
                'Status',
                $this->orderRepository->getFilterOptions('status'),
                'Filter by status',
                3
            ));
        
        return new ResourceMetadata(
            columns: collect($columns),
            defaultFilters: ['search', 'status', 'type', 'location_id', 'date'],
            defaultSort: '-created_at',
            filterPresets: $this->getFilterPresets(),
        );
    }
}
```

##### 3. Controller Response
Controllers pass the structured response to the frontend:

```php
// Web Controller
public function index(Request $request): Response
{
    $filters = $request->only(['status', 'type', 'location_id', 'date', 'search', 'sort', 'page']);
    $perPage = $request->input('per_page', 20);
    
    $paginatedData = $this->orderService->getPaginatedOrders($filters, $perPage);
    $responseData = $paginatedData->toArray();
    
    return Inertia::render('order/index', [
        'orders' => $responseData['data'],
        'pagination' => $responseData['pagination'],
        'metadata' => $responseData['metadata'],
    ]);
}

// API Controller (JSON:API compliant)
public function index(Request $request): JsonResponse
{
    $paginatedData = $this->orderService->getPaginatedOrders($filters, $perPage);
    $responseData = $paginatedData->toArray();
    
    return response()->json([
        'data' => $responseData['data'],
        'meta' => array_merge(
            $responseData['pagination'],
            ['resource' => $responseData['metadata']]
        ),
        'links' => [
            'self' => request()->fullUrl(),
            'first' => $responseData['pagination']['first_page_url'],
            // ...
        ],
    ]);
}
```

##### 4. Frontend Usage
Frontend components consume metadata to auto-configure filters:

```tsx
import { useResourceMetadata } from '@/hooks/use-resource-metadata';

function OrderDataTable({ orders, pagination, metadata }) {
  const { filters: metadataFilters } = useResourceMetadata(metadata);
  
  // Filters are automatically configured from server metadata
  return (
    <InertiaDataTable
      columns={columns}
      data={orders}
      pagination={pagination}
      filters={metadataFilters}
    />
  );
}
```

#### Response Structure

```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200,
    "from": 1,
    "to": 20,
    "path": "/orders",
    "first_page_url": "/orders?page=1",
    "last_page_url": "/orders?page=10",
    "next_page_url": "/orders?page=2",
    "prev_page_url": null,
    "links": [...]
  },
  "metadata": {
    "columns": {
      "status": {
        "key": "status",
        "label": "Status",
        "type": "enum",
        "sortable": true,
        "filter": {
          "key": "status",
          "filterType": "multi-select",
          "options": [...]
        }
      }
    },
    "filters": [...],
    "defaultFilters": ["search", "status", "type"],
    "filterPresets": [...]
  }
}
```

#### Benefits

1. **Automatic Filter Generation**: Filters are configured from server metadata
2. **Type Safety**: Full TypeScript support with generated types
3. **Consistency**: All resources follow the same pattern
4. **Performance**: Optimized queries with proper pagination
5. **Flexibility**: Easy to add new filter types or metadata

### Pagination Configuration

#### Overview
Pagination configuration is centralized through the ResourceMetadata system, ensuring consistency between backend validation and frontend options.

#### Implementation Pattern

1. **Repository Level**: Use the `ValidatesPagination` trait in repositories
```php
use App\Core\Traits\ValidatesPagination;

class OrderRepository implements OrderRepositoryInterface
{
    use ValidatesPagination;
    
    public function paginateWithFilters(...): LengthAwarePaginator
    {
        // Validate perPage parameter automatically
        $perPage = $this->validatePerPage($perPage);
        // ... rest of implementation
    }
}
```

2. **Metadata Configuration**: ResourceMetadata includes pagination options
```php
return new ResourceMetadata(
    columns: collect($columns),
    defaultFilters: ['search', 'status'],
    perPageOptions: [10, 20, 50, 100], // Centralized options
    defaultPerPage: 20,
);
```

3. **Controller Simplicity**: Controllers just cast to int
```php
$perPage = (int) $request->input('per_page', 20);
// No validation needed - handled by repository
```

4. **Frontend Integration**: Components receive options from metadata
```tsx
<DataTablePagination
    pagination={pagination}
    perPageOptions={metadata.perPageOptions} // From server
/>
```

5. **Out-of-bounds Page Handling**: Use `HandlesPaginationBounds` trait
```php
use App\Core\Traits\HandlesPaginationBounds;

class OrderController extends Controller
{
    use HandlesPaginationBounds;
    
    public function index(Request $request)
    {
        $paginatedData = $this->orderService->getPaginatedOrders($filters, $perPage);
        $responseData = $paginatedData->toArray();
        
        // Web Controller - Redirects to page 1
        if ($redirect = $this->handleOutOfBoundsPagination($responseData['pagination'], $request, 'orders.index')) {
            return $redirect;
        }
        
        // API Controller - Returns 404 with helpful info
        if ($errorResponse = $this->handleOutOfBoundsPaginationApi($responseData['pagination'])) {
            return $errorResponse;
        }
    }
}
```

#### Benefits of This Approach

1. **Single Source of Truth**: Pagination options defined once in ResourceMetadata
2. **Repository Validation**: ValidatesPagination trait ensures consistency
3. **No Config Files**: Follows interface-based architecture principles
4. **Flexible per Resource**: Each resource can have different options if needed
5. **Frontend Sync**: Options automatically flow to frontend via metadata

## Advanced Patterns

### Event-Driven Communication

#### Overview
Modules communicate asynchronously through domain events, maintaining loose coupling while enabling reactive behavior across the system.

#### Event Architecture

##### 1. Event Contracts
Events implement interfaces for consistent structure:

```php
// app-modules/order/src/Contracts/OrderEventInterface.php
interface OrderEventInterface
{
    public function getOrderId(): int;
    public function getOrderData(): array;
    public function getEventType(): string;
    public function getItemIds(): array;
    public function getLocationId(): ?int;
}
```

##### 2. Domain Events
Each module defines its own events:

```php
// app-modules/order/src/Events/OrderCreated.php
class OrderCreated implements OrderEventInterface
{
    public function __construct(private Order $order) {}
    
    public function getOrderData(): array
    {
        return [
            'id' => $this->order->id,
            'total' => $this->order->total_amount,
            'location_id' => $this->order->location_id,
            // Only expose what other modules need
        ];
    }
}
```

##### 3. Event Listeners
Other modules react to events without direct dependencies:

```php
// app-modules/inventory/src/Listeners/UpdateInventoryMetrics.php
class UpdateInventoryMetrics
{
    public function handle(OrderCreated|OrderCancelled $event): void
    {
        $itemIds = $event->getItemIds();
        // React to order without knowing Order model internals
    }
}
```

### Error Handling

#### Module-Specific Exceptions
Each module defines its own exceptions:

```php
// app-modules/item/src/Exceptions/InsufficientStockException.php
class InsufficientStockException extends Exception
{
    public function __construct(
        string $itemName, 
        int $requested, 
        int $available
    ) {
        $message = "Insufficient stock for {$itemName}. " .
                  "Requested: {$requested}, Available: {$available}";
        parent::__construct($message);
    }
}
```

#### Exception Transformation
Services transform domain exceptions to their context:

```php
try {
    $this->items->decrementStock($itemId, $quantity);
} catch (InsufficientStockException $e) {
    throw new OrderException("Cannot add item: " . $e->getMessage());
}
```

#### Controller Error Handling
Controllers handle exceptions appropriately:

```php
try {
    $order = $this->orderService->createOrder($data);
    return response()->json(['order' => $order], 201);
} catch (OrderException $e) {
    return response()->json(['error' => $e->getMessage()], 422);
} catch (\Exception $e) {
    Log::error('Order creation failed', ['error' => $e]);
    return response()->json(['error' => 'Server error'], 500);
}
```

#### Error Handling Best Practices

1. **Error Tracing**: Include correlation IDs for cross-module operations
2. **Logging Standards**: Log at module boundaries with context
3. **Debug Tooling**: Use Laravel Telescope for development debugging
4. **Error Monitoring**: Implement error tracking (e.g., Sentry) for production
5. **Graceful Degradation**: Handle failures without breaking the entire flow

## Feature Flags

### Configuration
Each module defines its features in `config/features.php`:

```php
return [
    'order' => [
        'split_bill' => [
            'enabled' => env('FEATURE_ORDER_SPLIT_BILL', true),
            'description' => 'Allow splitting bills',
            'rollout' => [
                'type' => 'percentage',
                'value' => 50, // 50% rollout
            ],
        ],
    ],
];
```

### Usage in Code

```php
// Simple check
if ($this->features->isEnabled('order.split_bill')) {
    // Feature logic
}

// Location-specific
if ($this->features->isEnabledForLocation('order.split_bill', $locationId)) {
    // Location-specific logic
}

// User-specific with context
$context = ['user_id' => $userId, 'location_id' => $locationId];
if ($this->features->isEnabled('order.advanced_features', $context)) {
    // Advanced features
}

// A/B testing with variants
$variant = $this->features->getVariant('ui.new_checkout', $context);
switch ($variant) {
    case 'variant_a':
        // New checkout flow
        break;
    default:
        // Original flow
}
```

### Rollout Strategies

1. **Percentage**: Random percentage of users
2. **Locations**: Specific location whitelist
3. **Users**: Specific user whitelist  
4. **Gradual**: Time-based rollout
5. **Dependencies**: Features that require other features

### Feature Flag Implementation

#### Testing Strategies

```php
// Test with feature enabled
$this->features->enable('order.split_bill');
$response = $this->post('/api/orders', $data);
$response->assertOk();

// Test with feature disabled
$this->features->disable('order.split_bill');
$response = $this->post('/api/orders', $data);
$response->assertStatus(422);
```

#### Rollback Procedures

1. **Monitoring**: Watch error rates and performance metrics
2. **Quick Disable**: Features can be disabled via environment variables
3. **Database Flags**: Override flags in database for instant changes
4. **Circuit Breaker**: Auto-disable features on high error rates

#### Performance Monitoring

```php
// Track feature flag performance
class FeatureFlagMiddleware
{
    public function handle($request, $next)
    {
        $start = microtime(true);
        $response = $next($request);
        
        if ($this->features->isEnabled('performance.tracking')) {
            $duration = microtime(true) - $start;
            Log::info('Request duration', [
                'feature' => $this->features->getCurrentFeature(),
                'duration' => $duration,
            ]);
        }
        
        return $response;
    }
}
```

### Example Implementation
```php
// Module Contract
interface UserRepositoryInterface
{
    public function find(int $id): ?UserData;
    public function create(array $data): UserData;
}

// Data Transfer Object
class UserData extends BaseData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// Repository Implementation
class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): UserData
    {
        $user = User::create($data);
        return UserData::from($user);
    }
}

// Service using interfaces
class UserService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private FeatureFlagInterface $features,
    ) {}
    
    public function create(array $data): UserData
    {
        // Complex business logic here
        $user = $this->users->create($data);
        
        if ($this->features->isEnabled('user.welcome_email')) {
            event(new UserCreated($user));
        }
        
        return $user;
    }
}

// Service Provider binding
class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
    }
}

// Controllers remain the same, using the service
```

## Development Workflow

## Implementation Guidelines

### Creating New Modules

**IMPORTANT**: When creating new modules, you MUST use the InterNACHI/modular package commands:

```bash
# Create a new module
sail artisan make:module my-module

# After creation, update composer autoload
composer update modules/my-module
```

These commands are MANDATORY for proper module setup. Never create modules manually.

### Adding New Features (Modular Approach)
1. Create the module using `sail artisan make:module {module-name}`
2. Run `composer update modules/{module-name}` to register autoloading
3. Define module contracts in `Contracts/` directory
4. Create DTOs in `Data/` directory for external data exchange
5. Implement repository fulfilling the contract
6. Register interface binding in service provider
7. Create service class using injected interfaces (not models)
8. Create web controller extending service logic for Inertia responses
9. Create API controller extending service logic for JSON responses
10. Use spatie/laravel-data DTOs for validation and transformation
11. Create routes in both `routes/web.php` and `routes/api.php` (or module routes)
12. DTOs handle both validation and API response formatting
13. Create page component in `resources/js/pages/` for web UI
14. Add feature flags in `config/features.php`
15. Run migrations if database changes needed

### Module Structure (using InterNACHI/modular)
When creating feature modules, follow this structure:
```
app-modules/
  user-management/
    src/
      Contracts/
        UserRepositoryInterface.php  # Module's public API
      Data/
        UserData.php                 # DTO for external consumption
      Repositories/
        UserRepository.php           # Interface implementation
      Http/
        Controllers/
          Web/UserController.php      # Inertia responses
          Api/UserController.php      # JSON responses
      Services/
        UserService.php              # Business logic
      Models/
        User.php                     # Internal model
      Providers/
        UserServiceProvider.php      # Interface bindings
    config/
      features.php                  # Module feature flags
    routes/
      web.php                       # Web routes
      api.php                       # API routes
    tests/
      Feature/
        Web/UserTest.php
        Api/UserApiTest.php
      Unit/
        UserRepositoryTest.php
```

**Note**: Frontend views (React/TSX components) are NOT stored in module directories. See [Frontend View Organization](#frontend-view-organization) section for view placement guidelines.

### Form Handling
- Use Inertia's `useForm` hook for form state
- Handle validation errors from Laravel
- Server-side validation through spatie/laravel-data DTOs

### Real-time Features (Planned)
- Will use Laravel Reverb for WebSockets
- Laravel Echo for frontend integration
- Redis for pub/sub and caching

## Frontend View Organization

### View Location Standards
All frontend views (React/TSX components) MUST be located in the main `resources/` directory, NOT within module directories. This maintains strict separation between server-side module code and frontend presentation.

### Directory Structure
```
resources/
├── js/
│   ├── pages/              # Inertia page components
│   │   ├── auth/          # Authentication pages
│   │   ├── onboarding/    # Onboarding flow
│   │   ├── {module}/      # Module-specific pages (lowercase)
│   │   └── ...
│   ├── components/        # Reusable components
│   │   ├── ui/           # Base UI components (shadcn/ui pattern)
│   │   ├── modules/      # Module-specific components
│   │   │   └── {module}/ # Components for specific module
│   │   └── ...          # Shared app components
│   ├── layouts/          # Layout components
│   │   ├── modules/      # Module-specific layouts
│   │   └── ...          # Shared layouts
│   ├── hooks/            # Custom React hooks
│   └── types/            # TypeScript type definitions
└── views/                # Blade templates (minimal use)
```

### Module View Guidelines

1. **Page Components**: Place in `resources/js/pages/{module}/`
   - Use lowercase module names for directories
   - Follow Inertia naming conventions (PascalCase for files)
   - Example: `resources/js/pages/order/index.tsx`

2. **Module Components**: Place in `resources/js/components/modules/{module}/`
   - Components specific to a module but not full pages
   - Example: `resources/js/components/modules/order/order-card.tsx`

3. **Shared Components**: Place in `resources/js/components/`
   - Components used across multiple modules
   - UI components follow shadcn/ui patterns in `components/ui/`

4. **Import Conventions**:
   - Use absolute imports with `@/` prefix
   - Module components: `@/components/modules/{module}/component-name`
   - Pages: Not typically imported directly (Inertia handles)

5. **Controller References**:
   ```php
   // Correct - references centralized view
   return Inertia::render('order/index', $data);
   
   // Incorrect - references module directory
   return Inertia::render('Order/Index', $data);
   ```

### Why This Structure?

1. **Separation of Concerns**: Frontend code is clearly separated from backend logic
2. **Build Optimization**: Vite can better optimize when all frontend assets are centralized
3. **Consistency**: All views follow the same organizational pattern
4. **Maintainability**: Easier to find and manage view files
5. **Module Independence**: Modules remain focused on business logic only

### Empty State Implementation

#### Overview
All list views and data-driven pages MUST implement proper empty states using the standardized `EmptyState` component. This provides a consistent user experience when no data is available and prevents showing irrelevant UI elements like stats cards or action buttons.

#### EmptyState Component
Located at `resources/js/components/empty-state.tsx`, this component provides:
- Icon display with decorative background
- Title and description text
- Optional action buttons
- Optional help text with links

#### Implementation Pattern

1. **Check for Empty Data**:
   ```tsx
   // At the top of your component
   const isEmpty = data.length === 0;
   ```

2. **Conditionally Render Header Actions**:
   ```tsx
   <Page.Header
     title="Page Title"
     subtitle="Page description"
     actions={
       !isEmpty && (
         <Page.Actions>
           {/* Action buttons only shown when data exists */}
         </Page.Actions>
       )
     }
   />
   ```

3. **Conditionally Render Content**:
   ```tsx
   <Page.Content>
     {isEmpty ? (
       <EmptyState
         icon={IconComponent}
         title="No data yet"
         description="Helpful description about what this page will show"
         actions={
           <Button onClick={() => router.visit('/create-route')}>
             <Plus className="mr-2 h-4 w-4" />
             Create First Item
           </Button>
         }
         helpText={
           <>
             Learn more about <a href="#" className="text-primary hover:underline">this feature</a>
           </>
         }
       />
     ) : (
       <>
         {/* Stats cards */}
         {/* Data table or content */}
       </>
     )}
   </Page.Content>
   ```

#### Required Empty States
All pages displaying lists or collections MUST implement empty states:
- **List Views**: Orders, Items, Inventory, Pricing Rules, Modifiers, Recipes, etc.
- **Dashboard Views**: When no data exists for charts or metrics
- **Search Results**: When no results match the search criteria
- **Filtered Views**: When filters result in no matching items

#### Empty State Guidelines

1. **Icons**: Use appropriate Lucide icons that match the content type
2. **Title**: Clear, concise title describing what's missing
3. **Description**: Helpful text explaining what the user can do
4. **Actions**: Primary action button to create or add the first item
5. **Help Text**: Links to documentation or guides (optional)

#### Example Implementation
```tsx
// inventory/index.tsx
const isEmpty = inventory.length === 0;

return (
  <PageLayout>
    <PageLayout.Header
      title="Inventory Management"
      subtitle="Track and manage stock levels"
      actions={
        !isEmpty && (
          <PageLayout.Actions>
            <Button>Stock Take</Button>
            <Button>New Adjustment</Button>
          </PageLayout.Actions>
        )
      }
    />
    
    <PageLayout.Content>
      {isEmpty ? (
        <EmptyState
          icon={Package}
          title="No inventory tracked yet"
          description="Start tracking inventory for your items to monitor stock levels."
          actions={
            <Button onClick={() => router.visit('/items')}>
              <Box className="mr-2 h-4 w-4" />
              Go to Items
            </Button>
          }
          helpText={
            <>
              Learn about <a href="#" className="text-primary hover:underline">inventory management</a>
            </>
          }
        />
      ) : (
        <>
          {/* Stats cards and data table */}
        </>
      )}
    </PageLayout.Content>
  </PageLayout>
);
```

#### Benefits
1. **Consistency**: All empty states look and behave the same way
2. **Better UX**: Users understand what to do when no data exists
3. **Cleaner UI**: Hides irrelevant elements like zero-value stats
4. **Guided Actions**: Directs users to create their first item
5. **Professional**: Polished appearance even with no data

## Web/API Best Practices

### Controller Separation
- **Web Controllers**: Return Inertia views, handle session-based auth, manage UI state
- **API Controllers**: Return JSON responses, use token-based auth, follow REST conventions
- Both controller types MUST delegate business logic to services

### Shared Components
1. **Data Transfer Objects**: Use spatie/laravel-data for validation and transformation
2. **Services**: All business logic lives here, used by both controller types
3. **Models**: Shared data layer
4. **Policies**: Authorization rules work for both web and API

### API Design Principles
- Use spatie/laravel-data for both request validation and API response transformation
- Version your API routes (`/api/v1/`)
- Follow RESTful conventions
- Return consistent error responses
- Use appropriate HTTP status codes

### Data Layer with Spatie Laravel-Data
The application uses the spatie/laravel-data package to unify request validation and API resource transformation:

```php
// Single DTO handles both validation and transformation
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Email;

class UserData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $name,
        
        #[Required, Email]
        public readonly string $email,
        
        public readonly ?string $phone = null,
    ) {}
    
    // Custom transformation logic if needed
    public function with(): array
    {
        return [
            'created_at' => now(),
        ];
    }
}
```

This approach eliminates the need for separate Form Request classes and API Resource classes:
- **Request Validation**: DTOs automatically validate incoming data
- **Response Transformation**: Same DTOs handle API response formatting
- **Type Safety**: Full TypeScript support via TypeScript transformer
- **Consistency**: Single source of truth for data structure

### Code Organization Rules
1. **Never duplicate business logic** - Always use services
2. **Controllers should be thin** - Only handle HTTP concerns
3. **Services are framework-agnostic** - Don't use HTTP-specific code in services
4. **Use dependency injection** - For better testability
5. **Share validation rules** - Through spatie/laravel-data DTOs
6. **Use interfaces for dependencies** - Never directly use models from other modules
7. **Return DTOs from repositories** - Not Eloquent models
8. **Check feature flags** - For conditional functionality

## Important Reminders

1. **All commands use Laravel Sail**: Every `artisan` command should be prefixed with `sail`
2. **No custom port**: The application runs on `localhost:80` or just `localhost`
3. **Module creation**: ALWAYS use `sail artisan make:module` and `composer update modules/{name}`
4. **Strict module boundaries**: Never import models across modules, use interfaces
5. **DTOs for data exchange**: Always return DTOs from repositories, not Eloquent models
6. **Service layer is mandatory**: All business logic goes in services, not controllers
7. **Feature flags for everything**: New features should be behind feature flags
8. **Test both web and API**: Every feature needs tests for both interfaces