import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { 
  Card, 
  CardContent, 
  CardDescription, 
  CardHeader, 
  CardTitle 
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { DatePickerWithRange } from '@/components/ui/date-range-picker';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  Download,
  TrendingUp,
  TrendingDown,
  Users,
  Clock,
  Calendar,
  DollarSign,
  AlertCircle,
  FileText,
  Mail,
  Printer,
} from 'lucide-react';
import { addDays, format } from 'date-fns';

interface PageProps {
  reports?: {
    attendance: any[];
    performance: any[];
    payroll: any[];
    schedules: any[];
  };
  stats?: {
    averageAttendance: number;
    totalHours: number;
    overtimeHours: number;
    totalPayroll: number;
  };
}

// Sample data for charts
const attendanceData = [
  { day: 'Mon', present: 45, absent: 5, late: 3 },
  { day: 'Tue', present: 43, absent: 7, late: 2 },
  { day: 'Wed', present: 44, absent: 6, late: 4 },
  { day: 'Thu', present: 46, absent: 4, late: 1 },
  { day: 'Fri', present: 42, absent: 8, late: 5 },
  { day: 'Sat', present: 48, absent: 2, late: 2 },
  { day: 'Sun', present: 40, absent: 10, late: 3 },
];

const performanceData = [
  { month: 'Jan', efficiency: 85, satisfaction: 92 },
  { month: 'Feb', efficiency: 88, satisfaction: 90 },
  { month: 'Mar', efficiency: 82, satisfaction: 94 },
  { month: 'Apr', efficiency: 90, satisfaction: 91 },
  { month: 'May', efficiency: 87, satisfaction: 93 },
  { month: 'Jun', efficiency: 92, satisfaction: 95 },
];

const departmentData = [
  { name: 'Kitchen', value: 12, color: '#3b82f6' },
  { name: 'Service', value: 18, color: '#10b981' },
  { name: 'Management', value: 5, color: '#f59e0b' },
  { name: 'Support', value: 8, color: '#8b5cf6' },
];

function StaffReportsContent({ 
  reports = {
    attendance: [],
    performance: [],
    payroll: [],
    schedules: [],
  },
  stats = {
    averageAttendance: 92.5,
    totalHours: 1840,
    overtimeHours: 120,
    totalPayroll: 45000,
  }
}: PageProps) {
  const [dateRange, setDateRange] = useState({
    from: addDays(new Date(), -30),
    to: new Date(),
  });
  const [selectedReport, setSelectedReport] = useState('attendance');
  const [selectedLocation, setSelectedLocation] = useState('all');
  const [selectedDepartment, setSelectedDepartment] = useState('all');

  const handleExport = (format: string) => {
    console.log(`Exporting ${selectedReport} report as ${format}`);
    // Implement export logic
  };

  const handlePrint = () => {
    window.print();
  };

  const handleEmail = () => {
    console.log('Emailing report');
    // Implement email logic
  };

  return (
    <>
      <Page.Header
        title="Staff Reports"
        subtitle="Analytics and insights for workforce management"
        actions={
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={() => handleEmail()}>
              <Mail className="mr-2 h-4 w-4" />
              Email
            </Button>
            <Button variant="outline" size="sm" onClick={handlePrint}>
              <Printer className="mr-2 h-4 w-4" />
              Print
            </Button>
            <Button size="sm" onClick={() => handleExport('pdf')}>
              <Download className="mr-2 h-4 w-4" />
              Export
            </Button>
          </div>
        }
      />

      <Page.Content>
        {/* Filters */}
        <Card className="mb-6">
          <CardContent className="pt-6">
            <div className="flex flex-col sm:flex-row gap-4">
              <div className="flex-1">
                <label className="text-sm font-medium mb-2 block">Date Range</label>
                <DatePickerWithRange
                  date={dateRange}
                  onDateChange={setDateRange}
                />
              </div>
              <div className="w-full sm:w-48">
                <label className="text-sm font-medium mb-2 block">Location</label>
                <Select value={selectedLocation} onValueChange={setSelectedLocation}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Locations</SelectItem>
                    <SelectItem value="main">Main Branch</SelectItem>
                    <SelectItem value="downtown">Downtown</SelectItem>
                    <SelectItem value="airport">Airport</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="w-full sm:w-48">
                <label className="text-sm font-medium mb-2 block">Department</label>
                <Select value={selectedDepartment} onValueChange={setSelectedDepartment}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Departments</SelectItem>
                    <SelectItem value="kitchen">Kitchen</SelectItem>
                    <SelectItem value="service">Service</SelectItem>
                    <SelectItem value="management">Management</SelectItem>
                    <SelectItem value="support">Support</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Stats Overview */}
        <div className="grid gap-4 md:grid-cols-4 mb-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Avg Attendance</CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.averageAttendance}%</div>
              <p className="text-xs text-muted-foreground flex items-center mt-1">
                <TrendingUp className="h-3 w-3 mr-1 text-green-600" />
                +2.5% from last month
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Hours</CardTitle>
              <Clock className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalHours.toLocaleString()}</div>
              <p className="text-xs text-muted-foreground">
                This period
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Overtime</CardTitle>
              <AlertCircle className="h-4 w-4 text-orange-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.overtimeHours}h</div>
              <p className="text-xs text-muted-foreground flex items-center mt-1">
                <TrendingDown className="h-3 w-3 mr-1 text-red-600" />
                -15% from last month
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Payroll</CardTitle>
              <DollarSign className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">${stats.totalPayroll.toLocaleString()}</div>
              <p className="text-xs text-muted-foreground">
                Estimated this month
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Report Tabs */}
        <Tabs value={selectedReport} onValueChange={setSelectedReport}>
          <TabsList>
            <TabsTrigger value="attendance">Attendance</TabsTrigger>
            <TabsTrigger value="performance">Performance</TabsTrigger>
            <TabsTrigger value="schedules">Schedules</TabsTrigger>
            <TabsTrigger value="payroll">Payroll</TabsTrigger>
          </TabsList>

          <TabsContent value="attendance" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Weekly Attendance Overview</CardTitle>
                <CardDescription>
                  Staff attendance patterns for the selected period
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="h-[300px] flex items-center justify-center border-2 border-dashed rounded-lg">
                  <div className="text-center">
                    <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                    <p className="text-muted-foreground">Attendance chart visualization</p>
                    <p className="text-sm text-muted-foreground">Install recharts to enable charts</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-2">
              <Card>
                <CardHeader>
                  <CardTitle>Department Distribution</CardTitle>
                  <CardDescription>Staff count by department</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="h-[250px] flex items-center justify-center border-2 border-dashed rounded-lg">
                    <div className="text-center">
                      <Users className="h-10 w-10 text-muted-foreground mx-auto mb-2" />
                      <p className="text-sm text-muted-foreground">Department distribution chart</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Attendance Trends</CardTitle>
                  <CardDescription>6-month attendance rate</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="h-[250px] flex items-center justify-center border-2 border-dashed rounded-lg">
                    <div className="text-center">
                      <TrendingUp className="h-10 w-10 text-muted-foreground mx-auto mb-2" />
                      <p className="text-sm text-muted-foreground">Attendance trends chart</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          <TabsContent value="performance" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Performance Metrics</CardTitle>
                <CardDescription>
                  Efficiency and satisfaction scores over time
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="h-[300px] flex items-center justify-center border-2 border-dashed rounded-lg">
                  <div className="text-center">
                    <TrendingUp className="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                    <p className="text-muted-foreground">Performance metrics visualization</p>
                    <p className="text-sm text-muted-foreground">Install recharts to enable charts</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="schedules" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Schedule Coverage</CardTitle>
                <CardDescription>
                  Shift coverage and scheduling efficiency
                </CardDescription>
              </CardHeader>
              <CardContent className="flex items-center justify-center h-64">
                <div className="text-center">
                  <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                  <p className="text-muted-foreground">Schedule analytics coming soon</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="payroll" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Payroll Summary</CardTitle>
                <CardDescription>
                  Payroll costs and distribution
                </CardDescription>
              </CardHeader>
              <CardContent className="flex items-center justify-center h-64">
                <div className="text-center">
                  <DollarSign className="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                  <p className="text-muted-foreground">Payroll reports coming soon</p>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </Page.Content>
    </>
  );
}

export default function StaffReports(props: PageProps) {
  return (
    <AppLayout>
      <Head title="Staff Reports" />
      <Page>
        <StaffReportsContent {...props} />
      </Page>
    </AppLayout>
  );
}