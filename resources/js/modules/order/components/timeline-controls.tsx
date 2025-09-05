import { Button } from '@/components/ui/button';
import { Slider } from '@/components/ui/slider';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { Play, Pause, SkipBack, SkipForward, RotateCcw } from 'lucide-react';
import { useEffect, useState, useRef, useCallback } from 'react';
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
  const [isPlaying, setIsPlaying] = useState(false);
  const [playbackSpeed, setPlaybackSpeed] = useState(1);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);
  
  // Calculate time range (events are in reverse order - latest first)
  const startTime = events.length > 0 ? parseISO(events[events.length - 1].createdAt) : new Date(); // Oldest event
  const endTime = events.length > 0 ? parseISO(events[0].createdAt) : new Date(); // Newest event
  const totalDuration = endTime.getTime() - startTime.getTime();
  
  // Current position as percentage
  const currentPosition = events.length > 0 
    ? ((currentEventIndex / (events.length - 1)) * 100) 
    : 0;
  
  // Format time display
  const formatTimeDisplay = (date: Date) => {
    return format(date, 'h:mm:ss a');
  };
  
  // Play/Pause functionality
  const togglePlayback = useCallback(() => {
    setIsPlaying(!isPlaying);
  }, [isPlaying]);
  
  // Handle playback
  useEffect(() => {
    if (isPlaying && events.length > 0) {
      intervalRef.current = setInterval(() => {
        onEventIndexChange(prev => {
          const next = prev + 1;
          if (next >= events.length) {
            setIsPlaying(false);
            return events.length - 1;
          }
          return next;
        });
      }, 1000 / playbackSpeed);
    } else {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    }
    
    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [isPlaying, playbackSpeed, events.length, onEventIndexChange]);
  
  // Skip to previous event (more recent since events are latest first)
  const skipToPrevious = () => {
    if (currentEventIndex > 0) {
      onEventIndexChange(currentEventIndex - 1);
      const event = events[currentEventIndex - 1];
      onTimeTravel(parseISO(event.createdAt));
    }
  };
  
  // Skip to next event (older since events are latest first)
  const skipToNext = () => {
    if (currentEventIndex < events.length - 1) {
      onEventIndexChange(currentEventIndex + 1);
      const event = events[currentEventIndex + 1];
      onTimeTravel(parseISO(event.createdAt));
    }
  };
  
  // Reset to beginning (oldest event - which is last in the array)
  const reset = () => {
    const oldestIndex = events.length - 1;
    onEventIndexChange(oldestIndex);
    if (events.length > 0) {
      onTimeTravel(parseISO(events[oldestIndex].createdAt));
    }
    setIsPlaying(false);
  };
  
  // Jump to latest (newest event - which is first in the array)
  const jumpToLatest = () => {
    if (events.length > 0) {
      onEventIndexChange(0);
      onTimeTravel(parseISO(events[0].createdAt));
    }
  };
  
  // Handle slider change
  const handleSliderChange = (value: number[]) => {
    const index = Math.round((value[0] / 100) * (events.length - 1));
    onEventIndexChange(index);
    if (events[index]) {
      onTimeTravel(parseISO(events[index].createdAt));
    }
  };
  
  // Event markers on timeline
  const getEventMarkers = () => {
    if (events.length <= 1) return [];
    
    return events.map((event, index) => {
      const position = (index / (events.length - 1)) * 100;
      return {
        position,
        isActive: index <= currentEventIndex,
        isCurrent: index === currentEventIndex,
      };
    });
  };
  
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
      <div className="px-4 py-3">
        {/* Playback controls */}
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-2">
            {/* Reset button */}
            <Button
              variant="outline"
              size="icon"
              onClick={reset}
              disabled={currentEventIndex === events.length - 1}
              className="h-8 w-8"
            >
              <RotateCcw className="h-4 w-4" />
            </Button>
            
            {/* Skip backward */}
            <Button
              variant="outline"
              size="icon"
              onClick={skipToPrevious}
              disabled={currentEventIndex === 0}
              className="h-8 w-8"
            >
              <SkipBack className="h-4 w-4" />
            </Button>
            
            {/* Play/Pause */}
            <Button
              variant="default"
              size="icon"
              onClick={togglePlayback}
              disabled={currentEventIndex >= events.length - 1}
              className="h-8 w-8"
            >
              {isPlaying ? (
                <Pause className="h-4 w-4" />
              ) : (
                <Play className="h-4 w-4" />
              )}
            </Button>
            
            {/* Skip forward */}
            <Button
              variant="outline"
              size="icon"
              onClick={skipToNext}
              disabled={currentEventIndex >= events.length - 1}
              className="h-8 w-8"
            >
              <SkipForward className="h-4 w-4" />
            </Button>
            
            {/* Speed selector */}
            <Select
              value={playbackSpeed.toString()}
              onValueChange={(value) => setPlaybackSpeed(parseFloat(value))}
            >
              <SelectTrigger className="h-8 w-20">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="0.5">0.5x</SelectItem>
                <SelectItem value="1">1x</SelectItem>
                <SelectItem value="2">2x</SelectItem>
                <SelectItem value="5">5x</SelectItem>
                <SelectItem value="10">10x</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          {/* Time display */}
          <div className="flex items-center gap-4">
            <div className="text-sm">
              <span className="text-gray-500">Event</span>{' '}
              <span className="font-medium">
                {currentEventIndex + 1} / {events.length}
              </span>
            </div>
            <div className="text-sm font-mono">
              {formatTimeDisplay(currentTime)}
            </div>
            <Button
              variant="outline"
              size="sm"
              onClick={jumpToLatest}
              disabled={currentEventIndex === 0}
            >
              Jump to Latest
            </Button>
          </div>
        </div>
        
        {/* Timeline slider */}
        <div className="relative">
          {/* Event markers */}
          <div className="absolute inset-x-0 top-1/2 -translate-y-1/2 pointer-events-none">
            {getEventMarkers().map((marker, index) => (
              <div
                key={index}
                className={cn(
                  "absolute w-2 h-2 rounded-full -translate-x-1/2 -translate-y-1/2",
                  "transition-all duration-200",
                  marker.isCurrent 
                    ? "bg-blue-500 ring-2 ring-blue-200 scale-150" 
                    : marker.isActive 
                      ? "bg-blue-400" 
                      : "bg-gray-300"
                )}
                style={{ left: `${marker.position}%`, top: '50%' }}
              />
            ))}
          </div>
          
          {/* Slider */}
          <Slider
            value={[currentPosition]}
            onValueChange={handleSliderChange}
            max={100}
            step={events.length > 1 ? (100 / (events.length - 1)) : 1}
            className="relative z-10"
          />
        </div>
        
        {/* Time range labels */}
        <div className="flex justify-between mt-2">
          <span className="text-xs text-gray-500">
            {formatTimeDisplay(startTime)}
          </span>
          <span className="text-xs text-gray-500">
            {formatTimeDisplay(endTime)}
          </span>
        </div>
      </div>
    </div>
  );
}