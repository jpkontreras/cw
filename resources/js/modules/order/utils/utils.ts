// resources/js/types/modules/order/utils.ts

import { KITCHEN_STATUS_CONFIG, ORDER_STATUS_CONFIG, ORDER_TYPE_CONFIG, PAYMENT_STATUS_CONFIG, TAX_RATE } from '../constants/constants';
import { KitchenStatus, Order, OrderItem, OrderStatus, OrderType, PaymentStatus } from '../types/types';

// Status utilities
export const getStatusColor = (status: OrderStatus): string => {
  return ORDER_STATUS_CONFIG[status]?.color || 'default';
};

export const getStatusLabel = (status: OrderStatus): string => {
  return ORDER_STATUS_CONFIG[status]?.label || status;
};

export const getStatusIcon = (status: OrderStatus): string => {
  return ORDER_STATUS_CONFIG[status]?.icon || 'circle';
};

export const getNextStatus = (currentStatus: OrderStatus): OrderStatus | null => {
  return ORDER_STATUS_CONFIG[currentStatus]?.next || null;
};

export const canTransitionToStatus = (fromStatus: OrderStatus, toStatus: OrderStatus): boolean => {
  const nextStatus = getNextStatus(fromStatus);
  if (!nextStatus) return false;

  // Allow direct transition to next status
  if (nextStatus === toStatus) return true;

  // Allow cancellation from certain statuses
  if (toStatus === 'cancelled' && ['draft', 'placed', 'confirmed'].includes(fromStatus)) return true;

  // Allow direct transition to completed from ready
  if (fromStatus === 'ready' && toStatus === 'completed') return true;

  return false;
};

// Order type utilities
export const getTypeLabel = (type: OrderType): string => {
  return ORDER_TYPE_CONFIG[type]?.label || type;
};

export const getTypeIcon = (type: OrderType): string => {
  return ORDER_TYPE_CONFIG[type]?.icon || 'circle';
};

export const requiresTable = (type: OrderType): boolean => {
  return ORDER_TYPE_CONFIG[type]?.requiresTable || false;
};

export const requiresDelivery = (type: OrderType): boolean => {
  return ORDER_TYPE_CONFIG[type]?.requiresDelivery || false;
};

// Payment utilities
export const getPaymentStatusColor = (status: PaymentStatus): string => {
  return PAYMENT_STATUS_CONFIG[status]?.color || 'default';
};

export const getPaymentStatusLabel = (status: PaymentStatus): string => {
  return PAYMENT_STATUS_CONFIG[status]?.label || status;
};

// Kitchen utilities
export const getKitchenStatusColor = (status: KitchenStatus): string => {
  return KITCHEN_STATUS_CONFIG[status]?.color || 'default';
};

export const getKitchenStatusLabel = (status: KitchenStatus): string => {
  return KITCHEN_STATUS_CONFIG[status]?.label || status;
};

// Order calculations
export const calculateSubtotal = (items: OrderItem[]): number => {
  return items.reduce((sum, item) => sum + item.totalPrice, 0);
};

export const calculateTax = (subtotal: number): number => {
  return Math.round(subtotal * TAX_RATE * 100) / 100;
};

export const calculateTotal = (subtotal: number, tax: number, tip: number = 0, discount: number = 0): number => {
  return Math.round((subtotal + tax + tip - discount) * 100) / 100;
};

export const calculateTipAmount = (subtotal: number, percentage: number): number => {
  return Math.round(subtotal * (percentage / 100) * 100) / 100;
};

// Order state checks
export const isOrderActive = (order: Order): boolean => {
  return !['completed', 'cancelled', 'refunded'].includes(order.status);
};

export const isOrderEditable = (order: Order): boolean => {
  return ['draft', 'placed'].includes(order.status);
};

export const isOrderCancellable = (order: Order): boolean => {
  return ['draft', 'placed', 'confirmed'].includes(order.status);
};

export const isOrderPaid = (order: Order): boolean => {
  return order.paymentStatus === 'paid';
};

export const isOrderHighPriority = (order: Order): boolean => {
  return order.priority === 'high';
};

// Time utilities
export const getOrderAge = (order: Order): string => {
  if (!order.createdAt) return 'Unknown';

  const createdDate = new Date(order.createdAt);
  if (isNaN(createdDate.getTime())) return 'Unknown';

  const now = new Date();
  const diffMs = now.getTime() - createdDate.getTime();
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins} min ago`;

  const diffHours = Math.floor(diffMins / 60);
  if (diffHours < 24) return `${diffHours} hr ago`;

  const diffDays = Math.floor(diffHours / 24);
  return `${diffDays} days ago`;
};

export const getPreparationTime = (order: Order): number | null => {
  if (!order.placedAt || !order.readyAt) return null;

  const placedDate = new Date(order.placedAt);
  const readyDate = new Date(order.readyAt);
  return Math.floor((readyDate.getTime() - placedDate.getTime()) / 60000);
};

export const formatDuration = (minutes: number): string => {
  if (minutes < 60) return `${minutes} min`;

  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
};

// Formatting utilities
export const formatOrderNumber = (orderNumber: string | undefined): string => {
  if (!orderNumber) return '#undefined';
  return `#${orderNumber}`;
};

export const formatCurrency = (amount: number | undefined | null): string => {
  if (amount === undefined || amount === null || isNaN(amount)) {
    return '$0';
  }
  return new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

export const formatPhone = (phone: string): string => {
  // Format Chilean phone numbers
  const cleaned = phone.replace(/\D/g, '');
  if (cleaned.length === 9 && cleaned.startsWith('9')) {
    return `+56 9 ${cleaned.slice(1, 5)} ${cleaned.slice(5)}`;
  }
  return phone;
};

// Validation utilities
export const validatePhone = (phone: string): boolean => {
  const cleaned = phone.replace(/\D/g, '');
  return cleaned.length === 9 && cleaned.startsWith('9');
};

export const validateEmail = (email: string): boolean => {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
};

// Grouping utilities
export const groupOrdersByCourse = (items: OrderItem[]): Record<string, OrderItem[]> => {
  return items.reduce(
    (groups, item) => {
      const course = item.course || 'other';
      if (!groups[course]) groups[course] = [];
      groups[course].push(item);
      return groups;
    },
    {} as Record<string, OrderItem[]>,
  );
};

export const groupOrdersByStatus = (orders: Order[]): Record<OrderStatus, Order[]> => {
  return orders.reduce(
    (groups, order) => {
      if (!groups[order.status]) groups[order.status] = [];
      groups[order.status].push(order);
      return groups;
    },
    {} as Record<OrderStatus, Order[]>,
  );
};

// Kitchen display utilities
export const getKitchenOrderPriority = (order: Order): number => {
  let priority = 0;

  // High priority orders
  if (order.priority === 'high') priority += 100;

  // Order age (older orders get higher priority)
  const ageMinutes = Math.floor((Date.now() - new Date(order.placedAt || order.createdAt).getTime()) / 60000);
  priority += Math.min(ageMinutes, 60); // Cap at 60 minutes

  // Status priority
  if (order.status === 'confirmed') priority += 20;
  if (order.status === 'preparing') priority += 10;

  return priority;
};

export const sortOrdersForKitchen = (orders: Order[]): Order[] => {
  return [...orders].sort((a, b) => {
    const priorityA = getKitchenOrderPriority(a);
    const priorityB = getKitchenOrderPriority(b);
    return priorityB - priorityA;
  });
};
