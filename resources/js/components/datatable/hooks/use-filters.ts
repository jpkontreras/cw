import { router } from '@inertiajs/react'
import * as React from 'react'
import type { FilterValue, UseFiltersReturn } from '../filters/types'

interface UseFiltersOptions {
  initialValues?: Record<string, FilterValue>
  onUpdate?: (values: Record<string, FilterValue>) => void
  debounce?: number
  preserveState?: boolean
  preserveScroll?: boolean
  only?: string[]
}

export function useFilters({
  initialValues = {},
  onUpdate,
  debounce = 300,
  preserveState = false,
  preserveScroll = true,
  only,
}: UseFiltersOptions = {}): UseFiltersReturn {
  const [values, setValues] = React.useState<Record<string, FilterValue>>(initialValues)
  const [touched, setTouched] = React.useState<Record<string, boolean>>({})
  const timeoutRef = React.useRef<NodeJS.Timeout>()

  // Debounced update effect
  React.useEffect(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current)
    }

    if (onUpdate && Object.keys(touched).length > 0) {
      timeoutRef.current = setTimeout(() => {
        onUpdate(values)
      }, debounce)
    }

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current)
      }
    }
  }, [values, onUpdate, debounce, touched])

  const setValue = React.useCallback((key: string, value: FilterValue) => {
    setValues((prev) => ({ ...prev, [key]: value }))
    setTouched((prev) => ({ ...prev, [key]: true }))
  }, [])

  const setMultipleValues = React.useCallback((newValues: Record<string, FilterValue>) => {
    setValues((prev) => ({ ...prev, ...newValues }))
    const touchedKeys = Object.keys(newValues).reduce(
      (acc, key) => ({ ...acc, [key]: true }),
      {}
    )
    setTouched((prev) => ({ ...prev, ...touchedKeys }))
  }, [])

  const reset = React.useCallback((keys?: string[]) => {
    if (keys) {
      const resetValues = keys.reduce((acc, key) => ({ ...acc, [key]: undefined }), {})
      setValues((prev) => ({ ...prev, ...resetValues }))
      const resetTouched = keys.reduce((acc, key) => ({ ...acc, [key]: false }), {})
      setTouched((prev) => ({ ...prev, ...resetTouched }))
    } else {
      setValues(initialValues)
      setTouched({})
    }
  }, [initialValues])

  const clear = React.useCallback(() => {
    setValues({})
    setTouched({})
  }, [])

  const activeFilterCount = React.useMemo(() => {
    return Object.entries(values).filter(([_, value]) => {
      if (value === undefined || value === null || value === '') return false
      if (Array.isArray(value) && value.length === 0) return false
      if (value === 'all') return false
      return true
    }).length
  }, [values])

  const hasActiveFilters = activeFilterCount > 0
  const touchedKeys = Object.keys(touched).filter((key) => touched[key])

  return {
    values,
    setValue,
    setValues: setMultipleValues,
    reset,
    clear,
    hasActiveFilters,
    activeFilterCount,
    touchedKeys,
  }
}

// Hook for syncing filters with Inertia URL parameters
export function useInertiaFilters(
  filterKeys: string[],
  options: UseFiltersOptions = {}
) {
  const { values, setValue, setValues, reset, clear, ...rest } = useFilters(options)

  // Sync filters to URL
  const syncToUrl = React.useCallback(
    (filterValues: Record<string, FilterValue>) => {
      const queryParams = new URLSearchParams(window.location.search)
      const currentParams = Object.fromEntries(queryParams)
      
      // Update query params
      filterKeys.forEach((key) => {
        const value = filterValues[key]
        if (value !== undefined && value !== null && value !== '' && value !== 'all') {
          queryParams.set(key, String(value))
        } else {
          queryParams.delete(key)
        }
      })

      const newParams = Object.fromEntries(queryParams)
      
      // Check if params actually changed to avoid unnecessary navigation
      const paramsChanged = JSON.stringify(currentParams) !== JSON.stringify(newParams)
      
      if (paramsChanged) {
        // Use Inertia router for navigation
        router.get(
          window.location.pathname,
          newParams,
          {
            preserveState: options.preserveState,
            preserveScroll: options.preserveScroll,
            only: options.only,
            replace: true,
          }
        )
      }
    },
    [filterKeys, options]
  )

  // Use ref to avoid dependency issues
  const valuesRef = React.useRef(values)
  valuesRef.current = values

  // Override setValue to sync with URL
  const setValueWithSync = React.useCallback(
    (key: string, value: FilterValue) => {
      const newValues = { ...valuesRef.current, [key]: value }
      setValue(key, value)
      if (options.onUpdate) {
        options.onUpdate(newValues)
      } else {
        syncToUrl(newValues)
      }
    },
    [setValue, options, syncToUrl]
  )

  // Override setValues to sync with URL
  const setValuesWithSync = React.useCallback(
    (newValues: Record<string, FilterValue>) => {
      const mergedValues = { ...valuesRef.current, ...newValues }
      setValues(newValues)
      if (options.onUpdate) {
        options.onUpdate(mergedValues)
      } else {
        syncToUrl(mergedValues)
      }
    },
    [setValues, options, syncToUrl]
  )

  // Initialize from URL on mount only if not already initialized
  React.useEffect(() => {
    // Skip if already has initial values (to prevent infinite loop)
    if (options.initialValues && Object.keys(options.initialValues).length > 0) {
      return;
    }
    
    const queryParams = new URLSearchParams(window.location.search)
    const urlFilters: Record<string, FilterValue> = {}
    
    filterKeys.forEach((key) => {
      const value = queryParams.get(key)
      if (value) {
        urlFilters[key] = value
      }
    })

    if (Object.keys(urlFilters).length > 0) {
      setValues(urlFilters)
    }
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  return {
    values,
    setValue: setValueWithSync,
    setValues: setValuesWithSync,
    reset,
    clear,
    ...rest,
  }
}