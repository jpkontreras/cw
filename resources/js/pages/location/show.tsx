import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
  Store, 
  MapPin, 
  Clock, 
  Phone, 
  Mail, 
  Globe, 
  DollarSign, 
  Percent,
  Truck,
  Users,
  Settings,
  Edit,
  Trash,
  ArrowLeft
} from 'lucide-react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

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
  postalCode: string | null;
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
  createdAt: string;
  updatedAt: string;
}

interface Statistics {
  total_users: number;
  managers: number;
  staff: number;
  child_locations: number;
  is_open: boolean;
  capabilities: string[];
}

interface Props {
  location: Location;
  statistics: Statistics;
  canEdit: boolean;
  canDelete: boolean;
}

export default function LocationShow({ location, statistics, canEdit, canDelete }: Props) {

  const handleDelete = () => {
    router.delete(`/locations/${location.id}`, {
      onSuccess: () => {
        // Redirect handled by controller
      },
    });
  };

  const getStatusBadgeVariant = (status: string) => {
    switch (status) {
      case 'active':
        return 'default';
      case 'inactive':
        return 'secondary';
      case 'maintenance':
        return 'destructive';
      default:
        return 'secondary';
    }
  };

  return (
    <AppLayout>
      <Head title={location.name} />
      <Page>
        <Page.Header
          title={location.name}
          subtitle={
            <div className="flex items-center gap-2 mt-1">
              <span className="font-mono text-sm">{location.code}</span>
              <Badge variant={getStatusBadgeVariant(location.status)}>
                {location.statusLabel}
              </Badge>
              {location.isDefault && (
                <Badge variant="outline">Default Location</Badge>
              )}
              {location.isOpen ? (
                <Badge variant="outline" className="text-green-600">Open</Badge>
              ) : (
                <Badge variant="outline" className="text-red-600">Closed</Badge>
              )}
            </div>
          }
          actions={
            <div className="flex items-center gap-2">
              <Button variant="outline" asChild>
                <Link href="/locations">
                  <ArrowLeft className="h-4 w-4 mr-2" />
                  Back
                </Link>
              </Button>
              {canEdit && (
                <Button variant="outline" asChild>
                  <Link href={`/locations/${location.id}/edit`}>
                    <Edit className="h-4 w-4 mr-2" />
                    Edit
                  </Link>
                </Button>
              )}
              {canDelete && (
                <AlertDialog>
                  <AlertDialogTrigger asChild>
                    <Button variant="outline" className="text-destructive">
                      <Trash className="h-4 w-4 mr-2" />
                      Delete
                    </Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>Delete Location</AlertDialogTitle>
                      <AlertDialogDescription>
                        Are you sure you want to delete "{location.name}"? This action cannot be undone.
                      </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel>Cancel</AlertDialogCancel>
                      <AlertDialogAction onClick={handleDelete}>Delete</AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
              )}
            </div>
          }
        />
        <Page.Content>
        <div className="grid gap-6 lg:grid-cols-3">
          <div className="lg:col-span-2 space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Location Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">Type</div>
                    <div className="mt-1">{location.typeLabel}</div>
                  </div>
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">Status</div>
                    <div className="mt-1">
                      <Badge variant={getStatusBadgeVariant(location.status)}>
                        {location.statusLabel}
                      </Badge>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <MapPin className="h-4 w-4" />
                  Address
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <div className="text-sm font-medium text-muted-foreground">Street Address</div>
                  <div className="mt-1">{location.address}</div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">City</div>
                    <div className="mt-1">{location.city}</div>
                  </div>
                  {location.state && (
                    <div>
                      <div className="text-sm font-medium text-muted-foreground">State/Province</div>
                      <div className="mt-1">{location.state}</div>
                    </div>
                  )}
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  {location.postalCode && (
                    <div>
                      <div className="text-sm font-medium text-muted-foreground">Postal Code</div>
                      <div className="mt-1">{location.postalCode}</div>
                    </div>
                  )}
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">Country</div>
                    <div className="mt-1">{location.country}</div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Contact Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {location.phone && (
                  <div className="flex items-center gap-3">
                    <Phone className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm font-medium text-muted-foreground">Phone</div>
                      <div>{location.phone}</div>
                    </div>
                  </div>
                )}
                {location.email && (
                  <div className="flex items-center gap-3">
                    <Mail className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm font-medium text-muted-foreground">Email</div>
                      <div>{location.email}</div>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Settings</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="flex items-center gap-3">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm font-medium text-muted-foreground">Timezone</div>
                      <div>{location.timezone}</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <DollarSign className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm font-medium text-muted-foreground">Currency</div>
                      <div>{location.currency}</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Percent className="h-4 w-4 text-muted-foreground" />
                    <div>
                      <div className="text-sm font-medium text-muted-foreground">Tax Rate</div>
                      <div>{location.taxRate}%</div>
                    </div>
                  </div>
                  {location.deliveryRadius && (
                    <div className="flex items-center gap-3">
                      <Truck className="h-4 w-4 text-muted-foreground" />
                      <div>
                        <div className="text-sm font-medium text-muted-foreground">Delivery Radius</div>
                        <div>{location.deliveryRadius} km</div>
                      </div>
                    </div>
                  )}
                </div>

                <div>
                  <div className="text-sm font-medium text-muted-foreground mb-2">Capabilities</div>
                  <div className="flex flex-wrap gap-2">
                    {location.hasDineIn && <Badge variant="secondary">Dine In</Badge>}
                    {location.hasTakeout && <Badge variant="secondary">Takeout</Badge>}
                    {location.hasDelivery && <Badge variant="secondary">Delivery</Badge>}
                    {location.hasCatering && <Badge variant="secondary">Catering</Badge>}
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Statistics</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <div className="text-2xl font-bold">{statistics.total_users}</div>
                  <div className="text-sm text-muted-foreground">Total Users</div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <div className="text-xl font-semibold">{statistics.managers}</div>
                    <div className="text-sm text-muted-foreground">Managers</div>
                  </div>
                  <div>
                    <div className="text-xl font-semibold">{statistics.staff}</div>
                    <div className="text-sm text-muted-foreground">Staff</div>
                  </div>
                </div>
                {statistics.child_locations > 0 && (
                  <div>
                    <div className="text-xl font-semibold">{statistics.child_locations}</div>
                    <div className="text-sm text-muted-foreground">Child Locations</div>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Quick Actions</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                <Button variant="outline" className="w-full justify-start" asChild>
                  <Link href={`/locations/${location.id}/users`}>
                    <Users className="h-4 w-4 mr-2" />
                    Manage Users
                  </Link>
                </Button>
                <Button variant="outline" className="w-full justify-start" asChild>
                  <Link href={`/locations/${location.id}/settings`}>
                    <Settings className="h-4 w-4 mr-2" />
                    Location Settings
                  </Link>
                </Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Timestamps</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2 text-sm">
                <div>
                  <span className="text-muted-foreground">Created:</span>{' '}
                  {new Date(location.createdAt).toLocaleString()}
                </div>
                <div>
                  <span className="text-muted-foreground">Updated:</span>{' '}
                  {new Date(location.updatedAt).toLocaleString()}
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}