# Order States Integration Guide

## Overview

The Order module uses `spatie/laravel-model-states` for state management at the **Model/Database layer**, while maintaining event sourcing for the **business logic layer**.

## Architecture Layers

### 1. Event Sourcing Layer (OrderAggregate)
- Handles business logic and state transitions
- Records events for audit trail
- Maintains internal status as strings
- Source of truth for business operations

### 2. Database Layer (Order Model)
- Uses state objects for permissions and UI
- Provides helper methods for state checks
- Handles display and presentation logic

## How It Works Together

### Creating an Order
```php
// 1. Event Sourcing creates the order
$orderUuid = $eventSourcedService->createOrder($data);

// 2. Database model is created by projector
$order = Order::find($orderUuid);

// 3. Check state permissions using model
if ($order->canBeModified()) {
    // Order can be modified
}

// 4. Get display info from state
$statusName = $order->status->displayName();  // "Draft"
$statusColor = $order->status->color();        // "gray"
```

### State Transitions

```php
// Using Event Sourcing (Business Logic)
$aggregate = OrderAggregate::retrieve($order->uuid);
$aggregate->confirmOrder();
$aggregate->persist();

// The projector will update the database model's state
// OrderProjector::onOrderConfirmed() sets:
$order->update(['status' => 'confirmed']);

// Now the model has the ConfirmedState object
$order->fresh();
$order->status instanceof ConfirmedState; // true
$order->status->canBeModified(); // true
$order->status->affectsKitchen(); // true
```

### Checking Permissions

```php
// At the Model level (for UI/API responses)
$order = Order::find($id);

// These use the state object methods
$order->canBeModified();      // Delegates to state->canBeModified()
$order->canBeCancelled();     // Delegates to state->canBeCancelled()
$order->canAddItems();        // Delegates to state->canAddItems()
$order->canProcessPayment();  // Delegates to state->canProcessPayment()
$order->affectsKitchen();     // Delegates to state->affectsKitchen()

// At the Aggregate level (for business logic)
$aggregate = OrderAggregate::retrieve($uuid);
if ($aggregate->canBeModified()) {
    $aggregate->modifyItems(...);
}
```

## State Flow

```
1. Draft (DraftState)
   ↓ startOrder()
2. Started (StartedState)  
   ↓ addItems()
3. Items Added (ItemsAddedState)
   ↓ validateItems()
4. Items Validated (ItemsValidatedState)
   ↓ calculatePromotions()
5. Promotions Calculated (PromotionsCalculatedState)
   ↓ calculatePrice()
6. Price Calculated (PriceCalculatedState)
   ↓ confirmOrder()
7. Confirmed (ConfirmedState)
   ↓ startPreparing()
8. Preparing (PreparingState)
   ↓ markReady()
9. Ready (ReadyState)
   ↓ deliver() or complete()
10. Delivering/Completed
```

## Usage in Controllers

```php
class OrderController extends Controller
{
    public function store(Request $request)
    {
        // 1. Create via event sourcing
        $data = CreateOrderData::validateAndCreate($request);
        $order = $this->orderService->createOrder($data);
        
        // 2. Check state for UI
        return Inertia::render('orders/show', [
            'order' => $order,
            'canModify' => $order->canBeModified(),
            'canCancel' => $order->canBeCancelled(),
            'statusDisplay' => $order->status->displayName(),
            'statusColor' => $order->status->color(),
        ]);
    }
    
    public function update(Request $request, Order $order)
    {
        // Check permission using state
        if (!$order->canBeModified()) {
            abort(403, 'Order cannot be modified in current state');
        }
        
        // Update via event sourcing
        $this->eventService->modifyOrder($order->uuid, $data);
        
        return redirect()->route('orders.show', $order);
    }
}
```

## Benefits of This Architecture

1. **Separation of Concerns**
   - Event sourcing handles business logic
   - States handle UI/permissions

2. **Type Safety**
   - State objects provide IDE autocomplete
   - No magic strings in controllers

3. **Maintainability**
   - State logic is centralized
   - Easy to add new states or modify behavior

4. **Testing**
   - States can be unit tested
   - Business logic tested separately

5. **Flexibility**
   - Can change UI permissions without affecting business logic
   - Can add new states without changing aggregate

## Common Patterns

### Adding a New State

1. Create the state class:
```php
class ReviewingState extends OrderState
{
    public static $name = 'reviewing';
    
    public function displayName(): string
    {
        return __('order.status.reviewing');
    }
    
    public function color(): string
    {
        return 'orange';
    }
    
    public function canBeModified(): bool
    {
        return true;
    }
}
```

2. Update transitions in OrderState:
```php
->allowTransition(ConfirmedState::class, ReviewingState::class)
->allowTransition(ReviewingState::class, PreparingState::class)
```

3. Update language files:
```php
'reviewing' => 'Under Review',
```

### Custom State Logic

```php
class PreparingState extends OrderState
{
    public function getEstimatedTime(): int
    {
        // Calculate based on order items
        $order = $this->model();
        return $order->items->sum('prep_time') ?? 15;
    }
    
    public function canRushOrder(): bool
    {
        // Check if kitchen can handle rush orders
        return Kitchen::canAcceptRushOrders();
    }
}
```

## Important Notes

1. **States are for the Model layer** - The database record uses state objects
2. **Aggregate maintains its own status** - Business logic uses strings
3. **Projector syncs them** - Events update both layers
4. **Don't mix concerns** - UI logic in states, business logic in aggregates
5. **States are immutable** - Once set, create new instance to change

## Debugging

```php
// Check current state
dd($order->status);                    // DraftState object
dd($order->status::$name);            // "draft"
dd($order->status->displayName());    // "Draft"

// Check available transitions
dd($order->status->transitionableStates());

// Check if can transition
dd($order->status->canTransitionTo(ConfirmedState::class));

// Get all possible states
dd(OrderState::config()->states());
```