declare namespace App.Core.Data {
  export type BaseData = {};
}
declare namespace Colame.Item.Data {
  export type IngredientData = {
    id: number | null;
    name: string;
    unit: string;
    costPerUnit: number;
    supplierId: number | null;
    storageRequirements: string | null;
    shelfLifeDays: number | null;
    currentStock: number;
    reorderLevel: number;
    reorderQuantity: number;
    isActive: boolean;
    createdAt: string | null;
    updatedAt: string | null;
    deletedAt: string | null;
  };
  export type InventoryAdjustmentData = {
    id: number | null;
    itemId: number;
    variantId: number | null;
    locationId: number | null;
    quantityChange: number;
    adjustmentType: string;
    reason: string;
    notes: string | null;
    beforeQuantity: number;
    afterQuantity: number;
    userId: number | null;
    createdAt: string | null;
  };
  export type InventoryMovementData = {
    id: number | null;
    inventoriableType: string;
    inventoriableId: number;
    locationId: number;
    movementType: string;
    quantity: number;
    unitCost: number | null;
    beforeQuantity: number;
    afterQuantity: number;
    referenceType: string | null;
    referenceId: string | null;
    reason: string | null;
    userId: number | null;
    createdAt: string | null;
  };
  export type InventoryTransferData = {
    id: number | null;
    itemId: number;
    variantId: number | null;
    fromLocationId: number;
    toLocationId: number;
    quantity: number;
    notes: string | null;
    status: string;
    transferId: string | null;
    initiatedBy: number | null;
    completedBy: number | null;
    initiatedAt: string | null;
    completedAt: string | null;
  };
  export type ItemData = {
    id: number | null;
    name: string;
    slug: string;
    description: string | null;
    sku: string | null;
    barcode: string | null;
    basePrice: number | null;
    baseCost: number;
    preparationTime: number;
    isActive: boolean;
    isAvailable: boolean;
    isFeatured: boolean;
    trackInventory: boolean;
    stockQuantity: number;
    lowStockThreshold: number;
    type: string;
    allergens: Array<any> | null;
    nutritionalInfo: Array<any> | null;
    sortOrder: number;
    availableFrom: string | null;
    availableUntil: string | null;
    createdAt: string | null;
    updatedAt: string | null;
    deletedAt: string | null;
  };
  export type ItemImageData = {
    id: number | null;
    itemId: number;
    imagePath: string;
    thumbnailPath: string | null;
    altText: string | null;
    isPrimary: boolean;
    sortOrder: number;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type ItemLocationPriceData = {
    id: number | null;
    itemId: number;
    itemVariantId: number | null;
    locationId: number;
    price: number;
    currency: string;
    validFrom: string | null;
    validUntil: string | null;
    availableDays: Array<any> | null;
    availableFromTime: string | null;
    availableUntilTime: string | null;
    isActive: boolean;
    priority: number;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type ItemLocationStockData = {
    id: number | null;
    itemId: number;
    itemVariantId: number | null;
    locationId: number;
    quantity: number;
    reservedQuantity: number;
    reorderPoint: number;
    reorderQuantity: number;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type ItemModifierData = {
    id: number | null;
    modifierGroupId: number;
    name: string;
    priceAdjustment: number;
    maxQuantity: number;
    isDefault: boolean;
    isActive: boolean;
    sortOrder: number;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type ItemVariantData = {
    id: number | null;
    itemId: number;
    name: string;
    sku: string | null;
    priceAdjustment: number;
    sizeMultiplier: number;
    isDefault: boolean;
    isActive: boolean;
    stockQuantity: number;
    sortOrder: number;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type ItemWithRelationsData = {
    item: Colame.Item.Data.ItemData;
    variants: Array<any> | null;
    modifierGroups: Array<any> | null;
    images: Array<any> | null;
    categories: Array<any> | null;
    tags: Array<any> | null;
    recipe: Colame.Item.Data.RecipeData | null;
    currentPrice: Colame.Item.Data.ItemLocationPriceData | null;
    childItems: Array<any> | null;
    stockInfo: Colame.Item.Data.ItemLocationStockData | null;
  };
  export type ModifierGroupData = {
    id: number | null;
    name: string;
    description: string | null;
    selectionType: string;
    isRequired: boolean;
    minSelections: number;
    maxSelections: number | null;
    isActive: boolean;
    createdAt: string | null;
    updatedAt: string | null;
    deletedAt: string | null;
  };
  export type ModifierGroupWithModifiersData = {
    modifierGroup: Colame.Item.Data.ModifierGroupData;
    modifiers: Array<any>;
    sortOrder: number;
  };
  export type ModifierPriceImpactData = {
    modifierId: number;
    modifierName: string;
    modifierGroupId: number;
    modifierGroupName: string;
    quantity: number;
    unitPrice: number;
    priceImpact: number;
  };
  export type PriceCalculationData = {
    itemId: number;
    variantId: number | null;
    locationId: number | null;
    basePrice: number;
    variantAdjustment: number;
    modifierAdjustments: Array<any>;
    locationPrice: number | null;
    subtotal: number;
    total: number;
    currency: string;
    appliedRules: Array<any>;
  };
  export type RecipeData = {
    id: number | null;
    itemId: number;
    itemVariantId: number | null;
    instructions: string;
    prepTimeMinutes: number;
    cookTimeMinutes: number;
    yieldQuantity: number;
    yieldUnit: string;
    notes: string | null;
    ingredients: Array<any> | null;
    totalCost: number | null;
    costPerUnit: number | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type RecipeIngredientData = {
    id: number | null;
    recipeId: number;
    ingredientId: number;
    quantity: number;
    unit: string;
    isOptional: boolean;
    ingredient: Colame.Item.Data.IngredientData | null;
  };
  export type StockAlertData = {
    itemId: number;
    variantId: number | null;
    locationId: number | null;
    itemName: string;
    currentQuantity: number;
    minQuantity: number;
    alertType: string;
    suggestedReorderQuantity: number | null;
  };
}
declare namespace Colame.Location.Data {
  export type CreateLocationData = {
    code: string | null;
    name: string;
    type: string;
    status: string;
    address: string;
    city: string;
    state: string | null;
    country: string;
    postalCode: string | null;
    phone: string | null;
    email: string | null;
    timezone: string;
    currency: string;
    openingHours: Array<any> | null;
    deliveryRadius: number | null;
    capabilities: Array<any>;
    parentLocationId: number | null;
    managerId: number | null;
    metadata: Array<any> | null;
    isDefault: boolean;
  };
  export type LocationData = {
    id: number;
    code: string;
    name: string;
    type: string;
    status: string;
    address: string;
    city: string;
    state: string | null;
    country: string;
    postalCode: string | null;
    phone: string | null;
    email: string | null;
    timezone: string;
    currency: string;
    openingHours: Array<any> | null;
    deliveryRadius: number | null;
    capabilities: Array<any>;
    parentLocationId: number | null;
    managerId: number | null;
    metadata: Array<any> | null;
    isDefault: boolean;
    parentLocation: Colame.Location.Data.LocationData | null;
    childLocations: any | any | null;
    managerName: string | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type LocationOperatingHoursData = {
    open: string;
    close: string;
    isClosed: boolean;
  };
  export type LocationSettingsData = {
    id: number;
    locationId: number;
    key: string;
    value: string | null;
    type: string;
    description: string | null;
    isEncrypted: boolean;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type LocationUserData = {
    id: number;
    name: string;
    email: string;
    role: string;
    isPrimary: boolean;
    assignedAt: string | null;
  };
  export type LocationWithRelationsData = {
    id: number;
    code: string;
    name: string;
    type: string;
    status: string;
    address: string;
    city: string;
    state: string | null;
    country: string;
    postalCode: string | null;
    phone: string | null;
    email: string | null;
    timezone: string;
    currency: string;
    openingHours: Array<any> | null;
    deliveryRadius: number | null;
    capabilities: Array<any>;
    parentLocationId: number | null;
    managerId: number | null;
    metadata: Array<any> | null;
    isDefault: boolean;
    parentLocation: Colame.Location.Data.LocationData | null;
    childLocations: any | any | null;
    managerName: string | null;
    users: any | any | null;
    settings: any | any | null;
    totalUsers: number | null;
    activeOrders: number | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type UpdateLocationData = {
    code: any | string;
    name: any | string;
    type: any | string;
    status: any | string;
    address: any | string;
    city: any | string;
    state: any | string | null;
    country: any | string;
    postalCode: any | string | null;
    phone: any | string | null;
    email: any | string | null;
    timezone: any | string;
    currency: any | string;
    openingHours: any | Array<any> | null;
    deliveryRadius: any | number | null;
    capabilities: any | Array<any>;
    parentLocationId: any | number | null;
    managerId: any | number | null;
    metadata: any | Array<any> | null;
    isDefault: any | boolean;
  };
}
declare namespace Colame.Location.Enums {
  export type LocationType = 'restaurant' | 'kiosk' | 'food_truck' | 'cloud_kitchen' | 'delivery_only' | 'franchise' | 'headquarters' | 'warehouse';
}
declare namespace Colame.Onboarding.Data {
  export type AccountSetupData = {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
    nationalId: string;
    password: string | null;
    passwordConfirmation: string | null;
    primaryRole: string;
    employeeCode: string | null;
  };
  export type BusinessSetupData = {
    businessName: string;
    legalName: string | null;
    taxId: string | null;
    businessType: string;
    businessEmail: string | null;
    businessPhone: string | null;
    website: string | null;
    description: string | null;
    fax: string | null;
    establishedDate: string | null;
    numberOfEmployees: number | null;
  };
  export type CompleteOnboardingData = {
    account: Colame.Onboarding.Data.AccountSetupData;
    business: Colame.Onboarding.Data.BusinessSetupData;
    location: Colame.Onboarding.Data.LocationSetupData;
    configuration: Colame.Onboarding.Data.ConfigurationSetupData;
  };
  export type ConfigurationSetupData = {
    dateFormat: string;
    timeFormat: string;
    language: string;
    currency: string;
    timezone: string;
    decimalSeparator: string;
    thousandsSeparator: string;
    firstDayOfWeek: number;
    orderPrefix: string | null;
    requireCustomerPhone: boolean;
    printAutomatically: boolean;
    autoConfirmOrders: boolean;
    enableTips: boolean;
    tipOptions: Array<any>;
    emailNotifications: boolean;
    smsNotifications: boolean;
    pushNotifications: boolean;
    logoUrl: string | null;
    primaryColor: string | null;
    secondaryColor: string | null;
    useTemplate: boolean;
    templateType: string | null;
    createSampleMenu: boolean;
    createSampleCategories: boolean;
  };
  export type LocationSetupData = {
    name: string;
    code: string | null;
    type: string | null;
    address: string | null;
    addressLine2: string | null;
    city: string | null;
    state: string | null;
    country: string;
    postalCode: string | null;
    phone: string;
    email: string | null;
    timezone: string;
    currency: string;
    capabilities: Array<any>;
    openingHours: Array<any> | null;
    paymentMethods: Array<any> | null;
    deliveryRadius: number | null;
    seatingCapacity: number | null;
    kitchenCapabilities: Array<any>;
    taxRate: number | null;
    taxIncluded: boolean;
    serviceCharge: number | null;
    isDefault: boolean;
    status: string;
  };
  export type OnboardingProgressData = {
    id: number | null;
    userId: number;
    step: string;
    completedSteps: Array<any>;
    data: Array<any> | null;
    isCompleted: boolean;
    completedAt: string | null;
    skipReason: string | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
}
declare namespace Colame.Order.Data {
  export type CreateOrderData = {
    locationId: number;
    type: string;
    items: any;
    userId: number | null;
    tableNumber: number | null;
    customerName: string | null;
    customerPhone: string | null;
    customerEmail: string | null;
    deliveryAddress: string | null;
    notes: string | null;
    specialInstructions: string | null;
    offerCodes: Array<any> | null;
    metadata: Array<any> | null;
  };
  export type CreateOrderItemData = {
    itemId: number;
    quantity: number;
    unitPrice: number;
    notes: string | null;
    modifiers: Array<any> | null;
    metadata: Array<any> | null;
  };
  export type OrderData = {
    id: number;
    orderNumber: string;
    userId: number | null;
    locationId: number;
    status: string;
    type: string;
    priority: string;
    subtotal: number;
    taxAmount: number;
    tipAmount: number;
    discountAmount: number;
    totalAmount: number;
    paymentStatus: string;
    customerName: string | null;
    customerPhone: string | null;
    customerEmail: string | null;
    deliveryAddress: string | null;
    tableNumber: number | null;
    waiterId: number | null;
    notes: string | null;
    specialInstructions: string | null;
    cancelReason: string | null;
    metadata: Array<any> | null;
    items: any | any | null;
    placedAt: string | null;
    confirmedAt: string | null;
    preparingAt: string | null;
    readyAt: string | null;
    deliveringAt: string | null;
    deliveredAt: string | null;
    completedAt: string | null;
    cancelledAt: string | null;
    scheduledAt: string | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type OrderItemData = {
    id: number;
    orderId: number;
    itemId: number;
    itemName: string;
    quantity: number;
    unitPrice: number;
    totalPrice: number;
    status: string;
    kitchenStatus: string;
    course: string | null;
    notes: string | null;
    modifiers: Array<any> | null;
    metadata: Array<any> | null;
    preparedAt: string | null;
    servedAt: string | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type OrderStatusHistoryData = {
    id: number;
    orderId: number;
    fromStatus: string;
    toStatus: string;
    userId: number | null;
    reason: string | null;
    metadata: Array<any> | null;
    createdAt: string | null;
  };
  export type OrderWithRelationsData = {
    order: Colame.Order.Data.OrderData;
    user: object | null;
    location: object | null;
    payments: Array<any> | null;
    offers: Array<any> | null;
  };
  export type PaymentTransactionData = {
    id: number;
    orderId: number;
    method: string;
    amount: number;
    status: string;
    referenceNumber: string | null;
    processorResponse: Array<any> | null;
    processedAt: string | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type UpdateOrderData = {
    notes: any | string;
    customerName: any | string;
    customerPhone: any | string;
    metadata: any | Array<any>;
    items: any | Array<any>;
  };
}
declare namespace Colame.Settings.Data {
  export type BulkUpdateSettingData = {
    settings: Array<any>;
    category: Colame.Settings.Enums.SettingCategory | null;
    validateBeforeUpdate: boolean;
  };
  export type OrganizationSettingsData = {
    businessName: string;
    legalName: string | null;
    taxId: string | null;
    email: string;
    phone: string;
    fax: string | null;
    website: string | null;
    address: string;
    addressLine2: string | null;
    city: string;
    state: string;
    postalCode: string;
    country: string;
    currency: string;
    timezone: string;
    dateFormat: string;
    timeFormat: string;
    logoUrl: string | null;
  };
  export type SettingData = {
    id: number | null;
    key: string;
    value: any;
    type: Colame.Settings.Enums.SettingType;
    category: Colame.Settings.Enums.SettingCategory;
    label: string;
    description: string | null;
    group: string | null;
    options: Array<any> | null;
    validation: Array<any> | null;
    defaultValue: any;
    isRequired: boolean;
    isPublic: boolean;
    isEncrypted: boolean;
    sortOrder: number | null;
    metadata: Array<any> | null;
    createdAt: string | null;
    updatedAt: string | null;
  };
  export type SettingGroupData = {
    category: Colame.Settings.Enums.SettingCategory;
    label: string;
    description: string;
    icon: string;
    settings: any;
    totalSettings: number;
    configuredSettings: number;
    isComplete: boolean;
  };
  export type SettingValidationResultData = {
    isValid: boolean;
    errors: Array<any>;
    warnings: Array<any>;
    validatedSettings: Array<any>;
  };
}
declare namespace Colame.Settings.Enums {
  export type SettingCategory =
    | 'organization'
    | 'order'
    | 'receipt'
    | 'inventory'
    | 'notification'
    | 'integration'
    | 'payment'
    | 'tax'
    | 'localization'
    | 'printing'
    | 'security'
    | 'appearance';
  export type SettingType =
    | 'string'
    | 'integer'
    | 'float'
    | 'boolean'
    | 'json'
    | 'array'
    | 'date'
    | 'datetime'
    | 'time'
    | 'file'
    | 'color'
    | 'select'
    | 'multiselect'
    | 'encrypted';
}
declare namespace Colame.Staff.Enums {
  export type AttendanceStatus = 'present' | 'late' | 'absent' | 'holiday' | 'leave' | 'half_day';
  export type ClockMethod = 'biometric' | 'pin' | 'mobile' | 'manual' | 'card' | 'facial';
  export type ShiftStatus = 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'no_show';
  export type StaffStatus = 'active' | 'inactive' | 'suspended' | 'terminated' | 'on_leave';
}
