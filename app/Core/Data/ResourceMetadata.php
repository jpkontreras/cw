<?php

declare(strict_types=1);

namespace App\Core\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * DTO for complete resource metadata
 */
class ResourceMetadata extends Data
{
    /**
     * @param DataCollection<string, ColumnMetadata> $columns
     * @param array<string> $defaultFilters
     * @param array<FilterPresetData> $filterPresets
     * @param array<int> $perPageOptions
     */
    public function __construct(
        #[DataCollectionOf(ColumnMetadata::class)]
        public readonly DataCollection $columns,
        public readonly array $defaultFilters = [],
        public readonly ?string $defaultSort = null,
        public readonly array $filterPresets = [],
        public readonly array $exportFormats = ['csv', 'excel'],
        public readonly array $actions = [],
        public readonly array $bulkActions = [],
        public readonly array $settings = [],
        public readonly array $perPageOptions = [10, 20, 50, 100],
        public readonly int $defaultPerPage = 20,
        public readonly bool $rowActions = false,
    ) {}

    /**
     * Get columns that have filters
     * 
     * @return array<FilterMetadata>
     */
    public function getFilters(): array
    {
        return $this->columns
            ->toCollection()
            ->filter(fn(ColumnMetadata $column) => $column->filter !== null)
            ->map(fn(ColumnMetadata $column) => $column->filter)
            ->values()
            ->toArray();
    }

    /**
     * Get searchable columns
     * 
     * @return array<string>
     */
    public function getSearchableColumns(): array
    {
        return $this->columns
            ->toCollection()
            ->filter(fn(ColumnMetadata $column) => $column->searchable)
            ->map(fn(ColumnMetadata $column) => $column->key)
            ->values()
            ->toArray();
    }

    /**
     * Get sortable columns
     * 
     * @return array<string>
     */
    public function getSortableColumns(): array
    {
        return $this->columns
            ->toCollection()
            ->filter(fn(ColumnMetadata $column) => $column->sortable)
            ->map(fn(ColumnMetadata $column) => $column->key)
            ->values()
            ->toArray();
    }

    /**
     * Convert to array with proper structure
     */
    public function toArray(): array
    {
        return [
            'columns' => $this->columns->toArray(),
            'filters' => $this->getFilters(),
            'defaultFilters' => $this->defaultFilters,
            'defaultSort' => $this->defaultSort,
            'filterPresets' => $this->filterPresets,
            'exportFormats' => $this->exportFormats,
            'actions' => $this->actions,
            'bulkActions' => $this->bulkActions,
            'settings' => array_merge([
                'searchableColumns' => $this->getSearchableColumns(),
                'sortableColumns' => $this->getSortableColumns(),
            ], $this->settings),
            'perPageOptions' => $this->perPageOptions,
            'defaultPerPage' => $this->defaultPerPage,
            'rowActions' => $this->rowActions,
        ];
    }
}