import React, { useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Search, X, ChevronLeft } from 'lucide-react';
import { cn } from '@/lib/utils';

interface SearchInputProps {
  searchQuery: string;
  setSearchQuery: (query: string) => void;
  isSearchMode: boolean;
  setIsSearchMode: (mode: boolean) => void;
}

export const SearchInput: React.FC<SearchInputProps> = ({
  searchQuery,
  setSearchQuery,
  isSearchMode,
  setIsSearchMode,
}) => {
  const searchInputRef = useRef<HTMLInputElement>(null);

  return (
    <div 
      className={cn(
        "relative transition-transform duration-200 ease-out",
        isSearchMode ? "scale-[1.02]" : "scale-100"
      )}
    >
      <div className="absolute inset-0 bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 rounded-2xl blur opacity-20 hover:opacity-30 transition-opacity" />
      <div className="relative flex items-center">
        {isSearchMode && (
          <Button
            variant="ghost"
            size="icon"
            onClick={() => {
              setIsSearchMode(false);
              setSearchQuery('');
            }}
            className="absolute left-2 h-9 w-9 z-10"
          >
            <ChevronLeft className="h-5 w-5" />
          </Button>
        )}
        <Search className={cn(
          "absolute h-5 w-5 text-gray-400 z-10 transition-all duration-200",
          isSearchMode ? "left-14" : "left-4"
        )} />
        <Input
          ref={searchInputRef}
          type="text"
          placeholder="Buscar productos o explorar categorías..."
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          onFocus={() => setIsSearchMode(true)}
          className={cn(
            "w-full h-14 text-base bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 rounded-2xl shadow-sm transition-all duration-200 focus:shadow-lg",
            isSearchMode ? "pl-24 pr-32" : "pl-12 pr-32"
          )}
          autoComplete="off"
        />
        <div className="absolute right-2 flex items-center gap-2">
          {searchQuery && (
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setSearchQuery('')}
              className="h-9 w-9"
            >
              <X className="h-4 w-4" />
            </Button>
          )}
          <Badge className="bg-gradient-to-r from-blue-500 to-purple-500 text-white border-0">
            Beta
          </Badge>
        </div>
      </div>
    </div>
  );
};