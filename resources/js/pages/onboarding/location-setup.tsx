import { useForm } from '@inertiajs/react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
import OnboardingLayout from '@/layouts/onboarding-layout'
import { OnboardingCard } from '@/modules/onboarding'
import { PhoneInput } from '@/components/ui/phone-input'

interface LocationSetupProps {
  progress?: any
  savedData?: any
  currentStep?: number
  totalSteps?: number
  completedSteps?: string[]
}

export default function LocationSetup({ progress, savedData, currentStep = 3, totalSteps = 4, completedSteps = [] }: LocationSetupProps) {
  // Parse saved phone into country code and number
  const parsePhone = (phone: string) => {
    if (!phone) return { countryCode: '+56', phoneNumber: '' }
    
    const countryPrefixes = ['+56', '+54', '+51', '+57', '+1', '+55', '+52', '+34']
    for (const prefix of countryPrefixes) {
      if (phone.startsWith(prefix)) {
        return {
          countryCode: prefix,
          phoneNumber: phone.slice(prefix.length).trim()
        }
      }
    }
    return { countryCode: '+56', phoneNumber: phone }
  }

  const savedPhone = parsePhone(savedData?.phone || '')

  const { data, setData, post, processing, errors } = useForm({
    name: savedData?.name || '',
    type: savedData?.type || 'restaurant',
    address: savedData?.address || '',
    city: savedData?.city || '',
    state: savedData?.state || '',
    country: savedData?.country || 'CL',
    postalCode: savedData?.postalCode || '',
    phoneCountryCode: savedPhone.countryCode,
    phoneNumber: savedPhone.phoneNumber,
    email: savedData?.email || '',
    timezone: savedData?.timezone || 'America/Santiago',
    currency: savedData?.currency || 'CLP',
    capabilities: savedData?.capabilities || ['dine_in', 'takeout'],
    deliveryRadius: savedData?.deliveryRadius || '',
  })

  const handleSubmit = () => {
    // Combine phone parts before sending
    const phone = data.phoneNumber ? `${data.phoneCountryCode} ${data.phoneNumber}` : ''
    post('/onboarding/location', {
      data: { ...data, phone }
    })
  }

  const handleCapabilityChange = (capability: string, checked: boolean) => {
    const newCapabilities = checked
      ? [...data.capabilities, capability]
      : data.capabilities.filter((c: string) => c !== capability)
    setData('capabilities', newCapabilities)
  }

  const handleCountryChange = (value: string) => {
    setData('country', value)
    // Update phone country code based on country selection
    const countryToPhone: Record<string, string> = {
      CL: '+56',
      AR: '+54',
      PE: '+51',
      CO: '+57',
    }
    if (countryToPhone[value]) {
      setData('phoneCountryCode', countryToPhone[value])
    }
  }

  // Check if this step is completed
  const isStepCompleted = progress?.completedSteps?.includes('location') || false
  
  // Form validation - only essential fields required
  const isFormValid = data.name.trim() !== '' && data.type !== '' && data.capabilities.length > 0

  return (
    <OnboardingLayout
      title="Location Setup - Onboarding"
      currentStep={currentStep}
      totalSteps={totalSteps}
      stepTitle="Location Details"
      stepDescription="Set up your primary business location"
      completedSteps={completedSteps.length}
    >
      <OnboardingCard 
        estimatedTime="3 min"
        stepNumber={currentStep}
        totalSteps={totalSteps}
        stepTitle="Location Details"
        stepDescription="Set up your primary business location"
        onBack={() => window.history.back()}
        onNext={handleSubmit}
        nextDisabled={processing || !isFormValid}
        nextLoading={processing}
      >
        <div className="space-y-3">
          {/* Form Section */}
          <form onSubmit={(e) => e.preventDefault()} className="space-y-3">
                {/* Required Fields */}
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
                      Location Type <span className="text-red-500">*</span>
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
                  {errors.capabilities && (
                    <p className="text-xs text-red-600 mt-1">{errors.capabilities}</p>
                  )}
                </div>

                {/* Divider with Optional Label */}
                <div className="pt-2">
                  <p className="text-tiny font-semibold text-neutral-600 dark:text-neutral-400 uppercase mb-1">
                    Optional Information
                  </p>
                  <div className="w-full border-t border-neutral-200 dark:border-neutral-800"></div>
                </div>

                {/* Optional Fields */}
                <div>
                  <Label htmlFor="address" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Street Address
                  </Label>
                  <Input
                    id="address"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    placeholder="123 Main Street"
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
                      placeholder="Santiago"
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
                      Country
                    </Label>
                    <Select
                      value={data.country}
                      onValueChange={handleCountryChange}
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
                    <Label className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                      Phone Number
                    </Label>
                    <PhoneInput
                      countryCode={data.phoneCountryCode}
                      phoneNumber={data.phoneNumber}
                      onCountryChange={(value) => setData('phoneCountryCode', value)}
                      onPhoneChange={(value) => setData('phoneNumber', value)}
                      error={!!errors.phoneNumber || !!errors.phoneCountryCode}
                    />
                    {(errors.phoneNumber || errors.phoneCountryCode) && (
                      <p className="text-xs text-red-600 mt-1">{errors.phoneNumber || errors.phoneCountryCode}</p>
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

          </form>
        </div>
      </OnboardingCard>
    </OnboardingLayout>
  )
}