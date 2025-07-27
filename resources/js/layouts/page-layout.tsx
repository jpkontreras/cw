import { cn } from '@/lib/utils';
import * as React from 'react';

interface PageProps {
  children: React.ReactNode;
  className?: string;
}

interface PageHeaderProps {
  title?: React.ReactNode;
  subtitle?: React.ReactNode;
  actions?: React.ReactNode;
  className?: string;
}

interface PageActionsProps {
  children: React.ReactNode;
  className?: string;
}

interface PageContentProps {
  children: React.ReactNode;
  className?: string;
  noPadding?: boolean;
}

interface PageBottomProps {
  children: React.ReactNode;
  className?: string;
}

type PageLayoutComponent = React.ForwardRefExoticComponent<PageProps & React.RefAttributes<HTMLDivElement>> & {
  Header: React.FC<PageHeaderProps>;
  Actions: React.ForwardRefExoticComponent<PageActionsProps & React.RefAttributes<HTMLDivElement>>;
  Content: React.ForwardRefExoticComponent<PageContentProps & React.RefAttributes<HTMLDivElement>>;
  Bottom: React.FC<PageBottomProps>;
};

const Page = React.forwardRef<HTMLDivElement, PageProps>(({ children, className }, ref) => {
  return (
    <div ref={ref} className={cn('flex h-full flex-col', className)}>
      {children}
    </div>
  );
});

Page.displayName = 'Page';

const PageHeader = ({ title, subtitle, actions, className }: PageHeaderProps) => {
  return (
    <header
      className={cn(
        'border-b bg-background',
        className,
      )}
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between py-4 sm:py-6">
          <div className="min-w-0 flex-1">
            {title && (
              <h1 className="font-semibold tracking-tight text-2xl sm:text-3xl">
                {title}
              </h1>
            )}

            {subtitle && (
              <p className="mt-1 text-muted-foreground">
                {subtitle}
              </p>
            )}
          </div>

          {actions && (
            <div className="ml-4 flex shrink-0 items-center gap-2 sm:ml-6">
              {actions}
            </div>
          )}
        </div>
      </div>
    </header>
  );
};

PageHeader.displayName = 'Page.Header';

const PageActions = React.forwardRef<HTMLDivElement, PageActionsProps>(({ children, className }, ref) => {
  return (
    <div
      ref={ref}
      className={cn('flex items-center gap-2', className)}
    >
      {children}
    </div>
  );
});

PageActions.displayName = 'Page.Actions';

const PageContent = React.forwardRef<HTMLDivElement, PageContentProps>(({ children, className, noPadding = false }, ref) => {
  return (
    <main
      ref={ref}
      className={cn('flex-1 overflow-y-auto overflow-x-hidden', className)}
    >
      <div className={cn(!noPadding && 'container mx-auto px-4 py-6 sm:px-6 lg:px-8')}>{children}</div>
    </main>
  );
});

PageContent.displayName = 'Page.Content';

const PageBottom = ({ children, className }: PageBottomProps) => {
  return (
    <div
      className={cn('border-t bg-white dark:bg-gray-950', 'shadow-[0_-2px_10px_rgba(0,0,0,0.05)]', className)}
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div className="w-full">
          {children}
        </div>
      </div>
    </div>
  );
};

PageBottom.displayName = 'Page.Bottom';

const PageLayout = Page as PageLayoutComponent;

PageLayout.Header = PageHeader;
PageLayout.Actions = PageActions;
PageLayout.Content = PageContent;
PageLayout.Bottom = PageBottom;

export default PageLayout;
