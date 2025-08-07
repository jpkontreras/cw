import { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  ArrowLeft,
  Edit,
  Eye,
  Copy,
  Trash2,
  MoreVertical,
  Clock,
  MapPin,
  Calendar,
  CheckCircle,
  XCircle,
  ChefHat,
  Coffee,
  Sun,
  Moon,
  Sparkles,
  FileText,
  Settings,
  Package,
  Star,
  TrendingUp,
  Leaf,
  DollarSign,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { toast } from 'sonner';

interface MenuItem {
  id: number;
  displayName?: string;
  displayDescription?: string;
  priceOverride?: number;
  isFeatured: boolean;
  isRecommended: boolean;
  isNew: boolean;
  isSeasonal: boolean;
  baseItem?: {
    name: string;
    description?: string;
    price: number;
  };
}

interface MenuSection {
  id: number;
  name: string;
  description?: string;
  isActive: boolean;
  isFeatured: boolean;
  sortOrder: number;
  items: MenuItem[];
}

interface Menu {
  id: number;
  name: string;
  slug: string;
  description?: string;
  type: 'regular' | 'breakfast' | 'lunch' | 'dinner' | 'event' | 'seasonal';
  isActive: boolean;
  isDefault: boolean;
  sortOrder: number;
  availableFrom?: string;
  availableUntil?: string;
  metadata?: any;
  sections?: MenuSection[];
  createdAt: string;
  updatedAt: string;
}

interface MenuStructure {
  totalSections: number;
  totalItems: number;
  sections: MenuSection[];
  featuredItems: number;
  recommendedItems: number;
}

interface MenuAvailability {
  isCurrentlyAvailable: boolean;
  nextAvailable?: string;
  rules?: Array<{
    id: number;
    days: string[];
    startTime: string;
    endTime: string;
  }>;
  locations?: Array<{
    id: number;
    name: string;
    isActive: boolean;
  }>;
}

interface PageProps {
  menu: Menu;
  structure?: MenuStructure;
  availability?: MenuAvailability;
}

const menuTypeIcons = {
  regular: ChefHat,
  breakfast: Coffee,
  lunch: Sun,
  dinner: Moon,
  event: Calendar,
  seasonal: Sparkles,
};

const menuTypeLabels = {
  regular: 'Regular Menu',
  breakfast: 'Breakfast Menu',
  lunch: 'Lunch Menu',
  dinner: 'Dinner Menu',
  event: 'Event Menu',
  seasonal: 'Seasonal Menu',
};

function MenuShowContent({ menu, structure, availability }: PageProps) {
  const [isDeleting, setIsDeleting] = useState(false);
  
  const Icon = menuTypeIcons[menu.type] || ChefHat;
  
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Menus', href: '/menus' },
    { title: menu.name, current: true },
  ];

  const handleDelete = async () => {
    if (!confirm('Are you sure you want to delete this menu? This action cannot be undone.')) {
      return;
    }
    
    setIsDeleting(true);
    router.delete(`/menus/${menu.id}`, {
      onSuccess: () => {
        toast.success('Menu deleted successfully');
      },
      onError: () => {
        toast.error('Failed to delete menu');
        setIsDeleting(false);
      },
    });
  };

  const handleDuplicate = () => {
    const name = prompt('Enter a name for the duplicated menu:', `${menu.name} (Copy)`);
    if (name) {
      router.post(`/menus/${menu.id}/duplicate`, { name }, {
        onSuccess: () => {
          toast.success('Menu duplicated successfully');
        },
        onError: () => {
          toast.error('Failed to duplicate menu');
        },
      });
    }
  };

  const handleSetDefault = () => {
    router.post(`/menus/${menu.id}/set-default`, {}, {
      onSuccess: () => {
        toast.success('Menu set as default');
      },
      onError: () => {
        toast.error('Failed to set menu as default');
      },
    });
  };

  const handleToggleActive = () => {
    const endpoint = menu.isActive ? 'deactivate' : 'activate';
    router.post(`/menus/${menu.id}/${endpoint}`, {}, {
      onSuccess: () => {
        toast.success(`Menu ${menu.isActive ? 'deactivated' : 'activated'} successfully`);
      },
      onError: () => {
        toast.error(`Failed to ${menu.isActive ? 'deactivate' : 'activate'} menu`);
      },
    });
  };

  return (
    <>
      <Page.Header
        title={
          <div className="flex items-center gap-3">
            <Icon className="h-6 w-6" />
            <span>{menu.name}</span>
            {menu.isDefault && (
              <Badge variant="secondary">Default</Badge>
            )}
            <Badge variant={menu.isActive ? 'success' : 'secondary'}>
              {menu.isActive ? 'Active' : 'Inactive'}
            </Badge>
          </div>
        }
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href="/menus">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back
              </Link>
            </Button>
            
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline">
                  <MoreVertical className="mr-2 h-4 w-4" />
                  Actions
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleToggleActive}>
                  {menu.isActive ? (
                    <>
                      <XCircle className="mr-2 h-4 w-4" />
                      Deactivate
                    </>
                  ) : (
                    <>
                      <CheckCircle className="mr-2 h-4 w-4" />
                      Activate
                    </>
                  )}
                </DropdownMenuItem>
                {!menu.isDefault && (
                  <DropdownMenuItem onClick={handleSetDefault}>
                    <Star className="mr-2 h-4 w-4" />
                    Set as Default
                  </DropdownMenuItem>
                )}
                <DropdownMenuItem onClick={handleDuplicate}>
                  <Copy className="mr-2 h-4 w-4" />
                  Duplicate
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem 
                  onClick={handleDelete}
                  className="text-destructive"
                  disabled={isDeleting}
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete Menu
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
            
            <Button variant="outline" asChild>
              <Link href={`/menus/${menu.id}/preview`}>
                <Eye className="mr-2 h-4 w-4" />
                Preview
              </Link>
            </Button>
            
            <Button variant="outline" asChild>
              <Link href={`/menus/${menu.id}/builder`}>
                <FileText className="mr-2 h-4 w-4" />
                Menu Builder
              </Link>
            </Button>
            
            <Button asChild>
              <Link href={`/menus/${menu.id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>
          </div>
        }
      />

      <Page.Content>
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            <Tabs defaultValue="overview" className="w-full">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="structure">Structure</TabsTrigger>
                <TabsTrigger value="availability">Availability</TabsTrigger>
              </TabsList>

              <TabsContent value="overview" className="space-y-6">
                {/* Menu Details */}
                <Card>
                  <CardHeader>
                    <CardTitle>Menu Details</CardTitle>
                    <CardDescription>
                      Basic information about this menu
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <p className="text-sm text-muted-foreground mb-1">Type</p>
                        <div className="flex items-center gap-2">
                          <Icon className="h-4 w-4" />
                          <span className="font-medium">{menuTypeLabels[menu.type]}</span>
                        </div>
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground mb-1">URL Slug</p>
                        <p className="font-medium font-mono text-sm">{menu.slug}</p>
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground mb-1">Sort Order</p>
                        <p className="font-medium">{menu.sortOrder}</p>
                      </div>
                      <div>
                        <p className="text-sm text-muted-foreground mb-1">Status</p>
                        <Badge variant={menu.isActive ? 'success' : 'secondary'}>
                          {menu.isActive ? 'Active' : 'Inactive'}
                        </Badge>
                      </div>
                    </div>
                    
                    {menu.description && (
                      <>
                        <Separator />
                        <div>
                          <p className="text-sm text-muted-foreground mb-2">Description</p>
                          <p className="text-sm">{menu.description}</p>
                        </div>
                      </>
                    )}
                  </CardContent>
                </Card>

                {/* Statistics */}
                {structure && (
                  <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base font-medium">
                          Sections
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className="text-2xl font-bold">{structure.totalSections}</p>
                      </CardContent>
                    </Card>
                    
                    <Card>
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base font-medium">
                          Total Items
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className="text-2xl font-bold">{structure.totalItems}</p>
                      </CardContent>
                    </Card>
                    
                    <Card>
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base font-medium flex items-center gap-1">
                          <Star className="h-4 w-4" />
                          Featured
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className="text-2xl font-bold">{structure.featuredItems}</p>
                      </CardContent>
                    </Card>
                    
                    <Card>
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base font-medium flex items-center gap-1">
                          <TrendingUp className="h-4 w-4" />
                          Recommended
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className="text-2xl font-bold">{structure.recommendedItems}</p>
                      </CardContent>
                    </Card>
                  </div>
                )}
              </TabsContent>

              <TabsContent value="structure" className="space-y-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Menu Structure</CardTitle>
                    <CardDescription>
                      Sections and items in this menu
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    {structure && structure.sections.length > 0 ? (
                      <div className="space-y-4">
                        {structure.sections.map((section) => (
                          <div key={section.id} className="border rounded-lg p-4">
                            <div className="flex items-start justify-between mb-3">
                              <div>
                                <h4 className="font-medium flex items-center gap-2">
                                  {section.name}
                                  {section.isFeatured && (
                                    <Badge variant="secondary" className="text-xs">
                                      Featured
                                    </Badge>
                                  )}
                                </h4>
                                {section.description && (
                                  <p className="text-sm text-muted-foreground mt-1">
                                    {section.description}
                                  </p>
                                )}
                              </div>
                              <Badge variant="outline">
                                {section.items.length} items
                              </Badge>
                            </div>
                            
                            {section.items.length > 0 && (
                              <div className="space-y-2">
                                {section.items.slice(0, 5).map((item) => (
                                  <div key={item.id} className="flex items-center justify-between text-sm">
                                    <div className="flex items-center gap-2">
                                      <span>{item.displayName || item.baseItem?.name}</span>
                                      <div className="flex gap-1">
                                        {item.isFeatured && (
                                          <Badge variant="secondary" className="text-xs">
                                            <Star className="mr-1 h-2 w-2" />
                                            Featured
                                          </Badge>
                                        )}
                                        {item.isNew && (
                                          <Badge variant="secondary" className="text-xs">
                                            <Sparkles className="mr-1 h-2 w-2" />
                                            New
                                          </Badge>
                                        )}
                                        {item.isSeasonal && (
                                          <Badge variant="secondary" className="text-xs">
                                            <Leaf className="mr-1 h-2 w-2" />
                                            Seasonal
                                          </Badge>
                                        )}
                                      </div>
                                    </div>
                                    <span className="font-medium">
                                      {formatCurrency(item.priceOverride || item.baseItem?.price || 0)}
                                    </span>
                                  </div>
                                ))}
                                {section.items.length > 5 && (
                                  <p className="text-xs text-muted-foreground pt-2">
                                    +{section.items.length - 5} more items
                                  </p>
                                )}
                              </div>
                            )}
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="text-center py-8">
                        <Package className="h-12 w-12 mx-auto text-muted-foreground mb-3" />
                        <p className="text-muted-foreground">No sections in this menu yet</p>
                        <Button className="mt-4" asChild>
                          <Link href={`/menus/${menu.id}/builder`}>
                            <FileText className="mr-2 h-4 w-4" />
                            Open Menu Builder
                          </Link>
                        </Button>
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="availability" className="space-y-6">
                {/* Availability Status */}
                <Card>
                  <CardHeader>
                    <CardTitle>Availability Status</CardTitle>
                    <CardDescription>
                      When this menu is available to customers
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {availability && (
                      <>
                        <div className="flex items-center justify-between">
                          <span className="text-sm font-medium">Currently Available</span>
                          <Badge variant={availability.isCurrentlyAvailable ? 'success' : 'secondary'}>
                            {availability.isCurrentlyAvailable ? 'Yes' : 'No'}
                          </Badge>
                        </div>
                        
                        {availability.nextAvailable && (
                          <div className="flex items-center justify-between">
                            <span className="text-sm font-medium">Next Available</span>
                            <span className="text-sm">{formatDate(availability.nextAvailable)}</span>
                          </div>
                        )}
                      </>
                    )}
                    
                    {menu.availableFrom || menu.availableUntil ? (
                      <div className="space-y-2 pt-2">
                        {menu.availableFrom && (
                          <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">Available From</span>
                            <span className="text-sm">{formatDate(menu.availableFrom)}</span>
                          </div>
                        )}
                        {menu.availableUntil && (
                          <div className="flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">Available Until</span>
                            <span className="text-sm">{formatDate(menu.availableUntil)}</span>
                          </div>
                        )}
                      </div>
                    ) : (
                      <p className="text-sm text-muted-foreground">No date restrictions</p>
                    )}
                  </CardContent>
                </Card>

                {/* Schedule Rules */}
                {availability?.rules && availability.rules.length > 0 && (
                  <Card>
                    <CardHeader>
                      <CardTitle>Schedule Rules</CardTitle>
                      <CardDescription>
                        Time-based availability rules
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-3">
                        {availability.rules.map((rule) => (
                          <div key={rule.id} className="flex items-center gap-3 text-sm">
                            <Clock className="h-4 w-4 text-muted-foreground" />
                            <div>
                              <span className="font-medium">{rule.days.join(', ')}</span>
                              <span className="text-muted-foreground ml-2">
                                {rule.startTime} - {rule.endTime}
                              </span>
                            </div>
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                )}

                {/* Location Availability */}
                {availability?.locations && availability.locations.length > 0 && (
                  <Card>
                    <CardHeader>
                      <CardTitle>Location Availability</CardTitle>
                      <CardDescription>
                        Where this menu is available
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-2">
                        {availability.locations.map((location) => (
                          <div key={location.id} className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                              <MapPin className="h-4 w-4 text-muted-foreground" />
                              <span className="text-sm">{location.name}</span>
                            </div>
                            <Badge variant={location.isActive ? 'success' : 'secondary'}>
                              {location.isActive ? 'Active' : 'Inactive'}
                            </Badge>
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                )}
              </TabsContent>
            </Tabs>
          </div>

          {/* Sidebar */}
          <div className="space-y-4">
            {/* Quick Actions */}
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">Quick Actions</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                <Button 
                  variant="outline" 
                  className="w-full justify-start"
                  asChild
                >
                  <Link href={`/menus/${menu.id}/builder`}>
                    <FileText className="mr-2 h-4 w-4" />
                    Menu Builder
                  </Link>
                </Button>
                
                <Button 
                  variant="outline" 
                  className="w-full justify-start"
                  asChild
                >
                  <Link href={`/menus/${menu.id}/preview`}>
                    <Eye className="mr-2 h-4 w-4" />
                    Preview Menu
                  </Link>
                </Button>
                
                <Button 
                  variant="outline" 
                  className="w-full justify-start"
                  asChild
                >
                  <Link href={`/menus/${menu.id}/edit`}>
                    <Settings className="mr-2 h-4 w-4" />
                    Edit Settings
                  </Link>
                </Button>
              </CardContent>
            </Card>

            {/* Meta Information */}
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base">Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div>
                    <p className="text-muted-foreground mb-1">ID</p>
                    <p className="font-mono font-medium">#{menu.id}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground mb-1">Default</p>
                    <Badge variant={menu.isDefault ? 'default' : 'secondary'} className="text-xs">
                      {menu.isDefault ? 'Yes' : 'No'}
                    </Badge>
                  </div>
                  <div>
                    <p className="text-muted-foreground mb-1">Created</p>
                    <p className="font-medium">{formatDate(menu.createdAt)}</p>
                  </div>
                  <div>
                    <p className="text-muted-foreground mb-1">Updated</p>
                    <p className="font-medium">{formatDate(menu.updatedAt)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </Page.Content>
    </>
  );
}

export default function MenuShow(props: PageProps) {
  return (
    <AppLayout>
      <Head title={props.menu.name} />
      <Page>
        <MenuShowContent {...props} />
      </Page>
    </AppLayout>
  );
}