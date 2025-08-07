import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { 
  MapPin,
  Save,
  Settings as SettingsIcon,
  Globe,
  DollarSign,
  Shield,
  Hash
} from 'lucide-react';

interface Settings {
  defaultTimezone: string;
  defaultCurrency: string;
  requireApproval: boolean;
  autoAssignCode: boolean;
  codePrefix: string;
}

interface Props {
  settings: Settings;
  timezones: string[];
  currencies: Record<string, string>;
}

export default function LocationSettings({ settings, timezones, currencies }: Props) {
  const { data, setData, put, processing } = useForm({
    defaultTimezone: settings.defaultTimezone,
    defaultCurrency: settings.defaultCurrency,
    requireApproval: settings.requireApproval,
    autoAssignCode: settings.autoAssignCode,
    codePrefix: settings.codePrefix,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put('/locations/settings');
  };

  return (
    <AppLayout>
      <Head title="Location Settings" />
      <Page>
        <Page.Header
          title="Location Settings"
          subtitle="Configure global settings for all locations"
          actions={
            <Button variant="outline" asChild>
              <Link href="/locations">
                <MapPin className="h-4 w-4 mr-2" />
                View Locations
              </Link>
            </Button>
          }
        />

        <Page.Content>
          <form onSubmit={handleSubmit} className="space-y-6">
          {/* Regional Settings */}
          <Card>
            <CardHeader>
              <div className="flex items-center gap-2">
                <Globe className="h-5 w-5 text-primary" />
                <CardTitle>Regional Settings</CardTitle>
              </div>
              <CardDescription>
                Default regional settings for new locations
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="timezone">Default Timezone</Label>
                  <Select
                    value={data.defaultTimezone}
                    onValueChange={(value) => setData('defaultTimezone', value)}
                  >
                    <SelectTrigger id="timezone">
                      <SelectValue placeholder="Select timezone" />
                    </SelectTrigger>
                    <SelectContent>
                      {timezones.map((tz) => (
                        <SelectItem key={tz} value={tz}>
                          {tz.replace('America/', '').replace(/_/g, ' ')}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <p className="text-xs text-muted-foreground">
                    Used for operating hours and scheduling
                  </p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="currency">Default Currency</Label>
                  <Select
                    value={data.defaultCurrency}
                    onValueChange={(value) => setData('defaultCurrency', value)}
                  >
                    <SelectTrigger id="currency">
                      <SelectValue placeholder="Select currency" />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(currencies).map(([code, name]) => (
                        <SelectItem key={code} value={code}>
                          {code} - {name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <p className="text-xs text-muted-foreground">
                    Default currency for pricing and transactions
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Location Codes */}
          <Card>
            <CardHeader>
              <div className="flex items-center gap-2">
                <Hash className="h-5 w-5 text-primary" />
                <CardTitle>Location Codes</CardTitle>
              </div>
              <CardDescription>
                Configure how location codes are generated
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="auto-assign">Auto-assign Codes</Label>
                  <p className="text-sm text-muted-foreground">
                    Automatically generate codes for new locations
                  </p>
                </div>
                <Switch
                  id="auto-assign"
                  checked={data.autoAssignCode}
                  onCheckedChange={(checked) => setData('autoAssignCode', checked)}
                />
              </div>

              {data.autoAssignCode && (
                <div className="space-y-2">
                  <Label htmlFor="code-prefix">Code Prefix</Label>
                  <Input
                    id="code-prefix"
                    value={data.codePrefix}
                    onChange={(e) => setData('codePrefix', e.target.value)}
                    placeholder="e.g., LOC, STORE"
                    maxLength={10}
                  />
                  <p className="text-xs text-muted-foreground">
                    Prefix for auto-generated codes (e.g., LOC-001, LOC-002)
                  </p>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Approval Settings */}
          <Card>
            <CardHeader>
              <div className="flex items-center gap-2">
                <Shield className="h-5 w-5 text-primary" />
                <CardTitle>Approval Settings</CardTitle>
              </div>
              <CardDescription>
                Control how new locations are activated
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="require-approval">Require Approval</Label>
                  <p className="text-sm text-muted-foreground">
                    New locations must be approved before becoming active
                  </p>
                </div>
                <Switch
                  id="require-approval"
                  checked={data.requireApproval}
                  onCheckedChange={(checked) => setData('requireApproval', checked)}
                />
              </div>
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setData({
                  defaultTimezone: settings.defaultTimezone,
                  defaultCurrency: settings.defaultCurrency,
                  requireApproval: settings.requireApproval,
                  autoAssignCode: settings.autoAssignCode,
                  codePrefix: settings.codePrefix,
                });
              }}
            >
              Reset
            </Button>
            <Button type="submit" disabled={processing}>
              <Save className="h-4 w-4 mr-2" />
              Save Settings
            </Button>
          </div>
        </form>

          {/* Information Card */}
          <Card>
          <CardHeader>
            <CardTitle className="text-base">About Location Settings</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3 text-sm text-muted-foreground">
              <p>
                These settings apply globally to all locations in your system and serve as defaults
                when creating new locations.
              </p>
              <ul className="space-y-1 list-disc list-inside">
                <li>Individual locations can override these defaults</li>
                <li>Changes here don't affect existing location configurations</li>
                <li>Location codes must be unique across the system</li>
              </ul>
            </div>
          </CardContent>
          </Card>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}