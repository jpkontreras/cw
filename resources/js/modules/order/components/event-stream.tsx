import { ScrollArea } from '@/components/ui/scroll-area';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import {
  Search,
  PlayCircle,
  ShoppingCart,
  CheckCircle,
  Edit,
  Percent,
  Tag,
  Calculator,
  DollarSign,
  CreditCard,
  CheckCircle2,
  XCircle,
  ArrowRightCircle,
  CheckSquare,
  AlertTriangle,
  User,
  Package,
  Sliders,
  TrendingUp,
  Circle,
  Filter,
  ArrowRight,
  Clock,
  Info,
  Hash,
  Zap,
  GitCommit,
  Activity,
  Layers,
  GitBranch
} from 'lucide-react';
import React, { useState, useMemo } from 'react';
import { format, parseISO } from 'date-fns';

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

interface EventStreamProps {
  events: OrderEvent[];
  selectedEvent: OrderEvent | null;
  onEventSelect: (event: OrderEvent) => void;
  currentTimestamp?: Date;
  className?: string;
  headerAction?: React.ReactNode;
}

const eventIcons: Record<string, React.ElementType> = {
  'play-circle': PlayCircle,
  'shopping-cart': ShoppingCart,
  'check-circle': CheckCircle,
  'edit': Edit,
  'percent': Percent,
  'tag': Tag,
  'calculator': Calculator,
  'dollar-sign': DollarSign,
  'credit-card': CreditCard,
  'check-circle-2': CheckCircle2,
  'x-circle': XCircle,
  'arrow-right-circle': ArrowRightCircle,
  'check-square': CheckSquare,
  'alert-triangle': AlertTriangle,
  'user': User,
  'package': Package,
  'sliders': Sliders,
  'trending-up': TrendingUp,
  'circle': Circle,
  'git-commit': GitCommit,
  'activity': Activity,
  'layers': Layers,
  'git-branch': GitBranch,
};

const eventColors: Record<string, string> = {
  blue: 'bg-blue-100 text-blue-800 border-blue-200',
  green: 'bg-green-100 text-green-800 border-green-200',
  yellow: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  purple: 'bg-purple-100 text-purple-800 border-purple-200',
  orange: 'bg-orange-100 text-orange-800 border-orange-200',
  red: 'bg-red-100 text-red-800 border-red-200',
  gray: 'bg-gray-100 text-gray-800 border-gray-200',
};

const eventBorderColors: Record<string, string> = {
  blue: 'border-l-4 border-l-blue-400 hover:border-l-blue-500',
  green: 'border-l-4 border-l-green-400 hover:border-l-green-500',
  yellow: 'border-l-4 border-l-yellow-400 hover:border-l-yellow-500',
  purple: 'border-l-4 border-l-purple-400 hover:border-l-purple-500',
  orange: 'border-l-4 border-l-orange-400 hover:border-l-orange-500',
  red: 'border-l-4 border-l-red-400 hover:border-l-red-500',
  gray: 'border-l-4 border-l-gray-400 hover:border-l-gray-500',
};

const eventGlowColors: Record<string, string> = {
  blue: 'shadow-blue-100',
  green: 'shadow-green-100',
  yellow: 'shadow-yellow-100',
  purple: 'shadow-purple-100',
  orange: 'shadow-orange-100',
  red: 'shadow-red-100',
  gray: 'shadow-gray-100',
};

export function EventStream({
  events,
  selectedEvent,
  onEventSelect,
  currentTimestamp,
  className = '',
  headerAction
}: EventStreamProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState<string | null>(null);
  const [expandedEventId, setExpandedEventId] = useState<number | null>(null);
  
  // Filter events based on search and type
  const filteredEvents = useMemo(() => {
    let filtered = [...events];
    
    if (searchQuery) {
      filtered = filtered.filter(event => 
        event.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
        event.userName.toLowerCase().includes(searchQuery.toLowerCase()) ||
        event.type.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }
    
    if (filterType) {
      filtered = filtered.filter(event => event.type === filterType);
    }
    
    return filtered;
  }, [events, searchQuery, filterType]);
  
  // Get unique event types for filtering
  const eventTypes = useMemo(() => {
    const types = new Set(events.map(e => e.type));
    return Array.from(types);
  }, [events]);
  
  const getEventIcon = (iconName: string) => {
    const Icon = eventIcons[iconName] || Circle;
    return Icon;
  };
  
  const isEventActive = (event: OrderEvent) => {
    if (!currentTimestamp) return true;
    return new Date(event.createdAt) <= currentTimestamp;
  };

  // Extract state transition information from event properties
  const getStateTransition = (event: OrderEvent) => {
    if (event.type === 'OrderStatusTransitioned' || event.type === 'OrderStatusChanged') {
      // Check for various property names used in different events
      const oldStatus = event.properties.fromStatus ||
                       event.properties.from_status ||
                       event.properties.oldStatus ||
                       event.properties.previousStatus ||
                       event.properties.from;

      const newStatus = event.properties.toStatus ||
                       event.properties.to_status ||
                       event.properties.newStatus ||
                       event.properties.status ||
                       event.properties.to;

      const statusLabels = {
        draft: 'Draft',
        placed: 'Placed',
        started: 'Started',
        confirmed: 'Confirmed',
        preparing: 'Preparing',
        in_preparation: 'In Preparation',
        ready: 'Ready',
        completed: 'Completed',
        cancelled: 'Cancelled',
        refunded: 'Refunded',
      };

      const statusColors = {
        draft: 'text-gray-600 bg-gray-100',
        placed: 'text-blue-600 bg-blue-100',
        started: 'text-blue-600 bg-blue-100',
        confirmed: 'text-green-600 bg-green-100',
        preparing: 'text-orange-600 bg-orange-100',
        in_preparation: 'text-orange-600 bg-orange-100',
        ready: 'text-purple-600 bg-purple-100',
        completed: 'text-green-700 bg-green-100',
        cancelled: 'text-red-600 bg-red-100',
        refunded: 'text-gray-600 bg-gray-100',
      };

      // Convert status to lowercase for matching
      const fromKey = oldStatus ? String(oldStatus).toLowerCase() : null;
      const toKey = newStatus ? String(newStatus).toLowerCase() : null;

      // Capitalize first letter for display if no label found
      const capitalizeFirst = (str: string) => str.charAt(0).toUpperCase() + str.slice(1);

      return {
        from: fromKey ? statusLabels[fromKey as keyof typeof statusLabels] || capitalizeFirst(fromKey) : null,
        to: toKey ? statusLabels[toKey as keyof typeof statusLabels] || capitalizeFirst(toKey) : null,
        fromColor: fromKey ? statusColors[fromKey as keyof typeof statusColors] || 'text-gray-600 bg-gray-100' : '',
        toColor: toKey ? statusColors[toKey as keyof typeof statusColors] || 'text-gray-600 bg-gray-100' : '',
      };
    }
    return null;
  };

  // Format monetary values from event properties
  const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount / 100);
  };

  // Get key metadata from event
  const getEventMetadata = (event: OrderEvent) => {
    const metadata = [];

    if (event.type === 'ItemAddedToOrder' && event.properties.item) {
      const item = event.properties.item as any;
      metadata.push({
        label: 'Item',
        value: item.name || 'Unknown',
        extra: item.quantity ? `×${item.quantity}` : '×1'
      });
      if (item.price) {
        metadata.push({ label: 'Price', value: formatMoney(item.price) });
      }
    }

    if (event.type === 'ItemsAddedToOrder' && event.properties.items) {
      const items = event.properties.items as any[];
      const totalItems = items.reduce((sum, item) => sum + (item.quantity || 1), 0);
      const itemNames = items.slice(0, 2).map(i => i.name).filter(Boolean).join(', ');
      metadata.push({
        label: 'Items',
        value: itemNames || `${totalItems} items`,
        extra: items.length > 2 ? `+${items.length - 2} more` : ''
      });
    }

    if (event.type === 'PromotionApplied') {
      if (event.properties.promotionName) {
        metadata.push({ label: 'Promo', value: String(event.properties.promotionName) });
      }
      if (event.properties.amount || event.properties.promotionAmount) {
        const amount = event.properties.amount || event.properties.promotionAmount;
        metadata.push({ label: 'Saved', value: formatMoney(amount as number) });
      }
    }

    if (event.type === 'PriceCalculated') {
      if (event.properties.total) {
        metadata.push({ label: 'Total', value: formatMoney(event.properties.total as number) });
      }
    }

    if (event.type === 'PaymentProcessed') {
      if (event.properties.amount) {
        metadata.push({ label: 'Paid', value: formatMoney(event.properties.amount as number) });
      }
      if (event.properties.method) {
        const method = String(event.properties.method);
        metadata.push({
          label: 'Via',
          value: method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' ')
        });
      }
    }

    if (event.type === 'TipAdded' && event.properties.tipAmount) {
      metadata.push({ label: 'Tip', value: formatMoney(event.properties.tipAmount as number) });
    }

    if (event.type === 'CustomerInfoEntered') {
      if (event.properties.customerName) {
        metadata.push({ label: 'Customer', value: String(event.properties.customerName) });
      }
      if (event.properties.customerPhone) {
        metadata.push({ label: 'Phone', value: String(event.properties.customerPhone) });
      }
    }

    return metadata;
  };

  // Handle event click
  const handleEventClick = (event: OrderEvent) => {
    // Toggle expansion
    if (expandedEventId === event.id) {
      setExpandedEventId(null);
    } else {
      setExpandedEventId(event.id);
    }
    // Also select the event
    onEventSelect(event);
  };
  
  return (
    <div className={cn("flex flex-col h-full bg-gray-50 border-r overflow-hidden max-w-md", className)}>
      {/* Header */}
      <div className="px-3 py-2 lg:px-4 lg:py-3 border-b bg-white flex-shrink-0">
        <div className="flex items-center justify-between mb-2 lg:mb-3">
          <h3 className="font-semibold text-base lg:text-lg flex items-center gap-2">
            <PlayCircle className="h-4 w-4 lg:h-5 lg:w-5" />
            <span className="hidden sm:inline">Event Stream</span>
            <span className="sm:hidden">Events</span>
          </h3>
          {headerAction && (
            <div>{headerAction}</div>
          )}
        </div>
        
        {/* Search - Hidden on mobile */}
        <div className="relative mb-2 lg:mb-3 hidden sm:block">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <Input
            placeholder="Search events..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9 pr-3 h-8 lg:h-9 text-sm"
          />
        </div>
        
        {/* Filter chips - Horizontal scroll on mobile */}
        <div className="flex gap-1 overflow-x-auto pb-1 scrollbar-hide touch-pan-x" style={{ WebkitOverflowScrolling: 'touch' }}>
          <Badge
            variant={filterType === null ? 'default' : 'outline'}
            className="cursor-pointer text-xs flex-shrink-0 whitespace-nowrap"
            onClick={() => setFilterType(null)}
          >
            All ({events.length})
          </Badge>
          {eventTypes.map(type => (
            <Badge
              key={type}
              variant={filterType === type ? 'default' : 'outline'}
              className="cursor-pointer text-xs flex-shrink-0 whitespace-nowrap"
              onClick={() => setFilterType(type === filterType ? null : type)}
            >
              {type.replace(/([A-Z])/g, ' $1').trim()}
            </Badge>
          ))}
        </div>
      </div>
      
      {/* Events List */}
      <div
        className="flex-1 overflow-y-auto overflow-x-hidden overscroll-contain touch-pan-y scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent hover:scrollbar-thumb-gray-400"
        style={{
          WebkitOverflowScrolling: 'touch',
          scrollBehavior: 'smooth'
        }}
      >
        <div className="px-3 py-2 space-y-2">
          {filteredEvents.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <Filter className="h-8 w-8 mx-auto mb-2 opacity-50" />
              <p className="text-sm">No events found</p>
            </div>
          ) : (
            filteredEvents.map((event, index) => {
              const Icon = getEventIcon(event.icon);
              const isActive = isEventActive(event);
              const isSelected = selectedEvent?.id === event.id;
              const isExpanded = expandedEventId === event.id;
              const stateTransition = getStateTransition(event);
              const metadata = getEventMetadata(event);
              const isLastEvent = index === filteredEvents.length - 1;
              const isFirstEvent = index === 0;

              return (
                <div key={event.id} className="relative flex">
                  {/* Icon column with connection line */}
                  <div className="relative flex flex-col items-center mr-2 sm:mr-3">
                    {/* Connection line from previous event */}
                    {!isFirstEvent && (
                      <div className="absolute w-px bg-gray-300 top-0 h-2 sm:h-3" />
                    )}

                    {/* Icon */}
                    <div className={cn(
                      "relative w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-all duration-200 z-10",
                      eventColors[event.color] || eventColors.gray,
                      isSelected && "ring-2 ring-blue-400 ring-offset-2 bg-white"
                    )}>
                      <Icon className="h-4 w-4 sm:h-5 sm:w-5" />
                      {isActive && (
                        <div className={cn(
                          "absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full",
                          isSelected ? "bg-blue-500" : "bg-green-500"
                        )} />
                      )}
                    </div>

                    {/* Connection line to next event */}
                    {!isLastEvent && (
                      <div className="flex-1 w-px bg-gray-300 min-h-[2rem]" />
                    )}
                  </div>

                  {/* Event content */}
                  <div
                    onClick={() => handleEventClick(event)}
                    className={cn(
                      "flex-1 p-2 sm:p-3 rounded-lg cursor-pointer transition-all duration-200 mb-2",
                      "hover:bg-white hover:shadow-md",
                      "border border-transparent",
                      isSelected && "bg-blue-50 border-blue-200 shadow-sm",
                      isExpanded && "bg-white shadow-lg border-gray-200",
                      !isActive && "opacity-50"
                    )}
                  >
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1">
                        {/* Event title and state transition */}
                        <div className="space-y-1">
                          <p className="text-xs sm:text-sm font-medium text-gray-900 line-clamp-1">
                            {event.description}
                          </p>

                          {/* State transition badge */}
                          {stateTransition && (
                            <div className="flex items-center gap-1 text-xs flex-wrap">
                              {stateTransition.from && (
                                <span className={cn("px-1 sm:px-1.5 py-0.5 rounded text-xs", stateTransition.fromColor)}>
                                  {stateTransition.from}
                                </span>
                              )}
                              <ArrowRight className="h-2.5 w-2.5 sm:h-3 sm:w-3 text-gray-400 flex-shrink-0" />
                              <span className={cn("px-1 sm:px-1.5 py-0.5 rounded font-medium text-xs", stateTransition.toColor)}>
                                {stateTransition.to}
                              </span>
                            </div>
                          )}
                        </div>

                          {/* Compact metadata line */}
                          {!isExpanded && (
                            <div className="flex items-center gap-1 sm:gap-2 mt-1 text-xs text-gray-500 flex-wrap">
                              <span className="whitespace-nowrap">{event.userName}</span>
                              <span className="hidden sm:inline">•</span>
                              <span className="whitespace-nowrap">{event.relativeTime}</span>
                              {metadata.length > 0 && metadata.slice(0, 1).map((meta, idx) => (
                                <React.Fragment key={idx}>
                                  <span className="hidden sm:inline">•</span>
                                  <span className="text-gray-600 truncate max-w-[150px]">
                                    {meta.value}
                                    {meta.extra && <span className="ml-1">{meta.extra}</span>}
                                  </span>
                                </React.Fragment>
                              ))}
                            </div>
                          )}

                          {/* Expanded details */}
                          {isExpanded && (
                            <div className="mt-3 space-y-3">
                              {/* Prominent Status Transition Display */}
                              {stateTransition && (
                                <div className="bg-gradient-to-r from-gray-50 to-blue-50 border border-blue-200 rounded-lg p-2 sm:p-3">
                                  <div className="flex items-center gap-1 sm:gap-2 mb-1">
                                    <ArrowRightCircle className="h-3 w-3 sm:h-4 sm:w-4 text-blue-600" />
                                    <span className="text-xs font-semibold text-blue-900">Status Transition</span>
                                  </div>
                                  <div className="flex items-center gap-2 sm:gap-3 flex-wrap">
                                    {stateTransition.from && (
                                      <div className={cn("px-2 sm:px-3 py-1 sm:py-1.5 rounded-md text-xs sm:text-sm font-medium", stateTransition.fromColor)}>
                                        {stateTransition.from}
                                      </div>
                                    )}
                                    <div className="flex items-center gap-1">
                                      <div className="w-4 sm:w-8 h-[2px] bg-gradient-to-r from-gray-400 to-blue-400"></div>
                                      <ArrowRight className="h-3 w-3 sm:h-4 sm:w-4 text-blue-500" />
                                    </div>
                                    <div className={cn("px-2 sm:px-3 py-1 sm:py-1.5 rounded-md text-xs sm:text-sm font-medium shadow-sm", stateTransition.toColor)}>
                                      {stateTransition.to}
                                    </div>
                                  </div>
                                </div>
                              )}

                              {/* Metadata chips */}
                              {metadata.length > 0 && (
                                <div className="flex flex-wrap gap-1 sm:gap-2">
                                  {metadata.map((meta, idx) => (
                                    <div key={idx} className="inline-flex items-center gap-1 text-xs bg-gray-100 px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full">
                                      <span className="font-medium text-gray-700">{meta.label}:</span>
                                      <span className="text-gray-900">{meta.value}</span>
                                      {meta.extra && <span className="text-gray-500">{meta.extra}</span>}
                                    </div>
                                  ))}
                                </div>
                              )}

                              {/* Event details */}
                              <div className="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                  <p className="font-medium text-gray-500 mb-1">Event Type</p>
                                  <p className="text-gray-900">{event.type.replace(/([A-Z])/g, ' $1').trim()}</p>
                                </div>
                                <div>
                                  <p className="font-medium text-gray-500 mb-1">Timestamp</p>
                                  <p className="text-gray-900">{event.timestamp}</p>
                                </div>
                                <div>
                                  <p className="font-medium text-gray-500 mb-1">User</p>
                                  <p className="text-gray-900">{event.userName}</p>
                                </div>
                                <div>
                                  <p className="font-medium text-gray-500 mb-1">Version</p>
                                  <p className="text-gray-900">#{event.version}</p>
                                </div>
                              </div>

                              {/* Additional properties in readable format */}
                              {Object.keys(event.properties).length > 0 && (
                                <div className="pt-2 border-t border-gray-100">
                                  <p className="font-medium text-gray-500 text-xs mb-2">Additional Details</p>
                                  <div className="space-y-1">
                                    {Object.entries(event.properties)
                                      .filter(([key]) => !['oldStatus', 'newStatus', 'status', 'from', 'to', 'items', 'item', 'amount', 'method', 'tipAmount', 'total', 'promotionName', 'promotionAmount', 'customerName', 'customerPhone'].includes(key))
                                      .slice(0, 5)
                                      .map(([key, value]) => (
                                        <div key={key} className="flex items-start gap-2 text-xs">
                                          <span className="font-medium text-gray-600 capitalize">
                                            {key.replace(/([A-Z])/g, ' $1').trim()}:
                                          </span>
                                          <span className="text-gray-900">
                                            {typeof value === 'object' ?
                                              Array.isArray(value) ? `${value.length} items` : 'Details...' :
                                              String(value)
                                            }
                                          </span>
                                        </div>
                                      ))}
                                  </div>
                                </div>
                              )}
                            </div>
                          )}
                        </div>

                      {/* Event number badge */}
                      <Badge variant="outline" className="text-xs h-6 px-2">
                        #{event.version}
                      </Badge>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </div>

      {/* Footer with stats - Hidden on mobile in compact mode */}
      <div className="px-3 py-2 lg:px-4 lg:py-3 border-t bg-white hidden lg:block flex-shrink-0">
        <div className="flex justify-between text-xs text-gray-500">
          <span>{filteredEvents.length} events</span>
          {events.length > 0 && (
            <span>
              {format(parseISO(events[0].createdAt), 'HH:mm')} - 
              {format(parseISO(events[events.length - 1].createdAt), 'HH:mm')}
            </span>
          )}
        </div>
      </div>
    </div>
  );
}