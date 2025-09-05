# Order Prefix Customization

## Current State
- Order prefix is hardcoded as "ORD-" in the system
- Configuration field `orderPrefix` exists in onboarding (`ConfigurationSetupData.php`)
- Setting `orders.prefix` is saved to database during onboarding
- Infrastructure to store and retrieve custom prefix is ready

## What Needs to be Completed

### 1. Order Number Generation
**File**: `app-modules/order/src/Aggregates/OrderAggregate.php`
- Update `generateOrderNumber()` method to:
  - Retrieve prefix from settings: `Setting::where('key', 'orders.prefix')->first()`
  - Use custom prefix or default to 'ORD' if not set
  - Format: `{prefix}-{sequence_number}`

### 2. Internationalization Support
**Files**: 
- `resources/lang/{locale}/order.php`
- Add translation keys:
  - `order.prefix.default` = 'ORD' (English), 'PED' (Spanish), 'CMD' (French)
  - `order.status.draft_indicator` = 'Draft', 'Borrador' (Spanish), 'Brouillon' (French)

### 3. Settings UI
**File**: `resources/js/pages/settings/orders.tsx`
- Add field for customizing order prefix
- Show preview of order number format
- Validation: Max 10 characters, alphanumeric only

### 4. Migration for Existing Data
- Create migration to set default 'ORD' prefix for existing installations
- Ensure backward compatibility

### 5. Order Display Components
**Files**:
- `resources/js/modules/order/utils/utils.ts`
- Update `formatOrderNumber()` to use dynamic prefix from settings
- Pass settings via props or context

## Implementation Priority
1. Backend order generation with settings lookup
2. Frontend display using the configured prefix
3. Settings UI for customization
4. Internationalization support
5. Migration for existing installations

## Testing Requirements
- Verify prefix is used in all order number displays
- Test with different locales
- Ensure prefix persists across system restarts
- Validate special characters handling