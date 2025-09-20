import React, { useState } from 'react'
import { Head, router } from '@inertiajs/react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Clock, Package, CheckCircle, AlertCircle, ChefHat, Users, DollarSign } from 'lucide-react'
import { formatCurrency } from '@/lib/format'
import AppLayout from '@/layouts/app-layout'
import Page from '@/layouts/page-layout'

interface OrderItem {
    id: number
    name: string
    quantity: number
    status: string
    kitchenStatus: string
}

interface Order {
    id: string
    orderNumber: string
    tableNumber?: string
    status: string
    type: string
    totalAmount: number
    customerName?: string
    createdAt: string
    itemCount: number
    items: OrderItem[]
}

interface Stats {
    todayOrders: number
    activeOrders: number
    readyToServe: number
    pendingPayment: number
}

interface Props {
    orders: Order[]
    stats: Stats
    location?: {
        id: number
        name: string
    }
}

function OperationsCenterContent({ orders, stats, location }: Props) {
    const [selectedStatus, setSelectedStatus] = useState<string>('all')

    const statusConfig: Record<string, { label: string; color: string; icon: React.ReactNode }> = {
        placed: { label: 'Placed', color: 'bg-blue-100 text-blue-800', icon: <Package className="w-4 h-4" /> },
        confirmed: { label: 'Confirmed', color: 'bg-green-100 text-green-800', icon: <CheckCircle className="w-4 h-4" /> },
        preparing: { label: 'Preparing', color: 'bg-yellow-100 text-yellow-800', icon: <ChefHat className="w-4 h-4" /> },
        ready: { label: 'Ready', color: 'bg-purple-100 text-purple-800', icon: <AlertCircle className="w-4 h-4" /> },
    }

    const filteredOrders = selectedStatus === 'all'
        ? orders
        : orders.filter(order => order.status === selectedStatus)

    const getTimeAgo = (date: string) => {
        const now = new Date()
        const created = new Date(date)
        const diff = Math.floor((now.getTime() - created.getTime()) / 1000 / 60)

        if (diff < 1) return 'Just now'
        if (diff < 60) return `${diff} min ago`
        const hours = Math.floor(diff / 60)
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`
        return `${Math.floor(hours / 24)} day${Math.floor(hours / 24) > 1 ? 's' : ''} ago`
    }

    const handleStatusChange = (orderId: string, newStatus: string) => {
        router.post(`/orders/${orderId}/status`, {
            status: newStatus,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Status updated
            }
        })
    }

    return (
        <>
            <Page.Header
                title="Operations Center"
                subtitle={location ? `Location: ${location.name}` : undefined}
                actions={
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit('/orders/kitchen/display')}
                        >
                            <ChefHat className="h-4 w-4 mr-1" />
                            Kitchen Display
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            onClick={() => router.visit('/orders/new')}
                        >
                            <AlertCircle className="h-4 w-4 mr-1" />
                            New Order
                        </Button>
                    </div>
                }
            />

            <Page.Content>
                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Today's Orders</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.todayOrders}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Orders</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.activeOrders}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Ready to Serve</CardTitle>
                            <CheckCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.readyToServe}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pending Payment</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pendingPayment}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Status Filter */}
                <div className="mb-6 flex gap-2">
                        <Button
                            variant={selectedStatus === 'all' ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setSelectedStatus('all')}
                        >
                            All Orders
                        </Button>
                        {Object.entries(statusConfig).map(([status, config]) => (
                            <Button
                                key={status}
                                variant={selectedStatus === status ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setSelectedStatus(status)}
                            >
                                {config.icon}
                                <span className="ml-1">{config.label}</span>
                            </Button>
                        ))}
                </div>

                {/* Orders Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {filteredOrders.map((order) => {
                            const config = statusConfig[order.status] || {
                                label: order.status,
                                color: 'bg-gray-100 text-gray-800',
                                icon: null
                            }

                            const timeAgo = getTimeAgo(order.createdAt)
                            const isUrgent = order.status === 'preparing' && timeAgo.includes('hour')

                            return (
                                <Card key={order.id}
                                      className={`hover:shadow-lg transition-shadow cursor-pointer ${isUrgent ? 'border-orange-500 border-2' : ''}`}
                                      onClick={() => router.visit(`/orders/${order.id}`)}>
                                    <CardHeader>
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <CardTitle className="text-lg flex items-center gap-2">
                                                    Order #{order.orderNumber}
                                                    {isUrgent && (
                                                        <Badge variant="destructive" className="text-xs">
                                                            Delayed
                                                        </Badge>
                                                    )}
                                                </CardTitle>
                                                <CardDescription>
                                                    {order.tableNumber && `Table ${order.tableNumber} • `}
                                                    {order.type === 'dine-in' ? 'Dine In' : order.type === 'takeaway' ? 'Takeaway' : 'Delivery'}
                                                </CardDescription>
                                            </div>
                                            <Badge className={config.color}>
                                                {config.icon}
                                                <span className="ml-1">{config.label}</span>
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            {order.customerName && (
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <Users className="w-4 h-4 mr-2" />
                                                    {order.customerName}
                                                </div>
                                            )}

                                            <div className="flex items-center text-sm text-gray-600">
                                                <Clock className="w-4 h-4 mr-2" />
                                                <span className={isUrgent ? 'text-orange-600 font-semibold' : ''}>
                                                    {timeAgo}
                                                </span>
                                            </div>

                                            {/* Show item summary */}
                                            {order.items && order.items.length > 0 && (
                                                <div className="pt-2 border-t">
                                                    <div className="text-xs text-gray-500 mb-1">Items:</div>
                                                    <div className="text-sm space-y-1">
                                                        {order.items.slice(0, 2).map((item, idx) => (
                                                            <div key={idx} className="flex justify-between">
                                                                <span className="text-gray-700">{item.quantity}x {item.name}</span>
                                                                {item.kitchenStatus === 'preparing' && (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        <ChefHat className="h-3 w-3" />
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        ))}
                                                        {order.items.length > 2 && (
                                                            <div className="text-xs text-gray-400">
                                                                +{order.items.length - 2} more items
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            )}

                                            <div className="flex items-center justify-between">
                                                <span className="text-sm text-gray-600">
                                                    {order.itemCount} item{order.itemCount !== 1 ? 's' : ''}
                                                </span>
                                                <span className="font-semibold">
                                                    {formatCurrency(order.totalAmount)}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Quick Actions */}
                                        <div className="mt-4 flex gap-2">
                                            {order.status === 'placed' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={(e) => {
                                                        e.stopPropagation()
                                                        handleStatusChange(order.id, 'confirmed')
                                                    }}
                                                >
                                                    Confirm
                                                </Button>
                                            )}
                                            {order.status === 'confirmed' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={(e) => {
                                                        e.stopPropagation()
                                                        handleStatusChange(order.id, 'preparing')
                                                    }}
                                                >
                                                    Start Preparing
                                                </Button>
                                            )}
                                            {order.status === 'preparing' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={(e) => {
                                                        e.stopPropagation()
                                                        handleStatusChange(order.id, 'ready')
                                                    }}
                                                >
                                                    Mark Ready
                                                </Button>
                                            )}
                                            {order.status === 'ready' && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={(e) => {
                                                        e.stopPropagation()
                                                        handleStatusChange(order.id, 'completed')
                                                    }}
                                                >
                                                    Complete
                                                </Button>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            )
                        })}
                </div>

                {filteredOrders.length === 0 && (
                        <Card className="mt-8">
                            <CardContent className="text-center py-12">
                                <Package className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                <p className="text-gray-500">No {selectedStatus !== 'all' ? statusConfig[selectedStatus]?.label.toLowerCase() : ''} orders found</p>
                            </CardContent>
                        </Card>
                )}
            </Page.Content>
        </>
    )
}

export default function OperationsCenter(props: Props) {
    return (
        <AppLayout>
            <Head title="Operations Center" />
            <Page>
                <OperationsCenterContent {...props} />
            </Page>
        </AppLayout>
    )
}