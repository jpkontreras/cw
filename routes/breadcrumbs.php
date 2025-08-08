<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Dashboard (root breadcrumb)
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push('Dashboard', route('dashboard'));
});

// ====================
// Items Module
// ====================

// Items
Breadcrumbs::for('item.index', function (BreadcrumbTrail $trail) {
    $trail->push('Items', route('item.index'));
});

// Items > Create
Breadcrumbs::for('item.create', function (BreadcrumbTrail $trail) {
    $trail->parent('item.index');
    $trail->push('Create Item', route('item.create'));
});

// Items > Item Details
Breadcrumbs::for('item.show', function (BreadcrumbTrail $trail, $itemId) {
    $trail->parent('item.index');
    $trail->push('Item Details', route('item.show', $itemId));
});

// Items > Item Details > Edit
Breadcrumbs::for('item.edit', function (BreadcrumbTrail $trail, $itemId) {
    $trail->parent('item.show', $itemId);
    $trail->push('Edit', route('item.edit', $itemId));
});

// ====================
// Pricing Module
// ====================

// Pricing
Breadcrumbs::for('pricing.index', function (BreadcrumbTrail $trail) {
    $trail->push('Pricing Rules', route('pricing.index'));
});

// Pricing > Create
Breadcrumbs::for('pricing.create', function (BreadcrumbTrail $trail) {
    $trail->parent('pricing.index');
    $trail->push('Create Rule', route('pricing.create'));
});

// Pricing > Pricing Rule
Breadcrumbs::for('pricing.show', function (BreadcrumbTrail $trail, $priceRuleId) {
    $trail->parent('pricing.index');
    $trail->push('Pricing Rule', route('pricing.show', $priceRuleId));
});

// Pricing > Pricing Rule > Edit
Breadcrumbs::for('pricing.edit', function (BreadcrumbTrail $trail, $priceRuleId) {
    $trail->parent('pricing.show', $priceRuleId);
    $trail->push('Edit', route('pricing.edit', $priceRuleId));
});

// Pricing > Calculator
Breadcrumbs::for('pricing.calculator', function (BreadcrumbTrail $trail) {
    $trail->parent('pricing.index');
    $trail->push('Price Calculator', route('pricing.calculator'));
});

// Pricing > Bulk Update
Breadcrumbs::for('pricing.bulk-update', function (BreadcrumbTrail $trail) {
    $trail->parent('pricing.index');
    $trail->push('Bulk Update', route('pricing.bulk-update'));
});

// ====================
// Modifiers Module
// ====================

// Modifiers
Breadcrumbs::for('modifier.index', function (BreadcrumbTrail $trail) {
    $trail->push('Modifiers', route('modifier.index'));
});

// Modifiers > Create
Breadcrumbs::for('modifier.create', function (BreadcrumbTrail $trail) {
    $trail->parent('modifier.index');
    $trail->push('Create Modifier Group', route('modifier.create'));
});

// Modifiers > Modifier Group
Breadcrumbs::for('modifier.show', function (BreadcrumbTrail $trail, $modifierGroupId) {
    $trail->parent('modifier.index');
    $trail->push('Modifier Group', route('modifier.show', $modifierGroupId));
});

// Modifiers > Modifier Group > Edit
Breadcrumbs::for('modifier.edit', function (BreadcrumbTrail $trail, $modifierGroupId) {
    $trail->parent('modifier.show', $modifierGroupId);
    $trail->push('Edit', route('modifier.edit', $modifierGroupId));
});

// Modifiers > Bulk Assign
Breadcrumbs::for('modifier.bulk-assign', function (BreadcrumbTrail $trail) {
    $trail->parent('modifier.index');
    $trail->push('Bulk Assign', route('modifier.bulk-assign'));
});

// Modifiers > Modifier Group > Add Modifier
Breadcrumbs::for('modifier.modifier.create', function (BreadcrumbTrail $trail, $groupId) {
    $trail->parent('modifier.show', $groupId);
    $trail->push('Add Modifier', route('modifier.modifier.create', $groupId));
});

// ====================
// Inventory Module
// ====================

// Inventory
Breadcrumbs::for('inventory.index', function (BreadcrumbTrail $trail) {
    $trail->push('Inventory', route('inventory.index'));
});

// Inventory > Adjustments
Breadcrumbs::for('inventory.adjustments', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Adjustments', route('inventory.adjustments'));
});

// Inventory > Transfers
Breadcrumbs::for('inventory.transfers', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Transfers', route('inventory.transfers'));
});

// Inventory > Stock Take
Breadcrumbs::for('inventory.stock-take', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Stock Take', route('inventory.stock-take'));
});

// Inventory > Reorder Settings
Breadcrumbs::for('inventory.reorder-settings', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('Reorder Settings', route('inventory.reorder-settings'));
});

// Inventory > History
Breadcrumbs::for('inventory.history', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.index');
    $trail->push('History', route('inventory.history'));
});

// ====================
// Recipes Module
// ====================

// Recipes
Breadcrumbs::for('recipe.index', function (BreadcrumbTrail $trail) {
    $trail->push('Recipes', route('recipe.index'));
});

// Recipes > Create
Breadcrumbs::for('recipe.create', function (BreadcrumbTrail $trail) {
    $trail->parent('recipe.index');
    $trail->push('Create Recipe', route('recipe.create'));
});

// Recipes > Recipe Details
Breadcrumbs::for('recipe.show', function (BreadcrumbTrail $trail, $recipeId) {
    $trail->parent('recipe.index');
    $trail->push('Recipe Details', route('recipe.show', $recipeId));
});

// Recipes > Recipe Details > Edit
Breadcrumbs::for('recipe.edit', function (BreadcrumbTrail $trail, $recipeId) {
    $trail->parent('recipe.show', $recipeId);
    $trail->push('Edit', route('recipe.edit', $recipeId));
});

// Recipes > Production
Breadcrumbs::for('recipe.production', function (BreadcrumbTrail $trail) {
    $trail->parent('recipe.index');
    $trail->push('Production', route('recipe.production'));
});

// Recipes > Cost Analysis
Breadcrumbs::for('recipe.cost-analysis', function (BreadcrumbTrail $trail) {
    $trail->parent('recipe.index');
    $trail->push('Cost Analysis', route('recipe.cost-analysis'));
});

// ====================
// Orders Module
// ====================

// Orders
Breadcrumbs::for('orders.index', function (BreadcrumbTrail $trail) {
    $trail->push('Orders', route('orders.index'));
});

// Orders > Dashboard
Breadcrumbs::for('orders.dashboard', function (BreadcrumbTrail $trail) {
    $trail->parent('orders.index');
    $trail->push('Dashboard', route('orders.dashboard'));
});

// Orders > Create
Breadcrumbs::for('orders.create', function (BreadcrumbTrail $trail) {
    $trail->parent('orders.index');
    $trail->push('Create Order', route('orders.create'));
});

// Orders > Operations Center
Breadcrumbs::for('orders.operations', function (BreadcrumbTrail $trail) {
    $trail->parent('orders.index');
    $trail->push('Operations Center', route('orders.operations'));
});

// Orders > Kitchen Display
Breadcrumbs::for('orders.kitchen', function (BreadcrumbTrail $trail) {
    $trail->parent('orders.index');
    $trail->push('Kitchen Display', route('orders.kitchen'));
});

// Orders > Order Details
Breadcrumbs::for('orders.show', function (BreadcrumbTrail $trail, $orderId) {
    $trail->parent('orders.index');
    $trail->push('Order Details', route('orders.show', $orderId));
});

// Orders > Order Details > Edit
Breadcrumbs::for('orders.edit', function (BreadcrumbTrail $trail, $orderId) {
    $trail->parent('orders.show', $orderId);
    $trail->push('Edit', route('orders.edit', $orderId));
});

// Orders > Order Details > Payment
Breadcrumbs::for('orders.payment', function (BreadcrumbTrail $trail, $orderId) {
    $trail->parent('orders.show', $orderId);
    $trail->push('Payment', route('orders.payment', $orderId));
});

// Orders > Order Details > Receipt
Breadcrumbs::for('orders.receipt', function (BreadcrumbTrail $trail, $orderId) {
    $trail->parent('orders.show', $orderId);
    $trail->push('Receipt', route('orders.receipt', $orderId));
});

// Orders > Order Details > Cancel
Breadcrumbs::for('orders.cancel.form', function (BreadcrumbTrail $trail, $orderId) {
    $trail->parent('orders.show', $orderId);
    $trail->push('Cancel Order', route('orders.cancel.form', $orderId));
});

// ====================
// Location Module
// ====================

// Locations
Breadcrumbs::for('locations.index', function (BreadcrumbTrail $trail) {
    $trail->push('Locations', route('locations.index'));
});

// Locations > Create
Breadcrumbs::for('locations.create', function (BreadcrumbTrail $trail) {
    $trail->parent('locations.index');
    $trail->push('Create Location', route('locations.create'));
});

// Locations > Location Details
Breadcrumbs::for('locations.show', function (BreadcrumbTrail $trail, $locationId) {
    $trail->parent('locations.index');
    $trail->push('Location Details', route('locations.show', $locationId));
});

// Locations > Location Details > Edit
Breadcrumbs::for('locations.edit', function (BreadcrumbTrail $trail, $locationId) {
    $trail->parent('locations.show', $locationId);
    $trail->push('Edit', route('locations.edit', $locationId));
});

// Locations > Location Details > Users
Breadcrumbs::for('locations.users', function (BreadcrumbTrail $trail, $locationId) {
    $trail->parent('locations.show', $locationId);
    $trail->push('Users', route('locations.users', $locationId));
});

// Locations > Location Details > Settings
Breadcrumbs::for('locations.settings', function (BreadcrumbTrail $trail, $locationId) {
    $trail->parent('locations.show', $locationId);
    $trail->push('Settings', route('locations.settings', $locationId));
});

// ====================
// Menu Module
// ====================

// Menu
Breadcrumbs::for('menu.index', function (BreadcrumbTrail $trail) {
    $trail->push('Menu', route('menu.index'));
});

// Menu > Create
Breadcrumbs::for('menu.create', function (BreadcrumbTrail $trail) {
    $trail->parent('menu.index');
    $trail->push('Create', route('menu.create'));
});

// Menu > Menu Details
Breadcrumbs::for('menu.show', function (BreadcrumbTrail $trail, $menuId) {
    $trail->parent('menu.index');
    $trail->push('Menu Details', route('menu.show', $menuId));
});

// Menu > Menu Details > Edit
Breadcrumbs::for('menu.edit', function (BreadcrumbTrail $trail, $menuId) {
    $trail->parent('menu.show', $menuId);
    $trail->push('Edit', route('menu.edit', $menuId));
});

// Menu > Menu Builder (global)
Breadcrumbs::for('menu.builder.index', function (BreadcrumbTrail $trail) {
    $trail->parent('menu.index');
    $trail->push('Menu Builder', route('menu.builder.index'));
});

// Menu > Menu Details > Builder
Breadcrumbs::for('menu.builder', function (BreadcrumbTrail $trail, $menuId) {
    $trail->parent('menu.show', $menuId);
    $trail->push('Builder', route('menu.builder', $menuId));
});

// Menu > Menu Details > Preview
Breadcrumbs::for('menu.preview', function (BreadcrumbTrail $trail, $menuId) {
    $trail->parent('menu.show', $menuId);
    $trail->push('Preview', route('menu.preview', $menuId));
});

// ====================
// Settings Module
// ====================

// Settings > Profile
Breadcrumbs::for('profile.edit', function (BreadcrumbTrail $trail) {
    $trail->push('Settings', route('profile.edit'));
    $trail->push('Profile', route('profile.edit'));
});

// Settings > Password
Breadcrumbs::for('password.edit', function (BreadcrumbTrail $trail) {
    $trail->push('Settings', route('profile.edit'));
    $trail->push('Password', route('password.edit'));
});