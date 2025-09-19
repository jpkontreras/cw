# Order-ES Module: Pure Event Sourcing Implementation

## ✅ What We've Built

A **clean, pure event-sourced order module** with complete CQRS separation. No hybrid patterns, no service layers, no confusion.

## 🏗️ Architecture Components

### 1. **Commands** (User Intentions)
- `StartOrder` - Begin a new order
- `AddItemToOrder` - Add item to cart
- `RemoveItemFromOrder` - Remove item
- `ConfirmOrder` - Finalize order
- `CancelOrder` - Cancel order

### 2. **Events** (Facts)
- `OrderStarted` - Order has begun
- `ItemAddedToOrder` - Item was added
- `ItemRemovedFromOrder` - Item was removed
- `OrderConfirmed` - Order was confirmed
- `OrderCancelled` - Order was cancelled

### 3. **Aggregate** (Business Logic)
- `Order` - Contains ALL business rules
- Guards against invalid state transitions
- Emits events based on commands
- Pure domain logic, no infrastructure

### 4. **Projectors** (Read Model Builders)
- `OrderProjector` - Updates read models from events
- Creates optimized views for queries
- Handles denormalization for performance

### 5. **Queries** (Read Operations)
- `GetOrder` - Fetch single order
- `GetOrdersByStatus` - List orders by status
- `GetKitchenOrders` - Kitchen display view

### 6. **Controllers** (Thin Layer)
- Just dispatch commands and queries
- No business logic
- Clean HTTP interface

## 🚀 How to Use

### Starting an Order
```bash
POST /orders-es/start
{
    "location_id": 1,
    "type": "dine_in"
}
```

### Adding Items
```bash
POST /orders-es/{orderId}/add-item
{
    "item_id": 123,
    "quantity": 2,
    "modifiers": [],
    "notes": "No onions"
}
```

### Confirming Order
```bash
POST /orders-es/{orderId}/confirm
{
    "payment_method": "cash",
    "tip_amount": 5.00
}
```

### Viewing Orders
```bash
GET /orders-es/
GET /orders-es/{orderId}
GET /orders-es/kitchen?location_id=1
```

## 🎯 Key Benefits vs Old Architecture

| Aspect | Old (order module) | New (order-es module) |
|--------|-------------------|----------------------|
| **Business Logic** | Scattered across services | All in aggregate |
| **Controllers** | Fat with logic | Thin dispatchers |
| **State Management** | Database-driven | Event-driven |
| **Testing** | Complex mocking | Simple event assertions |
| **Audit Trail** | Partial | Complete via events |
| **Complexity** | High (dual architecture) | Low (single pattern) |

## 📊 Code Comparison

### Old Way (order module)
```php
// Fat controller with service dependencies
public function store(Request $request, OrderService $service) {
    $data = CreateOrderData::validateAndCreate($request);
    $order = $service->createOrder($data);
    // More orchestration...
    return Inertia::render('Order/Show', ['order' => $order]);
}
```

### New Way (order-es module)
```php
// Thin controller just dispatches
public function start(Request $request): JsonResponse {
    $command = new StartOrder(
        customerId: $request->user()->id,
        locationId: $request->input('location_id')
    );
    $this->commandHandler->handleStartOrder($command);
    return response()->json(['id' => $command->orderId]);
}
```

## 🧪 Testing

```php
// Test with events, not mocks
test('order can be started')
    ->given([])
    ->when(new StartOrder($customerId, $locationId))
    ->assertRecorded([new OrderStarted(...)]);

test('cannot add items to cancelled order')
    ->given([
        new OrderStarted(...),
        new OrderCancelled(...)
    ])
    ->when(new AddItemToOrder(...))
    ->assertException(OrderAlreadyCancelledException::class);
```

## 📈 Next Steps

1. Add more complex business rules
2. Implement process managers for multi-step flows
3. Add more projections for different views
4. Implement event replay capabilities
5. Add integration with other modules via events

## 🚫 What NOT to Add

- ❌ Service layers (logic belongs in aggregates)
- ❌ Direct database queries in controllers
- ❌ Business logic in controllers
- ❌ Mixing read/write models
- ❌ Synchronous projections (use async)

## 📝 Module Status

✅ **COMPLETE** - This is a fully functional, pure event-sourced implementation ready for use.