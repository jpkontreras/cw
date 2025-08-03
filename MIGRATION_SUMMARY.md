# Laravel Data Migration Summary

## Overview
This document summarizes the migration of the Order module to comply with Laravel Data patterns and the subsequent fixes for data display issues.

## Key Changes Made

### 1. Order Module Laravel Data Compliance

#### Data Objects Updated
- **OrderData**: 
  - Converted methods to computed properties using `#[Computed]` attribute
  - Added lazy loading for items collection using `Lazy::whenLoaded()`
  - Added `fromModel()` method for proper model transformation
  - Added computed properties for `itemsCount()` and `items()`

- **CreateOrderData**:
  - Added `#[MapInputName]` attributes for field mapping during validation
  - Removed validation attributes in favor of `rules()` method
  - Made table number optional for dine_in orders
  - Fixed validation to handle camelCase input with snake_case validation rules

- **CreateOrderItemData**:
  - Added `#[MapInputName('itemId')]` for proper field mapping

#### Repository Pattern Implementation
- Updated OrderRepository to:
  - Always load items relation with orders
  - Return `DataCollection` instead of arrays
  - Use `::collect()` method instead of deprecated `::collection()`
  - Implement all BaseRepositoryInterface methods

- Fixed related repositories:
  - ItemRepository
  - PricingRepository
  - InventoryRepository
  - ModifierRepository
  - RecipeRepository

#### Controller Updates
- Replaced Laravel's `validate()` with Laravel Data's `validateAndCreate()`
- Updated to handle proper ValidationException namespace
- Convert Data objects to arrays before passing to Inertia
- Added proper null checks for order data

### 2. Data Display Issues Resolution

#### Configuration Change
- Updated `config/data.php` to set output mapping to `null`:
  ```php
  'name_mapping_strategy' => [
      'input' => SnakeCaseMapper::class,
      'output' => null,  // Changed from SnakeCaseMapper::class
  ],
  ```
  This ensures Data objects output properties in camelCase as defined in the PHP classes.

#### Frontend Updates
- Updated item index page to use camelCase properties:
  - `base_price` → `basePrice`
  - `is_available` → `isAvailable`
  - `track_stock` → `trackInventory`
  - `current_stock` → `stockQuantity`
  - `category_name` → `categoryName`
  - Stats properties also updated to camelCase

### 3. Bug Fixes

#### Validation Field Mapping
- Fixed "field not found" errors by adding `#[MapInputName]` attributes
- Laravel Data validates BEFORE mapping, so explicit mapping is required

#### Price Display
- Fixed null price causing TypeError by adding null coalescing in ItemRepository
- Updated frontend to properly handle null prices with fallback to dash (—)

#### Kitchen Display System
- Clarified that KDS only shows orders with status: confirmed, preparing, or ready
- Orders need to be confirmed to appear in the kitchen display

## Technical Insights

### Laravel Data Validation Flow
1. Validation happens BEFORE property name mapping
2. Use `#[MapInputName]` to map frontend field names to validation rules
3. `validateAndCreate()` handles both validation and DTO creation

### Data Serialization
1. Laravel Data respects the output name mapping configuration
2. Setting output mapping to `null` preserves original property names (camelCase)
3. Always convert Data objects to arrays before passing to Inertia

### Key Patterns Established
1. **No Form Requests**: Use Data object validation
2. **No Manual Mapping**: Let Laravel Data handle snake_case ↔ camelCase
3. **Lazy Loading**: Use `Lazy::whenLoaded()` for relations
4. **Computed Properties**: Use `#[Computed]` instead of methods
5. **Repository Returns**: Always return Data objects, not Eloquent models

## Lessons Learned
1. Laravel Data's validation occurs before field mapping - this is crucial for proper validation
2. The output mapping configuration affects how data is serialized to frontend
3. Consistency between backend (camelCase) and frontend expectations is critical
4. Always load necessary relations to avoid lazy loading issues

## Next Steps
- Apply similar patterns to other modules
- Consider creating shared traits for common Data object patterns
- Document the validation field mapping pattern for team reference