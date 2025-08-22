# Onboarding Module

This module provides a comprehensive onboarding flow for new users of the Colame restaurant management system.

## Features

- Multi-step onboarding wizard
- Progress tracking and persistence
- Account, business, location, and configuration setup
- API endpoints for mobile apps
- Middleware for enforcing onboarding completion
- Skip functionality (configurable)

## Installation

The module is automatically registered via the ServiceProvider.

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Enable/disable onboarding module
ONBOARDING_ENABLED=true

# Automatically redirect users to onboarding if not completed
ONBOARDING_AUTO_REDIRECT=false

# Allow users to skip onboarding
ONBOARDING_SKIP_ALLOWED=false
```

## Usage

### Automatic Redirect (Global)

To automatically redirect all authenticated users who haven't completed onboarding:

1. Set `ONBOARDING_AUTO_REDIRECT=true` in your `.env` file
2. The middleware will be automatically added to the web middleware group

### Manual Middleware Usage

Apply the middleware to specific routes or groups:

```php
// In your routes file
Route::middleware(['auth', 'onboarding.complete'])->group(function () {
    // Routes that require onboarding to be completed
});
```

### Or in Laravel 12's bootstrap/app.php

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        // ... other middleware
        \Colame\Onboarding\Http\Middleware\EnsureOnboardingCompleted::class,
    ]);
})
```

## API Endpoints

### Web Routes (Inertia)
- `GET /onboarding` - Overview page
- `GET /onboarding/account` - Account setup
- `POST /onboarding/account` - Save account data
- `GET /onboarding/business` - Business setup
- `POST /onboarding/business` - Save business data
- `GET /onboarding/location` - Location setup
- `POST /onboarding/location` - Save location data
- `GET /onboarding/configuration` - Configuration setup
- `POST /onboarding/configuration` - Save configuration data
- `GET /onboarding/review` - Review all information
- `POST /onboarding/complete` - Complete onboarding
- `POST /onboarding/skip` - Skip onboarding (if allowed)

### API Routes (JSON)
- `GET /api/onboarding/progress` - Get current progress
- `POST /api/onboarding/account` - Process account step
- `POST /api/onboarding/business` - Process business step
- `POST /api/onboarding/location` - Process location step
- `POST /api/onboarding/configuration` - Process configuration step
- `POST /api/onboarding/complete` - Complete onboarding
- `POST /api/onboarding/skip` - Skip onboarding
- `POST /api/onboarding/reset` - Reset onboarding progress

## Service Methods

```php
use Colame\Onboarding\Contracts\OnboardingServiceInterface;

// Check if user needs onboarding
$service->needsOnboarding($userId);

// Get current progress
$progress = $service->getProgress($userId);

// Get next step
$nextStep = $service->getNextStep($userId);

// Complete onboarding
$data = $service->completeOnboarding($userId);

// Skip onboarding
$service->skipOnboarding($userId, 'reason');

// Reset onboarding
$service->resetOnboarding($userId);
```

## Customization

### Adding Custom Steps

1. Add step configuration in `config/features.php`
2. Create corresponding DTOs in `src/Data/`
3. Update the service to handle the new step
4. Create React components in `resources/js/pages/onboarding/`
5. Add routes in `routes/web.php` and `routes/api.php`

### Modifying Validation Rules

Edit the validation rules in the DTO classes or in the seeder for database-driven validation.

## Testing

Run the module tests:

```bash
sail artisan test --filter Onboarding
```

## Database

The module creates two tables:
- `onboarding_progress` - Tracks user progress
- `onboarding_configurations` - Stores step configurations

Run migrations:
```bash
sail artisan migrate
```

Run seeders:
```bash
sail artisan db:seed --class="Colame\\Onboarding\\Database\\Seeders\\OnboardingSeeder"
```