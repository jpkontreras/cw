import React from 'react'
import { useForm, Link } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, ArrowRight, Mail, AlertCircle, CheckCircle, XCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import OnboardingLayout from '@/layouts/onboarding-layout'
import { OnboardingCard } from '@/modules/onboarding'
import { PhoneInput } from '@/components/ui/phone-input'

interface AccountSetupProps {
  progress?: {
    completedSteps: string[]
    currentStep: string
    totalSteps: number
  }
  savedData?: {
    firstName?: string
    lastName?: string
    email?: string
    phone?: string
  }
  user?: {
    email?: string
    name?: string
    email_verified?: boolean
  }
  currentStep?: number
  totalSteps?: number
  completedSteps?: string[]
}

export default function AccountSetup({ 
  progress, 
  savedData, 
  user,
  currentStep = 1,
  totalSteps = 4,
  completedSteps = []
}: AccountSetupProps) {
  // Parse existing phone into country code and number
  const parsePhone = (phone: string) => {
    if (phone?.startsWith('+56')) {
      return { countryCode: '+56', number: phone.slice(3).trim() }
    } else if (phone?.startsWith('+')) {
      // Try to match other country codes
      const match = phone.match(/^(\+\d{1,3})(.*)$/)
      if (match) {
        return { countryCode: match[1], number: match[2].trim() }
      }
    }
    return { countryCode: '+56', number: phone || '' } // Default to Chile
  }

  const parsedPhone = parsePhone(savedData?.phone || '')
  
  const { data, setData, post, processing, errors, hasErrors } = useForm({
    firstName: savedData?.firstName || '',
    lastName: savedData?.lastName || '',
    nationalId: savedData?.nationalId || '',
    email: user?.email || savedData?.email || '',
    countryCode: parsedPhone.countryCode,
    phoneNumber: parsedPhone.number,
    phone: savedData?.phone || '', // Keep full phone for submission
    primaryRole: savedData?.primaryRole || 'owner',
  })

  // Update phone field when country code or number changes
  const updatePhone = (countryCode: string, phoneNumber: string) => {
    const fullPhone = phoneNumber ? `${countryCode} ${phoneNumber}` : ''
    setData('phone', fullPhone)
  }

  const handleCountryChange = (value: string) => {
    setData('countryCode', value)
    updatePhone(value, data.phoneNumber)
  }

  const handlePhoneChange = (value: string) => {
    setData('phoneNumber', value)
    updatePhone(data.countryCode, value)
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post('/onboarding/account')
  }

  // Check if this step is completed (based on progress.completedSteps)
  const isStepCompleted = progress?.completedSteps?.includes('account') || false

  // Form validation states - check if all required fields are filled
  const isFormValid = data.firstName.trim() !== '' && 
                      data.lastName.trim() !== '' && 
                      data.email.trim() !== ''

  return (
    <OnboardingLayout
      title="Account Setup - Onboarding"
      currentStep={currentStep}
      totalSteps={totalSteps}
      stepTitle="Account Setup"
      stepDescription="Create your account and set up basic information"
      completedSteps={completedSteps.length}
    >
      <OnboardingCard estimatedTime="2 min">
        <div className="space-y-4">
          {/* Form Section */}
          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Email Display (if exists) */}
            {(user?.email || savedData?.email) && (
              <div className="flex items-center justify-between p-3.5 rounded-lg bg-neutral-50 dark:bg-neutral-900/50 border border-neutral-200 dark:border-neutral-800">
                <div className="flex items-center gap-3">
                  <Mail className="h-4 w-4 text-neutral-600 dark:text-neutral-400" />
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-neutral-900 dark:text-neutral-100">Email Address</span>
                    <span className="text-sm text-neutral-600 dark:text-neutral-400">{user?.email || savedData?.email}</span>
                  </div>
                </div>
                {user?.email_verified ? (
                  <Badge variant="secondary" className="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Verified
                  </Badge>
                ) : (
                  <Badge variant="secondary" className="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800">
                    <XCircle className="h-3 w-3 mr-1" />
                    Not Verified
                  </Badge>
                )}
              </div>
            )}

            {/* Name Fields - Side by side */}
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <Label htmlFor="firstName" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1.5">
                  First Name <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="firstName"
                  value={data.firstName}
                  onChange={(e) => setData('firstName', e.target.value)}
                  placeholder="Julio"
                  className={cn(
                    "h-9 transition-colors",
                    errors.firstName && "border-red-500 focus:ring-red-500"
                  )}
                  required
                />
                {errors.firstName && (
                  <p className="text-xs text-red-600 mt-1">
                    {errors.firstName}
                  </p>
                )}
              </div>
              
              <div>
                <Label htmlFor="lastName" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1.5">
                  Last Name <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="lastName"
                  value={data.lastName}
                  onChange={(e) => setData('lastName', e.target.value)}
                  placeholder="Patricio"
                  className={cn(
                    "h-9 transition-colors",
                    errors.lastName && "border-red-500 focus:ring-red-500"
                  )}
                  required
                />
                {errors.lastName && (
                  <p className="text-xs text-red-600 mt-1">
                    {errors.lastName}
                  </p>
                )}
              </div>
            </div>

            {/* National ID Field */}
            <div>
              <Label htmlFor="nationalId" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1.5">
                Tax ID / National ID <span className="text-neutral-400 text-xs ml-1">(Optional)</span>
              </Label>
              <Input
                id="nationalId"
                value={data.nationalId}
                onChange={(e) => setData('nationalId', e.target.value)}
                placeholder="RUT, passport, or other ID document"
                className={cn(
                  "h-9 transition-colors",
                  errors.nationalId && "border-red-500 focus:ring-red-500"
                )}
              />
              {errors.nationalId && (
                <p className="text-xs text-red-600 mt-1">
                  {errors.nationalId}
                </p>
              )}
            </div>

            {/* Phone Field */}
            <div>
              <Label className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1.5">
                Phone Number <span className="text-neutral-400 text-xs ml-1">(Optional)</span>
              </Label>
              <PhoneInput
                countryCode={data.countryCode}
                phoneNumber={data.phoneNumber}
                onCountryChange={handleCountryChange}
                onPhoneChange={handlePhoneChange}
                error={!!errors.phone}
              />
              {errors.phone && (
                <p className="text-xs text-red-600 mt-1">
                  {errors.phone}
                </p>
              )}
            </div>

            {/* Error Alert */}
            {hasErrors && Object.keys(errors).length > 0 && (
              <Alert className="border-red-200 bg-red-50 dark:bg-red-950/20 py-2.5">
                <AlertCircle className="h-3 w-3 text-red-600" />
                <AlertDescription className="text-xs text-red-800 dark:text-red-200">
                  Please correct the errors above before continuing.
                </AlertDescription>
              </Alert>
            )}

            {isStepCompleted && (
              <Alert className="py-2.5">
                <AlertDescription className="text-xs">
                  You've already completed this step. You can update your information or continue to the next step.
                </AlertDescription>
              </Alert>
            )}

            {/* Additional Info */}
            <div className="pt-3 mt-1 border-t border-neutral-200 dark:border-neutral-800">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                Your personal information is protected and will never be shared without your consent
              </p>
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center mt-1">
                Phone and Tax ID can be added later when needed for billing or legal compliance
              </p>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-between pt-3">
              <Link href="/onboarding">
                <Button type="button" variant="outline" size="sm">
                  <ArrowLeft className="mr-1 h-3 w-3" />
                  Back
                </Button>
              </Link>
              
              <Button 
                type="submit" 
                size="sm"
                disabled={processing || !isFormValid}
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