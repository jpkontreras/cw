import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, ArrowRight, Settings, CheckCircle } from 'lucide-react'
import OnboardingLayout from '@/components/modules/onboarding/OnboardingLayout'
import OnboardingCard from '@/components/modules/onboarding/OnboardingCard'

interface ConfigurationSetupProps {
  progress?: any
  savedData?: any
  currentStep?: number
  totalSteps?: number
  completedSteps?: string[]
}

export default function ConfigurationSetup({ progress, savedData, currentStep = 4, totalSteps = 4, completedSteps = [] }: ConfigurationSetupProps) {
  const { data, setData, post, processing, errors } = useForm({
    dateFormat: savedData?.dateFormat || 'd/m/Y',
    timeFormat: savedData?.timeFormat || 'H:i',
    language: savedData?.language || 'es',
    currency: savedData?.currency || 'CLP',
    timezone: savedData?.timezone || 'America/Santiago',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post('/onboarding/configuration')
  }

  const requiredFields = [
    { icon: CheckCircle, label: 'Currency', filled: !!data.currency },
    { icon: CheckCircle, label: 'Tax settings', filled: false },
    { icon: CheckCircle, label: 'Payment methods', filled: false },
    { icon: CheckCircle, label: 'Notifications', filled: false },
  ]

  return (
    <OnboardingLayout
      title="Configuration - Onboarding"
      currentStep={currentStep}
      totalSteps={totalSteps}
      stepTitle="Configuration"
      stepDescription="Customize your preferences and settings"
      completedSteps={completedSteps.length}
    >
      <OnboardingCard estimatedTime="5 min">
        <div className="space-y-6">
          {/* Overview Section */}
          <div className="flex gap-8">
            {/* Icon */}
            <div className="shrink-0">
              <div className="p-4 rounded-xl bg-gradient-to-br from-neutral-100 to-neutral-50 dark:from-neutral-800 dark:to-neutral-900">
                <Settings className="h-10 w-10 text-neutral-700 dark:text-neutral-300" />
              </div>
            </div>

            {/* Required Fields Preview */}
            <div className="flex-1 space-y-4">
              <div>
                <p className="text-base font-semibold text-neutral-800 dark:text-neutral-200 mb-1">
                  What we'll need from you:
                </p>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                  Configure your system preferences
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
                    <Label htmlFor="dateFormat">Date Format</Label>
                    <Select
                      value={data.dateFormat}
                      onValueChange={(value) => setData('dateFormat', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select date format" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="d/m/Y">DD/MM/YYYY (31/12/2024)</SelectItem>
                        <SelectItem value="m/d/Y">MM/DD/YYYY (12/31/2024)</SelectItem>
                        <SelectItem value="Y-m-d">YYYY-MM-DD (2024-12-31)</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.dateFormat && (
                      <p className="text-sm text-red-600">{errors.dateFormat}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="timeFormat">Time Format</Label>
                    <Select
                      value={data.timeFormat}
                      onValueChange={(value) => setData('timeFormat', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select time format" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="H:i">24-hour (23:59)</SelectItem>
                        <SelectItem value="h:i A">12-hour (11:59 PM)</SelectItem>
                        <SelectItem value="h:i a">12-hour (11:59 pm)</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.timeFormat && (
                      <p className="text-sm text-red-600">{errors.timeFormat}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="language">Language</Label>
                    <Select
                      value={data.language}
                      onValueChange={(value) => setData('language', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select language" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="es">Español</SelectItem>
                        <SelectItem value="en">English</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.language && (
                      <p className="text-sm text-red-600">{errors.language}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="currency">Currency</Label>
                    <Select
                      value={data.currency}
                      onValueChange={(value) => setData('currency', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select currency" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="CLP">Chilean Peso (CLP)</SelectItem>
                        <SelectItem value="USD">US Dollar (USD)</SelectItem>
                        <SelectItem value="EUR">Euro (EUR)</SelectItem>
                        <SelectItem value="ARS">Argentine Peso (ARS)</SelectItem>
                        <SelectItem value="PEN">Peruvian Sol (PEN)</SelectItem>
                        <SelectItem value="COP">Colombian Peso (COP)</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.currency && (
                      <p className="text-sm text-red-600">{errors.currency}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="timezone">Timezone</Label>
                  <Select
                    value={data.timezone}
                    onValueChange={(value) => setData('timezone', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select timezone" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="America/Santiago">Santiago (GMT-3)</SelectItem>
                      <SelectItem value="America/Argentina/Buenos_Aires">Buenos Aires (GMT-3)</SelectItem>
                      <SelectItem value="America/Lima">Lima (GMT-5)</SelectItem>
                      <SelectItem value="America/Bogota">Bogotá (GMT-5)</SelectItem>
                      <SelectItem value="America/Mexico_City">Mexico City (GMT-6)</SelectItem>
                      <SelectItem value="America/New_York">New York (GMT-5)</SelectItem>
                    </SelectContent>
                  </Select>
                  {errors.timezone && (
                    <p className="text-sm text-red-600">{errors.timezone}</p>
                  )}
                </div>

                {savedData?.dateFormat && (
                  <Alert>
                    <AlertDescription>
                      You've already completed this step. You can update your information or continue to review.
                    </AlertDescription>
                  </Alert>
                )}

            {/* Additional Info */}
            <div className="pt-4 border-t border-neutral-200 dark:border-neutral-800">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                These settings can be changed at any time from your dashboard
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
                Complete Setup
                <ArrowRight className="ml-2 h-4 w-4" />
              </Button>
            </div>
          </form>
        </div>
      </OnboardingCard>
    </OnboardingLayout>
  )
}