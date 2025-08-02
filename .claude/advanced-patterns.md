# Advanced Patterns

## Event-Driven Communication

### Overview
Modules communicate asynchronously through domain events, maintaining loose coupling while enabling reactive behavior.

### Event Architecture

#### 1. Event Contracts
```php
interface OrderEventInterface {
    public function getOrderId(): int;
    public function getOrderData(): array;
    public function getEventType(): string;
    public function getItemIds(): array;
    public function getLocationId(): ?int;
}
```

#### 2. Domain Events
```php
class OrderCreated implements OrderEventInterface {
    public function __construct(private Order $order) {}
    
    public function getOrderData(): array {
        return [
            'id' => $this->order->id,
            'total' => $this->order->total_amount,
            'location_id' => $this->order->location_id,
        ];
    }
}
```

#### 3. Event Listeners
```php
class UpdateInventoryMetrics {
    public function handle(OrderCreated|OrderCancelled $event): void {
        $itemIds = $event->getItemIds();
        // React to order without knowing Order model internals
    }
}
```

## Error Handling

### Module-Specific Exceptions
```php
class InsufficientStockException extends Exception {
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

### Exception Transformation
```php
try {
    $this->items->decrementStock($itemId, $quantity);
} catch (InsufficientStockException $e) {
    throw new OrderException("Cannot add item: " . $e->getMessage());
}
```

### Best Practices
1. **Error Tracing**: Include correlation IDs for cross-module operations
2. **Logging Standards**: Log at module boundaries with context
3. **Debug Tooling**: Use Laravel Telescope for development
4. **Error Monitoring**: Implement error tracking (e.g., Sentry)
5. **Graceful Degradation**: Handle failures without breaking flow

## Feature Flags

### Configuration
```php
// config/features.php
return [
    'order' => [
        'split_bill' => [
            'enabled' => env('FEATURE_ORDER_SPLIT_BILL', true),
            'description' => 'Allow splitting bills',
            'rollout' => [
                'type' => 'percentage',
                'value' => 50,
            ],
        ],
    ],
];
```

### Usage Patterns
```php
// Simple check
if ($this->features->isEnabled('order.split_bill')) {
    // Feature logic
}

// Location-specific
if ($this->features->isEnabledForLocation('order.split_bill', $locationId)) {
    // Location-specific logic
}

// User context
$context = ['user_id' => $userId, 'location_id' => $locationId];
if ($this->features->isEnabled('order.advanced_features', $context)) {
    // Advanced features
}

// A/B testing
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
5. **Dependencies**: Features requiring other features

### Testing with Feature Flags
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

### Performance Monitoring
```php
class FeatureFlagMiddleware {
    public function handle($request, $next) {
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

## Cross-Module Queries

### Query Services for Performance
When you need optimized queries with joins:

```php
class OrderQueryService {
    public function getOrdersWithRelations(): Collection {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->select([
                'orders.*', 
                'users.name as user_name', 
                'locations.name as location_name'
            ])
            ->get()
            ->map(fn($row) => OrderWithRelationsData::fromRawQuery($row));
    }
}
```

### Service Layer Orchestration
For standard operations, use services with interfaces:

```php
class OrderService {
    public function __construct(
        private OrderRepositoryInterface $orders,
        private UserRepositoryInterface $users,
        private LocationRepositoryInterface $locations,
    ) {}
    
    public function getOrderWithDetails(int $id): ?OrderWithRelationsData {
        $order = $this->orders->find($id);
        if (!$order) return null;
        
        $user = $this->users->find($order->userId);
        $location = $this->locations->find($order->locationId);
        
        return new OrderWithRelationsData($order, $user, $location);
    }
}
```