<?php

declare(strict_types=1);

namespace Colame\Location\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class LocationSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'location_id',
        'key',
        'value',
        'type',
        'description',
        'is_encrypted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get the location that owns the setting.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the decrypted value if encrypted.
     */
    public function getValue()
    {
        $value = $this->value;

        // Decrypt if encrypted
        if ($this->is_encrypted && $value !== null) {
            $value = Crypt::decryptString($value);
        }

        // Cast based on type
        return $this->castValue($value);
    }

    /**
     * Set the value, encrypting if necessary.
     */
    public function setValue($value): void
    {
        // Convert to string representation
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } else {
            $value = (string) $value;
        }

        // Encrypt if needed
        if ($this->is_encrypted) {
            $value = Crypt::encryptString($value);
        }

        $this->value = $value;
    }

    /**
     * Cast the value based on its type.
     */
    protected function castValue($value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                
            case 'integer':
                return (int) $value;
                
            case 'float':
            case 'decimal':
                return (float) $value;
                
            case 'json':
            case 'array':
                return json_decode($value, true);
                
            case 'object':
                return json_decode($value);
                
            default:
                return $value;
        }
    }

    /**
     * Scope to get settings by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope to get encrypted settings.
     */
    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Check if the setting should be encrypted based on key patterns.
     */
    public static function shouldEncrypt(string $key): bool
    {
        $encryptedPatterns = [
            'password',
            'secret',
            'token',
            'api_key',
            'private_key',
            'credential',
        ];

        foreach ($encryptedPatterns as $pattern) {
            if (stripos($key, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}