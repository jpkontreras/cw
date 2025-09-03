import React from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  X, 
  ChevronDown, 
  Filter,
  Check,
  DollarSign,
  Package,
  Tag
} from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuGroup,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { SearchFilters } from '../contexts/OrderContext';

interface FilterBarProps {
  filters: SearchFilters;
  activeCount: number;
  onFilterChange: (key: keyof SearchFilters, value: any) => void;
  onClearFilters: () => void;
  categories?: string[];
}

export const FilterBar: React.FC<FilterBarProps> = ({
  filters,
  activeCount,
  onFilterChange,
  onClearFilters,
  categories = []
}) => {
  const priceRanges = [
    { label: 'Hasta $2.000', min: 0, max: 2000 },
    { label: '$2.000 - $5.000', min: 2000, max: 5000 },
    { label: '$5.000 - $10.000', min: 5000, max: 10000 },
    { label: 'Más de $10.000', min: 10000, max: 999999 },
  ];

  const handlePriceRangeSelect = (min: number | null, max: number | null) => {
    onFilterChange('min_price', min === 0 ? null : min);
    onFilterChange('max_price', max === 999999 ? null : max);
  };

  const getCurrentPriceLabel = () => {
    const min = filters.min_price;
    const max = filters.max_price;
    
    if (min === undefined && max === undefined) return null;
    
    const range = priceRanges.find(r => {
      const minMatch = (r.min === 0 && !min) || r.min === min;
      const maxMatch = (r.max === 999999 && !max) || r.max === max;
      return minMatch && maxMatch;
    });
    
    if (range) return range.label;
    
    if (min && max) return `$${min.toLocaleString()} - $${max.toLocaleString()}`;
    if (min) return `Desde $${min.toLocaleString()}`;
    if (max) return `Hasta $${max.toLocaleString()}`;
    
    return 'Rango personalizado';
  };

  const hasActiveFilters = () => {
    return filters.category || 
           filters.min_price !== undefined || 
           filters.max_price !== undefined || 
           filters.is_available;
  };

  return (
    <div className="bg-white dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
      <div className="px-4 py-3">
        <div className="flex items-center gap-2">
          
          {/* Filter Button */}
          <div className="flex items-center gap-1.5">
            <Filter className="h-4 w-4 text-gray-500" />
            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Filtros:</span>
          </div>

          {/* Category Filter */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button 
                variant={filters.category ? "default" : "outline"}
                size="sm" 
                className={cn(
                  "h-8 px-3 font-normal gap-1.5",
                  !filters.category && "text-gray-600 dark:text-gray-400"
                )}
              >
                <Tag className="h-3.5 w-3.5" />
                <span>{filters.category || 'Categoría'}</span>
                <ChevronDown className="h-3 w-3 ml-1 opacity-50" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-56">
              <DropdownMenuLabel>Seleccionar Categoría</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuGroup>
                <DropdownMenuItem 
                  onClick={() => onFilterChange('category', null)}
                  className="justify-between"
                >
                  <span className={cn("flex items-center gap-2", !filters.category && "font-medium")}>
                    Todas las categorías
                  </span>
                  {!filters.category && <Check className="h-4 w-4 text-primary" />}
                </DropdownMenuItem>
                {categories.map(category => (
                  <DropdownMenuItem
                    key={category}
                    onClick={() => onFilterChange('category', category)}
                    className="justify-between"
                  >
                    <span className={cn("flex items-center gap-2", filters.category === category && "font-medium")}>
                      {category}
                    </span>
                    {filters.category === category && <Check className="h-4 w-4 text-primary" />}
                  </DropdownMenuItem>
                ))}
              </DropdownMenuGroup>
            </DropdownMenuContent>
          </DropdownMenu>

          {/* Price Range Filter */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button 
                variant={getCurrentPriceLabel() ? "default" : "outline"}
                size="sm" 
                className={cn(
                  "h-8 px-3 font-normal gap-1.5",
                  !getCurrentPriceLabel() && "text-gray-600 dark:text-gray-400"
                )}
              >
                <DollarSign className="h-3.5 w-3.5" />
                <span>{getCurrentPriceLabel() || 'Precio'}</span>
                <ChevronDown className="h-3 w-3 ml-1 opacity-50" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-56">
              <DropdownMenuLabel>Rango de Precio</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuGroup>
                <DropdownMenuItem 
                  onClick={() => handlePriceRangeSelect(null, null)}
                  className="justify-between"
                >
                  <span className={cn("flex items-center gap-2", 
                    filters.min_price === undefined && filters.max_price === undefined && "font-medium"
                  )}>
                    Todos los precios
                  </span>
                  {filters.min_price === undefined && filters.max_price === undefined && 
                    <Check className="h-4 w-4 text-primary" />}
                </DropdownMenuItem>
                {priceRanges.map((range, idx) => {
                  const isSelected = 
                    ((range.min === 0 && !filters.min_price) || filters.min_price === range.min) &&
                    ((range.max === 999999 && !filters.max_price) || filters.max_price === range.max);
                  
                  return (
                    <DropdownMenuItem
                      key={idx}
                      onClick={() => handlePriceRangeSelect(range.min, range.max)}
                      className="justify-between"
                    >
                      <span className={cn("flex items-center gap-2", isSelected && "font-medium")}>
                        {range.label}
                      </span>
                      {isSelected && <Check className="h-4 w-4 text-primary" />}
                    </DropdownMenuItem>
                  );
                })}
              </DropdownMenuGroup>
            </DropdownMenuContent>
          </DropdownMenu>

          {/* Availability Toggle */}
          <Button
            variant={filters.is_available ? "default" : "outline"}
            size="sm"
            onClick={() => onFilterChange('is_available', !filters.is_available)}
            className={cn(
              "h-8 px-3 font-normal gap-1.5",
              !filters.is_available && "text-gray-600 dark:text-gray-400"
            )}
          >
            <Package className="h-3.5 w-3.5" />
            <span>Disponible</span>
            {filters.is_available && <Check className="h-3.5 w-3.5 ml-1" />}
          </Button>

          {/* Clear Filters */}
          {hasActiveFilters() && (
            <>
              <div className="h-5 w-px bg-gray-300 dark:bg-gray-700 ml-1" />
              <Button
                variant="ghost"
                size="sm"
                onClick={onClearFilters}
                className="h-8 px-2.5 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
              >
                <X className="h-3.5 w-3.5" />
                <span className="ml-1.5">Limpiar filtros</span>
              </Button>
            </>
          )}

          {/* Active count badge */}
          {activeCount > 0 && (
            <Badge variant="secondary" className="ml-auto">
              {activeCount} {activeCount === 1 ? 'filtro activo' : 'filtros activos'}
            </Badge>
          )}
        </div>

        {/* Active Filter Pills - Now showing below */}
        {hasActiveFilters() && (
          <div className="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-800">
            {filters.category && (
              <Badge 
                variant="outline" 
                className="pl-2.5 pr-1 py-1 text-xs font-normal gap-1.5 border-gray-300 dark:border-gray-700"
              >
                <Tag className="h-3 w-3" />
                {filters.category}
                <button
                  onClick={() => onFilterChange('category', null)}
                  className="ml-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full p-0.5 transition-colors"
                >
                  <X className="h-2.5 w-2.5" />
                </button>
              </Badge>
            )}
            {getCurrentPriceLabel() && (
              <Badge 
                variant="outline" 
                className="pl-2.5 pr-1 py-1 text-xs font-normal gap-1.5 border-gray-300 dark:border-gray-700"
              >
                <DollarSign className="h-3 w-3" />
                {getCurrentPriceLabel()}
                <button
                  onClick={() => {
                    onFilterChange('min_price', null);
                    onFilterChange('max_price', null);
                  }}
                  className="ml-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full p-0.5 transition-colors"
                >
                  <X className="h-2.5 w-2.5" />
                </button>
              </Badge>
            )}
            {filters.is_available && (
              <Badge 
                variant="outline" 
                className="pl-2.5 pr-1 py-1 text-xs font-normal gap-1.5 border-gray-300 dark:border-gray-700"
              >
                <Package className="h-3 w-3" />
                Disponible ahora
                <button
                  onClick={() => onFilterChange('is_available', false)}
                  className="ml-1 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full p-0.5 transition-colors"
                >
                  <X className="h-2.5 w-2.5" />
                </button>
              </Badge>
            )}
          </div>
        )}
      </div>
    </div>
  );
};