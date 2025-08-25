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

  // Check if this step is completed
  const isStepCompleted = progress?.completedSteps?.includes('location') || false
  
  // Form validation - only essential fields required
  const isFormValid = data.name.trim() !== '' && 
                      data.country !== '' && 
                      data.phone.trim() !== '' &&
                      data.capabilities.length > 0

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
        <div className="space-y-3">
          {/* Form Section */}
          <form onSubmit={handleSubmit} className="space-y-3">
                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <Label htmlFor="name" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Location Name <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id="name"
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      placeholder="Main Restaurant"
                      className="h-9"
                      required
                    />
                    {errors.name && (
                      <p className="text-xs text-red-600 mt-1">{errors.name}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="type" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Location Type
                    </Label>
                    <Select
                      value={data.type}
                      onValueChange={(value) => setData('type', value)}
                    >
                      <SelectTrigger className="h-9">
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
                      <p className="text-xs text-red-600 mt-1">{errors.type}</p>
                    )}
                  </div>
                </div>

                <div>
                  <Label htmlFor="address" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Street Address
                  </Label>
                  <Input
                    id="address"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    placeholder="123 Main Street (optional)"
                    className="h-9"
                  />
                  {errors.address && (
                    <p className="text-xs text-red-600 mt-1">{errors.address}</p>
                  )}
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <Label htmlFor="city" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      City
                    </Label>
                    <Input
                      id="city"
                      value={data.city}
                      onChange={(e) => setData('city', e.target.value)}
                      placeholder="Santiago (optional)"
                      className="h-9"
                    />
                    {errors.city && (
                      <p className="text-xs text-red-600 mt-1">{errors.city}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="state" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      State/Region
                    </Label>
                    <Input
                      id="state"
                      value={data.state}
                      onChange={(e) => setData('state', e.target.value)}
                      placeholder="RM"
                      className="h-9"
                    />
                    {errors.state && (
                      <p className="text-xs text-red-600 mt-1">{errors.state}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <Label htmlFor="postalCode" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Postal Code
                    </Label>
                    <Input
                      id="postalCode"
                      value={data.postalCode}
                      onChange={(e) => setData('postalCode', e.target.value)}
                      placeholder="7500000"
                      className="h-9"
                    />
                    {errors.postalCode && (
                      <p className="text-xs text-red-600 mt-1">{errors.postalCode}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="country" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Country <span className="text-red-500">*</span>
                    </Label>
                    <Select
                      value={data.country}
                      onValueChange={(value) => setData('country', value)}
                    >
                      <SelectTrigger className="h-9">
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
                      <p className="text-xs text-red-600 mt-1">{errors.country}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <Label htmlFor="phone" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Phone Number <span className="text-red-500">*</span>
                    </Label>
                    <Input
                      id="phone"
                      value={data.phone}
                      onChange={(e) => setData('phone', e.target.value)}
                      placeholder="+56 2 1234 5678"
                      className="h-9"
                      required
                    />
                    {errors.phone && (
                      <p className="text-xs text-red-600 mt-1">{errors.phone}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="email" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Location Email
                    </Label>
                    <Input
                      id="email"
                      type="email"
                      value={data.email}
                      onChange={(e) => setData('email', e.target.value)}
                      placeholder="info@restaurant.cl"
                      className="h-9"
                    />
                    {errors.email && (
                      <p className="text-xs text-red-600 mt-1">{errors.email}</p>
                    )}
                  </div>
                </div>

                <div>
                  <Label className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Service Capabilities <span className="text-red-500">*</span>
                  </Label>
                  <div className="space-y-1">
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        id="dine_in"
                        checked={data.capabilities.includes('dine_in')}
                        onCheckedChange={(checked) => handleCapabilityChange('dine_in', !!checked)}
                      />
                      <Label htmlFor="dine_in" className="text-sm font-normal">Dine-in Service</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        id="takeout"
                        checked={data.capabilities.includes('takeout')}
                        onCheckedChange={(checked) => handleCapabilityChange('takeout', !!checked)}
                      />
                      <Label htmlFor="takeout" className="text-sm font-normal">Takeout/Pickup</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        id="delivery"
                        checked={data.capabilities.includes('delivery')}
                        onCheckedChange={(checked) => handleCapabilityChange('delivery', !!checked)}
                      />
                      <Label htmlFor="delivery" className="text-sm font-normal">Delivery Service</Label>
                    </div>
                  </div>
                </div>

                {data.capabilities.includes('delivery') && (
                  <div>
                    <Label htmlFor="deliveryRadius" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Delivery Radius (km)
                    </Label>
                    <Input
                      id="deliveryRadius"
                      type="number"
                      value={data.deliveryRadius}
                      onChange={(e) => setData('deliveryRadius', e.target.value)}
                      placeholder="5"
                      className="h-9"
                    />
                    {errors.deliveryRadius && (
                      <p className="text-xs text-red-600 mt-1">{errors.deliveryRadius}</p>
                    )}
                  </div>
                )}

                {isStepCompleted && (
                  <Alert className="py-2">
                    <AlertDescription className="text-xs">
                      You've already completed this step. You can update your information or continue to the next step.
                    </AlertDescription>
                  </Alert>
                )}

            {/* Additional Info */}
            <div className="pt-2 border-t border-neutral-200 dark:border-neutral-800">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                You can add more locations later from your settings
              </p>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-between pt-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => window.history.back()}
              >
                <ArrowLeft className="mr-1 h-3 w-3" />
                Back
              </Button>
              
              <Button 
                type="submit" 
                disabled={processing || !isFormValid} 
                size="sm"
              >
                {processing ? (
                  <span className="animate-pulse">Saving...</span>
                ) : (
                  <>
                    Continue Setup
                    <ArrowRight className="ml-1 h-3 w-3" />
                  </>
                )}
              </Button>
            </div>
          </form>
        </div>
      </OnboardingCard>
    </OnboardingLayout>
  )
}