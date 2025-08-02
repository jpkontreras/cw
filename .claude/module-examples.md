# Module Implementation Examples

## Complete Module Example: Item Management

### 1. Define the Contract
```php
// app-modules/item/src/Contracts/ItemRepositoryInterface.php
namespace Modules\Item\Contracts;

use Modules\Item\Data\ItemData;
use Illuminate\Pagination\LengthAwarePaginator;

interface ItemRepositoryInterface
{
    public function find(int $id): ?ItemData;
    public function findByBarcode(string $barcode): ?ItemData;
    public function create(array $data): ItemData;
    public function update(int $id, array $data): ItemData;
    public function delete(int $id): bool;
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;
    public function checkAvailability(int $id, int $quantity): bool;
    public function getCurrentPrice(int $id, ?int $locationId = null): float;
    public function getFilterOptions(string $field): array;
}
```

### 2. Create Data Transfer Objects
```php
// app-modules/item/src/Data/ItemData.php
namespace Modules\Item\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Min;

class ItemData extends Data
{
    public function __construct(
        public readonly ?int $id,
        
        #[Required]
        public readonly string $name,
        
        public readonly ?string $description,
        
        #[Required]
        public readonly string $sku,
        
        public readonly ?string $barcode,
        
        #[Required, Numeric, Min(0)]
        public readonly float $price,
        
        #[Required, Numeric, Min(0)]
        public readonly float $cost,
        
        public readonly bool $is_active = true,
        
        public readonly bool $track_inventory = false,
        
        public readonly ?array $category_ids = [],
        
        public readonly ?string $created_at = null,
        
        public readonly ?string $updated_at = null,
    ) {}
}
```

### 3. Implement the Repository
```php
// app-modules/item/src/Repositories/ItemRepository.php
namespace Modules\Item\Repositories;

use App\Core\Traits\ValidatesPagination;
use Modules\Item\Contracts\ItemRepositoryInterface;
use Modules\Item\Data\ItemData;
use Modules\Item\Models\Item;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemRepository implements ItemRepositoryInterface
{
    use ValidatesPagination;
    
    public function find(int $id): ?ItemData
    {
        $item = Item::find($id);
        return $item ? ItemData::from($item) : null;
    }
    
    public function findByBarcode(string $barcode): ?ItemData
    {
        $item = Item::where('barcode', $barcode)->first();
        return $item ? ItemData::from($item) : null;
    }
    
    public function create(array $data): ItemData
    {
        $item = Item::create($data);
        
        if (isset($data['category_ids'])) {
            $item->categories()->sync($data['category_ids']);
        }
        
        return ItemData::from($item);
    }
    
    public function update(int $id, array $data): ItemData
    {
        $item = Item::findOrFail($id);
        $item->update($data);
        
        if (isset($data['category_ids'])) {
            $item->categories()->sync($data['category_ids']);
        }
        
        return ItemData::from($item);
    }
    
    public function delete(int $id): bool
    {
        return Item::destroy($id) > 0;
    }
    
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        $perPage = $this->validatePerPage($perPage);
        $query = Item::query();
        
        // Search filter
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('barcode', $filters['search']);
            });
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        
        // Status filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        // Sorting
        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $query->orderBy($column, $direction);
        
        return $query->paginate($perPage);
    }
    
    public function checkAvailability(int $id, int $quantity): bool
    {
        $item = Item::find($id);
        
        if (!$item || !$item->track_inventory) {
            return true;
        }
        
        return $item->current_stock >= $quantity;
    }
    
    public function getCurrentPrice(int $id, ?int $locationId = null): float
    {
        $item = Item::find($id);
        
        if (!$item) {
            return 0;
        }
        
        // Check for location-specific pricing
        if ($locationId) {
            $locationPrice = $item->locationPrices()
                ->where('location_id', $locationId)
                ->first();
            
            if ($locationPrice) {
                return $locationPrice->price;
            }
        }
        
        return $item->price;
    }
    
    public function getFilterOptions(string $field): array
    {
        switch ($field) {
            case 'category':
                return Category::orderBy('name')
                    ->get()
                    ->map(fn($cat) => [
                        'value' => $cat->id,
                        'label' => $cat->name,
                    ])
                    ->toArray();
                    
            case 'status':
                return [
                    ['value' => '1', 'label' => 'Active'],
                    ['value' => '0', 'label' => 'Inactive'],
                ];
                
            default:
                return [];
        }
    }
}
```

### 4. Create the Service
```php
// app-modules/item/src/Services/ItemService.php
namespace Modules\Item\Services;

use App\Core\Contracts\ResourceMetadataInterface;
use App\Core\Data\PaginatedResourceData;
use App\Core\Data\ResourceMetadata;
use App\Core\Data\ColumnMetadata;
use App\Core\Data\FilterMetadata;
use Modules\Item\Contracts\ItemRepositoryInterface;
use Modules\Item\Data\ItemData;
use Modules\Item\Exceptions\InsufficientStockException;
use Modules\Inventory\Contracts\InventoryServiceInterface;
use App\Core\Contracts\FeatureFlagInterface;

class ItemService implements ItemServiceInterface, ResourceMetadataInterface
{
    public function __construct(
        private ItemRepositoryInterface $repository,
        private InventoryServiceInterface $inventory,
        private FeatureFlagInterface $features,
    ) {}
    
    public function createItem(array $data): ItemData
    {
        // Business logic validation
        if ($this->repository->findByBarcode($data['barcode'] ?? '')) {
            throw new \Exception('Barcode already exists');
        }
        
        $item = $this->repository->create($data);
        
        // Initialize inventory if tracking is enabled
        if ($item->track_inventory && $this->features->isEnabled('inventory.auto_initialize')) {
            $this->inventory->initializeItemStock($item->id);
        }
        
        return $item;
    }
    
    public function updateItem(int $id, array $data): ItemData
    {
        $existingItem = $this->repository->find($id);
        
        if (!$existingItem) {
            throw new \Exception('Item not found');
        }
        
        // Check if barcode is being changed and already exists
        if (isset($data['barcode']) && $data['barcode'] !== $existingItem->barcode) {
            if ($this->repository->findByBarcode($data['barcode'])) {
                throw new \Exception('Barcode already exists');
            }
        }
        
        return $this->repository->update($id, $data);
    }
    
    public function deleteItem(int $id): bool
    {
        // Check if item is used in active orders
        if ($this->hasActiveOrders($id)) {
            throw new \Exception('Cannot delete item with active orders');
        }
        
        return $this->repository->delete($id);
    }
    
    public function getPaginatedItems(array $filters, int $perPage = 20): PaginatedResourceData
    {
        $paginator = $this->repository->paginateWithFilters($filters, $perPage);
        $metadata = $this->getResourceMetadata()->toArray();
        
        return PaginatedResourceData::fromPaginator(
            $paginator,
            ItemData::class,
            $metadata
        );
    }
    
    public function checkAndReserveStock(int $itemId, int $quantity, ?int $locationId = null): void
    {
        if (!$this->repository->checkAvailability($itemId, $quantity)) {
            $item = $this->repository->find($itemId);
            throw new InsufficientStockException(
                $item->name,
                $quantity,
                $this->inventory->getCurrentStock($itemId, $locationId)
            );
        }
        
        if ($this->features->isEnabled('inventory.real_time_reservation')) {
            $this->inventory->reserveStock($itemId, $quantity, $locationId);
        }
    }
    
    public function getResourceMetadata(array $context = []): ResourceMetadata
    {
        $columns = [];
        
        $columns['name'] = ColumnMetadata::text('name', 'Name')
            ->sortable()
            ->searchable();
            
        $columns['sku'] = ColumnMetadata::text('sku', 'SKU')
            ->sortable()
            ->searchable();
            
        $columns['price'] = ColumnMetadata::number('price', 'Price')
            ->sortable()
            ->withFormat('currency');
            
        $columns['is_active'] = ColumnMetadata::boolean('is_active', 'Status')
            ->withFilter(FilterMetadata::select(
                'is_active',
                'Status',
                $this->repository->getFilterOptions('status'),
                'Filter by status'
            ));
            
        if ($this->features->isEnabled('item.categories')) {
            $columns['categories'] = ColumnMetadata::relation('categories', 'Categories')
                ->withFilter(FilterMetadata::multiSelect(
                    'category_id',
                    'Categories',
                    $this->repository->getFilterOptions('category'),
                    'Filter by category'
                ));
        }
        
        return new ResourceMetadata(
            columns: collect($columns),
            defaultFilters: ['search', 'is_active', 'category_id'],
            defaultSort: 'name',
            perPageOptions: [10, 20, 50, 100],
            defaultPerPage: 20,
        );
    }
    
    private function hasActiveOrders(int $itemId): bool
    {
        // This would check via OrderRepositoryInterface
        return false; // Simplified
    }
}
```

### 5. Register Service Provider
```php
// app-modules/item/src/Providers/ItemServiceProvider.php
namespace Modules\Item\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Item\Contracts\ItemRepositoryInterface;
use Modules\Item\Repositories\ItemRepository;
use Modules\Item\Contracts\ItemServiceInterface;
use Modules\Item\Services\ItemService;

class ItemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(ItemRepositoryInterface::class, ItemRepository::class);
        $this->app->bind(ItemServiceInterface::class, ItemService::class);
    }
    
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Load config
        $this->mergeConfigFrom(__DIR__ . '/../../config/features.php', 'features.item');
    }
}
```

### 6. Create Controllers

#### Web Controller
```php
// app-modules/item/src/Http/Controllers/Web/ItemController.php
namespace Modules\Item\Http\Controllers\Web;

use App\Core\Traits\HandlesPaginationBounds;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Item\Services\ItemServiceInterface;
use Modules\Item\Data\ItemData;

class ItemController extends Controller
{
    use HandlesPaginationBounds;
    
    public function __construct(
        private ItemServiceInterface $service
    ) {}
    
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'is_active', 'category_id', 'sort', 'page']);
        $perPage = (int) $request->input('per_page', 20);
        
        $paginatedData = $this->service->getPaginatedItems($filters, $perPage);
        $responseData = $paginatedData->toArray();
        
        if ($redirect = $this->handleOutOfBoundsPagination($responseData['pagination'], $request, 'items.index')) {
            return $redirect;
        }
        
        return Inertia::render('item/index', [
            'items' => $responseData['data'],
            'pagination' => $responseData['pagination'],
            'metadata' => $responseData['metadata'],
        ]);
    }
    
    public function create(): Response
    {
        return Inertia::render('item/create');
    }
    
    public function store(ItemData $data): \Illuminate\Http\RedirectResponse
    {
        $item = $this->service->createItem($data->toArray());
        
        return redirect()
            ->route('items.show', $item->id)
            ->with('success', 'Item created successfully');
    }
    
    public function show(int $id): Response
    {
        $item = $this->service->getItem($id);
        
        return Inertia::render('item/show', [
            'item' => $item,
        ]);
    }
    
    public function edit(int $id): Response
    {
        $item = $this->service->getItem($id);
        
        return Inertia::render('item/edit', [
            'item' => $item,
        ]);
    }
    
    public function update(int $id, ItemData $data): \Illuminate\Http\RedirectResponse
    {
        $item = $this->service->updateItem($id, $data->toArray());
        
        return redirect()
            ->route('items.show', $item->id)
            ->with('success', 'Item updated successfully');
    }
    
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $this->service->deleteItem($id);
        
        return redirect()
            ->route('items.index')
            ->with('success', 'Item deleted successfully');
    }
}
```

#### API Controller
```php
// app-modules/item/src/Http/Controllers/Api/ItemController.php
namespace Modules\Item\Http\Controllers\Api;

use App\Core\Traits\HandlesPaginationBounds;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Item\Services\ItemServiceInterface;
use Modules\Item\Data\ItemData;

class ItemController extends Controller
{
    use HandlesPaginationBounds;
    
    public function __construct(
        private ItemServiceInterface $service
    ) {}
    
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'is_active', 'category_id', 'sort', 'page']);
        $perPage = (int) $request->input('per_page', 20);
        
        $paginatedData = $this->service->getPaginatedItems($filters, $perPage);
        $responseData = $paginatedData->toArray();
        
        if ($errorResponse = $this->handleOutOfBoundsPaginationApi($responseData['pagination'])) {
            return $errorResponse;
        }
        
        return response()->json([
            'data' => $responseData['data'],
            'meta' => array_merge(
                $responseData['pagination'],
                ['resource' => $responseData['metadata']]
            ),
            'links' => [
                'self' => request()->fullUrl(),
                'first' => $responseData['pagination']['first_page_url'],
                'last' => $responseData['pagination']['last_page_url'],
                'prev' => $responseData['pagination']['prev_page_url'],
                'next' => $responseData['pagination']['next_page_url'],
            ],
        ]);
    }
    
    public function store(ItemData $data): JsonResponse
    {
        $item = $this->service->createItem($data->toArray());
        
        return response()->json([
            'data' => $item,
            'message' => 'Item created successfully',
        ], 201);
    }
    
    public function show(int $id): JsonResponse
    {
        $item = $this->service->getItem($id);
        
        if (!$item) {
            return response()->json([
                'error' => 'Item not found',
            ], 404);
        }
        
        return response()->json([
            'data' => $item,
        ]);
    }
    
    public function update(int $id, ItemData $data): JsonResponse
    {
        try {
            $item = $this->service->updateItem($id, $data->toArray());
            
            return response()->json([
                'data' => $item,
                'message' => 'Item updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->deleteItem($id);
            
            return response()->json([
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
```

### 7. Define Routes

#### Web Routes
```php
// app-modules/item/routes/web.php
use Modules\Item\Http\Controllers\Web\ItemController;

Route::middleware(['auth'])->group(function () {
    Route::resource('items', ItemController::class);
});
```

#### API Routes
```php
// app-modules/item/routes/api.php
use Modules\Item\Http\Controllers\Api\ItemController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('items', ItemController::class);
});
```

### 8. Feature Configuration
```php
// app-modules/item/config/features.php
return [
    'categories' => [
        'enabled' => env('FEATURE_ITEM_CATEGORIES', true),
        'description' => 'Enable item categorization',
    ],
    'barcode_scanning' => [
        'enabled' => env('FEATURE_ITEM_BARCODE', true),
        'description' => 'Enable barcode scanning for items',
    ],
    'bulk_import' => [
        'enabled' => env('FEATURE_ITEM_BULK_IMPORT', false),
        'description' => 'Allow bulk importing of items via CSV',
        'rollout' => [
            'type' => 'locations',
            'value' => [1, 2], // Location IDs
        ],
    ],
];
```

This complete example demonstrates:
- Interface-based architecture
- DTOs for data transfer
- Repository pattern implementation
- Service layer with business logic
- Web and API controllers sharing services
- Feature flag integration
- Proper error handling
- Pagination with metadata