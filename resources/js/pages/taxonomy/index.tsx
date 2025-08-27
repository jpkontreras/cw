import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { 
  Plus, 
  Tag, 
  Search, 
  ChevronRight,
  ChevronDown,
  Hash,
  Layers,
  Coffee,
  Utensils,
  MapPin,
  Users,
  Calendar,
  Percent,
  Flame,
  Apple,
  AlertCircle,
  Globe,
  ChefHat,
  DollarSign,
  Star,
  Circle,
  MoreVertical,
  Edit2,
  Trash2,
  Eye,
  EyeOff
} from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Taxonomy {
  id: number;
  name: string;
  slug: string;
  type: string;
  parentId: number | null;
  parent?: {
    id: number;
    name: string;
    slug: string;
  };
  metadata?: {
    icon?: string;
    color?: string;
    description?: string;
    level?: number;
    priority?: number;
    scoville?: string;
    [key: string]: any;
  };
  sortOrder: number;
  isActive: boolean;
  childrenCount?: number;
}

interface TaxonomyIndexProps {
  taxonomies: Taxonomy[];
  pagination: any;
  metadata?: any;
  filters: {
    type?: string;
    search?: string;
    is_active?: string;
    sort?: string;
  };
  types: Array<{ value: string; label: string }>;
}

// Type configuration with icons and descriptions
const typeConfig: Record<string, { 
  icon: React.ElementType; 
  label: string; 
  description: string; 
  color: string;
  bgColor: string;
  borderColor: string;
  isHierarchical?: boolean;
}> = {
  item_category: { 
    icon: Layers, 
    label: 'Item Categories', 
    description: 'Hierarchical categories for organizing menu items',
    color: 'text-blue-600',
    bgColor: 'bg-blue-50',
    borderColor: 'border-blue-200',
    isHierarchical: true
  },
  menu_section: { 
    icon: Utensils, 
    label: 'Menu Sections', 
    description: 'Time-based and special menu sections',
    color: 'text-amber-600',
    bgColor: 'bg-amber-50',
    borderColor: 'border-amber-200',
    isHierarchical: true
  },
  ingredient_type: { 
    icon: Coffee, 
    label: 'Ingredients', 
    description: 'Raw materials and ingredient classifications',
    color: 'text-green-600',
    bgColor: 'bg-green-50',
    borderColor: 'border-green-200',
    isHierarchical: true
  },
  location_zone: { 
    icon: MapPin, 
    label: 'Locations', 
    description: 'Geographic zones and delivery areas',
    color: 'text-cyan-600',
    bgColor: 'bg-cyan-50',
    borderColor: 'border-cyan-200',
    isHierarchical: true
  },
  dietary_label: { 
    icon: Apple, 
    label: 'Dietary Labels', 
    description: 'Dietary restrictions and preferences',
    color: 'text-emerald-600',
    bgColor: 'bg-emerald-50',
    borderColor: 'border-emerald-200'
  },
  allergen: { 
    icon: AlertCircle, 
    label: 'Allergens', 
    description: 'Food allergen information and warnings',
    color: 'text-red-600',
    bgColor: 'bg-red-50',
    borderColor: 'border-red-200'
  },
  cuisine_type: { 
    icon: Globe, 
    label: 'Cuisines', 
    description: 'Regional and international cuisine types',
    color: 'text-indigo-600',
    bgColor: 'bg-indigo-50',
    borderColor: 'border-indigo-200'
  },
  prep_method: { 
    icon: ChefHat, 
    label: 'Preparation', 
    description: 'Cooking and preparation methods',
    color: 'text-orange-600',
    bgColor: 'bg-orange-50',
    borderColor: 'border-orange-200'
  },
  spice_level: { 
    icon: Flame, 
    label: 'Spice Levels', 
    description: 'Spiciness ratings with Scoville scale',
    color: 'text-red-700',
    bgColor: 'bg-red-50',
    borderColor: 'border-red-200'
  },
  customer_segment: { 
    icon: Users, 
    label: 'Customer Segments', 
    description: 'Customer categorization and loyalty tiers',
    color: 'text-purple-600',
    bgColor: 'bg-purple-50',
    borderColor: 'border-purple-200'
  },
  price_range: { 
    icon: DollarSign, 
    label: 'Price Ranges', 
    description: 'Price tier classifications',
    color: 'text-yellow-600',
    bgColor: 'bg-yellow-50',
    borderColor: 'border-yellow-200'
  },
  promotion_type: { 
    icon: Percent, 
    label: 'Promotions', 
    description: 'Types of offers and discounts',
    color: 'text-pink-600',
    bgColor: 'bg-pink-50',
    borderColor: 'border-pink-200'
  },
  general_tag: { 
    icon: Tag, 
    label: 'General Tags', 
    description: 'Feature and status tags',
    color: 'text-gray-600',
    bgColor: 'bg-gray-50',
    borderColor: 'border-gray-200'
  },
  seasonal_tag: { 
    icon: Calendar, 
    label: 'Seasonal', 
    description: 'Holiday and seasonal tags',
    color: 'text-teal-600',
    bgColor: 'bg-teal-50',
    borderColor: 'border-teal-200'
  },
  feature_tag: { 
    icon: Star, 
    label: 'Features', 
    description: 'Display and marketing features',
    color: 'text-yellow-700',
    bgColor: 'bg-yellow-50',
    borderColor: 'border-yellow-200'
  }
};

interface TaxonomyWithChildren extends Taxonomy {
  children?: TaxonomyWithChildren[];
}

function buildHierarchy(taxonomies: Taxonomy[]): TaxonomyWithChildren[] {
  const itemMap = new Map<number, TaxonomyWithChildren>();
  const rootItems: TaxonomyWithChildren[] = [];

  // First pass: create all items
  taxonomies.forEach(tax => {
    itemMap.set(tax.id, { ...tax, children: [] });
  });

  // Second pass: build hierarchy
  taxonomies.forEach(tax => {
    const item = itemMap.get(tax.id)!;
    if (tax.parentId && itemMap.has(tax.parentId)) {
      itemMap.get(tax.parentId)!.children!.push(item);
    } else {
      rootItems.push(item);
    }
  });

  // Sort by sortOrder
  const sortItems = (items: TaxonomyWithChildren[]) => {
    items.sort((a, b) => a.sortOrder - b.sortOrder);
    items.forEach(item => {
      if (item.children?.length) sortItems(item.children);
    });
  };

  sortItems(rootItems);
  return rootItems;
}

function TaxonomyItem({ 
  taxonomy, 
  typeConfig: config,
  level = 0,
  isLast = false,
  expandedItems,
  onToggle,
  showTree = false
}: { 
  taxonomy: TaxonomyWithChildren;
  typeConfig: typeof typeConfig[keyof typeof typeConfig];
  level?: number;
  isLast?: boolean;
  expandedItems: Set<number>;
  onToggle: (id: number) => void;
  showTree?: boolean;
}) {
  const hasChildren = taxonomy.children && taxonomy.children.length > 0;
  const isExpanded = expandedItems.has(taxonomy.id);
  const Icon = config.icon;

  return (
    <div className="relative">
      {/* Tree lines for hierarchical display */}
      {showTree && level > 0 && (
        <>
          {/* Vertical line from parent */}
          {!isLast && (
            <div 
              className="absolute left-0 top-0 bottom-0 w-px bg-gray-200"
              style={{ left: `${(level - 1) * 24 + 20}px` }}
            />
          )}
          {/* Horizontal connector */}
          <div 
            className="absolute top-4 h-px bg-gray-200"
            style={{ 
              left: `${(level - 1) * 24 + 20}px`,
              width: '12px'
            }}
          />
        </>
      )}

      <div 
        className={cn(
          "group relative flex items-center gap-2 py-1.5 px-2 rounded-md transition-all",
          "hover:bg-gray-50 cursor-pointer",
          level > 0 && "ml-6"
        )}
        style={{ paddingLeft: showTree && level > 0 ? `${level * 24 + 8}px` : undefined }}
        onClick={() => router.visit(`/taxonomies/${taxonomy.id}`)}
      >
        {/* Expand/Collapse for hierarchical items */}
        {hasChildren && (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onToggle(taxonomy.id);
            }}
            className="p-0.5 hover:bg-gray-200 rounded transition-colors"
          >
            {isExpanded ? (
              <ChevronDown className="h-3 w-3 text-gray-400" />
            ) : (
              <ChevronRight className="h-3 w-3 text-gray-400" />
            )}
          </button>
        )}
        
        {/* Add spacer if no children to align icons */}
        {!hasChildren && showTree && (
          <div className="w-4" />
        )}
        
        {/* Icon */}
        <div className={cn(
          "flex items-center justify-center w-7 h-7 rounded-md flex-shrink-0",
          config.bgColor,
          config.borderColor,
          "border"
        )}>
          <Icon className={cn("h-3.5 w-3.5", config.color)} />
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0 flex items-center gap-2">
          <span className="text-sm font-medium text-gray-900 truncate">
            {taxonomy.name}
          </span>
          {taxonomy.metadata?.priority && (
            <Badge variant="outline" className="text-xs h-5 px-1.5">
              P{taxonomy.metadata.priority}
            </Badge>
          )}
          {taxonomy.metadata?.level !== undefined && (
            <Badge variant="outline" className="text-xs h-5 px-1.5">
              L{taxonomy.metadata.level}
            </Badge>
          )}
          {taxonomy.metadata?.scoville && (
            <Badge variant="outline" className="text-xs h-5 px-1.5 text-orange-600 border-orange-300">
              {taxonomy.metadata.scoville}
            </Badge>
          )}
        </div>

        {/* Status & Actions */}
        <div className="flex items-center gap-1.5">
          {hasChildren && (
            <span className="text-xs text-gray-500 font-medium">
              {taxonomy.children!.length}
            </span>
          )}
          
          {taxonomy.isActive ? (
            <Eye className="h-3.5 w-3.5 text-green-600" />
          ) : (
            <EyeOff className="h-3.5 w-3.5 text-gray-400" />
          )}

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                size="sm"
                className="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                onClick={(e) => e.stopPropagation()}
              >
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
              <DropdownMenuItem onClick={() => router.visit(`/taxonomies/${taxonomy.id}`)}>
                <Eye className="mr-2 h-4 w-4" />
                View Details
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => router.visit(`/taxonomies/${taxonomy.id}/edit`)}>
                <Edit2 className="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem className="text-red-600">
                <Trash2 className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      {/* Children - recursively render with their own expand state */}
      {hasChildren && isExpanded && (
        <div className="relative">
          {taxonomy.children!.map((child, idx) => (
            <TaxonomyItem
              key={child.id}
              taxonomy={child}
              typeConfig={config}
              level={level + 1}
              isLast={idx === taxonomy.children!.length - 1}
              expandedItems={expandedItems}
              onToggle={onToggle}
              showTree={showTree}
            />
          ))}
        </div>
      )}
    </div>
  );
}

function TaxonomiesIndexContent({ taxonomies, filters }: TaxonomyIndexProps) {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedType, setSelectedType] = useState<string>('all');
  const [expandedSections, setExpandedSections] = useState<Set<string>>(new Set());
  const [expandedItems, setExpandedItems] = useState<Set<number>>(new Set());

  // Group taxonomies by type
  const groupedTaxonomies = useMemo(() => {
    const groups: Record<string, Taxonomy[]> = {};
    
    taxonomies.forEach(taxonomy => {
      if (!groups[taxonomy.type]) {
        groups[taxonomy.type] = [];
      }
      groups[taxonomy.type].push(taxonomy);
    });

    return groups;
  }, [taxonomies]);

  // Build hierarchies for each type
  const hierarchies = useMemo(() => {
    const result: Record<string, TaxonomyWithChildren[]> = {};
    
    Object.entries(groupedTaxonomies).forEach(([type, items]) => {
      result[type] = buildHierarchy(items);
    });
    
    return result;
  }, [groupedTaxonomies]);

  // Filter taxonomies based on search
  const filteredHierarchies = useMemo(() => {
    if (!searchTerm) return hierarchies;
    
    const searchLower = searchTerm.toLowerCase();
    const filterItems = (items: TaxonomyWithChildren[]): TaxonomyWithChildren[] => {
      return items.reduce((acc: TaxonomyWithChildren[], item) => {
        const matches = 
          item.name.toLowerCase().includes(searchLower) ||
          item.metadata?.description?.toLowerCase().includes(searchLower);
        
        const filteredChildren = item.children ? filterItems(item.children) : [];
        
        if (matches || filteredChildren.length > 0) {
          acc.push({
            ...item,
            children: filteredChildren
          });
        }
        
        return acc;
      }, []);
    };

    const result: Record<string, TaxonomyWithChildren[]> = {};
    Object.entries(hierarchies).forEach(([type, items]) => {
      const filtered = filterItems(items);
      if (filtered.length > 0) {
        result[type] = filtered;
      }
    });
    
    return result;
  }, [hierarchies, searchTerm]);

  const typeCounts = useMemo(() => {
    const counts: Record<string, number> = { all: taxonomies.length };
    Object.entries(groupedTaxonomies).forEach(([type, items]) => {
      counts[type] = items.length;
    });
    return counts;
  }, [groupedTaxonomies, taxonomies]);

  const toggleSection = (type: string) => {
    const newExpanded = new Set(expandedSections);
    if (newExpanded.has(type)) {
      newExpanded.delete(type);
    } else {
      newExpanded.add(type);
    }
    setExpandedSections(newExpanded);
  };

  const toggleItem = (id: number) => {
    const newExpanded = new Set(expandedItems);
    if (newExpanded.has(id)) {
      newExpanded.delete(id);
    } else {
      newExpanded.add(id);
    }
    setExpandedItems(newExpanded);
  };

  const hasActiveFilters = Object.values(filters).some(value => value !== undefined && value !== null && value !== '');

  if (taxonomies.length === 0 && !hasActiveFilters) {
    return (
      <>
        <Page.Header 
          title="Categories & Tags"
          subtitle="Manage taxonomies for items, menus, and other system entities"
          actions={
            <Link href="/taxonomies/create">
              <Button>
                <Plus className="mr-2 h-4 w-4" />
                Add Taxonomy
              </Button>
            </Link>
          }
        />
        <Page.Content>
          <EmptyState
            icon={Tag}
            title="No taxonomies yet"
            description="Create categories and tags to organize your items, menus, and other system entities"
            actions={
              <Link href="/taxonomies/create">
                <Button size="lg">
                  <Plus className="mr-2 h-4 w-4" />
                  Create First Taxonomy
                </Button>
              </Link>
            }
            helpText={
              <>
                Learn more about <a href="#" className="text-primary hover:underline">organizing with taxonomies</a>
              </>
            }
          />
        </Page.Content>
      </>
    );
  }

  // Get types to display
  const displayTypes = selectedType === 'all' 
    ? Object.keys(typeConfig).filter(type => typeCounts[type] > 0)
    : [selectedType];

  return (
    <>
      <Page.Header 
        title="Categories & Tags"
        subtitle="Manage taxonomies for items, menus, and other system entities"
        actions={
          <Link href="/taxonomies/create">
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Add Taxonomy
            </Button>
          </Link>
        }
      />

      <Page.Content>
        <div className="flex gap-4 h-[calc(100vh-200px)]">
          {/* Sidebar Navigation */}
          <div className="w-52 flex-shrink-0">
            <div className="sticky top-0 h-full flex flex-col">
              {/* Search */}
              <div className="relative mb-3">
                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                <Input
                  placeholder="Search..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-8 h-8 text-sm"
                />
              </div>

              {/* Type Navigation */}
              <div className="space-y-0.5 overflow-y-auto">
                <button
                  onClick={() => setSelectedType('all')}
                  className={cn(
                    "w-full flex items-center justify-between px-2.5 py-1.5 rounded-md text-xs transition-colors",
                    selectedType === 'all' 
                      ? "bg-gray-100 text-gray-900 font-medium" 
                      : "hover:bg-gray-50 text-gray-600"
                  )}
                >
                  <div className="flex items-center gap-1.5">
                    <Hash className="h-3.5 w-3.5" />
                    <span>All Categories</span>
                  </div>
                  <span className="text-xs text-gray-500">
                    {typeCounts.all}
                  </span>
                </button>

                <div className="h-px bg-gray-200 my-1" />

                {Object.entries(typeConfig).map(([type, config]) => {
                  if (typeCounts[type] === 0) return null;
                  
                  const Icon = config.icon;
                  return (
                    <button
                      key={type}
                      onClick={() => setSelectedType(type)}
                      className={cn(
                        "w-full flex items-center justify-between px-2.5 py-1.5 rounded-md text-xs transition-colors",
                        selectedType === type 
                          ? "bg-gray-100 text-gray-900 font-medium" 
                          : "hover:bg-gray-50 text-gray-600"
                      )}
                    >
                      <div className="flex items-center gap-1.5 min-w-0">
                        <Icon className={cn("h-3.5 w-3.5 flex-shrink-0", config.color)} />
                        <span className="truncate">{config.label}</span>
                      </div>
                      <span className="text-xs text-gray-500 ml-1">
                        {typeCounts[type]}
                      </span>
                    </button>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Main Content - Scrollable */}
          <div className="flex-1 min-w-0 overflow-y-auto pr-2">
            <div className="space-y-3 pb-4">
              {displayTypes.map(type => {
                const config = typeConfig[type];
                if (!config || !filteredHierarchies[type]) return null;
                
                const Icon = config.icon;
                const isExpanded = expandedSections.has(type);

                return (
                  <Card key={type} className="overflow-hidden">
                    {/* Section Header */}
                    <div 
                      className={cn(
                        "px-4 py-3 cursor-pointer transition-colors",
                        "hover:bg-gray-50",
                        selectedType === 'all' && "cursor-pointer",
                        (selectedType !== 'all' || isExpanded) && "border-b"
                      )}
                      onClick={() => selectedType === 'all' && toggleSection(type)}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          {selectedType === 'all' && (
                            <button className="p-0.5 hover:bg-gray-200 rounded transition-colors">
                              {isExpanded ? (
                                <ChevronDown className="h-3 w-3 text-gray-400" />
                              ) : (
                                <ChevronRight className="h-3 w-3 text-gray-400" />
                              )}
                            </button>
                          )}
                          <div className={cn(
                            "flex items-center justify-center w-8 h-8 rounded-md",
                            config.bgColor,
                            config.borderColor,
                            "border"
                          )}>
                            <Icon className={cn("h-4 w-4", config.color)} />
                          </div>
                          <div>
                            <h3 className="text-sm font-semibold text-gray-900">{config.label}</h3>
                            <p className="text-xs text-gray-500">{config.description}</p>
                          </div>
                        </div>
                        <Badge variant="secondary" className="text-xs">
                          {typeCounts[type]} items
                        </Badge>
                      </div>
                    </div>

                    {/* Section Content */}
                    {(selectedType !== 'all' || isExpanded) && (
                      <div className="py-1">
                        {filteredHierarchies[type].map((item, idx) => (
                          <TaxonomyItem
                            key={item.id}
                            taxonomy={item}
                            typeConfig={config}
                            expandedItems={expandedItems}
                            onToggle={toggleItem}
                            showTree={config.isHierarchical}
                          />
                        ))}
                      </div>
                    )}
                  </Card>
                );
              })}

              {displayTypes.length === 0 && searchTerm && (
                <EmptyState
                  icon={Search}
                  title="No results found"
                  description={`No taxonomies match "${searchTerm}"`}
                  compact
                />
              )}
            </div>
          </div>
        </div>
      </Page.Content>
    </>
  );
}

export default function TaxonomiesIndex(props: TaxonomyIndexProps) {
  return (
    <AppLayout>
      <Head title="Categories & Tags" />
      <Page>
        <TaxonomiesIndexContent {...props} />
      </Page>
    </AppLayout>
  );
}