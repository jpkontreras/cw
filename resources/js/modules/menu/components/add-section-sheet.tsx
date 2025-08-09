import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import { FolderPlus, Sparkles, Layout } from 'lucide-react';
import { useState } from 'react';
import { SECTION_ICONS, SECTION_TEMPLATES, SECTION_TEMPLATE_CATEGORIES, MENU_TEMPLATES } from '../constants';
import type { MenuSection } from '../types';

interface AddSectionSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onAddSection: (section: Partial<MenuSection> | Partial<MenuSection>[]) => void;
}

export function AddSectionSheet({ open, onOpenChange, onAddSection }: AddSectionSheetProps) {
  const [customName, setCustomName] = useState('');
  const [customDescription, setCustomDescription] = useState('');
  const [selectedIcon, setSelectedIcon] = useState<string>('appetizers');

  const handleAddCustom = () => {
    if (!customName.trim()) return;
    
    onAddSection({
      name: customName,
      description: customDescription,
      icon: selectedIcon,
    });
    
    // Reset form
    setCustomName('');
    setCustomDescription('');
    setSelectedIcon('appetizers');
    onOpenChange(false);
  };

  const handleAddTemplate = (template: typeof SECTION_TEMPLATES[0]) => {
    onAddSection({
      name: template.name,
      description: template.description,
      icon: template.icon,
    });
    onOpenChange(false);
  };

  const handleAddMenuTemplate = (template: typeof MENU_TEMPLATES[0]) => {
    const sections = template.sections.map(section => ({
      name: section.name,
      description: section.description,
      icon: section.icon,
    }));
    onAddSection(sections);
    onOpenChange(false);
  };

  // Group templates by category
  const templatesByCategory = Object.entries(SECTION_TEMPLATE_CATEGORIES).reduce((acc, [key, label]) => {
    acc[key] = {
      label,
      templates: SECTION_TEMPLATES.filter(t => t.category === key),
    };
    return acc;
  }, {} as Record<string, { label: string; templates: typeof SECTION_TEMPLATES }>);

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="w-full sm:max-w-lg overflow-y-auto">
        <SheetHeader className="px-6 pt-6">
          <SheetTitle>Add Menu Section</SheetTitle>
          <SheetDescription>
            Choose from templates or create a custom section for your menu
          </SheetDescription>
        </SheetHeader>

        <Tabs defaultValue="full-menus" className="mt-6 px-6 pb-6">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="full-menus" className="flex items-center gap-2">
              <Layout className="h-4 w-4" />
              Full Menus
            </TabsTrigger>
            <TabsTrigger value="templates" className="flex items-center gap-2">
              <Sparkles className="h-4 w-4" />
              Sections
            </TabsTrigger>
            <TabsTrigger value="custom" className="flex items-center gap-2">
              <FolderPlus className="h-4 w-4" />
              Custom
            </TabsTrigger>
          </TabsList>

          <TabsContent value="full-menus" className="mt-6 space-y-3">
            <div className="space-y-2">
              <p className="text-sm text-muted-foreground mb-4">
                Choose a complete menu template to quickly set up all sections for your restaurant type.
              </p>
              {MENU_TEMPLATES.map((template) => {
                const Icon = SECTION_ICONS[template.icon as keyof typeof SECTION_ICONS];
                return (
                  <button
                    key={template.name}
                    onClick={() => handleAddMenuTemplate(template)}
                    className="w-full flex items-start gap-3 rounded-lg border border-gray-200 p-4 text-left transition-all hover:bg-gray-50 hover:border-gray-300 hover:shadow-sm"
                  >
                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-gray-100 to-gray-200">
                      <Icon className="h-6 w-6 text-gray-700" />
                    </div>
                    <div className="flex-1">
                      <div className="font-semibold text-sm mb-0.5">{template.name}</div>
                      <div className="text-xs text-gray-600 mb-2">{template.description}</div>
                      <div className="flex flex-wrap gap-1">
                        {template.sections.map((section, idx) => (
                          <span key={idx} className="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                            {section.name}
                          </span>
                        ))}
                      </div>
                    </div>
                  </button>
                );
              })}
            </div>
          </TabsContent>

          <TabsContent value="templates" className="mt-6 space-y-6">
            {Object.entries(templatesByCategory).map(([category, { label, templates }]) => (
              <div key={category}>
                <h3 className="mb-3 text-sm font-medium text-gray-700">{label}</h3>
                <div className="grid gap-2">
                  {templates.map((template) => {
                    const Icon = SECTION_ICONS[template.icon as keyof typeof SECTION_ICONS];
                    return (
                      <button
                        key={template.name}
                        onClick={() => handleAddTemplate(template)}
                        className="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-left transition-colors hover:bg-gray-50 hover:border-gray-300"
                      >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100">
                          <Icon className="h-5 w-5 text-gray-600" />
                        </div>
                        <div className="flex-1">
                          <div className="font-medium text-sm">{template.name}</div>
                          <div className="text-xs text-gray-500 mt-0.5">{template.description}</div>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>
            ))}
          </TabsContent>

          <TabsContent value="custom" className="mt-6 space-y-4">
            <div>
              <Label htmlFor="section-name">Section Name</Label>
              <Input
                id="section-name"
                placeholder="e.g., Chef's Specials"
                value={customName}
                onChange={(e) => setCustomName(e.target.value)}
                className="mt-1.5"
              />
            </div>

            <div>
              <Label htmlFor="section-description">Description (Optional)</Label>
              <Textarea
                id="section-description"
                placeholder="Brief description of this section..."
                value={customDescription}
                onChange={(e) => setCustomDescription(e.target.value)}
                className="mt-1.5"
                rows={3}
              />
            </div>

            <div>
              <Label>Icon</Label>
              <div className="mt-2 grid grid-cols-6 gap-2">
                {Object.entries(SECTION_ICONS).map(([key, Icon]) => (
                  <button
                    key={key}
                    type="button"
                    onClick={() => setSelectedIcon(key)}
                    className={cn(
                      "flex h-10 w-10 items-center justify-center rounded-lg border-2 transition-colors",
                      selectedIcon === key
                        ? "border-blue-500 bg-blue-50"
                        : "border-gray-200 hover:border-gray-300"
                    )}
                  >
                    <Icon className={cn(
                      "h-5 w-5",
                      selectedIcon === key ? "text-blue-600" : "text-gray-500"
                    )} />
                  </button>
                ))}
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-4">
              <Button
                variant="outline"
                onClick={() => onOpenChange(false)}
              >
                Cancel
              </Button>
              <Button
                onClick={handleAddCustom}
                disabled={!customName.trim()}
              >
                Add Section
              </Button>
            </div>
          </TabsContent>
        </Tabs>
      </SheetContent>
    </Sheet>
  );
}