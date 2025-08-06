import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { router, usePage } from '@inertiajs/react';
import { Check, Store } from 'lucide-react';
import * as React from 'react';

interface Location {
  id: number;
  code: string;
  name: string;
  type: string;
  status: string;
  address: string;
  city: string;
  displayName: string;
  isActive: boolean;
}

interface LocationData {
  current: Location | null;
  locations: Location[];
}

export function LocationSwitcher({ className }: { className?: string }) {
  const [open, setOpen] = React.useState(false);
  const [value, setValue] = React.useState<string>('');
  const page = usePage<{ location?: LocationData } & Record<string, unknown>>();
  const location = page.props.location;

  // Set initial value from current location
  React.useEffect(() => {
    if (location?.current) {
      setValue(location.current.id.toString());
    }
  }, [location]);

  const handleLocationChange = (locationId: string) => {
    setValue(locationId);
    setOpen(false);

    // Send request to update current location
    router.post(
      '/api/locations/current',
      {
        location_id: parseInt(locationId),
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          // Optionally show a success message
        },
      },
    );
  };

  if (!location || !location.locations || location.locations.length === 0) {
    return null;
  }

  const currentLocation = location.current || location.locations[0];

  return (
    <TooltipProvider>
      <Popover open={open} onOpenChange={setOpen}>
        <Tooltip>
          <TooltipTrigger asChild>
            <PopoverTrigger asChild>
              <Button variant="ghost" size="sm" className={cn('h-7 w-7 p-0 hover:bg-accent', className)} aria-label="Select location">
                <Store className="h-4 w-4 transition-colors" />
              </Button>
            </PopoverTrigger>
          </TooltipTrigger>
          <TooltipContent>
            <p className="text-xs">
              Current: <span className="font-medium">{currentLocation?.name || 'No location'}</span>
            </p>
          </TooltipContent>
        </Tooltip>
        <PopoverContent className="w-[300px] p-0" align="start">
          <Command>
            <CommandInput placeholder="Search locations..." />
            <CommandList>
              <CommandEmpty>No location found.</CommandEmpty>
              <CommandGroup>
                {(location.locations || []).map((loc) => (
                  <CommandItem key={loc.id} value={loc.id.toString()} onSelect={handleLocationChange} className="cursor-pointer">
                    <Check className={cn('mr-2 h-4 w-4', value === loc.id.toString() ? 'opacity-100' : 'opacity-0')} />
                    <div className="flex flex-col">
                      <div className="font-medium">{loc.name}</div>
                      <div className="text-xs text-muted-foreground">
                        {loc.code} • {loc.city}
                      </div>
                    </div>
                    {!loc.isActive && <span className="ml-auto text-xs text-muted-foreground">Inactive</span>}
                  </CommandItem>
                ))}
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
    </TooltipProvider>
  );
}
