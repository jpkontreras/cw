import { Head } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/components/ui/table';
import { router } from '@inertiajs/react';
import { 
    Search, 
    Filter, 
    Download, 
    ShoppingCart, 
    Clock, 
    CheckCircle,
    TrendingUp,
    DollarSign,
    Package,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';
import type { OrderListPageProps, Order, OrderFilters, OrderStats } from '@/types/modules/order';
import { 
    getStatusColor, 
    getStatusLabel, 
    getStatusIcon,
    getTypeLabel,
    getOrderAge,
    formatCurrency,
    formatOrderNumber 
} from '@/types/modules/order/utils';

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
            value: stats?.total_orders || orders.total,
            icon: ShoppingCart,
            color: 'text-blue-600',
            bgColor: 'bg-blue-100',
        },
        {
            title: 'Active Orders',
            value: stats?.active_orders || 0,
            icon: Clock,
            color: 'text-orange-600',
            bgColor: 'bg-orange-100',
        },
        {
            title: 'Ready to Serve',
            value: stats?.ready_to_serve || 0,
            icon: CheckCircle,
            color: 'text-green-600',
            bgColor: 'bg-green-100',
        },
        {
            title: 'Today\'s Revenue',
            value: formatCurrency(stats?.revenue_today || 0),
            icon: DollarSign,
            color: 'text-purple-600',
            bgColor: 'bg-purple-100',
        },
    ], [stats, orders.total]);

    const handleFilterChange = (key: keyof OrderFilters, value: string | undefined) => {
        const newFilters = { ...filters };
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

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        handleFilterChange('search', searchQuery || undefined);
    };

    const handleExport = () => {
        const params = new URLSearchParams(filters as any);
        window.location.href = `/orders/export?${params.toString()}`;
    };

    const handlePageChange = (page: number) => {
        router.get(`/orders?page=${page}`, filters, {
            preserveState: true,
            preserveScroll: false,
        });
    };

    const clearFilters = () => {
        setFilters({});
        setSearchQuery('');
        router.get('/orders', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const activeFilterCount = Object.keys(filters).length;

    return (
        <AppLayout>
            <Head title="Orders" />

            <div className="container mx-auto p-6">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-3xl font-bold">Orders</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="w-4 h-4 mr-2" />
                            Export
                        </Button>
                        <Button onClick={() => router.visit('/orders/create')}>
                            <ShoppingCart className="w-4 h-4 mr-2" />
                            Create Order
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    {statsCards.map((stat, index) => {
                        const Icon = stat.icon;
                        return (
                            <Card key={index}>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 px-6 pb-2 pt-6">
                                    <CardTitle className="text-sm font-medium">
                                        {stat.title}
                                    </CardTitle>
                                    <div className={`p-2 rounded-lg ${stat.bgColor}`}>
                                        <Icon className={`h-4 w-4 ${stat.color}`} />
                                    </div>
                                </CardHeader>
                                <CardContent className="px-6 pb-6">
                                    <div className="text-2xl font-bold">{stat.value}</div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardHeader className="px-6 pt-6">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base flex items-center gap-2">
                                <Filter className="w-4 h-4" />
                                Filters
                                {activeFilterCount > 0 && (
                                    <Badge variant="secondary">{activeFilterCount}</Badge>
                                )}
                            </CardTitle>
                            {activeFilterCount > 0 && (
                                <Button variant="ghost" size="sm" onClick={clearFilters}>
                                    Clear all
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            {/* Search */}
                            <form onSubmit={handleSearch} className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                <Input
                                    type="text"
                                    placeholder="Search orders..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="pl-10"
                                />
                            </form>

                            {/* Status Filter */}
                            <Select
                                value={filters.status || 'all'}
                                onValueChange={(value) => handleFilterChange('status', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    {statuses.map((status) => (
                                        <SelectItem key={status} value={status}>
                                            {getStatusLabel(status as any)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {/* Type Filter */}
                            <Select
                                value={filters.type || 'all'}
                                onValueChange={(value) => handleFilterChange('type', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    {types.map((type) => (
                                        <SelectItem key={type} value={type}>
                                            {getTypeLabel(type as any)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {/* Location Filter */}
                            <Select
                                value={filters.location_id || 'all'}
                                onValueChange={(value) => handleFilterChange('location_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Locations" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Locations</SelectItem>
                                    {locations.map((location) => (
                                        <SelectItem key={location.id} value={location.id.toString()}>
                                            {location.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {/* Date Filter */}
                            <Select
                                value={filters.date || 'all'}
                                onValueChange={(value) => handleFilterChange('date', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Time" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Time</SelectItem>
                                    <SelectItem value="today">Today</SelectItem>
                                    <SelectItem value="yesterday">Yesterday</SelectItem>
                                    <SelectItem value="week">This Week</SelectItem>
                                    <SelectItem value="month">This Month</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Orders Table */}
                <Card>
                    <CardContent className="p-0">
                        {orders.data.length === 0 ? (
                            <div className="text-center py-12">
                                <Package className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                <p className="text-gray-500">No orders found</p>
                                {activeFilterCount > 0 && (
                                    <Button variant="link" onClick={clearFilters} className="mt-2">
                                        Clear filters
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Order</TableHead>
                                            <TableHead>Customer</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Items</TableHead>
                                            <TableHead>Total</TableHead>
                                            <TableHead>Payment</TableHead>
                                            <TableHead>Time</TableHead>
                                            <TableHead>Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {orders.data.map((order: Order) => (
                                            <TableRow 
                                                key={order.id}
                                                className="cursor-pointer hover:bg-gray-50"
                                                onClick={() => router.visit(`/orders/${order.id}`)}
                                            >
                                                <TableCell className="font-medium">
                                                    {formatOrderNumber(order.order_number)}
                                                    {order.priority === 'high' && (
                                                        <Badge variant="destructive" className="ml-2 text-xs">
                                                            Priority
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {order.customer_name || 'Walk-in'}
                                                    {order.table_number && (
                                                        <span className="text-sm text-gray-500 block">
                                                            Table {order.table_number}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {getTypeLabel(order.type)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge className={getStatusColor(order.status)}>
                                                        {getStatusLabel(order.status)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{order.items.length}</TableCell>
                                                <TableCell className="font-medium">
                                                    {formatCurrency(order.total_amount)}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge 
                                                        variant={order.payment_status === 'paid' ? 'default' : 'secondary'}
                                                    >
                                                        {order.payment_status}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm text-gray-500">
                                                    {getOrderAge(order)}
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            router.visit(`/orders/${order.id}`);
                                                        }}
                                                    >
                                                        View
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {/* Pagination */}
                                {orders.last_page > 1 && (
                                    <div className="flex items-center justify-between px-6 py-4 border-t">
                                        <div className="text-sm text-gray-500">
                                            Showing {(orders.current_page - 1) * orders.per_page + 1} to{' '}
                                            {Math.min(orders.current_page * orders.per_page, orders.total)} of{' '}
                                            {orders.total} orders
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handlePageChange(orders.current_page - 1)}
                                                disabled={orders.current_page === 1}
                                            >
                                                <ChevronLeft className="w-4 h-4" />
                                                Previous
                                            </Button>
                                            {Array.from({ length: orders.last_page }, (_, i) => i + 1)
                                                .filter(page => {
                                                    const current = orders.current_page;
                                                    return page === 1 || 
                                                           page === orders.last_page || 
                                                           (page >= current - 1 && page <= current + 1);
                                                })
                                                .map((page, index, array) => (
                                                    <div key={page} className="flex items-center gap-2">
                                                        {index > 0 && array[index - 1] !== page - 1 && (
                                                            <span className="text-gray-400">...</span>
                                                        )}
                                                        <Button
                                                            variant={page === orders.current_page ? 'default' : 'outline'}
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
                                                onClick={() => handlePageChange(orders.current_page + 1)}
                                                disabled={orders.current_page === orders.last_page}
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
            </div>
        </AppLayout>
    );
}