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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { type BreadcrumbItem } from '@/types';
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  Clock,
  Download,
  Filter,
  Plus,
  RefreshCw,
  Users,
  MapPin,
  AlertCircle,
  Copy,
  Edit,
  Trash2,
  UserCheck,
  UserX,
  Coffee,
  CalendarDays,
} from 'lucide-react';
import { format, addDays, startOfWeek, endOfWeek, eachDayOfInterval, isSameDay, isToday } from 'date-fns';
import { cn } from '@/lib/utils';

interface Shift {
  id: number;
  staff_member: {
    id: number;
    first_name: string;
    last_name: string;
    profile_photo_url: string | null;
  };
  location: {
    id: number;
    name: string;
  };
  start_time: string;
  end_time: string;
  break_duration: number;
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'no_show';
  notes: string | null;
}

interface StaffMember {
  id: number;
  first_name: string;
  last_name: string;
  profile_photo_url: string | null;
  current_location?: {
    id: number;
    name: string;
  };
}

interface Location {
  id: number;
  name: string;
}

interface PageProps {
  shifts: Shift[];
  staff: StaffMember[];
  locations: Location[];
  currentWeek: {
    start: string;
    end: string;
  };
  stats: {
    totalShifts: number;
    totalHours: number;
    staffScheduled: number;
    openShifts: number;
  };
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Staff',
    href: '/staff',
  },
  {
    title: 'Schedule',
    href: '/staff/schedule',
  },
];

const timeSlots = Array.from({ length: 24 }, (_, i) => {
  const hour = i.toString().padStart(2, '0');
  return `${hour}:00`;
});

const getShiftColor = (status: string) => {
  switch (status) {
    case 'scheduled':
      return 'bg-blue-100 border-blue-300 text-blue-900';
    case 'in_progress':
      return 'bg-yellow-100 border-yellow-300 text-yellow-900';
    case 'completed':
      return 'bg-green-100 border-green-300 text-green-900';
    case 'cancelled':
      return 'bg-gray-100 border-gray-300 text-gray-900';
    case 'no_show':
      return 'bg-red-100 border-red-300 text-red-900';
    default:
      return 'bg-gray-100 border-gray-300 text-gray-900';
  }
};

function StaffScheduleContent({ 
  shifts = [], 
  staff = [], 
  locations = [], 
  currentWeek = {
    start: new Date().toISOString(),
    end: new Date().toISOString()
  },
  stats = {
    totalShifts: 0,
    totalHours: 0,
    staffScheduled: 0,
    openShifts: 0
  }
}: PageProps) {
  const [selectedLocation, setSelectedLocation] = useState<string>('all');
  const [viewType, setViewType] = useState<'week' | 'day' | 'month'>('week');
  const [currentDate, setCurrentDate] = useState(new Date(currentWeek?.start || new Date().toISOString()));
  const [createShiftOpen, setCreateShiftOpen] = useState(false);
  const [selectedShift, setSelectedShift] = useState<Shift | null>(null);

  const weekStart = startOfWeek(currentDate, { weekStartsOn: 1 });
  const weekEnd = endOfWeek(currentDate, { weekStartsOn: 1 });
  const weekDays = eachDayOfInterval({ start: weekStart, end: weekEnd });

  const navigateWeek = (direction: 'prev' | 'next') => {
    const newDate = direction === 'prev' 
      ? addDays(currentDate, -7)
      : addDays(currentDate, 7);
    setCurrentDate(newDate);
    
    // Fetch new week data
    router.get('/staff/schedule', {
      week: format(newDate, 'yyyy-MM-dd'),
      location: selectedLocation,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const getShiftsForDay = (date: Date) => {
    return shifts.filter(shift => {
      const shiftDate = new Date(shift.start_time);
      return isSameDay(shiftDate, date) && 
        (selectedLocation === 'all' || shift.location.id.toString() === selectedLocation);
    });
  };

  const getStaffInitials = (staff: StaffMember) => {
    return `${staff.first_name[0]}${staff.last_name[0]}`.toUpperCase();
  };

  const handleCreateShift = () => {
    setCreateShiftOpen(true);
  };

  const handleDuplicateWeek = () => {
    if (confirm('Duplicate this week\'s schedule to next week?')) {
      router.post('/staff/schedule/duplicate', {
        from_week: format(weekStart, 'yyyy-MM-dd'),
        to_week: format(addDays(weekStart, 7), 'yyyy-MM-dd'),
      });
    }
  };

  const handleExportSchedule = () => {
    router.post('/staff/schedule/export', {
      week: format(weekStart, 'yyyy-MM-dd'),
      location: selectedLocation,
      format: 'pdf',
    });
  };

  return (
    <>
      <Head title="Staff Schedule" />

      <Page
        title="Staff Schedule"
        description="Manage shifts and work schedules"
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={handleDuplicateWeek}>
              <Copy className="mr-2 h-4 w-4" />
              Duplicate Week
            </Button>
            <Button variant="outline" size="sm" onClick={handleExportSchedule}>
              <Download className="mr-2 h-4 w-4" />
              Export
            </Button>
            <Button onClick={handleCreateShift}>
              <Plus className="mr-2 h-4 w-4" />
              Create Shift
            </Button>
          </div>
        }
      >
        {/* Stats Cards */}
        <div className="grid gap-4 md:grid-cols-4 mb-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Shifts</CardTitle>
              <Calendar className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalShifts}</div>
              <p className="text-xs text-muted-foreground">This week</p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Hours</CardTitle>
              <Clock className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalHours}h</div>
              <p className="text-xs text-muted-foreground">Scheduled work hours</p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Staff Scheduled</CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.staffScheduled}</div>
              <p className="text-xs text-muted-foreground">Unique staff members</p>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Open Shifts</CardTitle>
              <AlertCircle className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.openShifts}</div>
              <p className="text-xs text-muted-foreground">Need assignment</p>
            </CardContent>
          </Card>
        </div>

        {/* Filters and View Controls */}
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Select value={selectedLocation} onValueChange={setSelectedLocation}>
              <SelectTrigger className="w-[200px]">
                <SelectValue placeholder="All Locations" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Locations</SelectItem>
                {locations.map((location) => (
                  <SelectItem key={location.id} value={location.id.toString()}>
                    <div className="flex items-center gap-2">
                      <MapPin className="h-4 w-4" />
                      {location.name}
                    </div>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            <Tabs value={viewType} onValueChange={(v) => setViewType(v as any)}>
              <TabsList>
                <TabsTrigger value="day">Day</TabsTrigger>
                <TabsTrigger value="week">Week</TabsTrigger>
                <TabsTrigger value="month">Month</TabsTrigger>
              </TabsList>
            </Tabs>
          </div>

          <div className="flex items-center gap-2">
            <Button variant="outline" size="icon" onClick={() => navigateWeek('prev')}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button 
              variant="outline" 
              onClick={() => {
                setCurrentDate(new Date());
                router.get('/staff/schedule', { 
                  week: format(new Date(), 'yyyy-MM-dd'),
                  location: selectedLocation,
                });
              }}
            >
              Today
            </Button>
            <Button variant="outline" size="icon" onClick={() => navigateWeek('next')}>
              <ChevronRight className="h-4 w-4" />
            </Button>
            <span className="text-sm font-medium ml-2">
              {format(weekStart, 'MMM d')} - {format(weekEnd, 'MMM d, yyyy')}
            </span>
          </div>
        </div>

        {/* Schedule Grid */}
        <Card>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b">
                    <th className="text-left p-4 font-medium text-sm text-muted-foreground w-20">
                      Time
                    </th>
                    {weekDays.map((day) => (
                      <th key={day.toISOString()} className="text-center p-4 min-w-[140px]">
                        <div className={cn(
                          "font-medium",
                          isToday(day) && "text-primary"
                        )}>
                          {format(day, 'EEE')}
                        </div>
                        <div className={cn(
                          "text-2xl",
                          isToday(day) ? "text-primary font-bold" : "text-muted-foreground"
                        )}>
                          {format(day, 'd')}
                        </div>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {timeSlots.map((time) => (
                    <tr key={time} className="border-b">
                      <td className="p-4 text-sm text-muted-foreground">
                        {time}
                      </td>
                      {weekDays.map((day) => {
                        const dayShifts = getShiftsForDay(day).filter(shift => {
                          const shiftHour = new Date(shift.start_time).getHours();
                          const slotHour = parseInt(time.split(':')[0]);
                          return shiftHour === slotHour;
                        });

                        return (
                          <td key={day.toISOString()} className="p-2 relative h-20 border-l">
                            {dayShifts.map((shift) => {
                              const staff = shift.staff_member;
                              const duration = 
                                (new Date(shift.end_time).getTime() - new Date(shift.start_time).getTime()) 
                                / (1000 * 60 * 60);
                              const heightPercent = (duration / 1) * 100;

                              return (
                                <div
                                  key={shift.id}
                                  className={cn(
                                    "absolute left-1 right-1 p-2 rounded border cursor-pointer transition-all hover:shadow-md hover:z-10",
                                    getShiftColor(shift.status)
                                  )}
                                  style={{
                                    height: `${Math.min(heightPercent, 100)}%`,
                                    top: '0',
                                  }}
                                  onClick={() => setSelectedShift(shift)}
                                >
                                  <div className="flex items-center gap-2">
                                    <Avatar className="h-6 w-6">
                                      <AvatarImage src={staff.profile_photo_url || undefined} />
                                      <AvatarFallback className="text-xs">
                                        {getStaffInitials(staff)}
                                      </AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1 min-w-0">
                                      <p className="text-xs font-medium truncate">
                                        {staff.first_name} {staff.last_name[0]}.
                                      </p>
                                      <p className="text-xs opacity-80">
                                        {format(new Date(shift.start_time), 'HH:mm')} - 
                                        {format(new Date(shift.end_time), 'HH:mm')}
                                      </p>
                                    </div>
                                  </div>
                                </div>
                              );
                            })}
                          </td>
                        );
                      })}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        {/* Create Shift Dialog */}
        <Dialog open={createShiftOpen} onOpenChange={setCreateShiftOpen}>
          <DialogContent className="sm:max-w-[500px]">
            <DialogHeader>
              <DialogTitle>Create New Shift</DialogTitle>
              <DialogDescription>
                Schedule a new shift for a staff member
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label htmlFor="staff">Staff Member</Label>
                <Select>
                  <SelectTrigger>
                    <SelectValue placeholder="Select staff member" />
                  </SelectTrigger>
                  <SelectContent>
                    {staff.map((member) => (
                      <SelectItem key={member.id} value={member.id.toString()}>
                        <div className="flex items-center gap-2">
                          <Avatar className="h-6 w-6">
                            <AvatarImage src={member.profile_photo_url || undefined} />
                            <AvatarFallback className="text-xs">
                              {getStaffInitials(member)}
                            </AvatarFallback>
                          </Avatar>
                          {member.first_name} {member.last_name}
                          {member.current_location && (
                            <Badge variant="secondary" className="ml-2">
                              {member.current_location.name}
                            </Badge>
                          )}
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="location">Location</Label>
                <Select>
                  <SelectTrigger>
                    <SelectValue placeholder="Select location" />
                  </SelectTrigger>
                  <SelectContent>
                    {locations.map((location) => (
                      <SelectItem key={location.id} value={location.id.toString()}>
                        {location.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="date">Date</Label>
                  <Input
                    id="date"
                    type="date"
                    defaultValue={format(currentDate, 'yyyy-MM-dd')}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="break">Break Duration</Label>
                  <Select defaultValue="30">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">No break</SelectItem>
                      <SelectItem value="15">15 minutes</SelectItem>
                      <SelectItem value="30">30 minutes</SelectItem>
                      <SelectItem value="45">45 minutes</SelectItem>
                      <SelectItem value="60">1 hour</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="startTime">Start Time</Label>
                  <Input
                    id="startTime"
                    type="time"
                    defaultValue="09:00"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="endTime">End Time</Label>
                  <Input
                    id="endTime"
                    type="time"
                    defaultValue="17:00"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="notes">Notes (Optional)</Label>
                <Textarea
                  id="notes"
                  placeholder="Add any special instructions or notes..."
                  rows={3}
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setCreateShiftOpen(false)}>
                Cancel
              </Button>
              <Button onClick={() => {
                // Handle shift creation
                setCreateShiftOpen(false);
              }}>
                Create Shift
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Shift Details Dialog */}
        {selectedShift && (
          <Dialog open={!!selectedShift} onOpenChange={() => setSelectedShift(null)}>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Shift Details</DialogTitle>
              </DialogHeader>
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <Avatar>
                    <AvatarImage src={selectedShift.staff_member.profile_photo_url || undefined} />
                    <AvatarFallback>
                      {getStaffInitials(selectedShift.staff_member)}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="font-medium">
                      {selectedShift.staff_member.first_name} {selectedShift.staff_member.last_name}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      {selectedShift.location.name}
                    </p>
                  </div>
                </div>

                <div className="space-y-2">
                  <div className="flex items-center gap-2 text-sm">
                    <CalendarDays className="h-4 w-4 text-muted-foreground" />
                    <span>{format(new Date(selectedShift.start_time), 'EEEE, MMMM d, yyyy')}</span>
                  </div>
                  <div className="flex items-center gap-2 text-sm">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <span>
                      {format(new Date(selectedShift.start_time), 'HH:mm')} - 
                      {format(new Date(selectedShift.end_time), 'HH:mm')}
                    </span>
                  </div>
                  <div className="flex items-center gap-2 text-sm">
                    <Coffee className="h-4 w-4 text-muted-foreground" />
                    <span>{selectedShift.break_duration} minute break</span>
                  </div>
                </div>

                <div>
                  <Badge variant={
                    selectedShift.status === 'scheduled' ? 'default' :
                    selectedShift.status === 'in_progress' ? 'secondary' :
                    selectedShift.status === 'completed' ? 'outline' :
                    'destructive'
                  }>
                    {selectedShift.status.replace('_', ' ')}
                  </Badge>
                </div>

                {selectedShift.notes && (
                  <div className="p-3 bg-muted rounded-lg">
                    <p className="text-sm">{selectedShift.notes}</p>
                  </div>
                )}

                <div className="flex justify-end gap-2">
                  <Button variant="outline" size="sm">
                    <Edit className="mr-2 h-4 w-4" />
                    Edit
                  </Button>
                  <Button variant="outline" size="sm">
                    <RefreshCw className="mr-2 h-4 w-4" />
                    Swap
                  </Button>
                  <Button variant="destructive" size="sm">
                    <Trash2 className="mr-2 h-4 w-4" />
                    Cancel
                  </Button>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        )}
      </Page>
    </>
  );
}

StaffScheduleContent.layout = (page: React.ReactNode) => (
  <AppLayout>{page}</AppLayout>
);

export default StaffScheduleContent;