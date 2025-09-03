import React from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, Plus, Star, Hash, Clock, TrendingUp, Package2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface SearchResult {
  id: number;
  name: string;
  price: number;
  category?: string;
  image?: string;
  description?: string;
  preparationTime?: number;
  matchReason?: 'exact' | 'fuzzy' | 'category' | 'recent';
  searchScore?: number;
}

interface Category {
  id: string;
  name: string;
  icon: any;
  color: string;
  emoji: string;
}

interface SearchViewProps {
  searchQuery: string;
  searchResults: SearchResult[];
  isSearching: boolean;
  favoriteItems: SearchResult[];
  recentSearches: string[];
  popularItems: SearchResult[];
  onAddItem: (item: SearchResult) => void;
  onToggleFavorite: (item: SearchResult) => void;
  onSearch: (query: string) => void;
  onCategorySelect: (categoryId: string) => void;
}

export const SearchView: React.FC<SearchViewProps> = ({
  searchQuery,
  searchResults,
  isSearching,
  favoriteItems,
  recentSearches,
  popularItems,
  onAddItem,
  onToggleFavorite,
  onSearch,
  onCategorySelect,
}) => {
  const categories: Category[] = [
    { id: 'empanadas', name: 'Empanadas', icon: Package2, color: 'from-orange-400 to-orange-600', emoji: '🥟' },
    { id: 'completos', name: 'Completos', icon: Package2, color: 'from-red-400 to-red-600', emoji: '🌭' },
    { id: 'pizzas', name: 'Pizzas', icon: Package2, color: 'from-yellow-400 to-yellow-600', emoji: '🍕' },
    { id: 'ensaladas', name: 'Ensaladas', icon: Package2, color: 'from-green-400 to-green-600', emoji: '🥗' },
    { id: 'bebidas', name: 'Bebidas', icon: Package2, color: 'from-blue-400 to-blue-600', emoji: '🥤' },
    { id: 'postres', name: 'Postres', icon: Package2, color: 'from-purple-400 to-purple-600', emoji: '🍰' },
  ];

  const isFavorite = (item: SearchResult) => {
    return favoriteItems.some(fav => fav.id === item.id);
  };

  // Show search results when searching
  if (searchQuery) {
    return (
      <div className="space-y-4">
        {isSearching ? (
          <div className="flex items-center justify-center py-12">
            <div className="animate-pulse flex items-center gap-2">
              <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" />
              <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce [animation-delay:0.1s]" />
              <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce [animation-delay:0.2s]" />
            </div>
          </div>
        ) : searchResults.length > 0 ? (
          <div className="space-y-4">
            <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
              <Search className="h-5 w-5 text-blue-500" />
              Resultados de búsqueda
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {searchResults.map((item) => (
                <div
                  key={item.id}
                  className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col"
                >
                  <div className="p-4 flex-1 flex flex-col">
                    <div className="flex items-start justify-between mb-3">
                      <div className="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-lg flex items-center justify-center">
                        <Package2 className="h-10 w-10 text-gray-400" />
                      </div>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={(e) => {
                          e.stopPropagation();
                          onToggleFavorite(item);
                        }}
                        className="h-8 w-8 -mr-2 -mt-2"
                      >
                        <Star className={cn(
                          "h-4 w-4",
                          isFavorite(item) ? "fill-yellow-400 text-yellow-400" : "text-gray-400"
                        )} />
                      </Button>
                    </div>
                    
                    <div className="flex-1">
                      <h4 className="font-semibold text-gray-900 dark:text-white mb-1 line-clamp-2">
                        {item.name}
                      </h4>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        {item.category || 'Uncategorized'}
                      </p>
                    </div>
                    
                    <div className="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-3">
                      <div className="flex items-center justify-between">
                        <p className="text-xl font-bold text-gray-900 dark:text-white">
                          ${item.price.toLocaleString('es-CL')}
                        </p>
                      </div>
                      <Button 
                        size="sm" 
                        className="w-full bg-blue-500 hover:bg-blue-600 text-white"
                        onClick={() => onAddItem(item)}
                      >
                        <Plus className="h-4 w-4" />
                        <span className="ml-1">Agregar</span>
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ) : (
          <div className="text-center py-12">
            <p className="text-gray-500 dark:text-gray-400">
              No se encontraron resultados para "{searchQuery}"
            </p>
          </div>
        )}
      </div>
    );
  }

  // Default view with categories, favorites, recent searches, popular items
  return (
    <div className="space-y-6">
      {/* Categories */}
      <div>
        <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
          <Hash className="h-5 w-5 text-purple-500" />
          Explorar Categorías
        </h3>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
          {categories.map((category) => (
              <button
                key={category.id}
                onClick={() => onCategorySelect(category.id)}
                className="relative overflow-hidden bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:scale-105 active:scale-95 transition-all duration-200 group"
              >
                <div className={cn(
                  "absolute inset-0 bg-gradient-to-br opacity-10 group-hover:opacity-20 transition-opacity",
                  category.color
                )} />
                <div className="relative flex flex-col items-center justify-center gap-1">
                  <div className="text-3xl mb-1">{category.emoji}</div>
                  <span className="text-xs font-medium text-gray-700 dark:text-gray-300">
                    {category.name}
                  </span>
                </div>
              </button>
          ))}
        </div>
      </div>

      {/* Recent Searches */}
      {recentSearches.length > 0 && (
        <div>
          <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
            <Clock className="h-5 w-5 text-blue-500" />
            Búsquedas Recientes
          </h3>
          <div className="flex flex-wrap gap-2">
            {recentSearches.map((search, idx) => (
              <button
                key={idx}
                onClick={() => onSearch(search)}
                className="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 hover:scale-105 active:scale-95 transition-all duration-200"
              >
                <Clock className="h-3.5 w-3.5 text-gray-400" />
                <span className="text-sm text-gray-700 dark:text-gray-300">{search}</span>
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Favorites */}
      {favoriteItems.length > 0 && (
        <div>
          <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
            <Star className="h-5 w-5 text-yellow-500" />
            Tus Favoritos
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {favoriteItems.slice(0, 6).map((item) => (
              <div
                key={item.id}
                onClick={() => onAddItem(item)}
                className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:scale-[1.02] transition-all duration-200 cursor-pointer"
              >
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-semibold text-gray-900 dark:text-white">
                      {item.name}
                    </h4>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      {item.category}
                    </p>
                  </div>
                  <Star className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                </div>
                <div className="mt-3 flex items-center justify-between">
                  <p className="text-lg font-bold text-gray-900 dark:text-white">
                    ${item.price.toLocaleString('es-CL')}
                  </p>
                  <Button size="sm" className="bg-blue-500 hover:bg-blue-600 text-white">
                    <Plus className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Popular Items */}
      <div>
        <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
          <TrendingUp className="h-5 w-5 text-green-500" />
          Productos Populares
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {popularItems.map((item) => (
            <div
              key={item.id}
              className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 flex flex-col"
            >
              <div className="p-4 flex-1 flex flex-col">
                <div className="flex items-start justify-between mb-3">
                  <Badge variant="secondary" className="text-xs">
                    Popular
                  </Badge>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={(e) => {
                      e.stopPropagation();
                      onToggleFavorite(item);
                    }}
                    className="h-8 w-8 -mr-2 -mt-2"
                  >
                    <Star className={cn(
                      "h-4 w-4",
                      isFavorite(item) ? "fill-yellow-400 text-yellow-400" : "text-gray-400"
                    )} />
                  </Button>
                </div>
                
                <div className="flex-1">
                  <h4 className="font-semibold text-gray-900 dark:text-white mb-1 line-clamp-2">
                    {item.name}
                  </h4>
                  <p className="text-sm text-gray-500 dark:text-gray-400">
                    {item.category || 'Uncategorized'}
                  </p>
                </div>
                
                <div className="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-3">
                  <div className="flex items-center justify-between">
                    <p className="text-xl font-bold text-gray-900 dark:text-white">
                      ${item.price.toLocaleString('es-CL')}
                    </p>
                  </div>
                  <Button 
                    size="sm" 
                    className="w-full bg-blue-500 hover:bg-blue-600 text-white"
                    onClick={() => onAddItem(item)}
                  >
                    <Plus className="h-4 w-4" />
                    <span className="ml-1">Agregar</span>
                  </Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};