# Order Module

This module handles all order-related functionality in the Colame restaurant management system.

## Architecture

The order module follows the interface-based architecture pattern with:
- **Contracts**: Define public APIs for cross-module communication
- **DTOs**: Data Transfer Objects for type-safe data exchange
- **Repositories**: Handle data persistence following repository pattern
- **Services**: Contain all business logic
- **Controllers**: Separate Web (Inertia) and API controllers
- **Events**: Domain events for cross-module communication

## Key Features

- Order lifecycle management (Draft → Placed → Confirmed → Preparing → Ready → Completed)
- Order item management with modifiers
- Status tracking and history
- Kitchen display integration
- Feature flag support for gradual rollout
- Event-driven architecture

## Directory Structure

```
order/
├── config/
│   └── features.php          # Module feature flags
├── database/
│   ├── factories/           # Model factories for testing
│   └── migrations/          # Database migrations
├── routes/
│   └── order-routes.php     # Web and API routes
├── src/
│   ├── Contracts/           # Public interfaces
│   ├── Data/               # DTOs
│   ├── Events/             # Domain events
│   ├── Exceptions/         # Module exceptions
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Api/        # API controllers
│   │       └── Web/        # Web controllers
│   ├── Models/             # Eloquent models
│   ├── Providers/          # Service providers
│   ├── Repositories/       # Repository implementations
│   └── Services/           # Business logic
└── tests/                  # Module tests
```

## Usage

### Creating an Order

```php
use Colame\Order\Contracts\OrderServiceInterface;
use Colame\Order\Data\CreateOrderData;

$orderService = app(OrderServiceInterface::class);

$orderData = CreateOrderData::from([
    'userId' => 1,
    'locationId' => 1,
    'items' => [
        [
            'itemId' => 101,
            'quantity' => 2,
            'unitPrice' => 15.99,
            'modifiers' => [
                ['id' => 1, 'name' => 'Extra cheese', 'price' => 2.00]
            ]
        ]
    ],
    'customerName' => 'John Doe',
    'customerPhone' => '+1234567890'
]);

$order = $orderService->createOrder($orderData);
```

### Updating Order Status

```php
// Confirm order
$order = $orderService->confirmOrder($orderId);

// Start preparing
$order = $orderService->startPreparingOrder($orderId);

// Mark as ready
$order = $orderService->markOrderReady($orderId);

// Complete order
$order = $orderService->completeOrder($orderId);

// Cancel order
$order = $orderService->cancelOrder($orderId, 'Customer request');
```

## API Endpoints

### Web Routes
- `GET /orders` - List orders
- `GET /orders/create` - Show create form
- `POST /orders` - Create order
- `GET /orders/{id}` - Show order
- `GET /orders/{id}/edit` - Show edit form
- `PUT /orders/{id}` - Update order
- `POST /orders/{id}/confirm` - Confirm order
- `POST /orders/{id}/cancel` - Cancel order
- `GET /orders/kitchen/display` - Kitchen display

### API Routes
- `GET /api/v1/orders` - List orders
- `POST /api/v1/orders` - Create order
- `GET /api/v1/orders/{id}` - Get order
- `PUT /api/v1/orders/{id}` - Update order
- `PATCH /api/v1/orders/{id}/status` - Update status
- `POST /api/v1/orders/{id}/cancel` - Cancel order
- `GET /api/v1/orders/statistics/summary` - Get statistics

## Feature Flags

The module includes several feature flags for controlled rollout:

- `order.split_bill` - Allow splitting bills
- `order.order_notes` - Allow adding notes to orders
- `order.quick_order` - Quick order creation
- `order.kitchen_display` - Kitchen display system
- `order.order_tracking` - Real-time order tracking

Configure in `.env`:
```
FEATURE_ORDER_SPLIT_BILL=false
FEATURE_ORDER_KITCHEN_DISPLAY=true
```

## Events

The module dispatches the following events:
- `OrderCreated` - When a new order is created
- `OrderStatusChanged` - When order status changes

Other modules can listen to these events without direct dependencies.

## Testing

Run module tests:
```bash
sail artisan test --filter=Colame\\Order
```

## Database

Run migrations:
```bash
sail artisan migrate
```

The module creates three tables:
- `orders` - Main orders table
- `order_items` - Order line items
- `order_status_history` - Status change tracking