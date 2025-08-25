<?php

declare(strict_types=1);

namespace Colame\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxonomyAttribute extends Model
{
    protected $fillable = [
        'taxonomy_id',
        'key',
        'value',
        'type',
    ];
    
    protected $casts = [
        'taxonomy_id' => 'integer',
    ];
    
    /**
     * Get the taxonomy this attribute belongs to
     */
    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }
    
    /**
     * Get the typed value based on the type field
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'number' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }
    
    /**
     * Set the value with proper type conversion
     */
    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            'json' => json_encode($value),
            'boolean' => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}