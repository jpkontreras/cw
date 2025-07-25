import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import * as React from 'react';

interface PageHeaderProps {
  title?: string;
  description?: string;
  children?: React.ReactNode;
  className?: string;
  showBackButton?: boolean;
  backHref?: string;
  onBack?: () => void;
}

interface PageContentProps {
  children: React.ReactNode;
  className?: string;
  noPadding?: boolean;
}

interface PageLayoutProps {
  children: React.ReactNode;
  className?: string;
}

export function PageHeader({ title, description, children, className, showBackButton = false, backHref, onBack }: PageHeaderProps) {
  const handleBack = () => {
    if (onBack) {
      onBack();
    } else if (backHref) {
      router.visit(backHref);
    } else {
      window.history.back();
    }
  };

  return (
    <div className={cn('border-b bg-background', className)}>
      <div className="px-4 py-4 sm:px-6 lg:px-8">
        {/* Header content */}
        <div className="flex items-center justify-between">
          <div className="flex min-w-0 flex-1 items-center gap-3">
            {showBackButton && (
              <Button variant="ghost" size="icon" className="h-9 w-9 rounded-full hover:bg-gray-100" onClick={handleBack}>
                <ArrowLeft className="h-5 w-5" />
                <span className="sr-only">Go back</span>
              </Button>
            )}
            <div>
              <h1 className="text-2xl font-semibold text-gray-900 sm:text-3xl">{title}</h1>
              {description && <p className="mt-1 text-sm text-gray-500 sm:text-base">{description}</p>}
            </div>
          </div>
          {children && <div className="mt-4 flex items-center gap-2 sm:mt-0 sm:ml-6 sm:flex-shrink-0">{children}</div>}
        </div>
      </div>
    </div>
  );
}

export function PageContent({ children, className, noPadding = false }: PageContentProps) {
  return <div className={cn('flex-1 overflow-hidden', !noPadding && 'px-4 py-6 sm:px-4 lg:px-4', className)}>{children}</div>;
}

export function PageLayout({ children, className }: PageLayoutProps) {
  return <div className={cn('flex min-h-full flex-col', className)}>{children}</div>;
}
