import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  ArrowLeft,
  Edit,
  Trash2,
  Tag,
  Package,
  Calendar,
  CheckCircle,
  XCircle,
  Hash,
  Plus,
  FolderTree,
  Layers,
  Clock,
  Palette,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface TaxonomyAttribute {
  id: number;
  key: string;
  value: string;
  type: string;
}

interface Taxonomy {
  id: number;
  name: string;
  slug: string;
  type: string;
  parentId: number | null;
  parent?: Taxonomy;
  metadata?: {
    icon?: string;
    color?: string;
    description?: string;
  };
  sortOrder: number;
  isActive: boolean;
  children?: Taxonomy[];
  attributes?: TaxonomyAttribute[];
  createdAt?: string;
  updatedAt?: string;
}

interface Props {
  taxonomy: Taxonomy;
  relatedItems?: any[];
  stats?: {
    totalItems: number;
    activeItems: number;
    lastUsed?: string;
  };
}

function TaxonomyShowContent({ taxonomy, relatedItems = [], stats }: Props) {
  const handleEdit = () => {
    router.get(`/taxonomies/${taxonomy.id}/edit`);
  };

  const handleDelete = () => {
    if (confirm(`Are you sure you want to delete "${taxonomy.name}"?`)) {
      router.delete(`/taxonomies/${taxonomy.id}`, {
        onSuccess: () => router.get('/taxonomies'),
      });
    }
  };

  const handleCreateChild = () => {
    router.get(`/taxonomies/create?type=${taxonomy.type}&parent=${taxonomy.id}`);
  };

  const taxonomyTypeLabel = taxonomy.type.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');

  return (
    <>
      <Page.Header
        title={
          <div className="space-y-1">
            <div className="flex items-center gap-3">
              <Link href="/taxonomies">
                <Button variant="ghost" size="icon" className="h-8 w-8">
                  <ArrowLeft className="h-4 w-4" />
                </Button>
              </Link>
              <h1 className="text-3xl font-bold">{taxonomy.name}</h1>
            </div>
            <div className="flex items-center gap-2 text-sm text-muted-foreground ml-11">
              {taxonomy.parent ? (
                <>
                  <FolderTree className="h-3 w-3" />
                  <span>Child of</span>
                  <Link 
                    href={`/taxonomies/${taxonomy.parent.id}`}
                    className="text-primary hover:underline font-medium"
                  >
                    {taxonomy.parent.name}
                  </Link>
                </>
              ) : (
                <>
                  <Layers className="h-3 w-3" />
                  <span>Root Taxonomy</span>
                </>
              )}
              <span className="text-muted-foreground/50">•</span>
              <span className="font-mono text-xs">{taxonomy.slug}</span>
            </div>
          </div>
        }
        actions={
          <div className="flex gap-2">
            {/* Show Create Child button for hierarchical types */}
            {['item_category', 'menu_section', 'location_group'].includes(taxonomy.type) && (
              <Button variant="outline" onClick={handleCreateChild}>
                <Plus className="mr-2 h-4 w-4" />
                Create Child
              </Button>
            )}
            <Button variant="outline" onClick={handleEdit}>
              <Edit className="mr-2 h-4 w-4" />
              Edit
            </Button>
            <Button variant="destructive" onClick={handleDelete}>
              <Trash2 className="mr-2 h-4 w-4" />
              Delete
            </Button>
          </div>
        }
      />

      <Page.Content>
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Main Content Area */}
          <div className="lg:col-span-2 space-y-6">
            {/* Overview Card */}
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-lg">Overview</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-1">
                    <p className="text-sm text-muted-foreground">Type</p>
                    <div className="flex items-center gap-2">
                      <Tag className="h-4 w-4 text-primary" />
                      <span className="font-medium">{taxonomyTypeLabel}</span>
                    </div>
                  </div>
                  
                  <div className="space-y-1">
                    <p className="text-sm text-muted-foreground">Status</p>
                    <div className="flex items-center gap-2">
                      {taxonomy.isActive ? (
                        <>
                          <CheckCircle className="h-4 w-4 text-green-600" />
                          <span className="font-medium text-green-600">Active</span>
                        </>
                      ) : (
                        <>
                          <XCircle className="h-4 w-4 text-red-600" />
                          <span className="font-medium text-red-600">Inactive</span>
                        </>
                      )}
                    </div>
                  </div>

                  <div className="space-y-1">
                    <p className="text-sm text-muted-foreground">Sort Order</p>
                    <div className="flex items-center gap-2">
                      <Hash className="h-4 w-4 text-muted-foreground" />
                      <span className="font-medium">{taxonomy.sortOrder}</span>
                    </div>
                  </div>

                  {taxonomy.metadata?.description && (
                    <div className="col-span-2 space-y-1">
                      <p className="text-sm text-muted-foreground">Description</p>
                      <p className="text-sm leading-relaxed">{taxonomy.metadata.description}</p>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Visual Settings */}
            {taxonomy.metadata && (taxonomy.metadata.icon || taxonomy.metadata.color) && (
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-lg flex items-center gap-2">
                    <Palette className="h-4 w-4" />
                    Visual Settings
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center gap-6">
                    {taxonomy.metadata.icon && (
                      <div className="flex items-center gap-3 bg-muted/50 px-4 py-2 rounded-lg">
                        <Hash className="h-4 w-4 text-muted-foreground" />
                        <div>
                          <p className="text-xs text-muted-foreground">Icon</p>
                          <p className="font-mono font-medium">{taxonomy.metadata.icon}</p>
                        </div>
                      </div>
                    )}
                    {taxonomy.metadata.color && (
                      <div className="flex items-center gap-3 bg-muted/50 px-4 py-2 rounded-lg">
                        <div 
                          className="w-6 h-6 rounded border-2 border-white shadow-sm"
                          style={{ backgroundColor: taxonomy.metadata.color }}
                        />
                        <div>
                          <p className="text-xs text-muted-foreground">Color</p>
                          <p className="font-mono font-medium">{taxonomy.metadata.color}</p>
                        </div>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Children */}
            {taxonomy.children && taxonomy.children.length > 0 && (
              <Card>
                <CardHeader className="pb-3">
                  <div className="flex items-center justify-between">
                    <CardTitle className="text-lg flex items-center gap-2">
                      <FolderTree className="h-4 w-4" />
                      Child Taxonomies
                    </CardTitle>
                    <Badge variant="secondary">{taxonomy.children.length}</Badge>
                  </div>
                  <CardDescription>
                    Taxonomies nested under this parent
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    {taxonomy.children.map((child) => (
                      <Link
                        key={child.id}
                        href={`/taxonomies/${child.id}`}
                        className={cn(
                          "flex items-center justify-between p-4 rounded-lg border",
                          "hover:bg-muted/50 transition-all duration-200",
                          "group hover:border-primary/20"
                        )}
                      >
                        <div className="flex items-center gap-3">
                          <div className={cn(
                            "w-8 h-8 rounded-lg flex items-center justify-center",
                            "bg-primary/10 group-hover:bg-primary/20 transition-colors"
                          )}>
                            <Tag className="h-4 w-4 text-primary" />
                          </div>
                          <div>
                            <p className="font-medium group-hover:text-primary transition-colors">
                              {child.name}
                            </p>
                            <p className="text-xs text-muted-foreground">{child.slug}</p>
                          </div>
                        </div>
                        <Badge 
                          variant={child.isActive ? 'default' : 'secondary'}
                          className={cn(
                            child.isActive ? 'bg-green-100 text-green-700' : ''
                          )}
                        >
                          {child.isActive ? 'Active' : 'Inactive'}
                        </Badge>
                      </Link>
                    ))}
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Attributes */}
            {taxonomy.attributes && taxonomy.attributes.length > 0 && (
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-lg">Custom Attributes</CardTitle>
                  <CardDescription>
                    Additional properties for this taxonomy
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="grid gap-3">
                    {taxonomy.attributes.map((attr) => (
                      <div 
                        key={attr.id}
                        className="flex items-center justify-between p-3 rounded-lg bg-muted/30"
                      >
                        <div className="flex items-center gap-3">
                          <span className="font-medium text-sm">{attr.key}</span>
                          <Badge variant="outline" className="text-xs">
                            {attr.type}
                          </Badge>
                        </div>
                        <span className="text-sm font-mono">{attr.value}</span>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Stats Card */}
            {stats && (
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-lg">Usage Statistics</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex justify-between items-center">
                    <div>
                      <p className="text-2xl font-bold">{stats.totalItems || 0}</p>
                      <p className="text-sm text-muted-foreground">Total Items</p>
                    </div>
                    <Package className="h-8 w-8 text-muted-foreground/20" />
                  </div>
                  
                  <div className="flex justify-between items-center">
                    <div>
                      <p className="text-2xl font-bold text-green-600">
                        {stats.activeItems || 0}
                      </p>
                      <p className="text-sm text-muted-foreground">Active Items</p>
                    </div>
                    <CheckCircle className="h-8 w-8 text-green-600/20" />
                  </div>
                  
                  {stats.lastUsed && (
                    <div className="pt-2 border-t">
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Clock className="h-3 w-3" />
                        <span>Last used {new Date(stats.lastUsed).toLocaleDateString()}</span>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}

            {/* System Info */}
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-lg">System Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-center justify-between py-2 border-b">
                  <span className="text-sm text-muted-foreground">ID</span>
                  <span className="font-mono text-sm font-medium">#{taxonomy.id}</span>
                </div>
                
                {taxonomy.createdAt && (
                  <div className="flex items-center justify-between py-2 border-b">
                    <span className="text-sm text-muted-foreground">Created</span>
                    <span className="text-sm">
                      {new Date(taxonomy.createdAt).toLocaleDateString()}
                    </span>
                  </div>
                )}
                
                {taxonomy.updatedAt && (
                  <div className="flex items-center justify-between py-2">
                    <span className="text-sm text-muted-foreground">Updated</span>
                    <span className="text-sm">
                      {new Date(taxonomy.updatedAt).toLocaleDateString()}
                    </span>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Related Items */}
            {relatedItems && relatedItems.length > 0 && (
              <Card>
                <CardHeader className="pb-3">
                  <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Related Items</CardTitle>
                    <Badge variant="secondary">{relatedItems.length}</Badge>
                  </div>
                  <CardDescription>
                    Items using this taxonomy
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    {relatedItems.slice(0, 5).map((item: any) => (
                      <Link
                        key={item.id}
                        href={`/items/${item.id}`}
                        className="flex items-center gap-2 text-sm hover:text-primary transition-colors group"
                      >
                        <Package className="h-3 w-3 text-muted-foreground group-hover:text-primary" />
                        <span>{item.name}</span>
                      </Link>
                    ))}
                    {relatedItems.length > 5 && (
                      <p className="text-xs text-muted-foreground pt-2">
                        +{relatedItems.length - 5} more items
                      </p>
                    )}
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </Page.Content>
    </>
  );
}

export default function TaxonomyShow(props: Props) {
  return (
    <AppLayout>
      <Head title={`${props.taxonomy.name} - Taxonomy`} />
      <Page>
        <TaxonomyShowContent {...props} />
      </Page>
    </AppLayout>
  );
}