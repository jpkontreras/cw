import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import InputError from '@/components/input-error';
import { ArrowLeft, Phone, Settings, Building2, Package, Clock } from 'lucide-react';

interface Location {
  id: number;
  code: string;
  name: string;
  type: string;
  status: string;
  address: string;
  addressLine2: string | null;
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
}

interface Props {
  location: Location;
  parentLocations: Location[];
  locationTypes: Record<string, string>;
  capabilities: Record<string, string>;
  timezones: string[];
  currencies: Record<string, string>;
}

export default function LocationEdit({
  location,
  parentLocations,
  locationTypes,
  capabilities,
  timezones,
  currencies,
}: Props) {
  const [activeTab, setActiveTab] = useState('inventory');

  const { data, setData, put, processing, errors } = useForm({
    name: location.name,
    type: location.type,
    status: location.status,
    address: location.address,
    addressLine2: location.addressLine2 || '',
    city: location.city,
    state: location.state || '',
    country: location.country,
    postalCode: location.postalCode || '',
    phone: location.phone || '',
    email: location.email || '',
    timezone: location.timezone,
    currency: location.currency,
    taxRate: location.taxRate,
    deliveryRadius: location.deliveryRadius?.toString() || '',
    capabilities: location.capabilities,
    parentLocationId: location.parentLocationId?.toString() || '',
    managerId: location.managerId?.toString() || '',
    isDefault: location.isDefault,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(`/locations/${location.id}`);
  };

  const handleCapabilityChange = (capability: string, checked: boolean) => {
    if (checked) {
      setData('capabilities', [...data.capabilities, capability]);
    } else {
      setData('capabilities', data.capabilities.filter(c => c !== capability));
    }
  };

  return (
    <AppLayout>
      <Head title={`Edit ${location.name}`} />
      <Page>
        <Page.Header
          title={`Edit ${location.name}`}
          subtitle="Update location details"
          actions={
            <Button variant="outline" asChild>
              <Link href="/locations">
                <ArrowLeft className="h-4 w-4 mr-2" />
                Back
              </Link>
            </Button>
          }
        />
        <Page.Content>
        <form onSubmit={handleSubmit} className="space-y-6 max-w-5xl mx-auto">
          {/* Essential Information - Always Visible */}
          <Card>
            <CardHeader>
              <div className="flex items-center gap-3">
                <Package className="h-5 w-5 text-muted-foreground" />
                <div>
                  <CardTitle>Basic Information</CardTitle>
                  <CardDescription>Essential details about your location</CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid gap-6">
                {/* Code and Name Row */}
                <div className="grid gap-6 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name">Location Name *</Label>
                    <Input
                      id="name"
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      placeholder="e.g., Santiago Centro"
                      className="text-base"
                      required
                    />
                    <InputError message={errors.name} />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="code">Location Code</Label>
                    <Input
                      id="code"
                      value={location.code}
                      className="text-base font-mono"
                      disabled
                    />
                  </div>
                </div>

                {/* Type and Status Row */}
                <div className="grid gap-6 md:grid-cols-[1fr,200px]">
                  <div className="space-y-2">
                    <Label htmlFor="address">Address *</Label>
                    <Input
                      id="address"
                      value={data.address}
                      onChange={(e) => setData('address', e.target.value)}
                      placeholder="e.g., Avenida Providencia 123"
                      required
                    />
                    <InputError message={errors.address} />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="type">Location Type *</Label>
                    <Select value={data.type} onValueChange={(value) => setData('type', value)}>
                      <SelectTrigger id="type">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {Object.entries(locationTypes).map(([value, label]) => (
                          <SelectItem key={value} value={value}>
                            {label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <InputError message={errors.type} />
                  </div>
                </div>

                {/* Address Line 2 */}
                <div className="space-y-2">
                  <Label htmlFor="addressLine2">Address Line 2</Label>
                  <Input
                    id="addressLine2"
                    value={data.addressLine2}
                    onChange={(e) => setData('addressLine2', e.target.value)}
                    placeholder="e.g., Office 201, Floor 2"
                  />
                  <InputError message={errors.addressLine2} />
                </div>

                {/* City and Country Row */}
                <div className="grid gap-6 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="city">City *</Label>
                    <Input
                      id="city"
                      value={data.city}
                      onChange={(e) => setData('city', e.target.value)}
                      placeholder="e.g., Santiago"
                      required
                    />
                    <InputError message={errors.city} />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="country">Country *</Label>
                    <Input
                      id="country"
                      value={data.country}
                      onChange={(e) => setData('country', e.target.value)}
                      maxLength={2}
                      placeholder="e.g., CL"
                      required
                    />
                    <InputError message={errors.country} />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Additional Details in Tabs */}
          <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
            <TabsList className="w-full grid grid-cols-5 h-auto p-1">
              <TabsTrigger value="inventory" className="flex items-center gap-2 py-2">
                <Package className="h-4 w-4" />
                <span className="hidden sm:inline">Inventory</span>
              </TabsTrigger>
              <TabsTrigger value="availability" className="flex items-center gap-2 py-2">
                <Clock className="h-4 w-4" />
                <span className="hidden sm:inline">Availability</span>
              </TabsTrigger>
              <TabsTrigger value="contact" className="flex items-center gap-2 py-2">
                <Phone className="h-4 w-4" />
                <span className="hidden sm:inline">Contact</span>
              </TabsTrigger>
              <TabsTrigger value="settings" className="flex items-center gap-2 py-2">
                <Settings className="h-4 w-4" />
                <span className="hidden sm:inline">Settings</span>
              </TabsTrigger>
              <TabsTrigger value="organization" className="flex items-center gap-2 py-2">
                <Building2 className="h-4 w-4" />
                <span className="hidden sm:inline">Organization</span>
              </TabsTrigger>
            </TabsList>

            {/* Inventory Tab */}
            <TabsContent value="inventory" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Inventory & Stock</CardTitle>
                  <CardDescription>
                    Manage stock levels and availability settings
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="postalCode">Postal Code</Label>
                    <Input
                      id="postalCode"
                      value={data.postalCode}
                      onChange={(e) => setData('postalCode', e.target.value)}
                      placeholder="e.g., 7500000"
                    />
                    <InputError message={errors.postalCode} />
                  </div>
                  <p className="text-sm text-muted-foreground">
                    Inventory settings will be configured after location creation.
                  </p>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Availability Tab */}
            <TabsContent value="availability" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Availability & Operations</CardTitle>
                  <CardDescription>
                    Configure operating hours and availability
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="space-y-2">
                    <Label htmlFor="status">Status</Label>
                    <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                      <SelectTrigger id="status">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
                        <SelectItem value="maintenance">Under Maintenance</SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError message={errors.status} />
                  </div>

                  <div className="space-y-2">
                    <Label>Service Capabilities</Label>
                    <div className="grid gap-3 sm:grid-cols-2">
                      {Object.entries(capabilities).map(([value, label]) => (
                        <div key={value} className="flex items-center space-x-2">
                          <Checkbox
                            id={`capability-${value}`}
                            checked={data.capabilities.includes(value)}
                            onCheckedChange={(checked) => handleCapabilityChange(value, !!checked)}
                          />
                          <Label htmlFor={`capability-${value}`} className="font-normal cursor-pointer">
                            {label}
                          </Label>
                        </div>
                      ))}
                    </div>
                    <InputError message={errors.capabilities} />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="deliveryRadius">Delivery Radius (km)</Label>
                    <Input
                      id="deliveryRadius"
                      type="number"
                      step="0.1"
                      min="0"
                      value={data.deliveryRadius}
                      onChange={(e) => setData('deliveryRadius', e.target.value)}
                      placeholder="Leave empty if delivery is not available"
                    />
                    <InputError message={errors.deliveryRadius} />
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Contact Tab */}
            <TabsContent value="contact" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Contact Information</CardTitle>
                  <CardDescription>
                    Add contact details for this location
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="phone">Phone Number</Label>
                      <Input
                        id="phone"
                        type="tel"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        placeholder="e.g., +56 2 1234 5678"
                      />
                      <InputError message={errors.phone} />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="email">Email Address</Label>
                      <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="e.g., santiago@example.com"
                      />
                      <InputError message={errors.email} />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="state">State/Province</Label>
                    <Input
                      id="state"
                      value={data.state}
                      onChange={(e) => setData('state', e.target.value)}
                      placeholder="e.g., Región Metropolitana"
                    />
                    <InputError message={errors.state} />
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Settings Tab */}
            <TabsContent value="settings" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Location Settings</CardTitle>
                  <CardDescription>
                    Configure location-specific settings
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                      <Label htmlFor="timezone">Timezone</Label>
                      <Select value={data.timezone} onValueChange={(value) => setData('timezone', value)}>
                        <SelectTrigger id="timezone">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {timezones.map((tz) => (
                            <SelectItem key={tz} value={tz}>
                              {tz}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <InputError message={errors.timezone} />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="currency">Currency</Label>
                      <Select value={data.currency} onValueChange={(value) => setData('currency', value)}>
                        <SelectTrigger id="currency">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {Object.entries(currencies).map(([code, name]) => (
                            <SelectItem key={code} value={code}>
                              {code} - {name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <InputError message={errors.currency} />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="taxRate">Tax Rate (%)</Label>
                      <Input
                        id="taxRate"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        value={data.taxRate}
                        onChange={(e) => setData('taxRate', parseFloat(e.target.value))}
                        required
                      />
                      <InputError message={errors.taxRate} />
                    </div>
                  </div>

                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="isDefault"
                      checked={data.isDefault}
                      onCheckedChange={(checked) => setData('isDefault', checked === true)}
                    />
                    <Label htmlFor="isDefault" className="font-normal cursor-pointer">
                      Set as default location
                    </Label>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Organization Tab */}
            <TabsContent value="organization" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Organization</CardTitle>
                  <CardDescription>
                    Set organizational hierarchy and management
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {parentLocations.length > 0 && (
                    <div className="space-y-2">
                      <Label htmlFor="parentLocationId">Parent Location</Label>
                      <Select value={data.parentLocationId} onValueChange={(value) => setData('parentLocationId', value)}>
                        <SelectTrigger id="parentLocationId">
                          <SelectValue placeholder="Select parent location" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="">None</SelectItem>
                          {parentLocations.map((loc) => (
                            <SelectItem key={loc.id} value={loc.id.toString()}>
                              {loc.displayName}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <InputError message={errors.parentLocationId} />
                    </div>
                  )}
                  
                  <p className="text-sm text-muted-foreground">
                    Manager assignment will be available after location creation.
                  </p>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>

          <div className="flex justify-end gap-3 pt-6">
            <Button variant="outline" asChild>
              <Link href="/locations">Cancel</Link>
            </Button>
            <Button type="submit" disabled={processing}>
              {processing ? 'Updating...' : 'Update Location'}
            </Button>
          </div>
        </form>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}