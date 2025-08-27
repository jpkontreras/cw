import { Head, useForm } from '@inertiajs/react'
import { Building2, Save, Palette, Globe, Shield, Bell } from 'lucide-react'
import AppLayout from '@/layouts/app-layout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Alert, AlertDescription } from '@/components/ui/alert'

interface Business {
  id: number
  name: string
  slug: string
  settings: Record<string, any>
  features: string[]
  limits: Record<string, number>
  primaryColor: string | null
  secondaryColor: string | null
  logoUrl: string | null
}

interface Props {
  business: Business
}

export default function BusinessSettings({ business }: Props) {
  const { data: brandingData, setData: setBrandingData, post: postBranding, processing: processingBranding } = useForm({
    primaryColor: business.primaryColor || '#000000',
    secondaryColor: business.secondaryColor || '#666666',
    logoUrl: business.logoUrl || '',
  })

  const { data: featuresData, setData: setFeaturesData, post: postFeatures, processing: processingFeatures } = useForm({
    enableOrders: business.features?.includes('orders') ?? true,
    enableInventory: business.features?.includes('inventory') ?? true,
    enableReports: business.features?.includes('reports') ?? true,
    enableOnlineOrdering: business.features?.includes('online_ordering') ?? false,
    enableTableReservations: business.features?.includes('reservations') ?? false,
    enableLoyaltyProgram: business.features?.includes('loyalty') ?? false,
  })

  const { data: notificationData, setData: setNotificationData, post: postNotifications, processing: processingNotifications } = useForm({
    emailNotifications: business.settings?.notifications?.email ?? true,
    smsNotifications: business.settings?.notifications?.sms ?? false,
    pushNotifications: business.settings?.notifications?.push ?? false,
    dailyReports: business.settings?.notifications?.dailyReports ?? true,
    lowStockAlerts: business.settings?.notifications?.lowStockAlerts ?? true,
    newOrderAlerts: business.settings?.notifications?.newOrderAlerts ?? true,
  })

  const handleBrandingSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    postBranding(`/businesses/${business.id}/settings/branding`)
  }

  const handleFeaturesSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    postFeatures(`/businesses/${business.id}/settings/features`)
  }

  const handleNotificationsSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    postNotifications(`/businesses/${business.id}/settings/notifications`)
  }

  return (
    <AppLayout>
      <Head title={`Business Settings - ${business.name}`} />

      <div className="space-y-6">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Business Settings</h2>
          <p className="text-muted-foreground">
            Configure your business preferences and features
          </p>
        </div>

        <Tabs defaultValue="general" className="space-y-4">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="general">General</TabsTrigger>
            <TabsTrigger value="branding">Branding</TabsTrigger>
            <TabsTrigger value="features">Features</TabsTrigger>
            <TabsTrigger value="notifications">Notifications</TabsTrigger>
          </TabsList>

          {/* General Settings */}
          <TabsContent value="general" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Business Limits</CardTitle>
                <CardDescription>
                  Current usage limits based on your subscription plan
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <Alert>
                  <Shield className="h-4 w-4" />
                  <AlertDescription>
                    These limits are determined by your subscription plan and cannot be modified directly.
                    Contact support to upgrade your plan.
                  </AlertDescription>
                </Alert>
                
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label>Maximum Locations</Label>
                    <p className="text-sm text-muted-foreground">
                      {business.limits?.maxLocations || 'Unlimited'}
                    </p>
                  </div>
                  <div className="space-y-2">
                    <Label>Maximum Users</Label>
                    <p className="text-sm text-muted-foreground">
                      {business.limits?.maxUsers || 'Unlimited'}
                    </p>
                  </div>
                  <div className="space-y-2">
                    <Label>Maximum Products</Label>
                    <p className="text-sm text-muted-foreground">
                      {business.limits?.maxProducts || 'Unlimited'}
                    </p>
                  </div>
                  <div className="space-y-2">
                    <Label>Monthly Orders</Label>
                    <p className="text-sm text-muted-foreground">
                      {business.limits?.maxMonthlyOrders || 'Unlimited'}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>API Access</CardTitle>
                <CardDescription>
                  Manage API keys and external integrations
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>API Key</Label>
                  <div className="flex gap-2">
                    <Input 
                      value="••••••••••••••••••••••••" 
                      disabled 
                      className="font-mono"
                    />
                    <Button variant="outline">Regenerate</Button>
                  </div>
                  <p className="text-sm text-muted-foreground">
                    Use this key to authenticate API requests
                  </p>
                </div>
                
                <div className="space-y-2">
                  <Label>Webhook URL</Label>
                  <Input 
                    placeholder="https://your-server.com/webhook" 
                  />
                  <p className="text-sm text-muted-foreground">
                    Receive real-time updates for business events
                  </p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Branding Settings */}
          <TabsContent value="branding">
            <form onSubmit={handleBrandingSubmit}>
              <Card>
                <CardHeader>
                  <CardTitle>
                    <div className="flex items-center gap-2">
                      <Palette className="h-5 w-5" />
                      Brand Customization
                    </div>
                  </CardTitle>
                  <CardDescription>
                    Customize the appearance of your business
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="logoUrl">Logo URL</Label>
                    <Input
                      id="logoUrl"
                      type="url"
                      value={brandingData.logoUrl}
                      onChange={(e) => setBrandingData('logoUrl', e.target.value)}
                      placeholder="https://example.com/logo.png"
                    />
                    <p className="text-sm text-muted-foreground">
                      Recommended size: 200x200px, PNG or JPG format
                    </p>
                  </div>

                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="primaryColor">Primary Color</Label>
                      <div className="flex gap-2">
                        <Input
                          id="primaryColor"
                          type="color"
                          value={brandingData.primaryColor}
                          onChange={(e) => setBrandingData('primaryColor', e.target.value)}
                          className="w-20"
                        />
                        <Input
                          value={brandingData.primaryColor}
                          onChange={(e) => setBrandingData('primaryColor', e.target.value)}
                          placeholder="#000000"
                        />
                      </div>
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="secondaryColor">Secondary Color</Label>
                      <div className="flex gap-2">
                        <Input
                          id="secondaryColor"
                          type="color"
                          value={brandingData.secondaryColor}
                          onChange={(e) => setBrandingData('secondaryColor', e.target.value)}
                          className="w-20"
                        />
                        <Input
                          value={brandingData.secondaryColor}
                          onChange={(e) => setBrandingData('secondaryColor', e.target.value)}
                          placeholder="#666666"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="flex justify-end">
                    <Button type="submit" disabled={processingBranding}>
                      <Save className="mr-2 h-4 w-4" />
                      Save Branding
                    </Button>
                  </div>
                </CardContent>
              </Card>
            </form>
          </TabsContent>

          {/* Features Settings */}
          <TabsContent value="features">
            <form onSubmit={handleFeaturesSubmit}>
              <Card>
                <CardHeader>
                  <CardTitle>
                    <div className="flex items-center gap-2">
                      <Globe className="h-5 w-5" />
                      Feature Management
                    </div>
                  </CardTitle>
                  <CardDescription>
                    Enable or disable features for your business
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="space-y-4">
                    <h3 className="font-medium">Core Features</h3>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="enableOrders">Order Management</Label>
                          <p className="text-sm text-muted-foreground">
                            Process and track customer orders
                          </p>
                        </div>
                        <Switch
                          id="enableOrders"
                          checked={featuresData.enableOrders}
                          onCheckedChange={(checked) => setFeaturesData('enableOrders', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="enableInventory">Inventory Tracking</Label>
                          <p className="text-sm text-muted-foreground">
                            Monitor stock levels and manage inventory
                          </p>
                        </div>
                        <Switch
                          id="enableInventory"
                          checked={featuresData.enableInventory}
                          onCheckedChange={(checked) => setFeaturesData('enableInventory', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="enableReports">Reports & Analytics</Label>
                          <p className="text-sm text-muted-foreground">
                            Access detailed business insights
                          </p>
                        </div>
                        <Switch
                          id="enableReports"
                          checked={featuresData.enableReports}
                          onCheckedChange={(checked) => setFeaturesData('enableReports', checked)}
                        />
                      </div>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <h3 className="font-medium">Advanced Features</h3>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="enableOnlineOrdering">Online Ordering</Label>
                          <p className="text-sm text-muted-foreground">
                            Accept orders through your website
                          </p>
                        </div>
                        <Switch
                          id="enableOnlineOrdering"
                          checked={featuresData.enableOnlineOrdering}
                          onCheckedChange={(checked) => setFeaturesData('enableOnlineOrdering', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="enableTableReservations">Table Reservations</Label>
                          <p className="text-sm text-muted-foreground">
                            Manage table bookings and reservations
                          </p>
                        </div>
                        <Switch
                          id="enableTableReservations"
                          checked={featuresData.enableTableReservations}
                          onCheckedChange={(checked) => setFeaturesData('enableTableReservations', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="enableLoyaltyProgram">Loyalty Program</Label>
                          <p className="text-sm text-muted-foreground">
                            Reward regular customers with points
                          </p>
                        </div>
                        <Switch
                          id="enableLoyaltyProgram"
                          checked={featuresData.enableLoyaltyProgram}
                          onCheckedChange={(checked) => setFeaturesData('enableLoyaltyProgram', checked)}
                        />
                      </div>
                    </div>
                  </div>

                  <div className="flex justify-end">
                    <Button type="submit" disabled={processingFeatures}>
                      <Save className="mr-2 h-4 w-4" />
                      Save Features
                    </Button>
                  </div>
                </CardContent>
              </Card>
            </form>
          </TabsContent>

          {/* Notifications Settings */}
          <TabsContent value="notifications">
            <form onSubmit={handleNotificationsSubmit}>
              <Card>
                <CardHeader>
                  <CardTitle>
                    <div className="flex items-center gap-2">
                      <Bell className="h-5 w-5" />
                      Notification Preferences
                    </div>
                  </CardTitle>
                  <CardDescription>
                    Configure how you receive business notifications
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="space-y-4">
                    <h3 className="font-medium">Notification Channels</h3>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="emailNotifications">Email Notifications</Label>
                          <p className="text-sm text-muted-foreground">
                            Receive updates via email
                          </p>
                        </div>
                        <Switch
                          id="emailNotifications"
                          checked={notificationData.emailNotifications}
                          onCheckedChange={(checked) => setNotificationData('emailNotifications', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="smsNotifications">SMS Notifications</Label>
                          <p className="text-sm text-muted-foreground">
                            Get text messages for urgent alerts
                          </p>
                        </div>
                        <Switch
                          id="smsNotifications"
                          checked={notificationData.smsNotifications}
                          onCheckedChange={(checked) => setNotificationData('smsNotifications', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="pushNotifications">Push Notifications</Label>
                          <p className="text-sm text-muted-foreground">
                            Real-time notifications in the app
                          </p>
                        </div>
                        <Switch
                          id="pushNotifications"
                          checked={notificationData.pushNotifications}
                          onCheckedChange={(checked) => setNotificationData('pushNotifications', checked)}
                        />
                      </div>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <h3 className="font-medium">Notification Types</h3>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="dailyReports">Daily Reports</Label>
                          <p className="text-sm text-muted-foreground">
                            Receive daily business summaries
                          </p>
                        </div>
                        <Switch
                          id="dailyReports"
                          checked={notificationData.dailyReports}
                          onCheckedChange={(checked) => setNotificationData('dailyReports', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="lowStockAlerts">Low Stock Alerts</Label>
                          <p className="text-sm text-muted-foreground">
                            Alert when inventory is running low
                          </p>
                        </div>
                        <Switch
                          id="lowStockAlerts"
                          checked={notificationData.lowStockAlerts}
                          onCheckedChange={(checked) => setNotificationData('lowStockAlerts', checked)}
                        />
                      </div>

                      <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                          <Label htmlFor="newOrderAlerts">New Order Alerts</Label>
                          <p className="text-sm text-muted-foreground">
                            Notify when new orders are placed
                          </p>
                        </div>
                        <Switch
                          id="newOrderAlerts"
                          checked={notificationData.newOrderAlerts}
                          onCheckedChange={(checked) => setNotificationData('newOrderAlerts', checked)}
                        />
                      </div>
                    </div>
                  </div>

                  <div className="flex justify-end">
                    <Button type="submit" disabled={processingNotifications}>
                      <Save className="mr-2 h-4 w-4" />
                      Save Notifications
                    </Button>
                  </div>
                </CardContent>
              </Card>
            </form>
          </TabsContent>
        </Tabs>
      </div>
    </AppLayout>
  )
}