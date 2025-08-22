<?php

declare(strict_types=1);

namespace Colame\Onboarding\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProgress extends Model
{
    protected $table = 'onboarding_progress';
    
    protected $fillable = [
        'user_id',
        'step',
        'completed_steps',
        'data',
        'is_completed',
        'completed_at',
        'skip_reason',
    ];
    
    protected $casts = [
        'completed_steps' => 'array',
        'data' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function hasCompletedStep(string $stepIdentifier): bool
    {
        return in_array($stepIdentifier, $this->completed_steps ?? []);
    }
    
    public function addCompletedStep(string $stepIdentifier): void
    {
        $steps = $this->completed_steps ?? [];
        if (!in_array($stepIdentifier, $steps)) {
            $steps[] = $stepIdentifier;
            $this->completed_steps = $steps;
        }
    }
    
    public function getStepData(string $stepIdentifier): ?array
    {
        return $this->data[$stepIdentifier] ?? null;
    }
    
    public function setStepData(string $stepIdentifier, array $stepData): void
    {
        $data = $this->data ?? [];
        $data[$stepIdentifier] = $stepData;
        $this->data = $data;
    }
    
    public function markAsCompleted(): void
    {
        $this->is_completed = true;
        $this->completed_at = now();
    }
    
    public function getProgressPercentage(): int
    {
        $totalSteps = 4; // Account, Business, Location, Configuration
        $completedCount = count($this->completed_steps ?? []);
        
        return (int) (($completedCount / $totalSteps) * 100);
    }
}