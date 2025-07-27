import { OrderDataTable } from '@/components/modules/order/order-data-table';
import { Button } from '@/components/ui/button';
import { LaravelPagination } from '@/components/laravel-pagination';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import type { OrderFilters, OrderListPageProps } from '@/types/modules/order';
import { formatCurrency } from '@/types/modules/order/utils';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, Clock, DollarSign, Package, Plus, ShoppingCart } from 'lucide-react';
import { useMemo } from 'react';

function OrderIndexContent({ orders, pagination, metadata, locations, filters: initialFilters = {}, stats }: OrderListPageProps) {
  // Stats cards data
  const statsCards = useMemo(
    () => [
      {
        title: 'Total Orders',
        value: stats?.totalOrders || stats?.total_orders || 0,
        icon: ShoppingCart,
        color: 'text-blue-600',
        indicatorColor: 'bg-blue-500',
        trend: '+12%',
        trendUp: true,
      },
      {
        title: 'Active Orders',
        value: stats?.activeOrders || stats?.active_orders || 0,
        icon: Clock,
        color: 'text-orange-600',
        indicatorColor: 'bg-orange-500',
      },
      {
        title: 'Ready to Serve',
        value: stats?.readyToServe || stats?.ready_to_serve || 0,
        icon: CheckCircle,
        color: 'text-green-600',
        indicatorColor: 'bg-green-500',
      },
      {
        title: "Today's Revenue",
        value: formatCurrency(stats?.revenueToday || stats?.revenue_today || 0),
        icon: DollarSign,
        color: 'text-purple-600',
        indicatorColor: 'bg-purple-500',
        trend: '+8%',
        trendUp: true,
      },
    ],
    [stats],
  );

  const handleExport = () => {
    const params = new URLSearchParams(initialFilters as any);
    window.location.href = `/orders/export?${params.toString()}`;
  };

  return (
    <>
      <Page.Header
        title="Orders"
        subtitle="Manage and track all your restaurant orders"
        actions={
          <Link href="/orders/create">
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Create Order
            </Button>
          </Link>
        }
      />

      <Page.Content>
        <div className="space-y-6">
          {/* Stats Cards - Minimal Design */}
          <div className="grid grid-cols-2 gap-2 lg:grid-cols-4">
            {statsCards.map((stat, index) => {
              const Icon = stat.icon;
              return (
                <div key={index} className="rounded-lg border px-3 py-2.5">
                  <div className="flex items-center gap-2.5">
                    <Icon className={`h-4 w-4 ${stat.color} flex-shrink-0`} />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-[11px] leading-none font-medium text-muted-foreground">{stat.title}</p>
                      <div className="mt-0.5 flex items-baseline gap-2">
                        <p className="text-lg leading-none font-semibold">{stat.value}</p>
                        {stat.trend && (
                          <span className={`flex items-center gap-0.5 text-[10px] font-medium ${stat.trendUp ? 'text-green-600' : 'text-red-600'}`}>
                            <span>{stat.trendUp ? '↑' : '↓'}</span>
                            {stat.trend}
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Orders Data Table */}
          <div>
            {!orders || orders.length === 0 ? (
              <div className="rounded-lg border bg-card">
                <div className="flex flex-col items-center justify-center px-4 py-24">
                  <div className="relative mb-6">
                    <div className="absolute inset-0 animate-pulse rounded-full bg-blue-100 opacity-30 blur-3xl" />
                    <div className="relative rounded-full bg-white p-6 shadow-lg">
                      <Package className="h-16 w-16 text-gray-400" />
                    </div>
                  </div>
                  <h3 className="mb-2 text-xl font-semibold text-gray-900">No orders found</h3>
                  <p className="mb-6 max-w-md text-center text-gray-600">
                    {Object.keys(filters).length > 0
                      ? 'No orders match your current filters. Try adjusting your search criteria.'
                      : 'Your orders will appear here once customers start placing them.'}
                  </p>
                  <div className="flex gap-3">
                    <Button onClick={() => router.visit('/orders/create')}>
                      <Plus className="mr-2 h-4 w-4" />
                      Create First Order
                    </Button>
                  </div>
                </div>
              </div>
            ) : (
              <OrderDataTable
                orders={orders}
                pagination={pagination}
                metadata={metadata}
                locations={locations}
                filters={initialFilters}
                onExport={handleExport}
              />
            )}
          </div>

          {/* Pagination */}
          {orders && orders.length > 0 && pagination && (
            <div className="mt-6">
              <LaravelPagination 
                pagination={pagination} 
                filters={initialFilters}
                preserveScroll={false}
                preserveState={true}
              />
            </div>
          )}
        </div>
      </Page.Content>
    </>
  );
}

export default function OrderIndex(props: OrderListPageProps) {
  return (
    <AppLayout>
      <Head title="Orders" />
      <Page>
        <OrderIndexContent {...props} />
      </Page>
    </AppLayout>
  );
}
