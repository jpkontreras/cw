# Order-ES Module: Pure Event Sourcing Architecture

## 🎯 Design Principles

This module implements **pure event sourcing with CQRS** - no hybrid patterns, no mixing of concerns.

### Core Principles:
1. **Commands** represent user intentions
2. **Aggregates** enforce business rules and emit events
3. **Events** are immutable facts
4. **Projectors** build read models from events
5. **Queries** read from projections only
6. **No traditional services** - business logic lives in aggregates
7. **Complete separation** between write and read models

## 📁 Module Structure

```
app-modules/order-es/
├── src/
│   ├── Commands/                    # User intentions
│   │   ├── StartOrder.php
│   │   ├── AddItemToOrder.php
│   │   ├── RemoveItemFromOrder.php
│   │   ├── UpdateItemQuantity.php
│   │   ├── ApplyPromotion.php
│   │   ├── SetPaymentMethod.php
│   │   ├── ConfirmOrder.php
│   │   └── CancelOrder.php
│   │
│   ├── CommandHandlers/             # Dispatch to aggregates
│   │   └── OrderCommandHandler.php  # Single handler for all order commands
│   │
│   ├── Aggregates/                  # Business logic & invariants
│   │   └── Order.php                # The only aggregate needed
│   │
│   ├── Events/                      # Facts that happened
│   │   ├── OrderStarted.php
│   │   ├── ItemAddedToOrder.php
│   │   ├── ItemRemovedFromOrder.php
│   │   ├── ItemQuantityUpdated.php
│   │   ├── PromotionApplied.php
│   │   ├── PaymentMethodSet.php
│   │   ├── OrderConfirmed.php
│   │   └── OrderCancelled.php
│   │
│   ├── Projectors/                  # Build read models
│   │   ├── OrderProjector.php      # Main order read model
│   │   ├── OrderItemProjector.php  # Order items read model
│   │   └── OrderStatsProjector.php # Statistics & reporting
│   │
│   ├── ProcessManagers/             # Orchestrate complex flows
│   │   └── OrderFulfillmentProcess.php
│   │
│   ├── Queries/                     # Read operations
│   │   ├── GetOrder.php
│   │   ├── GetOrdersByStatus.php
│   │   ├── GetKitchenOrders.php
│   │   └── GetOrderStats.php
│   │
│   ├── QueryHandlers/               # Execute queries
│   │   └── OrderQueryHandler.php
│   │
│   ├── ReadModels/                  # Projected data models
│   │   ├── Order.php               # Read-optimized order model
│   │   ├── OrderItem.php          # Read-optimized item model
│   │   └── OrderStats.php         # Aggregated statistics
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Web/
│   │   │   │   └── OrderController.php    # Thin: dispatch commands, execute queries
│   │   │   └── Api/
│   │   │       └── OrderController.php    # Thin: dispatch commands, execute queries
│   │   └── Resources/
│   │       └── OrderResource.php          # API response formatting
│   │
│   ├── Contracts/                   # Public interfaces
│   │   ├── CommandBus.php
│   │   └── QueryBus.php
│   │
│   └── Providers/
│       └── OrderEsServiceProvider.php
│
├── database/
│   └── migrations/
│       ├── create_order_read_models_table.php
│       └── create_order_item_read_models_table.php
│
└── routes/
    └── order-es-routes.php
```

## 🔄 Request Flow

### Write Path (Commands)
```
1. User Action
    ↓
2. Controller receives request
    ↓
3. Controller creates Command from request
    ↓
4. CommandBus dispatches to CommandHandler
    ↓
5. CommandHandler loads Aggregate
    ↓
6. Aggregate applies business logic
    ↓
7. Aggregate emits Events
    ↓
8. Events are stored
    ↓
9. Projectors update read models (async)
    ↓
10. Controller returns command result
```

### Read Path (Queries)
```
1. User requests data
    ↓
2. Controller creates Query
    ↓
3. QueryBus dispatches to QueryHandler
    ↓
4. QueryHandler reads from projections
    ↓
5. Controller returns data
```

## 💡 Key Differences from Traditional Architecture

### No Service Layer
```php
// ❌ Traditional (order module)
class OrderService {
    public function createOrder($data) {
        // Business logic mixed with infrastructure
        $this->validate($data);
        $this->calculateTotals();
        $this->saveToDatabase();
    }
}

// ✅ Event Sourced (order-es module)
class Order extends AggregateRoot {
    public function start($customerId, $locationId) {
        // Pure business logic
        $this->guardAgainstInvalidCustomer($customerId);
        $this->recordThat(new OrderStarted($customerId, $locationId));
    }
}
```

### Thin Controllers
```php
// ❌ Traditional
public function store(Request $request, OrderService $service) {
    $data = CreateOrderData::validateAndCreate($request);
    $order = $service->createOrder($data);
    // Complex orchestration...
    return Inertia::render('Order/Show', ['order' => $order]);
}

// ✅ Event Sourced
public function store(Request $request, CommandBus $bus) {
    $command = new StartOrder(
        customerId: $request->user()->id,
        locationId: $request->location_id
    );
    $bus->dispatch($command);
    return response()->json(['id' => $command->orderId]);
}
```

### Clear Separation
```php
// Commands (Write Model)
class AddItemToOrder {
    public function __construct(
        public readonly string $orderId,
        public readonly int $itemId,
        public readonly int $quantity
    ) {}
}

// Queries (Read Model)
class GetKitchenOrders {
    public function __construct(
        public readonly int $locationId,
        public readonly array $statuses = ['confirmed', 'preparing']
    ) {}
}
```

## 🚀 Implementation Steps

1. **Commands & Events** - Define all user actions and resulting facts
2. **Aggregate** - Implement business rules that process commands
3. **Command Handlers** - Wire commands to aggregates
4. **Projectors** - Build read models from events
5. **Queries & Handlers** - Implement read operations
6. **Controllers** - Thin layer that dispatches commands/queries
7. **Process Managers** - Handle complex multi-step flows

## 📋 Command/Event Mapping

| Command | Events Emitted | Business Rules |
|---------|---------------|----------------|
| StartOrder | OrderStarted | Must have valid customer & location |
| AddItemToOrder | ItemAddedToOrder | Item must be available, quantity > 0 |
| RemoveItemFromOrder | ItemRemovedFromOrder | Item must exist in order |
| UpdateItemQuantity | ItemQuantityUpdated | New quantity must be > 0 |
| ApplyPromotion | PromotionApplied | Promotion must be valid & applicable |
| SetPaymentMethod | PaymentMethodSet | Method must be supported |
| ConfirmOrder | OrderConfirmed | Order must have items, payment set |
| CancelOrder | OrderCancelled | Order must not be completed |

## 🎯 Benefits of This Architecture

1. **Complete Audit Trail** - Every change is an event
2. **Time Travel** - Replay events to any point
3. **No Data Loss** - Events are immutable
4. **Clear Business Logic** - All rules in aggregates
5. **Scalable Reads** - Multiple projections possible
6. **Testable** - Pure functions, no side effects
7. **Decoupled** - Write and read models independent

## 🔍 Testing Strategy

```php
// Test commands produce correct events
test('starting order emits OrderStarted event')
    ->given([])  // No previous events
    ->when(new StartOrder($customerId, $locationId))
    ->assertRecorded([
        new OrderStarted($orderId, $customerId, $locationId)
    ]);

// Test business rules
test('cannot add item to confirmed order')
    ->given([
        new OrderStarted(...),
        new OrderConfirmed(...)
    ])
    ->when(new AddItemToOrder(...))
    ->assertException(OrderAlreadyConfirmedException::class);
```

## 🚫 What NOT to Do

1. **Don't query aggregates** - They're write-only
2. **Don't put logic in controllers** - Keep them thin
3. **Don't bypass commands** - All changes through commands
4. **Don't mix read/write models** - Keep them separate
5. **Don't make synchronous projections** - Use async when possible