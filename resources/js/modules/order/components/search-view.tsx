import React from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Search, Plus, Star, Hash, Clock, TrendingUp, Package2, X, Heart, Sparkles, ChevronRight, ArrowRight } from 'lucide-react';
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
  recentItems?: SearchResult[];
  popularItems: SearchResult[];
  orderItems?: Array<{ id: number; quantity: number }>;
  onAddItem: (item: SearchResult) => void;
  onUpdateQuantity?: (itemId: number, delta: number) => void;
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
  recentItems = [],
  popularItems,
  orderItems = [],
  onAddItem,
  onUpdateQuantity,
  onToggleFavorite,
  onSearch,
  onCategorySelect,
}) => {
  // Helper function to get item quantity in cart
  const getItemQuantity = (itemId: number): number => {
    const item = orderItems.find(item => item.id === itemId);
    return item?.quantity || 0;
  };

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
                      {getItemQuantity(item.id) > 0 ? (
                        <div className="flex items-center gap-2 animate-in fade-in slide-in-from-bottom-2 duration-200">
                          <Button
                            size="sm"
                            variant="outline"
                            className="flex-1 transition-all hover:scale-105 active:scale-95"
                            onClick={() => onUpdateQuantity?.(item.id, -1)}
                          >
                            <span className="text-lg">−</span>
                          </Button>
                          <div className="flex-1 text-center">
                            <span className="font-bold text-lg bg-secondary text-secondary-foreground px-3 py-1 rounded-lg">{getItemQuantity(item.id)}</span>
                          </div>
                          <Button
                            size="sm"
                            className="flex-1 transition-all hover:scale-105 active:scale-95"
                            onClick={() => onUpdateQuantity?.(item.id, 1)}
                          >
                            <span className="text-lg">+</span>
                          </Button>
                        </div>
                      ) : (
                        <Button 
                          size="sm" 
                          className="w-full transition-all hover:scale-105 active:scale-95"
                          onClick={() => onAddItem(item)}
                        >
                          <Plus className="h-4 w-4" />
                          <span className="ml-1">Agregar</span>
                        </Button>
                      )}
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

      {/* Recent Items */}
      {recentItems.length > 0 && (
        <div className="w-full overflow-hidden">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
              <Clock className="h-5 w-5 text-blue-500" />
              Vistos Recientemente
              <Badge variant="secondary" className="text-xs">
                {recentItems.length}
              </Badge>
            </h3>
            <Button
              variant="ghost"
              size="sm"
              className="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 gap-1"
              onClick={() => onSearch('recent:')}
            >
              Ver todos
              <ArrowRight className="h-3 w-3" />
            </Button>
          </div>
          
          <div className="relative group overflow-hidden">
            <div 
              className="flex gap-3 overflow-x-auto scrollbar-hide scroll-smooth pb-2"
              style={{
                scrollbarWidth: 'none',
                msOverflowStyle: 'none',
                WebkitScrollbar: { display: 'none' }
              }}
            >
              {recentItems.map((item, index) => (
                <div
                  key={item.id}
                  className={cn(
                    "flex-none w-[180px] bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:shadow-md hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer",
                    index === 0 && "ml-0",
                    index === recentItems.length - 1 && "mr-0"
                  )}
                  onClick={() => onAddItem(item)}
                >
                  <div className="h-24 w-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-md flex items-center justify-center mb-2">
                    {item.image ? (
                      <img src={item.image} alt={item.name} className="w-full h-full object-cover rounded-md" />
                    ) : (
                      <Package2 className="h-12 w-12 text-gray-400" />
                    )}
                  </div>
                  
                  <h4 className="text-sm font-semibold text-gray-900 dark:text-white line-clamp-1">
                    {item.name}
                  </h4>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    ${item.price.toLocaleString('es-CL')}
                  </p>
                  
                  <div className="mt-3">
                    {getItemQuantity(item.id) > 0 ? (
                      <div className="flex items-center justify-between gap-1">
                        <Button
                          size="icon"
                          variant="outline"
                          className="h-7 w-7 rounded"
                          onClick={(e) => {
                            e.stopPropagation();
                            onUpdateQuantity?.(item.id, -1);
                          }}
                        >
                          <span className="text-xs">−</span>
                        </Button>
                        <span className="text-sm font-bold">{getItemQuantity(item.id)}</span>
                        <Button
                          size="icon"
                          className="h-7 w-7 rounded"
                          onClick={(e) => {
                            e.stopPropagation();
                            onUpdateQuantity?.(item.id, 1);
                          }}
                        >
                          <span className="text-xs">+</span>
                        </Button>
                      </div>
                    ) : (
                      <Button
                        size="sm"
                        className="w-full h-8"
                        onClick={(e) => {
                          e.stopPropagation();
                          onAddItem(item);
                        }}
                      >
                        <Plus className="h-3 w-3 mr-1" />
                        Agregar
                      </Button>
                    )}
                  </div>
                </div>
              ))}
              
              {/* Show more card */}
              {recentItems.length > 8 && (
                <div
                  className="flex-none w-[180px] bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-600 p-3 flex flex-col items-center justify-center cursor-pointer hover:scale-105 active:scale-95 transition-all duration-200"
                  onClick={() => onSearch('recent:')}
                >
                  <div className="text-center">
                    <div className="bg-blue-500 text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2">
                      <span className="font-bold">+{recentItems.length - 8}</span>
                    </div>
                    <p className="text-sm font-semibold text-blue-700 dark:text-blue-300">Ver más</p>
                    <p className="text-xs text-blue-600 dark:text-blue-400 mt-1">productos recientes</p>
                  </div>
                </div>
              )}
            </div>
            
            {/* Scroll indicators - fade effect on edges */}
            <div className="absolute left-0 top-0 bottom-2 w-8 bg-gradient-to-r from-white dark:from-gray-900 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
            <div className="absolute right-0 top-0 bottom-2 w-8 bg-gradient-to-l from-white dark:from-gray-900 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
          </div>
        </div>
      )}

      {/* Favorites */}
      {favoriteItems.length > 0 && (
        <div className="w-full overflow-hidden">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
              <Heart className="h-5 w-5 text-red-500 fill-red-500" />
              Tus Favoritos
              <Badge className="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                {favoriteItems.length}
              </Badge>
            </h3>
            <Button
              variant="ghost"
              size="sm"
              className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 gap-1"
              onClick={() => onSearch('favorites:')}
            >
              Ver todos
              <ArrowRight className="h-3 w-3" />
            </Button>
          </div>
          
          <div className="relative group overflow-hidden">
            <div 
              className="flex gap-3 overflow-x-auto scrollbar-hide scroll-smooth pb-2"
              style={{
                scrollbarWidth: 'none',
                msOverflowStyle: 'none',
                WebkitScrollbar: { display: 'none' }
              }}
            >
              {favoriteItems.map((item, index) => (
                <div
                  key={item.id}
                  className={cn(
                    "flex-none w-[200px] group relative bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-850 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:scale-[1.02] transition-all duration-300 cursor-pointer",
                    index === 0 && "ml-0",
                    index === favoriteItems.length - 1 && "mr-0"
                  )}
                >
                  {/* Favorite badge */}
                  <div className="absolute top-2 right-2 z-10">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-full hover:bg-white dark:hover:bg-gray-900"
                      onClick={(e) => {
                        e.stopPropagation();
                        onToggleFavorite(item);
                      }}
                    >
                      <Heart className="h-4 w-4 text-red-500 fill-red-500" />
                    </Button>
                  </div>
                  
                  {/* Item image placeholder */}
                  <div className="h-32 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                    {item.image ? (
                      <img src={item.image} alt={item.name} className="w-full h-full object-cover" />
                    ) : (
                      <Package2 className="h-16 w-16 text-gray-400 dark:text-gray-500" />
                    )}
                  </div>
                  
                  <div className="p-3">
                    <h4 className="font-semibold text-sm text-gray-900 dark:text-white line-clamp-1">
                      {item.name}
                    </h4>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                      {item.category || 'Sin categoría'}
                    </p>
                    <div className="mt-3 flex flex-col gap-2">
                      <p className="text-lg font-bold text-gray-900 dark:text-white">
                        ${item.price.toLocaleString('es-CL')}
                      </p>
                      {getItemQuantity(item.id) > 0 ? (
                        <div className="flex items-center justify-between gap-1">
                          <Button
                            size="icon"
                            variant="outline"
                            className="h-7 w-7 rounded-md flex-1"
                            onClick={(e) => {
                              e.stopPropagation();
                              onUpdateQuantity?.(item.id, -1);
                            }}
                          >
                            <span className="text-xs">−</span>
                          </Button>
                          <span className="font-bold text-sm flex-1 text-center">{getItemQuantity(item.id)}</span>
                          <Button
                            size="icon"
                            className="h-7 w-7 rounded-md flex-1"
                            onClick={(e) => {
                              e.stopPropagation();
                              onUpdateQuantity?.(item.id, 1);
                            }}
                          >
                            <span className="text-xs">+</span>
                          </Button>
                        </div>
                      ) : (
                        <Button 
                          size="sm"
                          className="w-full h-8"
                          onClick={(e) => {
                            e.stopPropagation();
                            onAddItem(item);
                          }}
                        >
                          <Plus className="h-3 w-3 mr-1" />
                          Agregar
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
              
              {/* Show more card for favorites */}
              {favoriteItems.length > 6 && (
                <div
                  className="flex-none w-[200px] bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl border-2 border-dashed border-red-300 dark:border-red-600 p-3 flex flex-col items-center justify-center cursor-pointer hover:scale-[1.02] active:scale-95 transition-all duration-300"
                  onClick={() => onSearch('favorites:')}
                >
                  <div className="text-center">
                    <div className="bg-red-500 text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2">
                      <span className="font-bold">+{favoriteItems.length - 6}</span>
                    </div>
                    <p className="text-sm font-semibold text-red-700 dark:text-red-300">Ver más</p>
                    <p className="text-xs text-red-600 dark:text-red-400 mt-1">favoritos</p>
                  </div>
                </div>
              )}
            </div>
            
            {/* Scroll indicators - fade effect on edges */}
            <div className="absolute left-0 top-0 bottom-2 w-8 bg-gradient-to-r from-white dark:from-gray-900 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
            <div className="absolute right-0 top-0 bottom-2 w-8 bg-gradient-to-l from-white dark:from-gray-900 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
          </div>
        </div>
      )}

      {/* Popular Items */}
      <div>
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-green-500" />
            Productos Populares
            <Sparkles className="h-4 w-4 text-amber-500" />
          </h3>
          <Badge variant="outline" className="text-xs border-green-200 text-green-700 dark:border-green-800 dark:text-green-400">
            Más vendidos
          </Badge>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {popularItems.slice(0, 12).map((item) => (
            <div
              key={item.id}
              className="group bg-white dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-green-400 dark:hover:border-green-600 hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden"
            >
              {/* Popular badge */}
              <div className="relative">
                <div className="absolute top-2 left-2 z-10">
                  <Badge className="bg-gradient-to-r from-green-500 to-emerald-500 text-white border-0 text-xs font-bold">
                    🔥 Popular
                  </Badge>
                </div>
                <div className="absolute top-2 right-2 z-10">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm rounded-full hover:bg-white dark:hover:bg-gray-900"
                    onClick={(e) => {
                      e.stopPropagation();
                      onToggleFavorite(item);
                    }}
                  >
                    <Star className={cn(
                      "h-4 w-4 transition-colors",
                      isFavorite(item) ? "fill-yellow-400 text-yellow-400" : "text-gray-400 hover:text-yellow-400"
                    )} />
                  </Button>
                </div>
                
                {/* Image placeholder */}
                <div className="h-32 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                  {item.image ? (
                    <img src={item.image} alt={item.name} className="w-full h-full object-cover" />
                  ) : (
                    <Package2 className="h-16 w-16 text-gray-400 dark:text-gray-500" />
                  )}
                </div>
              </div>
              
              <div className="p-3 flex-1 flex flex-col">
                <div className="flex-1">
                  <h4 className="font-bold text-sm text-gray-900 dark:text-white line-clamp-2">
                    {item.name}
                  </h4>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {item.category || 'Sin categoría'}
                  </p>
                  {item.preparationTime && (
                    <div className="flex items-center gap-1 mt-2">
                      <Clock className="h-3 w-3 text-gray-400" />
                      <span className="text-xs text-gray-500">{item.preparationTime} min</span>
                    </div>
                  )}
                </div>
                
                <div className="mt-3 space-y-2">
                  <p className="text-xl font-bold text-gray-900 dark:text-white">
                    ${item.price.toLocaleString('es-CL')}
                  </p>
                  {getItemQuantity(item.id) > 0 ? (
                    <div className="flex items-center gap-1">
                      <Button
                        size="icon"
                        variant="outline"
                        className="h-8 w-8 rounded-lg"
                        onClick={() => onUpdateQuantity?.(item.id, -1)}
                      >
                        <span className="text-sm">−</span>
                      </Button>
                      <div className="flex-1 text-center">
                        <span className="font-bold text-lg">{getItemQuantity(item.id)}</span>
                      </div>
                      <Button
                        size="icon"
                        className="h-8 w-8 rounded-lg"
                        onClick={() => onUpdateQuantity?.(item.id, 1)}
                      >
                        <span className="text-sm">+</span>
                      </Button>
                    </div>
                  ) : (
                    <Button 
                      size="sm" 
                      className="w-full h-9 font-semibold"
                      onClick={() => onAddItem(item)}
                    >
                      <Plus className="h-4 w-4 mr-1" />
                      Agregar
                    </Button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};