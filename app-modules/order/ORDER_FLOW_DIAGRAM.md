# Order Flow Diagram - Event Sourced Architecture

## 📊 Execution Timeline & Phases

```
┌─────────────────────────────────────────────────────────────┐
│ PHASE 1: Session Initiation (Executes ONCE at start)        │
│ Creates the session container that will hold the cart       │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ PHASE 2: Cart Building (Executes 0-N times)                 │
│ User adds/removes/updates items in the session              │
│ Each action modifies the existing session from Phase 1      │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ PHASE 3: Order Conversion (Executes ONCE at end)            │
│ Converts the session (with all cart items) into an order    │
│ Uses the SAME UUID throughout all phases                    │
└─────────────────────────────────────────────────────────────┘
```

## 🔄 Detailed Process Flow

### PHASE 1: Session Initiation 
**When:** User opens order page for the first time  
**Frequency:** ONCE per order flow  
**Purpose:** Create session container for cart operations

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
    ↓
✅ SESSION READY FOR CART OPERATIONS (UUID: xxxxx-xxxxx)
```

### PHASE 2: Cart Building
**When:** User interacts with cart  
**Frequency:** MULTIPLE times (0 to N)  
**Purpose:** Modify the existing session with cart items

#### 2.1 Adding Items (repeatable)
```
User Adds Item to Cart
    ↓
OrderFlowController::addToCart()
📁 src/Http/Controllers/Web/OrderFlowController.php:82
    ↓
OrderSessionService::addToCart($uuid, $data)  // Uses UUID from Phase 1
📁 src/Services/OrderSessionService.php:174
    ↓
OrderAggregate::retrieve($uuid)->addToCart()  // Same aggregate from Phase 1
📁 src/Aggregates/OrderAggregate.php:181
    ↓
Event: ItemAddedToCart (stored in stored_events)
📁 src/Events/Session/ItemAddedToCart.php
    ↓
OrderSessionProjector::onItemAddedToCart()
📁 src/Projectors/OrderSessionProjector.php:44
    ↓
Updates cart_items in existing OrderSession (projection)
    ↓
✅ ITEM ADDED TO SESSION CART
```

#### 2.2 Other Cart Operations (all repeatable)
- **Remove Item:** `removeFromCart()` → Event: ItemRemovedFromCart
- **Update Quantity:** `updateQuantity()` → Event: ItemQuantityUpdated  
- **Clear Cart:** `clearCart()` → Event: CartCleared

### PHASE 3: Session to Order Conversion
**When:** User confirms cart and proceeds to checkout  
**Frequency:** ONCE per order flow  
**Purpose:** Transform session into permanent order record

```
User Confirms Cart & Proceeds to Checkout
    ↓
OrderFlowController::convertToOrder()
📁 src/Http/Controllers/Web/OrderFlowController.php:169
    ↓
OrderSessionService::convertToOrder($sessionUuid, $data)
📁 src/Services/OrderSessionService.php:312
    ↓
    🔑 KEY: Continue with SAME aggregate (same UUID from Phase 1)
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
    ↓
✅ ORDER CREATED (UUID unchanged: xxxxx-xxxxx)
```

## 🏗️ Key Architecture Decisions

### Event Stream Timeline
```
TIME →  [Phase 1]──────[Phase 2]──────[Phase 2]──────[Phase 2]──────[Phase 3]
        ↓              ↓              ↓              ↓              ↓
EVENTS: SessionInit    ItemAdded      ItemUpdated    ItemRemoved    SessionConverted
        │              │              │              │              │
UUID:   ├──────────────┴──────────────┴──────────────┴──────────────┤
        └─────────── SAME UUID THROUGHOUT (e.g., abc-123) ──────────┘
        │                                                            │
STATE:  [Session Created]  [Cart Building...]                [Order Created]
```

### Single UUID Strategy
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