<?php

declare(strict_types=1);

namespace Colame\Menu\Contracts;

use Colame\Menu\Data\MenuData;
use Colame\Menu\Data\MenuWithRelationsData;
use Spatie\LaravelData\DataCollection;

interface MenuRepositoryInterface
{
    /**
     * Find a menu by ID
     */
    public function find(int $id): ?MenuData;
    
    /**
     * Find a menu by ID with all relations
     */
    public function findWithRelations(int $id): ?MenuWithRelationsData;
    
    /**
     * Find a menu by slug
     */
    public function findBySlug(string $slug): ?MenuData;
    
    /**
     * Get all menus
     */
    public function all(): DataCollection;
    
    /**
     * Get all active menus
     */
    public function getActive(): DataCollection;
    
    /**
     * Get menus by type
     */
    public function getByType(string $type): DataCollection;
    
    /**
     * Get menus for a specific location
     */
    public function getByLocation(int $locationId): DataCollection;
    
    /**
     * Get the default menu
     */
    public function getDefault(): ?MenuData;
    
    /**
     * Get currently available menus
     */
    public function getCurrentlyAvailable(): DataCollection;
    
    /**
     * Create a new menu
     */
    public function create(array $data): MenuData;
    
    /**
     * Update a menu
     */
    public function update(int $id, array $data): MenuData;
    
    /**
     * Delete a menu
     */
    public function delete(int $id): bool;
    
    /**
     * Activate a menu
     */
    public function activate(int $id): bool;
    
    /**
     * Deactivate a menu
     */
    public function deactivate(int $id): bool;
    
    /**
     * Set as default menu
     */
    public function setAsDefault(int $id): bool;
    
    /**
     * Clone a menu
     */
    public function clone(int $id, string $newName): MenuData;
}