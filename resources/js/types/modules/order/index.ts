// resources/js/types/modules/order/index.ts

// Import generated types
import '@/types/generated';

// Re-export the generated types with proper typing
export type Order = Colame.Order.Data.OrderData & {
  // Additional properties or overrides if needed
  id: string | number; // Allow both string and number for compatibility
  status: OrderStatus;
  type: OrderType;
  priority: 'normal' | 'high';
  paymentStatus: PaymentStatus;
  items?: OrderItem[];
  paidAmount?: number;
};

export type OrderItem = Colame.Order.Data.OrderItemData & {
  // Override properties that have different names in our codebase
  itemId: number;
  itemName: string;
  unitPrice: number;
  totalPrice: number;
  kitchenStatus: KitchenStatus;
  preparedAt?: string;
  servedAt?: string;
};

export type CreateOrderData = Colame.Order.Data.CreateOrderData;
export type UpdateOrderData = Colame.Order.Data.UpdateOrderData;
export type OrderWithRelations = Colame.Order.Data.OrderWithRelationsData;
export type PaymentTransaction = Colame.Order.Data.PaymentTransactionData;
export type OrderStatusHistory = Colame.Order.Data.OrderStatusHistoryData;

export interface OrderItemModifier {
  id: string;
  modifier_id: number;
  modifier_name: string;
  price: number;
  quantity: number;
}

export type OrderStatus = 
  | 'draft'
  | 'placed'
  | 'confirmed'
  | 'preparing'
  | 'ready'
  | 'delivering'
  | 'delivered'
  | 'completed'
  | 'cancelled'
  | 'refunded';

export type OrderType = 
  | 'dine_in'
  | 'takeout'
  | 'delivery'
  | 'catering';

export type PaymentStatus = 
  | 'pending'
  | 'partial'
  | 'paid'
  | 'refunded';

export type KitchenStatus = 
  | 'pending'
  | 'preparing'
  | 'ready'
  | 'served';

export type PaymentMethod = 
  | 'cash'
  | 'card'
  | 'transfer'
  | 'other';

// Component prop types
export interface OrderListPageProps {
  orders: {
    data: Order[];
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    current_page?: number; // for backwards compatibility
    last_page?: number; // for backwards compatibility
    per_page?: number; // for backwards compatibility
  };
  locations: Array<{ id: number; name: string }>;
  filters: Record<string, any>;
  statuses: string[];
  types: string[];
  stats?: {
    totalOrders: number;
    activeOrders: number;
    readyToServe: number;
    revenueToday: number;
    total_orders?: number; // for backwards compatibility
    active_orders?: number; // for backwards compatibility
    ready_to_serve?: number; // for backwards compatibility
    revenue_today?: number; // for backwards compatibility
  };
}

export interface OrderDetailPageProps {
  order: Order;
  user?: any;
  location?: any;
  payments?: PaymentTransaction[];
  offers?: any[];
  isPaid: boolean;
  remainingAmount: number;
  statusHistory?: OrderStatusHistory[];
}

export interface OrderCreatePageProps {
  locations: Array<{ id: number; name: string }>;
  tables?: Array<{ id: number; number: number; available: boolean }>;
  items?: Array<{
    id: number;
    name: string;
    price: number;
    category: string;
    modifiers?: Array<{
      id: number;
      name: string;
      price: number;
    }>;
  }>;
}

export interface KitchenDisplayPageProps {
  orders: Order[];
  locationId: number;
  settings?: {
    autoRefresh: boolean;
    soundEnabled: boolean;
    viewMode: 'grid' | 'list' | 'kanban';
  };
}

export interface OrderFilters {
  status?: string;
  type?: string;
  date?: string;
  location_id?: string;
  search?: string;
}

// Additional types for operations
export interface OrderOperationsPageProps {
  orders: Order[];
  locations: Array<{ id: number; name: string }>;
  stats: {
    active: number;
    preparing: number;
    ready: number;
    avgWaitTime: number;
  };
}

export interface OrderPaymentPageProps {
  order: Order;
  payments: PaymentTransaction[];
  remainingAmount: number;
  suggestedTip: number;
  paymentMethods: Array<{
    id: string;
    name: string;
    icon: string;
    enabled: boolean;
  }>;
}

export interface OrderDashboardData {
  metrics: {
    totalRevenue: number;
    totalOrders: number;
    averageOrderValue: number;
    avgPreparationTime: number;
    completionRate: number;
    satisfactionRate?: number;
    activeOrders: number;
    pendingOrders: number;
  };
  hourlyOrders: Array<{
    hour: number;
    count: number;
    revenue: number;
  }>;
  ordersByType: Array<{
    type: OrderType;
    count: number;
    revenue: number;
  }>;
  ordersByStatus: Array<{
    status: OrderStatus;
    count: number;
  }>;
  topItems: Array<{
    id: string;
    name: string;
    quantity: number;
    revenue: number;
    category?: string;
  }>;
  locationPerformance?: Array<{
    id: string;
    name: string;
    orders: number;
    revenue: number;
    avgTime: number;
    rating: number;
  }>;
  staffPerformance?: Array<{
    id: string;
    name: string;
    orders: number;
    revenue: number;
    avgTime: number;
    rating: number;
  }>;
  recentOrders?: Order[];
}

// Export utility type for form data
export type OrderFormData = Partial<Order>;