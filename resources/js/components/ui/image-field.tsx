import * as React from 'react'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { MediaManager } from '@/components/ui/media-manager'
import { ImageIcon, X, Plus } from 'lucide-react'

interface ImageFieldProps {
  value?: string | null
  onChange: (url: string | null) => void
  label?: string
  hint?: string
  error?: string
  className?: string
}

export function ImageField({
  value,
  onChange,
  label = 'Product Image',
  hint = 'Upload custom image or choose from library',
  error,
  className,
}: ImageFieldProps) {
  const [showMediaManager, setShowMediaManager] = React.useState(false)

  const handleSelect = (url: string) => {
    onChange(url)
  }

  const handleRemove = () => {
    onChange(null)
  }

  return (
    <div className={cn('space-y-2', className)}>
      {label && (
        <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
          {label}
          <span className="text-xs text-muted-foreground ml-2">(Optional)</span>
        </label>
      )}

      <div className="relative">
        {value ? (
          <div className="relative group">
            <div className="aspect-square rounded-lg overflow-hidden bg-muted border-2 border-border">
              <img
                src={value}
                alt="Selected image"
                className="w-full h-full object-cover"
              />
            </div>
            <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                onClick={() => setShowMediaManager(true)}
              >
                <ImageIcon className="w-4 h-4 mr-2" />
                Change
              </Button>
              <Button
                type="button"
                variant="secondary"
                size="sm"
                onClick={handleRemove}
              >
                <X className="w-4 h-4 mr-2" />
                Remove
              </Button>
            </div>
          </div>
        ) : (
          <button
            type="button"
            onClick={() => setShowMediaManager(true)}
            className={cn(
              'relative w-full aspect-square rounded-lg border-2 border-dashed transition-all',
              'hover:border-primary hover:bg-accent/50',
              'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
              'border-muted-foreground/25 bg-muted/30',
              error && 'border-destructive'
            )}
          >
            <div className="absolute inset-0 flex flex-col items-center justify-center p-6">
              <div className="rounded-full bg-background p-3 mb-3 shadow-sm">
                <ImageIcon className="w-8 h-8 text-muted-foreground" />
              </div>
              <p className="text-sm font-medium text-center mb-1">
                Choose Image
              </p>
              <p className="text-xs text-muted-foreground text-center">
                {hint}
              </p>
            </div>
          </button>
        )}
      </div>

      {error && (
        <p className="text-sm text-destructive">{error}</p>
      )}

      <MediaManager
        open={showMediaManager}
        onOpenChange={setShowMediaManager}
        onSelect={handleSelect}
        value={value}
      />
    </div>
  )
}