import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, Clock, CalendarDays, Activity } from 'lucide-react';
import { useCallback } from 'react';
import { format, parseISO } from 'date-fns';

interface TimelineControlsProps {
  events: Array<{
    id: number;
    createdAt: string;
    timestamp: string;
  }>;
  currentEventIndex: number;
  onEventIndexChange: (index: number) => void;
  onTimeTravel: (timestamp: Date) => void;
  className?: string;
}

export function TimelineControls({
  events,
  currentEventIndex,
  onEventIndexChange,
  onTimeTravel,
  className = '',
}: TimelineControlsProps) {
  // Calculate time range (events are in reverse order - latest first)
  const startTime = events.length > 0 ? parseISO(events[events.length - 1].createdAt) : new Date(); // Oldest event
  const endTime = events.length > 0 ? parseISO(events[0].createdAt) : new Date(); // Newest event
  
  // Format time display
  const formatTimeDisplay = (date: Date) => {
    return format(date, 'h:mm:ss a');
  };
  
  const formatDateDisplay = (date: Date) => {
    return format(date, 'MMM d, yyyy');
  };
  
  // Navigate to previous event (more recent since events are latest first)
  const navigateToPrevious = useCallback(() => {
    if (currentEventIndex > 0) {
      const newIndex = currentEventIndex - 1;
      onEventIndexChange(newIndex);
      const event = events[newIndex];
      onTimeTravel(parseISO(event.createdAt));
    }
  }, [currentEventIndex, events, onEventIndexChange, onTimeTravel]);
  
  // Navigate to next event (older since events are latest first)
  const navigateToNext = useCallback(() => {
    if (currentEventIndex < events.length - 1) {
      const newIndex = currentEventIndex + 1;
      onEventIndexChange(newIndex);
      const event = events[newIndex];
      onTimeTravel(parseISO(event.createdAt));
    }
  }, [currentEventIndex, events, onEventIndexChange, onTimeTravel]);
  
  // Jump to first event (oldest - last in array)
  const jumpToFirst = useCallback(() => {
    const oldestIndex = events.length - 1;
    onEventIndexChange(oldestIndex);
    if (events.length > 0) {
      onTimeTravel(parseISO(events[oldestIndex].createdAt));
    }
  }, [events, onEventIndexChange, onTimeTravel]);
  
  // Jump to latest (newest event - first in array)
  const jumpToLatest = useCallback(() => {
    if (events.length > 0) {
      onEventIndexChange(0);
      onTimeTravel(parseISO(events[0].createdAt));
    }
  }, [events, onEventIndexChange, onTimeTravel]);
  
  // Quick jump to specific event index
  const jumpToEvent = useCallback((index: number) => {
    if (index >= 0 && index < events.length) {
      onEventIndexChange(index);
      onTimeTravel(parseISO(events[index].createdAt));
    }
  }, [events, onEventIndexChange, onTimeTravel]);
  
  if (events.length === 0) {
    return (
      <div className={cn("flex items-center justify-center p-4 bg-gray-50 border-b", className)}>
        <p className="text-sm text-gray-500">No events to display</p>
      </div>
    );
  }
  
  const currentEvent = events[currentEventIndex];
  const currentTime = currentEvent ? parseISO(currentEvent.createdAt) : startTime;
  
  return (
    <div className={cn("bg-white border-b", className)}>
      <div className="px-4 lg:px-6 py-3">
        {/* Event Navigation Bar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          {/* Navigation Controls */}
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={navigateToPrevious}
              disabled={currentEventIndex === 0}
              className="h-8"
            >
              <ChevronLeft className="h-4 w-4 mr-1" />
              <span className="hidden sm:inline">Newer</span>
            </Button>
            
            <div className="flex items-center gap-2 px-3">
              <Activity className="h-4 w-4 text-gray-500" />
              <span className="text-sm font-medium">
                Event {currentEventIndex + 1} of {events.length}
              </span>
            </div>
            
            <Button
              variant="outline"
              size="sm"
              onClick={navigateToNext}
              disabled={currentEventIndex >= events.length - 1}
              className="h-8"
            >
              <span className="hidden sm:inline">Older</span>
              <ChevronRight className="h-4 w-4 ml-1" />
            </Button>
          </div>
          
          {/* Time Information */}
          <div className="flex items-center gap-4">
            {currentEvent && (
              <>
                <div className="flex items-center gap-1.5 text-sm">
                  <Clock className="h-3.5 w-3.5 text-gray-500" />
                  <span className="font-medium">{formatTimeDisplay(currentTime)}</span>
                </div>
                <div className="hidden md:flex items-center gap-1.5 text-sm text-gray-600">
                  <CalendarDays className="h-3.5 w-3.5" />
                  <span>{formatDateDisplay(currentTime)}</span>
                </div>
              </>
            )}
            
            <div className="flex gap-1">
              <Button
                variant={currentEventIndex === events.length - 1 ? "default" : "outline"}
                size="sm"
                onClick={jumpToFirst}
                disabled={currentEventIndex === events.length - 1}
                className="h-8 text-xs"
              >
                First
              </Button>
              <Button
                variant={currentEventIndex === 0 ? "default" : "outline"}
                size="sm"
                onClick={jumpToLatest}
                disabled={currentEventIndex === 0}
                className="h-8 text-xs"
              >
                Latest
              </Button>
            </div>
          </div>
        </div>
        
        {/* Event Timeline Visualization */}
        <div className="mt-4">
          {/* Event dots */}
          <div className="flex items-center justify-between relative h-12">
            {/* Timeline line */}
            <div className="absolute inset-x-0 top-1/2 h-0.5 bg-gray-200 -translate-y-1/2" />
            
            {/* Event markers */}
            {events.map((event, index) => {
              const isActive = index === currentEventIndex;
              const isPast = index > currentEventIndex;
              const isFuture = index < currentEventIndex;
              
              return (
                <button
                  key={event.id}
                  onClick={() => jumpToEvent(index)}
                  className={cn(
                    "relative z-10 w-3 h-3 rounded-full transition-all duration-200",
                    "hover:scale-150 focus:outline-none focus:ring-2 focus:ring-offset-2",
                    isActive && "w-4 h-4 bg-blue-500 ring-2 ring-blue-200",
                    isFuture && "bg-blue-400 hover:bg-blue-500",
                    isPast && "bg-gray-300 hover:bg-gray-400",
                    "focus:ring-blue-500"
                  )}
                  title={`${event.type} - ${event.timestamp}`}
                >
                  <span className="sr-only">Event {index + 1}</span>
                </button>
              );
            })}
          </div>
          
          {/* Time labels */}
          <div className="flex justify-between mt-2">
            <span className="text-xs text-gray-500">
              {formatTimeDisplay(startTime)}
              <span className="hidden sm:inline ml-1">• First</span>
            </span>
            {currentEvent && (
              <Badge variant="secondary" className="text-xs">
                {currentEvent.type}
              </Badge>
            )}
            <span className="text-xs text-gray-500">
              {formatTimeDisplay(endTime)}
              <span className="hidden sm:inline ml-1">• Latest</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}