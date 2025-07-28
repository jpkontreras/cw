// resources/js/types/modules/order/constants.ts

import { KitchenStatus, OrderStatus, OrderType, PaymentMethod, PaymentStatus } from './index';

// Status configurations
export const ORDER_STATUS_CONFIG: Record<
  OrderStatus,
  {
    label: string;
    color: string;
    icon?: string;
    next?: OrderStatus;
  }
> = {
  draft: {
    label: 'Draft',
    color: 'secondary',
    icon: 'edit',
    next: 'placed',
  },
  placed: {
    label: 'Placed',
    color: 'default',
    icon: 'shopping-cart',
    next: 'confirmed',
  },
  confirmed: {
    label: 'Confirmed',
    color: 'default',
    icon: 'check-circle',
    next: 'preparing',
  },
  preparing: {
    label: 'Preparing',
    color: 'warning',
    icon: 'clock',
    next: 'ready',
  },
  ready: {
    label: 'Ready',
    color: 'success',
    icon: 'check-circle',
    next: 'completed',
  },
  delivering: {
    label: 'Delivering',
    color: 'default',
    icon: 'truck',
    next: 'delivered',
  },
  delivered: {
    label: 'Delivered',
    color: 'success',
    icon: 'package-check',
    next: 'completed',
  },
  completed: {
    label: 'Completed',
    color: 'success',
    icon: 'check-circle-2',
  },
  cancelled: {
    label: 'Cancelled',
    color: 'destructive',
    icon: 'x-circle',
  },
  refunded: {
    label: 'Refunded',
    color: 'destructive',
    icon: 'rotate-ccw',
  },
};

// Order type configurations
export const ORDER_TYPE_CONFIG: Record<
  OrderType,
  {
    label: string;
    icon: string;
    requiresTable?: boolean;
    requiresDelivery?: boolean;
  }
> = {
  dine_in: {
    label: 'Dine In',
    icon: 'utensils',
    requiresTable: true,
  },
  takeout: {
    label: 'Takeout',
    icon: 'shopping-bag',
  },
  delivery: {
    label: 'Delivery',
    icon: 'truck',
    requiresDelivery: true,
  },
  catering: {
    label: 'Catering',
    icon: 'calendar',
  },
};

// Payment status configurations
export const PAYMENT_STATUS_CONFIG: Record<
  PaymentStatus,
  {
    label: string;
    color: string;
    icon: string;
  }
> = {
  pending: {
    label: 'Pending',
    color: 'warning',
    icon: 'clock',
  },
  partial: {
    label: 'Partially Paid',
    color: 'warning',
    icon: 'wallet',
  },
  paid: {
    label: 'Paid',
    color: 'success',
    icon: 'check-circle',
  },
  refunded: {
    label: 'Refunded',
    color: 'destructive',
    icon: 'rotate-ccw',
  },
};

// Kitchen status configurations
export const KITCHEN_STATUS_CONFIG: Record<
  KitchenStatus,
  {
    label: string;
    color: string;
    icon: string;
  }
> = {
  pending: {
    label: 'Pending',
    color: 'secondary',
    icon: 'clock',
  },
  preparing: {
    label: 'Preparing',
    color: 'warning',
    icon: 'chef-hat',
  },
  ready: {
    label: 'Ready',
    color: 'success',
    icon: 'check',
  },
  served: {
    label: 'Served',
    color: 'default',
    icon: 'utensils',
  },
};

// Payment method configurations
export const PAYMENT_METHOD_CONFIG: Record<
  PaymentMethod,
  {
    label: string;
    icon: string;
  }
> = {
  cash: {
    label: 'Cash',
    icon: 'banknote',
  },
  credit_card: {
    label: 'Credit Card',
    icon: 'credit-card',
  },
  debit_card: {
    label: 'Debit Card',
    icon: 'credit-card',
  },
  mobile_payment: {
    label: 'Mobile Payment',
    icon: 'smartphone',
  },
  gift_card: {
    label: 'Gift Card',
    icon: 'gift',
  },
  other: {
    label: 'Other',
    icon: 'wallet',
  },
};

// Course configurations
export const COURSE_CONFIG = {
  starter: { label: 'Starter', order: 1 },
  main: { label: 'Main Course', order: 2 },
  dessert: { label: 'Dessert', order: 3 },
  beverage: { label: 'Beverage', order: 4 },
};

// Priority configurations
export const PRIORITY_CONFIG = {
  normal: { label: 'Normal', color: 'default' },
  high: { label: 'High Priority', color: 'destructive', icon: 'alert-circle' },
};

// Validation rules
export const ORDER_VALIDATION = {
  minItems: 1,
  maxItems: 50,
  maxNoteLength: 500,
  maxSpecialInstructionsLength: 1000,
  phonePattern: /^[\d\s\-\+\(\)]+$/,
  emailPattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
};

// Default values
export const ORDER_DEFAULTS = {
  type: 'dine_in' as OrderType,
  priority: 'normal' as const,
  status: 'draft' as OrderStatus,
  paymentStatus: 'pending' as PaymentStatus,
  subtotal: 0,
  taxAmount: 0,
  tipAmount: 0,
  discountAmount: 0,
  totalAmount: 0,
};

// Tax rate (Chilean IVA)
export const TAX_RATE = 0.19; // 19%

// Tip suggestions
export const TIP_SUGGESTIONS = [10, 15, 20]; // percentages
