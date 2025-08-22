import React from 'react'
import { useForm, Link } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, ArrowRight, User, Mail, Phone, CheckCircle, AlertCircle } from 'lucide-react'
import { cn } from '@/lib/utils'
import OnboardingLayout from '@/components/modules/onboarding/OnboardingLayout'
import OnboardingCard from '@/components/modules/onboarding/OnboardingCard'

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
  const { data, setData, post, processing, errors, hasErrors } = useForm({
    firstName: savedData?.firstName || '',
    lastName: savedData?.lastName || '',
    phone: savedData?.phone || '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post('/onboarding/account')
  }

  // Form validation states
  const isFormValid = data.firstName && data.lastName && data.phone

  const requiredFields = [
    { icon: CheckCircle, label: 'Full name', filled: !!(data.firstName && data.lastName) },
    { icon: CheckCircle, label: 'Email', filled: !!(user?.email || savedData?.email) },
    { icon: CheckCircle, label: 'Phone number', filled: !!data.phone },
    { icon: CheckCircle, label: 'Password', filled: true }, // Assuming set during registration
  ]

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
        <div className="space-y-6">
          {/* Overview Section */}
          <div className="flex gap-8">
            {/* Icon */}
            <div className="shrink-0">
              <div className="p-4 rounded-xl bg-gradient-to-br from-neutral-100 to-neutral-50 dark:from-neutral-800 dark:to-neutral-900">
                <User className="h-10 w-10 text-neutral-700 dark:text-neutral-300" />
              </div>
            </div>

            {/* Required Fields Preview */}
            <div className="flex-1 space-y-4">
              <div>
                <p className="text-base font-semibold text-neutral-800 dark:text-neutral-200 mb-1">
                  What we'll need from you:
                </p>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                  Complete your personal information to get started
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
            {/* Email Display (if exists) */}
            {(user?.email || savedData?.email) && (
              <div className="flex items-center justify-between p-4 rounded-lg bg-neutral-50 dark:bg-neutral-900/50 border border-neutral-200 dark:border-neutral-800">
                <div className="flex items-center gap-3">
                  <Mail className="h-5 w-5 text-neutral-600 dark:text-neutral-400" />
                  <div>
                    <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">Email Address</p>
                    <p className="text-sm text-neutral-600 dark:text-neutral-400">{user?.email || savedData?.email}</p>
                  </div>
                </div>
                <Badge variant="secondary" className="text-xs">
                  <CheckCircle className="h-3 w-3" />
                  Verified
                </Badge>
              </div>
            )}

            {/* Name Fields */}
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="firstName" className="text-sm font-medium">
                  First Name <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="firstName"
                  value={data.firstName}
                  onChange={(e) => setData('firstName', e.target.value)}
                  placeholder="Enter your first name"
                  className={cn(
                    "transition-colors",
                    errors.firstName && "border-red-500 focus:ring-red-500"
                  )}
                  required
                />
                {errors.firstName && (
                  <p className="text-sm text-red-600 flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" />
                    {errors.firstName}
                  </p>
                )}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="lastName" className="text-sm font-medium">
                  Last Name <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="lastName"
                  value={data.lastName}
                  onChange={(e) => setData('lastName', e.target.value)}
                  placeholder="Enter your last name"
                  className={cn(
                    "transition-colors",
                    errors.lastName && "border-red-500 focus:ring-red-500"
                  )}
                  required
                />
                {errors.lastName && (
                  <p className="text-sm text-red-600 flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" />
                    {errors.lastName}
                  </p>
                )}
              </div>
            </div>

            {/* Phone Field */}
            <div className="space-y-2">
              <Label htmlFor="phone" className="text-sm font-medium">
                Phone Number <span className="text-red-500">*</span>
              </Label>
              <div className="relative">
                <Phone className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-500" />
                <Input
                  id="phone"
                  type="tel"
                  value={data.phone}
                  onChange={(e) => setData('phone', e.target.value)}
                  placeholder="+56 9 1234 5678"
                  className={cn(
                    "pl-10 transition-colors",
                    errors.phone && "border-red-500 focus:ring-red-500"
                  )}
                  required
                />
              </div>
              {errors.phone && (
                <p className="text-sm text-red-600 flex items-center gap-1">
                  <AlertCircle className="h-3 w-3" />
                  {errors.phone}
                </p>
              )}
              <p className="text-xs text-neutral-500 dark:text-neutral-400">
                Include country code for international numbers
              </p>
            </div>

            {/* Error Alert */}
            {hasErrors && Object.keys(errors).length > 0 && (
              <Alert className="border-red-200 bg-red-50 dark:bg-red-950/20">
                <AlertCircle className="h-4 w-4 text-red-600" />
                <AlertDescription className="text-red-800 dark:text-red-200">
                  Please correct the errors above before continuing.
                </AlertDescription>
              </Alert>
            )}

            {savedData?.firstName && (
              <Alert>
                <AlertDescription>
                  You've already completed this step. You can update your information or continue to the next step.
                </AlertDescription>
              </Alert>
            )}

            {/* Additional Info */}
            <div className="pt-4 border-t border-neutral-200 dark:border-neutral-800">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                Your personal information is protected and will never be shared without your consent
              </p>
            </div>

            {/* Action Buttons */}
            <div className="flex justify-between pt-4">
              <Link href="/onboarding">
                <Button type="button" variant="outline">
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Back
                </Button>
              </Link>
              
              <Button 
                type="submit" 
                size="lg"
                disabled={processing || !isFormValid}
              >
                {processing ? (
                  <span className="animate-pulse">Saving...</span>
                ) : (
                  <>
                    Continue Setup
                    <ArrowRight className="ml-2 h-4 w-4" />
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