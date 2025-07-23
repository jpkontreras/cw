import { PageContent, PageHeader, PageLayout } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { Order, OrderDetailPageProps, PaymentTransaction } from '@/types/modules/order';
import { PAYMENT_METHOD_CONFIG } from '@/types/modules/order/constants';
import {
  formatCurrency,
  formatOrderNumber,
  formatPhone,
  getKitchenStatusColor,
  getKitchenStatusLabel,
  getOrderAge,
  getStatusLabel,
  getTypeLabel,
} from '@/types/modules/order/utils';
import { Head, Link, router } from '@inertiajs/react';
import {
  AlertCircle,
  ArrowLeft,
  ArrowRight,
  CheckCircle,
  CheckCircle2,
  ChefHat,
  ChevronLeft,
  ChevronRight,
  Clock,
  Copy,
  CreditCard,
  DollarSign,
  Download,
  Edit,
  FileText,
  Flame,
  Home,
  Mail,
  MapPin,
  MoreVertical,
  Package,
  Phone,
  Printer,
  RefreshCw,
  Share2,
  ShoppingBag,
  Truck,
  User,
  XCircle,
} from 'lucide-react';
import { useState } from 'react';

// Enhanced Order Timeline Component
const OrderTimeline = ({ order }: { order: Order }) => {
  const statuses =
    order.type === 'delivery'
      ? ['placed', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'completed']
      : ['placed', 'confirmed', 'preparing', 'ready', 'completed'];
  const currentStatusIndex = statuses.indexOf(order.status);

  // Timeline status configuration
  const statusConfig: Record<string, { icon: any; label: string; color: string }> = {
    placed: { icon: ShoppingBag, label: 'Order Placed', color: 'text-gray-600' },
    confirmed: { icon: CheckCircle, label: 'Confirmed', color: 'text-blue-600' },
    preparing: { icon: ChefHat, label: 'Preparing', color: 'text-orange-600' },
    ready: { icon: Package, label: 'Ready', color: 'text-green-600' },
    delivering: { icon: Truck, label: 'Out for Delivery', color: 'text-purple-600' },
    delivered: { icon: Home, label: 'Delivered', color: 'text-green-600' },
    completed: { icon: CheckCircle2, label: 'Completed', color: 'text-green-600' },
  };

  return (
    <div className="relative h-full">
      {/* Timeline */}
      <div className="space-y-2">
        {statuses.map((status, index) => {
          const isPast = index <= currentStatusIndex;
          const isCurrent = status === order.status;
          const timestamp = order[`${status}At` as keyof Order] as string | undefined;
          const config = statusConfig[status];
          const Icon = config.icon;

          return (
            <div key={status} className="group relative flex items-start">
              {/* Timeline line */}
              {index < statuses.length - 1 && (
                <div
                  className={cn(
                    'absolute left-5 w-0.5 transition-all duration-500',
                    isPast ? 'bg-gradient-to-b from-primary to-primary/60' : 'bg-gray-200',
                  )}
                  style={{
                    top: '40px',
                    height: '64px',
                  }}
                />
              )}

              {/* Status indicator */}
              <div
                className={cn(
                  'relative z-10 flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300',
                  isPast ? 'bg-primary text-white shadow-md' : 'bg-gray-100 text-gray-400',
                  isCurrent && 'scale-110 ring-4 ring-primary/20',
                )}
              >
                <Icon className="h-5 w-5" />
              </div>

              {/* Status content */}
              <div className="ml-4 flex-1 pb-8 last:pb-0">
                <div className="flex items-start justify-between">
                  <div>
                    <h4 className={cn('font-medium transition-colors', isPast ? 'text-gray-900' : 'text-gray-400')}>{config.label}</h4>
                    {timestamp && (
                      <p className="mt-0.5 text-sm text-gray-500">
                        {new Date(timestamp).toLocaleString('en-US', {
                          month: 'short',
                          day: 'numeric',
                          hour: 'numeric',
                          minute: '2-digit',
                          hour12: true,
                        })}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            </div>
          );
        })}

        {/* Cancelled status */}
        {order.status === 'cancelled' && (
          <div className="group relative flex items-start">
            <div className="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-white shadow-md">
              <XCircle className="h-5 w-5" />
            </div>
            <div className="ml-4 flex-1">
              <h4 className="font-medium text-red-600">Order Cancelled</h4>
              {order.cancelledAt && (
                <p className="mt-0.5 text-sm text-gray-500">
                  {new Date(order.cancelledAt).toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true,
                  })}
                </p>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

// Enhanced Payment History Component
const PaymentHistory = ({ payments }: { payments: PaymentTransaction[] }) => {
  if (!payments || payments.length === 0) {
    return (
      <div className="py-12 text-center">
        <div className="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
          <CreditCard className="h-8 w-8 text-gray-400" />
        </div>
        <p className="text-base font-medium text-gray-900">No payment transactions yet</p>
        <p className="mt-1 text-sm text-gray-500">Payments will appear here once processed</p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {payments.map((payment) => {
        const methodConfig = PAYMENT_METHOD_CONFIG[payment.method as keyof typeof PAYMENT_METHOD_CONFIG];
        return (
          <div key={payment.id} className="group rounded-lg border border-gray-200 p-4 transition-all duration-200 hover:shadow-md">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div
                  className={cn(
                    'rounded-lg p-2 transition-colors',
                    payment.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600',
                  )}
                >
                  <CreditCard className="h-5 w-5" />
                </div>
                <div>
                  <p className="font-medium text-gray-900">{methodConfig?.label || payment.method}</p>
                  <p className="text-sm text-gray-500">{payment.referenceNumber || 'Processing...'}</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-lg font-semibold text-gray-900">{formatCurrency(payment.amount)}</p>
                <Badge variant={payment.status === 'completed' ? 'default' : 'secondary'} className="text-xs">
                  {payment.status}
                </Badge>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
};

// Status dropdown component
const StatusDropdown = ({ order }: { order: Order }) => {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showReasonDialog, setShowReasonDialog] = useState(false);
  const [pendingStatus, setPendingStatus] = useState<string | null>(null);
  const [reason, setReason] = useState('');

  const allStatuses = [
    { value: 'placed', label: 'Order Placed', icon: ShoppingBag, color: 'text-gray-600' },
    { value: 'confirmed', label: 'Confirmed', icon: CheckCircle, color: 'text-blue-600' },
    { value: 'preparing', label: 'Preparing', icon: ChefHat, color: 'text-orange-600' },
    { value: 'ready', label: 'Ready', icon: Package, color: 'text-green-600' },
    { value: 'delivering', label: 'Out for Delivery', icon: Truck, color: 'text-purple-600', requiresDelivery: true },
    { value: 'delivered', label: 'Delivered', icon: Home, color: 'text-green-600', requiresDelivery: true },
    { value: 'completed', label: 'Completed', icon: CheckCircle2, color: 'text-green-600' },
    { value: 'cancelled', label: 'Cancelled', icon: XCircle, color: 'text-red-600', requiresReason: true },
  ];

  // Filter statuses based on order type
  const statuses = allStatuses.filter((status) => {
    if (status.requiresDelivery && order.type !== 'delivery') {
      return false;
    }
    return true;
  });

  const currentIndex = statuses.findIndex((s) => s.value === order.status);

  // Get forward and backward statuses
  const forwardStatuses = statuses.filter((status, index) => {
    if (status.value === 'cancelled') return false; // Cancel is special
    if (status.value === order.status) return false;
    return index > currentIndex;
  });

  const backwardStatuses = statuses.filter((status, index) => {
    if (status.value === 'cancelled') return false; // Cancel is special
    if (status.value === order.status) return false;
    return index < currentIndex;
  });

  // Get next and previous status
  const nextStatus = currentIndex < statuses.length - 1 && statuses[currentIndex + 1].value !== 'cancelled' ? statuses[currentIndex + 1] : null;
  const prevStatus = currentIndex > 0 ? statuses[currentIndex - 1] : null;

  const getStatusAction = (status: string): string => {
    const actions: Record<string, string> = {
      placed: 'place',
      confirmed: 'confirm',
      preparing: 'start-preparing',
      ready: 'mark-ready',
      delivering: 'start-delivery',
      delivered: 'mark-delivered',
      completed: 'complete',
      cancelled: 'cancel',
    };
    return actions[status] || status;
  };

  const requiresReason = (targetStatus: string): boolean => {
    const statusConfig = statuses.find((s) => s.value === targetStatus);
    if (statusConfig?.requiresReason) return true;

    // Going backwards requires reason
    const currentIndex = statuses.findIndex((s) => s.value === order.status);
    const targetIndex = statuses.findIndex((s) => s.value === targetStatus);
    return currentIndex > targetIndex;
  };

  const handleStatusSelect = (newStatus: string) => {
    const statusConfig = statuses.find((s) => s.value === newStatus);

    // Check if confirmation needed
    const criticalStatuses = ['cancelled', 'completed'];
    const isGoingBack = statuses.findIndex((s) => s.value === newStatus) < statuses.findIndex((s) => s.value === order.status);

    if (criticalStatuses.includes(newStatus) || isGoingBack) {
      const confirmMessage =
        newStatus === 'cancelled'
          ? 'Are you sure you want to cancel this order? This action cannot be undone.'
          : isGoingBack
            ? `Are you sure you want to move this order back to ${statusConfig?.label}? This will reset progress.`
            : `Are you sure you want to mark this order as ${statusConfig?.label}?`;

      if (!confirm(confirmMessage)) {
        return;
      }
    }

    // Check if reason is required
    if (requiresReason(newStatus)) {
      setPendingStatus(newStatus);
      setShowReasonDialog(true);
      return;
    }

    // Direct update without reason
    updateStatus(newStatus);
  };

  const updateStatus = (status: string, reason?: string) => {
    setIsSubmitting(true);
    const action = getStatusAction(status);

    router.post(`/orders/${order.id}/${action}`, reason ? { reason } : {}, {
      preserveScroll: true,
      onFinish: () => {
        setIsSubmitting(false);
        setShowReasonDialog(false);
        setPendingStatus(null);
        setReason('');
      },
    });
  };

  const handleReasonSubmit = () => {
    if (pendingStatus && reason.trim()) {
      updateStatus(pendingStatus, reason);
    }
  };

  // Don't show dropdown for terminal states
  if (order.status === 'completed' || order.status === 'cancelled') {
    return null;
  }

  return (
    <>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button disabled={isSubmitting}>
            {isSubmitting ? (
              <>
                <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                Updating...
              </>
            ) : (
              <>
                <RefreshCw className="mr-2 h-4 w-4" />
                Update Status
              </>
            )}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-72">
          {/* Quick Navigation */}
          <div className="flex gap-2 border-b p-2">
            <Button
              size="sm"
              variant="outline"
              className="flex-1"
              onClick={() => prevStatus && handleStatusSelect(prevStatus.value)}
              disabled={!prevStatus || isSubmitting}
            >
              <ChevronLeft className="mr-1 h-4 w-4" />
              Previous
            </Button>
            <Button
              size="sm"
              variant="outline"
              className="flex-1"
              onClick={() => nextStatus && handleStatusSelect(nextStatus.value)}
              disabled={!nextStatus || isSubmitting}
            >
              Next
              <ChevronRight className="ml-1 h-4 w-4" />
            </Button>
          </div>

          {/* Forward Actions */}
          {forwardStatuses.length > 0 && (
            <>
              <DropdownMenuLabel className="text-xs text-gray-500">Move Forward</DropdownMenuLabel>
              {forwardStatuses.map((status) => {
                const Icon = status.icon;
                const isNext = nextStatus?.value === status.value;
                return (
                  <DropdownMenuItem key={status.value} onClick={() => handleStatusSelect(status.value)} className="cursor-pointer">
                    <ArrowRight className="mr-2 h-4 w-4 text-green-600" />
                    <Icon className={cn('mr-2 h-4 w-4', status.color)} />
                    <span className="flex-1">{status.label}</span>
                    {isNext && (
                      <Badge variant="secondary" className="ml-2 text-xs">
                        Next
                      </Badge>
                    )}
                  </DropdownMenuItem>
                );
              })}
            </>
          )}

          {/* Backward Actions */}
          {backwardStatuses.length > 0 && (
            <>
              <DropdownMenuSeparator />
              <DropdownMenuLabel className="text-xs text-gray-500">Move Back</DropdownMenuLabel>
              {backwardStatuses.map((status) => {
                const Icon = status.icon;
                return (
                  <DropdownMenuItem key={status.value} onClick={() => handleStatusSelect(status.value)} className="cursor-pointer">
                    <ArrowLeft className="mr-2 h-4 w-4 text-orange-600" />
                    <Icon className={cn('mr-2 h-4 w-4', status.color)} />
                    <span className="flex-1">{status.label}</span>
                    <span className="ml-2 text-xs text-gray-500">Requires reason</span>
                  </DropdownMenuItem>
                );
              })}
            </>
          )}

          {/* Special Actions */}
          {order.status !== 'cancelled' && order.status !== 'completed' && (
            <>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => handleStatusSelect('cancelled')} className="cursor-pointer text-red-600 focus:text-red-600">
                <XCircle className="mr-2 h-4 w-4" />
                Cancel Order
                <span className="ml-auto text-xs text-gray-500">Requires reason</span>
              </DropdownMenuItem>
            </>
          )}
        </DropdownMenuContent>
      </DropdownMenu>

      {/* Reason dialog */}
      <Dialog open={showReasonDialog} onOpenChange={setShowReasonDialog}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Reason Required</DialogTitle>
            <DialogDescription>Please provide a reason for this status change.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <Textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder={
                pendingStatus === 'cancelled' ? 'Please provide a reason for cancellation...' : 'Please provide a reason for this status change...'
              }
              className="min-h-[100px]"
              autoFocus
            />
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setShowReasonDialog(false);
                setPendingStatus(null);
                setReason('');
              }}
            >
              Cancel
            </Button>
            <Button onClick={handleReasonSubmit} disabled={!reason.trim()}>
              Update Status
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
};

export default function ShowOrder({ order: orderData, user, location, payments = [], isPaid, remainingAmount }: OrderDetailPageProps) {
  const [isRefreshing, setIsRefreshing] = useState(false);

  const order = orderData as Order;

  const handleStatusUpdate = (action: string) => {
    router.post(
      `/orders/${order.id}/${action}`,
      {},
      {
        preserveScroll: true,
      },
    );
  };

  const handleRefresh = () => {
    setIsRefreshing(true);
    router.reload({
      onFinish: () => setIsRefreshing(false),
    });
  };

  const handleCopyOrderNumber = () => {
    navigator.clipboard.writeText(order.orderNumber || '');
    // TODO: Show toast notification
  };

  const canEdit = ['draft', 'placed'].includes(order.status);
  const canCancel = ['draft', 'placed', 'confirmed'].includes(order.status);
  const showDeliveryInfo = order.type === 'delivery';
  const showTableInfo = order.type === 'dine_in';

  const orderNumber = formatOrderNumber(order.orderNumber);
  const headerDescription = `${getTypeLabel(order.type)} • ${getOrderAge(order)}`;

  // Get order type icon
  const getOrderTypeIcon = () => {
    switch (order.type) {
      case 'dine_in':
        return <Home className="h-4 w-4" />;
      case 'takeout':
        return <ShoppingBag className="h-4 w-4" />;
      case 'delivery':
        return <Truck className="h-4 w-4" />;
      default:
        return <Package className="h-4 w-4" />;
    }
  };

  return (
    <AppLayout>
      <Head title={`Order ${formatOrderNumber(order.orderNumber)}`} />

      <PageLayout>
        <PageHeader title={orderNumber} description={headerDescription}>
          {/* Primary Action - Status Management */}
          <StatusDropdown order={order} />

          {/* Secondary Actions */}
          {canEdit && (
            <Link href={`/orders/${order.id}/edit`}>
              <Button variant="outline">
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Button>
            </Link>
          )}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon">
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
              <DropdownMenuItem onClick={handleCopyOrderNumber}>
                <Copy className="mr-2 h-3.5 w-3.5" />
                Copy Order Number
              </DropdownMenuItem>
              <DropdownMenuItem onClick={handleRefresh} disabled={isRefreshing}>
                <RefreshCw className={cn('mr-2 h-3.5 w-3.5', isRefreshing && 'animate-spin')} />
                Refresh
              </DropdownMenuItem>
              <DropdownMenuItem>
                <Share2 className="mr-2 h-3.5 w-3.5" />
                Share
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <Link href={`/orders/${order.id}/receipt`}>
                <DropdownMenuItem>
                  <Printer className="mr-2 h-3.5 w-3.5" />
                  Print Receipt
                </DropdownMenuItem>
              </Link>
              <DropdownMenuItem>
                <FileText className="mr-2 h-3.5 w-3.5" />
                View Invoice
              </DropdownMenuItem>
              <DropdownMenuItem>
                <Download className="mr-2 h-3.5 w-3.5" />
                Export PDF
              </DropdownMenuItem>
              {canCancel && (
                <>
                  <DropdownMenuSeparator />
                  <Link href={`/orders/${order.id}/cancel`}>
                    <DropdownMenuItem className="text-red-600 hover:bg-red-50 hover:text-red-700">
                      <XCircle className="mr-2 h-3.5 w-3.5" />
                      Cancel Order
                    </DropdownMenuItem>
                  </Link>
                </>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        </PageHeader>

        <PageContent noPadding>
          <div className="px-4 py-4 lg:px-6">
            <Tabs defaultValue="details" className="w-full">
              <TabsList className="grid w-full grid-cols-4">
                <TabsTrigger value="details">Details</TabsTrigger>
                <TabsTrigger value="items">Items</TabsTrigger>
                <TabsTrigger value="payment">Payment</TabsTrigger>
                <TabsTrigger value="info">Info</TabsTrigger>
              </TabsList>

              <TabsContent value="details">
                <Card className="mt-6">
                  <CardContent className="p-0">
                    <div className="grid grid-cols-1 divide-x divide-gray-200 lg:grid-cols-3">
                      {/* Main Content */}
                      <div className="p-6 lg:col-span-2">
                        <div className="space-y-6">
                          {/* Status Section */}
                          <div>
                            <h3 className="mb-3 text-base font-semibold">Order Status</h3>
                            <div className="flex flex-wrap items-center gap-3">
                              <Badge
                                className={cn(
                                  'px-3 py-1 text-sm font-medium',
                                  order.status === 'cancelled'
                                    ? 'border-red-200 bg-red-100 text-red-700'
                                    : 'border-blue-200 bg-blue-100 text-blue-700',
                                )}
                              >
                                {getStatusLabel(order.status)}
                              </Badge>
                              {order.priority === 'high' && (
                                <Badge variant="destructive" className="border-red-200 bg-red-100 text-red-700">
                                  <Flame className="mr-1 h-3 w-3" />
                                  High Priority
                                </Badge>
                              )}
                              {isPaid && (
                                <Badge className="border-green-200 bg-green-100 text-green-700">
                                  <CheckCircle className="mr-1 h-3 w-3" />
                                  Paid
                                </Badge>
                              )}
                            </div>
                          </div>

                          <Separator />

                          {/* Customer Information */}
                          <div>
                            <h3 className="mb-4 flex items-center gap-2 text-base font-semibold">
                              <User className="h-4 w-4 text-gray-600" />
                              Customer Information
                            </h3>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                              <div className="group">
                                <p className="mb-1 text-sm text-gray-500">Name</p>
                                <p className="font-medium text-gray-900">{order.customerName || 'Walk-in Customer'}</p>
                              </div>
                              {order.customerPhone && (
                                <div className="group">
                                  <p className="mb-1 text-sm text-gray-500">Phone</p>
                                  <p className="flex items-center gap-2 font-medium text-gray-900">
                                    <Phone className="h-4 w-4 text-gray-400" />
                                    {formatPhone(order.customerPhone)}
                                  </p>
                                </div>
                              )}
                              {order.customerEmail && (
                                <div className="group">
                                  <p className="mb-1 text-sm text-gray-500">Email</p>
                                  <p className="flex items-center gap-2 font-medium text-gray-900">
                                    <Mail className="h-4 w-4 text-gray-400" />
                                    {order.customerEmail}
                                  </p>
                                </div>
                              )}
                              {showTableInfo && order.tableNumber && (
                                <div className="group">
                                  <p className="mb-1 text-sm text-gray-500">Table</p>
                                  <p className="font-medium text-gray-900">Table {order.tableNumber}</p>
                                </div>
                              )}
                            </div>
                          </div>

                          {/* Delivery Information */}
                          {showDeliveryInfo && order.deliveryAddress && (
                            <>
                              <Separator />
                              <div>
                                <h3 className="mb-4 flex items-center gap-2 text-base font-semibold">
                                  <MapPin className="h-4 w-4 text-gray-600" />
                                  Delivery Information
                                </h3>
                                <p className="leading-relaxed text-gray-700">{order.deliveryAddress}</p>
                              </div>
                            </>
                          )}

                          {/* Notes & Instructions */}
                          {(order.notes || order.specialInstructions) && (
                            <>
                              <Separator />
                              <div>
                                <h3 className="mb-4 flex items-center gap-2 text-base font-semibold">
                                  <FileText className="h-4 w-4 text-gray-600" />
                                  Notes & Instructions
                                </h3>
                                {order.notes && (
                                  <div className="mb-4">
                                    <p className="mb-1 text-sm text-gray-500">Order Notes</p>
                                    <p className="text-gray-700">{order.notes}</p>
                                  </div>
                                )}
                                {order.specialInstructions && (
                                  <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <div className="flex items-start gap-3">
                                      <ChefHat className="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" />
                                      <div>
                                        <p className="text-sm font-medium text-amber-900">Kitchen Instructions</p>
                                        <p className="mt-1 text-sm text-amber-800">{order.specialInstructions}</p>
                                      </div>
                                    </div>
                                  </div>
                                )}
                              </div>
                            </>
                          )}
                        </div>
                      </div>

                      {/* Timeline Sidebar */}
                      <div className="p-6 lg:col-span-1">
                        <h3 className="mb-6 text-base font-semibold">Order Timeline</h3>
                        <OrderTimeline order={order} />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="items">
                <Card className="mt-6">
                  <CardContent className="p-6">
                    <div className="mb-6 flex items-center justify-between">
                      <h3 className="flex items-center gap-2 text-base font-semibold">
                        <Package className="h-4 w-4 text-gray-600" />
                        Order Items
                      </h3>
                      <Badge variant="secondary" className="px-3">
                        {order.items?.length || 0} {order.items?.length === 1 ? 'item' : 'items'}
                      </Badge>
                    </div>

                    {!order.items || order.items.length === 0 ? (
                      <div className="py-12 text-center">
                        <div className="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                          <Package className="h-8 w-8 text-gray-400" />
                        </div>
                        <p className="text-base font-medium text-gray-900">No items in this order</p>
                        <p className="mt-1 text-sm text-gray-500">Items will appear here once added</p>
                      </div>
                    ) : (
                      <div className="space-y-4">
                        {order.items.map((item: any) => (
                          <div key={item.id} className="group -mx-4 rounded-lg p-4 transition-colors hover:bg-gray-50">
                            <div className="flex items-start justify-between">
                              <div className="flex-1">
                                <div className="flex items-center gap-3">
                                  <span className="text-base font-medium text-gray-900">
                                    {item.quantity}× {item.itemName}
                                  </span>
                                  <Badge variant="outline" className={cn('text-xs', getKitchenStatusColor(item.kitchenStatus))}>
                                    {getKitchenStatusLabel(item.kitchenStatus)}
                                  </Badge>
                                  {item.course && (
                                    <Badge variant="secondary" className="text-xs">
                                      {item.course}
                                    </Badge>
                                  )}
                                </div>
                                {item.modifiers && item.modifiers.length > 0 && (
                                  <div className="mt-2 text-sm text-gray-600">
                                    <span className="font-medium">Modifiers:</span>{' '}
                                    {item.modifiers.map((mod: any, idx: number) => (
                                      <span key={idx}>
                                        {mod.modifierName} (+{formatCurrency(mod.price)}){idx < item.modifiers.length - 1 && ', '}
                                      </span>
                                    ))}
                                  </div>
                                )}
                                {item.notes && (
                                  <p className="mt-2 flex items-start gap-1 text-sm text-gray-500 italic">
                                    <AlertCircle className="mt-0.5 h-3 w-3 flex-shrink-0" />
                                    {item.notes}
                                  </p>
                                )}
                              </div>
                              <div className="ml-4 text-right">
                                <p className="text-base font-semibold text-gray-900">{formatCurrency(item.totalPrice)}</p>
                                <p className="text-sm text-gray-500">{formatCurrency(item.unitPrice)} each</p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}

                    {/* Financial Summary */}
                    {order.items && order.items.length > 0 && (
                      <>
                        <Separator className="my-6" />
                        <div className="space-y-3">
                          <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Subtotal</span>
                            <span className="font-medium">{formatCurrency(order.subtotal)}</span>
                          </div>
                          {order.taxAmount > 0 && (
                            <div className="flex justify-between text-sm">
                              <span className="text-gray-600">Tax (19%)</span>
                              <span className="font-medium">{formatCurrency(order.taxAmount)}</span>
                            </div>
                          )}
                          {order.tipAmount > 0 && (
                            <div className="flex justify-between text-sm">
                              <span className="text-gray-600">Tip</span>
                              <span className="font-medium">{formatCurrency(order.tipAmount)}</span>
                            </div>
                          )}
                          {order.discountAmount > 0 && (
                            <div className="flex justify-between text-sm text-green-600">
                              <span>Discount</span>
                              <span className="font-medium">-{formatCurrency(order.discountAmount)}</span>
                            </div>
                          )}
                          <Separator className="my-3" />
                          <div className="flex items-baseline justify-between">
                            <span className="text-base font-semibold">Total</span>
                            <span className="text-2xl font-bold text-gray-900">{formatCurrency(order.totalAmount)}</span>
                          </div>
                        </div>
                      </>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="payment">
                <Card className="mt-6">
                  <CardContent className="p-6">
                    <h3 className="mb-6 flex items-center gap-2 text-base font-semibold">
                      <CreditCard className="h-4 w-4 text-gray-600" />
                      Payment Information
                    </h3>

                    {!isPaid && (
                      <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-3">
                            <DollarSign className="h-5 w-5 text-amber-600" />
                            <div>
                              <p className="font-medium text-amber-900">Payment Required</p>
                              <p className="text-sm text-amber-800">Remaining amount: {formatCurrency(remainingAmount)}</p>
                            </div>
                          </div>
                          <Link href={`/orders/${order.id}/payment`}>
                            <Button size="sm" variant="default">
                              Process Payment
                            </Button>
                          </Link>
                        </div>
                      </div>
                    )}

                    <PaymentHistory payments={payments} />
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="info">
                <Card className="mt-6">
                  <CardContent className="p-6">
                    <div className="space-y-6">
                      {/* Basic Information */}
                      <div>
                        <h3 className="mb-4 flex items-center gap-2 text-base font-semibold">
                          <FileText className="h-4 w-4 text-gray-600" />
                          Basic Information
                        </h3>
                        <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                          <div>
                            <dt className="text-sm text-gray-500">Order Number</dt>
                            <dd className="mt-1 text-sm font-medium text-gray-900">{order.orderNumber}</dd>
                          </div>
                          <div>
                            <dt className="text-sm text-gray-500">Order Type</dt>
                            <dd className="mt-1 flex items-center gap-2 text-sm font-medium text-gray-900">
                              {getOrderTypeIcon()}
                              {getTypeLabel(order.type)}
                            </dd>
                          </div>
                          <div>
                            <dt className="text-sm text-gray-500">Location</dt>
                            <dd className="mt-1 text-sm font-medium text-gray-900">{location?.name || 'N/A'}</dd>
                          </div>
                          {user && (
                            <div>
                              <dt className="text-sm text-gray-500">Served By</dt>
                              <dd className="mt-1 text-sm font-medium text-gray-900">{user.name}</dd>
                            </div>
                          )}
                        </dl>
                      </div>

                      <Separator />

                      {/* Timestamps */}
                      <div>
                        <h3 className="mb-4 flex items-center gap-2 text-base font-semibold">
                          <Clock className="h-4 w-4 text-gray-600" />
                          Timestamps
                        </h3>
                        <dl className="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                          <div>
                            <dt className="text-sm text-gray-500">Created</dt>
                            <dd className="mt-1 text-sm font-medium text-gray-900">
                              {order.createdAt ? new Date(order.createdAt).toLocaleString() : 'N/A'}
                            </dd>
                          </div>
                          {order.scheduledAt && (
                            <div>
                              <dt className="text-sm text-gray-500">Scheduled For</dt>
                              <dd className="mt-1 text-sm font-medium text-gray-900">{new Date(order.scheduledAt).toLocaleString()}</dd>
                            </div>
                          )}
                          {order.completedAt && (
                            <div>
                              <dt className="text-sm text-gray-500">Completed</dt>
                              <dd className="mt-1 text-sm font-medium text-gray-900">{new Date(order.completedAt).toLocaleString()}</dd>
                            </div>
                          )}
                        </dl>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          </div>
        </PageContent>
      </PageLayout>
    </AppLayout>
  );
}
