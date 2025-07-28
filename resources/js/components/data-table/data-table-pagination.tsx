import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTablePagination as PaginationType } from '@/types/datatable';
import { Table } from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import * as React from 'react';

interface DataTablePaginationProps<TData> {
  table?: Table<TData>;
  pagination: PaginationType;
  onPageChange?: (page: number) => void;
  onPageSizeChange?: (pageSize: number) => void;
  perPageOptions?: number[];
}

export function DataTablePagination<TData>({ pagination, onPageChange, onPageSizeChange, perPageOptions = [10, 20, 50, 100] }: DataTablePaginationProps<TData>) {
  const pageSizeOptions = perPageOptions;

  const handlePageChange = (page: number) => {
    if (onPageChange) {
      onPageChange(page);
    }
  };

  const handlePageSizeChange = (pageSize: string) => {
    if (onPageSizeChange) {
      onPageSizeChange(Number(pageSize));
    }
  };

  return (
    <div className="flex items-center justify-between px-2">
      <div className="flex items-center space-x-6 lg:space-x-8">
        <div className="flex items-center space-x-2">
          <p className="text-sm font-medium">Rows per page</p>
          <Select value={`${pagination.per_page}`} onValueChange={handlePageSizeChange}>
            <SelectTrigger className="h-8 w-[70px]">
              <SelectValue placeholder={`${pagination.per_page}`} />
            </SelectTrigger>
            <SelectContent side="top">
              {pageSizeOptions.map((pageSize) => (
                <SelectItem key={pageSize} value={`${pageSize}`}>
                  {pageSize}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="flex w-[100px] items-center justify-center text-sm font-medium">
          {pagination.from && pagination.to ? (
            <>
              {pagination.from}-{pagination.to} of {pagination.total}
            </>
          ) : (
            <>0 results</>
          )}
        </div>
      </div>
      <div className="flex items-center space-x-2">
        <Button variant="outline" className="hidden h-8 w-8 p-0 lg:flex" onClick={() => handlePageChange(1)} disabled={pagination.current_page === 1}>
          <span className="sr-only">Go to first page</span>
          <ChevronsLeft className="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          className="h-8 w-8 p-0"
          onClick={() => handlePageChange(pagination.current_page - 1)}
          disabled={!pagination.prev_page_url}
        >
          <span className="sr-only">Go to previous page</span>
          <ChevronLeft className="h-4 w-4" />
        </Button>

        {/* Page numbers */}
        <div className="flex items-center gap-1">
          {generatePageNumbers(pagination).map((pageNum, idx) => (
            <React.Fragment key={idx}>
              {pageNum === '...' ? (
                <span className="px-1">...</span>
              ) : (
                <Button
                  variant={pageNum === pagination.current_page ? 'default' : 'outline'}
                  className="h-8 w-8 p-0"
                  onClick={() => handlePageChange(pageNum as number)}
                >
                  {pageNum}
                </Button>
              )}
            </React.Fragment>
          ))}
        </div>

        <Button
          variant="outline"
          className="h-8 w-8 p-0"
          onClick={() => handlePageChange(pagination.current_page + 1)}
          disabled={!pagination.next_page_url}
        >
          <span className="sr-only">Go to next page</span>
          <ChevronRight className="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          className="hidden h-8 w-8 p-0 lg:flex"
          onClick={() => handlePageChange(pagination.last_page)}
          disabled={pagination.current_page === pagination.last_page}
        >
          <span className="sr-only">Go to last page</span>
          <ChevronsRight className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}

// Helper function to generate page numbers with ellipsis
function generatePageNumbers(pagination: PaginationType): (number | string)[] {
  const { current_page, last_page } = pagination;
  const delta = 2; // Number of pages to show on each side of current page
  const range: (number | string)[] = [];
  const rangeWithDots: (number | string)[] = [];
  let l: number | undefined;

  for (let i = 1; i <= last_page; i++) {
    if (i === 1 || i === last_page || (i >= current_page - delta && i <= current_page + delta)) {
      range.push(i);
    }
  }

  range.forEach((i) => {
    if (l && typeof i === 'number' && typeof l === 'number') {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    if (typeof i === 'number') {
      l = i;
    }
  });

  return rangeWithDots;
}
