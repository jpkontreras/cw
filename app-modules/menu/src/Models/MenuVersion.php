<?php

declare(strict_types=1);

namespace Colame\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class MenuVersion extends Model
{
    protected $fillable = [
        'menu_id',
        'version_number',
        'version_name',
        'snapshot',
        'change_type',
        'change_description',
        'created_by',
        'published_at',
        'archived_at',
        'metadata',
    ];
    
    protected $casts = [
        'menu_id' => 'integer',
        'version_number' => 'integer',
        'snapshot' => 'array',
        'created_by' => 'integer',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'metadata' => 'array',
    ];
    
    public const CHANGE_CREATED = 'created';
    public const CHANGE_UPDATED = 'updated';
    public const CHANGE_PUBLISHED = 'published';
    public const CHANGE_ARCHIVED = 'archived';
    
    public const VALID_CHANGE_TYPES = [
        self::CHANGE_CREATED,
        self::CHANGE_UPDATED,
        self::CHANGE_PUBLISHED,
        self::CHANGE_ARCHIVED,
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (MenuVersion $version) {
            if (!$version->version_number) {
                $lastVersion = static::where('menu_id', $version->menu_id)
                    ->orderBy('version_number', 'desc')
                    ->first();
                
                $version->version_number = $lastVersion ? $lastVersion->version_number + 1 : 1;
            }
            
            if (!$version->version_name) {
                $version->version_name = "Version {$version->version_number}";
            }
        });
    }
    
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
    
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
    
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
    
    public function publish(): void
    {
        $this->update([
            'published_at' => now(),
            'change_type' => self::CHANGE_PUBLISHED,
        ]);
    }
    
    public function archive(): void
    {
        $this->update([
            'archived_at' => now(),
            'change_type' => self::CHANGE_ARCHIVED,
        ]);
    }
}