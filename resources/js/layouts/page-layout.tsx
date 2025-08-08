import { cn } from '@/lib/utils';
import * as React from 'react';
import { Button } from '@/components/ui/button';
import { PanelLeftClose, PanelLeftOpen, PanelRightClose, PanelRightOpen } from 'lucide-react';
import {
  ResizablePanelGroup,
  ResizablePanel,
  ResizableHandle,
} from '@/components/ui/resizable';
import type { ImperativePanelHandle } from 'react-resizable-panels';

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
    collapsedSize?: number; // Size when collapsed (e.g., 5 for 5%)
    defaultCollapsed?: boolean; // Initial collapsed state
    onCollapsedChange?: (collapsed: boolean) => void; // Callback for collapse state changes
    title?: string;
    renderContent: (collapsed: boolean, toggleCollapse: () => void) => React.ReactNode; // Render function with toggle
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
    collapsedSize = 5,
    defaultCollapsed = false,
    onCollapsedChange,
    title,
    renderContent,
    showToggle = true,
    resizable = true,
  } = sidebar;

  // Panel ref for imperative control
  const panelRef = React.useRef<ImperativePanelHandle>(null);
  
  // Track resizing state for smooth animations
  const [isResizing, setIsResizing] = React.useState(false);
  
  // Track collapsed state
  const [isCollapsed, setIsCollapsed] = React.useState(defaultCollapsed);

  // Toggle handler using imperative API
  const handleToggle = React.useCallback(() => {
    if (panelRef.current) {
      if (isCollapsed) {
        panelRef.current.expand();
      } else {
        panelRef.current.collapse();
      }
    }
  }, [isCollapsed]);

  // Handle collapse state changes
  const handleCollapse = React.useCallback(() => {
    setIsCollapsed(true);
    onCollapsedChange?.(true);
  }, [onCollapsedChange]);

  const handleExpand = React.useCallback(() => {
    setIsCollapsed(false);
    onCollapsedChange?.(false);
  }, [onCollapsedChange]);

  const toggleIcon = React.useMemo(() => {
    if (position === 'left') {
      return isCollapsed ? <PanelLeftOpen className="h-4 w-4" /> : <PanelLeftClose className="h-4 w-4" />;
    }
    return isCollapsed ? <PanelRightOpen className="h-4 w-4" /> : <PanelRightClose className="h-4 w-4" />;
  }, [position, isCollapsed]);

  const sidebarContent = (
    <div className="h-full flex flex-col">
      {/* Header with toggle button */}
      {showToggle && (
        <div className={cn(
          "flex items-center border-b transition-all duration-300",
          isCollapsed ? "justify-center p-2" : "justify-between p-4"
        )}>
          {!isCollapsed && title && (
            <h3 className="text-lg font-semibold">
              {title}
            </h3>
          )}
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8"
            onClick={handleToggle}
          >
            {toggleIcon}
          </Button>
        </div>
      )}
      
      {/* Content area - let the panel handle the collapse animation */}
      <div className="flex-1 overflow-hidden">
        {renderContent(isCollapsed, handleToggle)}
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

  // Non-resizable mode - use flex layout with transitions
  if (!resizable) {
    return (
      <main className={cn('flex-1 overflow-x-hidden overflow-y-auto', className)}>
        <div className={cn('flex h-full')}>
          {position === 'left' && (
            <aside className={cn(sidebarClasses, isCollapsed ? 'w-16' : 'w-80', 'flex-shrink-0 transition-all duration-300')}>
              {sidebarContent}
            </aside>
          )}
          
          <div className={cn("flex-1 min-w-0", mainContentClasses)}>
            {children}
          </div>
          
          {position === 'right' && (
            <aside className={cn(sidebarClasses, isCollapsed ? 'w-16' : 'w-80', 'flex-shrink-0 transition-all duration-300')}>
              {sidebarContent}
            </aside>
          )}
        </div>
      </main>
    );
  }

  // Resizable layout with native collapse support
  return (
    <main className={cn('flex-1 overflow-x-hidden overflow-y-auto', className)}>
      <ResizablePanelGroup
        direction="horizontal"
        className="h-full"
      >
        {position === 'left' && (
          <>
            <ResizablePanel
              ref={panelRef}
              defaultSize={defaultCollapsed ? collapsedSize : defaultSize}
              minSize={minSize}
              maxSize={maxSize}
              collapsible={true}
              collapsedSize={collapsedSize}
              onCollapse={handleCollapse}
              onExpand={handleExpand}
              className={cn(
                sidebarClasses,
                !isResizing && "transition-all duration-300 ease-in-out"
              )}
            >
              {sidebarContent}
            </ResizablePanel>
            <ResizableHandle 
              withHandle 
              className="w-2" 
              onDragging={setIsResizing}
            />
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
            <ResizableHandle 
              withHandle 
              className="w-2" 
              onDragging={setIsResizing}
            />
            <ResizablePanel
              ref={panelRef}
              defaultSize={defaultCollapsed ? collapsedSize : defaultSize}
              minSize={minSize}
              maxSize={maxSize}
              collapsible={true}
              collapsedSize={collapsedSize}
              onCollapse={handleCollapse}
              onExpand={handleExpand}
              className={cn(
                sidebarClasses,
                !isResizing && "transition-all duration-300 ease-in-out"
              )}
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
