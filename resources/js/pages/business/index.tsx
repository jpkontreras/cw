import { Head, Link } from '@inertiajs/react'
import { Building2, Settings, Users } from 'lucide-react'
import AppLayout from '@/layouts/app-layout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface Business {
    id: number
    name: string
    slug: string
    type: string
    status: string
    email: string | null
    phone: string | null
    subscriptionTier: string
    isDemo: boolean
    createdAt: string
}

interface Props {
    businesses: Business[]
    currentBusiness: Business | null
}

export default function BusinessIndex({ businesses, currentBusiness }: Props) {
    return (
        <AppLayout>
            <Head title="Businesses" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight">Businesses</h2>
                        <p className="text-muted-foreground">
                            Manage your businesses and switch between them
                        </p>
                    </div>
                </div>

                {businesses.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Building2 className="h-12 w-12 text-muted-foreground mb-4" />
                            <p className="text-lg font-semibold mb-2">No businesses yet</p>
                            <p className="text-muted-foreground">
                                Your business will be created during the onboarding process
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {businesses.map((business) => (
                            <Card 
                                key={business.id} 
                                className={currentBusiness?.id === business.id ? 'ring-2 ring-primary' : ''}
                            >
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-lg">{business.name}</CardTitle>
                                        {currentBusiness?.id === business.id && (
                                            <Badge variant="default" className="ml-2">Current</Badge>
                                        )}
                                    </div>
                                    <CardDescription>
                                        {business.type.charAt(0).toUpperCase() + business.type.slice(1)} Business
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2 text-sm">
                                        <div className="flex items-center justify-between">
                                            <span className="text-muted-foreground">Status:</span>
                                            <Badge variant={business.status === 'active' ? 'success' : 'secondary'}>
                                                {business.status}
                                            </Badge>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-muted-foreground">Plan:</span>
                                            <Badge variant="outline">
                                                {business.subscriptionTier}
                                            </Badge>
                                        </div>
                                        {business.isDemo && (
                                            <Badge variant="warning" className="w-full justify-center">
                                                Demo Business
                                            </Badge>
                                        )}
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        {currentBusiness?.id !== business.id && (
                                            <Link 
                                                href={`/businesses/${business.id}/switch`} 
                                                method="post"
                                                as="button"
                                                className="w-full"
                                            >
                                                <Button variant="outline" className="w-full">
                                                    Switch to this business
                                                </Button>
                                            </Link>
                                        )}
                                        <div className="grid grid-cols-2 gap-2">
                                            <Link href={`/businesses/${business.id}`}>
                                                <Button variant="ghost" size="sm" className="w-full">
                                                    <Settings className="mr-2 h-4 w-4" />
                                                    Settings
                                                </Button>
                                            </Link>
                                            <Link href={`/businesses/${business.id}/users`}>
                                                <Button variant="ghost" size="sm" className="w-full">
                                                    <Users className="mr-2 h-4 w-4" />
                                                    Users
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    )
}