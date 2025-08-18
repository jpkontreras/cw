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
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { Progress } from '@/components/ui/progress';
import { type BreadcrumbItem } from '@/types';
import {
  User,
  Mail,
  Phone,
  MapPin,
  Calendar,
  Shield,
  Building2,
  Clock,
  Edit,
  Trash2,
  Download,
  Send,
  CreditCard,
  Contact,
  FileText,
  Award,
  TrendingUp,
  AlertCircle,
  CheckCircle,
  UserCheck,
  UserX,
  CalendarDays,
  Timer,
  DollarSign,
  Star,
} from 'lucide-react';
import { format, differenceInYears, differenceInDays } from 'date-fns';
import { cn } from '@/lib/utils';

interface StaffMember {
  id: number;
  employee_code: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  date_of_birth: string | null;
  hire_date: string;
  national_id: string;
  status: 'active' | 'inactive' | 'suspended' | 'terminated' | 'on_leave';
  profile_photo_url: string | null;
  address: {
    street?: string;
    city?: string;
    state?: string;
    postal_code?: string;
    country?: string;
  };
  emergency_contacts: Array<{
    name: string;
    phone: string;
    relationship: string;
    email?: string;
  }>;
  roles: Array<{
    id: number;
    name: string;
    hierarchy_level: number;
    location?: {
      id: number;
      name: string;
    };
  }>;
  locations: Array<{
    id: number;
    name: string;
    type: string;
  }>;
  years_of_service: number;
}

interface AttendanceSummary {
  total_days: number;
  present_days: number;
  late_days: number;
  absent_days: number;
  attendance_rate: number;
  average_hours: number;
  overtime_hours: number;
}

interface RecentShift {
  id: number;
  date: string;
  start_time: string;
  end_time: string;
  location: string;
  status: string;
}

interface Document {
  id: number;
  name: string;
  type: string;
  uploaded_at: string;
  expires_at?: string;
}

interface PageProps {
  staff: StaffMember;
  attendance: AttendanceSummary;
  recent_shifts: RecentShift[];
  documents: Document[];
  performance?: {
    rating: number;
    reviews_count: number;
    last_review?: string;
  };
}

function StaffShowContent({ 
  staff, 
  attendance,
  recent_shifts,
  documents,
  performance
}: PageProps) {
  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Staff',
      href: '/staff',
    },
    {
      title: `${staff.first_name} ${staff.last_name}`,
      href: `/staff/${staff.id}`,
    },
  ];

  const initials = `${staff.first_name[0]}${staff.last_name[0]}`.toUpperCase();
  const age = staff.date_of_birth 
    ? differenceInYears(new Date(), new Date(staff.date_of_birth))
    : null;

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'inactive':
        return 'bg-gray-100 text-gray-800 border-gray-200';
      case 'suspended':
        return 'bg-orange-100 text-orange-800 border-orange-200';
      case 'terminated':
        return 'bg-red-100 text-red-800 border-red-200';
      case 'on_leave':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'active':
        return <UserCheck className="h-4 w-4" />;
      case 'inactive':
      case 'suspended':
      case 'terminated':
        return <UserX className="h-4 w-4" />;
      case 'on_leave':
        return <CalendarDays className="h-4 w-4" />;
      default:
        return null;
    }
  };

  const handleDelete = () => {
    if (confirm(`Are you sure you want to delete ${staff.first_name} ${staff.last_name}?`)) {
      router.delete(`/staff/${staff.id}`);
    }
  };

  return (
    <>
      <Head title={`${staff.first_name} ${staff.last_name}`} />

      <Page
        title=""
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm">
              <Send className="mr-2 h-4 w-4" />
              Send Message
            </Button>
            <Button variant="outline" size="sm">
              <Download className="mr-2 h-4 w-4" />
              Export
            </Button>
            <Button variant="outline" size="sm" asChild>
              <Link href={`/staff/${staff.id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleDelete}
              className="text-red-600 hover:text-red-700"
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          </div>
        }
      >
        {/* Profile Header */}
        <div className="mb-6">
          <div className="flex items-start gap-6">
            <Avatar className="h-24 w-24">
              <AvatarImage src={staff.profile_photo_url || undefined} />
              <AvatarFallback className="text-2xl">{initials}</AvatarFallback>
            </Avatar>
            
            <div className="flex-1">
              <div className="flex items-center gap-3 mb-2">
                <h1 className="text-3xl font-bold">
                  {staff.first_name} {staff.last_name}
                </h1>
                <Badge 
                  variant="outline" 
                  className={cn("border", getStatusColor(staff.status))}
                >
                  {getStatusIcon(staff.status)}
                  <span className="ml-1 capitalize">{staff.status.replace('_', ' ')}</span>
                </Badge>
              </div>
              
              <div className="flex items-center gap-6 text-sm text-muted-foreground mb-4">
                <span className="flex items-center gap-1">
                  <User className="h-4 w-4" />
                  {staff.employee_code}
                </span>
                <span className="flex items-center gap-1">
                  <Mail className="h-4 w-4" />
                  {staff.email}
                </span>
                {staff.phone && (
                  <span className="flex items-center gap-1">
                    <Phone className="h-4 w-4" />
                    {staff.phone}
                  </span>
                )}
                <span className="flex items-center gap-1">
                  <Calendar className="h-4 w-4" />
                  {staff.years_of_service} years
                </span>
              </div>
              
              <div className="flex items-center gap-2">
                {staff.roles.map((role) => (
                  <Badge key={role.id} variant="secondary">
                    <Shield className="mr-1 h-3 w-3" />
                    {role.name}
                    {role.location && (
                      <span className="ml-1 text-xs opacity-70">
                        @ {role.location.name}
                      </span>
                    )}
                  </Badge>
                ))}
              </div>
            </div>
            
            {/* Quick Stats */}
            <div className="grid grid-cols-2 gap-4 min-w-[200px]">
              <Card>
                <CardContent className="p-4">
                  <div className="text-2xl font-bold">
                    {attendance.attendance_rate.toFixed(1)}%
                  </div>
                  <p className="text-xs text-muted-foreground">Attendance</p>
                </CardContent>
              </Card>
              <Card>
                <CardContent className="p-4">
                  <div className="text-2xl font-bold">
                    {attendance.average_hours.toFixed(1)}h
                  </div>
                  <p className="text-xs text-muted-foreground">Avg Daily</p>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>

        <Tabs defaultValue="overview" className="space-y-4">
          <TabsList>
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="attendance">Attendance</TabsTrigger>
            <TabsTrigger value="schedule">Schedule</TabsTrigger>
            <TabsTrigger value="documents">Documents</TabsTrigger>
            {performance && <TabsTrigger value="performance">Performance</TabsTrigger>}
            <TabsTrigger value="personal">Personal Info</TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium">Present Days</CardTitle>
                  <CheckCircle className="h-4 w-4 text-green-600" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{attendance.present_days}</div>
                  <p className="text-xs text-muted-foreground">
                    Out of {attendance.total_days} total
                  </p>
                </CardContent>
              </Card>
              
              <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium">Late Days</CardTitle>
                  <AlertCircle className="h-4 w-4 text-yellow-600" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{attendance.late_days}</div>
                  <p className="text-xs text-muted-foreground">
                    {((attendance.late_days / attendance.total_days) * 100).toFixed(1)}% rate
                  </p>
                </CardContent>
              </Card>
              
              <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium">Overtime</CardTitle>
                  <Timer className="h-4 w-4 text-orange-600" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{attendance.overtime_hours}h</div>
                  <p className="text-xs text-muted-foreground">
                    This month
                  </p>
                </CardContent>
              </Card>
              
              {performance && (
                <Card>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Performance</CardTitle>
                    <Star className="h-4 w-4 text-yellow-500" />
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">{performance.rating}/5</div>
                    <p className="text-xs text-muted-foreground">
                      {performance.reviews_count} reviews
                    </p>
                  </CardContent>
                </Card>
              )}
            </div>

            {/* Recent Shifts */}
            <Card>
              <CardHeader>
                <CardTitle>Recent Shifts</CardTitle>
                <CardDescription>Last 7 days of work</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {recent_shifts.map((shift) => (
                    <div key={shift.id} className="flex items-center justify-between p-3 border rounded-lg">
                      <div className="flex items-center gap-3">
                        <div className={cn(
                          "h-2 w-2 rounded-full",
                          shift.status === 'completed' ? 'bg-green-500' :
                          shift.status === 'scheduled' ? 'bg-blue-500' :
                          'bg-gray-500'
                        )} />
                        <div>
                          <p className="font-medium text-sm">
                            {format(new Date(shift.date), 'EEEE, MMM d')}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {shift.start_time} - {shift.end_time} at {shift.location}
                          </p>
                        </div>
                      </div>
                      <Badge variant="outline" className="text-xs">
                        {shift.status}
                      </Badge>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Personal Info Tab */}
          <TabsContent value="personal" className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              {/* Personal Details */}
              <Card>
                <CardHeader>
                  <CardTitle>Personal Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div>
                    <p className="text-sm text-muted-foreground">Full Name</p>
                    <p className="font-medium">{staff.first_name} {staff.last_name}</p>
                  </div>
                  
                  {staff.date_of_birth && (
                    <div>
                      <p className="text-sm text-muted-foreground">Date of Birth</p>
                      <p className="font-medium">
                        {format(new Date(staff.date_of_birth), 'MMMM d, yyyy')}
                        {age && <span className="text-muted-foreground ml-2">({age} years old)</span>}
                      </p>
                    </div>
                  )}
                  
                  <div>
                    <p className="text-sm text-muted-foreground">National ID (RUT)</p>
                    <p className="font-medium">{staff.national_id}</p>
                  </div>
                  
                  <div>
                    <p className="text-sm text-muted-foreground">Employee Code</p>
                    <p className="font-medium">{staff.employee_code}</p>
                  </div>
                  
                  <div>
                    <p className="text-sm text-muted-foreground">Hire Date</p>
                    <p className="font-medium">
                      {format(new Date(staff.hire_date), 'MMMM d, yyyy')}
                    </p>
                  </div>
                </CardContent>
              </Card>

              {/* Contact Information */}
              <Card>
                <CardHeader>
                  <CardTitle>Contact Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div>
                    <p className="text-sm text-muted-foreground">Email</p>
                    <p className="font-medium">{staff.email}</p>
                  </div>
                  
                  {staff.phone && (
                    <div>
                      <p className="text-sm text-muted-foreground">Phone</p>
                      <p className="font-medium">{staff.phone}</p>
                    </div>
                  )}
                  
                  {staff.address && Object.keys(staff.address).length > 0 && (
                    <div>
                      <p className="text-sm text-muted-foreground">Address</p>
                      <p className="font-medium">
                        {staff.address.street && `${staff.address.street}, `}
                        {staff.address.city && `${staff.address.city}, `}
                        {staff.address.state && `${staff.address.state} `}
                        {staff.address.postal_code}
                        {staff.address.country && `, ${staff.address.country}`}
                      </p>
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* Emergency Contacts */}
              <Card className="md:col-span-2">
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Contact className="h-5 w-5" />
                    Emergency Contacts
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid gap-4 md:grid-cols-2">
                    {staff.emergency_contacts.map((contact, index) => (
                      <div key={index} className="p-4 border rounded-lg space-y-2">
                        <div className="flex items-center justify-between">
                          <p className="font-medium">{contact.name}</p>
                          <Badge variant="secondary">{contact.relationship}</Badge>
                        </div>
                        <div className="space-y-1 text-sm text-muted-foreground">
                          <p className="flex items-center gap-1">
                            <Phone className="h-3 w-3" />
                            {contact.phone}
                          </p>
                          {contact.email && (
                            <p className="flex items-center gap-1">
                              <Mail className="h-3 w-3" />
                              {contact.email}
                            </p>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Documents Tab */}
          <TabsContent value="documents" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Documents</CardTitle>
                <CardDescription>
                  Employment documents and certifications
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {documents.map((doc) => {
                    const isExpiring = doc.expires_at && 
                      differenceInDays(new Date(doc.expires_at), new Date()) < 30;
                    
                    return (
                      <div key={doc.id} className="flex items-center justify-between p-3 border rounded-lg">
                        <div className="flex items-center gap-3">
                          <FileText className="h-5 w-5 text-muted-foreground" />
                          <div>
                            <p className="font-medium text-sm">{doc.name}</p>
                            <p className="text-xs text-muted-foreground">
                              Uploaded {format(new Date(doc.uploaded_at), 'MMM d, yyyy')}
                            </p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          {doc.expires_at && (
                            <Badge variant={isExpiring ? "destructive" : "secondary"}>
                              {isExpiring && <AlertCircle className="mr-1 h-3 w-3" />}
                              Expires {format(new Date(doc.expires_at), 'MMM d, yyyy')}
                            </Badge>
                          )}
                          <Badge variant="outline">{doc.type}</Badge>
                          <Button variant="ghost" size="sm">
                            <Download className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </Page>
    </>
  );
}

StaffShowContent.layout = (page: React.ReactNode) => (
  <AppLayout>{page}</AppLayout>
);

export default StaffShowContent;