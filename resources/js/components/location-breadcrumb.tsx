import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import { Building2, Check, Clock, MapPin, Plus, Settings, Store, Users } from 'lucide-react';
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
  isOpen?: boolean;
}

interface LocationData {
  current: Location | null;
  locations: Location[];
}

export function LocationBreadcrumb({ className }: { className?: string }) {
  const [open, setOpen] = React.useState(false);
  const [switcherOpen, setSwitcherOpen] = React.useState(false);
  const [value, setValue] = React.useState<string>('');
  const page = usePage<Record<string, any>>();
  const location = page.props.location as LocationData | undefined;

  React.useEffect(() => {
    if (location?.current) {
      setValue(location.current.id.toString());
    }
  }, [location]);

  const handleLocationChange = (locationId: string) => {
    setValue(locationId);
    setSwitcherOpen(false);
    setOpen(false);

    router.post(
      '/api/locations/current',
      {
        location_id: parseInt(locationId),
      },
      {
        preserveState: true,
        preserveScroll: true,
      },
    );
  };

  // Case 1: No locations - show store icon with red notification dot
  if (!location || !location.locations || location.locations.length === 0) {
    return (
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button 
            variant="ghost" 
            size="sm" 
            className={cn("h-7 w-7 p-0 relative hover:bg-accent", className)}
            aria-label="Location settings"
          >
            <Store className="h-4 w-4 text-muted-foreground" />
            <span className="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-red-500 animate-pulse" />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-80" align="start">
          <div className="space-y-4">
            <div className="text-center py-4">
              <Store className="h-12 w-12 text-muted-foreground mx-auto mb-3" />
              <h3 className="font-semibold text-lg mb-1">No Location Set</h3>
              <p className="text-sm text-muted-foreground">
                Create your first location to start managing your business
              </p>
            </div>
            <Button asChild className="w-full">
              <Link href="/locations/create">
                <Plus className="mr-2 h-4 w-4" />
                Create First Location
              </Link>
            </Button>
          </div>
        </PopoverContent>
      </Popover>
    );
  }

  const currentLocation = location.current || location.locations[0];

  // Location info section (reusable)
  const LocationInfo = () => (
    <>
      <div className="space-y-3">
        <div>
          <h3 className="font-semibold text-lg">{currentLocation.name}</h3>
          <p className="text-sm text-muted-foreground">{currentLocation.code}</p>
        </div>
        
        <div className="space-y-2">
          <div className="flex items-start gap-2">
            <MapPin className="h-4 w-4 text-muted-foreground mt-0.5" />
            <div className="text-sm">
              <p>{currentLocation.address}</p>
              <p className="text-muted-foreground">{currentLocation.city}</p>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            <Building2 className="h-4 w-4 text-muted-foreground" />
            <span className="text-sm capitalize">{currentLocation.type}</span>
          </div>
          
          <div className="flex items-center gap-2">
            <Clock className="h-4 w-4 text-muted-foreground" />
            <span className="text-sm">
              {currentLocation.isOpen ? (
                <span className="text-green-600">Open now</span>
              ) : (
                <span className="text-red-600">Closed</span>
              )}
            </span>
          </div>
        </div>
      </div>
      
      <Separator />
      
      <div className="space-y-1">
        <Button variant="ghost" size="sm" className="w-full justify-start" asChild>
          <Link href={`/locations/${currentLocation.id}`}>
            <Store className="mr-2 h-4 w-4" />
            View Details
          </Link>
        </Button>
        <Button variant="ghost" size="sm" className="w-full justify-start" asChild>
          <Link href={`/locations/${currentLocation.id}/users`}>
            <Users className="mr-2 h-4 w-4" />
            Manage Staff
          </Link>
        </Button>
        <Button variant="ghost" size="sm" className="w-full justify-start" asChild>
          <Link href={`/locations/${currentLocation.id}/settings`}>
            <Settings className="mr-2 h-4 w-4" />
            Location Settings
          </Link>
        </Button>
      </div>
    </>
  );

  // Case 2: Single location
  if (location.locations.length === 1) {
    return (
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button 
            variant="ghost" 
            size="sm" 
            className={cn("h-7 w-7 p-0 hover:bg-accent", className)}
            aria-label="Location settings"
          >
            <Store className="h-4 w-4 text-muted-foreground" />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-80" align="start">
          <div className="space-y-4">
            <LocationInfo />
            <Separator />
            <Button variant="outline" size="sm" className="w-full" asChild>
              <Link href="/locations/create">
                <Plus className="mr-2 h-4 w-4" />
                Add Another Location
              </Link>
            </Button>
          </div>
        </PopoverContent>
      </Popover>
    );
  }

  // Case 3: Multiple locations
  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button 
          variant="ghost" 
          size="sm" 
          className={cn("h-7 w-7 p-0 hover:bg-accent", className)}
          aria-label="Location settings"
        >
          <Store className="h-4 w-4 text-muted-foreground" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-80" align="start">
        <div className="space-y-4">
          <LocationInfo />
          <Separator />
          
          {/* Location switcher */}
          <Popover open={switcherOpen} onOpenChange={setSwitcherOpen}>
            <PopoverTrigger asChild>
              <Button variant="outline" size="sm" className="w-full justify-between">
                Switch Location
                <span className="text-muted-foreground">
                  {location.locations.length} available
                </span>
              </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[300px] p-0" align="start" side="right">
              <Command>
                <CommandInput placeholder="Search locations..." />
                <CommandList>
                  <CommandEmpty>No location found.</CommandEmpty>
                  <CommandGroup>
                    {location.locations.map((loc) => (
                      <CommandItem 
                        key={loc.id} 
                        value={loc.id.toString()} 
                        onSelect={handleLocationChange} 
                        className="cursor-pointer"
                      >
                        <Check 
                          className={cn(
                            'mr-2 h-4 w-4',
                            value === loc.id.toString() ? 'opacity-100' : 'opacity-0'
                          )} 
                        />
                        <div className="flex flex-col">
                          <div className="font-medium">{loc.name}</div>
                          <div className="text-xs text-muted-foreground">
                            {loc.code} • {loc.city}
                          </div>
                        </div>
                        {!loc.isActive && (
                          <span className="ml-auto text-xs text-muted-foreground">Inactive</span>
                        )}
                      </CommandItem>
                    ))}
                  </CommandGroup>
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
          
          <Button variant="outline" size="sm" className="w-full" asChild>
            <Link href="/locations">
              <Settings className="mr-2 h-4 w-4" />
              Manage All Locations
            </Link>
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  );
}