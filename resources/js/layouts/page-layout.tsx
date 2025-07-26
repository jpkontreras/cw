import { cn } from '@/lib/utils';
import * as React from 'react';
import { useMeasure } from 'react-use';

interface PageProps {
  children: React.ReactNode;
  className?: string;
}

interface PageHeaderProps {
  title?: React.ReactNode;
  subtitle?: React.ReactNode;
  actions?: React.ReactNode;
  className?: string;
  compactThreshold?: number;
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

type PageLayoutComponent = React.ForwardRefExoticComponent<PageProps & React.RefAttributes<HTMLDivElement>> & {
  Header: React.FC<PageHeaderProps>;
  Actions: React.ForwardRefExoticComponent<PageActionsProps & React.RefAttributes<HTMLDivElement>>;
  Content: React.ForwardRefExoticComponent<PageContentProps & React.RefAttributes<HTMLDivElement>>;
  Bottom: React.FC<PageBottomProps>;
};

interface PageInternalContextValue extends PageContextValue {
  setHeaderHeight: (height: number) => void;
  setBottomHeight: (height: number) => void;
  setIsCompact: (compact: boolean) => void;
}

const PageContext = React.createContext<PageInternalContextValue>({
  headerHeight: 0,
  bottomHeight: 0,
  isCompact: false,
  setScrollElement: () => {},
  scrollY: 0,
  setHeaderHeight: () => {},
  setBottomHeight: () => {},
  setIsCompact: () => {},
});

const PageActionsContext = React.createContext<{ isCompact: boolean }>({
  isCompact: false,
});

const usePageContext = () => React.useContext(PageContext);
export const usePageActionsContext = () => React.useContext(PageActionsContext);

export const usePageState = () => {
  const { isCompact, headerHeight, bottomHeight, scrollY } = usePageContext();
  return {
    isCompact,
    headerHeight,
    bottomHeight,
    scrollY,
  };
};

const Page = React.forwardRef<HTMLDivElement, PageProps>(({ children, className }, ref) => {
  const [headerHeight, setHeaderHeight] = React.useState(0);
  const [bottomHeight, setBottomHeight] = React.useState(0);
  const [isCompact, setIsCompact] = React.useState(false);
  const [scrollElement, setScrollElement] = React.useState<HTMLElement | null>(null);
  const [scrollY, setScrollY] = React.useState(0);

  React.useEffect(() => {
    if (!scrollElement) return;

    const handleScroll = () => {
      setScrollY(scrollElement.scrollTop);
    };

    scrollElement.addEventListener('scroll', handleScroll, { passive: true });
    return () => scrollElement.removeEventListener('scroll', handleScroll);
  }, [scrollElement]);

  const contextValue = React.useMemo(
    () => ({
      headerHeight,
      bottomHeight,
      isCompact,
      setScrollElement,
      scrollY,
      setHeaderHeight,
      setBottomHeight,
      setIsCompact,
    }),
    [headerHeight, bottomHeight, isCompact, scrollY],
  );

  return (
    <PageContext.Provider value={contextValue}>
      <div ref={ref} className={cn('relative flex h-full flex-col', className)}>
        {children}
      </div>
    </PageContext.Provider>
  );
});

Page.displayName = 'Page';

const PageHeader = ({ title, subtitle, actions, className, compactThreshold = 20 }: PageHeaderProps) => {
  const [headerRef, { height }] = useMeasure<HTMLDivElement>();
  const { scrollY, setHeaderHeight, setIsCompact } = usePageContext();
  const [isCompactLocal, setIsCompactLocal] = React.useState(false);

  React.useEffect(() => {
    const newIsCompact = scrollY > compactThreshold;
    setIsCompactLocal(newIsCompact);
    setIsCompact(newIsCompact);
  }, [scrollY, compactThreshold, setIsCompact]);

  React.useEffect(() => {
    setHeaderHeight(height);
  }, [height, setHeaderHeight]);

  return (
    <header
      ref={headerRef}
      className={cn(
        'absolute top-0 right-0 left-0 z-50 border-b bg-background/95',
        'backdrop-blur supports-[backdrop-filter]:bg-background/60',
        className,
      )}
    >
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        <div className={cn('flex items-center justify-between', isCompactLocal ? 'h-[45px]' : 'py-4 sm:py-6')}>
          <div className="min-w-0 flex-1">
            {title && (
              <h1 className={cn('truncate font-semibold tracking-tight', isCompactLocal ? 'text-3xl leading-none' : 'text-2xl sm:text-3xl')}>
                {title}
              </h1>
            )}

            {subtitle && (
              <p
                className={cn(
                  'text-muted-foreground',
                  'overflow-hidden',
                  isCompactLocal ? 'invisible max-h-0 opacity-0' : 'visible mt-1 max-h-6 opacity-100',
                )}
              >
                {subtitle}
              </p>
            )}
          </div>

          {actions && (
            <div className="ml-4 flex shrink-0 items-center gap-2 sm:ml-6">
              <PageActionsContext.Provider value={{ isCompact: isCompactLocal }}>{actions}</PageActionsContext.Provider>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};

PageHeader.displayName = 'Page.Header';

const PageActions = React.forwardRef<HTMLDivElement, PageActionsProps>(({ children, className }, ref) => {
  const { isCompact } = usePageActionsContext();

  return (
    <div
      ref={ref}
      className={cn(
        'flex items-center gap-2',
        isCompact &&
          ['[&_button]:h-10', '[&_button]:px-4', '[&_button]:text-sm', '[&_a>button]:h-10', '[&_a>button]:px-4', '[&_a>button]:text-sm'].join(' '),
        className,
      )}
    >
      {children}
    </div>
  );
});

PageActions.displayName = 'Page.Actions';

const PageContent = React.forwardRef<HTMLDivElement, PageContentProps>(({ children, className, noPadding = false }, ref) => {
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
      className={cn('absolute inset-0 overflow-x-hidden overflow-y-auto', className)}
      style={{
        top: `${headerHeight}px`,
        bottom: `${bottomHeight}px`,
      }}
    >
      <div className={cn(!noPadding && 'container mx-auto px-4 py-6 sm:px-6 lg:px-8')}>{children}</div>
    </main>
  );
});

PageContent.displayName = 'Page.Content';

const PageBottom = ({ children, className }: PageBottomProps) => {
  const [bottomRef, { height }] = useMeasure<HTMLDivElement>();
  const { isCompact, setBottomHeight } = usePageContext();

  React.useEffect(() => {
    setBottomHeight(height);
    return () => setBottomHeight(0);
  }, [height, setBottomHeight]);

  return (
    <div
      ref={bottomRef}
      className={cn('absolute right-0 bottom-0 left-0 z-40 border-t bg-white dark:bg-gray-950', 'shadow-[0_-2px_10px_rgba(0,0,0,0.05)]', className)}
    >
      <div className={cn('container mx-auto px-4 sm:px-6 lg:px-8', isCompact ? 'flex h-[45px] items-center' : 'py-4')}>
        <div
          className={cn(
            'w-full',
            isCompact && ['[&_button]:h-8', '[&_button]:px-3', '[&_button]:text-xs', '[&_button_svg]:h-4', '[&_button_svg]:w-4', 'text-xs'].join(' '),
          )}
        >
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
