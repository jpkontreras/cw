import * as React from 'react';
import { useMeasure } from 'react-use';
import { cn } from '@/lib/utils';

// ============================================================================
// Types
// ============================================================================

interface PageProps {
  children: React.ReactNode;
  className?: string;
}

interface PageHeaderProps {
  title?: string;
  subtitle?: string;
  children?: React.ReactNode;
  className?: string;
  compactThreshold?: number;
}

interface PageTitleProps {
  children: React.ReactNode;
  className?: string;
}

interface PageSubtitleProps {
  children: React.ReactNode;
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

interface PageContextValue {
  headerHeight: number;
  bottomHeight: number;
  isCompact: boolean;
  setScrollElement: (element: HTMLElement | null) => void;
  scrollY: number;
}

// ============================================================================
// Component Type Definitions for Compound Components
// ============================================================================

type PageComponent = React.ForwardRefExoticComponent<PageProps & React.RefAttributes<HTMLDivElement>> & {
  Header: React.ForwardRefExoticComponent<PageHeaderProps & React.RefAttributes<HTMLDivElement>>;
  Title: React.FC<PageTitleProps>;
  Subtitle: React.FC<PageSubtitleProps>;
  Actions: React.ForwardRefExoticComponent<PageActionsProps & React.RefAttributes<HTMLDivElement>>;
  Content: React.ForwardRefExoticComponent<PageContentProps & React.RefAttributes<HTMLDivElement>>;
  Bottom: React.ForwardRefExoticComponent<PageBottomProps & React.RefAttributes<HTMLDivElement>>;
};

// ============================================================================
// Context
// ============================================================================

const PageContext = React.createContext<PageContextValue>({
  headerHeight: 0,
  bottomHeight: 0,
  isCompact: false,
  setScrollElement: () => {},
  scrollY: 0,
});

const PageActionsContext = React.createContext<{ isCompact: boolean }>({
  isCompact: false,
});

const usePageContext = () => React.useContext(PageContext);
export const usePageActionsContext = () => React.useContext(PageActionsContext);

// ============================================================================
// Utility Functions
// ============================================================================

function extractPageHeaderContent(children: React.ReactNode) {
  const childArray = React.Children.toArray(children);
  let title: React.ReactNode = null;
  let subtitle: React.ReactNode = null;
  let actions: React.ReactNode = null;
  let hasCompoundComponents = false;
  
  childArray.forEach(child => {
    if (React.isValidElement(child)) {
      if (child.type === PageTitle) {
        const typedChild = child as React.ReactElement<PageTitleProps>;
        title = typedChild.props.children;
        hasCompoundComponents = true;
      } else if (child.type === PageSubtitle) {
        const typedChild = child as React.ReactElement<PageSubtitleProps>;
        subtitle = typedChild.props.children;
        hasCompoundComponents = true;
      } else if (child.type === PageActions) {
        actions = child;
        hasCompoundComponents = true;
      }
    }
  });
  
  // If no compound components found, treat all children as actions
  const defaultActions = hasCompoundComponents ? null : children;
  
  return {
    title,
    subtitle,
    actions: actions || defaultActions,
  };
}

// ============================================================================
// Components
// ============================================================================

const Page = React.forwardRef<HTMLDivElement, PageProps>(
  ({ children, className }, ref) => {
    const [headerHeight, setHeaderHeight] = React.useState(0);
    const [bottomHeight, setBottomHeight] = React.useState(0);
    const [isCompact, setIsCompact] = React.useState(false);
    const [scrollElement, setScrollElement] = React.useState<HTMLElement | null>(null);
    const [scrollY, setScrollY] = React.useState(0);

    // Track scroll on the scroll element
    React.useEffect(() => {
      if (!scrollElement) return;

      const handleScroll = () => {
        setScrollY(scrollElement.scrollTop);
      };

      scrollElement.addEventListener('scroll', handleScroll, { passive: true });
      return () => scrollElement.removeEventListener('scroll', handleScroll);
    }, [scrollElement]);

    const contextValue = React.useMemo(
      () => ({ headerHeight, bottomHeight, isCompact, setScrollElement, scrollY }),
      [headerHeight, bottomHeight, isCompact, scrollY]
    );

    // Pass compact state to header if it exists
    const childArray = React.Children.toArray(children);
    const enhancedChildren = childArray.map(child => {
      if (React.isValidElement(child) && child.type === PageHeader) {
        return React.cloneElement(child as React.ReactElement<InternalPageHeaderProps>, {
          onCompactChange: setIsCompact,
          onHeightChange: setHeaderHeight,
        });
      }
      if (React.isValidElement(child) && child.type === PageBottom) {
        return React.cloneElement(child as React.ReactElement<InternalPageBottomProps>, {
          onHeightChange: setBottomHeight,
        });
      }
      return child;
    });

    return (
      <PageContext.Provider value={contextValue}>
        <div 
          ref={ref} 
          className={cn('relative flex h-full flex-col', className)}
        >
          {enhancedChildren}
        </div>
      </PageContext.Provider>
    );
  }
);

Page.displayName = 'Page';

// ============================================================================

interface InternalPageHeaderProps extends PageHeaderProps {
  onCompactChange?: (compact: boolean) => void;
  onHeightChange?: (height: number) => void;
}

const PageHeader = React.forwardRef<HTMLDivElement, InternalPageHeaderProps>(
  ({ 
    title, 
    subtitle, 
    children, 
    className, 
    compactThreshold = 20,
    onCompactChange,
    onHeightChange,
  }, ref) => {
    const [headerRef, { height }] = useMeasure<HTMLDivElement>();
    const { scrollY } = usePageContext();
    const [isCompact, setIsCompact] = React.useState(false);
    
    // Update compact state immediately on scroll
    React.useEffect(() => {
      setIsCompact(scrollY > compactThreshold);
    }, [scrollY, compactThreshold]);

    // Notify parent of height changes
    React.useEffect(() => {
      onHeightChange?.(height);
    }, [height, onHeightChange]);

    // Notify parent of compact state changes
    React.useEffect(() => {
      onCompactChange?.(isCompact);
    }, [isCompact, onCompactChange]);

    // Extract content from compound components
    const extracted = extractPageHeaderContent(children);
    const finalTitle = title || extracted.title;
    const finalSubtitle = subtitle || extracted.subtitle;
    const finalActions = title || subtitle ? children : extracted.actions;

    return (
      <header
        ref={(node: HTMLDivElement | null) => {
          if (node) headerRef(node);
          if (typeof ref === 'function') ref(node);
          else if (ref) ref.current = node;
        }}
        className={cn(
          'absolute top-0 left-0 right-0 z-50 border-b bg-background/95',
          'backdrop-blur supports-[backdrop-filter]:bg-background/60',
          className
        )}
      >
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div 
            className={cn(
              'flex items-center justify-between',
              isCompact ? 'h-[45px]' : 'py-4 sm:py-6'
            )}
          >
            {/* Title Section */}
            <div className="min-w-0 flex-1">
              {finalTitle && (
                <h1 
                  className={cn(
                    'truncate font-semibold tracking-tight',
                    isCompact 
                      ? 'text-lg leading-none' 
                      : 'text-2xl sm:text-3xl'
                  )}
                >
                  {finalTitle}
                </h1>
              )}
              
              {finalSubtitle && (
                <p 
                  className={cn(
                    'text-muted-foreground',
                    'overflow-hidden',
                    isCompact
                      ? 'invisible max-h-0 opacity-0'
                      : 'visible mt-1 max-h-6 opacity-100'
                  )}
                >
                  {finalSubtitle}
                </p>
              )}
            </div>

            {/* Actions Section */}
            {finalActions && (
              <div className="ml-4 flex shrink-0 items-center gap-2 sm:ml-6">
                <PageActionsContext.Provider value={{ isCompact }}>
                  {finalActions}
                </PageActionsContext.Provider>
              </div>
            )}
          </div>
        </div>
      </header>
    );
  }
);

PageHeader.displayName = 'Page.Header';

// ============================================================================

const PageTitle: React.FC<PageTitleProps> = ({ children }) => {
  return <>{children}</>;
};

PageTitle.displayName = 'Page.Title';

// ============================================================================

const PageSubtitle: React.FC<PageSubtitleProps> = ({ children }) => {
  return <>{children}</>;
};

PageSubtitle.displayName = 'Page.Subtitle';

// ============================================================================

const PageActions = React.forwardRef<HTMLDivElement, PageActionsProps>(
  ({ children, className }, ref) => {
    const { isCompact } = usePageActionsContext();
    
    return (
      <div 
        ref={ref} 
        className={cn(
          'flex items-center gap-2',
          // Use CSS selectors to target buttons within the actions
          isCompact && [
            '[&_button]:h-8',
            '[&_button]:px-3', 
            '[&_button]:text-xs',
            '[&_a>button]:h-8',
            '[&_a>button]:px-3',
            '[&_a>button]:text-xs',
          ].join(' '),
          className
        )}
      >
        {children}
      </div>
    );
  }
);

PageActions.displayName = 'Page.Actions';

// ============================================================================

const PageContent = React.forwardRef<HTMLDivElement, PageContentProps>(
  ({ children, className, noPadding = false }, ref) => {
    const { setScrollElement, headerHeight, bottomHeight } = usePageContext();
    const contentRef = React.useRef<HTMLDivElement>(null);
    
    React.useEffect(() => {
      setScrollElement(contentRef.current);
    }, [setScrollElement]);
    
    return (
      <main
        ref={(node: HTMLDivElement | null) => {
          contentRef.current = node;
          if (typeof ref === 'function') ref(node);
          else if (ref) ref.current = node;
        }}
        className={cn(
          'absolute inset-0 overflow-y-auto overflow-x-hidden',
          className
        )}
        style={{
          top: `${headerHeight}px`,
          bottom: `${bottomHeight}px`,
        }}
      >
        <div className={cn(
          !noPadding && 'container mx-auto px-4 py-6 sm:px-6 lg:px-8'
        )}>
          {children}
        </div>
      </main>
    );
  }
);

PageContent.displayName = 'Page.Content';

// ============================================================================

interface InternalPageBottomProps extends PageBottomProps {
  onHeightChange?: (height: number) => void;
}

const PageBottom = React.forwardRef<HTMLDivElement, InternalPageBottomProps>(
  ({ children, className, onHeightChange }, ref) => {
    const [bottomRef, { height }] = useMeasure<HTMLDivElement>();
    const { isCompact } = usePageContext();

    React.useEffect(() => {
      onHeightChange?.(height);
      return () => onHeightChange?.(0);
    }, [height, onHeightChange]);

    return (
      <div
        ref={(node: HTMLDivElement | null) => {
          if (node) bottomRef(node);
          if (typeof ref === 'function') ref(node);
          else if (ref) ref.current = node;
        }}
        className={cn(
          'absolute bottom-0 left-0 right-0 z-40 border-t bg-white dark:bg-gray-950',
          'shadow-[0_-2px_10px_rgba(0,0,0,0.05)]',
          className
        )}
      >
        <div className={cn(
          'container mx-auto px-4 sm:px-6 lg:px-8',
          isCompact ? 'h-[45px] flex items-center' : 'py-4'
        )}>
          <div className={cn(
            'w-full',
            isCompact && [
              '[&_button]:h-8',
              '[&_button]:px-3',
              '[&_button]:text-xs',
              '[&_button_svg]:h-4',
              '[&_button_svg]:w-4',
              'text-xs',
            ].join(' ')
          )}>
            {children}
          </div>
        </div>
      </div>
    );
  }
);

PageBottom.displayName = 'Page.Bottom';

// ============================================================================
// Compose and Export
// ============================================================================

// Type assertion to add compound components
const TypedPage = Page as PageComponent;

TypedPage.Header = PageHeader as React.ForwardRefExoticComponent<PageHeaderProps & React.RefAttributes<HTMLDivElement>>;
TypedPage.Title = PageTitle;
TypedPage.Subtitle = PageSubtitle;
TypedPage.Actions = PageActions;
TypedPage.Content = PageContent;
TypedPage.Bottom = PageBottom as React.ForwardRefExoticComponent<PageBottomProps & React.RefAttributes<HTMLDivElement>>;

export default TypedPage;