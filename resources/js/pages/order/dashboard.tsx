import { PageContent, PageHeader, PageLayout } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { OrderDashboardPageProps } from '@/types/modules/order';
import { formatCurrency, getStatusColor, getStatusLabel } from '@/types/modules/order/utils';
import { Head } from '@inertiajs/react';
import {
  Activity,
  AlertCircle,
  ChefHat,
  Clock,
  DollarSign,
  Download,
  Package,
  RefreshCw,
  ShoppingCart,
  Timer,
  TrendingDown,
  TrendingUp,
  Truck,
  Users,
} from 'lucide-react';
import { useMemo, useState } from 'react';

// Chart components (simplified versions - in real app use recharts or similar)
const SimpleBarChart = ({ data, dataKey, color = 'blue' }: any) => {
  const maxValue = Math.max(...data.map((d: any) => d[dataKey]));

  return (
    <div className="space-y-2">
      {data.map((item: any, index: number) => (
        <div key={index} className="flex items-center gap-2">
          <span className="w-20 text-sm text-gray-600">{item.label}</span>
          <div className="flex-1">
            <div className="h-6 overflow-hidden rounded bg-gray-100">
              <div className={`h-full bg-${color}-500 transition-all duration-300`} style={{ width: `${(item[dataKey] / maxValue) * 100}%` }} />
            </div>
          </div>
          <span className="w-16 text-right text-sm font-medium">{item[dataKey]}</span>
        </div>
      ))}
    </div>
  );
};

const SimplePieChart = ({ data }: any) => {
  const total = data.reduce((sum: number, item: any) => sum + item.value, 0);

  return (
    <div className="space-y-2">
      {data.map((item: any, index: number) => (
        <div key={index} className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className={`h-3 w-3 rounded-full ${item.color}`} />
            <span className="text-sm">{item.label}</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-sm font-medium">{item.value}</span>
            <span className="text-sm text-gray-500">({((item.value / total) * 100).toFixed(1)}%)</span>
          </div>
        </div>
      ))}
    </div>
  );
};

const MetricCard = ({
  title,
  value,
  change,
  changeType = 'neutral',
  icon: Icon,
  color = 'blue',
  format = 'number',
}: {
  title: string;
  value: number | string;
  change?: string;
  changeType?: 'positive' | 'negative' | 'neutral';
  icon: any;
  color?: string;
  format?: 'number' | 'currency' | 'time';
}) => {
  const formattedValue = format === 'currency' ? formatCurrency(Number(value)) : format === 'time' ? `${value} min` : value.toLocaleString();

  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div className="space-y-2">
            <p className="text-sm text-gray-600">{title}</p>
            <p className="text-2xl font-bold">{formattedValue}</p>
            {change && (
              <div
                className={`flex items-center gap-1 text-sm ${
                  changeType === 'positive' ? 'text-green-600' : changeType === 'negative' ? 'text-red-600' : 'text-gray-600'
                }`}
              >
                {changeType === 'positive' && <TrendingUp className="h-4 w-4" />}
                {changeType === 'negative' && <TrendingDown className="h-4 w-4" />}
                <span>{change}</span>
              </div>
            )}
          </div>
          <div className={`rounded-lg p-3 bg-${color}-100`}>
            <Icon className={`h-6 w-6 text-${color}-600`} />
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default function OrderDashboard({
  metrics,
  hourlyOrders,
  ordersByType,
  ordersByStatus,
  topItems,
  locationPerformance,
  staffPerformance,
  recentOrders,
  filters,
}: OrderDashboardPageProps) {
  const [selectedPeriod, setSelectedPeriod] = useState(filters?.period || 'today');
  const [selectedLocation, setSelectedLocation] = useState(filters?.location_id || 'all');
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Process data for charts
  const hourlyData = useMemo(() => {
    return (
      hourlyOrders?.map((h) => ({
        label: `${h.hour}:00`,
        orders: h.count,
        revenue: h.revenue,
      })) || []
    );
  }, [hourlyOrders]);

  const typeData = useMemo(() => {
    const colors = {
      dine_in: 'bg-blue-500',
      takeout: 'bg-green-500',
      delivery: 'bg-purple-500',
      catering: 'bg-orange-500',
    };
    return (
      ordersByType?.map((t) => ({
        label: t.type.replace('_', ' ').charAt(0).toUpperCase() + t.type.slice(1),
        value: t.count,
        color: colors[t.type as keyof typeof colors] || 'bg-gray-500',
      })) || []
    );
  }, [ordersByType]);

  const statusData = useMemo(() => {
    return (
      ordersByStatus?.map((s) => ({
        label: getStatusLabel(s.status as any),
        orders: s.count,
      })) || []
    );
  }, [ordersByStatus]);

  const handleRefresh = () => {
    setIsRefreshing(true);
    // In real app, this would trigger data refresh
    setTimeout(() => setIsRefreshing(false), 1000);
  };

  return (
    <AppLayout>
      <Head title="Order Dashboard" />

      <PageLayout>
        <PageHeader title="Order Dashboard" description="Real-time analytics and insights for your restaurant">
          <div className="flex items-center gap-2">
            {/* Period Filter */}
            <Select value={selectedPeriod} onValueChange={setSelectedPeriod}>
              <SelectTrigger className="w-40">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="today">Today</SelectItem>
                <SelectItem value="yesterday">Yesterday</SelectItem>
                <SelectItem value="week">This Week</SelectItem>
                <SelectItem value="month">This Month</SelectItem>
                <SelectItem value="quarter">This Quarter</SelectItem>
              </SelectContent>
            </Select>

            {/* Location Filter */}
            <Select value={selectedLocation} onValueChange={setSelectedLocation}>
              <SelectTrigger className="w-48">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Locations</SelectItem>
                <SelectItem value="1">Main Branch</SelectItem>
                <SelectItem value="2">Downtown Branch</SelectItem>
              </SelectContent>
            </Select>

            <Button variant="outline" size="icon" onClick={handleRefresh}>
              <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
            </Button>

            <Button variant="outline">
              <Download className="mr-2 h-4 w-4" />
              Export
            </Button>
          </div>
        </PageHeader>

        <PageContent>
          {/* Key Metrics */}
          <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
              title="Total Revenue"
              value={metrics?.totalRevenue || 0}
              change="+12.5% from yesterday"
              changeType="positive"
              icon={DollarSign}
              color="green"
              format="currency"
            />
            <MetricCard
              title="Total Orders"
              value={metrics?.totalOrders || 0}
              change="+8 orders"
              changeType="positive"
              icon={ShoppingCart}
              color="blue"
            />
            <MetricCard
              title="Average Order Value"
              value={metrics?.averageOrderValue || 0}
              change="-5.2%"
              changeType="negative"
              icon={Activity}
              color="purple"
              format="currency"
            />
            <MetricCard
              title="Avg. Preparation Time"
              value={metrics?.avgPreparationTime || 0}
              change="2 min faster"
              changeType="positive"
              icon={Clock}
              color="orange"
              format="time"
            />
          </div>

          {/* Main Content Tabs */}
          <Tabs defaultValue="overview" className="space-y-6">
            <TabsList className="grid w-full grid-cols-5">
              <TabsTrigger value="overview">Overview</TabsTrigger>
              <TabsTrigger value="orders">Orders</TabsTrigger>
              <TabsTrigger value="items">Items</TabsTrigger>
              <TabsTrigger value="performance">Performance</TabsTrigger>
              <TabsTrigger value="live">Live Monitor</TabsTrigger>
            </TabsList>

            {/* Overview Tab */}
            <TabsContent value="overview" className="space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Hourly Orders Chart */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Orders by Hour</CardTitle>
                    <CardDescription>Order volume throughout the day</CardDescription>
                  </CardHeader>
                  <CardContent className="p-6">
                    <SimpleBarChart data={hourlyData} dataKey="orders" color="blue" />
                  </CardContent>
                </Card>

                {/* Order Types Distribution */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Order Types</CardTitle>
                    <CardDescription>Distribution by order type</CardDescription>
                  </CardHeader>
                  <CardContent className="p-6">
                    <SimplePieChart data={typeData} />
                  </CardContent>
                </Card>

                {/* Order Status Overview */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Order Status</CardTitle>
                    <CardDescription>Current order pipeline</CardDescription>
                  </CardHeader>
                  <CardContent className="p-6">
                    <SimpleBarChart data={statusData} dataKey="orders" color="green" />
                  </CardContent>
                </Card>

                {/* Quick Stats */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Quick Stats</CardTitle>
                    <CardDescription>Key performance indicators</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4 p-6">
                    <div className="flex items-center justify-between">
                      <span className="text-sm">Order Completion Rate</span>
                      <div className="flex items-center gap-2">
                        <Progress value={metrics?.completionRate || 0} className="w-24" />
                        <span className="text-sm font-medium">{metrics?.completionRate || 0}%</span>
                      </div>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm">Customer Satisfaction</span>
                      <div className="flex items-center gap-2">
                        <Progress value={metrics?.satisfactionRate || 85} className="w-24" />
                        <span className="text-sm font-medium">85%</span>
                      </div>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm">Table Turnover Rate</span>
                      <span className="text-sm font-medium">3.2x</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm">Delivery On-Time Rate</span>
                      <span className="text-sm font-medium">92%</span>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

            {/* Orders Tab */}
            <TabsContent value="orders" className="space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Order Flow */}
                <Card className="lg:col-span-2">
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Order Flow</CardTitle>
                    <CardDescription>Real-time order pipeline</CardDescription>
                  </CardHeader>
                  <CardContent className="p-6">
                    <div className="grid grid-cols-4 gap-4">
                      {['placed', 'preparing', 'ready', 'delivering'].map((status) => {
                        const count = ordersByStatus?.find((s) => s.status === status)?.count || 0;
                        return (
                          <div key={status} className="text-center">
                            <div
                              className={`rounded-lg p-4 ${getStatusColor(status as any)
                                .replace('text-', 'bg-')
                                .replace('-600', '-100')}`}
                            >
                              <p className="text-2xl font-bold">{count}</p>
                              <p className="mt-1 text-sm text-gray-600">{getStatusLabel(status as any)}</p>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </CardContent>
                </Card>

                {/* Peak Hours */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Peak Hours</CardTitle>
                    <CardDescription>Busiest times today</CardDescription>
                  </CardHeader>
                  <CardContent className="p-6">
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <span className="text-sm">12:00 - 13:00</span>
                        <Badge variant="destructive">45 orders</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm">19:00 - 20:00</span>
                        <Badge variant="destructive">38 orders</Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-sm">13:00 - 14:00</span>
                        <Badge>32 orders</Badge>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>

              {/* Recent Orders */}
              <Card>
                <CardHeader className="px-6 pt-6">
                  <CardTitle>Recent Orders</CardTitle>
                  <CardDescription>Latest order activity</CardDescription>
                </CardHeader>
                <CardContent className="p-6">
                  <div className="space-y-3">
                    {recentOrders?.slice(0, 5).map((order) => (
                      <div key={order.id} className="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                        <div className="flex items-center gap-4">
                          <div className="flex flex-col">
                            <span className="font-medium">{order.order_number}</span>
                            <span className="text-sm text-gray-500">{order.customer_name || 'Walk-in'}</span>
                          </div>
                          <Badge className={getStatusColor(order.status)}>{getStatusLabel(order.status)}</Badge>
                        </div>
                        <div className="text-right">
                          <p className="font-medium">{formatCurrency(order.total_amount)}</p>
                          <p className="text-sm text-gray-500">{new Date(order.created_at).toLocaleTimeString()}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Items Tab */}
            <TabsContent value="items" className="space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Top Selling Items */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Top Selling Items</CardTitle>
                    <CardDescription>Best performers today</CardDescription>
                  </CardHeader>
                  <CardContent className="p-6">
                    <div className="space-y-3">
                      {topItems?.slice(0, 10).map((item, index) => (
                        <div key={item.id} className="flex items-center justify-between">
                          <div className="flex items-center gap-3">
                            <span className="w-6 text-sm font-medium text-gray-500">{index + 1}.</span>
                            <span className="text-sm">{item.name}</span>
                          </div>
                          <div className="flex items-center gap-3">
                            <Badge variant="secondary">{item.quantity} sold</Badge>
                            <span className="text-sm font-medium">{formatCurrency(item.revenue)}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>

                {/* Category Performance */}
                <Card>
                  <CardHeader>
                    <CardTitle>Category Performance</CardTitle>
                    <CardDescription>Sales by category</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {[
                        { category: 'Main Courses', revenue: 145000, percentage: 45 },
                        { category: 'Beverages', revenue: 65000, percentage: 20 },
                        { category: 'Starters', revenue: 55000, percentage: 17 },
                        { category: 'Desserts', revenue: 35000, percentage: 11 },
                        { category: 'Others', revenue: 25000, percentage: 7 },
                      ].map((cat) => (
                        <div key={cat.category}>
                          <div className="mb-1 flex items-center justify-between">
                            <span className="text-sm">{cat.category}</span>
                            <span className="text-sm font-medium">{formatCurrency(cat.revenue)}</span>
                          </div>
                          <Progress value={cat.percentage} className="h-2" />
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

            {/* Performance Tab */}
            <TabsContent value="performance" className="space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Location Performance */}
                <Card>
                  <CardHeader>
                    <CardTitle>Location Performance</CardTitle>
                    <CardDescription>Comparison across branches</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {locationPerformance?.map((loc) => (
                        <div key={loc.id} className="space-y-2">
                          <div className="flex items-center justify-between">
                            <span className="font-medium">{loc.name}</span>
                            <span className="text-sm text-gray-500">{loc.orders} orders</span>
                          </div>
                          <div className="grid grid-cols-3 gap-2 text-sm">
                            <div>
                              <p className="text-gray-500">Revenue</p>
                              <p className="font-medium">{formatCurrency(loc.revenue)}</p>
                            </div>
                            <div>
                              <p className="text-gray-500">Avg. Time</p>
                              <p className="font-medium">{loc.avgTime} min</p>
                            </div>
                            <div>
                              <p className="text-gray-500">Rating</p>
                              <p className="font-medium">{loc.rating}/5</p>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>

                {/* Staff Performance */}
                <Card>
                  <CardHeader>
                    <CardTitle>Staff Performance</CardTitle>
                    <CardDescription>Top performing staff today</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {staffPerformance?.slice(0, 5).map((staff) => (
                        <div key={staff.id} className="flex items-center justify-between">
                          <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                              <Users className="h-4 w-4" />
                            </div>
                            <div>
                              <p className="font-medium">{staff.name}</p>
                              <p className="text-sm text-gray-500">{staff.role}</p>
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="font-medium">{staff.orders} orders</p>
                            <p className="text-sm text-gray-500">{formatCurrency(staff.revenue)}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

            {/* Live Monitor Tab */}
            <TabsContent value="live" className="space-y-6">
              <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Active Orders */}
                <Card>
                  <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>Active Orders</CardTitle>
                    <Activity className="h-4 w-4 animate-pulse text-green-500" />
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between rounded bg-orange-50 p-2">
                        <div>
                          <p className="font-medium">Table 5</p>
                          <p className="text-sm text-gray-500">Preparing - 5 min</p>
                        </div>
                        <ChefHat className="h-4 w-4 text-orange-500" />
                      </div>
                      <div className="flex items-center justify-between rounded bg-blue-50 p-2">
                        <div>
                          <p className="font-medium">Delivery #234</p>
                          <p className="text-sm text-gray-500">Ready - 2 min</p>
                        </div>
                        <Package className="h-4 w-4 text-blue-500" />
                      </div>
                      <div className="flex items-center justify-between rounded bg-purple-50 p-2">
                        <div>
                          <p className="font-medium">Takeout #456</p>
                          <p className="text-sm text-gray-500">Delivering</p>
                        </div>
                        <Truck className="h-4 w-4 text-purple-500" />
                      </div>
                    </div>
                  </CardContent>
                </Card>

                {/* Kitchen Status */}
                <Card>
                  <CardHeader>
                    <CardTitle>Kitchen Status</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      <div>
                        <div className="mb-1 flex items-center justify-between">
                          <span className="text-sm">Queue Length</span>
                          <span className="text-sm font-medium">8 orders</span>
                        </div>
                        <Progress value={60} className="h-2" />
                      </div>
                      <div>
                        <div className="mb-1 flex items-center justify-between">
                          <span className="text-sm">Capacity</span>
                          <span className="text-sm font-medium">75%</span>
                        </div>
                        <Progress value={75} className="h-2" />
                      </div>
                      <div className="pt-2">
                        <p className="text-sm text-gray-500">Est. wait time</p>
                        <p className="text-xl font-bold">12 min</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                {/* Alerts */}
                <Card>
                  <CardHeader>
                    <CardTitle>Alerts</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      <div className="flex items-start gap-3 rounded bg-red-50 p-2">
                        <AlertCircle className="mt-0.5 h-4 w-4 text-red-500" />
                        <div className="flex-1">
                          <p className="text-sm font-medium">Order #789 delayed</p>
                          <p className="text-xs text-gray-500">15 min overdue</p>
                        </div>
                      </div>
                      <div className="flex items-start gap-3 rounded bg-yellow-50 p-2">
                        <Timer className="mt-0.5 h-4 w-4 text-yellow-500" />
                        <div className="flex-1">
                          <p className="text-sm font-medium">High wait times</p>
                          <p className="text-xs text-gray-500">Kitchen at capacity</p>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </TabsContent>
          </Tabs>
        </PageContent>
      </PageLayout>
    </AppLayout>
  );
}
