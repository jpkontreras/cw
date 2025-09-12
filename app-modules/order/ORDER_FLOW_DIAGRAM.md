# Order Flow Diagram - Event Sourced Architecture

## 🔄 Process Flow Details

### 1. Session Initiation
```
User Opens Order Page
    ↓
OrderFlowController::startSession()
📁 src/Http/Controllers/Web/OrderFlowController.php:19
    ↓ 
    Validates: StartOrderFlowData::validateAndCreate($request->all())
    ↓
OrderSessionService::startSession(StartOrderFlowData $data)
📁 src/Services/OrderSessionService.php:26
    ↓
    Gets location context via UserLocationServiceInterface::getEffectiveLocation()
    📁 Uses interface from app-modules/location/src/Contracts/
    ↓
OrderAggregate::retrieve($uuid)->initiateSession()
📁 src/Aggregates/OrderAggregate.php:89
    ↓
Event: OrderSessionInitiated (stored in stored_events table)
📁 src/Events/Session/OrderSessionInitiated.php
    ↓
OrderSessionProjector::onOrderSessionInitiated()
📁 src/Projectors/OrderSessionProjector.php:25
    ↓
OrderSession record created (read model projection)
📁 src/Models/OrderSession.php
```

### 2. Cart Building
```
User Adds Item
    ↓
OrderFlowController::addToCart()
📁 src/Http/Controllers/Web/OrderFlowController.php:82
    ↓
OrderSessionService::addToCart($uuid, $data)
📁 src/Services/OrderSessionService.php:174
    ↓
OrderAggregate::retrieve($uuid)->addToCart()
📁 src/Aggregates/OrderAggregate.php:181
    ↓
Event: ItemAddedToCart (stored in stored_events)
📁 src/Events/Session/ItemAddedToCart.php
    ↓
OrderSessionProjector::onItemAddedToCart()
📁 src/Projectors/OrderSessionProjector.php:44
    ↓
Updates cart_items in OrderSession (projection)
```

### 3. Session to Order Conversion (SAME UUID)
```
User Confirms Cart
    ↓
OrderFlowController::convertToOrder()
📁 src/Http/Controllers/Web/OrderFlowController.php:169
    ↓
OrderSessionService::convertToOrder($sessionUuid, $data)
📁 src/Services/OrderSessionService.php:312
    ↓
    Continue with SAME aggregate (same UUID)
    OrderAggregate::retrieve($sessionUuid)
    ↓
    Get location data for currency from session
    $currency = $locationData['currency'] ?? 'CLP'
    ↓
Event: SessionConverted (using same aggregateRootUuid)
📁 src/Events/Session/SessionConverted.php
    orderId = sessionUuid (SAME UUID for continuity)
    ↓
OrderSessionProjector::onSessionConverted()
📁 src/Projectors/OrderSessionProjector.php:182
    Updates session status to 'converted'
    ↓
OrderFromSessionProjector::onSessionConverted()
📁 src/Projectors/OrderFromSessionProjector.php:22
    ↓
    Calculate totals using ItemRepositoryInterface
    Uses Akaunting\Money for currency handling
    ↓
    Order::updateOrCreate(['id' => $event->orderId])
    Creates Order record (projection) with same UUID
```

### 4. Key Architecture Decisions

#### Single UUID Strategy
```
Session UUID = Order UUID = Aggregate UUID
    ↓
Benefits:
- Single source of truth (stored_events)
- Complete event history in one stream
- No complex UUID mapping
- Simpler event replay
```

#### Dependency Injection Pattern
```
OrderSessionService
    ↓
Constructor injection with nullable interfaces:
- ?UserLocationServiceInterface $userLocationService = null
- ?ItemRepositoryInterface $itemRepository = null
    ↓
Interfaces bound in respective ServiceProviders:
- LocationServiceProvider binds UserLocationServiceInterface
- OrderServiceProvider registers projectors
```

#### Currency Handling
```
Location provides currency
    ↓
OrderSessionService uses Akaunting\Money\Money
    ↓
new Money($amount, new Currency($currency))
    ↓
Handles both array and object formats from ItemRepository:
- is_array($item) ? $item['basePrice'] : $item->basePrice
```

#### Event Sourcing Flow
```
User Action
    ↓
Service Method
    ↓
OrderAggregate::retrieve($uuid)
    ->recordThat(new Event(...))
    ->persist()
    ↓
Event stored in stored_events table
    ↓
Projectors update read models:
- OrderSessionProjector → order_sessions table
- OrderFromSessionProjector → orders table
    ↓
getSessionState() reads from projections (not events)