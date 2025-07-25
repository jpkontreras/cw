# Order Module Features Documentation

## Overview

The Order module is a comprehensive order management system for the Colame restaurant management platform. It provides full lifecycle management of orders from creation through completion, with support for multiple order types, real-time kitchen display, payment processing, and advanced analytics.

## Feature Flags

The module uses feature flags for controlled rollout of functionality:

### Currently Available Feature Flags

1. **`order.split_bill`** (Default: false)
   - Allows splitting bills between multiple payments
   - Rollout: 0% (percentage-based)
   - Enables the `splitOrder()` functionality

2. **`order.order_notes`** (Default: true)
   - Allows adding notes to orders and individual order items
   - Enabled globally

3. **`order.quick_order`** (Default: false)
   - Quick order creation for frequent items
   - Rollout: Location-based (empty by default)

4. **`order.order_modifications`** (Default: true)
   - Allows modifying orders after placement
   - Only works for orders in 'draft' or 'placed' status

5. **`order.kitchen_display`** (Default: true)
   - Kitchen display system for order management
   - Enables real-time kitchen view and order tracking

6. **`order.order_tracking`** (Default: false)
   - Real-time order tracking for customers
   - Rollout: 25% (percentage-based)

7. **`order.bulk_orders`** (Default: false)
   - Support for bulk order creation
   - Currently not implemented

8. **`order.order_templates`** (Default: false)
   - Save and reuse order templates
   - Currently not implemented

9. **`order.advanced_analytics`** (Default: false)
   - Advanced order analytics and reporting
   - Rollout: User-based (empty by default)

10. **`order.order_queue_management`** (Default: false)
    - Advanced queue management for kitchen operations
    - Currently not implemented

## Order Statuses and Workflow

### Order Status Flow

```
draft → placed → confirmed → preparing → ready → delivering → delivered → completed
                     ↓            ↓          ↓         ↓
                 cancelled    cancelled  cancelled  cancelled
```

### Available Statuses

1. **`draft`** - Initial status, order being created
2. **`placed`** - Order has been placed by customer
3. **`confirmed`** - Order confirmed by restaurant
4. **`preparing`** - Kitchen is preparing the order
5. **`ready`** - Order is ready for pickup/serving
6. **`delivering`** - Order is out for delivery (delivery orders only)
7. **`delivered`** - Order has been delivered (delivery orders only)
8. **`completed`** - Order successfully completed
9. **`cancelled`** - Order cancelled (requires reason)
10. **`refunded`** - Order has been refunded

### Status Transition Rules

- Orders can only be cancelled from: `draft`, `placed`, or `confirmed` status
- Orders can only be modified in: `draft` or `placed` status
- Delivery statuses (`delivering`, `delivered`) only available for delivery-type orders
- Going backwards in the flow requires a reason
- Each status transition triggers timestamp updates

## Order Types

1. **`dine_in`** - Restaurant dining
2. **`takeout`** - Customer pickup
3. **`delivery`** - Home delivery
4. **`catering`** - Catering services

## Core Features

### Order Creation and Management

- **Create Orders** with customer information, items, modifiers, and special instructions
- **Order Numbers** automatically generated in format: `ORD-YYYYMMDD-XXXX`
- **Multi-location Support** - Orders tied to specific locations
- **Table Management** - Assign orders to tables for dine-in
- **Waiter Assignment** - Track which staff member handles the order

### Order Items

- **Item Management** - Add multiple items with quantities
- **Modifiers Support** - Add item modifiers with price adjustments
- **Item Status Tracking** - Track individual item status (pending, preparing, prepared, served)
- **Kitchen Status** - Separate kitchen-specific status tracking
- **Course Types** - Categorize items by course (starter, main, dessert, beverage)
- **Item Notes** - Add specific notes per item

### Calculations and Pricing

- **Automatic Total Calculation** - Subtotal, tax, discount, and total
- **Tax Calculation** - Default 10% tax rate (configurable)
- **Modifier Pricing** - Automatic price adjustment for modifiers
- **Tip Support** - Add and calculate tips
- **Discount Application** - Apply percentage-based discounts
- **Commission Calculation** - Calculate delivery platform commissions
- **Estimated Preparation Time** - Calculate based on order complexity

### Payment Features

- **Payment Status Tracking** - pending, partial, paid, refunded
- **Multiple Payment Methods** - cash, card, transfer, other
- **Split Payments** - Split bill across multiple payment methods (when enabled)
- **Partial Payments** - Accept partial payments
- **Payment History** - Track all payment transactions
- **Tip Processing** - Handle tips during payment

### Validation Rules

- **Order Validation**
  - At least one item required
  - Maximum 100 items per order
  - Maximum 999 quantity per item
  - Valid customer phone format (7-15 digits)
  - Minimum order amount: $0.01

- **Status Validation**
  - Only valid status transitions allowed
  - Reason required for cancellations
  - Reason required for backward transitions

### Kitchen Features

- **Kitchen Display** - Real-time view of active orders
- **Order Queue** - View orders by status
- **Item Status Updates** - Update individual item status
- **Preparation Tracking** - Track when items are prepared
- **Service Tracking** - Track when items are served

### Analytics and Reporting

- **Order Statistics**
  - Total orders by period
  - Revenue metrics
  - Average order value
  - Completion rates
  - Active order counts

- **Dashboard Metrics**
  - Hourly order distribution
  - Order type distribution
  - Status distribution
  - Top selling items
  - Location performance
  - Staff performance

- **Time Period Filters**
  - Today, Yesterday, Week, Month, Quarter
  - Custom date ranges

### Advanced Features

- **Order Merging** - Merge multiple orders (planned)
- **Order Splitting** - Split order into multiple bills (when enabled)
- **Order Templates** - Save frequent orders as templates (planned)
- **Bulk Orders** - Create multiple orders at once (planned)
- **Queue Management** - Advanced kitchen queue optimization (planned)

## API Endpoints

### Web Routes (Inertia)

```
GET    /orders                     - List all orders
GET    /orders/dashboard           - Order analytics dashboard
GET    /orders/operations          - Operations center view
GET    /orders/kitchen             - Kitchen display
GET    /orders/create              - Create order form
POST   /orders                     - Store new order
GET    /orders/{id}                - View order details
GET    /orders/{id}/edit           - Edit order form
PUT    /orders/{id}                - Update order
POST   /orders/{id}/place          - Place order
POST   /orders/{id}/confirm        - Confirm order
POST   /orders/{id}/start-preparing - Start preparation
POST   /orders/{id}/mark-ready     - Mark as ready
POST   /orders/{id}/start-delivery - Start delivery
POST   /orders/{id}/mark-delivered - Mark as delivered
POST   /orders/{id}/complete       - Complete order
GET    /orders/{id}/cancel         - Cancel form
POST   /orders/{id}/cancel         - Cancel order
GET    /orders/{id}/payment        - Payment page
POST   /orders/{id}/payment/process - Process payment
GET    /orders/{id}/receipt        - View receipt
```

### API Routes (JSON)

```
GET    /api/v1/orders              - List orders
POST   /api/v1/orders              - Create order
GET    /api/v1/orders/{id}         - Get order
PUT    /api/v1/orders/{id}         - Update order
DELETE /api/v1/orders/{id}         - Delete order
PATCH  /api/v1/orders/{id}/status  - Update status
POST   /api/v1/orders/{id}/cancel  - Cancel order
PATCH  /api/v1/orders/{id}/items/{itemId}/status - Update item status
POST   /api/v1/orders/{id}/offers  - Apply offers
GET    /api/v1/orders/statistics/summary - Get statistics
```

## Events

The module emits the following domain events:

1. **`OrderCreated`** - Fired when a new order is created
   - Contains full order data
   - Other modules can react to new orders

2. **`OrderStatusChanged`** - Fired when order status changes
   - Contains order data, old status, and new status
   - Enables reactive workflows across modules

## Data Models

### Order Model
- **Identity**: order_number, id
- **Customer Info**: name, phone, email, delivery address
- **Location**: location_id, table_number
- **Staff**: user_id (creator), waiter_id
- **Financial**: subtotal, tax_amount, tip_amount, discount_amount, total_amount
- **Status**: status, payment_status, priority
- **Metadata**: notes, special_instructions, cancel_reason, metadata (JSON)
- **Timestamps**: placed_at, confirmed_at, preparing_at, ready_at, delivering_at, delivered_at, completed_at, cancelled_at, scheduled_at

### OrderItem Model
- **Identity**: id, order_id, item_id
- **Details**: item_name, quantity, unit_price, total_price
- **Status**: status, kitchen_status, course
- **Customization**: notes, modifiers (JSON), metadata (JSON)
- **Timestamps**: prepared_at, served_at

## Service Architecture

### Core Services

1. **OrderService** - Main business logic orchestration
2. **OrderCalculationService** - Price and total calculations
3. **OrderStatusService** - Status transition management
4. **OrderValidationService** - Business rule validation

### Repository Pattern
- Uses interface-based architecture for loose coupling
- Returns DTOs instead of Eloquent models
- Supports dependency injection

## Error Handling

### Exception Types
1. **OrderException** - Base exception for order-related errors
2. **OrderNotFoundException** - Order not found errors
3. **InvalidOrderStateException** - Invalid status transitions
4. **InvalidOrderException** - Validation failures

### Error Responses
- Structured error responses with codes and messages
- HTTP status codes for API responses
- User-friendly error messages for web interface

## Security Considerations

- Authentication required for all endpoints
- Location-based access control
- Role-based permissions (planned)
- Audit trail for status changes

## Performance Features

- Paginated order listings (20 per page default)
- Optimized queries with eager loading
- Caching for frequently accessed data
- Real-time updates via WebSockets (planned)

## Integration Points

The order module integrates with other modules through interfaces:

- **Item Module** - Product catalog and pricing
- **Location Module** - Multi-location support
- **User Module** - Staff and customer management
- **Offer Module** - Discounts and promotions
- **Payment Module** - Payment processing

## Future Enhancements

1. **Real-time Updates** - WebSocket support for live order tracking
2. **Advanced Analytics** - Detailed reporting and insights
3. **AI-powered Features** - Preparation time prediction, demand forecasting
4. **Mobile Optimization** - Native mobile app support
5. **Third-party Integrations** - Delivery platforms, POS systems
6. **Voice Ordering** - Voice-based order creation
7. **Customer Notifications** - SMS/Email order updates
8. **Loyalty Integration** - Points and rewards system