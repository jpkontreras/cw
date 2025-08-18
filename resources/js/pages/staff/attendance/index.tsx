import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { InertiaDataTable } from '@/modules/data-table';
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Progress } from '@/components/ui/progress';
import { type BreadcrumbItem } from '@/types';
import { EmptyState } from '@/components/empty-state';
import { ColumnDef } from '@tanstack/react-table';
import {
  Clock,
  UserCheck,
  UserX,
  Calendar,
  MapPin,
  Fingerprint,
  Smartphone,
  Edit,
  Download,
  TrendingUp,
  TrendingDown,
  Users,
  Timer,
  Coffee,
  LogIn,
  LogOut,
  AlertCircle,
  CheckCircle,
  XCircle,
} from 'lucide-react';
import { format, differenceInMinutes, differenceInHours } from 'date-fns';
import { cn } from '@/lib/utils';

interface AttendanceRecord {
  id: number;
  staff_member: {
    id: number;
    first_name: string;
    last_name: string;
    employee_code: string;
    profile_photo_url: string | null;
  };
  location: {
    id: number;
    name: string;
  };
  clock_in_time: string;
  clock_out_time: string | null;
  clock_in_method: 'biometric' | 'pin' | 'mobile' | 'manual' | 'card' | 'facial';
  clock_out_method: 'biometric' | 'pin' | 'mobile' | 'manual' | 'card' | 'facial' | null;
  break_start: string | null;
  break_end: string | null;
  status: 'present' | 'late' | 'absent' | 'holiday' | 'leave' | 'half_day';
  overtime_minutes: number;
  shift?: {
    id: number;
    start_time: string;
    end_time: string;
  };
}

interface StaffMember {
  id: number;
  first_name: string;
  last_name: string;
  employee_code: string;
  profile_photo_url: string | null;
  is_clocked_in: boolean;
  last_clock_in?: string;
}

interface PageProps {
  attendance: AttendanceRecord[];
  activeStaff: StaffMember[];
  locations: Array<{ id: number; name: string }>;
  pagination: any;
  metadata: any;
  stats: {
    presentToday: number;
    lateToday: number;
    absentToday: number;
    averageWorkHours: number;
    overtimeHours: number;
    onTimeRate: number;
  };
  features: {
    biometric_clock: boolean;
    mobile_clock: boolean;
    facial_recognition: boolean;
  };
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Staff',
    href: '/staff',
  },
  {
    title: 'Attendance',
    href: '/staff/attendance',
  },
];

const getMethodIcon = (method: string) => {
  switch (method) {
    case 'biometric':
      return <Fingerprint className="h-3 w-3" />;
    case 'mobile':
      return <Smartphone className="h-3 w-3" />;
    case 'pin':
    case 'card':
    case 'manual':
    case 'facial':
    default:
      return <Clock className="h-3 w-3" />;
  }
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'present':
      return 'text-green-600';
    case 'late':
      return 'text-yellow-600';
    case 'absent':
      return 'text-red-600';
    case 'holiday':
      return 'text-blue-600';
    case 'leave':
      return 'text-purple-600';
    case 'half_day':
      return 'text-orange-600';
    default:
      return 'text-gray-600';
  }
};

function AttendanceIndexContent({
  attendance = [],
  activeStaff = [],
  locations = [],
  pagination,
  metadata,
  stats = {
    presentToday: 0,
    lateToday: 0,
    absentToday: 0,
    averageWorkHours: 0,
    overtimeHours: 0,
    onTimeRate: 0,
  },
  features = {
    biometric_clock: false,
    mobile_clock: false,
    facial_recognition: false,
  },
}: PageProps) {
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [selectedLocation, setSelectedLocation] = useState<string>('all');
  const [clockInDialogOpen, setClockInDialogOpen] = useState(false);
  const [selectedStaff, setSelectedStaff] = useState<StaffMember | null>(null);

  const columns: ColumnDef<AttendanceRecord>[] = [
    {
      accessorKey: 'employee',
      header: 'Employee',
      cell: ({ row }) => {
        const record = row.original;
        const initials = `${record.staff_member.first_name[0]}${record.staff_member.last_name[0]}`.toUpperCase();
        
        return (
          <div className="flex items-center gap-3">
            <Avatar className="h-9 w-9">
              <AvatarImage src={record.staff_member.profile_photo_url || undefined} />
              <AvatarFallback>{initials}</AvatarFallback>
            </Avatar>
            <div>
              <p className="font-medium">
                {record.staff_member.first_name} {record.staff_member.last_name}
              </p>
              <p className="text-xs text-muted-foreground">
                {record.staff_member.employee_code}
              </p>
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: 'clock_in',
      header: 'Clock In',
      cell: ({ row }) => {
        const record = row.original;
        const clockInTime = new Date(record.clock_in_time);
        const scheduledTime = record.shift ? new Date(record.shift.start_time) : null;
        const isLate = scheduledTime && clockInTime > scheduledTime;
        
        return (
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              {getMethodIcon(record.clock_in_method)}
              <span className={cn(
                "text-sm font-medium",
                isLate && "text-yellow-600"
              )}>
                {format(clockInTime, 'HH:mm:ss')}
              </span>
            </div>
            {scheduledTime && (
              <p className="text-xs text-muted-foreground">
                Scheduled: {format(scheduledTime, 'HH:mm')}
                {isLate && (
                  <span className="text-yellow-600 ml-1">
                    ({differenceInMinutes(clockInTime, scheduledTime)} min late)
                  </span>
                )}
              </p>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'clock_out',
      header: 'Clock Out',
      cell: ({ row }) => {
        const record = row.original;
        
        if (!record.clock_out_time) {
          return (
            <Badge variant="secondary" className="gap-1">
              <Timer className="h-3 w-3" />
              Active
            </Badge>
          );
        }
        
        const clockOutTime = new Date(record.clock_out_time);
        
        return (
          <div className="flex items-center gap-2">
            {getMethodIcon(record.clock_out_method || 'manual')}
            <span className="text-sm font-medium">
              {format(clockOutTime, 'HH:mm:ss')}
            </span>
          </div>
        );
      },
    },
    {
      accessorKey: 'break',
      header: 'Break',
      cell: ({ row }) => {
        const record = row.original;
        
        if (!record.break_start) {
          return <span className="text-sm text-muted-foreground">No break</span>;
        }
        
        const breakDuration = record.break_end 
          ? differenceInMinutes(new Date(record.break_end), new Date(record.break_start))
          : 0;
        
        return (
          <div className="flex items-center gap-1 text-sm">
            <Coffee className="h-3 w-3 text-muted-foreground" />
            <span>{breakDuration} min</span>
          </div>
        );
      },
    },
    {
      accessorKey: 'hours',
      header: 'Work Hours',
      cell: ({ row }) => {
        const record = row.original;
        
        if (!record.clock_out_time) {
          const hoursWorked = differenceInHours(new Date(), new Date(record.clock_in_time));
          return (
            <div className="space-y-1">
              <span className="text-sm font-medium">{hoursWorked}h (ongoing)</span>
            </div>
          );
        }
        
        const totalMinutes = differenceInMinutes(
          new Date(record.clock_out_time),
          new Date(record.clock_in_time)
        );
        
        let breakMinutes = 0;
        if (record.break_start && record.break_end) {
          breakMinutes = differenceInMinutes(
            new Date(record.break_end),
            new Date(record.break_start)
          );
        }
        
        const workMinutes = totalMinutes - breakMinutes;
        const hours = Math.floor(workMinutes / 60);
        const minutes = workMinutes % 60;
        
        return (
          <div className="space-y-1">
            <span className="text-sm font-medium">
              {hours}h {minutes}m
            </span>
            {record.overtime_minutes > 0 && (
              <Badge variant="secondary" className="text-xs">
                +{Math.floor(record.overtime_minutes / 60)}h OT
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
        const record = row.original;
        return (
          <div className="flex items-center gap-1 text-sm">
            <MapPin className="h-3 w-3 text-muted-foreground" />
            <span>{record.location.name}</span>
          </div>
        );
      },
    },
    {
      accessorKey: 'status',
      header: 'Status',
      cell: ({ row }) => {
        const status = row.original.status;
        const Icon = status === 'present' ? CheckCircle :
                    status === 'late' ? AlertCircle :
                    XCircle;
        
        return (
          <div className={cn("flex items-center gap-1", getStatusColor(status))}>
            <Icon className="h-4 w-4" />
            <span className="text-sm font-medium capitalize">
              {status.replace('_', ' ')}
            </span>
          </div>
        );
      },
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const record = row.original;
        
        return (
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              // Handle edit
            }}
          >
            <Edit className="h-4 w-4" />
          </Button>
        );
      },
    },
  ];

  const handleClockIn = (staffId: number, method: string) => {
    // Get location ID - use first location if "all" is selected and locations exist
    const locationId = selectedLocation === 'all' 
      ? (locations.length > 0 ? locations[0].id : null)
      : parseInt(selectedLocation);
    
    if (!locationId) {
      console.error('No location available for clock in');
      return;
    }
    
    router.post('/staff/attendance/clock-in', {
      staff_member_id: staffId,
      method: method,
      location_id: locationId,
    });
    setClockInDialogOpen(false);
  };

  const handleClockOut = (staffId: number) => {
    router.post('/staff/attendance/clock-out', {
      staff_member_id: staffId,
      method: 'manual',
    });
  };

  return (
    <>
      <Head title="Attendance Tracking" />

      <Page
        title="Attendance Tracking"
        description="Monitor staff attendance and work hours"
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm">
              <Download className="mr-2 h-4 w-4" />
              Export Report
            </Button>
            <Button onClick={() => setClockInDialogOpen(true)}>
              <LogIn className="mr-2 h-4 w-4" />
              Manual Clock In
            </Button>
          </div>
        }
      >
        {/* Stats Cards */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6 mb-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Present</CardTitle>
              <UserCheck className="h-4 w-4 text-green-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.presentToday || 0}</div>
              <Progress value={75} className="h-2 mt-2" />
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Late</CardTitle>
              <Clock className="h-4 w-4 text-yellow-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.lateToday || 0}</div>
              <p className="text-xs text-muted-foreground mt-1">
                {stats.presentToday > 0 
                  ? `${((stats.lateToday / stats.presentToday) * 100).toFixed(1)}% of present`
                  : 'No attendance yet'
                }
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Absent</CardTitle>
              <UserX className="h-4 w-4 text-red-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.absentToday || 0}</div>
              <p className="text-xs text-muted-foreground mt-1">
                Unexcused absences
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Avg Hours</CardTitle>
              <Timer className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {stats.averageWorkHours !== undefined 
                  ? `${stats.averageWorkHours.toFixed(1)}h`
                  : '0.0h'
                }
              </div>
              <p className="text-xs text-muted-foreground mt-1">
                Per employee today
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Overtime</CardTitle>
              <TrendingUp className="h-4 w-4 text-orange-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.overtimeHours || 0}h</div>
              <p className="text-xs text-muted-foreground mt-1">
                Total today
              </p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">On-Time Rate</CardTitle>
              <CheckCircle className="h-4 w-4 text-green-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.onTimeRate || 0}%</div>
              <div className="flex items-center text-xs text-green-600 mt-1">
                <TrendingUp className="h-3 w-3 mr-1" />
                +2.5% from yesterday
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Active Staff Section */}
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>Currently Clocked In</CardTitle>
            <CardDescription>
              Staff members currently working
            </CardDescription>
          </CardHeader>
          <CardContent>
            {activeStaff.length === 0 ? (
              <p className="text-sm text-muted-foreground text-center py-4">
                No staff currently clocked in
              </p>
            ) : (
              <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                {activeStaff.map((staff) => {
                  const initials = `${staff.first_name[0]}${staff.last_name[0]}`.toUpperCase();
                  const workingHours = staff.last_clock_in 
                    ? differenceInHours(new Date(), new Date(staff.last_clock_in))
                    : 0;
                  
                  return (
                    <div
                      key={staff.id}
                      className="flex items-center justify-between p-3 border rounded-lg"
                    >
                      <div className="flex items-center gap-3">
                        <Avatar>
                          <AvatarImage src={staff.profile_photo_url || undefined} />
                          <AvatarFallback>{initials}</AvatarFallback>
                        </Avatar>
                        <div>
                          <p className="font-medium text-sm">
                            {staff.first_name} {staff.last_name}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {workingHours}h working
                          </p>
                        </div>
                      </div>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleClockOut(staff.id)}
                      >
                        <LogOut className="h-4 w-4" />
                      </Button>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Filters */}
        <div className="flex items-center gap-4 mb-4">
          <div className="flex items-center gap-2">
            <Label htmlFor="date">Date:</Label>
            <Input
              id="date"
              type="date"
              value={selectedDate}
              onChange={(e) => setSelectedDate(e.target.value)}
              className="w-auto"
            />
          </div>
          
          <div className="flex items-center gap-2">
            <Label htmlFor="location">Location:</Label>
            <Select value={selectedLocation} onValueChange={setSelectedLocation}>
              <SelectTrigger className="w-[180px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Locations</SelectItem>
                {locations.map((location) => (
                  <SelectItem key={location.id} value={location.id.toString()}>
                    {location.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Attendance Table */}
        <Card>
          <CardContent className="p-0">
            {attendance.length === 0 ? (
              <EmptyState
                icon={Clock}
                title="No attendance records"
                description="No attendance records found for the selected date"
              />
            ) : (
              <InertiaDataTable
                columns={columns}
                data={attendance}
                pagination={pagination}
                metadata={metadata}
                routeName="staff.attendance.index"
              />
            )}
          </CardContent>
        </Card>

        {/* Manual Clock In Dialog */}
        <Dialog open={clockInDialogOpen} onOpenChange={setClockInDialogOpen}>
          <DialogContent className="sm:max-w-[425px]">
            <DialogHeader>
              <DialogTitle>Manual Clock In</DialogTitle>
              <DialogDescription>
                Manually clock in a staff member
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label>Select Staff Member</Label>
                <Select onValueChange={(value) => {
                  const staff = activeStaff.find(s => s.id.toString() === value);
                  setSelectedStaff(staff || null);
                }}>
                  <SelectTrigger>
                    <SelectValue placeholder="Choose staff member" />
                  </SelectTrigger>
                  <SelectContent>
                    {activeStaff.filter(s => !s.is_clocked_in).map((staff) => {
                      const initials = `${staff.first_name[0]}${staff.last_name[0]}`.toUpperCase();
                      return (
                        <SelectItem key={staff.id} value={staff.id.toString()}>
                          <div className="flex items-center gap-2">
                            <Avatar className="h-6 w-6">
                              <AvatarImage src={staff.profile_photo_url || undefined} />
                              <AvatarFallback className="text-xs">{initials}</AvatarFallback>
                            </Avatar>
                            {staff.first_name} {staff.last_name}
                            <span className="text-muted-foreground">
                              ({staff.employee_code})
                            </span>
                          </div>
                        </SelectItem>
                      );
                    })}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Clock In Method</Label>
                <div className="grid grid-cols-2 gap-2">
                  {['manual', 'pin', 'card', 'mobile'].map((method) => (
                    <Button
                      key={method}
                      variant="outline"
                      className="justify-start"
                      onClick={() => selectedStaff && handleClockIn(selectedStaff.id, method)}
                      disabled={!selectedStaff}
                    >
                      {getMethodIcon(method)}
                      <span className="ml-2 capitalize">{method}</span>
                    </Button>
                  ))}
                </div>
              </div>

              {features.biometric_clock && (
                <Button
                  variant="outline"
                  className="w-full justify-start"
                  onClick={() => selectedStaff && handleClockIn(selectedStaff.id, 'biometric')}
                  disabled={!selectedStaff}
                >
                  <Fingerprint className="mr-2 h-4 w-4" />
                  Biometric Scan
                </Button>
              )}
            </div>
          </DialogContent>
        </Dialog>
      </Page>
    </>
  );
}

AttendanceIndexContent.layout = (page: React.ReactNode) => (
  <AppLayout>{page}</AppLayout>
);

export default AttendanceIndexContent;