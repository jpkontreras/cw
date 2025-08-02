# CLAUDE.md

Core guidance for Claude Code when working with this Laravel/React modular application.

## Quick Links
- [Advanced Patterns](.claude/advanced-patterns.md) - Events, Error Handling, Feature Flags
- [Frontend Details](.claude/frontend-guide.md) - Component patterns, Empty states
- [Module Examples](.claude/module-examples.md) - Full implementation examples

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
# Start full development environment
composer dev

# Or run services individually:
sail artisan serve      # Laravel server (localhost:80)
npm run dev             # Vite dev server with HMR
sail artisan queue:work # Queue worker
```

### Testing & Quality
```bash
# Backend
sail artisan test       # Run Pest tests
sail artisan test --filter TestName

# Frontend
npm run lint            # ESLint
npm run format          # Prettier
npm run types           # TypeScript checking
```

### Database
```bash
sail artisan migrate              # Run migrations
sail artisan migrate:fresh --seed # Reset database
sail artisan db:seed              # Run seeders only
```

## Architecture

### Tech Stack
- **Backend**: Laravel 12.x, PHP 8.2+, SQLite/PostgreSQL, Inertia.js
- **Frontend**: React 19, TypeScript 5.7, Vite, Tailwind CSS 4.0
- **Architecture**: Interface-based modules with dependency injection

### Key Directories
```
app/
├── Core/           # Infrastructure (interfaces, traits, base classes)
├── Services/       # Business logic (shared by web/API)
├── Http/Controllers/
│   ├── Web/       # Inertia responses
│   └── Api/       # JSON responses
app-modules/        # Feature modules
├── {module}/
│   ├── src/
│   │   ├── Contracts/    # Public interfaces
│   │   ├── Data/         # DTOs
│   │   ├── Repositories/ # Interface implementations
│   │   └── Services/     # Module services
│   └── config/features.php
resources/js/
├── pages/         # Inertia page components
├── components/    # Reusable React components
│   ├── ui/       # Base UI (shadcn/ui pattern)
│   └── modules/  # Module-specific components
```

## Core Architecture Principles

### Interface-Based Module Architecture
Modules communicate through interfaces, never direct model imports. This ensures zero coupling between modules.

**Critical Rules:**
1. **No Cross-Module Model Imports** - Use interfaces only
2. **DTOs for Data Exchange** - Never pass Eloquent models between modules
3. **Repository Pattern** - Single domain focus, return DTOs
4. **Service Layer** - Orchestrate cross-module operations
5. **Strict Boundaries** - Models store foreign keys only

### Quick Pattern Reference

```php
// 1. Define Interface (in module's Contracts/)
interface ItemRepositoryInterface {
    public function find(int $id): ?ItemData;
}

// 2. Create DTO (in module's Data/)
class ItemData extends Data {
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

// 3. Implement Repository
class ItemRepository implements ItemRepositoryInterface {
    public function find(int $id): ?ItemData {
        $item = Item::find($id);
        return $item ? ItemData::from($item) : null;
    }
}

// 4. Bind in ServiceProvider
$this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);

// 5. Use via Dependency Injection
class OrderService {
    public function __construct(
        private ItemRepositoryInterface $items
    ) {}
}
```

### Service Layer Pattern

All business logic MUST live in services, shared by both Web and API controllers:

```php
// Service handles business logic
class OrderService {
    public function createOrder(array $data): OrderData { /* logic */ }
}

// Web Controller returns Inertia view
class Web\OrderController {
    public function store(Request $request, OrderService $service) {
        $order = $service->createOrder($request->validated());
        return Inertia::render('order/show', ['order' => $order]);
    }
}

// API Controller returns JSON
class Api\OrderController {
    public function store(Request $request, OrderService $service) {
        $order = $service->createOrder($request->validated());
        return response()->json($order);
    }
}
```

### Pagination & Filtering

Use the standardized pagination system with metadata:

```php
// Repository: Use ValidatesPagination trait
use ValidatesPagination;

public function paginateWithFilters($filters, $perPage) {
    $perPage = $this->validatePerPage($perPage);
    return Order::query()->paginate($perPage);
}

// Service: Return PaginatedResourceData
public function getPaginatedOrders($filters, $perPage): PaginatedResourceData {
    $paginator = $this->repository->paginateWithFilters($filters, $perPage);
    return PaginatedResourceData::fromPaginator($paginator, OrderData::class);
}

// Controller: Use HandlesPaginationBounds trait
use HandlesPaginationBounds;

public function index(Request $request) {
    $data = $this->service->getPaginatedOrders($filters, $perPage);
    
    // Handle out-of-bounds pages
    if ($redirect = $this->handleOutOfBoundsPagination($data['pagination'], $request, 'orders.index')) {
        return $redirect;
    }
    
    return Inertia::render('order/index', $data->toArray());
}
```

## Implementation Guidelines

### Creating New Modules

**MANDATORY**: Use InterNACHI/modular commands:
```bash
sail artisan make:module my-module
composer update modules/my-module
```

### Implementation Steps
1. Create module: `sail artisan make:module {name}`
2. Update autoload: `composer update modules/{name}`
3. Define interfaces in `Contracts/`
4. Create DTOs in `Data/`
5. Implement repositories
6. Bind in ServiceProvider
7. Create service with injected interfaces
8. Add Web/API controllers
9. Add routes
10. Create views in `resources/js/pages/{module}/`

### Module Structure
```
app-modules/{module}/
├── src/
│   ├── Contracts/      # Public interfaces
│   ├── Data/          # DTOs
│   ├── Repositories/  # Interface implementations
│   ├── Services/      # Business logic
│   ├── Models/        # Eloquent models (module-internal)
│   ├── Events/        # Domain events
│   ├── Exceptions/    # Module-specific exceptions
│   ├── Http/Controllers/
│   │   ├── Web/      # Inertia responses
│   │   └── Api/      # JSON responses
│   ├── Listeners/     # Event listeners
│   ├── Console/Commands/ # Artisan commands
│   └── Providers/    # Service provider bindings
├── config/
│   └── features.php   # Module feature flags
├── database/
│   ├── migrations/    # Module migrations
│   ├── factories/     # Model factories
│   └── seeders/      # Database seeders
├── routes/
│   └── {module}-routes.php # Module routes
├── resources/         # Blade views (if needed)
└── tests/            # Module tests
    ├── Feature/
    └── Unit/
```

## Frontend Organization

### Views Location
Frontend views are in `resources/js/`, NOT in module directories:

```
resources/js/
├── pages/{module}/     # Inertia pages (lowercase)
├── components/
│   ├── ui/            # Base UI (shadcn/ui)
│   └── modules/{module}/ # Module components
└── layouts/           # Layout components
```

### Key Rules
1. **Pages**: `resources/js/pages/{module}/index.tsx`
2. **Render**: `Inertia::render('module/page', $data)`
3. **Imports**: Use `@/` prefix
4. **Empty States**: Use `<EmptyState>` component for all list views

## Data Transfer Objects

Use spatie/laravel-data for validation and transformation:

```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class OrderData extends Data {
    public function __construct(
        #[Required] public readonly string $number,
        public readonly ?int $location_id,
    ) {}
}
```

DTOs handle both request validation and API responses - no separate Form Requests or Resources needed.

## Important Reminders

1. **All commands use Laravel Sail**: Every `artisan` command should be prefixed with `sail`
2. **No custom port**: The application runs on `localhost:80` or just `localhost`
3. **Module creation**: ALWAYS use `sail artisan make:module` and `composer update modules/{name}`
4. **Strict module boundaries**: Never import models across modules, use interfaces
5. **DTOs for data exchange**: Always return DTOs from repositories, not Eloquent models
6. **Service layer is mandatory**: All business logic goes in services, not controllers
7. **Feature flags for everything**: New features should be behind feature flags
8. **Test both web and API**: Every feature needs tests for both interfaces