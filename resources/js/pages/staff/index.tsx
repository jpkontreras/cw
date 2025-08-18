import { useState, useMemo } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { InertiaDataTable } from '@/modules/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuLabel 
} from '@/components/ui/dropdown-menu';
import { 
  Card, 
  CardContent, 
  CardDescription, 
  CardHeader, 
  CardTitle 
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { 
  Plus,
  MoreHorizontal,
  Download,
  FileUp,
  Users,
  UserCheck,
  UserX,
  Clock,
  Calendar,
  Shield,
  Mail,
  Phone,
  Edit,
  Eye,
  Trash2,
  ChevronDown,
  AlertCircle,
  CalendarCheck
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency } from '@/lib/format';
import { ColumnDef } from '@tanstack/react-table';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { type BreadcrumbItem } from '@/types';
import { EmptyState } from '@/components/empty-state';

interface StaffMember {
  id: number;
  employee_code: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  status: 'active' | 'inactive' | 'suspended' | 'terminated' | 'on_leave';
  hire_date: string;
  profile_photo_url: string | null;
  roles: Array<{
    id: number;
    name: string;
    hierarchy_level: number;
  }>;
  current_location?: {
    id: number;
    name: string;
  };
  years_of_service: number;
  attendance_rate?: number;
  last_clock_in?: string;
}

interface PageProps {
  staff: StaffMember[];
  pagination: any;
  metadata: any;
  features: {
    biometric_clock: boolean;
    mobile_clock: boolean;
    shift_swapping: boolean;
    performance_tracking: boolean;
    training_modules: boolean;
    payroll_integration: boolean;
  };
  stats?: {
    totalStaff: number;
    activeStaff: number;
    onLeave: number;
    presentToday: number;
    scheduledToday: number;
    averageAttendance: number;
  };
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Staff',
    href: '/staff',
  },
];

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'active':
      return 'default';
    case 'inactive':
      return 'secondary';
    case 'suspended':
      return 'destructive';
    case 'terminated':
      return 'outline';
    case 'on_leave':
      return 'secondary';
    default:
      return 'secondary';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'active':
      return <UserCheck className="h-3 w-3" />;
    case 'inactive':
    case 'suspended':
    case 'terminated':
      return <UserX className="h-3 w-3" />;
    case 'on_leave':
      return <CalendarCheck className="h-3 w-3" />;
    default:
      return null;
  }
};

function StaffIndexContent({ 
  staff, 
  pagination, 
  metadata, 
  features,
  stats 
}: PageProps) {
  const [selectedStaff, setSelectedStaff] = useState<number[]>([]);
  const [importDialogOpen, setImportDialogOpen] = useState(false);

  const columns: ColumnDef<StaffMember>[] = useMemo(() => [
    {
      id: 'select',
      header: ({ table }) => (
        <Checkbox
          checked={table.getIsAllPageRowsSelected()}
          onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
          aria-label="Select all"
        />
      ),
      cell: ({ row }) => (
        <Checkbox
          checked={row.getIsSelected()}
          onCheckedChange={(value) => row.toggleSelected(!!value)}
          aria-label="Select row"
        />
      ),
      enableSorting: false,
      enableHiding: false,
    },
    {
      accessorKey: 'employee',
      header: 'Employee',
      cell: ({ row }) => {
        const staff = row.original;
        const initials = `${staff.first_name[0]}${staff.last_name[0]}`.toUpperCase();
        
        return (
          <div className="flex items-center gap-3">
            <Avatar className="h-9 w-9">
              <AvatarImage src={staff.profile_photo_url || undefined} />
              <AvatarFallback>{initials}</AvatarFallback>
            </Avatar>
            <div>
              <Link 
                href={`/staff/${staff.id}`}
                className="font-medium hover:underline"
              >
                {staff.first_name} {staff.last_name}
              </Link>
              <div className="text-xs text-muted-foreground">
                {staff.employee_code}
              </div>
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: 'contact',
      header: 'Contact',
      cell: ({ row }) => {
        const staff = row.original;
        return (
          <div className="space-y-1">
            <div className="flex items-center gap-1 text-sm">
              <Mail className="h-3 w-3 text-muted-foreground" />
              <span className="text-muted-foreground">{staff.email}</span>
            </div>
            {staff.phone && (
              <div className="flex items-center gap-1 text-sm">
                <Phone className="h-3 w-3 text-muted-foreground" />
                <span className="text-muted-foreground">{staff.phone}</span>
              </div>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'roles',
      header: 'Role',
      cell: ({ row }) => {
        const roles = row.original.roles;
        if (!roles || roles.length === 0) {
          return <span className="text-muted-foreground">No role</span>;
        }
        
        const primaryRole = roles.reduce((prev, current) => 
          (prev.hierarchy_level > current.hierarchy_level) ? prev : current
        );
        
        return (
          <div className="flex items-center gap-2">
            <Shield className="h-3 w-3 text-muted-foreground" />
            <span className="text-sm">{primaryRole.name}</span>
            {roles.length > 1 && (
              <Badge variant="secondary" className="text-xs">
                +{roles.length - 1}
              </Badge>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'location',
      header: 'Location',
      cell: ({ row }) => {
        const location = row.original.current_location;
        return location ? (
          <span className="text-sm">{location.name}</span>
        ) : (
          <span className="text-sm text-muted-foreground">Not assigned</span>
        );
      },
    },
    {
      accessorKey: 'status',
      header: 'Status',
      cell: ({ row }) => {
        const status = row.original.status;
        return (
          <Badge variant={getStatusVariant(status)} className="gap-1">
            {getStatusIcon(status)}
            {status.replace('_', ' ')}
          </Badge>
        );
      },
    },
    {
      accessorKey: 'attendance',
      header: 'Attendance',
      cell: ({ row }) => {
        const staff = row.original;
        const rate = staff.attendance_rate;
        
        if (rate === undefined) {
          return <span className="text-sm text-muted-foreground">N/A</span>;
        }
        
        const color = rate >= 95 ? 'text-green-600' : 
                     rate >= 85 ? 'text-yellow-600' : 
                     'text-red-600';
        
        return (
          <div className="space-y-1">
            <span className={cn("text-sm font-medium", color)}>
              {rate.toFixed(1)}%
            </span>
            {staff.last_clock_in && (
              <div className="text-xs text-muted-foreground">
                Last: {new Date(staff.last_clock_in).toLocaleDateString()}
              </div>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'hire_date',
      header: 'Service',
      cell: ({ row }) => {
        const staff = row.original;
        return (
          <div className="space-y-1">
            <span className="text-sm">
              {staff.years_of_service} {staff.years_of_service === 1 ? 'year' : 'years'}
            </span>
            <div className="text-xs text-muted-foreground">
              Since {new Date(staff.hire_date).toLocaleDateString()}
            </div>
          </div>
        );
      },
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const staff = row.original;
        
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuItem asChild>
                <Link href={`/staff/${staff.id}`}>
                  <Eye className="mr-2 h-4 w-4" />
                  View Profile
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link href={`/staff/${staff.id}/edit`}>
                  <Edit className="mr-2 h-4 w-4" />
                  Edit
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link href={`/staff/${staff.id}/schedule`}>
                  <Calendar className="mr-2 h-4 w-4" />
                  View Schedule
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link href={`/staff/${staff.id}/attendance`}>
                  <Clock className="mr-2 h-4 w-4" />
                  Attendance History
                </Link>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                className="text-red-600"
                onClick={() => {
                  if (confirm(`Are you sure you want to delete ${staff.first_name} ${staff.last_name}?`)) {
                    router.delete(`/staff/${staff.id}`);
                  }
                }}
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ], []);

  const handleBulkAction = (action: string) => {
    switch (action) {
      case 'export':
        router.post('/staff/export', { ids: selectedStaff });
        break;
      case 'deactivate':
        if (confirm(`Are you sure you want to deactivate ${selectedStaff.length} staff members?`)) {
          router.post('/staff/bulk-update', { 
            ids: selectedStaff,
            status: 'inactive'
          });
        }
        break;
      case 'delete':
        if (confirm(`Are you sure you want to delete ${selectedStaff.length} staff members?`)) {
          router.delete('/staff/bulk-delete', {
            data: { ids: selectedStaff }
          });
        }
        break;
    }
  };

  return (
    <>
      <Head title="Staff Management" />

      <Page 
        title="Staff Management"
        description="Manage your team members, roles, and schedules"
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex items-center gap-2">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <Download className="mr-2 h-4 w-4" />
                  Export
                  <ChevronDown className="ml-2 h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuItem onClick={() => router.post('/staff/export', { format: 'csv' })}>
                  Export as CSV
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => router.post('/staff/export', { format: 'xlsx' })}>
                  Export as Excel
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => router.post('/staff/export', { format: 'pdf' })}>
                  Export as PDF
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
            
            <Button
              variant="outline"
              size="sm"
              onClick={() => setImportDialogOpen(true)}
            >
              <FileUp className="mr-2 h-4 w-4" />
              Import
            </Button>
            
            <Button asChild>
              <Link href="/staff/create">
                <Plus className="mr-2 h-4 w-4" />
                Add Staff Member
              </Link>
            </Button>
          </div>
        }
      >
        {/* Stats Cards */}
        {stats && (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Staff</CardTitle>
                <Users className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.totalStaff}</div>
                <p className="text-xs text-muted-foreground">
                  {stats.activeStaff} active, {stats.onLeave} on leave
                </p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Present Today</CardTitle>
                <UserCheck className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.presentToday}</div>
                <p className="text-xs text-muted-foreground">
                  Out of {stats.scheduledToday} scheduled
                </p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Avg Attendance</CardTitle>
                <Clock className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.averageAttendance.toFixed(1)}%</div>
                <p className="text-xs text-muted-foreground">
                  Last 30 days
                </p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Quick Actions</CardTitle>
                <Calendar className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent className="space-y-2">
                <Button variant="outline" size="sm" className="w-full justify-start" asChild>
                  <Link href="/staff/schedule">
                    <Calendar className="mr-2 h-4 w-4" />
                    Schedule
                  </Link>
                </Button>
                <Button variant="outline" size="sm" className="w-full justify-start" asChild>
                  <Link href="/staff/attendance">
                    <Clock className="mr-2 h-4 w-4" />
                    Attendance
                  </Link>
                </Button>
              </CardContent>
            </Card>
          </div>
        )}

        {/* Bulk Actions Alert */}
        {selectedStaff.length > 0 && (
          <Alert className="mb-4">
            <AlertCircle className="h-4 w-4" />
            <AlertDescription className="flex items-center justify-between">
              <span>{selectedStaff.length} staff member(s) selected</span>
              <div className="flex gap-2">
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => handleBulkAction('export')}
                >
                  Export Selected
                </Button>
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => handleBulkAction('deactivate')}
                >
                  Deactivate
                </Button>
                <Button 
                  size="sm" 
                  variant="destructive"
                  onClick={() => handleBulkAction('delete')}
                >
                  Delete
                </Button>
              </div>
            </AlertDescription>
          </Alert>
        )}

        {/* Data Table */}
        <Card>
          <CardContent className="p-0">
            {staff.length === 0 ? (
              <EmptyState
                icon={Users}
                title="No staff members yet"
                description="Start building your team by adding staff members"
                action={{
                  label: 'Add Staff Member',
                  href: '/staff/create',
                }}
              />
            ) : (
              <InertiaDataTable
                columns={columns}
                data={staff}
                pagination={pagination}
                metadata={metadata}
                routeName="staff.index"
                onRowSelectionChange={(rows) => {
                  setSelectedStaff(rows.map(r => r.id));
                }}
              />
            )}
          </CardContent>
        </Card>

        {/* Import Dialog */}
        <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Import Staff Members</DialogTitle>
              <DialogDescription>
                Upload a CSV or Excel file with staff information
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div className="border-2 border-dashed rounded-lg p-6 text-center">
                <FileUp className="mx-auto h-12 w-12 text-muted-foreground" />
                <p className="mt-2 text-sm text-muted-foreground">
                  Drop your file here or click to browse
                </p>
              </div>
              <div className="flex justify-between">
                <Button variant="outline" onClick={() => setImportDialogOpen(false)}>
                  Cancel
                </Button>
                <Button>Import</Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </Page>
    </>
  );
}

StaffIndexContent.layout = (page: React.ReactNode) => (
  <AppLayout>{page}</AppLayout>
);

export default StaffIndexContent;