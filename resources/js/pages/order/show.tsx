import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { Order, OrderDetailPageProps } from '@/modules/order';
import { OrderNumberDisplay } from '@/modules/order/components/order-number-display';
import { EventStream } from '@/modules/order/components/event-stream';
import { TimelineControls } from '@/modules/order/components/timeline-controls';
import { OrderStateViewer } from '@/modules/order/components/order-state-viewer';
import { AddEventDialog } from '@/modules/order/components/add-event-dialog';
import { Head, router } from '@inertiajs/react';
import {
  ArrowLeft,
  RefreshCw,
  Download,
  Share2,
  MoreVertical,
  Printer,
  FileText,
} from 'lucide-react';
import { useState, useEffect, useCallback } from 'react';
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
}

export default function ShowOrder({ 
  order: initialOrderData, 
  eventStreamData: initialEventStreamData 
}: ShowOrderProps) {
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [eventStream] = useState<EventStreamData | null>(initialEventStreamData || null);
  const [selectedEvent, setSelectedEvent] = useState<OrderEvent | null>(null);
  const [currentEventIndex, setCurrentEventIndex] = useState(0);
  const [currentTimestamp, setCurrentTimestamp] = useState<Date | null>(null);
  const [orderState, setOrderState] = useState<Record<string, unknown> | null>(initialEventStreamData?.currentState || null);
  
  const order = initialOrderData as Order;
  
  // Initialize with provided data (events are in reverse order - latest first)
  useEffect(() => {
    if (initialEventStreamData && initialEventStreamData.events.length > 0) {
      const latestEvent = initialEventStreamData.events[0]; // First event is the latest
      setSelectedEvent(latestEvent);
      setCurrentEventIndex(0); // Start at index 0 (latest event)
      setCurrentTimestamp(new Date(latestEvent.createdAt));
    }
  }, [initialEventStreamData]);
  
  // Handle event selection
  const handleEventSelect = useCallback((event: OrderEvent) => {
    setSelectedEvent(event);
    const index = eventStream?.events.findIndex(e => e.id === event.id) ?? 0;
    setCurrentEventIndex(index);
    setCurrentTimestamp(new Date(event.createdAt));
    
    // For now, we'll compute the state locally from the event stream
    // This is more efficient than making a server request for each selection
    // The server already provided us with the current state and all events
    if (eventStream && index >= 0 && index < eventStream.events.length) {
      // If selecting the latest event (first in array), use the current state
      if (index === 0) {
        setOrderState(eventStream.currentState);
      } else {
        // For historical events, we could compute the state at that point
        // For now, just show the event data
        setOrderState({
          ...eventStream.currentState,
          // Mark as historical view
          _isHistorical: true,
          _eventIndex: index,
        });
      }
    }
  }, [eventStream]);
  
  // Handle event index change from timeline
  const handleEventIndexChange = useCallback((index: number) => {
    if (!eventStream) return;
    
    const event = eventStream.events[index];
    if (event) {
      handleEventSelect(event);
    }
  }, [eventStream, handleEventSelect]);
  
  // Handle time travel
  const handleTimeTravel = useCallback(async (timestamp: Date) => {
    setCurrentTimestamp(timestamp);
    
    // Find the event closest to this timestamp
    if (eventStream) {
      const targetTime = timestamp.getTime();
      let closestEvent = eventStream.events[0];
      let minDiff = Math.abs(new Date(closestEvent.createdAt).getTime() - targetTime);
      
      for (const event of eventStream.events) {
        const eventTime = new Date(event.createdAt).getTime();
        const diff = Math.abs(eventTime - targetTime);
        if (diff < minDiff) {
          minDiff = diff;
          closestEvent = event;
        }
      }
      
      if (closestEvent) {
        handleEventSelect(closestEvent);
      }
    }
  }, [eventStream, handleEventSelect]);
  
  // Handle refresh
  const handleRefresh = () => {
    setIsRefreshing(true);
    router.reload({
      onFinish: () => setIsRefreshing(false),
    });
  };
  
  
  return (
    <AppLayout>
      <Head title={`Order ${order.orderNumber || 'Event Stream'}`} />
      
      <div className="flex h-screen flex-col">
        {/* Header */}
        <div className="flex items-center justify-between border-b bg-white px-6 py-4">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => router.visit('/orders')}
            >
              <ArrowLeft className="h-4 w-4" />
            </Button>
            <div>
              <OrderNumberDisplay order={order} size="lg" />
              <p className="text-sm text-gray-500 mt-1">
                Event Sourcing View • {eventStream?.statistics.totalEvents || 0} events
              </p>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            {/* Add Event Button */}
            <AddEventDialog
              orderUuid={order.uuid || ''}
              orderId={typeof order.id === 'number' ? order.id : parseInt(order.id)}
              orderStatus={order.status}
              onEventAdded={() => handleRefresh()}
            />
            
            {/* Refresh */}
            <Button
              variant="outline"
              size="icon"
              onClick={handleRefresh}
              disabled={isRefreshing}
            >
              <RefreshCw className={cn("h-4 w-4", isRefreshing && "animate-spin")} />
            </Button>
            
            {/* More Actions */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                  <MoreVertical className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuItem>
                  <Share2 className="mr-2 h-3.5 w-3.5" />
                  Share
                </DropdownMenuItem>
                <DropdownMenuSeparator />
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
        
        {/* Timeline Controls */}
        {eventStream && (
          <TimelineControls
            events={eventStream.events}
            currentEventIndex={currentEventIndex}
            onEventIndexChange={handleEventIndexChange}
            onTimeTravel={handleTimeTravel}
          />
        )}
        
        {/* Main Content */}
        <div className="flex flex-1 overflow-hidden">
          {/* Event Stream Sidebar */}
          <div className="w-96 flex-shrink-0">
            {eventStream && eventStream.events.length > 0 ? (
              <EventStream
                events={eventStream.events}
                selectedEvent={selectedEvent}
                onEventSelect={handleEventSelect}
                currentTimestamp={currentTimestamp || undefined}
              />
            ) : (
              <div className="flex h-full items-center justify-center bg-gray-50 border-r">
                <div className="text-center">
                  <p className="text-sm text-gray-500">No events to display</p>
                  <p className="text-xs text-gray-400 mt-1">Events will appear here as they occur</p>
                </div>
              </div>
            )}
          </div>
          
          {/* Order State Viewer */}
          <div className="flex-1 overflow-hidden">
            <OrderStateViewer
              orderState={orderState}
              currentTimestamp={currentTimestamp || undefined}
            />
          </div>
        </div>
      </div>
    </AppLayout>
  );
}