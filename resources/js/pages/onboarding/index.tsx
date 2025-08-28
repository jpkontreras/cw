import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Building2, CheckCircle, Clock, MapPin, Settings, User } from 'lucide-react';
import React from 'react';

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
  const progressPercentage = progress ? (progress.completedSteps.length / availableSteps.length) * 100 : 0;
  const completedCount = progress?.completedSteps.length || 0;
  const currentStep = nextStep || availableSteps[0];
  const currentStepDetails = stepDetails[currentStep as keyof typeof stepDetails];
  const currentStepIndex = availableSteps.indexOf(currentStep) + 1;

  return (
    <>
      <Head title="Welcome - Onboarding" />

      <div className="flex min-h-screen flex-col bg-gradient-to-b from-neutral-50 to-white dark:from-neutral-950 dark:to-neutral-900">
        {/* Minimal Header */}
        <div className="border-b border-neutral-200 bg-white/50 backdrop-blur-sm dark:border-neutral-800 dark:bg-neutral-900/50">
          <div className="mx-auto max-w-3xl px-4 py-3 sm:px-6 lg:px-8">
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <h1 className="text-xl font-medium text-neutral-900 dark:text-neutral-100">Colame Setup</h1>
                <span className="text-sm text-neutral-500 dark:text-neutral-400">
                  {completedCount} of {availableSteps.length} completed
                </span>
              </div>
              <Progress value={progressPercentage} className="h-1.5" />
            </div>
          </div>
        </div>

        {/* Main Content - Centered and Focused */}
        <div className="flex flex-1 items-center justify-center px-4 py-6 sm:px-6 lg:px-8">
          <div className="w-full max-w-2xl">
            {/* Current Action Card - Single Focus */}
            {!progress?.isCompleted && currentStepDetails && (
              <div className="space-y-4">
                {/* Step Counter */}
                <div className="text-center">
                  <p className="mb-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Step {currentStepIndex} of {availableSteps.length}
                  </p>
                  <h2 className="text-3xl font-semibold text-neutral-900 dark:text-neutral-100">{currentStepDetails.title}</h2>
                  <p className="mt-1 text-lg text-neutral-600 dark:text-neutral-400">{currentStepDetails.description}</p>
                </div>

                {/* Action Card */}
                <Card className="relative border-0 bg-white shadow-lg dark:bg-neutral-900">
                  {/* Time Badge at top */}
                  <div className="absolute top-4 right-4">
                    <Badge variant="secondary" className="text-sm">
                      <Clock className="h-3 w-3" />
                      {currentStepDetails.estimatedTime}
                    </Badge>
                  </div>

                  <CardContent className="p-6">
                    <div className="space-y-4">
                      {/* Icon and Required Fields Section */}
                      <div className="flex gap-6">
                        {/* Icon */}
                        <div className="shrink-0">
                          <div className="rounded-xl bg-gradient-to-br from-neutral-100 to-neutral-50 p-3 dark:from-neutral-800 dark:to-neutral-900">
                            {React.createElement(currentStepDetails.icon, { className: 'h-8 w-8 text-neutral-700 dark:text-neutral-300' })}
                          </div>
                        </div>

                        {/* Required Fields */}
                        <div className="flex-1 space-y-3">
                          <div>
                            <p className="text-base font-semibold text-neutral-800 dark:text-neutral-200">What we'll need from you:</p>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                              Have this information ready to complete this step quickly
                            </p>
                          </div>
                          <div className="grid grid-cols-2 gap-2">
                            {currentStepDetails.requiredFields.map((field) => (
                              <div key={field} className="flex items-center gap-2 rounded-lg bg-neutral-50 p-2 dark:bg-neutral-800/50">
                                <CheckCircle className="h-4 w-4 shrink-0 text-neutral-400" />
                                <span className="text-sm text-neutral-700 dark:text-neutral-300">{field}</span>
                              </div>
                            ))}
                          </div>
                        </div>
                      </div>

                      {/* Additional Info */}
                      <div className="border-t border-neutral-200 pt-3 dark:border-neutral-800">
                        <p className="text-center text-xs text-neutral-500 dark:text-neutral-400">
                          This information will be saved securely and you can update it anytime
                        </p>
                      </div>

                      {/* CTA Button */}
                      <Link href={`/onboarding/${currentStep}`} className="block">
                        <Button size="lg" className="w-full">
                          Continue
                          <ArrowRight className="h-4 w-4" />
                        </Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>

                {/* Progress Indicator */}
                <div className="flex justify-center pt-2">
                  <div className="flex items-center gap-2">
                    {availableSteps.map((step) => {
                      const isCompleted = progress?.completedSteps.includes(step);
                      const isCurrent = step === currentStep;
                      return (
                        <div
                          key={step}
                          className={cn(
                            'h-2 rounded-full transition-all',
                            isCompleted ? 'w-8 bg-green-500' : isCurrent ? 'w-8 bg-primary' : 'w-2 bg-neutral-300 dark:bg-neutral-700',
                          )}
                        />
                      );
                    })}
                  </div>
                </div>
              </div>
            )}

            {/* Completion State */}
            {progress?.isCompleted && (
              <div className="space-y-8">
                <div className="text-center">
                  <div className="mb-4 inline-flex rounded-full bg-green-100 p-4 dark:bg-green-900/30">
                    <CheckCircle className="h-12 w-12 text-green-600 dark:text-green-400" />
                  </div>
                  <h2 className="text-3xl font-semibold text-neutral-900 dark:text-neutral-100">Setup Complete!</h2>
                  <p className="mt-2 text-lg text-neutral-600 dark:text-neutral-400">Your restaurant management system is ready to use</p>
                </div>

                <Card className="border-0 bg-white shadow-xl dark:bg-neutral-900">
                  <CardContent className="p-8">
                    <div className="space-y-4">
                      <Link href="/dashboard" className="block">
                        <Button size="lg" className="w-full text-base">
                          Go to Dashboard
                          <ArrowRight className="h-5 w-5" />
                        </Button>
                      </Link>
                      <Link href="/onboarding/review" className="block">
                        <Button size="lg" variant="outline" className="w-full text-base">
                          Review Configuration
                        </Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
