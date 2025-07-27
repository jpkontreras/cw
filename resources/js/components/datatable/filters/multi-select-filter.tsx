import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
} from '@/components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { cn } from '@/lib/utils'
import { Check, ChevronDown, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import type { FilterOption, MultiSelectFilterConfig, SingleFilterProps } from './types'

export function MultiSelectFilter({
  config,
  value,
  onChange,
  className,
}: SingleFilterProps<MultiSelectFilterConfig>) {
  const [options, setOptions] = useState<FilterOption[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const [open, setOpen] = useState(false)

  const selectedValues = (value as string[]) || []

  useEffect(() => {
    const loadOptions = async () => {
      if (typeof config.options === 'function') {
        setIsLoading(true)
        try {
          const loadedOptions = await config.options()
          setOptions(loadedOptions)
        } catch (error) {
          console.error('Failed to load filter options:', error)
          setOptions([])
        } finally {
          setIsLoading(false)
        }
      } else {
        setOptions(config.options)
      }
    }

    loadOptions()
  }, [config.options])

  const handleSelect = (optionValue: string) => {
    const newValues = selectedValues.includes(optionValue)
      ? selectedValues.filter((v) => v !== optionValue)
      : [...selectedValues, optionValue]

    // Check max items limit
    if (config.maxItems && newValues.length > config.maxItems) {
      return
    }

    onChange(newValues.length > 0 ? newValues : undefined)
  }

  const handleClear = () => {
    onChange(undefined)
    setOpen(false)
  }

  const selectedOptions = options.filter((opt) => selectedValues.includes(opt.value))

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn(
            'h-10 justify-between',
            config.width || 'w-[200px]',
            !selectedValues.length && 'text-muted-foreground',
            className
          )}
          disabled={config.disabled || isLoading}
        >
          <div className="flex items-center gap-1">
            {config.icon && <config.icon className="mr-2 h-4 w-4 shrink-0" />}
            <span className="truncate">
              {isLoading
                ? 'Loading...'
                : selectedValues.length > 0
                ? `${selectedValues.length} selected`
                : config.placeholder || `Select ${config.label}`}
            </span>
          </div>
          <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[200px] p-0" align="start">
        <Command>
          <CommandInput placeholder={`Search ${config.label.toLowerCase()}...`} />
          <CommandEmpty>No {config.label.toLowerCase()} found.</CommandEmpty>
          <CommandGroup>
            {options.map((option) => (
              <CommandItem
                key={option.value}
                value={option.value}
                onSelect={() => handleSelect(option.value)}
                disabled={option.disabled}
              >
                <Check
                  className={cn(
                    'mr-2 h-4 w-4',
                    selectedValues.includes(option.value) ? 'opacity-100' : 'opacity-0'
                  )}
                />
                <div className="flex items-center">
                  {option.icon && <option.icon className="mr-2 h-4 w-4" />}
                  {option.label}
                </div>
              </CommandItem>
            ))}
          </CommandGroup>
        </Command>
        {selectedValues.length > 0 && (
          <div className="border-t p-2">
            <Button
              variant="ghost"
              size="sm"
              className="h-8 w-full"
              onClick={handleClear}
            >
              <X className="mr-2 h-3.5 w-3.5" />
              Clear selection
            </Button>
          </div>
        )}
      </PopoverContent>
    </Popover>
  )
}

// Alternative compact display for selected values
export function MultiSelectFilterCompact({
  config,
  value,
  onChange,
  className,
}: SingleFilterProps<MultiSelectFilterConfig>) {
  const [options, setOptions] = useState<FilterOption[]>([])
  const selectedValues = (value as string[]) || []

  useEffect(() => {
    const loadOptions = async () => {
      if (typeof config.options === 'function') {
        try {
          const loadedOptions = await config.options()
          setOptions(loadedOptions)
        } catch (error) {
          console.error('Failed to load filter options:', error)
          setOptions([])
        }
      } else {
        setOptions(config.options)
      }
    }

    loadOptions()
  }, [config.options])

  const selectedOptions = options.filter((opt) => selectedValues.includes(opt.value))

  const handleRemove = (optionValue: string) => {
    const newValues = selectedValues.filter((v) => v !== optionValue)
    onChange(newValues.length > 0 ? newValues : undefined)
  }

  if (selectedOptions.length === 0) {
    return <MultiSelectFilter config={config} value={value} onChange={onChange} className={className} />
  }

  return (
    <div className={cn('flex flex-wrap gap-1', className)}>
      {selectedOptions.slice(0, 3).map((option) => (
        <Badge key={option.value} variant="secondary" className="h-7">
          {option.label}
          <button
            type="button"
            onClick={() => handleRemove(option.value)}
            className="ml-1 rounded-full outline-none ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2"
          >
            <X className="h-3 w-3" />
          </button>
        </Badge>
      ))}
      {selectedOptions.length > 3 && (
        <Badge variant="secondary" className="h-7">
          +{selectedOptions.length - 3} more
        </Badge>
      )}
      <MultiSelectFilter config={config} value={value} onChange={onChange} />
    </div>
  )
}