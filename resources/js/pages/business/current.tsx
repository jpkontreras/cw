import { router } from '@inertiajs/react'

export default function BusinessCurrent() {
  // This component just redirects to the current business show page
  // The actual redirection should be handled by the controller
  router.visit('/dashboard')
  
  return null
}