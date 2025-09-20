import { InertiaDataTable } from '@/modules/data-table';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/empty-state';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import type { OrderListPageProps } from '@/modules/order';
import { formatCurrency } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, Clock, CreditCard, Plus, ShoppingCart } from 'lucide-react';
import { useMemo } from 'react';

function OrderIndexContent({ view }: OrderListPageProps) {
  const { orders, pagination, metadata, locations, filters, stats } = view;

  const fastFilterCards = useMemo(
    () => [
      {
        title: "Today's Orders",
        value: stats?.todayOrders || 0,
        icon: ShoppingCart,
        color: 'text-blue-600',
        indicatorColor: 'bg-blue-500',
        filters: { date: 'today' },
        description: 'View all orders from today',
      },
      {
        title: 'Active Orders',
        value: stats?.activeOrders || 0,
        icon: Clock,
        color: 'text-orange-600',
        indicatorColor: 'bg-orange-500',
        filters: { status: 'placed,confirmed,preparing,ready' },
        description: 'Orders in progress',
      },
      {
        title: 'Ready to Serve',
        value: stats?.readyToServe || 0,
        icon: CheckCircle,
        color: 'text-green-600',
        indicatorColor: 'bg-green-500',
        filters: { status: 'ready' },
        description: 'Orders ready for pickup',
      },
      {
        title: 'Pending Payment',
        value: stats?.pendingPayment || 0,
        icon: CreditCard,
        color: 'text-purple-600',
        indicatorColor: 'bg-purple-500',
        filters: { paymentStatus: 'pending' },
        description: 'Awaiting payment',
      },
    ],
    [stats],
  );

  const toggleFastFilter = (filterCard: typeof fastFilterCards[0]) => {
    const params = new URLSearchParams(window.location.search);
    
    const isActive = Object.entries(filterCard.filters).every(([key, value]) => {
      const currentValue = params.get(key);
      return currentValue === value;
    });
    
    if (isActive) {
      Object.keys(filterCard.filters).forEach((key) => {
        params.delete(key);
      });
    } else {
      Object.entries(filterCard.filters).forEach(([key, value]) => {
        params.set(key, value);
      });
    }
    
    params.set('page', '1');
    
    router.get(window.location.pathname, Object.fromEntries(params), {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleExport = () => {
    const params = new URLSearchParams(filters as any);
    window.location.href = `/orders/export?${params.toString()}`;
  };

  return (
    <>
      <Page.Header
        title="Orders"
        subtitle="Manage and track all your restaurant orders"
        actions={
          <Link href="/orders/new">
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Create Order
            </Button>
          </Link>
        }
      />

      <Page.Content>
        {orders && orders.length === 0 ? (
          <EmptyState
            icon={ShoppingCart}
            title="No orders yet"
            description="When customers place orders, they'll appear here. Start by creating your first order or wait for customers to place orders."
            actions={
              <Link href="/orders/new">
                <Button size="lg">
                  <Plus className="mr-2 h-4 w-4" />
                  Create First Order
                </Button>
              </Link>
            }
            helpText={
              <>
                Learn more about <a href="#" className="text-primary hover:underline">managing orders</a>
              </>
            }
          />
        ) : (
          <div className="space-y-6">
            {/* Fast Filter Cards */}
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <p className="text-xs font-medium text-muted-foreground">Quick Filters</p>
                <div className="h-px flex-1 bg-border" />
              </div>
              <div className="grid grid-cols-2 gap-2 lg:grid-cols-4">
              {fastFilterCards.map((card, index) => {
                const Icon = card.icon;
                const isActive = Object.entries(card.filters).every(([key, value]) => {
                  const currentValue = filters[key as keyof typeof filters];
                  return currentValue === value;
                });
                
                return (
                  <button
                    key={index}
                    onClick={() => toggleFastFilter(card)}
                    className={`group relative overflow-hidden rounded-lg border px-3 py-2.5 text-left transition-all hover:shadow-sm ${
                      isActive ? 'border-primary bg-primary/5' : 'hover:border-primary/50'
                    }`}
                    title={isActive ? `Click to remove ${card.title} filter` : card.description}
                  >
                    <div className="flex items-center gap-2.5">
                      <Icon className={`h-4 w-4 ${card.color} flex-shrink-0 transition-transform group-hover:scale-110`} />
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-[11px] leading-none font-medium text-muted-foreground">{card.title}</p>
                        <div className="mt-0.5 flex items-baseline gap-2">
                          <p className="text-lg leading-none font-semibold">{card.value}</p>
                        </div>
                      </div>
                    </div>
                    {isActive && (
                      <div className={`absolute bottom-0 left-0 right-0 h-0.5 ${card.indicatorColor}`} />
                    )}
                  </button>
                );
              })}
              </div>
            </div>

            {/* Orders Data Table */}
            {pagination && metadata && <InertiaDataTable data={orders || []} pagination={pagination} metadata={metadata} rowClickRoute="/orders/:id" />}
          </div>
        )}
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