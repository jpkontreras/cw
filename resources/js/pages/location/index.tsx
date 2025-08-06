import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import LocationLayout from './layout';
import Page from '@/layouts/page-layout';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { 
  Store, 
  MapPin, 
  Clock, 
  Users, 
  Settings, 
  Plus, 
  Search,
  Filter,
  MoreHorizontal,
  Phone,
  Mail,
  Building2,
  ChevronRight,
  Globe,
  Hash,
  Activity,
  Power,
  Warehouse,
  ChefHat,
  ArrowUpRight,
  Calendar,
  Zap,
  TrendingUp
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface Location {
  id: number;
  code: string;
  name: string;
  type: string;
  status: string;
  address: string;
  city: string;
  state: string | null;
  country: string;
  postalCode?: string;
  phone: string | null;
  email: string | null;
  timezone: string;
  currency: string;
  taxRate: number;
  openingHours: any;
  deliveryRadius: number | null;
  capabilities: string[];
  parentLocationId: number | null;
  managerId: number | null;
  metadata: any;
  isDefault: boolean;
  displayName: string;
  isActive: boolean;
  typeLabel: string;
  statusLabel: string;
  statusColor: string;
  isOpen: boolean;
  fullAddress: string;
  hasDelivery: boolean;
  hasDineIn: boolean;
  hasTakeout: boolean;
  hasCatering: boolean;
  parentLocation?: Location;
  childLocations?: Location[];
}

interface Props {
  locations: Location[];
  canCreate: boolean;
}

export default function LocationIndex({ locations, canCreate }: Props) {
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [typeFilter, setTypeFilter] = useState('all');

  // Filter locations based on search and filters
  const filteredLocations = locations.filter(location => {
    const matchesSearch = 
      location.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      location.code.toLowerCase().includes(searchQuery.toLowerCase()) ||
      location.city.toLowerCase().includes(searchQuery.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || location.status === statusFilter;
    const matchesType = typeFilter === 'all' || location.type === typeFilter;
    
    return matchesSearch && matchesStatus && matchesType;
  });

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'restaurant':
        return <Store className="h-5 w-5" />;
      case 'kitchen':
      case 'central_kitchen':
        return <ChefHat className="h-5 w-5" />;
      case 'warehouse':
        return <Warehouse className="h-5 w-5" />;
      default:
        return <Building2 className="h-5 w-5" />;
    }
  };

  const renderLocationCard = (location: Location) => (
    <Card 
      key={location.id} 
      className="group relative h-full flex flex-col overflow-hidden border border-border/40 shadow-sm hover:shadow-md hover:border-border/60 transition-all duration-300"
    >
      {/* Header Section */}
      <div className="p-6 pb-0">
        <div className="flex items-start justify-between mb-4">
          <div className="flex items-start gap-3">
            <div className="p-2.5 bg-primary/10 rounded-xl">
              {getTypeIcon(location.type)}
            </div>
            <div className="min-w-0 flex-1">
              <h3 className="font-semibold text-lg leading-tight truncate">
                {location.name}
              </h3>
              <div className="flex items-center gap-2 mt-1">
                <code className="text-xs text-muted-foreground bg-muted px-1.5 py-0.5 rounded">
                  {location.code}
                </code>
                {location.isDefault && (
                  <Badge variant="secondary" className="text-xs h-5">
                    DEFAULT
                  </Badge>
                )}
              </div>
            </div>
          </div>
          
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8 shrink-0"
              >
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
              <DropdownMenuItem asChild>
                <Link href={`/locations/${location.id}`}>
                  View Details
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link href={`/locations/${location.id}/edit`}>
                  Edit Location
                </Link>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild>
                <Link href={`/locations/${location.id}/users`}>
                  Manage Users
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link href={`/locations/${location.id}/settings`}>
                  Settings
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        {/* Status Section */}
        <div className="flex items-center gap-4 py-3 border-y border-border/50">
          <div className="flex items-center gap-2">
            {location.status === 'active' ? (
              <div className="flex items-center gap-1.5">
                <div className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" />
                <span className="text-sm font-medium text-emerald-600">Active</span>
              </div>
            ) : (
              <div className="flex items-center gap-1.5">
                <div className="h-2 w-2 rounded-full bg-gray-400" />
                <span className="text-sm font-medium text-gray-600">Inactive</span>
              </div>
            )}
          </div>
          
          <Separator orientation="vertical" className="h-4" />
          
          <div className="flex items-center gap-2">
            {location.isOpen ? (
              <div className="flex items-center gap-1.5">
                <Activity className="h-3.5 w-3.5 text-blue-500" />
                <span className="text-sm text-blue-600">Open Now</span>
              </div>
            ) : (
              <div className="flex items-center gap-1.5">
                <Power className="h-3.5 w-3.5 text-gray-400" />
                <span className="text-sm text-gray-500">Closed</span>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Content Section - Flex grow to push button down */}
      <div className="flex-1 flex flex-col p-6 space-y-4">
        {/* Location Info */}
        <div className="space-y-3">
          <div className="flex items-start gap-2">
            <MapPin className="h-4 w-4 text-muted-foreground mt-0.5 shrink-0" />
            <div className="flex-1 text-sm min-w-0">
              <p className="font-medium truncate">{location.address}</p>
              <p className="text-muted-foreground">
                {location.city}{location.state ? `, ${location.state}` : ''} {location.postalCode}
              </p>
            </div>
          </div>

          {/* Contact info - Fixed height to maintain consistency */}
          <div className="min-h-[48px]">
            {(location.phone || location.email) && (
              <div className="grid grid-cols-1 gap-2 text-sm">
                {location.phone && (
                  <div className="flex items-center gap-2">
                    <Phone className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                    <span className="text-muted-foreground truncate">{location.phone}</span>
                  </div>
                )}
                {location.email && (
                  <div className="flex items-center gap-2">
                    <Mail className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                    <span className="text-muted-foreground truncate">{location.email}</span>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Metadata Pills */}
        <div className="flex flex-wrap gap-2">
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-muted/50 rounded-full text-xs">
            <Clock className="h-3 w-3" />
            {location.timezone}
          </div>
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-muted/50 rounded-full text-xs">
            <Globe className="h-3 w-3" />
            {location.currency}
          </div>
          {location.capabilities.map((capability) => (
            <div key={capability} className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary/5 text-primary rounded-full text-xs">
              <Zap className="h-3 w-3" />
              {capability.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
            </div>
          ))}
        </div>

        {/* Spacer to push button to bottom */}
        <div className="flex-1" />

        {/* Action Button - Always at bottom */}
        <Link 
          href={`/locations/${location.id}`}
          className="group/link flex items-center justify-between w-full p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors border border-border/40"
        >
          <span className="text-sm font-medium">View Location Details</span>
          <ArrowUpRight className="h-4 w-4 text-muted-foreground group-hover/link:text-primary transition-colors" />
        </Link>
      </div>
    </Card>
  );

  return (
    <LocationLayout title="Locations">
      <Page>
        <Page.Header
          title="Locations"
          subtitle="Manage your business locations and branches"
          actions={canCreate && (
            <Button asChild>
              <Link href="/locations/create">
                <Plus className="h-4 w-4 mr-2" />
                Add Location
              </Link>
            </Button>
          )}
        />
        
        <Page.Content>
          {locations.length === 0 ? (
            <EmptyState
              icon={Store}
              title="No locations yet"
              description="Get started by creating your first business location."
              action={
                canCreate && (
                  <Button asChild>
                    <Link href="/locations/create">
                      <Plus className="h-4 w-4 mr-2" />
                      Create First Location
                    </Link>
                  </Button>
                )
              }
            />
          ) : (
            <div className="space-y-6">
              {/* Search and Filters */}
              <div className="flex flex-col sm:flex-row gap-4">
                <div className="relative flex-1 max-w-md">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input
                    placeholder="Search locations..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="pl-9"
                  />
                </div>
                
                <div className="flex gap-2">
                  <Select value={statusFilter} onValueChange={setStatusFilter}>
                    <SelectTrigger className="w-[140px]">
                      <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Status</SelectItem>
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="inactive">Inactive</SelectItem>
                      <SelectItem value="maintenance">Maintenance</SelectItem>
                    </SelectContent>
                  </Select>
                  
                  <Select value={typeFilter} onValueChange={setTypeFilter}>
                    <SelectTrigger className="w-[160px]">
                      <SelectValue placeholder="All Types" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Types</SelectItem>
                      <SelectItem value="restaurant">Restaurant</SelectItem>
                      <SelectItem value="kitchen">Kitchen</SelectItem>
                      <SelectItem value="central_kitchen">Central Kitchen</SelectItem>
                      <SelectItem value="warehouse">Warehouse</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              {/* Locations Grid */}
              {filteredLocations.length === 0 ? (
                <Card className="border-0 shadow-sm">
                  <CardContent className="py-12 text-center">
                    <Search className="h-12 w-12 mx-auto text-muted-foreground/30 mb-4" />
                    <p className="text-muted-foreground">No locations found matching your criteria</p>
                    <Button
                      variant="link"
                      onClick={() => {
                        setSearchQuery('');
                        setStatusFilter('all');
                        setTypeFilter('all');
                      }}
                      className="mt-2"
                    >
                      Clear filters
                    </Button>
                  </CardContent>
                </Card>
              ) : (
                <div className="grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                  {filteredLocations.map(renderLocationCard)}
                </div>
              )}
            </div>
          )}
        </Page.Content>
      </Page>
    </LocationLayout>
  );
}