# Order Item Modifications Guide

## Overview
The Order module now supports comprehensive item-level modifications including:
- Size changes
- Topping additions/removals
- Ingredient customizations
- Special preparations
- Price adjustments

## Architecture

### Database Structure

#### `order_items` table
- `base_item_name`: Original item name
- `base_price`: Base price before modifiers
- `unit_price`: Final unit price with modifiers
- `modifiers_total`: Total price adjustment from modifiers
- `total_price`: Final total price
- `modifiers`: JSON array of active modifiers
- `modifier_history`: JSON array tracking all changes
- `modifier_count`: Number of active modifiers
- `special_instructions`: Customer special instructions

#### `order_item_modifiers` table
Detailed tracking of each modifier for reporting and analytics:
- Individual modifier records
- Price adjustments
- Kitchen notifications
- Audit trail

## Event Sourcing

### ItemModifiersChanged Event
Tracks individual item customizations with:
- Added modifiers
- Removed modifiers
- Updated modifiers
- Price changes
- Kitchen notifications

## Usage Examples

### 1. Adding Extra Toppings

```php
use Colame\Order\Aggregates\OrderAggregate;

// Add extra cheese to a pizza
$aggregate = OrderAggregate::retrieve($orderUuid);

$aggregate->modifyItemModifiers(
    orderItemId: 123,
    itemName: 'Margherita Pizza',
    addedModifiers: [
        [
            'id' => 'mod_cheese_001',
            'type' => 'topping',
            'name' => 'Extra Cheese',
            'action' => 'add',
            'priceAdjustment' => 150, // $1.50 in cents
            'quantity' => 1,
            'group' => 'Toppings',
            'affectsKitchen' => true,
        ]
    ],
    modifiedBy: 'waiter@restaurant.com',
    reason: 'Customer request'
);

$aggregate->persist();
```

### 2. Changing Size

```php
// Change from medium to large
$aggregate->modifyItemModifiers(
    orderItemId: 124,
    itemName: 'Coca Cola',
    addedModifiers: [
        [
            'id' => 'mod_size_large',
            'type' => 'size',
            'name' => 'Large',
            'action' => 'replace',
            'priceAdjustment' => 100, // $1.00 extra
            'quantity' => 1,
            'group' => 'Size',
            'affectsKitchen' => false,
        ]
    ],
    removedModifiers: ['mod_size_medium'], // Remove previous size
    modifiedBy: 'waiter@restaurant.com',
    reason: 'Size upgrade'
);
```

### 3. Removing Ingredients

```php
// Remove onions from a burger
$aggregate->modifyItemModifiers(
    orderItemId: 125,
    itemName: 'Classic Burger',
    addedModifiers: [
        [
            'id' => 'mod_no_onions',
            'type' => 'ingredient',
            'name' => 'No Onions',
            'action' => 'remove',
            'priceAdjustment' => 0, // No price change
            'quantity' => 1,
            'group' => 'Ingredients',
            'affectsKitchen' => true,
        ]
    ],
    modifiedBy: 'waiter@restaurant.com',
    reason: 'Customer allergy'
);
```

### 4. Complex Customization

```php
// Multiple modifications at once
$aggregate->modifyItemModifiers(
    orderItemId: 126,
    itemName: 'Chicken Sandwich',
    addedModifiers: [
        [
            'id' => 'mod_bacon',
            'type' => 'topping',
            'name' => 'Add Bacon',
            'action' => 'add',
            'priceAdjustment' => 200, // $2.00
            'quantity' => 2, // Double bacon
            'group' => 'Proteins',
            'affectsKitchen' => true,
        ],
        [
            'id' => 'mod_spicy',
            'type' => 'preparation',
            'name' => 'Make it Spicy',
            'action' => 'modify',
            'priceAdjustment' => 0,
            'quantity' => 1,
            'group' => 'Preparation',
            'affectsKitchen' => true,
            'metadata' => ['spice_level' => 'hot']
        ]
    ],
    removedModifiers: ['mod_mayo'], // No mayo
    modifiedBy: 'waiter@restaurant.com',
    reason: 'Customer preferences'
);
```

## Kitchen Display

Modifiers are formatted for kitchen display:
- `+ Extra Cheese` (addition)
- `NO Onions` (removal)
- `REPLACE WITH Large` (replacement)
- `2x Bacon` (quantity > 1)

## API Integration

### Adding Modifiers via API

```php
// In your controller
public function addItemModifier(Request $request, $orderId, $itemId)
{
    $order = Order::findOrFail($orderId);
    
    $modifierData = ItemModifierData::validateAndCreate($request);
    
    $aggregate = OrderAggregate::retrieve($order->uuid);
    
    $aggregate->modifyItemModifiers(
        orderItemId: $itemId,
        itemName: $request->item_name,
        addedModifiers: [$modifierData->toArray()],
        modifiedBy: $request->user()->email,
        reason: $request->reason ?? 'Customer request'
    );
    
    $aggregate->persist();
    
    return response()->json(['success' => true]);
}
```

## Reporting

### Get All Modifiers for an Item

```sql
SELECT * FROM order_item_modifiers 
WHERE order_item_id = ? 
AND status = 'active';
```

### Most Popular Modifiers

```sql
SELECT name, type, COUNT(*) as usage_count 
FROM order_item_modifiers 
GROUP BY name, type 
ORDER BY usage_count DESC;
```

### Revenue from Modifiers

```sql
SELECT 
    DATE(added_at) as date,
    SUM(total_price_adjustment) as modifier_revenue
FROM order_item_modifiers
WHERE status = 'active'
GROUP BY DATE(added_at);
```

## Best Practices

1. **Always track who made changes** - Use `modifiedBy` field
2. **Provide reasons** - Helps with customer service
3. **Group related modifiers** - Use the `group` field
4. **Set kitchen flags appropriately** - Not all changes need kitchen notification
5. **Use structured metadata** - Store additional context in metadata field
6. **Validate prices** - Ensure price adjustments are calculated correctly
7. **Handle conflicts** - Remove old size when adding new size

## Status Flow

1. Customer requests modification
2. Waiter/system adds modifier via event
3. Event is recorded (audit trail)
4. Projector updates database
5. Kitchen receives notification (if needed)
6. Order totals are recalculated
7. Receipt shows itemized modifiers

## Error Handling

The system prevents modifications when:
- Order is completed/cancelled
- Order is being delivered
- Item doesn't exist
- Invalid modifier data

## Future Enhancements

- Modifier templates
- Bulk modifier operations
- Modifier dependencies (e.g., can't add cheese if lactose-free selected)
- Time-based modifiers (breakfast vs dinner options)
- Modifier limits (max 3 toppings)
- Combo modifier pricing