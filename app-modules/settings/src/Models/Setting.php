<?php

declare(strict_types=1);

namespace Colame\Settings\Models;

use Colame\Settings\Enums\SettingCategory;
use Colame\Settings\Enums\SettingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'label',
        'description',
        'group',
        'options',
        'validation',
        'default_value',
        'is_required',
        'is_public',
        'is_encrypted',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'type' => SettingType::class,
        'category' => SettingCategory::class,
        'options' => 'array',
        'validation' => 'array',
        'metadata' => 'array',
        'is_required' => 'boolean',
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the value attribute
     */
    public function getValueAttribute($value): mixed
    {
        if ($this->is_encrypted && $value !== null) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, return null
                return null;
            }
        }

        if ($value === null) {
            return $this->default_value;
        }

        return $this->castValue($value);
    }

    /**
     * Set the value attribute
     */
    public function setValueAttribute($value): void
    {
        if ($value !== null && $this->is_encrypted) {
            $value = Crypt::encryptString((string) $value);
        } elseif ($value !== null) {
            $value = $this->prepareValueForStorage($value);
        }

        $this->attributes['value'] = $value;
    }

    /**
     * Cast value based on type
     */
    protected function castValue($value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            SettingType::BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingType::INTEGER => (int) $value,
            SettingType::FLOAT => (float) $value,
            SettingType::JSON, SettingType::ARRAY, SettingType::MULTISELECT => is_string($value) 
                ? json_decode($value, true) 
                : $value,
            SettingType::DATE => $value instanceof \DateTimeInterface 
                ? $value->format('Y-m-d')
                : $value,
            SettingType::DATETIME => $value instanceof \DateTimeInterface 
                ? $value->format('Y-m-d H:i:s')
                : $value,
            SettingType::TIME => $value instanceof \DateTimeInterface 
                ? $value->format('H:i:s')
                : $value,
            default => $value,
        };
    }

    /**
     * Prepare value for storage in database
     */
    protected function prepareValueForStorage($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            SettingType::BOOLEAN => $value ? '1' : '0',
            SettingType::JSON, SettingType::ARRAY, SettingType::MULTISELECT => is_array($value) 
                ? json_encode($value) 
                : $value,
            SettingType::DATE, SettingType::DATETIME, SettingType::TIME => $value instanceof \DateTimeInterface 
                ? $value->format('Y-m-d H:i:s') 
                : $value,
            default => (string) $value,
        };
    }

    /**
     * Scope for getting settings by category
     */
    public function scopeByCategory($query, SettingCategory $category)
    {
        return $query->where('category', $category->value);
    }

    /**
     * Scope for getting public settings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for getting required settings
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope for ordering by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('category')
            ->orderBy('group')
            ->orderBy('sort_order')
            ->orderBy('key');
    }

    /**
     * Get typed value
     */
    public function getTypedValue(): mixed
    {
        return $this->value;
    }

    /**
     * Check if setting has a value
     */
    public function hasValue(): bool
    {
        return $this->value !== null && $this->value !== '' && $this->value !== [];
    }

    /**
     * Reset to default value
     */
    public function resetToDefault(): void
    {
        $this->value = null;
        $this->save();
    }
}