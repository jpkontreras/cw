import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import { Building2, Check, MapPin, Phone, Plus, Settings, Store, Users, Eye, Cog } from 'lucide-react';
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

  // Case 2: Single location
  if (location.locations.length === 1) {
    return (
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button 
            variant="ghost" 
            size="sm" 
            className={cn("h-8 px-2 gap-1.5 hover:bg-accent", className)}
            aria-label="Location settings"
          >
            <Store className="h-4 w-4" />
            <span className="text-sm font-medium hidden sm:inline">{currentLocation.name}</span>
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-[320px] p-0" align="start">
          <div className="bg-gradient-to-br from-neutral-50 to-neutral-100/50 dark:from-neutral-900 dark:to-neutral-900/50 p-4 border-b">
            <div className="flex items-start justify-between">
              <div className="space-y-1">
                <h3 className="font-semibold text-base">{currentLocation.name}</h3>
                <p className="text-xs text-muted-foreground font-mono">{currentLocation.code}</p>
              </div>
              <div className={cn(
                "px-2 py-0.5 rounded-full text-xs font-medium",
                currentLocation.isOpen 
                  ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"
                  : "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
              )}>
                {currentLocation.isOpen ? 'Open' : 'Closed'}
              </div>
            </div>
          </div>
          
          <div className="p-3 space-y-3">
            <div className="space-y-2.5">
              <div className="flex gap-3 text-sm">
                <MapPin className="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
                <div className="space-y-0.5">
                  {currentLocation.address ? (
                    <>
                      <p className="text-neutral-900 dark:text-neutral-100">{currentLocation.address}</p>
                      {currentLocation.city && (
                        <p className="text-xs text-muted-foreground">{currentLocation.city}</p>
                      )}
                    </>
                  ) : (
                    <p className="text-muted-foreground italic">No fixed address</p>
                  )}
                </div>
              </div>
              
              {currentLocation.phone && (
                <div className="flex gap-3 text-sm">
                  <Phone className="h-4 w-4 text-muted-foreground shrink-0" />
                  <span className="text-neutral-900 dark:text-neutral-100">{currentLocation.phone}</span>
                </div>
              )}
              
              <div className="flex gap-3 text-sm">
                <Building2 className="h-4 w-4 text-muted-foreground shrink-0" />
                <span className="text-neutral-900 dark:text-neutral-100 capitalize">{currentLocation.type.replace('_', ' ')}</span>
              </div>
            </div>
            
            <Separator />
            
            <div className="grid gap-1">
              <Button variant="ghost" size="sm" className="w-full justify-start h-9 text-neutral-700 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-neutral-100" asChild>
                <Link href={`/locations/${currentLocation.id}`}>
                  <Eye className="mr-2 h-3.5 w-3.5" />
                  View Details
                </Link>
              </Button>
              <Button variant="ghost" size="sm" className="w-full justify-start h-9 text-neutral-700 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-neutral-100" asChild>
                <Link href={`/locations/${currentLocation.id}/users`}>
                  <Users className="mr-2 h-3.5 w-3.5" />
                  Manage Staff
                </Link>
              </Button>
              <Button variant="ghost" size="sm" className="w-full justify-start h-9 text-neutral-700 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-neutral-100" asChild>
                <Link href={`/locations/${currentLocation.id}/settings`}>
                  <Cog className="mr-2 h-3.5 w-3.5" />
                  Location Settings
                </Link>
              </Button>
            </div>
            
            <Separator />
            
            <Button variant="outline" size="sm" className="w-full h-9" asChild>
              <Link href="/locations/create">
                <Plus className="mr-2 h-3.5 w-3.5" />
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
          {/* Current location info */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <h4 className="font-semibold text-sm">Current Location</h4>
              <span className={cn(
                "text-xs px-2 py-0.5 rounded-full",
                currentLocation.isActive 
                  ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400" 
                  : "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400"
              )}>
                {currentLocation.isActive ? 'Active' : 'Inactive'}
              </span>
            </div>
            <div className="space-y-1.5">
              <div className="flex items-start gap-2">
                <Building2 className="h-3.5 w-3.5 text-muted-foreground mt-0.5" />
                <div className="flex-1">
                  <p className="text-sm font-medium">{currentLocation.displayName}</p>
                  <p className="text-xs text-muted-foreground capitalize">{currentLocation.type}</p>
                </div>
              </div>
              {currentLocation.address && (
                <div className="flex items-start gap-2">
                  <MapPin className="h-3.5 w-3.5 text-muted-foreground mt-0.5" />
                  <p className="text-xs text-muted-foreground">
                    {currentLocation.address}, {currentLocation.city}
                  </p>
                </div>
              )}
            </div>
            <div className="flex gap-2 pt-2">
              <Button variant="outline" size="sm" asChild className="flex-1">
                <Link href={`/locations/${currentLocation.id}`}>
                  <Eye className="mr-1.5 h-3.5 w-3.5" />
                  View
                </Link>
              </Button>
              <Button variant="outline" size="sm" asChild className="flex-1">
                <Link href="/locations/settings">
                  <Cog className="mr-1.5 h-3.5 w-3.5" />
                  Settings
                </Link>
              </Button>
            </div>
          </div>
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