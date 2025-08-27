import { Head, Link, useForm } from '@inertiajs/react'
import { Building2, Save, ArrowLeft } from 'lucide-react'
import AppLayout from '@/layouts/app-layout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useState } from 'react'

interface Business {
  id: number
  name: string
  slug: string
  legalName: string | null
  taxId: string | null
  type: string
  status: string
  email: string | null
  phone: string | null
  website: string | null
  address: string | null
  addressLine2: string | null
  city: string | null
  state: string | null
  country: string
  postalCode: string | null
  currency: string
  timezone: string
  locale: string
  subscriptionTier: string
  trialEndsAt: string | null
  logoUrl: string | null
  primaryColor: string | null
  secondaryColor: string | null
  isDemo: boolean
  createdAt: string
  updatedAt: string
}

interface Props {
  business: Business
  canEdit: boolean
}

export default function BusinessShow({ business, canEdit }: Props) {
  const [isEditing, setIsEditing] = useState(false)
  
  const { data, setData, put, processing, errors, reset } = useForm({
    name: business.name || '',
    legalName: business.legalName || '',
    taxId: business.taxId || '',
    type: business.type || 'independent',
    email: business.email || '',
    phone: business.phone || '',
    website: business.website || '',
    address: business.address || '',
    addressLine2: business.addressLine2 || '',
    city: business.city || '',
    state: business.state || '',
    postalCode: business.postalCode || '',
    country: business.country || 'CL',
    currency: business.currency || 'CLP',
    timezone: business.timezone || 'America/Santiago',
    locale: business.locale || 'es_CL',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    put(`/businesses/${business.id}`, {
      onSuccess: () => {
        setIsEditing(false)
      },
    })
  }

  const handleCancel = () => {
    setIsEditing(false)
    reset()
  }

  return (
    <AppLayout>
      <Head title={`Business - ${business.name}`} />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link href="/businesses">
              <Button variant="ghost" size="icon">
                <ArrowLeft className="h-4 w-4" />
              </Button>
            </Link>
            <div>
              <h2 className="text-3xl font-bold tracking-tight">{business.name}</h2>
              <p className="text-muted-foreground">
                Manage your business information and settings
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            {business.isDemo && (
              <Badge variant="warning">Demo Business</Badge>
            )}
            <Badge variant={business.status === 'active' ? 'success' : 'secondary'}>
              {business.status}
            </Badge>
            <Badge variant="outline">
              {business.subscriptionTier} Plan
            </Badge>
          </div>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="grid gap-6">
            {/* Basic Information */}
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>Basic Information</CardTitle>
                    <CardDescription>
                      Essential business details and identification
                    </CardDescription>
                  </div>
                  {canEdit && !isEditing && (
                    <Button 
                      type="button" 
                      onClick={() => setIsEditing(true)}
                      variant="outline"
                    >
                      Edit
                    </Button>
                  )}
                  {isEditing && (
                    <div className="flex gap-2">
                      <Button 
                        type="button" 
                        onClick={handleCancel}
                        variant="outline"
                      >
                        Cancel
                      </Button>
                      <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        Save Changes
                      </Button>
                    </div>
                  )}
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name">Business Name</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="name"
                          value={data.name}
                          onChange={(e) => setData('name', e.target.value)}
                          disabled={processing}
                        />
                        {errors.name && (
                          <p className="text-sm text-red-600">{errors.name}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.name}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="legalName">Legal Name</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="legalName"
                          value={data.legalName}
                          onChange={(e) => setData('legalName', e.target.value)}
                          disabled={processing}
                        />
                        {errors.legalName && (
                          <p className="text-sm text-red-600">{errors.legalName}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.legalName || 'Not specified'}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="taxId">Tax ID (RUT)</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="taxId"
                          value={data.taxId}
                          onChange={(e) => setData('taxId', e.target.value)}
                          placeholder="12.345.678-9"
                          disabled={processing}
                        />
                        {errors.taxId && (
                          <p className="text-sm text-red-600">{errors.taxId}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.taxId || 'Not specified'}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="type">Business Type</Label>
                    {isEditing ? (
                      <>
                        <Select
                          value={data.type}
                          onValueChange={(value) => setData('type', value)}
                          disabled={processing}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Select business type" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="independent">Independent Business</SelectItem>
                            <SelectItem value="franchise">Franchise</SelectItem>
                            <SelectItem value="corporate">Corporate/Chain</SelectItem>
                          </SelectContent>
                        </Select>
                        {errors.type && (
                          <p className="text-sm text-red-600">{errors.type}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm capitalize">{business.type}</p>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Contact Information */}
            <Card>
              <CardHeader>
                <CardTitle>Contact Information</CardTitle>
                <CardDescription>
                  How customers and partners can reach your business
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="email">Email</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="email"
                          type="email"
                          value={data.email}
                          onChange={(e) => setData('email', e.target.value)}
                          disabled={processing}
                        />
                        {errors.email && (
                          <p className="text-sm text-red-600">{errors.email}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.email || 'Not specified'}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="phone">Phone</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="phone"
                          value={data.phone}
                          onChange={(e) => setData('phone', e.target.value)}
                          disabled={processing}
                        />
                        {errors.phone && (
                          <p className="text-sm text-red-600">{errors.phone}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.phone || 'Not specified'}</p>
                    )}
                  </div>

                  <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="website">Website</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="website"
                          type="url"
                          value={data.website}
                          onChange={(e) => setData('website', e.target.value)}
                          disabled={processing}
                        />
                        {errors.website && (
                          <p className="text-sm text-red-600">{errors.website}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">
                        {business.website ? (
                          <a href={business.website} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">
                            {business.website}
                          </a>
                        ) : (
                          'Not specified'
                        )}
                      </p>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Address */}
            <Card>
              <CardHeader>
                <CardTitle>Address</CardTitle>
                <CardDescription>
                  Physical location of your business headquarters
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="address">Street Address</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="address"
                          value={data.address}
                          onChange={(e) => setData('address', e.target.value)}
                          disabled={processing}
                        />
                        {errors.address && (
                          <p className="text-sm text-red-600">{errors.address}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.address || 'Not specified'}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="addressLine2">Address Line 2</Label>
                    {isEditing ? (
                      <>
                        <Input
                          id="addressLine2"
                          value={data.addressLine2}
                          onChange={(e) => setData('addressLine2', e.target.value)}
                          placeholder="Apartment, suite, etc."
                          disabled={processing}
                        />
                        {errors.addressLine2 && (
                          <p className="text-sm text-red-600">{errors.addressLine2}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.addressLine2 || '-'}</p>
                    )}
                  </div>

                  <div className="grid gap-4 md:grid-cols-3">
                    <div className="space-y-2">
                      <Label htmlFor="city">City</Label>
                      {isEditing ? (
                        <>
                          <Input
                            id="city"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                            disabled={processing}
                          />
                          {errors.city && (
                            <p className="text-sm text-red-600">{errors.city}</p>
                          )}
                        </>
                      ) : (
                        <p className="text-sm">{business.city || 'Not specified'}</p>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="state">State/Region</Label>
                      {isEditing ? (
                        <>
                          <Input
                            id="state"
                            value={data.state}
                            onChange={(e) => setData('state', e.target.value)}
                            disabled={processing}
                          />
                          {errors.state && (
                            <p className="text-sm text-red-600">{errors.state}</p>
                          )}
                        </>
                      ) : (
                        <p className="text-sm">{business.state || 'Not specified'}</p>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="postalCode">Postal Code</Label>
                      {isEditing ? (
                        <>
                          <Input
                            id="postalCode"
                            value={data.postalCode}
                            onChange={(e) => setData('postalCode', e.target.value)}
                            disabled={processing}
                          />
                          {errors.postalCode && (
                            <p className="text-sm text-red-600">{errors.postalCode}</p>
                          )}
                        </>
                      ) : (
                        <p className="text-sm">{business.postalCode || 'Not specified'}</p>
                      )}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Regional Settings */}
            <Card>
              <CardHeader>
                <CardTitle>Regional Settings</CardTitle>
                <CardDescription>
                  Currency, timezone, and locale preferences
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-3">
                  <div className="space-y-2">
                    <Label htmlFor="currency">Currency</Label>
                    {isEditing ? (
                      <>
                        <Select
                          value={data.currency}
                          onValueChange={(value) => setData('currency', value)}
                          disabled={processing}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Select currency" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="CLP">Chilean Peso (CLP)</SelectItem>
                            <SelectItem value="USD">US Dollar (USD)</SelectItem>
                            <SelectItem value="EUR">Euro (EUR)</SelectItem>
                          </SelectContent>
                        </Select>
                        {errors.currency && (
                          <p className="text-sm text-red-600">{errors.currency}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.currency}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="timezone">Timezone</Label>
                    {isEditing ? (
                      <>
                        <Select
                          value={data.timezone}
                          onValueChange={(value) => setData('timezone', value)}
                          disabled={processing}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Select timezone" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="America/Santiago">Santiago</SelectItem>
                            <SelectItem value="America/New_York">New York</SelectItem>
                            <SelectItem value="America/Los_Angeles">Los Angeles</SelectItem>
                            <SelectItem value="Europe/London">London</SelectItem>
                            <SelectItem value="Europe/Paris">Paris</SelectItem>
                            <SelectItem value="Asia/Tokyo">Tokyo</SelectItem>
                          </SelectContent>
                        </Select>
                        {errors.timezone && (
                          <p className="text-sm text-red-600">{errors.timezone}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.timezone}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="locale">Locale</Label>
                    {isEditing ? (
                      <>
                        <Select
                          value={data.locale}
                          onValueChange={(value) => setData('locale', value)}
                          disabled={processing}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Select locale" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="es_CL">Spanish (Chile)</SelectItem>
                            <SelectItem value="en_US">English (US)</SelectItem>
                            <SelectItem value="es_ES">Spanish (Spain)</SelectItem>
                            <SelectItem value="pt_BR">Portuguese (Brazil)</SelectItem>
                          </SelectContent>
                        </Select>
                        {errors.locale && (
                          <p className="text-sm text-red-600">{errors.locale}</p>
                        )}
                      </>
                    ) : (
                      <p className="text-sm">{business.locale}</p>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Subscription Information */}
            {!isEditing && (
              <Card>
                <CardHeader>
                  <CardTitle>Subscription Information</CardTitle>
                  <CardDescription>
                    Your current plan and billing details
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label>Current Plan</Label>
                      <div className="flex items-center gap-2">
                        <Badge variant="outline" className="capitalize">
                          {business.subscriptionTier}
                        </Badge>
                        {business.trialEndsAt && (
                          <Badge variant="info">
                            Trial ends {new Date(business.trialEndsAt).toLocaleDateString()}
                          </Badge>
                        )}
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Business ID</Label>
                      <p className="text-sm font-mono">{business.slug}</p>
                    </div>
                    <div className="space-y-2">
                      <Label>Created</Label>
                      <p className="text-sm">
                        {new Date(business.createdAt).toLocaleDateString()}
                      </p>
                    </div>
                    <div className="space-y-2">
                      <Label>Last Updated</Label>
                      <p className="text-sm">
                        {new Date(business.updatedAt).toLocaleDateString()}
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        </form>
      </div>
    </AppLayout>
  )
}