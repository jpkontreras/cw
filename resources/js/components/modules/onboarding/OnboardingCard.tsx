import React from 'react'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Clock } from 'lucide-react'

interface OnboardingCardProps {
  estimatedTime?: string
  children: React.ReactNode
}

export default function OnboardingCard({ estimatedTime, children }: OnboardingCardProps) {
  return (
    <Card className="shadow-lg border-0 bg-white dark:bg-neutral-900 relative">
      {estimatedTime && (
        <div className="absolute top-4 right-4">
          <Badge variant="secondary" className="text-sm">
            <Clock className="h-3 w-3" />
            {estimatedTime}
          </Badge>
        </div>
      )}
      
      <CardContent className="p-8">
        {children}
      </CardContent>
    </Card>
  )
}