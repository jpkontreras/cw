import { Card, CardContent } from '@/components/ui/card';
import React from 'react';

interface OnboardingCardProps {
  estimatedTime?: string;
  children: React.ReactNode;
}

export default function OnboardingCard({ children }: OnboardingCardProps) {
  return (
    <Card className="border-0 bg-white shadow-lg dark:bg-neutral-900">
      <CardContent className="py-4">{children}</CardContent>
    </Card>
  );
}
