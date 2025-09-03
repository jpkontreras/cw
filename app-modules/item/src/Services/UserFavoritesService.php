<?php

namespace Colame\Item\Services;

use Colame\Item\Models\UserItemFavorite;
use Colame\Item\Models\Item;
use Colame\Item\Data\ItemSearchData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class UserFavoritesService
{
    /**
     * Get user's favorite items.
     */
    public function getFavorites(int $userId, int $limit = 20): Collection
    {
        $favorites = UserItemFavorite::with('item')
            ->where('user_id', $userId)
            ->orderBy('order_position')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
        
        return $favorites->map(function ($favorite) {
            $item = $favorite->item;
            if (!$item) return null;
            
            return ItemSearchData::from([
                'id' => $item->id,
                'name' => $item->name,
                'basePrice' => $item->base_price,
                'category' => $item->category,
                'description' => $item->description,
                'sku' => $item->sku,
                'isAvailable' => $item->is_available,
                'isActive' => $item->is_active,
                'preparationTime' => $item->preparation_time,
                'stockQuantity' => $item->stock_quantity,
                'image' => $item->image,
                'isFavorite' => true,
                'orderFrequency' => $item->order_frequency ?? 0,
            ]);
        })->filter();
    }
    
    /**
     * Toggle favorite status for an item.
     */
    public function toggleFavorite(int $userId, int $itemId): array
    {
        $favorite = UserItemFavorite::where('user_id', $userId)
            ->where('item_id', $itemId)
            ->first();
        
        if ($favorite) {
            $favorite->delete();
            return ['is_favorite' => false, 'message' => 'Item removed from favorites'];
        }
        
        UserItemFavorite::create([
            'user_id' => $userId,
            'item_id' => $itemId,
            'order_position' => $this->getNextOrderPosition($userId),
        ]);
        
        return ['is_favorite' => true, 'message' => 'Item added to favorites'];
    }
    
    /**
     * Add item to favorites.
     */
    public function addFavorite(int $userId, int $itemId): bool
    {
        $exists = UserItemFavorite::where('user_id', $userId)
            ->where('item_id', $itemId)
            ->exists();
        
        if (!$exists) {
            UserItemFavorite::create([
                'user_id' => $userId,
                'item_id' => $itemId,
                'order_position' => $this->getNextOrderPosition($userId),
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove item from favorites.
     */
    public function removeFavorite(int $userId, int $itemId): bool
    {
        return UserItemFavorite::where('user_id', $userId)
            ->where('item_id', $itemId)
            ->delete() > 0;
    }
    
    /**
     * Check if item is favorite.
     */
    public function isFavorite(int $userId, int $itemId): bool
    {
        return UserItemFavorite::where('user_id', $userId)
            ->where('item_id', $itemId)
            ->exists();
    }
    
    /**
     * Update order position of favorites.
     */
    public function updateOrder(int $userId, array $itemIds): void
    {
        foreach ($itemIds as $position => $itemId) {
            UserItemFavorite::where('user_id', $userId)
                ->where('item_id', $itemId)
                ->update(['order_position' => $position]);
        }
    }
    
    /**
     * Get the next order position for a user's favorites.
     */
    private function getNextOrderPosition(int $userId): int
    {
        $maxPosition = UserItemFavorite::where('user_id', $userId)
            ->max('order_position');
        
        return $maxPosition ? $maxPosition + 1 : 0;
    }
    
    /**
     * Get user's favorite items (alias for getFavorites).
     */
    public function getUserFavorites(int $userId, int $limit = 50): Collection
    {
        $favorites = UserItemFavorite::with('item')
            ->where('user_id', $userId)
            ->orderBy('order_position')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
        
        return $favorites->map(function ($favorite) {
            return $favorite->item;
        })->filter()->values();
    }
    
    /**
     * Get recent item IDs for user.
     */
    public function getRecentItemIds(int $userId, int $limit = 50): array
    {
        // PostgreSQL requires ORDER BY columns to be in SELECT when using DISTINCT
        // So we need to get the latest entry for each item_id
        $recentItemIds = DB::table('user_recent_items')
            ->select('item_id', DB::raw('MAX(created_at) as latest_interaction'))
            ->where('user_id', $userId)
            ->groupBy('item_id')
            ->orderByDesc('latest_interaction')
            ->limit($limit)
            ->pluck('item_id');
        
        return $recentItemIds->toArray();
    }
    
    /**
     * Get recent items for user.
     */
    public function getRecentItems(int $userId, int $limit = 10): Collection
    {
        // PostgreSQL requires ORDER BY columns to be in SELECT when using DISTINCT
        // So we need to get the latest entry for each item_id
        $recentItemIds = DB::table('user_recent_items')
            ->select('item_id', DB::raw('MAX(created_at) as latest_interaction'))
            ->where('user_id', $userId)
            ->groupBy('item_id')
            ->orderByDesc('latest_interaction')
            ->limit($limit)
            ->pluck('item_id');
        
        if ($recentItemIds->isEmpty()) {
            return collect();
        }
        
        // Get items in the same order as recent interactions
        $items = Item::whereIn('id', $recentItemIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');
        
        // Return in the order they were viewed/added
        return $recentItemIds->map(function ($itemId) use ($items) {
            $item = $items->get($itemId);
            if (!$item) return null;
            
            return ItemSearchData::from([
                'id' => $item->id,
                'name' => $item->name,
                'basePrice' => $item->base_price,
                'category' => $item->category,
                'description' => $item->description,
                'sku' => $item->sku,
                'isAvailable' => $item->is_available,
                'isActive' => $item->is_active,
                'preparationTime' => $item->preparation_time,
                'stockQuantity' => $item->stock_quantity,
                'image' => $item->image,
                'orderFrequency' => $item->order_frequency ?? 0,
            ]);
        })->filter();
    }
    
    /**
     * Track item interaction (view, add, search).
     */
    public function trackItemInteraction(int $userId, int $itemId, string $type = 'viewed'): void
    {
        DB::table('user_recent_items')->insert([
            'user_id' => $userId,
            'item_id' => $itemId,
            'interaction_type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Keep only last 50 interactions per user to prevent table bloat
        $oldInteractions = DB::table('user_recent_items')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->skip(50)
            ->take(PHP_INT_MAX)
            ->pluck('id');
        
        if ($oldInteractions->isNotEmpty()) {
            DB::table('user_recent_items')
                ->whereIn('id', $oldInteractions)
                ->delete();
        }
    }
    
    /**
     * Clear recent items for user.
     */
    public function clearRecentItems(int $userId): bool
    {
        return DB::table('user_recent_items')
            ->where('user_id', $userId)
            ->delete() > 0;
    }
    
    /**
     * Get recent searches for user (legacy - kept for compatibility).
     */
    public function getRecentSearches(int $userId, int $limit = 10): Collection
    {
        return DB::table('search_logs')
            ->where('user_id', $userId)
            ->whereNotNull('query')
            ->where('query', '!=', '')
            ->select('query')
            ->distinct()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('query');
    }
    
    /**
     * Record a search query.
     */
    public function recordSearch(int $userId, string $query, ?string $sessionId = null, ?string $ipAddress = null): string
    {
        $searchId = \Str::uuid()->toString();
        
        DB::table('search_logs')->insert([
            'id' => $searchId,
            'query' => $query,
            'user_id' => $userId,
            'session_id' => $sessionId ?? session()->getId(),
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return $searchId;
    }
    
    /**
     * Clear recent searches for user.
     */
    public function clearRecentSearches(int $userId): bool
    {
        return DB::table('search_logs')
            ->where('user_id', $userId)
            ->delete() > 0;
    }
    
    /**
     * Get popular items among user's favorites.
     */
    public function getPopularFavorites(int $limit = 10): Collection
    {
        $popularItemIds = UserItemFavorite::select('item_id')
            ->selectRaw('COUNT(*) as favorite_count')
            ->groupBy('item_id')
            ->orderByDesc('favorite_count')
            ->limit($limit)
            ->pluck('item_id');
        
        return Item::whereIn('id', $popularItemIds)
            ->where('is_active', true)
            ->where('is_available', true)
            ->get()
            ->map(function ($item) {
                return ItemSearchData::from([
                    'id' => $item->id,
                    'name' => $item->name,
                    'basePrice' => $item->base_price,
                    'category' => $item->category,
                    'description' => $item->description,
                    'sku' => $item->sku,
                    'isAvailable' => $item->is_available,
                    'isActive' => $item->is_active,
                    'preparationTime' => $item->preparation_time,
                    'stockQuantity' => $item->stock_quantity,
                    'image' => $item->image,
                    'isPopular' => true,
                    'orderFrequency' => $item->order_frequency ?? 0,
                ]);
            });
    }
}