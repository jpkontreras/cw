// Laravel Pagination Data Types
export interface LaravelPaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface LaravelPaginationMeta<T = any> {
  currentPage: number;
  data: T[];
  firstPageUrl: string;
  from: number | null;
  lastPage: number;
  lastPageUrl: string;
  links: LaravelPaginationLink[];
  nextPageUrl: string | null;
  path: string;
  perPage: number;
  prevPageUrl: string | null;
  to: number | null;
  total: number;
}

export interface LaravelPagination<T = any> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    currentPage: number;
    from: number | null;
    lastPage: number;
    links: LaravelPaginationLink[];
    path: string;
    perPage: number;
    to: number | null;
    total: number;
  };
}

// Simple pagination format (what we're currently using)
export interface SimplePagination<T = any> {
  data: T[];
  currentPage: number;
  firstPageUrl: string;
  from: number | null;
  lastPage: number;
  lastPageUrl: string;
  links: LaravelPaginationLink[];
  nextPageUrl: string | null;
  path: string;
  perPage: number;
  prevPageUrl: string | null;
  to: number | null;
  total: number;
}

// Legacy format for backwards compatibility
export interface LegacyPagination<T = any> {
  data: T[];
  currentPage?: number;
  current_page?: number;
  lastPage?: number;
  last_page?: number;
  perPage?: number;
  per_page?: number;
  total: number;
}

// Unified type that supports all formats
export type PaginationData<T = any> = SimplePagination<T> | LaravelPagination<T> | LegacyPagination<T>;

// Type guard to check if pagination has Laravel links
export function hasLaravelLinks(pagination: any): pagination is SimplePagination {
  return pagination && Array.isArray(pagination.links) && pagination.links.length > 0;
}

// Helper to normalize pagination data
export function normalizePagination<T>(pagination: PaginationData<T>): SimplePagination<T> {
  // If it's already in simple format with links
  if ('links' in pagination && Array.isArray(pagination.links)) {
    return pagination as SimplePagination<T>;
  }

  // If it's Laravel resource format
  if ('meta' in pagination && pagination.meta) {
    return {
      data: pagination.data,
      currentPage: pagination.meta.currentPage,
      firstPageUrl: `${pagination.meta.path}?page=1`,
      from: pagination.meta.from,
      lastPage: pagination.meta.lastPage,
      lastPageUrl: `${pagination.meta.path}?page=${pagination.meta.lastPage}`,
      links: pagination.meta.links,
      nextPageUrl: pagination.links.next,
      path: pagination.meta.path,
      perPage: pagination.meta.perPage,
      prevPageUrl: pagination.links.prev,
      to: pagination.meta.to,
      total: pagination.meta.total,
    };
  }

  // Legacy format - create links array
  const currentPage = (pagination as any).currentPage || (pagination as any).current_page || 1;
  const lastPage = (pagination as any).lastPage || (pagination as any).last_page || 1;
  const perPage = (pagination as any).perPage || (pagination as any).per_page || 15;
  const total = pagination.total || 0;
  const from = total > 0 ? (currentPage - 1) * perPage + 1 : null;
  const to = total > 0 ? Math.min(currentPage * perPage, total) : null;

  // Generate links array
  const links: LaravelPaginationLink[] = [
    {
      url: currentPage > 1 ? `?page=${currentPage - 1}` : null,
      label: '&laquo; Previous',
      active: false,
    },
  ];

  // Add page numbers
  for (let i = 1; i <= lastPage; i++) {
    links.push({
      url: `?page=${i}`,
      label: String(i),
      active: i === currentPage,
    });
  }

  links.push({
    url: currentPage < lastPage ? `?page=${currentPage + 1}` : null,
    label: 'Next &raquo;',
    active: false,
  });

  return {
    data: pagination.data,
    currentPage: currentPage,
    firstPageUrl: '?page=1',
    from,
    lastPage: lastPage,
    lastPageUrl: `?page=${lastPage}`,
    links,
    nextPageUrl: currentPage < lastPage ? `?page=${currentPage + 1}` : null,
    path: '',
    perPage: perPage,
    prevPageUrl: currentPage > 1 ? `?page=${currentPage - 1}` : null,
    to,
    total,
  };
}
