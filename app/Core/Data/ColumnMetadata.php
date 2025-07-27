<?php

declare(strict_types=1);

namespace App\Core\Data;

use Spatie\LaravelData\Data;

/**
 * DTO for column metadata configuration
 */
class ColumnMetadata extends Data
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly bool $sortable = false,
        public readonly bool $searchable = false,
        public readonly bool $visible = true,
        public readonly bool $exportable = true,
        public readonly ?string $format = null,
        public readonly ?string $align = null,
        public readonly ?string $width = null,
        public readonly ?FilterMetadata $filter = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a text column
     */
    public static function text(
        string $key,
        string $label,
        bool $searchable = true,
        bool $sortable = true
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'string',
            sortable: $sortable,
            searchable: $searchable,
        );
    }

    /**
     * Create a numeric column
     */
    public static function number(
        string $key,
        string $label,
        ?string $format = null,
        bool $sortable = true
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'number',
            format: $format,
            sortable: $sortable,
            align: 'right',
        );
    }

    /**
     * Create a currency column
     */
    public static function currency(
        string $key,
        string $label,
        string $currency = 'CLP',
        bool $sortable = true
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'currency',
            format: $currency,
            sortable: $sortable,
            align: 'right',
        );
    }

    /**
     * Create a date column
     */
    public static function date(
        string $key,
        string $label,
        string $format = 'Y-m-d',
        bool $sortable = true
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'date',
            format: $format,
            sortable: $sortable,
        );
    }

    /**
     * Create a datetime column
     */
    public static function dateTime(
        string $key,
        string $label,
        string $format = 'Y-m-d H:i:s',
        bool $sortable = true
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'datetime',
            format: $format,
            sortable: $sortable,
        );
    }

    /**
     * Create a boolean column
     */
    public static function boolean(
        string $key,
        string $label,
        string $trueLabel = 'Yes',
        string $falseLabel = 'No'
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'boolean',
            metadata: [
                'trueLabel' => $trueLabel,
                'falseLabel' => $falseLabel,
            ],
        );
    }

    /**
     * Create an enum/status column
     */
    public static function enum(
        string $key,
        string $label,
        array $options,
        bool $sortable = true
    ): self {
        return new self(
            key: $key,
            label: $label,
            type: 'enum',
            sortable: $sortable,
            metadata: [
                'options' => $options,
            ],
        );
    }

    /**
     * Add filter configuration to column
     */
    public function withFilter(FilterMetadata $filter): self
    {
        return new self(
            key: $this->key,
            label: $this->label,
            type: $this->type,
            sortable: $this->sortable,
            searchable: $this->searchable,
            visible: $this->visible,
            exportable: $this->exportable,
            format: $this->format,
            align: $this->align,
            width: $this->width,
            filter: $filter,
            metadata: $this->metadata,
        );
    }
}