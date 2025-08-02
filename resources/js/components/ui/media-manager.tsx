import * as React from 'react'
import { cn } from '@/lib/utils'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import { 
  Upload, 
  Search, 
  X, 
  Loader2,
  Check,
  ImageIcon,
  Sparkles
} from 'lucide-react'
import axios from 'axios'

interface MediaManagerProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  onSelect: (url: string) => void
  value?: string | null
}

interface DefaultImage {
  id: string
  name: string
  category: string
  url: string
  thumbnail: string
  filename: string
  sizes: {
    thumbnail: string
    small: string
    medium: string
    large: string
  }
}

const categoryIcons: Record<string, string> = {
  'Asian Cuisine': '🍜',
  'Bakery & Bread': '🥐',
  'Beverages': '🥤',
  'Desserts': '🍰',
  'Fast Food': '🍔',
  'Main Dishes': '🍽️',
}

export function MediaManager({ open, onOpenChange, onSelect, value }: MediaManagerProps) {
  const [images, setImages] = React.useState<Record<string, DefaultImage[]>>({})
  const [searchQuery, setSearchQuery] = React.useState('')
  const [loading, setLoading] = React.useState(true)
  const [selectedImage, setSelectedImage] = React.useState<string | null>(value || null)
  const [uploading, setUploading] = React.useState(false)
  const [dragActive, setDragActive] = React.useState(false)

  // Fetch default images when modal opens
  React.useEffect(() => {
    if (open) {
      fetchImages()
    }
  }, [open])

  const fetchImages = async () => {
    try {
      setLoading(true)
      const response = await axios.get('/api/default-images')
      setImages(response.data.images)
      setLoading(false)
    } catch (error) {
      console.error('Failed to load default images:', error)
      setLoading(false)
    }
  }

  // Filter images based on search
  const filteredCategories = React.useMemo(() => {
    const filtered: Record<string, DefaultImage[]> = {}
    
    Object.entries(images).forEach(([category, categoryImages]) => {
      if (searchQuery) {
        const matchingImages = categoryImages.filter(img =>
          img.name.toLowerCase().includes(searchQuery.toLowerCase())
        )
        if (matchingImages.length > 0) {
          filtered[category] = matchingImages
        }
      } else {
        filtered[category] = categoryImages
      }
    })
    
    return filtered
  }, [images, searchQuery])

  const handleImageSelect = (image: DefaultImage) => {
    setSelectedImage(image.url)
  }

  const handleConfirmSelection = () => {
    if (selectedImage) {
      onSelect(selectedImage)
      onOpenChange(false)
    }
  }

  const handleFile = async (file: File) => {
    if (file && file.type.startsWith('image/')) {
      // Validate file size (2MB)
      if (file.size > 2 * 1024 * 1024) {
        alert('File size must be less than 2MB')
        return
      }

      setUploading(true)

      // Create a preview and use data URL for now
      const reader = new FileReader()
      reader.onload = (e) => {
        const result = e.target?.result as string
        setSelectedImage(result)
        setUploading(false)
        // Auto-confirm for uploads
        onSelect(result)
        onOpenChange(false)
      }
      reader.readAsDataURL(file)
    }
  }

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true)
    } else if (e.type === 'dragleave') {
      setDragActive(false)
    }
  }

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setDragActive(false)
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0])
    }
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      handleFile(e.target.files[0])
    }
  }

  // Handle keyboard navigation
  React.useEffect(() => {
    if (!open) return

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onOpenChange(false)
      }
    }

    document.addEventListener('keydown', handleKeyDown)
    return () => document.removeEventListener('keydown', handleKeyDown)
  }, [open, onOpenChange])

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent 
        className="h-[85vh] flex flex-col p-0"
        style={{ width: '85vw', maxWidth: '1400px' }}
      >
        <DialogHeader className="px-6 py-4 border-b">
          <DialogTitle className="text-xl">Media Manager</DialogTitle>
          <DialogDescription>
            Select an image from the library or upload a new one
          </DialogDescription>
        </DialogHeader>

        <div className="flex-1 flex overflow-hidden">
          {/* Left Side - Default Images Library (2/3) */}
          <div className="flex-1 flex flex-col border-r overflow-hidden">
            {/* Search Bar */}
            <div className="p-4 border-b flex-shrink-0">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search images..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-9 pr-4"
                />
                {searchQuery && (
                  <Button
                    variant="ghost"
                    size="sm"
                    className="absolute right-1 top-1/2 -translate-y-1/2 h-7 w-7 p-0"
                    onClick={() => setSearchQuery('')}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>
            </div>

            {/* Images Grid with Categories */}
            <ScrollArea className="flex-1 overflow-hidden">
              <div className="p-6 space-y-6">
                {loading ? (
                  <div className="flex items-center justify-center h-64">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                  </div>
                ) : Object.keys(filteredCategories).length === 0 ? (
                  <div className="flex flex-col items-center justify-center h-64 text-center">
                    <ImageIcon className="h-12 w-12 text-muted-foreground mb-4" />
                    <p className="text-muted-foreground">No images found</p>
                    {searchQuery && (
                      <p className="text-sm text-muted-foreground mt-1">
                        Try adjusting your search
                      </p>
                    )}
                  </div>
                ) : (
                  Object.entries(filteredCategories).map(([category, categoryImages]) => {
                    const displayName = categoryImages[0]?.category || category
                    const icon = categoryIcons[displayName] || '📦'
                    
                    return (
                      <div key={category} className="space-y-3">
                        <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                          <span className="text-lg">{icon}</span>
                          <span>{displayName}</span>
                          <div className="h-px flex-1 bg-border" />
                        </div>
                        
                        <div className="flex flex-wrap gap-3">
                          {categoryImages.map(image => (
                            <button
                              key={image.id}
                              onClick={() => handleImageSelect(image)}
                              onDoubleClick={() => {
                                handleImageSelect(image)
                                handleConfirmSelection()
                              }}
                              className={cn(
                                'group relative overflow-hidden rounded-md border-2 transition-all',
                                'hover:scale-105 hover:shadow-md hover:z-10',
                                'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1',
                                selectedImage === image.url
                                  ? 'border-primary ring-2 ring-primary ring-offset-1'
                                  : 'border-transparent hover:border-primary/50'
                              )}
                              style={{ width: '75px', height: '75px' }}
                            >
                              <img
                                src={image.thumbnail}
                                alt={image.name}
                                className="h-full w-full object-cover"
                              />
                              
                              {/* Overlay with name */}
                              <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 via-black/50 to-transparent p-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <p className="text-[10px] font-medium text-white truncate">
                                  {image.name}
                                </p>
                              </div>
                              
                              {/* Selected indicator */}
                              {selectedImage === image.url && (
                                <div className="absolute right-1 top-1 rounded-full bg-primary p-1 shadow-lg">
                                  <Check className="h-3 w-3 text-primary-foreground" />
                                </div>
                              )}
                            </button>
                          ))}
                        </div>
                      </div>
                    )
                  })
                )}
              </div>
            </ScrollArea>
          </div>

          {/* Right Side - Upload Section (1/3) */}
          <div className="w-[350px] flex flex-col flex-shrink-0">
            <div className="p-5 flex-1 flex flex-col overflow-hidden">
              <div className="mb-4">
                <h3 className="text-lg font-semibold flex items-center gap-2">
                  <Sparkles className="h-5 w-5 text-primary" />
                  Upload Custom Image
                </h3>
                <p className="text-sm text-muted-foreground mt-1">
                  Drop your image here or click to browse
                </p>
              </div>

              <label
                htmlFor="media-upload"
                className={cn(
                  'flex-1 relative rounded-lg border-2 border-dashed cursor-pointer transition-all',
                  'hover:border-primary hover:bg-accent/50',
                  dragActive
                    ? 'border-primary bg-primary/5'
                    : 'border-muted-foreground/25 bg-muted/30'
                )}
                onDragEnter={handleDrag}
                onDragLeave={handleDrag}
                onDragOver={handleDrag}
                onDrop={handleDrop}
              >
                <div className="absolute inset-0 flex flex-col items-center justify-center p-6">
                  <div className="rounded-full bg-background p-4 mb-4 shadow-sm">
                    <Upload className="w-8 h-8 text-muted-foreground" />
                  </div>
                  <p className="text-base font-medium mb-2">
                    Drop image here
                  </p>
                  <p className="text-sm text-muted-foreground text-center">
                    or click to browse files
                  </p>
                  <p className="text-xs text-muted-foreground mt-4">
                    PNG, JPG or WEBP (MAX. 2MB)
                  </p>
                  {uploading && (
                    <div className="mt-4">
                      <Loader2 className="h-6 w-6 animate-spin" />
                    </div>
                  )}
                </div>
                <input
                  id="media-upload"
                  type="file"
                  className="hidden"
                  accept="image/*"
                  onChange={handleFileChange}
                  disabled={uploading}
                />
              </label>

              {/* Tips */}
              <div className="mt-6 space-y-2">
                <p className="text-xs text-muted-foreground">
                  💡 Double-click any image to select and close
                </p>
                <p className="text-xs text-muted-foreground">
                  ⌨️ Press ESC to cancel
                </p>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter className="px-6 py-4 border-t">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button 
            onClick={handleConfirmSelection}
            disabled={!selectedImage}
          >
            Select Image
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}