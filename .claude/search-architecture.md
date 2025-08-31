# Search Architecture

This document describes the modular search implementation using Laravel Scout and MeiliSearch.

## Overview

The search system is designed to be modular, allowing each module to implement its own search logic while providing a unified API for cross-module searches. It uses MeiliSearch for fast, typo-tolerant searching with support for facets, filters, and learning from user behavior.

## Core Components

### 1. Core Infrastructure (`app/Core`)

#### Contracts
- **`SearchableInterface`**: Standard interface for searchable models (extends Scout's functionality)
- **`ModuleSearchInterface`**: Contract that each module's search service must implement
- **`SearchResultData`**: Standardized DTO for search results

#### Services
- **`UnifiedSearchService`**: Orchestrates searches across multiple modules, handles registration

### 2. Module Implementation

Each searchable module must:

1. **Make Model Searchable**
```php
use Laravel\Scout\Searchable;

class Order extends Model {
    use Searchable;
    
    public function toSearchableArray(): array {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            // Include all fields you want searchable
        ];
    }
    
    public function shouldBeSearchable(): bool {
        // Control what gets indexed
        return $this->status !== 'draft';
    }
}
```

2. **Create Search Service**
```php
class OrderSearchService implements OrderSearchInterface, ModuleSearchInterface {
    
    public function search(string $query, array $filters = []): SearchResultData {
        $searchBuilder = Order::search($query);
        
        // Apply filters
        if (!empty($filters['status'])) {
            $searchBuilder->where('status', $filters['status']);
        }
        
        $results = $searchBuilder->paginate($filters['per_page'] ?? 20);
        
        return new SearchResultData(
            items: OrderSearchData::collection($results),
            query: $query,
            searchId: Str::uuid(),
            total: $results->total(),
            facets: $this->getFacets(),
            suggestions: $this->getSuggestions($query)
        );
    }
    
    public function getSearchableFields(): array {
        return [
            'order_number' => ['weight' => 10, 'exact_match' => true],
            'customer_name' => ['weight' => 8],
            'customer_phone' => ['weight' => 7],
            // Define field weights and properties
        ];
    }
}
```

3. **Register with UnifiedSearchService**
```php
// In module's ServiceProvider boot() method
public function boot(): void {
    if ($this->app->bound(UnifiedSearchService::class)) {
        $this->app->make(UnifiedSearchService::class)->registerModule(
            'orders', // Module identifier
            $this->app->make(OrderSearchService::class)
        );
    }
}
```

## API Endpoints

### Global Search
```http
GET /api/search?q=search_term&types[]=orders&types[]=items
```

**Response:**
```json
{
    "query": "search_term",
    "results": {
        "orders": {
            "items": [...],
            "total": 25,
            "searchId": "uuid",
            "facets": {...}
        },
        "items": {
            "items": [...],
            "total": 10,
            "searchId": "uuid"
        }
    },
    "meta": {
        "total": 35,
        "types": ["orders", "items"]
    }
}
```

### Type-Specific Search
```http
GET /api/search/orders?q=john&filters[status]=pending&filters[min_amount]=100
```

### Suggestions/Autocomplete
```http
GET /api/search/suggest?q=joh&type=orders
```

**Response:**
```json
{
    "query": "joh",
    "suggestions": [
        {
            "type": "customer",
            "value": "John Doe",
            "label": "John Doe"
        },
        {
            "type": "order_number",
            "value": "JOH-2024-001",
            "label": "Order #JOH-2024-001"
        }
    ]
}
```

### Record Selection (for learning)
```http
POST /api/search/select
{
    "search_id": "uuid",
    "type": "orders",
    "entity_id": 123
}
```

## MeiliSearch Configuration

### Docker Setup
```yaml
# docker-compose.yml
services:
  meilisearch:
    image: getmeili/meilisearch:v1.6
    ports:
      - "7700:7700"
    environment:
      - MEILI_MASTER_KEY=${MEILISEARCH_KEY:-masterKey}
      - MEILI_ENV=development
    volumes:
      - sail-meilisearch:/meili_data
    networks:
      - sail
```

### Environment Configuration
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey
```

### Index Management
```bash
# Import all records to MeiliSearch
sail artisan scout:import "Colame\Order\Models\Order"

# Flush index
sail artisan scout:flush "Colame\Order\Models\Order"

# Update index settings (run after model changes)
sail artisan scout:sync-index-settings
```

## Advanced Features

### 1. Faceted Search
Each module can provide facets for filtering:

```php
private function getFacets(): array {
    return [
        'status' => Order::groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status'),
        'payment_status' => Order::groupBy('payment_status')
            ->selectRaw('payment_status, count(*) as count')
            ->pluck('count', 'payment_status'),
    ];
}
```

### 2. Search Learning
The system tracks searches and selections to improve results:

```php
// Automatically recorded when user selects a result
DB::table('search_selections')->insert([
    'search_id' => $searchId,
    'entity_type' => 'orders',
    'entity_id' => $orderId,
    'user_id' => auth()->id(),
]);

// Use this data to boost frequently selected items
Order::where('id', $frequentlySelected)
    ->increment('view_count');
```

### 3. Custom Ranking
Configure ranking rules per module:

```php
public function configureIndex(): array {
    return [
        'rankingRules' => [
            'words',          // Number of words matched
            'typo',           // Fewer typos = higher rank
            'proximity',      // Words close together
            'attribute',      // Match in important fields first
            'sort',           // Custom sort order
            'exactness',      // Exact matches first
            'custom',         // Your custom ranking
        ],
        'searchableAttributes' => [
            'order_number',
            'customer_name',
            'customer_phone',
        ],
        'filterableAttributes' => [
            'status',
            'payment_status',
            'location_id',
            'total_amount',
        ],
        'sortableAttributes' => [
            'created_at',
            'total_amount',
            'order_number',
        ],
    ];
}
```

## Adding Search to a New Module

### Step 1: Update Model
```php
use Laravel\Scout\Searchable;

class YourModel extends Model {
    use Searchable;
    
    public function toSearchableArray(): array {
        return [/* your searchable fields */];
    }
}
```

### Step 2: Create Search Service
```php
class YourSearchService implements YourSearchInterface, ModuleSearchInterface {
    // Implement required methods
}
```

### Step 3: Create Search DTO
```php
class YourSearchData extends BaseData {
    public function __construct(
        public int $id,
        public string $name,
        // your fields
        public ?float $searchScore = null,
    ) {}
}
```

### Step 4: Register in ServiceProvider
```php
public function register(): void {
    $this->app->bind(YourSearchInterface::class, YourSearchService::class);
}

public function boot(): void {
    if ($this->app->bound(UnifiedSearchService::class)) {
        $this->app->make(UnifiedSearchService::class)->registerModule(
            'your_module',
            $this->app->make(YourSearchService::class)
        );
    }
}
```

### Step 5: Run Migrations & Index
```bash
# Run migrations
sail artisan migrate

# Import existing data to search index
sail artisan scout:import "YourModule\Models\YourModel"
```

## Performance Optimization

### 1. Batch Imports
```php
// For large datasets, use chunks
YourModel::chunk(500, function ($models) {
    $models->searchable();
});
```

### 2. Queue Configuration
```env
SCOUT_QUEUE=true  # Process indexing in background
```

### 3. Index Only Changed Fields
```php
public function toSearchableArray(): array {
    // Only include fields that should trigger reindex
    if ($this->isDirty(['status', 'customer_name'])) {
        return parent::toSearchableArray();
    }
    return [];
}
```

### 4. Caching
```php
// Cache popular searches
Cache::remember("search:{$query}", 300, function () use ($query) {
    return $this->performSearch($query);
});

// Cache facets
Cache::remember('search:facets:orders', 600, function () {
    return $this->calculateFacets();
});
```

## Testing

### Unit Tests
```php
class OrderSearchServiceTest extends TestCase {
    public function test_search_returns_correct_structure() {
        $service = new OrderSearchService();
        $results = $service->search('test');
        
        $this->assertInstanceOf(SearchResultData::class, $results);
        $this->assertIsString($results->searchId);
        $this->assertIsInt($results->total);
    }
}
```

### Integration Tests
```php
public function test_search_api_endpoint() {
    Order::factory()->create(['customer_name' => 'John Doe']);
    
    $response = $this->getJson('/api/search/orders?q=john');
    
    $response->assertOk()
        ->assertJsonStructure([
            'items',
            'total',
            'searchId',
        ]);
}
```

## Troubleshooting

### Common Issues

1. **Search not returning results**
   - Check if model implements `Searchable` trait
   - Verify `toSearchableArray()` returns correct data
   - Run `sail artisan scout:import` to reindex

2. **MeiliSearch connection failed**
   - Verify MeiliSearch is running: `docker ps`
   - Check environment variables
   - Test connection: `curl http://localhost:7700/health`

3. **Filters not working**
   - Ensure fields are in `filterableAttributes`
   - Rebuild index after configuration changes
   - Check filter syntax in query

4. **Performance issues**
   - Enable queue for indexing
   - Use pagination for large result sets
   - Implement caching for frequent searches

## Best Practices

1. **Always use DTOs** for search results, not Eloquent models
2. **Index only necessary fields** to keep index size manageable
3. **Implement shouldBeSearchable()** to control what gets indexed
4. **Use appropriate field weights** to improve relevance
5. **Track search analytics** to understand user behavior
6. **Cache frequently searched queries** for better performance
7. **Implement suggestions** for better UX
8. **Provide facets** for easy filtering
9. **Test search functionality** thoroughly
10. **Monitor MeiliSearch metrics** in production