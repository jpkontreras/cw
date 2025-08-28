import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import React from 'react';

interface OnboardingCardProps {
  estimatedTime?: string;
  stepNumber: number;
  totalSteps: number;
  stepTitle: string;
  stepDescription: string;
  onBack?: () => void;
  onNext?: () => void;
  nextDisabled?: boolean;
  nextLoading?: boolean;
  children: React.ReactNode;
}

export default function OnboardingCard({ 
  stepNumber,
  totalSteps,
  stepTitle,
  stepDescription,
  onBack,
  onNext,
  nextDisabled,
  nextLoading,
  children 
}: OnboardingCardProps) {
  return (
    <Card className="border-0 bg-white shadow-lg dark:bg-neutral-900">
      <CardContent className="pt-3 pb-4">
        <div className="space-y-4">
          {/* Header with title and buttons */}
          <div className="pb-3 border-b border-neutral-200 dark:border-neutral-800">
            <div className="flex items-center justify-between">
              <div className="space-y-1">
                <h2 className="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                  {stepTitle}
                </h2>
                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                  {stepDescription}
                </p>
              </div>
              
              {/* Navigation buttons */}
              <div className="flex gap-2">
                {onBack && (
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={onBack}
                >
                  <ArrowLeft className="mr-1 h-3 w-3" />
                  Back
                </Button>
              )}
              
              {onNext && (
                <Button 
                  type="submit" 
                  disabled={nextDisabled} 
                  size="sm"
                  onClick={onNext}
                >
                  {nextLoading ? (
                    <span className="animate-pulse">Saving...</span>
                  ) : (
                    <>
                      Continue Setup
                      <ArrowRight className="ml-1 h-3 w-3" />
                    </>
                  )}
                </Button>
              )}
            </div>
            </div>
          </div>
          
          {/* Form content */}
          {children}
        </div>
      </CardContent>
    </Card>
  );
}
