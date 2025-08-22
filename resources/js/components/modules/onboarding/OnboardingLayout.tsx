import React from 'react'
import { Head } from '@inertiajs/react'
import { Progress } from '@/components/ui/progress'
import { cn } from '@/lib/utils'

interface OnboardingLayoutProps {
  title: string
  currentStep: number
  totalSteps: number
  stepTitle: string
  stepDescription: string
  completedSteps: number
  children: React.ReactNode
}

export default function OnboardingLayout({
  title,
  currentStep,
  totalSteps,
  stepTitle,
  stepDescription,
  completedSteps,
  children,
}: OnboardingLayoutProps) {
  const progressPercentage = (completedSteps / totalSteps) * 100

  return (
    <>
      <Head title={title} />
      
      <div className="min-h-screen bg-gradient-to-b from-neutral-50 to-white dark:from-neutral-950 dark:to-neutral-900 flex flex-col">
        {/* Header with Progress */}
        <div className="border-b border-neutral-200 dark:border-neutral-800 bg-white/50 dark:bg-neutral-900/50 backdrop-blur-sm">
          <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h1 className="text-xl font-medium text-neutral-900 dark:text-neutral-100">Colame Setup</h1>
                <span className="text-sm text-neutral-500 dark:text-neutral-400">
                  {completedSteps} of {totalSteps} completed
                </span>
              </div>
              <Progress value={progressPercentage} className="h-1.5" />
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
          <div className="w-full max-w-2xl">
            <div className="space-y-8">
              {/* Step Counter */}
              <div className="text-center">
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-2">
                  Step {currentStep} of {totalSteps}
                </p>
                <h2 className="text-3xl font-semibold text-neutral-900 dark:text-neutral-100">
                  {stepTitle}
                </h2>
                <p className="text-lg text-neutral-600 dark:text-neutral-400 mt-2">
                  {stepDescription}
                </p>
              </div>

              {/* Content */}
              {children}

              {/* Progress Indicators */}
              <div className="flex justify-center">
                <div className="flex items-center gap-2">
                  {Array.from({ length: totalSteps }).map((_, index) => {
                    const isCompleted = index < completedSteps
                    const isCurrent = index === currentStep - 1
                    return (
                      <div
                        key={index}
                        className={cn(
                          "h-2 rounded-full transition-all",
                          isCompleted ? "w-8 bg-green-500" :
                          isCurrent ? "w-8 bg-primary" :
                          "w-2 bg-neutral-300 dark:bg-neutral-700"
                        )}
                      />
                    )
                  })}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}