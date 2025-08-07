import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Printer,
  Download,
  Edit,
  ArrowLeft,
  Smartphone,
  Monitor,
  Star,
  TrendingUp,
  Sparkles,
  Leaf,
  Clock,
  AlertCircle,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';

interface MenuItem {
  id: number;
  displayName?: string;
  displayDescription?: string;
  priceOverride?: number;
  isFeatured: boolean;
  isRecommended: boolean;
  isNew: boolean;
  isSeasonal: boolean;
  dietaryLabels?: string[];
  allergenInfo?: string[];
  calorieCount?: number;
  preparationTime?: number;
  baseItem?: {
    name: string;
    description?: string;
    price: number;
  };
}

interface MenuSection {
  id: number;
  name: string;
  description?: string;
  isActive: boolean;
  isFeatured: boolean;
  isAvailable: boolean;
  items: MenuItem[];
  children?: MenuSection[];
}

interface MenuStructure {
  id: number;
  name: string;
  slug: string;
  description?: string;
  type: string;
  isActive: boolean;
  isAvailable: boolean;
  sections: MenuSection[];
  metadata?: any;
}

interface PageProps {
  menu: MenuStructure;
}

// Dietary label icons/colors
const dietaryLabelStyles: Record<string, { icon: string; color: string }> = {
  vegetarian: { icon: '🌱', color: 'text-green-600' },
  vegan: { icon: '🌿', color: 'text-green-700' },
  gluten_free: { icon: '🌾', color: 'text-yellow-600' },
  dairy_free: { icon: '🥛', color: 'text-blue-600' },
  nut_free: { icon: '🥜', color: 'text-orange-600' },
  halal: { icon: '☪️', color: 'text-emerald-600' },
  kosher: { icon: '✡️', color: 'text-blue-700' },
  organic: { icon: '🌿', color: 'text-green-600' },
};

function MenuItemCard({ item }: { item: MenuItem }) {
  const name = item.displayName || item.baseItem?.name || 'Unnamed Item';
  const description = item.displayDescription || item.baseItem?.description;
  const price = item.priceOverride ?? item.baseItem?.price ?? 0;
  
  return (
    <div className="flex justify-between items-start py-4 group">
      <div className="flex-1 pr-4">
        <div className="flex items-start gap-3">
          <div className="flex-1">
            <div className="flex items-center gap-2 flex-wrap">
              <h4 className="font-semibold text-gray-900">{name}</h4>
              
              {item.isFeatured && (
                <Badge variant="secondary" className="text-xs">
                  <Star className="mr-1 h-3 w-3" />
                  Featured
                </Badge>
              )}
              
              {item.isRecommended && (
                <Badge variant="secondary" className="text-xs">
                  <TrendingUp className="mr-1 h-3 w-3" />
                  Chef's Choice
                </Badge>
              )}
              
              {item.isNew && (
                <Badge variant="secondary" className="text-xs bg-green-100 text-green-800">
                  <Sparkles className="mr-1 h-3 w-3" />
                  New
                </Badge>
              )}
              
              {item.isSeasonal && (
                <Badge variant="secondary" className="text-xs bg-orange-100 text-orange-800">
                  <Leaf className="mr-1 h-3 w-3" />
                  Seasonal
                </Badge>
              )}
            </div>
            
            {description && (
              <p className="text-sm text-gray-600 mt-1 leading-relaxed">
                {description}
              </p>
            )}
            
            <div className="flex items-center gap-4 mt-2">
              {item.dietaryLabels && item.dietaryLabels.length > 0 && (
                <div className="flex items-center gap-2">
                  {item.dietaryLabels.map(label => {
                    const style = dietaryLabelStyles[label];
                    return style ? (
                      <span
                        key={label}
                        className={cn("text-sm", style.color)}
                        title={label.replace('_', ' ')}
                      >
                        {style.icon}
                      </span>
                    ) : null;
                  })}
                </div>
              )}
              
              {item.calorieCount && (
                <span className="text-xs text-gray-500">
                  {item.calorieCount} cal
                </span>
              )}
              
              {item.preparationTime && (
                <span className="text-xs text-gray-500 flex items-center gap-1">
                  <Clock className="h-3 w-3" />
                  {item.preparationTime} min
                </span>
              )}
            </div>
            
            {item.allergenInfo && item.allergenInfo.length > 0 && (
              <p className="text-xs text-gray-500 mt-2 flex items-center gap-1">
                <AlertCircle className="h-3 w-3" />
                Contains: {item.allergenInfo.join(', ')}
              </p>
            )}
          </div>
        </div>
      </div>
      
      <div className="text-right">
        <div className="font-semibold text-gray-900">
          {formatCurrency(price)}
        </div>
        {item.priceOverride && item.baseItem?.price && (
          <div className="text-xs text-gray-500 line-through">
            {formatCurrency(item.baseItem.price)}
          </div>
        )}
      </div>
    </div>
  );
}

function MenuSectionDisplay({ section, depth = 0 }: { section: MenuSection; depth?: number }) {
  if (!section.isAvailable || section.items.length === 0) {
    return null;
  }
  
  return (
    <div className={cn("mb-8", depth > 0 && "ml-8")}>
      <div className="mb-4">
        <div className="flex items-center gap-2">
          <h3 className={cn(
            "font-bold text-gray-900",
            depth === 0 ? "text-xl" : "text-lg"
          )}>
            {section.name}
          </h3>
          {section.isFeatured && (
            <Badge variant="outline" className="text-xs">
              Featured
            </Badge>
          )}
        </div>
        {section.description && (
          <p className="text-sm text-gray-600 mt-1">{section.description}</p>
        )}
      </div>
      
      <div className="space-y-2">
        {section.items.map((item, index) => (
          <div key={item.id}>
            <MenuItemCard item={item} />
            {index < section.items.length - 1 && (
              <Separator className="my-2" />
            )}
          </div>
        ))}
      </div>
      
      {section.children && section.children.length > 0 && (
        <div className="mt-6">
          {section.children.map(child => (
            <MenuSectionDisplay key={child.id} section={child} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}

function MenuPreviewContent({ menu }: PageProps) {
  const [viewMode, setViewMode] = useState<'desktop' | 'mobile'>('desktop');
  const [fontSize, setFontSize] = useState<'small' | 'medium' | 'large'>('medium');
  
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Menus', href: '/menus' },
    { title: menu.name, href: `/menus/${menu.id}` },
    { title: 'Preview', current: true },
  ];
  
  const fontSizeClasses = {
    small: 'text-sm',
    medium: 'text-base',
    large: 'text-lg',
  };
  
  const handlePrint = () => {
    window.print();
  };
  
  const handleExport = (format: string) => {
    window.location.href = `/menus/${menu.id}/export/${format}`;
  };
  
  return (
    <>
      <Page.Header
        title={`Menu Preview: ${menu.name}`}
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link href={`/menus/${menu.id}`}>
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Menu
              </Link>
            </Button>
            <Button variant="outline" onClick={handlePrint}>
              <Printer className="mr-2 h-4 w-4" />
              Print
            </Button>
            <Button variant="outline" asChild>
              <Link href={`/menus/${menu.id}/builder`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit Menu
              </Link>
            </Button>
            <Select onValueChange={handleExport}>
              <SelectTrigger className="w-32">
                <Download className="mr-2 h-4 w-4" />
                Export
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="pdf">Export as PDF</SelectItem>
                <SelectItem value="json">Export as JSON</SelectItem>
                <SelectItem value="csv">Export as CSV</SelectItem>
              </SelectContent>
            </Select>
          </div>
        }
      />
      
      <Page.Content>
        {/* View Controls */}
        <div className="mb-6 flex items-center justify-between print:hidden">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <Button
                variant={viewMode === 'desktop' ? 'default' : 'outline'}
                size="sm"
                onClick={() => setViewMode('desktop')}
              >
                <Monitor className="h-4 w-4" />
              </Button>
              <Button
                variant={viewMode === 'mobile' ? 'default' : 'outline'}
                size="sm"
                onClick={() => setViewMode('mobile')}
              >
                <Smartphone className="h-4 w-4" />
              </Button>
            </div>
            
            <Select value={fontSize} onValueChange={(value: any) => setFontSize(value)}>
              <SelectTrigger className="w-32">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="small">Small</SelectItem>
                <SelectItem value="medium">Medium</SelectItem>
                <SelectItem value="large">Large</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
        
        {/* Menu Preview */}
        <div className={cn(
          "bg-white rounded-lg shadow-lg print:shadow-none",
          viewMode === 'mobile' && "max-w-md mx-auto",
        )}>
          <div className={cn(
            "p-8 print:p-12",
            fontSizeClasses[fontSize]
          )}>
            {/* Menu Header */}
            <div className="text-center mb-8 pb-6 border-b-2 border-gray-200">
              <h1 className="text-3xl font-bold text-gray-900 mb-2 font-serif">
                {menu.name}
              </h1>
              {menu.description && (
                <p className="text-gray-600 max-w-2xl mx-auto">
                  {menu.description}
                </p>
              )}
              
              {!menu.isAvailable && (
                <Badge variant="destructive" className="mt-4">
                  Currently Unavailable
                </Badge>
              )}
            </div>
            
            {/* Menu Sections */}
            {menu.sections.length === 0 ? (
              <div className="text-center py-12">
                <p className="text-gray-500">No menu items available</p>
              </div>
            ) : (
              <div className="space-y-8">
                {menu.sections
                  .filter(section => section.isActive && section.items.length > 0)
                  .map((section, index) => (
                    <div key={section.id}>
                      {index > 0 && <Separator className="my-8" />}
                      <MenuSectionDisplay section={section} />
                    </div>
                  ))}
              </div>
            )}
            
            {/* Footer */}
            <div className="mt-12 pt-6 border-t text-center text-sm text-gray-500">
              <p>Prices are subject to change. Please inform us of any allergies.</p>
              {menu.metadata?.lastUpdated && (
                <p className="mt-2">
                  Last updated: {new Date(menu.metadata.lastUpdated).toLocaleDateString()}
                </p>
              )}
            </div>
          </div>
        </div>
        
        {/* Print Styles */}
        <style jsx global>{`
          @media print {
            body * {
              visibility: hidden;
            }
            .print\\:shadow-none, .print\\:shadow-none * {
              visibility: visible;
            }
            .print\\:shadow-none {
              position: absolute;
              left: 0;
              top: 0;
              width: 100%;
            }
            .print\\:p-12 {
              padding: 3rem;
            }
            .print\\:hidden {
              display: none !important;
            }
          }
        `}</style>
      </Page.Content>
    </>
  );
}

export default function MenuPreview(props: PageProps) {
  return (
    <AppLayout>
      <Head title={`Preview - ${props.menu.name}`} />
      <Page>
        <MenuPreviewContent {...props} />
      </Page>
    </AppLayout>
  );
}