import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, ArrowRight, Building2, CheckCircle } from 'lucide-react'
import OnboardingLayout from '@/components/modules/onboarding/OnboardingLayout'
import OnboardingCard from '@/components/modules/onboarding/OnboardingCard'

interface BusinessSetupProps {
  progress?: any
  savedData?: {
    businessName?: string
    legalName?: string
    taxId?: string
    businessType?: string
    website?: string
    description?: string
  }
  currentStep?: number
  totalSteps?: number
  completedSteps?: string[]
}

export default function BusinessSetup({ progress, savedData, currentStep = 2, totalSteps = 4, completedSteps = [] }: BusinessSetupProps) {
  const { data, setData, post, processing, errors } = useForm({
    businessName: savedData?.businessName || '',
    legalName: savedData?.legalName || '',
    taxId: savedData?.taxId || '',
    businessType: savedData?.businessType || 'restaurant',
    website: savedData?.website || '',
    description: savedData?.description || '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post('/onboarding/business')
  }

  const requiredFields = [
    { icon: CheckCircle, label: 'Business name', filled: !!data.businessName },
    { icon: CheckCircle, label: 'Business type', filled: !!data.businessType },
    { icon: CheckCircle, label: 'Operating hours', filled: false },
  ]

  return (
    <OnboardingLayout
      title="Business Setup - Onboarding"
      currentStep={currentStep}
      totalSteps={totalSteps}
      stepTitle="Business Information"
      stepDescription="Tell us about your restaurant business"
      completedSteps={completedSteps.length}
    >
      <OnboardingCard estimatedTime="5 min">
        <div className="space-y-6">
          {/* Overview Section */}
          <div className="flex gap-8">
            {/* Icon */}
            <div className="shrink-0">
              <div className="p-4 rounded-xl bg-gradient-to-br from-neutral-100 to-neutral-50 dark:from-neutral-800 dark:to-neutral-900">
                <Building2 className="h-10 w-10 text-neutral-700 dark:text-neutral-300" />
              </div>
            </div>

            {/* Required Fields Preview */}
            <div className="flex-1 space-y-4">
              <div>
                <p className="text-base font-semibold text-neutral-800 dark:text-neutral-200 mb-1">
                  What we'll need from you:
                </p>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                  Have this information ready to complete this step quickly
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
                <div className="space-y-2">
                  <Label htmlFor="businessName">Business Name</Label>
                  <Input
                    id="businessName"
                    value={data.businessName}
                    onChange={(e) => setData('businessName', e.target.value)}
                    placeholder="My Restaurant"
                    required
                  />
                  {errors.businessName && (
                    <p className="text-sm text-red-600">{errors.businessName}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="legalName">Legal Name (Optional)</Label>
                  <Input
                    id="legalName"
                    value={data.legalName}
                    onChange={(e) => setData('legalName', e.target.value)}
                    placeholder="My Restaurant S.A."
                  />
                  <p className="text-xs text-gray-500">Leave empty if same as business name</p>
                  {errors.legalName && (
                    <p className="text-sm text-red-600">{errors.legalName}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="taxId">Tax ID (RUT) <span className="text-xs text-gray-500">(Optional)</span></Label>
                  <Input
                    id="taxId"
                    value={data.taxId}
                    onChange={(e) => setData('taxId', e.target.value)}
                    placeholder="12.345.678-9"
                  />
                  {errors.taxId && (
                    <p className="text-sm text-red-600">{errors.taxId}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="businessType">Business Type</Label>
                  <Select
                    value={data.businessType}
                    onValueChange={(value) => setData('businessType', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select business type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="restaurant">Restaurant</SelectItem>
                      <SelectItem value="franchise">Franchise</SelectItem>
                      <SelectItem value="chain">Restaurant Chain</SelectItem>
                      <SelectItem value="food_truck">Food Truck</SelectItem>
                      <SelectItem value="catering">Catering Service</SelectItem>
                    </SelectContent>
                  </Select>
                  {errors.businessType && (
                    <p className="text-sm text-red-600">{errors.businessType}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="website">Website (Optional)</Label>
                  <Input
                    id="website"
                    type="url"
                    value={data.website}
                    onChange={(e) => setData('website', e.target.value)}
                    placeholder="https://myrestaurant.cl"
                  />
                  {errors.website && (
                    <p className="text-sm text-red-600">{errors.website}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="description">Description (Optional)</Label>
                  <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Tell us about your restaurant..."
                    rows={4}
                  />
                  {errors.description && (
                    <p className="text-sm text-red-600">{errors.description}</p>
                  )}
                </div>

            {savedData?.businessName && (
              <Alert>
                <AlertDescription>
                  You've already completed this step. You can update your information or continue to the next step.
                </AlertDescription>
              </Alert>
            )}

            {/* Additional Info */}
            <div className="pt-4 border-t border-neutral-200 dark:border-neutral-800">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                This information will be saved securely and you can update it anytime
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