import { useState } from 'react';
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { type BreadcrumbItem } from '@/types';
import { EmptyState } from '@/components/empty-state';
import {
  Shield,
  Users,
  Plus,
  Edit,
  Trash2,
  ChevronRight,
  Lock,
  Unlock,
  UserCheck,
  Settings,
  AlertCircle,
  CheckCircle,
  XCircle,
  Crown,
  ShieldCheck,
  ShieldAlert,
  Key,
  FileText,
  Package,
  ShoppingCart,
  MapPin,
  Tag,
  DollarSign,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface Permission {
  id: number;
  name: string;
  slug: string;
  module: string;
  description: string | null;
}

interface Role {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  hierarchy_level: number;
  is_system: boolean;
  permissions: Permission[];
  staff_count: number;
}

interface PermissionGroup {
  module: string;
  icon: React.ReactNode;
  permissions: Permission[];
}

interface PageProps {
  roles: Role[];
  permissions: Permission[];
  stats: {
    totalRoles: number;
    totalPermissions: number;
    customRoles: number;
    systemRoles: number;
  };
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Staff',
    href: '/staff',
  },
  {
    title: 'Roles & Permissions',
    href: '/staff/roles',
  },
];

const moduleIcons: Record<string, React.ReactNode> = {
  staff: <Users className="h-4 w-4" />,
  orders: <ShoppingCart className="h-4 w-4" />,
  items: <Package className="h-4 w-4" />,
  menu: <FileText className="h-4 w-4" />,
  locations: <MapPin className="h-4 w-4" />,
  offers: <Tag className="h-4 w-4" />,
  settings: <Settings className="h-4 w-4" />,
  reports: <FileText className="h-4 w-4" />,
  finance: <DollarSign className="h-4 w-4" />,
};

const getHierarchyIcon = (level: number) => {
  if (level >= 90) return <Crown className="h-4 w-4 text-yellow-600" />;
  if (level >= 70) return <ShieldCheck className="h-4 w-4 text-blue-600" />;
  if (level >= 50) return <Shield className="h-4 w-4 text-green-600" />;
  return <UserCheck className="h-4 w-4 text-gray-600" />;
};

const getHierarchyLabel = (level: number) => {
  if (level >= 90) return 'Super Admin';
  if (level >= 70) return 'Admin';
  if (level >= 50) return 'Manager';
  if (level >= 30) return 'Supervisor';
  return 'Staff';
};

function RolesIndexContent({ roles, permissions, stats }: PageProps) {
  const [selectedRole, setSelectedRole] = useState<Role | null>(roles[0] || null);
  const [createRoleOpen, setCreateRoleOpen] = useState(false);
  const [editRoleOpen, setEditRoleOpen] = useState(false);
  const [deleteRoleOpen, setDeleteRoleOpen] = useState(false);
  const [newRole, setNewRole] = useState({
    name: '',
    description: '',
    hierarchy_level: 10,
    permissions: [] as number[],
  });

  // Group permissions by module
  const permissionGroups = permissions.reduce<PermissionGroup[]>((groups, permission) => {
    const existingGroup = groups.find(g => g.module === permission.module);
    if (existingGroup) {
      existingGroup.permissions.push(permission);
    } else {
      groups.push({
        module: permission.module,
        icon: moduleIcons[permission.module] || <Shield className="h-4 w-4" />,
        permissions: [permission],
      });
    }
    return groups;
  }, []);

  const handleCreateRole = () => {
    router.post('/staff/roles', newRole, {
      onSuccess: () => {
        setCreateRoleOpen(false);
        setNewRole({
          name: '',
          description: '',
          hierarchy_level: 10,
          permissions: [],
        });
      },
    });
  };

  const handleUpdateRole = () => {
    if (!selectedRole) return;
    
    router.put(`/staff/roles/${selectedRole.id}`, {
      ...selectedRole,
    }, {
      onSuccess: () => {
        setEditRoleOpen(false);
      },
    });
  };

  const handleDeleteRole = () => {
    if (!selectedRole || selectedRole.is_system) return;
    
    router.delete(`/staff/roles/${selectedRole.id}`, {
      onSuccess: () => {
        setDeleteRoleOpen(false);
        setSelectedRole(roles[0] || null);
      },
    });
  };

  const togglePermission = (permissionId: number, forNewRole: boolean = false) => {
    if (forNewRole) {
      setNewRole(prev => ({
        ...prev,
        permissions: prev.permissions.includes(permissionId)
          ? prev.permissions.filter(id => id !== permissionId)
          : [...prev.permissions, permissionId],
      }));
    } else if (selectedRole) {
      const hasPermission = selectedRole.permissions.some(p => p.id === permissionId);
      if (hasPermission) {
        router.delete(`/staff/roles/${selectedRole.id}/permissions/${permissionId}`);
      } else {
        router.post(`/staff/roles/${selectedRole.id}/permissions/${permissionId}`);
      }
    }
  };

  return (
    <>
      <Head title="Roles & Permissions" />

      <Page
        title="Roles & Permissions"
        description="Manage staff roles and their permissions"
        breadcrumbs={breadcrumbs}
        actions={
          <Button onClick={() => setCreateRoleOpen(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Create Role
          </Button>
        }
      >
        {/* Stats Cards */}
        <div className="grid gap-4 md:grid-cols-4 mb-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Roles</CardTitle>
              <Shield className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalRoles}</div>
              <p className="text-xs text-muted-foreground">
                {stats.systemRoles} system, {stats.customRoles} custom
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Permissions</CardTitle>
              <Key className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalPermissions}</div>
              <p className="text-xs text-muted-foreground">
                Across {Object.keys(moduleIcons).length} modules
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Custom Roles</CardTitle>
              <Settings className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.customRoles}</div>
              <p className="text-xs text-muted-foreground">
                User-defined roles
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">System Roles</CardTitle>
              <Lock className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.systemRoles}</div>
              <p className="text-xs text-muted-foreground">
                Cannot be modified
              </p>
            </CardContent>
          </Card>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          {/* Roles List */}
          <Card className="lg:col-span-1">
            <CardHeader>
              <CardTitle>Roles</CardTitle>
              <CardDescription>
                Select a role to view its permissions
              </CardDescription>
            </CardHeader>
            <CardContent className="p-0">
              <ScrollArea className="h-[600px]">
                <div className="space-y-1 p-4">
                  {roles.length === 0 ? (
                    <EmptyState
                      icon={Shield}
                      title="No roles"
                      description="Create your first role to get started"
                      action={{
                        label: 'Create Role',
                        onClick: () => setCreateRoleOpen(true),
                      }}
                    />
                  ) : (
                    roles.map((role) => (
                      <button
                        key={role.id}
                        onClick={() => setSelectedRole(role)}
                        className={cn(
                          "w-full text-left p-3 rounded-lg border transition-colors",
                          selectedRole?.id === role.id
                            ? "bg-primary/10 border-primary"
                            : "hover:bg-muted"
                        )}
                      >
                        <div className="flex items-start justify-between">
                          <div className="space-y-1">
                            <div className="flex items-center gap-2">
                              {getHierarchyIcon(role.hierarchy_level)}
                              <span className="font-medium">{role.name}</span>
                              {role.is_system && (
                                <Badge variant="secondary" className="text-xs">
                                  <Lock className="mr-1 h-3 w-3" />
                                  System
                                </Badge>
                              )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                              {role.description || 'No description'}
                            </p>
                            <div className="flex items-center gap-4 text-xs text-muted-foreground">
                              <span className="flex items-center gap-1">
                                <Users className="h-3 w-3" />
                                {role.staff_count} staff
                              </span>
                              <span className="flex items-center gap-1">
                                <Key className="h-3 w-3" />
                                {role.permissions.length} permissions
                              </span>
                            </div>
                          </div>
                          <ChevronRight className="h-4 w-4 text-muted-foreground mt-1" />
                        </div>
                      </button>
                    ))
                  )}
                </div>
              </ScrollArea>
            </CardContent>
          </Card>

          {/* Permission Matrix */}
          <Card className="lg:col-span-2">
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>
                    {selectedRole ? selectedRole.name : 'Select a Role'}
                  </CardTitle>
                  <CardDescription>
                    {selectedRole 
                      ? `${selectedRole.permissions.length} permissions assigned`
                      : 'Choose a role to manage its permissions'
                    }
                  </CardDescription>
                </div>
                {selectedRole && (
                  <div className="flex items-center gap-2">
                    <Badge variant="outline">
                      {getHierarchyLabel(selectedRole.hierarchy_level)}
                    </Badge>
                    {!selectedRole.is_system && (
                      <>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => setEditRoleOpen(true)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => setDeleteRoleOpen(true)}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </>
                    )}
                  </div>
                )}
              </div>
            </CardHeader>
            <CardContent>
              {selectedRole ? (
                <ScrollArea className="h-[500px]">
                  <div className="space-y-6">
                    {permissionGroups.map((group) => (
                      <div key={group.module} className="space-y-3">
                        <div className="flex items-center gap-2 font-medium">
                          {group.icon}
                          <span className="capitalize">{group.module}</span>
                          <Badge variant="secondary" className="ml-auto">
                            {selectedRole.permissions.filter(p => p.module === group.module).length}/
                            {group.permissions.length}
                          </Badge>
                        </div>
                        <div className="grid gap-2 pl-6">
                          {group.permissions.map((permission) => {
                            const hasPermission = selectedRole.permissions.some(
                              p => p.id === permission.id
                            );
                            
                            return (
                              <div
                                key={permission.id}
                                className="flex items-start space-x-3 p-2 rounded-lg hover:bg-muted"
                              >
                                <Checkbox
                                  id={`perm-${permission.id}`}
                                  checked={hasPermission}
                                  onCheckedChange={() => togglePermission(permission.id)}
                                  disabled={selectedRole.is_system}
                                />
                                <div className="flex-1 space-y-1">
                                  <Label
                                    htmlFor={`perm-${permission.id}`}
                                    className="text-sm font-normal cursor-pointer"
                                  >
                                    {permission.name}
                                  </Label>
                                  {permission.description && (
                                    <p className="text-xs text-muted-foreground">
                                      {permission.description}
                                    </p>
                                  )}
                                </div>
                                <Badge variant="outline" className="text-xs">
                                  {permission.slug}
                                </Badge>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    ))}
                  </div>
                </ScrollArea>
              ) : (
                <div className="flex flex-col items-center justify-center h-[500px] text-center">
                  <Shield className="h-12 w-12 text-muted-foreground mb-4" />
                  <p className="text-muted-foreground">
                    Select a role from the list to manage its permissions
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Create Role Dialog */}
        <Dialog open={createRoleOpen} onOpenChange={setCreateRoleOpen}>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Create New Role</DialogTitle>
              <DialogDescription>
                Define a new role with specific permissions
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Role Name</Label>
                  <Input
                    id="name"
                    value={newRole.name}
                    onChange={(e) => setNewRole({ ...newRole, name: e.target.value })}
                    placeholder="e.g., Kitchen Staff"
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="level">Hierarchy Level</Label>
                  <Input
                    id="level"
                    type="number"
                    min="1"
                    max="100"
                    value={newRole.hierarchy_level}
                    onChange={(e) => setNewRole({ 
                      ...newRole, 
                      hierarchy_level: parseInt(e.target.value) 
                    })}
                  />
                  <p className="text-xs text-muted-foreground">
                    {getHierarchyLabel(newRole.hierarchy_level)}
                  </p>
                </div>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={newRole.description}
                  onChange={(e) => setNewRole({ ...newRole, description: e.target.value })}
                  placeholder="Describe the role's responsibilities..."
                  rows={3}
                />
              </div>
              
              <div className="space-y-2">
                <Label>Permissions</Label>
                <ScrollArea className="h-[300px] border rounded-lg p-4">
                  <div className="space-y-4">
                    {permissionGroups.map((group) => (
                      <div key={group.module} className="space-y-2">
                        <div className="flex items-center gap-2 font-medium text-sm">
                          {group.icon}
                          <span className="capitalize">{group.module}</span>
                        </div>
                        <div className="grid gap-2 pl-6">
                          {group.permissions.map((permission) => (
                            <div key={permission.id} className="flex items-center space-x-2">
                              <Checkbox
                                id={`new-perm-${permission.id}`}
                                checked={newRole.permissions.includes(permission.id)}
                                onCheckedChange={() => togglePermission(permission.id, true)}
                              />
                              <Label
                                htmlFor={`new-perm-${permission.id}`}
                                className="text-sm font-normal cursor-pointer"
                              >
                                {permission.name}
                              </Label>
                            </div>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </ScrollArea>
              </div>
            </div>
            
            <DialogFooter>
              <Button variant="outline" onClick={() => setCreateRoleOpen(false)}>
                Cancel
              </Button>
              <Button onClick={handleCreateRole}>
                Create Role
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Delete Confirmation Dialog */}
        {selectedRole && (
          <Dialog open={deleteRoleOpen} onOpenChange={setDeleteRoleOpen}>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Delete Role</DialogTitle>
                <DialogDescription>
                  Are you sure you want to delete the "{selectedRole.name}" role?
                </DialogDescription>
              </DialogHeader>
              
              {selectedRole.staff_count > 0 && (
                <Alert>
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>
                    This role is currently assigned to {selectedRole.staff_count} staff member(s).
                    They will need to be reassigned to another role.
                  </AlertDescription>
                </Alert>
              )}
              
              <DialogFooter>
                <Button variant="outline" onClick={() => setDeleteRoleOpen(false)}>
                  Cancel
                </Button>
                <Button variant="destructive" onClick={handleDeleteRole}>
                  Delete Role
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        )}
      </Page>
    </>
  );
}

RolesIndexContent.layout = (page: React.ReactNode) => (
  <AppLayout>{page}</AppLayout>
);

export default RolesIndexContent;