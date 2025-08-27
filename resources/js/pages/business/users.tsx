import { Head, Link, useForm } from '@inertiajs/react'
import { Users, Plus, Mail, Shield, Trash2, Edit, UserCheck, UserX, Search } from 'lucide-react'
import AppLayout from '@/layouts/app-layout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { useState } from 'react'

interface BusinessUser {
  id: number
  userId: number
  businessId: number
  role: string
  status: string
  isOwner: boolean
  joinedAt: string
  lastAccessedAt: string | null
  userName: string
  userEmail: string
}

interface Business {
  id: number
  name: string
  slug: string
}

interface Props {
  business: Business
  users: BusinessUser[]
  currentUserId: number
  canManageUsers: boolean
}

export default function BusinessUsers({ business, users, currentUserId, canManageUsers }: Props) {
  const [isInviteOpen, setIsInviteOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')
  const [selectedRole, setSelectedRole] = useState<string>('all')

  const { data: inviteData, setData: setInviteData, post: postInvite, processing: processingInvite, errors: inviteErrors, reset: resetInvite } = useForm({
    email: '',
    role: 'member',
  })

  const { delete: deleteUser, processing: processingDelete } = useForm()

  const { patch: updateRole, processing: processingUpdate } = useForm()

  const handleInviteUser = (e: React.FormEvent) => {
    e.preventDefault()
    postInvite(`/businesses/${business.id}/users/invite`, {
      onSuccess: () => {
        setIsInviteOpen(false)
        resetInvite()
      },
    })
  }

  const handleRemoveUser = (userId: number) => {
    if (confirm('Are you sure you want to remove this user from the business?')) {
      deleteUser(`/businesses/${business.id}/users/${userId}`)
    }
  }

  const handleUpdateRole = (userId: number, newRole: string) => {
    updateRole(`/businesses/${business.id}/users/${userId}/role`, {
      data: { role: newRole },
    })
  }

  const filteredUsers = users.filter(user => {
    const matchesSearch = searchQuery === '' || 
      user.userName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      user.userEmail.toLowerCase().includes(searchQuery.toLowerCase())
    
    const matchesRole = selectedRole === 'all' || user.role === selectedRole
    
    return matchesSearch && matchesRole
  })

  const getRoleBadgeVariant = (role: string) => {
    switch (role) {
      case 'owner':
        return 'default'
      case 'manager':
        return 'secondary'
      case 'admin':
        return 'outline'
      default:
        return 'ghost'
    }
  }

  const getStatusBadgeVariant = (status: string) => {
    switch (status) {
      case 'active':
        return 'success'
      case 'pending':
        return 'warning'
      case 'inactive':
        return 'secondary'
      default:
        return 'outline'
    }
  }

  return (
    <AppLayout>
      <Head title={`Team Members - ${business.name}`} />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-3xl font-bold tracking-tight">Team Members</h2>
            <p className="text-muted-foreground">
              Manage users who have access to {business.name}
            </p>
          </div>
          {canManageUsers && (
            <Dialog open={isInviteOpen} onOpenChange={setIsInviteOpen}>
              <DialogTrigger asChild>
                <Button>
                  <Plus className="mr-2 h-4 w-4" />
                  Invite User
                </Button>
              </DialogTrigger>
              <DialogContent>
                <form onSubmit={handleInviteUser}>
                  <DialogHeader>
                    <DialogTitle>Invite User</DialogTitle>
                    <DialogDescription>
                      Send an invitation to add a new user to your business
                    </DialogDescription>
                  </DialogHeader>
                  <div className="space-y-4 py-4">
                    <div className="space-y-2">
                      <Label htmlFor="email">Email Address</Label>
                      <Input
                        id="email"
                        type="email"
                        value={inviteData.email}
                        onChange={(e) => setInviteData('email', e.target.value)}
                        placeholder="user@example.com"
                      />
                      {inviteErrors.email && (
                        <p className="text-sm text-red-600">{inviteErrors.email}</p>
                      )}
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="role">Role</Label>
                      <Select
                        value={inviteData.role}
                        onValueChange={(value) => setInviteData('role', value)}
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="member">Member</SelectItem>
                          <SelectItem value="admin">Admin</SelectItem>
                          <SelectItem value="manager">Manager</SelectItem>
                        </SelectContent>
                      </Select>
                      {inviteErrors.role && (
                        <p className="text-sm text-red-600">{inviteErrors.role}</p>
                      )}
                    </div>
                  </div>
                  <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => setIsInviteOpen(false)}>
                      Cancel
                    </Button>
                    <Button type="submit" disabled={processingInvite}>
                      <Mail className="mr-2 h-4 w-4" />
                      Send Invitation
                    </Button>
                  </DialogFooter>
                </form>
              </DialogContent>
            </Dialog>
          )}
        </div>

        {/* Statistics Cards */}
        <div className="grid gap-4 md:grid-cols-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Users</CardTitle>
              <Users className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{users.length}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Active Users</CardTitle>
              <UserCheck className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {users.filter(u => u.status === 'active').length}
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Pending Invites</CardTitle>
              <Mail className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {users.filter(u => u.status === 'pending').length}
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Admins</CardTitle>
              <Shield className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {users.filter(u => u.role === 'admin' || u.role === 'owner').length}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Filters */}
        <Card>
          <CardHeader>
            <CardTitle>Team Members</CardTitle>
            <CardDescription>
              View and manage all users who have access to this business
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex gap-4 mb-4">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search by name or email..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-9"
                />
              </div>
              <Select value={selectedRole} onValueChange={setSelectedRole}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Filter by role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Roles</SelectItem>
                  <SelectItem value="owner">Owner</SelectItem>
                  <SelectItem value="manager">Manager</SelectItem>
                  <SelectItem value="admin">Admin</SelectItem>
                  <SelectItem value="member">Member</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {filteredUsers.length === 0 ? (
              <Alert>
                <UserX className="h-4 w-4" />
                <AlertDescription>
                  No users found matching your search criteria.
                </AlertDescription>
              </Alert>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>User</TableHead>
                    <TableHead>Role</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Joined</TableHead>
                    <TableHead>Last Active</TableHead>
                    {canManageUsers && <TableHead className="text-right">Actions</TableHead>}
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredUsers.map((user) => (
                    <TableRow key={user.id}>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <Avatar className="h-8 w-8">
                            <AvatarImage src={`https://ui-avatars.com/api/?name=${encodeURIComponent(user.userName)}&background=random`} />
                            <AvatarFallback>
                              {user.userName.split(' ').map(n => n[0]).join('').toUpperCase()}
                            </AvatarFallback>
                          </Avatar>
                          <div>
                            <div className="font-medium">{user.userName}</div>
                            <div className="text-sm text-muted-foreground">{user.userEmail}</div>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Badge variant={getRoleBadgeVariant(user.role)}>
                            {user.role}
                          </Badge>
                          {user.isOwner && (
                            <Badge variant="default" className="text-xs">
                              Owner
                            </Badge>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant={getStatusBadgeVariant(user.status)}>
                          {user.status}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {new Date(user.joinedAt).toLocaleDateString()}
                      </TableCell>
                      <TableCell>
                        {user.lastAccessedAt 
                          ? new Date(user.lastAccessedAt).toLocaleDateString()
                          : 'Never'
                        }
                      </TableCell>
                      {canManageUsers && (
                        <TableCell className="text-right">
                          {!user.isOwner && user.userId !== currentUserId && (
                            <div className="flex justify-end gap-2">
                              <Select
                                value={user.role}
                                onValueChange={(value) => handleUpdateRole(user.userId, value)}
                                disabled={processingUpdate}
                              >
                                <SelectTrigger className="w-[100px] h-8">
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="member">Member</SelectItem>
                                  <SelectItem value="admin">Admin</SelectItem>
                                  <SelectItem value="manager">Manager</SelectItem>
                                </SelectContent>
                              </Select>
                              <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => handleRemoveUser(user.userId)}
                                disabled={processingDelete}
                              >
                                <Trash2 className="h-4 w-4 text-destructive" />
                              </Button>
                            </div>
                          )}
                          {user.userId === currentUserId && (
                            <span className="text-sm text-muted-foreground">You</span>
                          )}
                          {user.isOwner && user.userId !== currentUserId && (
                            <span className="text-sm text-muted-foreground">Owner</span>
                          )}
                        </TableCell>
                      )}
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  )
}