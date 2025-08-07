<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\MenuVersionData;
use Spatie\LaravelData\DataCollection;

interface MenuVersioningInterface
{
    /**
     * Create a new version of a menu
     */
    public function createVersion(int $menuId, string $changeType, ?string $description = null): MenuVersionData;
    
    /**
     * Get all versions for a menu
     */
    public function getVersions(int $menuId): DataCollection;
    
    /**
     * Get a specific version
     */
    public function getVersion(int $versionId): ?MenuVersionData;
    
    /**
     * Get the latest version for a menu
     */
    public function getLatestVersion(int $menuId): ?MenuVersionData;
    
    /**
     * Get the current published version
     */
    public function getPublishedVersion(int $menuId): ?MenuVersionData;
    
    /**
     * Publish a version
     */
    public function publishVersion(int $versionId): bool;
    
    /**
     * Archive a version
     */
    public function archiveVersion(int $versionId): bool;
    
    /**
     * Restore menu from a specific version
     */
    public function restoreFromVersion(int $versionId): bool;
    
    /**
     * Compare two versions
     */
    public function compareVersions(int $versionId1, int $versionId2): array;
    
    /**
     * Delete old versions (keep specified number of recent versions)
     */
    public function pruneOldVersions(int $menuId, int $keepCount = 10): int;
}