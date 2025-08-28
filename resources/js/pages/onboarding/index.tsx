import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { 
  ArrowRight, 
  Building2, 
  CheckCircle, 
  Clock, 
  MapPin, 
  Settings, 
  User,
  Check
} from 'lucide-react';
import OnboardingLayout from '@/layouts/onboarding-layout';

interface OnboardingProgressData {
  id?: number;
  userId: number;
  step: string;
  completedSteps: string[];
  isCompleted: boolean;
  getProgressPercentage?: () => number;
}

interface OnboardingIndexProps {
  progress?: OnboardingProgressData;
  nextStep?: string;
  availableSteps: string[];
}

const stepDetails = {
  account: {
    title: 'Account Setup',
    description: 'Create your account and set up basic information',
    icon: User,
    estimatedTime: '2 min',
    requiredFields: ['Full name', 'Email', 'Password', 'Phone number'],
  },
  business: {
    title: 'Business Information',
    description: 'Tell us about your restaurant business',
    icon: Building2,
    estimatedTime: '5 min',
    requiredFields: ['Business name', 'Business type', 'Tax ID', 'Operating hours'],
  },
  location: {
    title: 'Location Details',
    description: 'Set up your primary restaurant location',
    icon: MapPin,
    estimatedTime: '3 min',
    requiredFields: ['Address', 'Contact info', 'Service areas', 'Capacity'],
  },
  configuration: {
    title: 'Configuration',
    description: 'Customize your preferences and settings',
    icon: Settings,
    estimatedTime: '5 min',
    requiredFields: ['Currency', 'Tax settings', 'Payment methods', 'Notifications'],
  },
};

export default function OnboardingIndex({ progress, nextStep, availableSteps }: OnboardingIndexProps) {
  const completedCount = progress?.completedSteps.length || 0;
  const currentStep = nextStep || availableSteps[0];
  const currentStepIndex = availableSteps.indexOf(currentStep);
  const currentStepDetails = stepDetails[currentStep as keyof typeof stepDetails];

  // If onboarding is completed
  if (progress?.isCompleted) {
    return (
      <OnboardingLayout
        title="Setup Complete"
        currentStep={availableSteps.length}
        totalSteps={availableSteps.length}
        stepTitle="All Done!"
        stepDescription="Your restaurant management system is ready"
        completedSteps={availableSteps.length}
      >
        <div className="space-y-6">
          <div className="text-center">
            <div className="mb-6 inline-flex rounded-full bg-green-100 p-4 dark:bg-green-900/30">
              <CheckCircle className="h-16 w-16 text-green-600 dark:text-green-400" />
            </div>
            <h2 className="text-3xl font-semibold text-neutral-900 dark:text-neutral-100 mb-2">
              Congratulations!
            </h2>
            <p className="text-lg text-neutral-600 dark:text-neutral-400">
              Your Colame setup is complete. You're ready to start managing your restaurant.
            </p>
          </div>

          <Card className="border-0 bg-white shadow-lg dark:bg-neutral-900">
            <CardContent className="p-6">
              <div className="space-y-3">
                <Link href="/dashboard" className="block">
                  <Button size="lg" className="w-full">
                    Go to Dashboard
                    <ArrowRight className="h-4 w-4" />
                  </Button>
                </Link>
                <Link href="/onboarding/review" className="block">
                  <Button size="lg" variant="outline" className="w-full">
                    Review Configuration
                  </Button>
                </Link>
              </div>
            </CardContent>
          </Card>
        </div>
      </OnboardingLayout>
    );
  }

  // If current step details not found
  if (!currentStepDetails) {
    return (
      <OnboardingLayout
        title="Onboarding"
        currentStep={0}
        totalSteps={availableSteps.length}
        stepTitle="Getting Started"
        stepDescription="Let's set up your restaurant"
        completedSteps={0}
      >
        <div className="text-center py-8">
          <p className="text-neutral-600 dark:text-neutral-400">
            Unable to load onboarding steps. Please refresh the page.
          </p>
        </div>
      </OnboardingLayout>
    );
  }
  
  const CurrentIcon = currentStepDetails.icon;

  // Show onboarding journey overview (in progress)
  return (
    <OnboardingLayout
      title="Welcome - Getting Started"
      currentStep={currentStepIndex + 1}
      totalSteps={availableSteps.length}
      stepTitle="Onboarding Overview"
      stepDescription="Complete all steps to set up your restaurant"
      completedSteps={completedCount}
    >
      {/* Steps Overview */}
      <div className="grid gap-6 lg:grid-cols-2 lg:items-stretch">
        {/* Steps List */}
        <div>
          <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-4">
            Setup Steps
          </h2>
          <div className="space-y-3">
            {availableSteps.map((step) => {
              const details = stepDetails[step as keyof typeof stepDetails];
              const isCompleted = progress?.completedSteps.includes(step);
              const isCurrent = step === currentStep;

              if (!details) return null;

              const Icon = details.icon;

              return (
                <div
                  key={step}
                  className={cn(
                    "flex items-center gap-4 p-4 rounded-lg border transition-all",
                    isCompleted && "border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30",
                    isCurrent && "border-primary bg-primary/5 shadow-sm",
                    !isCompleted && !isCurrent && "border-neutral-200 dark:border-neutral-800"
                  )}
                >
                  {/* Step Icon/Number */}
                  <div className={cn(
                    "flex h-10 w-10 shrink-0 items-center justify-center rounded-full",
                    isCompleted && "bg-green-600 text-white",
                    isCurrent && "bg-primary text-primary-foreground",
                    !isCompleted && !isCurrent && "bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400"
                  )}>
                    {isCompleted ? (
                      <Check className="h-5 w-5" />
                    ) : (
                      <Icon className="h-5 w-5" />
                    )}
                  </div>

                  {/* Step Details */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <h3 className={cn(
                        "font-medium",
                        isCurrent && "text-primary",
                        isCompleted && "text-green-700 dark:text-green-400"
                      )}>
                        {details.title}
                      </h3>
                      {isCurrent && (
                        <Badge className="text-xs overflow-hidden">Current</Badge>
                      )}
                    </div>
                    <p className="text-sm text-neutral-600 dark:text-neutral-400 mt-0.5">
                      {details.description}
                    </p>
                  </div>

                  {/* Time & Actions */}
                  <div className="flex items-center gap-3">
                    <span className="text-xs text-neutral-500 dark:text-neutral-400">
                      {details.estimatedTime}
                    </span>
                    {isCompleted && (
                      <Link href={`/onboarding/${step}`}>
                        <Button variant="ghost" size="sm" className="text-xs">
                          Edit
                        </Button>
                      </Link>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Current Step Details */}
        <div className="flex">
          <Card className="border-2 border-primary/50 flex-1 flex flex-col">
            <CardHeader>
              <div className="flex items-start gap-4">
                <div className="rounded-lg bg-primary/10 p-2">
                  <CurrentIcon className="h-6 w-6 text-primary" />
                </div>
                <div className="flex-1">
                  <CardTitle className="text-xl">
                    {currentStepDetails.title}
                  </CardTitle>
                  <CardDescription className="mt-1">
                    {currentStepDetails.description}
                  </CardDescription>
                </div>
                <Badge variant="outline" className="overflow-hidden">
                  Step {currentStepIndex + 1}
                </Badge>
              </div>
            </CardHeader>
            
            <CardContent className="space-y-6 flex-1 flex flex-col">
              <div className="flex-1 space-y-6">
                {/* Quick Info */}
                <div className="flex items-center gap-4 text-sm">
                  <div className="flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
                    <Clock className="h-4 w-4" />
                    <span>About {currentStepDetails.estimatedTime}</span>
                  </div>
                  <div className="flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
                    <CheckCircle className="h-4 w-4" />
                    <span>{currentStepDetails.requiredFields.length} required fields</span>
                  </div>
                </div>

                {/* Required Fields */}
                <div>
                  <h4 className="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-3">
                    Information we'll need:
                  </h4>
                  <div className="space-y-2">
                    {currentStepDetails.requiredFields.map((field) => (
                      <div 
                        key={field} 
                        className="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400"
                      >
                        <div className="h-1.5 w-1.5 rounded-full bg-primary/60" />
                        <span>{field}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* CTA */}
              <div className="mt-auto pt-6">
                <Link href={`/onboarding/${currentStep}`} className="block">
                  <Button size="lg" className="w-full">
                    {completedCount === 0 ? "Get Started" : "Continue Setup"}
                    <ArrowRight className="h-4 w-4 ml-2" />
                  </Button>
                </Link>
                <p className="text-xs text-center text-neutral-500 dark:text-neutral-400 mt-3">
                  You can always come back and edit any step later
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </OnboardingLayout>
  );
}