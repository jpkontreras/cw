import { useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, ArrowRight, Settings, CheckCircle } from 'lucide-react'
import OnboardingLayout from '@/layouts/onboarding-layout'
import { OnboardingCard } from '@/modules/onboarding'

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
        <div className="space-y-3">
          {/* Form Section */}
          <form onSubmit={handleSubmit} className="space-y-3">
                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <Label htmlFor="dateFormat" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Date Format</Label>
                    <Select
                      value={data.dateFormat}
                      onValueChange={(value) => setData('dateFormat', value)}
                    >
                      <SelectTrigger className="h-9">
                        <SelectValue placeholder="Select date format" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="d/m/Y">DD/MM/YYYY (31/12/2024)</SelectItem>
                        <SelectItem value="m/d/Y">MM/DD/YYYY (12/31/2024)</SelectItem>
                        <SelectItem value="Y-m-d">YYYY-MM-DD (2024-12-31)</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.dateFormat && (
                      <p className="text-xs text-red-600 mt-1">{errors.dateFormat}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="timeFormat" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Time Format</Label>
                    <Select
                      value={data.timeFormat}
                      onValueChange={(value) => setData('timeFormat', value)}
                    >
                      <SelectTrigger className="h-9">
                        <SelectValue placeholder="Select time format" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="H:i">24-hour (23:59)</SelectItem>
                        <SelectItem value="h:i A">12-hour (11:59 PM)</SelectItem>
                        <SelectItem value="h:i a">12-hour (11:59 pm)</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.timeFormat && (
                      <p className="text-xs text-red-600 mt-1">{errors.timeFormat}</p>
                    )}
                  </div>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                  <div>
                    <Label htmlFor="language" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Language</Label>
                    <Select
                      value={data.language}
                      onValueChange={(value) => setData('language', value)}
                    >
                      <SelectTrigger className="h-9">
                        <SelectValue placeholder="Select language" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="es">Español</SelectItem>
                        <SelectItem value="en">English</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.language && (
                      <p className="text-xs text-red-600 mt-1">{errors.language}</p>
                    )}
                  </div>

                  <div>
                    <Label htmlFor="currency" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Currency</Label>
                    <Select
                      value={data.currency}
                      onValueChange={(value) => setData('currency', value)}
                    >
                      <SelectTrigger className="h-9">
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
                      <p className="text-xs text-red-600 mt-1">{errors.currency}</p>
                    )}
                  </div>
                </div>

                <div>
                  <Label htmlFor="timezone" className="text-xs font-medium text-neutral-600 dark:text-neutral-400 mb-1">Timezone</Label>
                  <Select
                    value={data.timezone}
                    onValueChange={(value) => setData('timezone', value)}
                  >
                    <SelectTrigger className="h-9">
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
                    <p className="text-xs text-red-600 mt-1">{errors.timezone}</p>
                  )}
                </div>

                {savedData?.dateFormat && (
                  <Alert className="py-2">
                    <AlertDescription className="text-xs">
                      You've already completed this step. You can update your information or continue to review.
                    </AlertDescription>
                  </Alert>
                )}

            {/* Additional Info */}
            <div className="pt-2 border-t border-neutral-200 dark:border-neutral-800">
              <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center">
                These settings can be changed at any time from your dashboard
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
              
              <Button type="submit" disabled={processing} size="sm">
                Complete Setup
                <ArrowRight className="ml-1 h-3 w-3" />
              </Button>
            </div>
          </form>
        </div>
      </OnboardingCard>
    </OnboardingLayout>
  )
}