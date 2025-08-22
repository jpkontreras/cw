# Offer Module

A comprehensive promotional offers and discounts management system for the Colame restaurant platform.

## Features

### Core Functionality
- **Multiple Offer Types**: Percentage, Fixed Amount, Buy X Get Y, Combo Deals, Happy Hour, Early Bird, Loyalty Rewards, Staff Discounts
- **Advanced Scheduling**: Date ranges, recurring schedules, day/time restrictions
- **Smart Validation**: Minimum amounts, usage limits, customer segments, location-specific
- **Automatic Application**: Auto-apply eligible offers or require promo codes
- **Offer Stacking**: Support for combining multiple offers with priority rules
- **Analytics & Reporting**: Track usage, ROI, and performance metrics

## Architecture

Following the CLAUDE.md guidelines, this module implements:

- **Interface-Based Design**: All components communicate through contracts
- **DTO Pattern**: Using laravel-data for validation and data transfer
- **Repository Pattern**: Data access layer returns DTOs exclusively
- **Service Layer**: Business logic shared between Web and API controllers
- **Feature Flags**: Granular control over module features

## Structure

```
app-modules/offer/
├── src/
│   ├── Contracts/          # Public interfaces
│   ├── Data/              # DTOs (laravel-data)
│   ├── Models/            # Eloquent models
│   ├── Repositories/      # Repository implementations
│   ├── Services/          # Business logic
│   └── Http/Controllers/  # Web & API controllers
├── database/
│   ├── migrations/        # Database migrations
│   └── seeders/          # Test data seeders
├── routes/               # Module routes
├── config/               # Feature flags
└── tests/                # Module tests
```

## Installation

The module is auto-registered via InterNACHI/modular. Run migrations:

```bash
sail artisan migrate
```

Optionally seed test data:

```bash
sail artisan db:seed --class="Colame\\Offer\\Database\\Seeders\\OfferSeeder"
```

## API Endpoints

### Web Routes (Inertia)
- `GET /offers` - List all offers
- `GET /offers/create` - Create offer form
- `POST /offers` - Store new offer
- `GET /offers/{id}` - View offer details
- `GET /offers/{id}/edit` - Edit offer form
- `PUT /offers/{id}` - Update offer
- `DELETE /offers/{id}` - Delete offer
- `POST /offers/{id}/duplicate` - Duplicate offer
- `POST /offers/{id}/activate` - Activate offer
- `POST /offers/{id}/deactivate` - Deactivate offer
- `GET /offers/{id}/analytics` - View analytics
- `POST /offers/bulk-action` - Bulk operations

### API Routes (JSON)
All web routes plus:
- `POST /api/offers/validate` - Validate offer for order
- `POST /api/offers/apply` - Apply offer to order
- `POST /api/offers/apply-best` - Auto-apply best offer
- `POST /api/offers/available` - Get available offers for order
- `POST /api/offers/check-code` - Validate promo code

## Usage Examples

### Creating an Offer

```php
use Colame\Offer\Contracts\OfferServiceInterface;

$service = app(OfferServiceInterface::class);

$offer = $service->createOffer([
    'name' => 'Weekend Special',
    'type' => 'percentage',
    'value' => 20,
    'validDays' => ['saturday', 'sunday'],
    'isActive' => true,
]);
```

### Applying Offers to Orders

```php
// Get available offers for an order
$offers = $service->getAvailableOffersForOrder([
    'total_amount' => 100,
    'location_id' => 1,
    'item_ids' => [1, 2, 3],
]);

// Apply best offer automatically
$appliedOffer = $service->applyBestOfferToOrder([
    'total_amount' => 100,
    'customer_id' => 123,
]);

// Apply specific offer
$appliedOffer = $service->applyOfferToOrder($offerId, $orderData);
```

### Calculating Discounts

```php
use Colame\Offer\Contracts\OfferCalculatorInterface;

$calculator = app(OfferCalculatorInterface::class);

$calculation = $calculator->calculate($offer, $orderData);
// Returns: DiscountCalculationData with amount, final price, etc.
```

## Testing

Run module tests:

```bash
sail artisan test --filter=OfferServiceTest
```

## Configuration

Feature flags in `config/features/offer.php`:

- `offer.management` - Core offer management
- `offer.advanced_scheduling` - Recurring schedules
- `offer.analytics` - Usage analytics
- `offer.auto_application` - Auto-apply offers
- `offer.stacking` - Allow offer combinations
- `offer.code_redemption` - Promo code support
- `offer.customer_segments` - Customer targeting
- `offer.location_specific` - Location-based offers
- `offer.bulk_operations` - Bulk actions
- `offer.api_access` - API endpoints

## Dependencies

This module can optionally integrate with:
- **Item Module**: Target specific products
- **Menu Module**: Target menu categories
- **Order Module**: Apply offers during checkout
- **Location Module**: Location-specific offers
- **Staff Module**: Staff discount validation

All dependencies use interface-based injection for loose coupling.