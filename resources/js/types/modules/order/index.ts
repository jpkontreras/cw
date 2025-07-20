// resources/js/types/modules/order/index.ts

export interface Order {
  id: string;
  order_number: string;
  status: OrderStatus;
  type: OrderType;
  priority: 'normal' | 'high';
  
  // Customer Information
  customer_name?: string;
  customer_phone?: string;
  customer_email?: string;
  delivery_address?: string;
  
  // Order Details
  location_id: number;
  table_number?: number;
  waiter?: {
    id: number;
    name: string;
  };
  
  // Items
  items: OrderItem[];
  
  // Financial
  subtotal: number;
  tax_amount: number;
  tip_amount: number;
  discount_amount: number;
  total_amount: number;
  payment_status: PaymentStatus;
  
  // Additional Info
  notes?: string;
  special_instructions?: string;
  
  // Timestamps
  created_at: string;
  placed_at?: string;
  confirmed_at?: string;
  prepared_at?: string;
  ready_at?: string;
  delivered_at?: string;
  completed_at?: string;
  cancelled_at?: string;
  scheduled_at?: string;
}

export interface OrderItem {
  id: string;
  item_id: number;
  item_name: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  notes?: string;
  kitchen_status: KitchenStatus;
  course?: 'starter' | 'main' | 'dessert' | 'beverage';
  modifiers?: OrderItemModifier[];
  prepared_at?: string;
  served_at?: string;
}

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

export interface OrderStatusHistory {
  id: string;
  order_id: string;
  status: OrderStatus;
  changed_by: {
    id: number;
    name: string;
  };
  changed_at: string;
  notes?: string;
}

export interface PaymentTransaction {
  id: string;
  order_id: string;
  method: PaymentMethod;
  amount: number;
  status: 'pending' | 'completed' | 'failed' | 'refunded';
  reference_number?: string;
  processed_at?: string;
  processor_response?: any;
}

export type PaymentMethod = 
  | 'cash'
  | 'credit_card'
  | 'debit_card'
  | 'mobile_payment'
  | 'gift_card'
  | 'other';

// Request/Response Types
export interface CreateOrderRequest {
  location_id: number;
  type: OrderType;
  table_number?: number;
  customer_name?: string;
  customer_phone?: string;
  customer_email?: string;
  delivery_address?: string;
  items: Array<{
    item_id: number;
    quantity: number;
    modifiers?: number[];
    notes?: string;
  }>;
  notes?: string;
  special_instructions?: string;
}

export interface OrderResponse {
  data: Order;
  meta?: {
    available_actions: string[];
    next_status?: OrderStatus;
  };
}

export interface OrderListResponse {
  data: Order[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface OrderFilters {
  status?: string;
  type?: string;
  date?: string;
  location_id?: string;
  search?: string;
}

export interface OrderStats {
  total_orders: number;
  active_orders: number;
  ready_to_serve: number;
  revenue_today: number;
  average_order_value: number;
  completion_rate: number;
}

// Update types for real-time updates
export interface OrderUpdate {
  order_id: string;
  type: 'status_changed' | 'item_updated' | 'payment_received' | 'cancelled';
  data: any;
  timestamp: string;
}

export interface KitchenOrderUpdate {
  order_id: string;
  items: Array<{
    item_id: string;
    status: KitchenStatus;
  }>;
  timestamp: string;
}

// Props interfaces for pages
export interface OrderListPageProps {
  orders: OrderListResponse;
  locations: Array<{ id: number; name: string }>;
  filters: OrderFilters;
  statuses: string[];
  types: string[];
  stats?: OrderStats;
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

export interface KitchenDisplayPageProps {
  orders: Order[];
  locationId: number;
  settings?: {
    autoRefresh: boolean;
    soundEnabled: boolean;
    viewMode: 'grid' | 'list' | 'kanban';
  };
}

// Dashboard Types
export interface OrderMetrics {
    totalRevenue: number;
    totalOrders: number;
    averageOrderValue: number;
    avgPreparationTime: number;
    completionRate: number;
    satisfactionRate?: number;
    activeOrders: number;
    pendingOrders: number;
}

export interface HourlyOrderData {
    hour: number;
    count: number;
    revenue: number;
}

export interface OrderTypeDistribution {
    type: OrderType;
    count: number;
    revenue: number;
}

export interface OrderStatusDistribution {
    status: OrderStatus;
    count: number;
}

export interface TopItem {
    id: string;
    name: string;
    quantity: number;
    revenue: number;
    category?: string;
}

export interface LocationPerformance {
    id: string;
    name: string;
    orders: number;
    revenue: number;
    avgTime: number;
    rating: number;
}

export interface StaffPerformance {
    id: string;
    name: string;
    role: string;
    orders: number;
    revenue: number;
    avgTime?: number;
}

export interface DashboardFilters {
    period?: 'today' | 'yesterday' | 'week' | 'month' | 'quarter';
    location_id?: string;
}

export interface OrderDashboardPageProps {
    metrics: OrderMetrics;
    hourlyOrders: HourlyOrderData[];
    ordersByType: OrderTypeDistribution[];
    ordersByStatus: OrderStatusDistribution[];
    topItems: TopItem[];
    locationPerformance?: LocationPerformance[];
    staffPerformance?: StaffPerformance[];
    recentOrders?: Order[];
    filters?: DashboardFilters;
}

// Cancel Order Page Props
export interface CancelOrderPageProps {
    order: OrderWithRelationsData;
}