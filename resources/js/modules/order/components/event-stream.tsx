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
  Filter
} from 'lucide-react';
import { useState, useMemo } from 'react';
import { format, parseISO } from 'date-fns';

interface OrderEvent {
  id: number;
  type: string;
  eventClass: string;
  version: number;
  properties: Record<string, any>;
  metadata: Record<string, any>;
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

export function EventStream({ 
  events, 
  selectedEvent, 
  onEventSelect, 
  currentTimestamp,
  className = '' 
}: EventStreamProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState<string | null>(null);
  
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
  
  return (
    <div className={cn("flex flex-col h-full bg-gray-50 border-r", className)}>
      {/* Header */}
      <div className="p-4 border-b bg-white">
        <h3 className="font-semibold text-lg mb-3 flex items-center gap-2">
          <PlayCircle className="h-5 w-5" />
          Event Stream
        </h3>
        
        {/* Search */}
        <div className="relative mb-3">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
          <Input
            placeholder="Search events..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-9 pr-3 h-9"
          />
        </div>
        
        {/* Filter chips */}
        <div className="flex flex-wrap gap-1">
          <Badge
            variant={filterType === null ? 'default' : 'outline'}
            className="cursor-pointer text-xs"
            onClick={() => setFilterType(null)}
          >
            All ({events.length})
          </Badge>
          {eventTypes.map(type => (
            <Badge
              key={type}
              variant={filterType === type ? 'default' : 'outline'}
              className="cursor-pointer text-xs"
              onClick={() => setFilterType(type === filterType ? null : type)}
            >
              {type.replace(/([A-Z])/g, ' $1').trim()}
            </Badge>
          ))}
        </div>
      </div>
      
      {/* Events List */}
      <ScrollArea className="flex-1">
        <div className="p-4 space-y-3">
          {filteredEvents.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <Filter className="h-8 w-8 mx-auto mb-2 opacity-50" />
              <p className="text-sm">No events found</p>
            </div>
          ) : (
            filteredEvents.map((event) => {
              const Icon = getEventIcon(event.icon);
              const isActive = isEventActive(event);
              const isSelected = selectedEvent?.id === event.id;
              
              return (
                <div
                  key={event.id}
                  onClick={() => onEventSelect(event)}
                  className={cn(
                    "relative flex items-start gap-3 p-3 rounded-lg cursor-pointer transition-all",
                    "hover:bg-white hover:shadow-sm",
                    isSelected && "bg-white shadow-md ring-2 ring-blue-500",
                    !isActive && "opacity-50"
                  )}
                >
                  {/* Icon */}
                  <div className={cn(
                    "flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center",
                    eventColors[event.color] || eventColors.gray
                  )}>
                    <Icon className="h-4 w-4" />
                  </div>
                  
                  {/* Content */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1">
                        <p className="text-sm font-medium text-gray-900 line-clamp-2">
                          {event.description}
                        </p>
                        <div className="flex items-center gap-2 mt-1">
                          <span className="text-xs text-gray-500">
                            {event.userName}
                          </span>
                          <span className="text-xs text-gray-400">•</span>
                          <span className="text-xs text-gray-500">
                            {event.timestamp}
                          </span>
                        </div>
                      </div>
                      
                      {/* Version badge */}
                      <Badge variant="outline" className="text-xs flex-shrink-0">
                        v{event.version}
                      </Badge>
                    </div>
                    
                    {/* Additional info for selected event */}
                    {isSelected && (
                      <div className="mt-2 pt-2 border-t">
                        <div className="text-xs text-gray-600 space-y-1">
                          <div>
                            <span className="font-medium">Event Type:</span> {event.type}
                          </div>
                          <div>
                            <span className="font-medium">Time:</span> {event.relativeTime}
                          </div>
                          {event.userId && (
                            <div>
                              <span className="font-medium">User ID:</span> {event.userId}
                            </div>
                          )}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              );
            })
          )}
        </div>
      </ScrollArea>
      
      {/* Footer with stats */}
      <div className="p-4 border-t bg-white">
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