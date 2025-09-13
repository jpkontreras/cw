import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { Order, OrderDetailPageProps } from '@/modules/order';
import { OrderNumberDisplay } from '@/modules/order/components/order-number-display';
import { EventStream } from '@/modules/order/components/event-stream';
import { OrderStateViewer } from '@/modules/order/components/order-state-viewer';
import { OrderActionRecorder } from '@/modules/order/components/order-action-recorder';
import { Head, router } from '@inertiajs/react';
import {
  ArrowLeft,
  RefreshCw,
  Download,
  Share2,
  MoreVertical,
  Printer,
  FileText,
  Plus,
  ChevronRight,
  CheckCircle,
  XCircle,
  Clock,
  Package,
  type LucideIcon,
} from 'lucide-react';
import React, { useState, useEffect, useCallback } from 'react';
import { StateTransitionData, StateMetadata } from '@/types/order-states';
import * as Icons from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface OrderEvent {
  id: number;
  type: string;
  eventClass: string;
  version: number;
  properties: Record<string, unknown>;
  metadata: Record<string, unknown>;
  userId: number | null;
  userName: string;
  description: string;
  icon: string;
  color: string;
  createdAt: string;
  timestamp: string;
  relativeTime: string;
}

interface EventStreamData {
  orderUuid: string;
  events: OrderEvent[];
  currentState: Record<string, unknown>;
  statistics: {
    totalEvents: number;
    eventTypes: Record<string, number>;
    firstEventAt: string;
    lastEventAt: string;
    duration: string;
  };
}

interface ShowOrderProps extends OrderDetailPageProps {
  eventStreamData?: EventStreamData | null;
  stateTransitionData?: StateTransitionData | null;
}

export default function ShowOrder({ 
  order: initialOrderData, 
  eventStreamData: initialEventStreamData,
  stateTransitionData: initialStateData 
}: ShowOrderProps) {
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [eventStream] = useState<EventStreamData | null>(initialEventStreamData || null);
  const [selectedEvent, setSelectedEvent] = useState<OrderEvent | null>(null);
  const [currentTimestamp, setCurrentTimestamp] = useState<Date | null>(null);
  const [orderState, setOrderState] = useState<Record<string, unknown> | null>(initialEventStreamData?.currentState || null);
  const [isActionRecorderOpen, setIsActionRecorderOpen] = useState(false);
  const [isChangingStatus, setIsChangingStatus] = useState(false);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [stateData, setStateData] = useState<StateTransitionData | null>(initialStateData || null);
  
  const order = initialOrderData as Order;
  
  // Initialize with provided data (events are in reverse order - latest first)
  useEffect(() => {
    if (initialEventStreamData && initialEventStreamData.events.length > 0) {
      const latestEvent = initialEventStreamData.events[0]; // First event is the latest
      setSelectedEvent(latestEvent);
      setCurrentTimestamp(new Date(latestEvent.createdAt));
    }
  }, [initialEventStreamData]);
  
  // Handle event selection
  const handleEventSelect = useCallback((event: OrderEvent) => {
    setSelectedEvent(event);
    const index = eventStream?.events.findIndex(e => e.id === event.id) ?? 0;
    setCurrentTimestamp(new Date(event.createdAt));
    
    // For now, we'll compute the state locally from the event stream
    if (eventStream && index >= 0 && index < eventStream.events.length) {
      // If selecting the latest event (first in array), use the current state
      if (index === 0) {
        setOrderState(eventStream.currentState);
      } else {
        // For historical events, we could compute the state at that point
        setOrderState({
          ...eventStream.currentState,
          _isHistorical: true,
        });
      }
    }
  }, [eventStream]);
  
  // Handle refresh
  const handleRefresh = () => {
    setIsRefreshing(true);
    router.reload({
      onFinish: () => setIsRefreshing(false),
    });
  };
  
  // Get the icon component from the icon name
  const getIconComponent = (iconName: string): LucideIcon => {
    const iconKey = iconName.split('-').map((part, index) => 
      index === 0 ? part : part.charAt(0).toUpperCase() + part.slice(1)
    ).join('');
    
    return (Icons as any)[iconKey] || Icons.CheckCircle;
  };
  
  // Get next available status from server data
  const getNextStatus = () => {
    if (!stateData || stateData.next_states.length === 0) {
      return null;
    }
    
    // Prioritize certain transitions for better UX
    const priorityOrder = ['confirmed', 'preparing', 'ready', 'completed'];
    const prioritizedState = stateData.next_states.find(
      state => priorityOrder.includes(state.value)
    );
    
    const nextState = prioritizedState || stateData.next_states[0];
    
    return {
      value: nextState.value,
      label: nextState.action_label,
      icon: getIconComponent(nextState.icon || 'check-circle'),
      color: `text-${nextState.color || 'blue'}-600`
    };
  };
  
  // Handle quick status change
  const handleQuickStatusChange = (newStatus: string) => {
    setIsChangingStatus(true);
    
    router.post(`/es-order/${order.id}/status`, {
      status: newStatus,
    }, {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        setIsChangingStatus(false);
      },
      onError: (errors) => {
        setIsChangingStatus(false);
        console.error('Failed to change status:', errors);
      },
      onFinish: () => {
        setIsChangingStatus(false);
      },
    });
  };
  
  
  return (
    <AppLayout>
      <Head title={`Order ${order.orderNumber || order.id}`} />
      
      <div className="flex h-screen flex-col">
        {/* Header - Responsive Padding */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b bg-white px-4 lg:px-6 py-3 lg:py-4 gap-3 sm:gap-0">
          <div className="flex items-center gap-2 sm:gap-4 w-full sm:w-auto">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => router.visit('/es-order')}
              className="flex-shrink-0"
            >
              <ArrowLeft className="h-4 w-4" />
            </Button>
            <div className="flex-1 sm:flex-initial">
              <OrderNumberDisplay order={order} size="lg" />
              <p className="text-xs sm:text-sm text-gray-500 mt-1">
                Order Details • {eventStream?.statistics?.totalEvents || 0} events
              </p>
            </div>
          </div>
          
          <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
            {/* Quick Status Change */}
            {(() => {
              const nextStatus = getNextStatus();
              const canCancel = stateData?.can_cancel || false;
              
              return (
                <>
                  {canCancel && (
                    <Button
                      variant="outline"
                      onClick={() => setShowCancelModal(true)}
                      disabled={isChangingStatus}
                      className="hover:bg-red-50 hover:text-red-600 hover:border-red-300"
                    >
                      <XCircle className="h-4 w-4 mr-2" />
                      <span className="hidden sm:inline">Cancel Order</span>
                    </Button>
                  )}
                  
                  {nextStatus && (
                    <Button
                      variant="default"
                      onClick={() => handleQuickStatusChange(nextStatus.value)}
                      disabled={isChangingStatus}
                      className="group"
                    >
                      {React.createElement(nextStatus.icon, { 
                        className: cn(
                          "h-4 w-4 mr-1 sm:mr-2",
                          nextStatus.color,
                          "group-hover:scale-110 transition-transform"
                        )
                      })}
                      <span className="hidden sm:inline">{nextStatus.label}</span>
                      <ChevronRight className="h-4 w-4 ml-1 hidden sm:inline opacity-50" />
                    </Button>
                  )}
                </>
              );
            })()}
            
            {/* More Actions */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="flex-shrink-0">
                  <MoreVertical className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuItem 
                  onClick={handleRefresh}
                  disabled={isRefreshing}
                  className="font-semibold"
                >
                  <RefreshCw className={cn("mr-2 h-3.5 w-3.5", isRefreshing && "animate-spin")} />
                  Refresh
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem>
                  <Share2 className="mr-2 h-3.5 w-3.5" />
                  Share
                </DropdownMenuItem>
                <DropdownMenuItem>
                  <Printer className="mr-2 h-3.5 w-3.5" />
                  Print Receipt
                </DropdownMenuItem>
                <DropdownMenuItem>
                  <FileText className="mr-2 h-3.5 w-3.5" />
                  View Invoice
                </DropdownMenuItem>
                <DropdownMenuItem>
                  <Download className="mr-2 h-3.5 w-3.5" />
                  Export Events
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
        
        {/* Main Content - Responsive Layout */}
        <div className="flex flex-col lg:flex-row flex-1 overflow-hidden">
          {/* Event Stream - Responsive Width */}
          <div className="w-full lg:w-96 xl:w-[28rem] flex-shrink-0 border-b lg:border-b-0 lg:border-r">
            {eventStream && eventStream.events && eventStream.events.length > 0 ? (
              <EventStream
                events={eventStream.events}
                selectedEvent={selectedEvent}
                onEventSelect={handleEventSelect}
                currentTimestamp={currentTimestamp || undefined}
                className="h-64 lg:h-full"
                headerAction={
                  <Button
                    size="sm"
                    variant="secondary"
                    onClick={() => setIsActionRecorderOpen(true)}
                  >
                    <Plus className="h-4 w-4 mr-1" />
                    Add Action
                  </Button>
                }
              />
            ) : (
              <div className="flex h-64 lg:h-full items-center justify-center bg-gray-50">
                <div className="text-center">
                  <p className="text-sm text-gray-500">No events to display</p>
                  <p className="text-xs text-gray-400 mt-1">Events will appear here as they occur</p>
                </div>
              </div>
            )}
          </div>
          
          {/* Order State Viewer - Responsive */}
          <div className="flex-1 overflow-hidden min-h-0">
            <OrderStateViewer
              orderState={orderState || {
                order: order,
                items: order.items,
                user: order.user,
                location: order.orderLocation,
                payments: order.payments || [],
                offers: order.offers || [],
              }}
              currentTimestamp={currentTimestamp || undefined}
              className="h-full"
            />
          </div>
        </div>
      </div>
      
      {/* Order Action Recorder Panel */}
      <OrderActionRecorder
        isOpen={isActionRecorderOpen}
        onClose={() => setIsActionRecorderOpen(false)}
        orderUuid={order.uuid || order.id}
        orderId={typeof order.id === 'number' ? order.id : parseInt(order.id)}
        orderStatus={order.status}
        orderTotal={order.totalAmount || 0}
        onActionRecorded={handleRefresh}
      />
      
      {/* Cancel Confirmation Modal */}
      {showCancelModal && (
        <>
          <div 
            className="fixed inset-0 bg-black/50 z-50 transition-opacity"
            onClick={() => setShowCancelModal(false)}
          />
          <div className="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-50 w-full max-w-md">
            <div className="bg-white rounded-lg shadow-xl p-6">
              <div className="flex items-start gap-4">
                <div className="p-3 bg-red-100 rounded-full">
                  <XCircle className="h-6 w-6 text-red-600" />
                </div>
                <div className="flex-1">
                  <h3 className="text-lg font-semibold text-gray-900">Cancel Order?</h3>
                  <p className="mt-2 text-sm text-gray-500">
                    Are you sure you want to cancel order #{order.orderNumber}? This action cannot be undone.
                  </p>
                  
                  <div className="mt-6 flex gap-3 justify-end">
                    <Button
                      variant="outline"
                      onClick={() => setShowCancelModal(false)}
                    >
                      Keep Order
                    </Button>
                    <Button
                      variant="destructive"
                      onClick={() => {
                        handleQuickStatusChange('cancelled');
                        setShowCancelModal(false);
                      }}
                      disabled={isChangingStatus}
                    >
                      <XCircle className="h-4 w-4 mr-2" />
                      Yes, Cancel Order
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </>
      )}
    </AppLayout>
  );
}