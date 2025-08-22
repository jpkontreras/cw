import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Page from '@/layouts/page-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { 
  Building2, 
  ShoppingCart, 
  Receipt, 
  Calculator, 
  Bell, 
  Plug,
  CheckCircle,
  AlertCircle,
  Package,
  CreditCard,
  Globe,
  Printer,
  Shield,
  Palette
} from 'lucide-react';

interface SettingGroup {
  category: string;
  label: string;
  description: string;
  icon: string;
  settings: any[];
  totalSettings: number;
  configuredSettings: number;
  isComplete: boolean;
}

interface Props {
  groups: SettingGroup[];
  isFullyConfigured: boolean;
  missingSettings: any[];
}

const iconMap: Record<string, any> = {
  'building-2': Building2,
  'shopping-cart': ShoppingCart,
  'receipt': Receipt,
  'calculator': Calculator,
  'bell': Bell,
  'plug': Plug,
  'package': Package,
  'credit-card': CreditCard,
  'globe': Globe,
  'printer': Printer,
  'shield': Shield,
  'palette': Palette,
};

function SystemSettingsContent({ groups, isFullyConfigured, missingSettings }: Props) {
  return (
    <>
      <Page.Header
        title="System Settings"
        subtitle="Configure system-wide settings for your organization"
      />
      
      <Page.Content>
        <div className="space-y-6">
          {!isFullyConfigured && missingSettings.length > 0 && (
            <Card className="border-yellow-200 bg-yellow-50">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <AlertCircle className="h-5 w-5 text-yellow-600" />
                  Configuration Required
                </CardTitle>
                <CardDescription>
                  {missingSettings.length} required settings need to be configured
                </CardDescription>
              </CardHeader>
            </Card>
          )}

          <div className="grid gap-4 md:grid-cols-2">
            {groups && groups.length > 0 ? (
              groups.map((group) => {
                const Icon = iconMap[group.icon] || Building2;
                
                return (
                  <Card key={group.category} className="hover:shadow-md transition-shadow">
                    <CardHeader>
                      <CardTitle className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <Icon className="h-5 w-5" />
                          {group.label}
                        </div>
                        {group.isComplete && (
                          <CheckCircle className="h-4 w-4 text-green-600" />
                        )}
                      </CardTitle>
                      <CardDescription>{group.description}</CardDescription>
                    </CardHeader>
                    <CardContent>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">
                          {group.configuredSettings || 0} / {group.totalSettings || 0} configured
                        </span>
                        <Button size="sm" variant="outline" asChild>
                          <Link href={`/system-settings/${group.category.toLowerCase().replace('_', '-')}`}>
                            Configure
                          </Link>
                        </Button>
                      </div>
                    </CardContent>
                  </Card>
                );
              })
            ) : (
              <Card className="col-span-2">
                <CardContent className="flex flex-col items-center justify-center py-12">
                  <AlertCircle className="h-12 w-12 text-muted-foreground mb-4" />
                  <p className="text-lg font-medium mb-2">No Settings Available</p>
                  <p className="text-sm text-muted-foreground mb-4">Settings have not been initialized yet.</p>
                  <Button asChild>
                    <Link href="/system-settings/initialize">Initialize Settings</Link>
                  </Button>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </Page.Content>
    </>
  );
}

export default function SystemSettings(props: Props) {
  return (
    <AppLayout>
      <Head title="System Settings" />
      <Page>
        <SystemSettingsContent {...props} />
      </Page>
    </AppLayout>
  );
}