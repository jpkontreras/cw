import { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';
import {
  ArrowLeft,
  Save,
  CalendarIcon,
  Clock,
  MapPin,
  Settings,
  FileText,
} from 'lucide-react';
import { toast } from 'sonner';

interface Menu {
  id: number;
  name: string;
  slug: string;
  description?: string;
  type: string;
  isActive: boolean;
  isDefault: boolean;
  sortOrder: number;
  availableFrom?: string;
  availableUntil?: string;
  metadata?: any;
  sections?: any[];
  availabilityRules?: any[];
  locations?: any[];
}

interface PageProps {
  menu: Menu;
  menuTypes?: Record<string, string>;
}

function EditMenuContent({ menu, menuTypes = {} }: PageProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    name: menu.name,
    slug: menu.slug,
    description: menu.description || '',
    type: menu.type,
    isActive: menu.isActive,
    isDefault: menu.isDefault,
    sortOrder: menu.sortOrder,
    availableFrom: menu.availableFrom ? new Date(menu.availableFrom) : null,
    availableUntil: menu.availableUntil ? new Date(menu.availableUntil) : null,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.put(`/menu/${menu.id}`, formData, {
      onSuccess: () => {
        toast.success('Menu updated successfully');
      },
      onError: (errors) => {
        toast.error('Failed to update menu');
        console.error(errors);
      },
      onFinish: () => {
        setIsSubmitting(false);
      },
    });
  };

  const generateSlug = (name: string) => {
    return name
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  };

  return (
    <>
      <Page.Header
        title={`Edit ${menu.name}`}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={`/menu/${menu.id}`}>
                <ArrowLeft className="mr-2 h-4 w-4" />
                Cancel
              </Link>
            </Button>
            <Button variant="outline" asChild>
              <Link href={`/menu/${menu.id}/builder`}>
                <FileText className="mr-2 h-4 w-4" />
                Menu Builder
              </Link>
            </Button>
          </div>
        }
      />

      <Page.Content>
        <form onSubmit={handleSubmit}>
          <Tabs defaultValue="general" className="w-full">
            <TabsList className="grid w-full max-w-md grid-cols-3">
              <TabsTrigger value="general">General</TabsTrigger>
              <TabsTrigger value="availability">Availability</TabsTrigger>
              <TabsTrigger value="locations">Locations</TabsTrigger>
            </TabsList>

            <TabsContent value="general" className="space-y-6">
              {/* Basic Information */}
              <Card>
                <CardHeader>
                  <CardTitle>Basic Information</CardTitle>
                  <CardDescription>
                    Update the basic details for your menu
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="name">Menu Name *</Label>
                    <Input
                      id="name"
                      placeholder="e.g., Lunch Special Menu"
                      value={formData.name}
                      onChange={(e) => {
                        const name = e.target.value;
                        setFormData({
                          ...formData,
                          name,
                          slug: generateSlug(name),
                        });
                      }}
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="slug">URL Slug</Label>
                    <Input
                      id="slug"
                      placeholder="lunch-special-menu"
                      value={formData.slug}
                      onChange={(e) =>
                        setFormData({ ...formData, slug: e.target.value })
                      }
                    />
                    <p className="text-sm text-muted-foreground">
                      The URL-friendly version of the menu name
                    </p>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="description">Description</Label>
                    <Textarea
                      id="description"
                      placeholder="Describe this menu..."
                      value={formData.description}
                      onChange={(e) =>
                        setFormData({ ...formData, description: e.target.value })
                      }
                      rows={4}
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="type">Menu Type</Label>
                    <Select
                      value={formData.type}
                      onValueChange={(value) =>
                        setFormData({ ...formData, type: value })
                      }
                    >
                      <SelectTrigger id="type">
                        <SelectValue placeholder="Select menu type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="regular">Regular Menu</SelectItem>
                        <SelectItem value="breakfast">Breakfast Menu</SelectItem>
                        <SelectItem value="lunch">Lunch Menu</SelectItem>
                        <SelectItem value="dinner">Dinner Menu</SelectItem>
                        <SelectItem value="event">Event Menu</SelectItem>
                        <SelectItem value="seasonal">Seasonal Menu</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="sortOrder">Sort Order</Label>
                    <Input
                      id="sortOrder"
                      type="number"
                      placeholder="0"
                      value={formData.sortOrder}
                      onChange={(e) =>
                        setFormData({ 
                          ...formData, 
                          sortOrder: parseInt(e.target.value) || 0 
                        })
                      }
                    />
                    <p className="text-sm text-muted-foreground">
                      Lower numbers appear first
                    </p>
                  </div>
                </CardContent>
              </Card>

              {/* Settings */}
              <Card>
                <CardHeader>
                  <CardTitle>Settings</CardTitle>
                  <CardDescription>
                    Configure menu settings and defaults
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="active">Active</Label>
                      <p className="text-sm text-muted-foreground">
                        Make this menu available to customers
                      </p>
                    </div>
                    <Switch
                      id="active"
                      checked={formData.isActive}
                      onCheckedChange={(checked) =>
                        setFormData({ ...formData, isActive: checked })
                      }
                    />
                  </div>

                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="default">Default Menu</Label>
                      <p className="text-sm text-muted-foreground">
                        Set as the primary menu for your restaurant
                      </p>
                    </div>
                    <Switch
                      id="default"
                      checked={formData.isDefault}
                      onCheckedChange={(checked) =>
                        setFormData({ ...formData, isDefault: checked })
                      }
                    />
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="availability" className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Availability Schedule</CardTitle>
                  <CardDescription>
                    Set when this menu should be available
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                      <Label>Available From</Label>
                      <Popover>
                        <PopoverTrigger asChild>
                          <Button
                            variant="outline"
                            className={cn(
                              "w-full justify-start text-left font-normal",
                              !formData.availableFrom && "text-muted-foreground"
                            )}
                          >
                            <CalendarIcon className="mr-2 h-4 w-4" />
                            {formData.availableFrom ? (
                              format(formData.availableFrom, "PPP")
                            ) : (
                              <span>Pick a date</span>
                            )}
                          </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0">
                          <Calendar
                            mode="single"
                            selected={formData.availableFrom}
                            onSelect={(date) =>
                              setFormData({ ...formData, availableFrom: date })
                            }
                            initialFocus
                          />
                        </PopoverContent>
                      </Popover>
                    </div>

                    <div className="space-y-2">
                      <Label>Available Until</Label>
                      <Popover>
                        <PopoverTrigger asChild>
                          <Button
                            variant="outline"
                            className={cn(
                              "w-full justify-start text-left font-normal",
                              !formData.availableUntil && "text-muted-foreground"
                            )}
                          >
                            <CalendarIcon className="mr-2 h-4 w-4" />
                            {formData.availableUntil ? (
                              format(formData.availableUntil, "PPP")
                            ) : (
                              <span>Pick a date</span>
                            )}
                          </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0">
                          <Calendar
                            mode="single"
                            selected={formData.availableUntil}
                            onSelect={(date) =>
                              setFormData({ ...formData, availableUntil: date })
                            }
                            initialFocus
                          />
                        </PopoverContent>
                      </Popover>
                    </div>
                  </div>

                  {menu.availabilityRules && menu.availabilityRules.length > 0 && (
                    <div className="space-y-2 pt-4 border-t">
                      <Label>Availability Rules</Label>
                      <div className="space-y-2">
                        {menu.availabilityRules.map((rule, index) => (
                          <div key={index} className="flex items-center gap-2">
                            <Clock className="h-4 w-4 text-muted-foreground" />
                            <span className="text-sm">
                              {rule.days.join(', ')}: {rule.startTime} - {rule.endTime}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="locations" className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Location Availability</CardTitle>
                  <CardDescription>
                    Manage which locations can use this menu
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {menu.locations && menu.locations.length > 0 ? (
                    <div className="space-y-2">
                      {menu.locations.map((location) => (
                        <div key={location.id} className="flex items-center gap-2">
                          <MapPin className="h-4 w-4 text-muted-foreground" />
                          <span>{location.name}</span>
                          {location.isActive && (
                            <Badge variant="secondary" className="ml-auto">
                              Active
                            </Badge>
                          )}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-muted-foreground">
                      This menu is available at all locations
                    </p>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>

          {/* Form Actions */}
          <div className="flex justify-end gap-3 mt-6">
            <Button
              type="button"
              variant="outline"
              onClick={() => router.visit(`/menu/${menu.id}`)}
              disabled={isSubmitting}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              <Save className="mr-2 h-4 w-4" />
              Save Changes
            </Button>
          </div>
        </form>
      </Page.Content>
    </>
  );
}

export default function EditMenu(props: PageProps) {
  return (
    <AppLayout>
      <Head title={`Edit ${props.menu.name}`} />
      <Page>
        <EditMenuContent {...props} />
      </Page>
    </AppLayout>
  );
}