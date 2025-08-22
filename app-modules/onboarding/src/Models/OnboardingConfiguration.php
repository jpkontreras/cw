<?php

declare(strict_types=1);

namespace Colame\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingConfiguration extends Model
{
    protected $table = 'onboarding_configurations';
    
    protected $fillable = [
        'key',
        'step_identifier',
        'title',
        'description',
        'order',
        'is_required',
        'is_active',
        'validation_rules',
        'metadata',
    ];
    
    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'validation_rules' => 'array',
        'metadata' => 'array',
    ];
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
    
    public function scopeForStep($query, string $stepIdentifier)
    {
        return $query->where('step_identifier', $stepIdentifier);
    }
}