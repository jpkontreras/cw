import { Head, useForm } from '@inertiajs/react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ArrowLeft, CheckCircle2, Edit } from 'lucide-react'

interface ReviewProps {
  progress: any
  data: {
    account?: any
    business?: any
    location?: any
    configuration?: any
  }
}

export default function Review({ progress, data }: ReviewProps) {
  const { post, processing } = useForm({})

  const handleComplete = () => {
    post('/onboarding/complete')
  }

  const formatValue = (value: any): string => {
    if (Array.isArray(value)) {
      return value.join(', ')
    }
    if (typeof value === 'object' && value !== null) {
      return JSON.stringify(value, null, 2)
    }
    return value?.toString() || 'Not set'
  }

  return (
    <>
      <Head title="Review - Onboarding" />
      
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
        <div className="max-w-4xl w-full">
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
              <CheckCircle2 className="h-8 w-8 text-green-600" />
            </div>
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Review Your Information</h1>
            <p className="text-gray-600">Please review all your information before completing the setup</p>
          </div>

          <div className="space-y-6">
            {/* Account Information */}
            {data.account && (
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <div>
                      <CardTitle>Account Information</CardTitle>
                      <CardDescription>Your personal details</CardDescription>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => window.location.href = '/onboarding/account'}
                    >
                      <Edit className="h-4 w-4 mr-2" />
                      Edit
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <dl className="grid gap-3">
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Name</dt>
                      <dd className="col-span-2">{data.account.firstName} {data.account.lastName}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Email</dt>
                      <dd className="col-span-2">{data.account.email}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Phone</dt>
                      <dd className="col-span-2">{data.account.phone}</dd>
                    </div>
                  </dl>
                </CardContent>
              </Card>
            )}

            {/* Business Information */}
            {data.business && (
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <div>
                      <CardTitle>Business Information</CardTitle>
                      <CardDescription>Your restaurant details</CardDescription>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => window.location.href = '/onboarding/business'}
                    >
                      <Edit className="h-4 w-4 mr-2" />
                      Edit
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <dl className="grid gap-3">
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Business Name</dt>
                      <dd className="col-span-2">{data.business.businessName}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Legal Name</dt>
                      <dd className="col-span-2">{data.business.legalName || data.business.businessName}</dd>
                    </div>
                    {data.business.taxId && (
                      <div className="grid grid-cols-3 gap-4">
                        <dt className="font-medium text-gray-500">Tax ID (RUT)</dt>
                        <dd className="col-span-2">{data.business.taxId}</dd>
                      </div>
                    )}
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Business Type</dt>
                      <dd className="col-span-2 capitalize">{data.business.businessType?.replace('_', ' ')}</dd>
                    </div>
                    {data.business.website && (
                      <div className="grid grid-cols-3 gap-4">
                        <dt className="font-medium text-gray-500">Website</dt>
                        <dd className="col-span-2">{data.business.website}</dd>
                      </div>
                    )}
                  </dl>
                </CardContent>
              </Card>
            )}

            {/* Location Information */}
            {data.location && (
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <div>
                      <CardTitle>Location Information</CardTitle>
                      <CardDescription>Your primary location</CardDescription>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => window.location.href = '/onboarding/location'}
                    >
                      <Edit className="h-4 w-4 mr-2" />
                      Edit
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <dl className="grid gap-3">
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Location Name</dt>
                      <dd className="col-span-2">{data.location.name}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Address</dt>
                      <dd className="col-span-2">
                        {data.location.address}, {data.location.city}
                        {data.location.state && `, ${data.location.state}`}
                        {data.location.postalCode && ` ${data.location.postalCode}`}
                      </dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Phone</dt>
                      <dd className="col-span-2">{data.location.phone}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Capabilities</dt>
                      <dd className="col-span-2 capitalize">
                        {data.location.capabilities?.map((c: string) => c.replace('_', ' ')).join(', ')}
                      </dd>
                    </div>
                  </dl>
                </CardContent>
              </Card>
            )}

            {/* Configuration */}
            {data.configuration && (
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <div>
                      <CardTitle>System Configuration</CardTitle>
                      <CardDescription>Your preferences</CardDescription>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => window.location.href = '/onboarding/configuration'}
                    >
                      <Edit className="h-4 w-4 mr-2" />
                      Edit
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <dl className="grid gap-3">
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Language</dt>
                      <dd className="col-span-2">{data.configuration.language === 'es' ? 'Español' : 'English'}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Currency</dt>
                      <dd className="col-span-2">{data.configuration.currency}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Timezone</dt>
                      <dd className="col-span-2">{data.configuration.timezone}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Date Format</dt>
                      <dd className="col-span-2">{data.configuration.dateFormat}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                      <dt className="font-medium text-gray-500">Time Format</dt>
                      <dd className="col-span-2">{data.configuration.timeFormat}</dd>
                    </div>
                  </dl>
                </CardContent>
              </Card>
            )}

            <Alert className="bg-blue-50">
              <AlertDescription>
                By completing the setup, you confirm that all the information provided is accurate and you agree to our terms of service.
              </AlertDescription>
            </Alert>

            <div className="flex justify-between">
              <Button
                variant="outline"
                onClick={() => window.history.back()}
              >
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back
              </Button>
              
              <Button 
                size="lg"
                onClick={handleComplete}
                disabled={processing}
                className="bg-green-600 hover:bg-green-700"
              >
                <CheckCircle2 className="mr-2 h-5 w-5" />
                Complete Setup
              </Button>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}