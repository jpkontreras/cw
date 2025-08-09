import React, { useState, useCallback } from 'react';
import { cn } from '@/lib/utils';
import { Upload, X, Image as ImageIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface ImagePickerProps {
  value?: File | string | null;
  onChange: (file: File | null) => void;
  className?: string;
}

export function ImagePicker({ value, onChange, className }: ImagePickerProps) {
  const [preview, setPreview] = useState<string | null>(
    typeof value === 'string' ? value : null
  );
  const [dragActive, setDragActive] = useState(false);

  const handleFile = useCallback((file: File) => {
    if (file && file.type.startsWith('image/')) {
      onChange(file);
      const reader = new FileReader();
      reader.onload = (e) => {
        setPreview(e.target?.result as string);
      };
      reader.readAsDataURL(file);
    }
  }, [onChange]);

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      handleFile(e.target.files[0]);
    }
  };

  const handleRemove = () => {
    onChange(null);
    setPreview(null);
  };

  return (
    <div className={cn("relative", className)}>
      {preview ? (
        <div className="relative group">
          <div className="aspect-square rounded-lg overflow-hidden bg-muted">
            <img
              src={preview}
              alt="Item preview"
              className="w-full h-full object-cover"
            />
          </div>
          <div className="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
            <label htmlFor="image-replace" className="cursor-pointer">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                className="pointer-events-none"
              >
                <Upload className="w-4 h-4 mr-2" />
                Replace
              </Button>
              <input
                id="image-replace"
                type="file"
                className="hidden"
                accept="image/*"
                onChange={handleChange}
              />
            </label>
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
        <label
          htmlFor="image-upload"
          className={cn(
            "relative block aspect-square rounded-lg border-2 border-dashed cursor-pointer transition-colors",
            dragActive
              ? "border-primary bg-primary/5"
              : "border-muted-foreground/25 hover:border-muted-foreground/50 bg-muted/30 hover:bg-muted/50"
          )}
          onDragEnter={handleDrag}
          onDragLeave={handleDrag}
          onDragOver={handleDrag}
          onDrop={handleDrop}
        >
          <div className="absolute inset-0 flex flex-col items-center justify-center p-6">
            <div className="rounded-full bg-muted p-3 mb-4">
              <ImageIcon className="w-8 h-8 text-muted-foreground" />
            </div>
            <p className="text-sm font-medium text-center mb-1">
              Drop image here or click to upload
            </p>
            <p className="text-xs text-muted-foreground text-center">
              PNG, JPG or WEBP (MAX. 2MB)
            </p>
          </div>
          <input
            id="image-upload"
            type="file"
            className="hidden"
            accept="image/*"
            onChange={handleChange}
          />
        </label>
      )}
    </div>
  );
}