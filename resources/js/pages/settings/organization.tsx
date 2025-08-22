import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface OrganizationSettings {
  businessName: string;
  legalName?: string;
  taxId?: string;
  email: string;
  phone: string;
  fax?: string;
  website?: string;
  address: string;
  addressLine2?: string;
  city: string;
  state: string;
  postalCode: string;
  country: string;
  currency: string;
  timezone: string;
  dateFormat: string;
  timeFormat: string;
  logoUrl?: string;
}

interface Props {
  settings: any[];
  localizationSettings: any[];
  currentValues: OrganizationSettings;
}

function OrganizationSettingsContent({ settings, localizationSettings, currentValues }: Props) {
  const { data, setData, put, processing, errors } = useForm<OrganizationSettings>({
    businessName: currentValues.businessName || '',
    legalName: currentValues.legalName || '',
    taxId: currentValues.taxId || '',
    email: currentValues.email || '',
    phone: currentValues.phone || '',
    fax: currentValues.fax || '',
    website: currentValues.website || '',
    address: currentValues.address || '',
    addressLine2: currentValues.addressLine2 || '',
    city: currentValues.city || '',
    state: currentValues.state || '',
    postalCode: currentValues.postalCode || '',
    country: currentValues.country || 'CL',
    currency: currentValues.currency || 'CLP',
    timezone: currentValues.timezone || 'America/Santiago',
    dateFormat: currentValues.dateFormat || 'd/m/Y',
    timeFormat: currentValues.timeFormat || 'H:i',
    logoUrl: currentValues.logoUrl || '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    put('/system-settings/organization');
  };

  return (
    <>
      <Page.Header
        title="Organization Settings"
        subtitle="Basic information about your organization"
      />
      
      <Page.Content>
        <form onSubmit={submit} className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Business Information</CardTitle>
            <CardDescription>Basic information about your organization</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="businessName">Business Name *</Label>
                <Input
                  id="businessName"
                  type="text"
                  value={data.businessName}
                  onChange={(e) => setData('businessName', e.target.value)}
                  required
                />
                {errors.businessName && (
                  <p className="text-sm text-red-600">{errors.businessName}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="legalName">Legal Name</Label>
                <Input
                  id="legalName"
                  type="text"
                  value={data.legalName}
                  onChange={(e) => setData('legalName', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="taxId">Tax ID / RUT</Label>
                <Input
                  id="taxId"
                  type="text"
                  value={data.taxId}
                  onChange={(e) => setData('taxId', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="email">Email *</Label>
                <Input
                  id="email"
                  type="email"
                  value={data.email}
                  onChange={(e) => setData('email', e.target.value)}
                  required
                />
                {errors.email && (
                  <p className="text-sm text-red-600">{errors.email}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone">Phone *</Label>
                <Input
                  id="phone"
                  type="tel"
                  value={data.phone}
                  onChange={(e) => setData('phone', e.target.value)}
                  required
                />
                {errors.phone && (
                  <p className="text-sm text-red-600">{errors.phone}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="website">Website</Label>
                <Input
                  id="website"
                  type="url"
                  value={data.website}
                  onChange={(e) => setData('website', e.target.value)}
                  placeholder="https://example.com"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Address</CardTitle>
            <CardDescription>Physical location of your business</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="address">Street Address *</Label>
              <Input
                id="address"
                type="text"
                value={data.address}
                onChange={(e) => setData('address', e.target.value)}
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="addressLine2">Address Line 2</Label>
              <Input
                id="addressLine2"
                type="text"
                value={data.addressLine2}
                onChange={(e) => setData('addressLine2', e.target.value)}
              />
            </div>

            <div className="grid gap-4 md:grid-cols-3">
              <div className="space-y-2">
                <Label htmlFor="city">City *</Label>
                <Input
                  id="city"
                  type="text"
                  value={data.city}
                  onChange={(e) => setData('city', e.target.value)}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="state">State/Province *</Label>
                <Input
                  id="state"
                  type="text"
                  value={data.state}
                  onChange={(e) => setData('state', e.target.value)}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="postalCode">Postal Code *</Label>
                <Input
                  id="postalCode"
                  type="text"
                  value={data.postalCode}
                  onChange={(e) => setData('postalCode', e.target.value)}
                  required
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="country">Country *</Label>
              <Select value={data.country} onValueChange={(value) => setData('country', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select country" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="CL">Chile</SelectItem>
                  <SelectItem value="US">United States</SelectItem>
                  <SelectItem value="MX">Mexico</SelectItem>
                  <SelectItem value="AR">Argentina</SelectItem>
                  <SelectItem value="BR">Brazil</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Regional Settings</CardTitle>
            <CardDescription>Localization and display preferences</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="currency">Currency *</Label>
                <Select value={data.currency} onValueChange={(value) => setData('currency', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select currency" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="CLP">Chilean Peso (CLP)</SelectItem>
                    <SelectItem value="USD">US Dollar (USD)</SelectItem>
                    <SelectItem value="EUR">Euro (EUR)</SelectItem>
                    <SelectItem value="MXN">Mexican Peso (MXN)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="timezone">Timezone *</Label>
                <Select value={data.timezone} onValueChange={(value) => setData('timezone', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select timezone" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="America/Santiago">Santiago (Chile)</SelectItem>
                    <SelectItem value="America/New_York">New York (US Eastern)</SelectItem>
                    <SelectItem value="America/Chicago">Chicago (US Central)</SelectItem>
                    <SelectItem value="America/Los_Angeles">Los Angeles (US Pacific)</SelectItem>
                    <SelectItem value="America/Mexico_City">Mexico City</SelectItem>
                    <SelectItem value="America/Buenos_Aires">Buenos Aires</SelectItem>
                    <SelectItem value="America/Sao_Paulo">São Paulo</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="dateFormat">Date Format *</Label>
                <Select value={data.dateFormat} onValueChange={(value) => setData('dateFormat', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select date format" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="d/m/Y">DD/MM/YYYY</SelectItem>
                    <SelectItem value="m/d/Y">MM/DD/YYYY</SelectItem>
                    <SelectItem value="Y-m-d">YYYY-MM-DD</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="timeFormat">Time Format *</Label>
                <Select value={data.timeFormat} onValueChange={(value) => setData('timeFormat', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select time format" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="H:i">24 Hour (14:30)</SelectItem>
                    <SelectItem value="h:i A">12 Hour (2:30 PM)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="flex justify-end">
          <Button type="submit" disabled={processing}>
            Save Organization Settings
          </Button>
        </div>
      </form>
      </Page.Content>
    </>
  );
}

export default function OrganizationSettings(props: Props) {
  return (
    <AppLayout>
      <Head title="Organization Settings" />
      <Page>
        <OrganizationSettingsContent {...props} />
      </Page>
    </AppLayout>
  );
}