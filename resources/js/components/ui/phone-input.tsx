import React from 'react'
import { ChevronDown } from 'lucide-react'
import { cn } from '@/lib/utils'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

interface Country {
  code: string
  name: string
  dial: string
  flag: string
}

const countries: Country[] = [
  { code: 'CL', name: 'Chile', dial: '+56', flag: '🇨🇱' },
  { code: 'US', name: 'United States', dial: '+1', flag: '🇺🇸' },
  { code: 'AR', name: 'Argentina', dial: '+54', flag: '🇦🇷' },
  { code: 'BR', name: 'Brazil', dial: '+55', flag: '🇧🇷' },
  { code: 'PE', name: 'Peru', dial: '+51', flag: '🇵🇪' },
  { code: 'CO', name: 'Colombia', dial: '+57', flag: '🇨🇴' },
  { code: 'MX', name: 'Mexico', dial: '+52', flag: '🇲🇽' },
  { code: 'ES', name: 'Spain', dial: '+34', flag: '🇪🇸' },
]

interface PhoneInputProps {
  countryCode: string
  phoneNumber: string
  onCountryChange: (value: string) => void
  onPhoneChange: (value: string) => void
  error?: boolean
  className?: string
}

export function PhoneInput({
  countryCode,
  phoneNumber,
  onCountryChange,
  onPhoneChange,
  error = false,
  className
}: PhoneInputProps) {
  const selectedCountry = countries.find(c => c.dial === countryCode) || countries[0]

  return (
    <div className={cn("flex gap-2", className)}>
      {/* Country Code Selector */}
      <Select value={countryCode} onValueChange={onCountryChange}>
        <SelectTrigger className={cn(
          "w-[110px] h-9",
          error && "border-red-500 focus:ring-red-500"
        )}>
          <SelectValue>
            <div className="flex items-center gap-1.5">
              <span className="text-base">{selectedCountry.flag}</span>
              <span className="text-sm">{selectedCountry.dial}</span>
            </div>
          </SelectValue>
        </SelectTrigger>
        <SelectContent>
          {countries.map((country) => (
            <SelectItem key={country.code} value={country.dial}>
              <div className="flex items-center gap-2">
                <span className="text-base">{country.flag}</span>
                <span className="text-sm font-medium">{country.dial}</span>
                <span className="text-xs text-neutral-500">{country.name}</span>
              </div>
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* Phone Number Input */}
      <Input
        type="tel"
        value={phoneNumber}
        onChange={(e) => onPhoneChange(e.target.value)}
        placeholder="9 1234 5678"
        className={cn(
          "flex-1 h-9",
          error && "border-red-500 focus:ring-red-500"
        )}
      />
    </div>
  )
}