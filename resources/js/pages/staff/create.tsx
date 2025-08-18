import { useForm } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem } from '@/types';
import { 
  User, 
  Mail, 
  Phone, 
  MapPin, 
  Calendar, 
  Shield, 
  Building2,
  AlertCircle,
  Plus,
  X,
  Save,
  ArrowLeft,
  CreditCard,
  Contact
} from 'lucide-react';
import { useState } from 'react';

interface Location {
  id: number;
  name: string;
  type: string;
}

interface Role {
  id: number;
  name: string;
  description: string;
  hierarchy_level: number;
}

interface PageProps {
  locations: Location[];
  roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Staff',
    href: '/staff',
  },
  {
    title: 'Add Staff Member',
    href: '/staff/create',
  },
];

interface EmergencyContact {
  name: string;
  phone: string;
  relationship: string;
  email?: string;
}

function CreateStaffContent({ locations, roles }: PageProps) {
  const [emergencyContacts, setEmergencyContacts] = useState<EmergencyContact[]>([
    { name: '', phone: '', relationship: '' }
  ]);

  const { data, setData, post, processing, errors, reset } = useForm({
    // Personal Information
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    dateOfBirth: '',
    nationalId: '',
    employeeCode: '',
    
    // Address
    address: {
      street: '',
      city: '',
      state: '',
      postalCode: '',
      country: 'Chile',
    },
    
    // Employment
    hireDate: new Date().toISOString().split('T')[0],
    primaryLocationId: '',
    roleIds: [] as number[],
    
    // Emergency Contacts
    emergencyContacts: emergencyContacts,
    
    // Banking (optional)
    bankDetails: {
      bankName: '',
      accountType: '',
      accountNumber: '',
      routingNumber: '',
    },
    
    // Settings
    sendWelcomeEmail: true,
    createSystemAccount: true,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/staff', {
      preserveScroll: true,
      onSuccess: () => reset(),
    });
  };

  const addEmergencyContact = () => {
    const newContacts = [...emergencyContacts, { name: '', phone: '', relationship: '' }];
    setEmergencyContacts(newContacts);
    setData('emergencyContacts', newContacts);
  };

  const removeEmergencyContact = (index: number) => {
    const newContacts = emergencyContacts.filter((_, i) => i !== index);
    setEmergencyContacts(newContacts);
    setData('emergencyContacts', newContacts);
  };

  const updateEmergencyContact = (index: number, field: keyof EmergencyContact, value: string) => {
    const newContacts = [...emergencyContacts];
    newContacts[index] = { ...newContacts[index], [field]: value };
    setEmergencyContacts(newContacts);
    setData('emergencyContacts', newContacts);
  };

  const toggleRole = (roleId: number) => {
    const newRoles = data.roleIds.includes(roleId)
      ? data.roleIds.filter(id => id !== roleId)
      : [...data.roleIds, roleId];
    setData('roleIds', newRoles);
  };

  return (
    <>
      <Head title="Add Staff Member" />

      <Page
        title="Add Staff Member"
        description="Create a new staff member profile"
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link href="/staff">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Cancel
              </Link>
            </Button>
          </div>
        }
      >
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Personal Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <User className="h-5 w-5" />
                Personal Information
              </CardTitle>
              <CardDescription>
                Basic information about the staff member
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="firstName">First Name *</Label>
                  <Input
                    id="firstName"
                    value={data.firstName}
                    onChange={e => setData('firstName', e.target.value)}
                    placeholder="John"
                    required
                  />
                  {errors.firstName && (
                    <p className="text-sm text-red-500">{errors.firstName}</p>
                  )}
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="lastName">Last Name *</Label>
                  <Input
                    id="lastName"
                    value={data.lastName}
                    onChange={e => setData('lastName', e.target.value)}
                    placeholder="Doe"
                    required
                  />
                  {errors.lastName && (
                    <p className="text-sm text-red-500">{errors.lastName}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="email">Email Address *</Label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      id="email"
                      type="email"
                      value={data.email}
                      onChange={e => setData('email', e.target.value)}
                      placeholder="john.doe@example.com"
                      className="pl-10"
                      required
                    />
                  </div>
                  {errors.email && (
                    <p className="text-sm text-red-500">{errors.email}</p>
                  )}
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="phone">Phone Number</Label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      id="phone"
                      type="tel"
                      value={data.phone}
                      onChange={e => setData('phone', e.target.value)}
                      placeholder="+56 9 1234 5678"
                      className="pl-10"
                    />
                  </div>
                  {errors.phone && (
                    <p className="text-sm text-red-500">{errors.phone}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="dateOfBirth">Date of Birth *</Label>
                  <Input
                    id="dateOfBirth"
                    type="date"
                    value={data.dateOfBirth}
                    onChange={e => setData('dateOfBirth', e.target.value)}
                    required
                  />
                  {errors.dateOfBirth && (
                    <p className="text-sm text-red-500">{errors.dateOfBirth}</p>
                  )}
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="nationalId">National ID (RUT) *</Label>
                  <Input
                    id="nationalId"
                    value={data.nationalId}
                    onChange={e => setData('nationalId', e.target.value)}
                    placeholder="12.345.678-9"
                    required
                  />
                  {errors.nationalId && (
                    <p className="text-sm text-red-500">{errors.nationalId}</p>
                  )}
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="employeeCode">Employee Code *</Label>
                  <Input
                    id="employeeCode"
                    value={data.employeeCode}
                    onChange={e => setData('employeeCode', e.target.value)}
                    placeholder="EMP001"
                    required
                  />
                  {errors.employeeCode && (
                    <p className="text-sm text-red-500">{errors.employeeCode}</p>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Address */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <MapPin className="h-5 w-5" />
                Address
              </CardTitle>
              <CardDescription>
                Residential address information
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="street">Street Address</Label>
                <Input
                  id="street"
                  value={data.address.street}
                  onChange={e => setData('address', { ...data.address, street: e.target.value })}
                  placeholder="123 Main Street"
                />
              </div>
              
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="city">City</Label>
                  <Input
                    id="city"
                    value={data.address.city}
                    onChange={e => setData('address', { ...data.address, city: e.target.value })}
                    placeholder="Santiago"
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="state">State/Region</Label>
                  <Input
                    id="state"
                    value={data.address.state}
                    onChange={e => setData('address', { ...data.address, state: e.target.value })}
                    placeholder="Región Metropolitana"
                  />
                </div>
              </div>
              
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="postalCode">Postal Code</Label>
                  <Input
                    id="postalCode"
                    value={data.address.postalCode}
                    onChange={e => setData('address', { ...data.address, postalCode: e.target.value })}
                    placeholder="7500000"
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="country">Country</Label>
                  <Input
                    id="country"
                    value={data.address.country}
                    onChange={e => setData('address', { ...data.address, country: e.target.value })}
                    placeholder="Chile"
                  />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Employment Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5" />
                Employment Information
              </CardTitle>
              <CardDescription>
                Work location, role, and start date
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="hireDate">Hire Date *</Label>
                  <Input
                    id="hireDate"
                    type="date"
                    value={data.hireDate}
                    onChange={e => setData('hireDate', e.target.value)}
                    required
                  />
                  {errors.hireDate && (
                    <p className="text-sm text-red-500">{errors.hireDate}</p>
                  )}
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="primaryLocationId">Primary Location *</Label>
                  <Select
                    value={data.primaryLocationId}
                    onValueChange={(value) => setData('primaryLocationId', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select location" />
                    </SelectTrigger>
                    <SelectContent>
                      {locations.map((location) => (
                        <SelectItem key={location.id} value={location.id.toString()}>
                          <div className="flex items-center gap-2">
                            <Building2 className="h-4 w-4" />
                            {location.name}
                            <Badge variant="secondary" className="ml-2">
                              {location.type}
                            </Badge>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.primaryLocationId && (
                    <p className="text-sm text-red-500">{errors.primaryLocationId}</p>
                  )}
                </div>
              </div>

              <Separator />

              <div className="space-y-2">
                <Label>Roles & Permissions</Label>
                <div className="space-y-2">
                  {roles.map((role) => (
                    <div key={role.id} className="flex items-start space-x-3 p-3 rounded-lg border">
                      <Checkbox
                        id={`role-${role.id}`}
                        checked={data.roleIds.includes(role.id)}
                        onCheckedChange={() => toggleRole(role.id)}
                      />
                      <div className="space-y-1 flex-1">
                        <Label
                          htmlFor={`role-${role.id}`}
                          className="flex items-center gap-2 cursor-pointer"
                        >
                          <Shield className="h-4 w-4" />
                          {role.name}
                          <Badge variant="outline" className="ml-auto">
                            Level {role.hierarchy_level}
                          </Badge>
                        </Label>
                        <p className="text-sm text-muted-foreground">
                          {role.description}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
                {errors.roleIds && (
                  <p className="text-sm text-red-500">{errors.roleIds}</p>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Emergency Contacts */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Contact className="h-5 w-5" />
                Emergency Contacts
              </CardTitle>
              <CardDescription>
                Add at least one emergency contact
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {emergencyContacts.map((contact, index) => (
                <div key={index} className="space-y-4 p-4 border rounded-lg">
                  <div className="flex items-center justify-between">
                    <h4 className="font-medium">Contact {index + 1}</h4>
                    {emergencyContacts.length > 1 && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeEmergencyContact(index)}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                  
                  <div className="grid grid-cols-3 gap-4">
                    <div className="space-y-2">
                      <Label>Name *</Label>
                      <Input
                        value={contact.name}
                        onChange={e => updateEmergencyContact(index, 'name', e.target.value)}
                        placeholder="Contact name"
                        required
                      />
                    </div>
                    
                    <div className="space-y-2">
                      <Label>Phone *</Label>
                      <Input
                        value={contact.phone}
                        onChange={e => updateEmergencyContact(index, 'phone', e.target.value)}
                        placeholder="+56 9 1234 5678"
                        required
                      />
                    </div>
                    
                    <div className="space-y-2">
                      <Label>Relationship *</Label>
                      <Input
                        value={contact.relationship}
                        onChange={e => updateEmergencyContact(index, 'relationship', e.target.value)}
                        placeholder="Spouse, Parent, etc."
                        required
                      />
                    </div>
                  </div>
                  
                  <div className="space-y-2">
                    <Label>Email (Optional)</Label>
                    <Input
                      type="email"
                      value={contact.email || ''}
                      onChange={e => updateEmergencyContact(index, 'email', e.target.value)}
                      placeholder="contact@example.com"
                    />
                  </div>
                </div>
              ))}
              
              <Button
                type="button"
                variant="outline"
                onClick={addEmergencyContact}
                className="w-full"
              >
                <Plus className="mr-2 h-4 w-4" />
                Add Another Contact
              </Button>
            </CardContent>
          </Card>

          {/* Account Settings */}
          <Card>
            <CardHeader>
              <CardTitle>Account Settings</CardTitle>
              <CardDescription>
                System account and notification preferences
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="createSystemAccount"
                  checked={data.createSystemAccount}
                  onCheckedChange={(checked) => setData('createSystemAccount', checked as boolean)}
                />
                <Label htmlFor="createSystemAccount" className="cursor-pointer">
                  Create system login account
                </Label>
              </div>
              
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="sendWelcomeEmail"
                  checked={data.sendWelcomeEmail}
                  onCheckedChange={(checked) => setData('sendWelcomeEmail', checked as boolean)}
                />
                <Label htmlFor="sendWelcomeEmail" className="cursor-pointer">
                  Send welcome email with login credentials
                </Label>
              </div>
            </CardContent>
          </Card>

          {/* Form Actions */}
          <div className="flex items-center justify-between">
            <Button variant="outline" asChild>
              <Link href="/staff">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Cancel
              </Link>
            </Button>
            
            <Button type="submit" disabled={processing}>
              <Save className="mr-2 h-4 w-4" />
              {processing ? 'Creating...' : 'Create Staff Member'}
            </Button>
          </div>
        </form>
      </Page>
    </>
  );
}

CreateStaffContent.layout = (page: React.ReactNode) => (
  <AppLayout>{page}</AppLayout>
);

export default CreateStaffContent;