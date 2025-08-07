import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Head, Link } from '@inertiajs/react';
import { 
  Building, 
  Building2, 
  Truck, 
  Store, 
  Cloud, 
  Bike, 
  Package, 
  Utensils,
  MapPin,
  Users,
  CheckCircle,
  XCircle
} from 'lucide-react';

interface LocationType {
  value: string;
  label: string;
  description: string;
  icon: string;
}

interface TypeStatistic {
  count: number;
  type: string;
  label: string;
  description: string;
  icon: string;
  capabilities: string[];
  supportsOrders: boolean;
}

interface Props {
  locationTypes: LocationType[];
  typeStatistics: Record<string, TypeStatistic>;
  totalLocations: number;
}

const iconMap: Record<string, React.ElementType> = {
  'utensils': Utensils,
  'store': Store,
  'truck': Truck,
  'cloud': Cloud,
  'bike': Bike,
  'building': Building,
  'building-2': Building2,
  'package': Package,
};

export default function LocationTypes({ locationTypes, typeStatistics, totalLocations }: Props) {
  const getIcon = (iconName: string) => {
    const Icon = iconMap[iconName] || MapPin;
    return Icon;
  };

  return (
    <AppLayout>
      <Head title="Location Types" />
      <Page>
        <Page.Header
          title="Location Types"
          subtitle="Manage different types of business locations"
          actions={
            <>
              <Button variant="outline" asChild>
                <Link href="/locations">
                  <MapPin className="h-4 w-4 mr-2" />
                  View All Locations
                </Link>
              </Button>
              <Button asChild>
                <Link href="/locations/create">
                  Create Location
                </Link>
              </Button>
            </>
          }
        />

        <Page.Content>
          {/* Summary Card */}
          <Card>
          <CardHeader>
            <CardTitle>Overview</CardTitle>
            <CardDescription>
              Total of {totalLocations} location{totalLocations !== 1 ? 's' : ''} across {Object.keys(typeStatistics).length} different types
            </CardDescription>
          </CardHeader>
        </Card>

          {/* Location Type Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {Object.values(typeStatistics).map((stat) => {
            const Icon = getIcon(stat.icon);
            
            return (
              <Card key={stat.type} className="hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-primary/10 rounded-lg">
                        <Icon className="h-6 w-6 text-primary" />
                      </div>
                      <div>
                        <CardTitle className="text-lg">{stat.label}</CardTitle>
                        <div className="flex items-center gap-2 mt-1">
                          <Badge variant="secondary">
                            {stat.count} location{stat.count !== 1 ? 's' : ''}
                          </Badge>
                          {stat.supportsOrders ? (
                            <Badge variant="outline" className="text-green-600">
                              <CheckCircle className="h-3 w-3 mr-1" />
                              Orders
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="text-muted-foreground">
                              <XCircle className="h-3 w-3 mr-1" />
                              No Orders
                            </Badge>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <p className="text-sm text-muted-foreground">
                    {stat.description}
                  </p>
                  
                  {/* Capabilities */}
                  <div>
                    <p className="text-xs font-medium mb-2 text-muted-foreground">Default Capabilities:</p>
                    <div className="flex flex-wrap gap-1">
                      {stat.capabilities.map((capability) => (
                        <Badge key={capability} variant="outline" className="text-xs">
                          {capability.replace(/_/g, ' ')}
                        </Badge>
                      ))}
                    </div>
                  </div>
                  
                  {/* Actions */}
                  {stat.count > 0 && (
                    <div className="pt-2">
                      <Button 
                        variant="outline" 
                        size="sm" 
                        className="w-full"
                        asChild
                      >
                        <Link href={`/locations?type=${stat.type}`}>
                          View {stat.label} Locations
                        </Link>
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </div>

          {/* Empty State for types with no locations */}
          {Object.values(typeStatistics).every(stat => stat.count === 0) && (
            <Card className="border-dashed">
            <CardContent className="flex flex-col items-center justify-center py-12">
              <MapPin className="h-12 w-12 text-muted-foreground mb-4" />
              <h3 className="text-lg font-semibold mb-2">No Locations Yet</h3>
              <p className="text-muted-foreground text-center mb-4">
                Get started by creating your first location
              </p>
              <Button asChild>
                <Link href="/locations/create">
                  Create First Location
                </Link>
              </Button>
            </CardContent>
            </Card>
          )}

          {/* Information Card */}
          <Card>
          <CardHeader>
            <CardTitle className="text-base">Location Type Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <h4 className="font-medium mb-2">Customer-Facing Types</h4>
                <ul className="space-y-1 text-muted-foreground">
                  <li>• Restaurant - Full service dining</li>
                  <li>• Kiosk - Quick service stands</li>
                  <li>• Food Truck - Mobile operations</li>
                  <li>• Cloud Kitchen - Delivery only</li>
                </ul>
              </div>
              <div>
                <h4 className="font-medium mb-2">Administrative Types</h4>
                <ul className="space-y-1 text-muted-foreground">
                  <li>• Headquarters - Central management</li>
                  <li>• Warehouse - Inventory storage</li>
                  <li>• Franchise - Licensed operations</li>
                  <li>• Delivery Only - Third-party kitchens</li>
                </ul>
              </div>
            </div>
          </CardContent>
          </Card>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}