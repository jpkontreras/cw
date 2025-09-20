import React, { useState, useEffect } from 'react'
import { Head, router } from '@inertiajs/react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
    Clock, Package, CheckCircle, AlertCircle, ChefHat, Users, DollarSign,
    TrendingUp, TrendingDown, Activity, Timer, AlertTriangle, Utensils,
    ShoppingBag, Truck, MapPin, BarChart3, Zap, Coffee, Pizza, Salad,
    ArrowUp, ArrowDown, RefreshCw, Filter, Download, Settings, Eye
} from 'lucide-react'
import { formatCurrency } from '@/lib/format'
import AppLayout from '@/layouts/app-layout'
import Page from '@/layouts/page-layout'
import { cn } from '@/lib/utils'
import { EmptyState } from '@/components/empty-state'

// Types
interface OrderItem {
    id: number
    name: string
    quantity: number
    status: string
    kitchenStatus: string
    preparationTime?: number
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
    preparationStartedAt?: string
    estimatedReadyTime?: string
}

interface Stats {
    todayOrders: number
    activeOrders: number
    readyToServe: number
    pendingPayment: number
    todayRevenue: number
    averageOrderValue: number
    averagePrepTime: number
    ordersPerHour: number
    completedToday: number
    cancelledToday: number
}

interface StaffMember {
    id: number
    name: string
    role: string
    status: 'active' | 'break' | 'offline'
    ordersHandled: number
    averageTime: number
}

interface Alert {
    id: string
    type: 'warning' | 'error' | 'info'
    title: string
    description: string
    timestamp: string
}

interface Props {
    orders: Order[]
    stats: Stats
    location?: {
        id: number
        name: string
    }
    staff?: StaffMember[]
    alerts?: Alert[]
    hourlyData?: {
        hour: string
        orders: number
        revenue: number
    }[]
}

function OperationsCenterContent({ orders = [], stats, location, staff = [], alerts = [], hourlyData = [] }: Props) {
    const [selectedView, setSelectedView] = useState<'overview' | 'orders' | 'kitchen' | 'staff'>('overview')
    const [autoRefresh, setAutoRefresh] = useState(true)
    const [lastRefresh, setLastRefresh] = useState(new Date())
    const [isRefreshing, setIsRefreshing] = useState(false)

    // Auto-refresh every 30 seconds
    useEffect(() => {
        if (!autoRefresh) return

        const interval = setInterval(() => {
            setIsRefreshing(true)
            router.reload({
                only: ['orders', 'stats', 'staff', 'alerts'],
                preserveState: true,
                onFinish: () => setIsRefreshing(false),
            })
            setLastRefresh(new Date())
        }, 30000)

        return () => clearInterval(interval)
    }, [autoRefresh])

    const getTimeAgo = (date: string) => {
        const now = new Date()
        const created = new Date(date)
        const diff = Math.floor((now.getTime() - created.getTime()) / 1000 / 60)

        if (diff < 1) return 'Just now'
        if (diff < 60) return `${diff}m`
        const hours = Math.floor(diff / 60)
        if (hours < 24) return `${hours}h`
        return `${Math.floor(hours / 24)}d`
    }

    const getOrdersByStatus = (status: string) => {
        return orders.filter(order => order.status === status)
    }

    // Calculate real-time metrics - with null safety
    const delayedOrders = orders.filter(order => {
        const created = new Date(order.createdAt)
        const now = new Date()
        const diffMinutes = (now.getTime() - created.getTime()) / 1000 / 60
        return order.status === 'preparing' && diffMinutes > 30
    })

    const peakHour = hourlyData.reduce((max, current) =>
        current.orders > (max?.orders || 0) ? current : max, hourlyData[0])

    const currentHourOrders = hourlyData.find(h => {
        const hour = new Date().getHours()
        return h.hour === `${hour}:00`
    })?.orders || 0

    const getStatusColor = (status: string) => {
        switch(status) {
            case 'placed': return 'bg-blue-500'
            case 'confirmed': return 'bg-green-500'
            case 'preparing': return 'bg-yellow-500'
            case 'ready': return 'bg-purple-500'
            case 'completed': return 'bg-gray-500'
            default: return 'bg-gray-400'
        }
    }

    const getAlertIcon = (type: string) => {
        switch(type) {
            case 'error': return <AlertCircle className="h-4 w-4 text-red-500" />
            case 'warning': return <AlertTriangle className="h-4 w-4 text-yellow-500" />
            default: return <Activity className="h-4 w-4 text-blue-500" />
        }
    }

    return (
        <>
            {/* Header with Actions */}
            <div className="border-b bg-white sticky top-0 z-10">
                <div className="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Operations Center</h1>
                        <p className="text-sm text-gray-500 mt-1">
                            {location?.name || 'All Locations'} • Real-time monitoring
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Auto-refresh indicator */}
                        <div className="flex items-center gap-2 text-sm text-gray-500">
                            <RefreshCw className={cn("h-4 w-4", isRefreshing && "animate-spin")} />
                            <span>Last: {lastRefresh.toLocaleTimeString()}</span>
                        </div>

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setAutoRefresh(!autoRefresh)}
                        >
                            {autoRefresh ? 'Pause' : 'Resume'} Refresh
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit('/orders/kitchen-display')}
                        >
                            <ChefHat className="h-4 w-4 mr-1" />
                            Kitchen Display
                        </Button>

                        <Button
                            size="sm"
                            onClick={() => router.visit('/orders/new')}
                        >
                            New Order
                        </Button>
                    </div>
                </div>

                {/* View Tabs */}
                <div className="px-6 pb-4">
                    <Tabs value={selectedView} onValueChange={(v) => setSelectedView(v as 'overview' | 'orders' | 'kitchen' | 'staff')} className="w-full">
                        <TabsList className="grid w-full max-w-md grid-cols-4">
                            <TabsTrigger value="overview">Overview</TabsTrigger>
                            <TabsTrigger value="orders">Orders</TabsTrigger>
                            <TabsTrigger value="kitchen">Kitchen</TabsTrigger>
                            <TabsTrigger value="staff">Staff</TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>
            </div>

            <div className="p-6 pt-2">
                {selectedView === 'overview' && (
                    <div className="space-y-6">
                        {/* Show empty state if no orders at all */}
                        {orders.length === 0 ? (
                            <EmptyState
                                icon={Activity}
                                title="No activity yet"
                                description="Your operations dashboard will populate when orders start coming in"
                                actions={
                                    <Button
                                        onClick={() => router.visit('/orders/new')}
                                    >
                                        Create First Order
                                    </Button>
                                }
                            />
                        ) : (
                            <>
                        {/* Critical Alerts */}
                        {(delayedOrders.length > 0 || alerts.length > 0) && (
                            <Card className="border-orange-200 bg-orange-50">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <AlertTriangle className="h-5 w-5 text-orange-600" />
                                        Requires Attention
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {delayedOrders.length > 0 && (
                                        <div className="flex items-center justify-between p-3 bg-white rounded-lg">
                                            <div className="flex items-center gap-3">
                                                <Timer className="h-5 w-5 text-orange-600" />
                                                <div>
                                                    <p className="font-medium">{delayedOrders.length} Delayed Orders</p>
                                                    <p className="text-sm text-gray-500">Orders exceeding 30 minutes</p>
                                                </div>
                                            </div>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => setSelectedView('orders')}
                                            >
                                                View Orders
                                            </Button>
                                        </div>
                                    )}

                                    {alerts.slice(0, 3).map(alert => (
                                        <div key={alert.id} className="flex items-start gap-3 p-3 bg-white rounded-lg">
                                            {getAlertIcon(alert.type)}
                                            <div className="flex-1">
                                                <p className="font-medium text-sm">{alert.title}</p>
                                                <p className="text-xs text-gray-500">{alert.description}</p>
                                            </div>
                                            <span className="text-xs text-gray-400">{getTimeAgo(alert.timestamp)}</span>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {/* Key Performance Metrics */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-gray-600">Today's Revenue</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-2xl font-bold">{formatCurrency(stats.todayRevenue || 0)}</span>
                                        <Badge variant="outline" className="text-xs">
                                            <TrendingUp className="h-3 w-3 mr-1" />
                                            12%
                                        </Badge>
                                    </div>
                                    <Progress value={75} className="mt-2" />
                                    <p className="text-xs text-gray-500 mt-1">75% of daily target</p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-gray-600">Active Orders</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{stats.activeOrders}</div>
                                    <div className="flex gap-2 mt-2">
                                        <Badge variant="outline" className="text-xs">
                                            <Clock className="h-3 w-3 mr-1" />
                                            {stats.averagePrepTime || 15}m avg
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-gray-600">Orders/Hour</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-2xl font-bold">{currentHourOrders}</span>
                                        <span className="text-sm text-gray-500">/ {stats.ordersPerHour || 8} avg</span>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-2">
                                        Peak: {peakHour?.hour} ({peakHour?.orders} orders)
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-gray-600">Completion Rate</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">
                                        {stats.completedToday ? Math.round((stats.completedToday / (stats.completedToday + stats.cancelledToday)) * 100) : 100}%
                                    </div>
                                    <div className="flex gap-2 mt-2 text-xs">
                                        <span className="text-green-600">{stats.completedToday || 0} completed</span>
                                        <span className="text-red-600">{stats.cancelledToday || 0} cancelled</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Order Pipeline */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Order Pipeline</CardTitle>
                                <CardDescription>Real-time order flow visualization</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-5 gap-4">
                                    {['placed', 'confirmed', 'preparing', 'ready', 'completed'].map((status) => {
                                        const count = getOrdersByStatus(status).length
                                        const percentage = orders.length > 0 ? (count / orders.length) * 100 : 0

                                        return (
                                            <div key={status} className="text-center">
                                                <div className="relative">
                                                    <div className={cn(
                                                        "h-32 rounded-lg flex items-end justify-center pb-2",
                                                        "bg-gradient-to-t from-gray-100 to-transparent"
                                                    )}>
                                                        <div
                                                            className={cn("w-full rounded-t-lg transition-all", getStatusColor(status))}
                                                            style={{ height: `${Math.max(percentage, 5)}%` }}
                                                        />
                                                    </div>
                                                    <span className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-2xl font-bold">
                                                        {count}
                                                    </span>
                                                </div>
                                                <p className="text-sm font-medium mt-2 capitalize">{status}</p>
                                            </div>
                                        )
                                    })}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Staff Overview */}
                        {staff.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Active Staff Performance</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        {staff.filter(s => s.status === 'active').slice(0, 6).map(member => (
                                            <div key={member.id} className="flex items-center justify-between p-3 border rounded-lg">
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                        <Users className="h-5 w-5 text-gray-600" />
                                                    </div>
                                                    <div>
                                                        <p className="font-medium text-sm">{member.name}</p>
                                                        <p className="text-xs text-gray-500">{member.role}</p>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-bold text-sm">{member.ordersHandled}</p>
                                                    <p className="text-xs text-gray-500">{member.averageTime}m avg</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                            </>
                        )}
                    </div>
                )}

                {selectedView === 'orders' && (
                    <div className="space-y-4">
                        {orders.length === 0 ? (
                            <EmptyState
                                icon={ShoppingBag}
                                title="No active orders"
                                description="Orders will appear here when customers place them"
                            />
                        ) : (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Active Orders Management</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {orders.slice(0, 10).map(order => (
                                            <div
                                                key={order.id}
                                                className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 cursor-pointer"
                                                onClick={() => router.visit(`/orders/${order.id}`)}
                                            >
                                                <div className="flex items-center gap-4">
                                                    <div>
                                                        <p className="font-medium">Order #{order.orderNumber}</p>
                                                        <p className="text-sm text-gray-500">
                                                            {order.customerName || 'Guest'} • {getTimeAgo(order.createdAt)} ago
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <Badge className={cn("", getStatusColor(order.status), "text-white")}>
                                                        {order.status}
                                                    </Badge>
                                                    <span className="font-medium">{formatCurrency(order.totalAmount)}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}

                {selectedView === 'kitchen' && (
                    <div>
                        {orders.length === 0 ? (
                            <EmptyState
                                    icon={ChefHat}
                                    title="No kitchen activity"
                                    description="Kitchen metrics will appear here when orders are being prepared"
                                    actions={
                                        <Button
                                            onClick={() => router.visit('/orders/kitchen-display')}
                                        >
                                            Open Kitchen Display
                                        </Button>
                                    }
                                />
                        ) : (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Kitchen Performance</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-3 gap-4">
                                        <div className="text-center">
                                            <p className="text-2xl font-bold">15m</p>
                                            <p className="text-sm text-gray-500">Avg Prep Time</p>
                                        </div>
                                        <div className="text-center">
                                            <p className="text-2xl font-bold">98%</p>
                                            <p className="text-sm text-gray-500">On-Time Rate</p>
                                        </div>
                                        <div className="text-center">
                                            <p className="text-2xl font-bold">2.3m</p>
                                            <p className="text-sm text-gray-500">Fastest Order</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}

                {selectedView === 'staff' && (
                    <div>
                        {staff.length === 0 ? (
                            <EmptyState
                                icon={Users}
                                title="No staff members"
                                description="Staff members will appear here once they are added to the system"
                            />
                        ) : (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Staff Management</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {staff.slice(0, 5).map((member, i) => (
                                            <div key={i} className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                        {member.name.charAt(0)}
                                                    </div>
                                                    <div>
                                                        <p className="font-medium">{member.name}</p>
                                                        <p className="text-sm text-gray-500">{member.role}</p>
                                                    </div>
                                                </div>
                                                <Badge className={member.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100'}>
                                                    {member.status}
                                                </Badge>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>
        </>
    )
}

export default function OperationsCenter(props: Props) {
    return (
        <AppLayout>
            <Head title="Operations Center" />
            <OperationsCenterContent {...props} />
        </AppLayout>
    )
}