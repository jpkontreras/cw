import { Badge } from '@/components/ui/badge';
import type { Order, OrderStatusHistory } from '../types/types';
import { ORDER_STATUS_CONFIG } from '../constants/constants';
import { getStatusLabel } from '../utils/utils';
import { CheckCircle, ChefHat, Clock, FileCheck, Package, ShoppingCart, Truck, XCircle } from 'lucide-react';

interface OrderTimelineProps {
  order: Order;
  statusHistory?: OrderStatusHistory[];
  variant?: 'default' | 'compact' | 'horizontal';
  showTimestamps?: boolean;
  className?: string;
}

export function OrderTimeline({ order, statusHistory, variant = 'default', showTimestamps = true, className = '' }: OrderTimelineProps) {
  const statuses = ['placed', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'completed'];
  const currentStatusIndex = statuses.indexOf(order.status);

  const statusIcons = {
    placed: ShoppingCart,
    confirmed: FileCheck,
    preparing: ChefHat,
    ready: Package,
    delivering: Truck,
    delivered: CheckCircle,
    completed: CheckCircle,
    cancelled: XCircle,
  };

  const getStatusTime = (status: string): string | undefined => {
    const timeField = `${status}At` as keyof Order;
    return order[timeField] as string | undefined;
  };

  const formatTime = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  if (variant === 'horizontal') {
    return (
      <div className={`flex items-center justify-between ${className}`}>
        {statuses.map((status, index) => {
          const isPast = index <= currentStatusIndex;
          const isCurrent = status === order.status;
          const Icon = statusIcons[status as keyof typeof statusIcons] || Clock;

          return (
            <div key={status} className="relative flex-1">
              {/* Connection line */}
              {index < statuses.length - 1 && <div className={`absolute top-4 left-1/2 h-0.5 w-full ${isPast ? 'bg-primary' : 'bg-gray-200'}`} />}

              {/* Status node */}
              <div className="relative z-10 flex flex-col items-center">
                <div
                  className={`flex h-8 w-8 items-center justify-center rounded-full ${
                    isPast ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400'
                  } ${isCurrent ? 'ring-4 ring-primary/20' : ''}`}
                >
                  <Icon className="h-4 w-4" />
                </div>
                <span className={`mt-2 text-xs ${isPast ? 'text-gray-900' : 'text-gray-400'}`}>{getStatusLabel(status as any)}</span>
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  if (variant === 'compact') {
    const completedSteps = currentStatusIndex + 1;
    const totalSteps = order.status === 'cancelled' ? statuses.length : statuses.length;

    return (
      <div className={`space-y-2 ${className}`}>
        <div className="flex items-center justify-between">
          <span className="text-sm font-medium">Order Progress</span>
          <span className="text-sm text-gray-500">
            {completedSteps} of {totalSteps} steps
          </span>
        </div>
        <div className="h-2 w-full rounded-full bg-gray-200">
          <div className="h-2 rounded-full bg-primary transition-all duration-300" style={{ width: `${(completedSteps / totalSteps) * 100}%` }} />
        </div>
        <div className="flex items-center justify-between">
          <Badge className={`${ORDER_STATUS_CONFIG[order.status]?.color || 'bg-gray-500'}`}>{getStatusLabel(order.status)}</Badge>
          {showTimestamps && order.updatedAt && <span className="text-xs text-gray-500">Last updated: {formatTime(order.updatedAt)}</span>}
        </div>
      </div>
    );
  }

  // Default vertical timeline
  return (
    <div className={`relative ${className}`}>
      {statuses.map((status, index) => {
        const isPast = index <= currentStatusIndex;
        const isCurrent = status === order.status;
        const statusInfo = ORDER_STATUS_CONFIG[status as keyof typeof ORDER_STATUS_CONFIG];
        const timestamp = getStatusTime(status);
        const Icon = statusIcons[status as keyof typeof statusIcons] || Clock;

        return (
          <div key={status} className="mb-6 flex items-start last:mb-0">
            {/* Timeline line */}
            {index < statuses.length - 1 && <div className={`absolute top-8 left-4 h-16 w-0.5 ${isPast ? 'bg-primary' : 'bg-gray-200'}`} />}

            {/* Status circle */}
            <div
              className={`relative z-10 flex h-8 w-8 items-center justify-center rounded-full ${
                isPast ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400'
              } ${isCurrent ? 'ring-4 ring-primary/20' : ''}`}
            >
              {isPast ? <Icon className="h-4 w-4" /> : <span className="text-xs font-medium">{index + 1}</span>}
            </div>

            {/* Status info */}
            <div className="ml-4 flex-1">
              <div className="flex items-center justify-between">
                <h4 className={`font-medium ${isPast ? 'text-gray-900' : 'text-gray-400'}`}>{getStatusLabel(status as any)}</h4>
                {showTimestamps && timestamp && <span className="text-sm text-gray-500">{formatTime(timestamp)}</span>}
              </div>
              {isCurrent && <p className="mt-1 text-sm text-primary">Current Status</p>}
              {statusHistory && (
                <div className="mt-2 space-y-1">
                  {statusHistory
                    .filter((h) => h.toStatus === status)
                    .map((history, idx) => (
                      <p key={idx} className="text-xs text-gray-500">
                        {history.reason || `Status changed`}
                      </p>
                    ))}
                </div>
              )}
            </div>
          </div>
        );
      })}

      {/* Cancelled status (if applicable) */}
      {order.status === 'cancelled' && (
        <div className="flex items-start">
          <div className="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white">
            <XCircle className="h-4 w-4" />
          </div>
          <div className="ml-4 flex-1">
            <h4 className="font-medium text-red-600">Cancelled</h4>
            {order.cancelledAt && showTimestamps && <p className="text-sm text-gray-500">{formatTime(order.cancelledAt)}</p>}
            {statusHistory && (
              <div className="mt-2">
                {statusHistory
                  .filter((h) => h.toStatus === 'cancelled')
                  .map((history, idx) => (
                    <p key={idx} className="text-xs text-gray-500">
                      Reason: {history.reason || 'No reason provided'}
                    </p>
                  ))}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
