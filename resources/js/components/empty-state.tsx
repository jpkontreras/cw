import { LucideIcon } from 'lucide-react';
import { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface EmptyStateProps {
  icon: LucideIcon;
  title: string;
  description: string;
  actions?: ReactNode;
  helpText?: ReactNode;
  className?: string;
}

export function EmptyState({
  icon: Icon,
  title,
  description,
  actions,
  helpText,
  className,
}: EmptyStateProps) {
  return (
    <div className={cn("flex items-center justify-center min-h-[60vh]", className)}>
      <div className="text-center max-w-2xl mx-auto">
        {/* Decorative element */}
        <div className="relative mb-8">
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="h-32 w-32 bg-gradient-to-br from-gray-50 to-gray-100 rounded-full"></div>
          </div>
          <div className="relative flex items-center justify-center">
            <div className="h-24 w-24 bg-white rounded-2xl shadow-lg flex items-center justify-center transform rotate-3">
              <Icon className="h-12 w-12 text-gray-400" />
            </div>
          </div>
        </div>
        
        {/* Content */}
        <h3 className="text-2xl font-semibold text-gray-900 mb-3">{title}</h3>
        <p className="text-base text-gray-600 max-w-md mx-auto mb-8 leading-relaxed">
          {description}
        </p>
        
        {/* Action buttons */}
        {actions && (
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            {actions}
          </div>
        )}
        
        {/* Help text */}
        {helpText && (
          <div className="text-sm text-gray-500 mt-8">
            {helpText}
          </div>
        )}
      </div>
    </div>
  );
}