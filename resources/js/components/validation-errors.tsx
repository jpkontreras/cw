import { Button } from '@/components/ui/button';
import { usePage } from '@inertiajs/react';
import { AlertCircle, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Errors {
  [key: string]: string | string[];
}

interface FormattedError {
  field: string;
  message: string;
}

export function ValidationErrors() {
  const { errors } = usePage().props as { errors: Errors };
  const [isVisible, setIsVisible] = useState(true);

  // Reset visibility when errors change
  useEffect(() => {
    if (errors && Object.keys(errors).length > 0) {
      setIsVisible(true);
    }
  }, [errors]);

  if (!errors || Object.keys(errors).length === 0 || !isVisible) {
    return null;
  }

  // Format errors into a more readable structure
  const formattedErrors: FormattedError[] = [];
  Object.entries(errors).forEach(([field, messages]) => {
    if (Array.isArray(messages)) {
      messages.forEach((message) => {
        formattedErrors.push({
          field: formatFieldName(field),
          message: cleanErrorMessage(message),
        });
      });
    } else if (typeof messages === 'string') {
      formattedErrors.push({
        field: field === 'error' ? '' : formatFieldName(field),
        message: cleanErrorMessage(messages),
      });
    }
  });

  if (formattedErrors.length === 0) {
    return null;
  }

  return (
    <div className="w-full rounded-lg border border-red-200 bg-red-50/50 backdrop-blur-sm p-3">
      <div className="flex items-start gap-2.5">
        <div className="flex-shrink-0">
          <div className="flex h-7 w-7 items-center justify-center rounded-full bg-red-100">
            <AlertCircle className="h-4 w-4 text-red-600" />
          </div>
        </div>

        <div className="flex-1 min-w-0">
          <h3 className="text-sm font-semibold text-red-900 mb-1">Please fix the following errors</h3>
          <div className="text-red-800">
            {formattedErrors.length === 1 ? (
              <p className="text-xs">
                <span className="font-medium">{formattedErrors[0].field}:</span> {formattedErrors[0].message}
              </p>
            ) : (
              <ul className="space-y-0.5 text-xs">
                {formattedErrors.map((error, index) => (
                  <li key={index} className="flex items-baseline gap-1.5">
                    <span className="text-red-400 flex-shrink-0">•</span>
                    <span>
                      <span className="font-medium text-red-900">{error.field}:</span> {error.message}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        <Button
          variant="ghost"
          size="sm"
          onClick={() => setIsVisible(false)}
          className="h-6 w-6 flex-shrink-0 rounded-full hover:bg-red-100 text-red-600 p-0"
        >
          <X className="h-3.5 w-3.5" />
          <span className="sr-only">Dismiss</span>
        </Button>
      </div>
    </div>
  );
}

// Helper function to format field names from dot notation
function formatFieldName(field: string): string {
  // Handle nested field names like "sections.0.sortOrder"
  const parts = field.split('.');
  let formatted = '';

  for (let i = 0; i < parts.length; i++) {
    const part = parts[i];

    // Check if it's a number (array index)
    if (!isNaN(Number(part))) {
      const sectionNum = Number(part) + 1;
      // Add section number inline
      if (i > 0 && parts[i - 1]) {
        formatted = `${parts[i - 1].charAt(0).toUpperCase() + parts[i - 1].slice(1)} ${sectionNum}`;
      }
    } else if (i === 0 || (i > 0 && isNaN(Number(parts[i - 1])))) {
      // Only add non-numeric parts that aren't already included
      const readable = part
        .replace(/([A-Z])/g, ' $1')
        .replace(/_/g, ' ')
        .replace(/sort order/gi, 'sort order')
        .toLowerCase()
        .trim();

      if (i === 0) {
        formatted = readable.charAt(0).toUpperCase() + readable.slice(1);
      } else if (parts[i - 1] && !isNaN(Number(parts[i - 1]))) {
        // This is the field name after an index
        formatted += ` ${readable}`;
      } else {
        formatted += `.${readable}`;
      }
    }
  }

  return formatted;
}

// Helper function to clean up error messages
function cleanErrorMessage(message: string): string {
  // Remove field name repetition from the message
  return message
    .replace(/The sections\.\d+\.[a-z_]+ field/gi, 'This field')
    .replace(/The sections\.\d+\.[a-z_]+/gi, 'This')
    .replace(/The [a-z_.]+\sfield/gi, 'This field')
    .replace(/field is required\.?/gi, 'is required')
    .replace(/is required\.?/gi, 'Required')
    .replace(/field must be/gi, 'must be')
    .replace(/This field Required/gi, 'Required')
    .replace(/This Required/gi, 'Required');
}
