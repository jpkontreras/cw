import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, ArrowRight, MapPin, CheckCircle } from 'lucide-react'
import OnboardingLayout from '@/components/modules/onboarding/OnboardingLayout'
import OnboardingCard from '@/components/modules/onboarding/OnboardingCard'

interface LocationSetupProps {
  progress?: any
  savedData?: any
  currentStep?: number
  totalSteps?: number
  completedSteps?: string[]
}

export default function LocationSetup({ progress, savedData, currentStep = 3, totalSteps = 4, completedSteps = [] }: LocationSetupProps) {
  const { data, setData, post, processing, errors } = useForm({
    name: savedData?.name || '',
    type: savedData?.type || 'restaurant',
    address: savedData?.address || '',
    city: savedData?.city || '',
    state: savedData?.state || '',
    country: savedData?.country || 'CL',
    postalCode: savedData?.postalCode || '',
    phone: savedData?.phone || '',
    email: savedData?.email || '',
    timezone: savedData?.timezone || 'America/Santiago',
    currency: savedData?.currency || 'CLP',
    capabilities: savedData?.capabilities || ['dine_in', 'takeout'],
    deliveryRadius: savedData?.deliveryRadius || '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post('/onboarding/location')
  }

  const handleCapabilityChange = (capability: string, checked: boolean) => {
    const newCapabilities = checked
      ? [...data.capabilities, capability]
      : data.capabilities.filter((c: string) => c !== capability)
    setData('capabilities', newCapabilities)
  }

  const requiredFields = [
    { icon: CheckCircle, label: 'Address', filled: !!data.address },
    { icon: CheckCircle, label: 'Contact info', filled: !!data.phone },
    { icon: CheckCircle, label: 'Service areas', filled: data.capabilities.length > 0 },
    { icon: CheckCircle, label: 'Capacity', filled: false },
  ]

  return (
    <OnboardingLayout
      title="Location Setup - Onboarding"
      currentStep={currentStep}
      totalSteps={totalSteps}
      stepTitle="Location Details"
      stepDescription="Set up your primary restaurant location"
      completedSteps={completedSteps.length}
    >
      <OnboardingCard estimatedTime="3 min">
        <div className="space-y-6">
          {/* Overview Section */}
          <div className="flex gap-8">
            {/* Icon */}
            <div className="shrink-0">
              <div className="p-4 rounded-xl bg-gradient-to-br from-neutral-100 to-neutral-50 dark:from-neutral-800 dark:to-neutral-900">
                <MapPin className="h-10 w-10 text-neutral-700 dark:text-neutral-300" />
              </div>
            </div>

            {/* Required Fields Preview */}
            <div className="flex-1 space-y-4">
              <div>
                <p className="text-base font-semibold text-neutral-800 dark:text-neutral-200 mb-1">
                  What we'll need from you:
                </p>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                  Configure your main restaurant location
                </p>
              </div>
              <div className="grid grid-cols-2 gap-3">
                {requiredFields.map((field) => (
                  <div key={field.label} className="flex items-center gap-2 p-3 rounded-lg bg-neutral-50 dark:bg-neutral-800/50">
                    <CheckCircle className={`h-4 w-4 shrink-0 ${field.filled ? 'text-green-500' : 'text-neutral-400'}`} />
                    <span className="text-sm text-neutral-700 dark:text-neutral-300">{field.label}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Divider */}
          <div className="border-t border-neutral-200 dark:border-neutral-800" />

          {/* Form Section */}
          <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name">Location Name</Label>
                    <Input
                      id="name"
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      placeholder="Main Restaurant"
                      required
                    />
                    {errors.name && (
                      <p className="text-sm text-red-600">{errors.name}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="type">Location Type</Label>
                    <Select
                      value={data.type}
                      onValueChange={(value) => setData('type', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select type" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="restaurant">Restaurant</SelectItem>
                        <SelectItem value="kitchen">Kitchen</SelectItem>
                        <SelectItem value="warehouse">Warehouse</SelectItem>
                        <SelectItem value="central_kitchen">Central Kitchen</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.type && (
                      <p className="text-sm text-red-600">{errors.type}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="address">Street Address</Label>
                  <Input
                    id="address"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    placeholder="123 Main Street"
                    required
                  />
                  {errors.address && (
                    <p className="text-sm text-red-600">{errors.address}</p>
                  )}
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="city">City</Label>
                    <Input
                      id="city"
                      value={data.city}
                      onChange={(e) => setData('city', e.target.value)}
                      placeholder="Santiago"
                      required
                    />
                    {errors.city && (
                      <p className="text-sm text-red-600">{errors.city}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="state">State/Region</Label>
                    <Input
                      id="state"
                      value={data.state}
                      onChange={(e) => setData('state', e.target.value)}
                      placeholder="RM"
                    />
                    {errors.state && (
                      <p className="text-sm text-red-600">{errors.state}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="postalCode">Postal Code</Label>
                    <Input
                      id="postalCode"
                      value={data.postalCode}
                      onChange={(e) => setData('postalCode', e.target.value)}
                      placeholder="7500000"
                    />
                    {errors.postalCode && (
                      <p className="text-sm text-red-600">{errors.postalCode}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="country">Country</Label>
                    <Select
                      value={data.country}
                      onValueChange={(value) => setData('country', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select country" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="CL">Chile</SelectItem>
                        <SelectItem value="AR">Argentina</SelectItem>
                        <SelectItem value="PE">Peru</SelectItem>
                        <SelectItem value="CO">Colombia</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.country && (
                      <p className="text-sm text-red-600">{errors.country}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="phone">Phone Number</Label>
                    <Input
                      id="phone"
                      value={data.phone}
                      onChange={(e) => setData('phone', e.target.value)}
                      placeholder="+56 2 1234 5678"
                      required
                    />
                    {errors.phone && (
                      <p className="text-sm text-red-600">{errors.phone}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="email">Location Email</Label>
                    <Input
                      id="email"
                      type="email"
                      value={data.email}
                      onChange={(e) => setData('email', e.target.value)}
                      placeholder="info@restaurant.cl"
                    />
                    {errors.email && (
                      <p className="text-sm text-red-600">{errors.email}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>Service Capabilities</Label>
                  <div className="space-y-2">
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        id="dine_in"
                        checked={data.capabilities.includes('dine_in')}
                        onCheckedChange={(checked) => handleCapabilityChange('dine_in', !!checked)}
                      />
                      <Label htmlFor="dine_in" className="font-normal">Dine-in Service</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        id="takeout"
                        checked={data.capabilities.includes('takeout')}
                        onCheckedChange={(checked) => handleCapabilityChange('takeout', !!checked)}
                      />
                      <Label htmlFor="takeout" className="font-normal">Takeout/Pickup</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        id="delivery"
                        checked={data.capabilities.includes('delivery')}
                        onCheckedChange={(checked) => handleCapabilityChange('delivery', !!checked)}
                      />
                      <Label htmlFor="delivery" className="font-normal">Delivery Service</Label>
                    </div>
                  </div>
                </div>

                {data.capabilities.includes('delivery') && (
                  <div className="space-y-2">
                    <Label htmlFor="deliveryRadius">Delivery Radius (km)</Label>
                    <Input
                      id="deliveryRadius"
                      type="number"
                      value={data.deliveryRadius}
                      onChange={(e) => setData('deliveryRadius', e.target.value)}
                      placeholder="5"
                    />
                    {errors.deliveryRadius && (
                      <p className="text-sm text-red-600">{errors.deliveryRadius}</p>
                    )}
                  </div>
                )}

                {savedData?.name && (
                  <Alert>
                    <AlertDescription>
                      You've already completed this step. You can update your information or continue to the next step.
                    </AlertDescription>
                  </Alert>
                )}

            {/* Additional Info */}
            <div className="pt-4 border-t border-neutral-200 dark:border-neutral-800">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                You can add more locations later from your settings
              </p>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-between pt-4">
              <Button
                type="button"
                variant="outline"
                onClick={() => window.history.back()}
              >
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back
              </Button>
              
              <Button type="submit" disabled={processing} size="lg">
                Continue Setup
                <ArrowRight className="ml-2 h-4 w-4" />
              </Button>
            </div>
          </form>
        </div>
      </OnboardingCard>
    </OnboardingLayout>
  )
}