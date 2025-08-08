import { cn } from '@/lib/utils';
import * as React from 'react';
import { Button } from '@/components/ui/button';
import { PanelLeftClose, PanelLeftOpen, PanelRightClose, PanelRightOpen } from 'lucide-react';
import {
  ResizablePanelGroup,
  ResizablePanel,
  ResizableHandle,
} from '@/components/ui/resizable';

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

interface PageSplitContentProps {
  children: React.ReactNode;
  sidebar: {
    position?: 'left' | 'right';
    defaultSize?: number; // Default size percentage (e.g., 30 for 30%)
    minSize?: number; // Minimum size percentage
    maxSize?: number; // Maximum size percentage
    collapsed: boolean;
    onToggle: (collapsed: boolean) => void;
    title?: string;
    renderExpanded: () => React.ReactNode;
    renderCollapsed: () => React.ReactNode;
    showToggle?: boolean;
    resizable?: boolean; // Enable/disable resizing
  };
  className?: string;
  mainClassName?: string;
  sidebarClassName?: string;
}

type PageLayoutComponent = React.ForwardRefExoticComponent<PageProps & React.RefAttributes<HTMLDivElement>> & {
  Header: React.FC<PageHeaderProps>;
  Actions: React.ForwardRefExoticComponent<PageActionsProps & React.RefAttributes<HTMLDivElement>>;
  Content: React.ForwardRefExoticComponent<PageContentProps & React.RefAttributes<HTMLDivElement>>;
  Bottom: React.FC<PageBottomProps>;
  SplitContent: React.FC<PageSplitContentProps>;
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
    <header className={cn('border-b bg-background', className)}>
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between py-3 sm:py-4">
          <div className="min-w-0 flex-1">
            {title && <h1 className="text-xl font-semibold tracking-tight sm:text-2xl">{title}</h1>}

            {subtitle && <p className="mt-0.5 text-sm text-muted-foreground">{subtitle}</p>}
          </div>

          {actions && <div className="ml-4 flex shrink-0 items-center gap-2 sm:ml-6">{actions}</div>}
        </div>
      </div>
    </header>
  );
};

PageHeader.displayName = 'Page.Header';

const PageActions = React.forwardRef<HTMLDivElement, PageActionsProps>(({ children, className }, ref) => {
  return (
    <div ref={ref} className={cn('flex items-center gap-2', className)}>
      {children}
    </div>
  );
});

PageActions.displayName = 'Page.Actions';

const PageContent = React.forwardRef<HTMLDivElement, PageContentProps>(({ children, className, noPadding = false }, ref) => {
  return (
    <main ref={ref} className={cn('flex-1 overflow-x-hidden overflow-y-auto', className)}>
      <div className={cn(!noPadding && 'container mx-auto px-4 py-6 sm:px-6 lg:px-8')}>{children}</div>
    </main>
  );
});

PageContent.displayName = 'Page.Content';

const PageBottom = ({ children, className }: PageBottomProps) => {
  return (
    <div className={cn('border-t bg-white dark:bg-gray-950', 'shadow-[0_-2px_10px_rgba(0,0,0,0.05)]', className)}>
      <div className="container mx-auto px-4 py-4 sm:px-6 lg:px-8">
        <div className="w-full">{children}</div>
      </div>
    </div>
  );
};

PageBottom.displayName = 'Page.Bottom';

const PageSplitContent = ({ children, sidebar, className, mainClassName, sidebarClassName }: PageSplitContentProps) => {
  const {
    position = 'right',
    defaultSize = 30,
    minSize = 15,
    maxSize = 50,
    collapsed,
    onToggle,
    title,
    renderExpanded,
    renderCollapsed,
    showToggle = true,
    resizable = true,
  } = sidebar;

  const toggleIcon = React.useMemo(() => {
    if (position === 'left') {
      return collapsed ? <PanelLeftOpen className="h-4 w-4" /> : <PanelLeftClose className="h-4 w-4" />;
    }
    return collapsed ? <PanelRightOpen className="h-4 w-4" /> : <PanelRightClose className="h-4 w-4" />;
  }, [position, collapsed]);

  const sidebarContent = (
    <div className="h-full flex flex-col">
      {/* Expanded state header */}
      {!collapsed && title && showToggle && (
        <div className="flex items-center justify-between p-4 border-b">
          <h3 className="text-lg font-semibold">{title}</h3>
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8"
            onClick={() => onToggle(!collapsed)}
          >
            {toggleIcon}
          </Button>
        </div>
      )}
      
      {/* Collapsed state header */}
      {collapsed && showToggle && (
        <div className="flex flex-col items-center pt-4 pb-2">
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8 mb-2"
            onClick={() => onToggle(!collapsed)}
          >
            {toggleIcon}
          </Button>
          {/* Optional collapsed indicator */}
          <div className="w-8 h-px bg-gray-300"></div>
        </div>
      )}
      
      {/* Content area */}
      <div className={cn(
        "flex-1",
        collapsed ? "overflow-y-auto px-1" : "overflow-hidden"
      )}>
        {collapsed ? renderCollapsed() : renderExpanded()}
      </div>
    </div>
  );

  const sidebarClasses = cn(
    "bg-white border-gray-200 h-full",
    position === 'left' ? 'border-r' : 'border-l',
    sidebarClassName
  );

  const mainContentClasses = cn(
    "h-full",
    mainClassName
  );

  if (!resizable || collapsed) {
    // Non-resizable or collapsed state - use flex layout
    return (
      <main className={cn('flex-1 overflow-x-hidden overflow-y-auto', className)}>
        <div className={cn('flex h-full')}>
          {position === 'left' && (
            <aside className={cn(sidebarClasses, collapsed ? 'w-16' : 'w-80', 'flex-shrink-0 transition-all duration-300')}>
              {sidebarContent}
            </aside>
          )}
          
          <div className={cn("flex-1 min-w-0", mainContentClasses)}>
            {children}
          </div>
          
          {position === 'right' && (
            <aside className={cn(sidebarClasses, collapsed ? 'w-16' : 'w-80', 'flex-shrink-0 transition-all duration-300')}>
              {sidebarContent}
            </aside>
          )}
        </div>
      </main>
    );
  }

  // Resizable layout
  return (
    <main className={cn('flex-1 overflow-x-hidden overflow-y-auto', className)}>
      <ResizablePanelGroup
        direction="horizontal"
        className="h-full"
      >
        {position === 'left' && (
          <>
            <ResizablePanel
              defaultSize={defaultSize}
              minSize={minSize}
              maxSize={maxSize}
              className={sidebarClasses}
            >
              {sidebarContent}
            </ResizablePanel>
            <ResizableHandle withHandle className="w-2" />
            <ResizablePanel className={mainContentClasses}>
              {children}
            </ResizablePanel>
          </>
        )}
        
        {position === 'right' && (
          <>
            <ResizablePanel className={mainContentClasses}>
              {children}
            </ResizablePanel>
            <ResizableHandle withHandle className="w-2" />
            <ResizablePanel
              defaultSize={defaultSize}
              minSize={minSize}
              maxSize={maxSize}
              className={sidebarClasses}
            >
              {sidebarContent}
            </ResizablePanel>
          </>
        )}
      </ResizablePanelGroup>
    </main>
  );
};

PageSplitContent.displayName = 'Page.SplitContent';

const PageLayout = Page as PageLayoutComponent;

PageLayout.Header = PageHeader;
PageLayout.Actions = PageActions;
PageLayout.Content = PageContent;
PageLayout.Bottom = PageBottom;
PageLayout.SplitContent = PageSplitContent;

export default PageLayout;
