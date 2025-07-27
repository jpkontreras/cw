<?php

declare(strict_types=1);

namespace App\Core\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * DTO for filter metadata configuration
 */
class FilterMetadata extends Data
{
    /**
     * @param DataCollection<int, FilterOptionData> $options
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly string $filterType,
        public readonly ?string $placeholder = null,
        public readonly ?string $icon = null,
        public readonly ?string $width = null,
        #[DataCollectionOf(FilterOptionData::class)]
        public readonly ?DataCollection $options = null,
        public readonly bool $sortable = false,
        public readonly bool $searchable = false,
        public readonly bool $required = false,
        public readonly ?int $debounceMs = null,
        public readonly ?int $maxItems = null,
        public readonly ?array $presets = null,
        public readonly ?string $format = null,
        public readonly ?string $minDate = null,
        public readonly ?string $maxDate = null,
        public readonly array $validation = [],
    ) {}

    /**
     * Create a search filter
     */
    public static function search(
        string $key,
        string $label,
        ?string $placeholder = null,
        int $debounceMs = 300
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'string',
            filterType: 'search',
            placeholder: $placeholder ?? "Search {$label}...",
            debounceMs: $debounceMs,
            searchable: true,
        );
    }

    /**
     * Create a select filter
     */
    public static function select(
        string $key,
        string $label,
        array $options,
        ?string $placeholder = null
    ): self {
        $optionData = FilterOptionData::collect(
            array_map(fn($opt) => FilterOptionData::from($opt), $options),
            DataCollection::class
        );

        return new self(
            key: $key,
            label: $label,
            type: 'enum',
            filterType: 'select',
            placeholder: $placeholder ?? "All {$label}",
            options: $optionData,
            sortable: true,
        );
    }

    /**
     * Create a multi-select filter
     */
    public static function multiSelect(
        string $key,
        string $label,
        array $options,
        ?string $placeholder = null,
        ?int $maxItems = null
    ): self {
        $optionData = FilterOptionData::collect(
            array_map(fn($opt) => FilterOptionData::from($opt), $options),
            DataCollection::class
        );

        return new self(
            key: $key,
            label: $label,
            type: 'enum',
            filterType: 'multi-select',
            placeholder: $placeholder ?? "Filter by {$label}",
            options: $optionData,
            maxItems: $maxItems,
            sortable: true,
        );
    }

    /**
     * Create a date filter
     */
    public static function date(
        string $key,
        string $label,
        ?array $presets = null,
        ?string $format = null
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'date',
            filterType: 'date',
            placeholder: 'Select date',
            presets: $presets,
            format: $format,
            sortable: true,
        );
    }

    /**
     * Create a date range filter
     */
    public static function dateRange(
        string $key,
        string $label,
        ?array $presets = null,
        ?string $format = null
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'date',
            filterType: 'date-range',
            placeholder: 'Select date range',
            presets: $presets,
            format: $format,
            sortable: true,
        );
    }

    /**
     * Create a boolean filter
     */
    public static function boolean(
        string $key,
        string $label,
        string $trueLabel = 'Yes',
        string $falseLabel = 'No'
    ): self {
        $options = FilterOptionData::collect([
            FilterOptionData::from(['value' => 'true', 'label' => $trueLabel]),
            FilterOptionData::from(['value' => 'false', 'label' => $falseLabel]),
        ], DataCollection::class);

        return new self(
            key: $key,
            label: $label,
            type: 'boolean',
            filterType: 'select',
            options: $options,
            sortable: true,
        );
    }
}