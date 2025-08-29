# Event-Sourced Order Flows

## Overview

The order module uses **Event Sourcing** with `spatie/laravel-event-sourcing` to handle complex, multi-step order flows with real-time promotions, validations, and cross-module communication.

## Architecture Benefits

### 1. **Resilience & Scalability**
- **No Deadlocks**: Async event processing prevents database locks during peak hours
- **Self-Healing**: Failed steps can be retried or compensated without losing data
- **Partial Failures**: System continues working even if some modules are down

### 2. **Perfect for Mobile Apps**
- **Offline-First**: Mobile devices create events offline, sync when reconnected
- **Idempotent**: Same operation executed multiple times = same result
- **Multi-Version Support**: Old and new app versions coexist peacefully

### 3. **Cross-Module Communication**
- **Decoupled**: Modules communicate via events, not direct dependencies
- **Async Processing**: Each module reacts to events independently
- **No Circular Dependencies**: Events flow in one direction

## Key Components

### OrderAggregate
The event source that records all order actions as immutable events:
```php
OrderAggregate::retrieve($uuid)
    ->startOrder($staffId, $locationId)
    ->addItems($items)
    ->applyPromotion($promotionId)
    ->confirmOrder()
    ->persist();
```

### Projectors (Cross-Module Reactions)
- **ItemValidationProjector**: Item module validates availability/stock
- **PromotionCalculatorProjector**: Offer module calculates applicable promotions
- **OrderProjector**: Updates the read model (database)

### Process Manager
Handles synchronous API responses while events process asynchronously:
```php
$processManager->startProcess($data);
$result = $processManager->waitForCompletion($processId, timeout: 5);
```

## API Flow for Mobile Apps

```
POST /api/orders/flow/start         → Start order, get UUID
POST /api/orders/flow/{uuid}/items  → Add items, get promotions
POST /api/orders/flow/{uuid}/promotion → Apply/remove promotions
POST /api/orders/flow/{uuid}/confirm → Confirm and send to kitchen
GET  /api/orders/flow/{uuid}/state  → Poll current state (recovery)
```

## Implementation Pattern

### 1. Define Events (What Happened)
```php
class OrderStarted extends ShouldBeStored {
    public function __construct(
        public string $aggregateRootUuid,
        public string $staffId,
        public string $locationId
    ) {}
}
```

### 2. Record in Aggregate
```php
public function startOrder($staffId, $locationId): self {
    $this->recordThat(new OrderStarted(
        $this->uuid(), $staffId, $locationId
    ));
    return $this;
}
```

### 3. React with Projectors
```php
class ItemValidationProjector extends Projector {
    public function onItemsAddedToOrder($event): void {
        // Validate items exist and are available
        // Emit ItemsValidated or ItemValidationFailed
    }
}
```

### 4. Handle in API
```php
public function addItems(Request $request, $orderUuid) {
    // Add items to aggregate
    OrderAggregate::retrieve($orderUuid)
        ->addItems($items)
        ->persist();
    
    // Wait for validation and promotions
    $result = $this->processManager->waitForCompletion($processId);
    
    return response()->json($result);
}
```

## Testing Event-Sourced Flows

```php
// Test complete flow
$response = $this->postJson('/api/orders/flow/start', [...]);
$orderUuid = $response->json('data.order_uuid');

$response = $this->postJson("/api/orders/flow/{$orderUuid}/items", [...]);
$this->assertEquals('items_validated', $response->json('data.status'));

// Test event replay
Projectionist::replay(OrderProjector::class, $orderUuid);
```

## When to Use Event Sourcing

✅ **Use for:**
- Complex multi-step workflows (orders, bookings, payments)
- Operations requiring audit trails
- Offline-first mobile scenarios
- Cross-module orchestration

❌ **Don't use for:**
- Simple CRUD operations
- Read-heavy features
- Real-time data that changes frequently

## Migration Commands

```bash
# Create event store tables
sail artisan event-sourcing:create-stored-event-migration
sail artisan migrate

# Replay events (debugging/recovery)
sail artisan event-sourcing:replay OrderProjector

# Clear projections
sail artisan event-sourcing:clear-event-handlers
```

## Monitoring & Debugging

### View Event Stream
```sql
SELECT * FROM stored_events 
WHERE aggregate_uuid = 'order-uuid'
ORDER BY created_at;
```

### Replay Specific Order
```php
Projectionist::replay(
    OrderProjector::class,
    $orderUuid
);
```

## See Also
- [Full Documentation](../docs/ORDER_FLOW_ARCHITECTURE.md)
- [Order Module Tests](../app-modules/order/tests/Feature/TakeOrderFlowTest.php)
- [Spatie Event Sourcing Docs](https://spatie.be/docs/laravel-event-sourcing)