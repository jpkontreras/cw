import { Head, Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { PageLayout, PageHeader, PageContent } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { router } from '@inertiajs/react';
import { 
    ShoppingCart, 
    Clock, 
    CheckCircle,
    DollarSign,
    Package,
    Plus,
    ArrowUpRight,
    ArrowDownRight,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';
import type { OrderListPageProps, Order, OrderFilters } from '@/types/modules/order';
import { formatCurrency } from '@/types/modules/order/utils';
import { OrderDataTable } from '@/components/modules/order/order-data-table';

export default function OrderIndex({ 
    orders, 
    locations, 
    filters: initialFilters = {}, 
    statuses, 
    types,
    stats 
}: OrderListPageProps) {
    const [filters, setFilters] = useState<OrderFilters>(initialFilters);
    const [searchQuery, setSearchQuery] = useState(initialFilters.search || '');

    // Stats cards data
    const statsCards = useMemo(() => [
        {
            title: 'Total Orders',
            value: stats?.totalOrders || orders.total,
            icon: ShoppingCart,
            color: 'text-blue-600',
            indicatorColor: 'bg-blue-500',
            trend: '+12%',
            trendUp: true,
        },
        {
            title: 'Active Orders',
            value: stats?.activeOrders || 0,
            icon: Clock,
            color: 'text-orange-600',
            indicatorColor: 'bg-orange-500',
        },
        {
            title: 'Ready to Serve',
            value: stats?.readyToServe || 0,
            icon: CheckCircle,
            color: 'text-green-600',
            indicatorColor: 'bg-green-500',
        },
        {
            title: 'Today\'s Revenue',
            value: formatCurrency(stats?.revenueToday || 0),
            icon: DollarSign,
            color: 'text-purple-600',
            indicatorColor: 'bg-purple-500',
            trend: '+8%',
            trendUp: true,
        },
    ], [stats, orders.total]);

    const handleFilterChange = (key: string, value: string | undefined) => {
        const newFilters: any = { ...filters };
        if (value && value !== 'all') {
            newFilters[key] = value;
        } else {
            delete newFilters[key];
        }
        setFilters(newFilters);
        
        router.get('/orders', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearch = (query: string) => {
        setSearchQuery(query);
        handleFilterChange('search', query || undefined);
    };

    const handleExport = () => {
        const params = new URLSearchParams(filters as any);
        window.location.href = `/orders/export?${params.toString()}`;
    };

    const handlePageChange = (page: number) => {
        router.get(`/orders?page=${page}`, filters as any, {
            preserveState: true,
            preserveScroll: false,
        });
    };

    return (
        <AppLayout>
            <Head title="Orders" />
            
            <PageLayout>
                <PageHeader
                    title="Orders"
                    description="Manage and track all your restaurant orders"
                >
                    <Link href="/orders/create">
                        <Button>
                            <Plus className="w-4 h-4 mr-2" />
                            Create Order
                        </Button>
                    </Link>
                </PageHeader>

                <PageContent>

                    {/* Stats Cards - Minimal Design */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-2 mb-6">
                        {statsCards.map((stat, index) => {
                            const Icon = stat.icon;
                            return (
                                <div 
                                    key={index}
                                    className="bg-white rounded-lg border border-gray-100 p-3 hover:border-gray-200 transition-colors"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-1.5 mb-1">
                                                <div className={`w-1 h-1 rounded-full ${stat.indicatorColor}`} />
                                                <p className="text-xs text-gray-500 truncate">{stat.title}</p>
                                            </div>
                                            <p className="text-lg font-semibold text-gray-900 truncate">{stat.value}</p>
                                        </div>
                                        <div className="flex flex-col items-end gap-1">
                                            <Icon className={`h-3.5 w-3.5 ${stat.color}`} />
                                            {stat.trend && (
                                                <div className={`flex items-center text-xs ${stat.trendUp ? 'text-green-600' : 'text-red-600'}`}>
                                                    {stat.trendUp ? '↑' : '↓'}
                                                    {stat.trend}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* Orders Data Table */}
                    <Card className="shadow-sm border-gray-200">
                        <CardContent className="p-6">
                            {orders.data.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-24 px-4">
                                    <div className="relative mb-6">
                                        <div className="absolute inset-0 bg-blue-100 rounded-full blur-3xl opacity-30 animate-pulse" />
                                        <div className="relative bg-white rounded-full p-6 shadow-lg">
                                            <Package className="w-16 h-16 text-gray-400" />
                                        </div>
                                    </div>
                                    <h3 className="text-xl font-semibold text-gray-900 mb-2">No orders found</h3>
                                    <p className="text-gray-600 text-center mb-6 max-w-md">
                                        {Object.keys(filters).length > 0 
                                            ? "No orders match your current filters. Try adjusting your search criteria."
                                            : "Your orders will appear here once customers start placing them."
                                        }
                                    </p>
                                    <div className="flex gap-3">
                                        <Button 
                                            onClick={() => router.visit('/orders/create')}
                                        >
                                            <Plus className="w-4 h-4 mr-2" />
                                            Create First Order
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <>
                                    <OrderDataTable
                                        orders={orders.data}
                                        locations={locations}
                                        statuses={statuses}
                                        types={types}
                                        filters={filters}
                                        onExport={handleExport}
                                        onFilterChange={handleFilterChange}
                                        onSearch={handleSearch}
                                        searchQuery={searchQuery}
                                    />
                                    
                                    {/* Pagination */}
                                    {orders.lastPage > 1 && (
                                        <div className="flex items-center justify-between mt-4 pt-4 border-t">
                                            <div className="text-sm text-gray-500">
                                                Showing {(orders.currentPage - 1) * orders.perPage + 1} to{' '}
                                                {Math.min(orders.currentPage * orders.perPage, orders.total)} of{' '}
                                                {orders.total} orders
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handlePageChange(orders.currentPage - 1)}
                                                    disabled={orders.currentPage === 1}
                                                >
                                                    <ChevronLeft className="w-4 h-4" />
                                                    Previous
                                                </Button>
                                                {Array.from({ length: orders.lastPage }, (_, i) => i + 1)
                                                    .filter(page => {
                                                        const current = orders.currentPage;
                                                        return page === 1 || 
                                                               page === orders.lastPage || 
                                                               (page >= current - 1 && page <= current + 1);
                                                    })
                                                    .map((page, index, array) => (
                                                        <div key={page} className="flex items-center gap-2">
                                                            {index > 0 && array[index - 1] !== page - 1 && (
                                                                <span className="text-gray-400">...</span>
                                                            )}
                                                            <Button
                                                                variant={page === orders.currentPage ? 'default' : 'outline'}
                                                                size="sm"
                                                                onClick={() => handlePageChange(page)}
                                                            >
                                                                {page}
                                                            </Button>
                                                        </div>
                                                    ))}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handlePageChange(orders.currentPage + 1)}
                                                    disabled={orders.currentPage === orders.lastPage}
                                                >
                                                    Next
                                                    <ChevronRight className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>
                </PageContent>
            </PageLayout>
        </AppLayout>
    );
}