<?php

namespace Colame\AiDiscovery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class DiscoverySession extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'session_uuid',
        'restaurant_context',
        'conversation_history',
        'extracted_data',
        'confidence_scores',
        'status',
        'messages_count',
        'tokens_used',
    ];

    protected $casts = [
        'restaurant_context' => 'array',
        'conversation_history' => 'array',
        'extracted_data' => 'array',
        'confidence_scores' => 'array',
        'messages_count' => 'integer',
        'tokens_used' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
        'messages_count' => 0,
        'tokens_used' => 0,
        'conversation_history' => '[]',
        'extracted_data' => '{"variants":[],"modifiers":[],"metadata":[]}',
        'confidence_scores' => '{}',
    ];

    /**
     * Get the user that owns the session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for abandoned sessions
     */
    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    /**
     * Get average confidence score
     */
    public function getAverageConfidenceAttribute(): float
    {
        if (empty($this->confidence_scores)) {
            return 0.0;
        }

        $scores = array_values($this->confidence_scores);
        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if session is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Mark session as completed
     */
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark session as abandoned
     */
    public function markAbandoned(): void
    {
        $this->update(['status' => 'abandoned']);
    }

    /**
     * Add message to conversation history
     */
    public function addMessage(array $message): void
    {
        $history = $this->conversation_history;
        $history[] = $message;

        $this->update([
            'conversation_history' => $history,
            'messages_count' => $this->messages_count + 1,
        ]);
    }

    /**
     * Update extracted data
     */
    public function updateExtractedData(array $data): void
    {
        $current = $this->extracted_data;

        if (isset($data['variants'])) {
            $current['variants'] = array_merge($current['variants'] ?? [], $data['variants']);
        }

        if (isset($data['modifiers'])) {
            $current['modifiers'] = array_merge($current['modifiers'] ?? [], $data['modifiers']);
        }

        if (isset($data['metadata'])) {
            $current['metadata'] = array_merge($current['metadata'] ?? [], $data['metadata']);
        }

        $this->update(['extracted_data' => $current]);
    }

    /**
     * Get extracted variants
     */
    public function getVariantsAttribute(): array
    {
        return $this->extracted_data['variants'] ?? [];
    }

    /**
     * Get extracted modifiers
     */
    public function getModifiersAttribute(): array
    {
        return $this->extracted_data['modifiers'] ?? [];
    }

    /**
     * Get metadata
     */
    public function getMetadataAttribute(): array
    {
        return $this->extracted_data['metadata'] ?? [];
    }
}