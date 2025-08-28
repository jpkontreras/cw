import { useForm, Link } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, CheckCircle, Edit2, User, Building2, MapPin, Settings } from 'lucide-react'
import { cn } from '@/lib/utils'
import OnboardingLayout from '@/layouts/onboarding-layout'
import { OnboardingCard } from '@/modules/onboarding'

interface ReviewProps {
  progress: any
  data: {
    account?: any
    business?: any
    location?: any
    configuration?: any
  }
  currentStep?: number
  totalSteps?: number
  completedSteps?: string[]
}

export default function Review({ progress, data, currentStep = 4, totalSteps = 4, completedSteps = [] }: ReviewProps) {
  const { post, processing } = useForm({})

  const handleComplete = () => {
    post('/onboarding/complete')
  }

  const sections = [
    {
      id: 'account',
      title: 'Account Information',
      icon: User,
      editUrl: '/onboarding/account',
      data: data.account,
      items: [
        { 
          primary: data.account ? `${data.account.firstName} ${data.account.lastName}` : 'Not provided',
          secondary: data.account?.email 
        },
        { 
          primary: data.account?.phone || 'No phone',
          secondary: data.account?.nationalId ? `RUT: ${data.account.nationalId}` : null
        },
      ]
    },
    {
      id: 'business',
      title: 'Business Information',
      icon: Building2,
      editUrl: '/onboarding/business',
      data: data.business,
      items: [
        { 
          primary: data.business?.businessName || 'Not provided',
          secondary: data.business?.businessType?.replace('_', ' ') || null,
          capitalize: true
        },
        { 
          primary: data.business?.legalName && data.business?.legalName !== data.business?.businessName 
            ? `Legal: ${data.business.legalName}` 
            : null,
          secondary: data.business?.taxId ? `Tax ID: ${data.business.taxId}` : null
        },
      ]
    },
    {
      id: 'location',
      title: 'Location Information',
      icon: MapPin,
      editUrl: '/onboarding/location',
      data: data.location,
      items: [
        { 
          primary: data.location?.name || 'Not provided',
          secondary: data.location?.address || 'No fixed address'
        },
        { 
          primary: data.location?.phone || 'No phone',
          secondary: data.location?.capabilities?.map((c: string) => {
            const formatted = c.replace('_', ' ')
            return formatted.charAt(0).toUpperCase() + formatted.slice(1)
          }).join(' • ') || 'No services'
        },
      ]
    },
    {
      id: 'configuration',
      title: 'System Configuration',
      icon: Settings,
      editUrl: '/onboarding/configuration',
      data: data.configuration,
      items: [
        { 
          primary: data.configuration?.language === 'es' ? 'Español' : 'English',
          secondary: `${data.configuration?.currency || 'CLP'} • ${data.configuration?.timezone || 'America/Santiago'}`
        },
        { 
          primary: `${data.configuration?.dateFormat || 'DD/MM/YYYY'} • ${data.configuration?.timeFormat || '24h'}`,
          secondary: 'Date & Time formats'
        },
      ]
    }
  ]

  return (
    <OnboardingLayout
      title="Review - Onboarding"
      currentStep={currentStep}
      totalSteps={totalSteps}
      stepTitle="Review Your Information"
      stepDescription="Please review all your information before completing the setup"
      completedSteps={completedSteps.length}
    >
      <OnboardingCard estimatedTime="1 min">
        <div className="space-y-4">
          {/* Section Cards */}
          <div className="space-y-3">
            {sections.map((section) => {
              const Icon = section.icon
              const hasData = section.data && Object.keys(section.data).length > 0
              
              return (
                <div 
                  key={section.id}
                  className="border border-neutral-200 dark:border-neutral-800 rounded-lg p-4 bg-white dark:bg-neutral-900/50"
                >
                  {/* Section Header */}
                  <div className="flex items-center justify-between mb-3">
                    <div className="flex items-center gap-3">
                      <div className="p-2 rounded-lg bg-neutral-100 dark:bg-neutral-800">
                        <Icon className="w-4 h-4 text-neutral-700 dark:text-neutral-300" />
                      </div>
                      <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                        {section.title}
                      </h3>
                    </div>
                    <Link href={section.editUrl}>
                      <Button variant="ghost" size="sm" className="h-7 px-2">
                        <Edit2 className="w-3.5 h-3.5 mr-1" />
                        <span className="text-xs">Edit</span>
                      </Button>
                    </Link>
                  </div>

                  {/* Section Content - New Layout */}
                  {hasData ? (
                    <div className="ml-11 space-y-2">
                      {section.items.map((item, idx) => {
                        if (!item.primary) return null
                        
                        return (
                          <div key={idx} className="space-y-0.5">
                            <div className={cn(
                              "text-sm font-medium text-neutral-900 dark:text-neutral-100",
                              item.capitalize && "capitalize"
                            )}>
                              {item.primary}
                            </div>
                            {item.secondary && (
                              <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                {item.secondary}
                              </div>
                            )}
                          </div>
                        )
                      })}
                    </div>
                  ) : (
                    <div className="ml-11">
                      <Badge variant="secondary" className="text-xs">
                        Not configured
                      </Badge>
                    </div>
                  )}
                </div>
              )
            })}
          </div>

          {/* Terms Notice */}
          <Alert className="bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900 py-3">
            <AlertDescription className="text-sm text-blue-800 dark:text-blue-200">
              By completing the setup, you confirm that all the information provided is accurate and you agree to our terms of service.
            </AlertDescription>
          </Alert>

          {/* Action Buttons */}
          <div className="flex justify-between pt-2">
            <Link href="/onboarding/configuration">
              <Button variant="outline" size="sm">
                <ArrowLeft className="mr-1 h-3 w-3" />
                Back
              </Button>
            </Link>
            
            <Button 
              size="sm"
              onClick={handleComplete}
              disabled={processing}
              className="bg-green-600 hover:bg-green-700 text-white"
            >
              {processing ? (
                <span className="animate-pulse">Completing...</span>
              ) : (
                <>
                  <CheckCircle className="mr-1 h-3 w-3" />
                  Complete Setup
                </>
              )}
            </Button>
          </div>
        </div>
      </OnboardingCard>
    </OnboardingLayout>
  )
}