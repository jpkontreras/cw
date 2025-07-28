// Laravel Pagination Data Types
export interface LaravelPaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface LaravelPaginationMeta<T = any> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: LaravelPaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
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
    current_page: number;
    from: number | null;
    last_page: number;
    links: LaravelPaginationLink[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
}

// Simple pagination format (what we're currently using)
export interface SimplePagination<T = any> {
  data: T[];
  current_page: number;
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: LaravelPaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
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
      current_page: pagination.meta.current_page,
      first_page_url: `${pagination.meta.path}?page=1`,
      from: pagination.meta.from,
      last_page: pagination.meta.last_page,
      last_page_url: `${pagination.meta.path}?page=${pagination.meta.last_page}`,
      links: pagination.meta.links,
      next_page_url: pagination.links.next,
      path: pagination.meta.path,
      per_page: pagination.meta.per_page,
      prev_page_url: pagination.links.prev,
      to: pagination.meta.to,
      total: pagination.meta.total,
    };
  }

  // Legacy format - create links array
  const currentPage = (pagination as any).current_page || (pagination as any).currentPage || 1;
  const lastPage = (pagination as any).last_page || (pagination as any).lastPage || 1;
  const perPage = (pagination as any).per_page || (pagination as any).perPage || 15;
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
    current_page: currentPage,
    first_page_url: '?page=1',
    from,
    last_page: lastPage,
    last_page_url: `?page=${lastPage}`,
    links,
    next_page_url: currentPage < lastPage ? `?page=${currentPage + 1}` : null,
    path: '',
    per_page: perPage,
    prev_page_url: currentPage > 1 ? `?page=${currentPage - 1}` : null,
    to,
    total,
  };
}
