<?php

declare(strict_types=1);

namespace App\Core\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Base DTO for paginated resource responses with metadata
 * 
 * @template TData
 */
class PaginatedResourceData extends Data
{
    /**
     * @param DataCollection<int, TData>|Collection<int, TData>|array<TData> $data
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly DataCollection|Collection|array $data,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly ?int $from,
        public readonly ?int $to,
        public readonly string $path,
        public readonly ?string $firstPageUrl,
        public readonly ?string $lastPageUrl,
        public readonly ?string $nextPageUrl,
        public readonly ?string $prevPageUrl,
        public readonly array $links,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create from Laravel's LengthAwarePaginator
     * 
     * @template T of Data
     * @param LengthAwarePaginator $paginator
     * @param class-string<T>|null $dataClass The Data class to transform items to
     * @param array<string, mixed> $metadata Additional metadata to include
     * @return self
     */
    public static function fromPaginator(
        LengthAwarePaginator $paginator,
        ?string $dataClass = null,
        array $metadata = []
    ): self {
        // Transform items to DTOs if data class provided
        $items = $paginator->items();
        
        // Use DataCollection for proper laravel-data support
        $data = $dataClass 
            ? $dataClass::collection($items)
            : DataCollection::make($items);

        // Build links array compatible with Laravel pagination
        $links = [];
        
        // Previous link
        $links[] = [
            'url' => $paginator->previousPageUrl(),
            'label' => '&laquo; Previous',
            'active' => false,
        ];
        
        // Page number links
        // Generate simple page links without using elements()
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        
        // Show pages around current page
        $start = max(1, $currentPage - 2);
        $end = min($lastPage, $currentPage + 2);
        
        if ($start > 1) {
            $links[] = [
                'url' => $paginator->url(1),
                'label' => '1',
                'active' => false,
            ];
            if ($start > 2) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                ];
            }
        }
        
        for ($page = $start; $page <= $end; $page++) {
            $links[] = [
                'url' => $paginator->url($page),
                'label' => (string) $page,
                'active' => $page === $currentPage,
            ];
        }
        
        if ($end < $lastPage) {
            if ($end < $lastPage - 1) {
                $links[] = [
                    'url' => null,
                    'label' => '...',
                    'active' => false,
                ];
            }
            $links[] = [
                'url' => $paginator->url($lastPage),
                'label' => (string) $lastPage,
                'active' => false,
            ];
        }
        
        // Next link
        $links[] = [
            'url' => $paginator->nextPageUrl(),
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        return new self(
            data: $data,
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            from: $paginator->firstItem(),
            to: $paginator->lastItem(),
            path: $paginator->path(),
            firstPageUrl: $paginator->url(1),
            lastPageUrl: $paginator->url($paginator->lastPage()),
            nextPageUrl: $paginator->nextPageUrl(),
            prevPageUrl: $paginator->previousPageUrl(),
            links: $links,
            metadata: $metadata,
        );
    }

    /**
     * Convert to array with proper structure for frontend
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data instanceof DataCollection 
                ? $this->data->toArray()
                : (is_array($this->data) ? $this->data : $this->data->toArray()),
            'pagination' => [
                'current_page' => $this->currentPage,
                'last_page' => $this->lastPage,
                'per_page' => $this->perPage,
                'total' => $this->total,
                'from' => $this->from,
                'to' => $this->to,
                'path' => $this->path,
                'first_page_url' => $this->firstPageUrl,
                'last_page_url' => $this->lastPageUrl,
                'next_page_url' => $this->nextPageUrl,
                'prev_page_url' => $this->prevPageUrl,
                'links' => $this->links,
            ],
            'metadata' => $this->metadata,
        ];
    }
}