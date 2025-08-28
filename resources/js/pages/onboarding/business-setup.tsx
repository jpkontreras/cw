import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, ArrowRight, Building2, CheckCircle } from 'lucide-react'
import OnboardingLayout from '@/layouts/onboarding-layout'
import { OnboardingCard } from '@/modules/onboarding'

interface BusinessSetupProps {
  progress?: any
  savedData?: {
    businessName?: string
    legalName?: string
    taxId?: string
    businessType?: string
    businessEmail?: string
    businessPhone?: string
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
    businessType: savedData?.businessType || 'independent',
    businessEmail: savedData?.businessEmail || '',
    businessPhone: savedData?.businessPhone || '',
    website: savedData?.website || '',
    description: savedData?.description || '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post('/onboarding/business')
  }

  // Check if this step is completed
  const isStepCompleted = progress?.completedSteps?.includes('business') || false
  
  // Form validation
  const isFormValid = data.businessName.trim() !== '' && data.businessType !== ''

  return (
    <OnboardingLayout
      title="Business Setup - Onboarding"
      currentStep={currentStep}
      totalSteps={totalSteps}
      stepTitle="Business Information"
      stepDescription="Tell us about your business"
      completedSteps={completedSteps.length}
    >
      <OnboardingCard estimatedTime="5 min">
        <div className="space-y-3">
          {/* Form Section */}
          <form onSubmit={handleSubmit} className="space-y-3">
                <div>
                  <Label htmlFor="businessName" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Business Name <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="businessName"
                    value={data.businessName}
                    onChange={(e) => setData('businessName', e.target.value)}
                    placeholder="My Business"
                    className="h-9"
                    required
                  />
                  {errors.businessName && (
                    <p className="text-xs text-red-600 mt-1">{errors.businessName}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="legalName" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Legal Name <span className="text-xs text-neutral-500">(Optional)</span>
                  </Label>
                  <Input
                    id="legalName"
                    value={data.legalName}
                    onChange={(e) => setData('legalName', e.target.value)}
                    placeholder="My Business S.A. (leave empty if same as business name)"
                    className="h-9"
                  />
                  {errors.legalName && (
                    <p className="text-xs text-red-600 mt-1">{errors.legalName}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="taxId" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Tax ID (RUT) <span className="text-xs text-neutral-500">(Optional)</span>
                  </Label>
                  <Input
                    id="taxId"
                    value={data.taxId}
                    onChange={(e) => setData('taxId', e.target.value)}
                    placeholder="12.345.678-9"
                    className="h-9"
                  />
                  {errors.taxId && (
                    <p className="text-xs text-red-600 mt-1">{errors.taxId}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="businessType" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Business Type <span className="text-red-500">*</span>
                  </Label>
                  <Select
                    value={data.businessType}
                    onValueChange={(value) => setData('businessType', value)}
                  >
                    <SelectTrigger className="h-9">
                      <SelectValue placeholder="Select business type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="independent">Independent Business</SelectItem>
                      <SelectItem value="franchise">Franchise</SelectItem>
                      <SelectItem value="corporate">Corporate/Chain</SelectItem>
                    </SelectContent>
                  </Select>
                  {errors.businessType && (
                    <p className="text-xs text-red-600 mt-1">{errors.businessType}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="website" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Website <span className="text-xs text-neutral-500">(Optional)</span>
                  </Label>
                  <Input
                    id="website"
                    type="url"
                    value={data.website}
                    onChange={(e) => setData('website', e.target.value)}
                    placeholder="https://mybusiness.cl"
                    className="h-9"
                  />
                  {errors.website && (
                    <p className="text-xs text-red-600 mt-1">{errors.website}</p>
                  )}
                </div>

                <div>
                  <Label htmlFor="description" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">
                    Description <span className="text-xs text-neutral-500">(Optional)</span>
                  </Label>
                  <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Tell us about your business..."
                    rows={3}
                    className="resize-none"
                  />
                  {errors.description && (
                    <p className="text-xs text-red-600 mt-1">{errors.description}</p>
                  )}
                </div>

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
                This information will be saved securely and you can update it anytime
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