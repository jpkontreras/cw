import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';
import { Button } from './ui/button';
import { cn } from '@/lib/utils';
import type { SimplePagination, PaginationData, LaravelPaginationLink } from '@/types/pagination';
import { normalizePagination } from '@/types/pagination';

interface LaravelPaginationProps<T = any> {
  pagination: PaginationData<T>;
  onPageChange?: (page: number) => void;
  preserveScroll?: boolean;
  preserveState?: boolean;
  className?: string;
  showInfo?: boolean;
  infoClassName?: string;
  filters?: Record<string, any>;
}

export function LaravelPagination<T = any>({
  pagination: rawPagination,
  onPageChange,
  preserveScroll = false,
  preserveState = true,
  className,
  showInfo = true,
  infoClassName,
  filters = {},
}: LaravelPaginationProps<T>) {
  // Normalize pagination data to handle different formats
  const pagination = normalizePagination(rawPagination);
  
  if (!pagination || pagination.total === 0) {
    return null;
  }

  const handlePageChange = (page: number) => {
    if (onPageChange) {
      onPageChange(page);
    } else {
      // Default Inertia navigation
      const url = new URL(window.location.href);
      url.searchParams.set('page', String(page));
      
      // Add any filters to the URL
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          url.searchParams.set(key, String(value));
        }
      });
      
      router.get(url.pathname + url.search, {}, {
        preserveScroll,
        preserveState,
      });
    }
  };

  const renderPageButton = (link: LaravelPaginationLink, index: number) => {
    // Skip if it's a previous/next button (handled separately)
    if (link.label.includes('Previous') || link.label.includes('Next')) {
      return null;
    }

    // Ellipsis
    if (link.label === '...') {
      return (
        <span key={`ellipsis-${index}`} className="flex h-9 w-9 items-center justify-center">
          <MoreHorizontal className="h-4 w-4" />
          <span className="sr-only">More pages</span>
        </span>
      );
    }

    const pageNumber = parseInt(link.label, 10);
    if (isNaN(pageNumber)) return null;

    return (
      <Button
        key={`page-${pageNumber}`}
        variant={link.active ? 'default' : 'outline'}
        size="sm"
        onClick={() => handlePageChange(pageNumber)}
        disabled={link.active}
        className={cn('h-9 w-9 p-0', link.active && 'pointer-events-none')}
      >
        {link.label}
      </Button>
    );
  };

  // Get page numbers to display with ellipsis logic
  const getPageButtons = () => {
    const links = pagination.links;
    const buttons: React.ReactNode[] = [];
    
    // Filter out Previous/Next links
    const pageLinks = links.filter(link => 
      !link.label.includes('Previous') && !link.label.includes('Next')
    );
    
    if (pageLinks.length <= 7) {
      // Show all pages if 7 or fewer
      return pageLinks.map((link, index) => renderPageButton(link, index));
    }
    
    const currentPage = pagination.current_page;
    const lastPage = pagination.last_page;
    
    // Always show first page
    buttons.push(renderPageButton(pageLinks[0], 0));
    
    if (currentPage > 3) {
      buttons.push(
        <span key="ellipsis-start" className="flex h-9 w-9 items-center justify-center">
          <MoreHorizontal className="h-4 w-4" />
        </span>
      );
    }
    
    // Show pages around current page
    const start = Math.max(2, currentPage - 1);
    const end = Math.min(lastPage - 1, currentPage + 1);
    
    for (let i = start; i <= end; i++) {
      const link = pageLinks.find(l => parseInt(l.label, 10) === i);
      if (link) {
        buttons.push(renderPageButton(link, i));
      }
    }
    
    if (currentPage < lastPage - 2) {
      buttons.push(
        <span key="ellipsis-end" className="flex h-9 w-9 items-center justify-center">
          <MoreHorizontal className="h-4 w-4" />
        </span>
      );
    }
    
    // Always show last page if more than 1 page
    if (lastPage > 1) {
      buttons.push(renderPageButton(pageLinks[pageLinks.length - 1], pageLinks.length - 1));
    }
    
    return buttons;
  };

  const { current_page, last_page, from, to, total } = pagination;
  const hasPrevious = current_page > 1;
  const hasNext = current_page < last_page;

  return (
    <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between', className)}>
      {showInfo && (
        <div className={cn('text-sm text-muted-foreground', infoClassName)}>
          {from && to ? (
            <>
              Showing <span className="font-medium">{from}</span> to{' '}
              <span className="font-medium">{to}</span> of{' '}
              <span className="font-medium">{total}</span> results
            </>
          ) : (
            <>No results</>
          )}
        </div>
      )}
      
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(current_page - 1)}
          disabled={!hasPrevious}
          className="h-9 px-3"
        >
          <ChevronLeft className="mr-1 h-4 w-4" />
          <span className="hidden sm:inline">Previous</span>
        </Button>
        
        <div className="flex items-center gap-1">
          {getPageButtons()}
        </div>
        
        <Button
          variant="outline"
          size="sm"
          onClick={() => handlePageChange(current_page + 1)}
          disabled={!hasNext}
          className="h-9 px-3"
        >
          <span className="hidden sm:inline">Next</span>
          <ChevronRight className="ml-1 h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}