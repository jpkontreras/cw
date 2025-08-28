import React from 'react'
import { Head } from '@inertiajs/react'
import { Progress } from '@/components/ui/progress'
import { cn } from '@/lib/utils'
import AppLogoIcon from '@/components/app-logo-icon'

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
  return (
    <>
      <Head title={title} />
      
      <div className="min-h-screen bg-gradient-to-b from-neutral-50 to-white dark:from-neutral-950 dark:to-neutral-900 flex flex-col">
        {/* Header with Progress */}
        <div className="border-b border-neutral-200 dark:border-neutral-800 bg-white/50 dark:bg-neutral-900/50 backdrop-blur-sm">
          <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground">
                  <AppLogoIcon className="size-4 fill-current" />
                </div>
                <h1 className="text-lg font-medium text-neutral-900 dark:text-neutral-100">Business Setup</h1>
              </div>
              
              {/* Progress Indicators in Header */}
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                  <span className="text-xs text-neutral-600 dark:text-neutral-400 font-medium">
                    Step {currentStep} of {totalSteps}
                  </span>
                  <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    • {currentStep - 1} of {totalSteps} completed
                  </span>
                </div>
                <div className="flex items-center gap-1.5">
                  {Array.from({ length: totalSteps }).map((_, index) => {
                    const isCompleted = index < currentStep - 1
                    const isCurrent = index === currentStep - 1
                    return (
                      <div
                        key={index}
                        className={cn(
                          "h-1.5 rounded-full transition-all",
                          isCompleted ? "w-6 bg-green-500" :
                          isCurrent ? "w-6 bg-primary" :
                          "w-1.5 bg-neutral-300 dark:bg-neutral-700"
                        )}
                      />
                    )
                  })}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-6">
          <div className="w-full max-w-2xl">
            <div className="space-y-4">
              {/* Content */}
              {children}
            </div>
          </div>
        </div>
      </div>
    </>
  )
}