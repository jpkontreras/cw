import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Head, Link } from '@inertiajs/react';
import { 
  MapPin,
  ChevronRight,
  Building2,
  Plus,
  Edit,
  Users,
  Settings
} from 'lucide-react';

interface Location {
  id: number;
  name: string;
  code: string;
  type: string;
  status: string;
  isDefault: boolean;
  parentLocationId: number | null;
  childLocations?: Location[];
}

interface Props {
  hierarchy: Location[];
  canEdit: boolean;
}

function LocationNode({ location, depth = 0 }: { location: Location; depth?: number }) {
  const hasChildren = location.childLocations && location.childLocations.length > 0;
  
  return (
    <div className={`${depth > 0 ? 'ml-8' : ''}`}>
      <div className="group flex items-center gap-3 p-3 rounded-lg hover:bg-muted/50 transition-colors">
        <div className="flex items-center gap-3 flex-1">
          {depth > 0 && (
            <div className="w-6 h-6 flex items-center justify-center text-muted-foreground">
              <ChevronRight className="h-4 w-4" />
            </div>
          )}
          <div className="p-2 bg-primary/10 rounded-lg">
            <Building2 className="h-5 w-5 text-primary" />
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2">
              <Link 
                href={`/locations/${location.id}`}
                className="font-medium hover:underline"
              >
                {location.name}
              </Link>
              <Badge variant="outline" className="text-xs">
                {location.code}
              </Badge>
              {location.isDefault && (
                <Badge variant="default" className="text-xs">
                  Default
                </Badge>
              )}
              {location.status !== 'active' && (
                <Badge variant="secondary" className="text-xs">
                  {location.status}
                </Badge>
              )}
            </div>
            <div className="text-sm text-muted-foreground mt-1">
              Type: {location.type.replace(/_/g, ' ')}
            </div>
          </div>
        </div>
        <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <Button
            variant="ghost"
            size="sm"
            asChild
          >
            <Link href={`/locations/${location.id}/edit`}>
              <Edit className="h-4 w-4" />
            </Link>
          </Button>
          <Button
            variant="ghost"
            size="sm"
            asChild
          >
            <Link href={`/locations/${location.id}/users`}>
              <Users className="h-4 w-4" />
            </Link>
          </Button>
          <Button
            variant="ghost"
            size="sm"
            asChild
          >
            <Link href={`/locations/${location.id}/settings`}>
              <Settings className="h-4 w-4" />
            </Link>
          </Button>
        </div>
      </div>
      
      {hasChildren && (
        <div className="mt-1">
          {location.childLocations!.map((child) => (
            <LocationNode key={child.id} location={child} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}

export default function LocationHierarchy({ hierarchy, canEdit }: Props) {
  // Find root locations (those without parents)
  const rootLocations = hierarchy.filter(loc => !loc.parentLocationId);
  
  // Build hierarchy tree
  const buildTree = (locations: Location[]): Location[] => {
    const locationMap = new Map<number, Location>();
    const tree: Location[] = [];
    
    // First pass: create map
    locations.forEach(loc => {
      locationMap.set(loc.id, { ...loc, childLocations: [] });
    });
    
    // Second pass: build tree
    locations.forEach(loc => {
      const current = locationMap.get(loc.id)!;
      if (loc.parentLocationId) {
        const parent = locationMap.get(loc.parentLocationId);
        if (parent) {
          parent.childLocations = parent.childLocations || [];
          parent.childLocations.push(current);
        } else {
          tree.push(current);
        }
      } else {
        tree.push(current);
      }
    });
    
    return tree;
  };
  
  const tree = buildTree(hierarchy);
  
  return (
    <AppLayout>
      <Head title="Location Hierarchy" />
      <Page>
        <Page.Header
          title="Location Hierarchy"
          subtitle="View and manage the organizational structure of your locations"
          actions={
            <>
              <Button variant="outline" asChild>
                <Link href="/locations">
                  <MapPin className="h-4 w-4 mr-2" />
                  All Locations
                </Link>
              </Button>
              {canEdit && (
                <Button asChild>
                  <Link href="/locations/create">
                    <Plus className="h-4 w-4 mr-2" />
                    Create Location
                  </Link>
                </Button>
              )}
            </>
          }
        />

        <Page.Content>
          {/* Hierarchy Tree */}
          <Card>
          <CardHeader>
            <CardTitle>Organization Structure</CardTitle>
            <CardDescription>
              Showing {hierarchy.length} location{hierarchy.length !== 1 ? 's' : ''} in the system
            </CardDescription>
          </CardHeader>
          <CardContent>
            {tree.length > 0 ? (
              <div className="space-y-2">
                {tree.map((location) => (
                  <LocationNode key={location.id} location={location} />
                ))}
              </div>
            ) : (
              <div className="text-center py-12">
                <MapPin className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-semibold mb-2">No Locations Found</h3>
                <p className="text-muted-foreground mb-4">
                  Start by creating your first location
                </p>
                {canEdit && (
                  <Button asChild>
                    <Link href="/locations/create">
                      <Plus className="h-4 w-4 mr-2" />
                      Create First Location
                    </Link>
                  </Button>
                )}
              </div>
            )}
          </CardContent>
        </Card>

          {/* Legend */}
          <Card>
          <CardHeader>
            <CardTitle className="text-base">Hierarchy Information</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <h4 className="font-medium mb-2">Structure</h4>
                <ul className="space-y-1 text-muted-foreground">
                  <li>• Parent locations appear at the root level</li>
                  <li>• Child locations are nested under their parents</li>
                  <li>• Multiple levels of hierarchy are supported</li>
                </ul>
              </div>
              <div>
                <h4 className="font-medium mb-2">Management</h4>
                <ul className="space-y-1 text-muted-foreground">
                  <li>• Click location names to view details</li>
                  <li>• Hover over locations to see quick actions</li>
                  <li>• Edit hierarchy by changing parent locations</li>
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