import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { AiResponseParser } from '../utils/AiResponseParser';
import {
  Package,
  DollarSign,
  AlertCircle,
  ChevronRight,
  Sparkles,
  Check,
  MessageSquare
} from 'lucide-react';

interface Variant {
  name: string;
  price: string;
  description: string;
}

interface Modifier {
  name: string;
  price: string;
  category: string;
}

interface AiDiscoveryPanelProps {
  aiResponse: string;
  onVariantSelect: (variant: Variant) => void;
  onModifierToggle: (modifier: Modifier) => void;
  onQuestionAnswer: (question: string, answer: string) => void;
  extractedData: Record<string, unknown>;
}

export function AiDiscoveryPanel({
  aiResponse,
  onVariantSelect,
  onModifierToggle,
  onQuestionAnswer,
  extractedData
}: AiDiscoveryPanelProps) {
  const [activeTab, setActiveTab] = useState('variants');
  const [selectedVariants, setSelectedVariants] = useState<Variant[]>([]);
  const [selectedModifiers, setSelectedModifiers] = useState<Modifier[]>([]);
  const [parsedData, setParsedData] = useState<{
    variants: Variant[];
    modifiers: { group: string; items: { name: string; price: string }[] }[];
    questions: string[];
    rawOptions: { [key: string]: string[] };
  } | null>(null);

  useEffect(() => {
    if (aiResponse) {
      const actionableItems = AiResponseParser.extractActionableItems(aiResponse);
      console.log('Parsed actionable items:', actionableItems);
      setParsedData(actionableItems);
    }
  }, [aiResponse]);

  if (!parsedData) {
    return (
      <Card>
        <CardContent className="flex items-center justify-center py-8">
          <div className="text-center">
            <Sparkles className="h-8 w-8 text-muted-foreground mx-auto mb-3" />
            <p className="text-sm text-muted-foreground">Processing AI response...</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      {/* Quick Questions */}
      {/* Raw Options Display */}
      {parsedData.rawOptions && Object.keys(parsedData.rawOptions).length > 0 && (
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-sm">Available Options</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {Object.entries(parsedData.rawOptions).map(([category, options]) => (
              <div key={category}>
                <p className="text-xs font-medium text-muted-foreground mb-2">{category}:</p>
                <div className="flex flex-wrap gap-2">
                  {(options as string[]).map((option, idx) => (
                    <Badge key={idx} variant="secondary" className="text-xs">
                      {option}
                    </Badge>
                  ))}
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      {/* Quick Questions */}
      {parsedData.questions.length > 0 && (
        <Card className="border-yellow-200 bg-yellow-50/50">
          <CardHeader className="pb-3">
            <CardTitle className="text-sm flex items-center gap-2">
              <MessageSquare className="h-4 w-4" />
              Quick Questions
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {parsedData.questions.map((question: string, idx: number) => (
              <Button
                key={idx}
                variant="outline"
                size="sm"
                className="w-full justify-start text-left h-auto py-2 px-3 text-xs"
                onClick={() => onQuestionAnswer(question, '')}
              >
                <ChevronRight className="h-3 w-3 mr-2 flex-shrink-0" />
                <span className="line-clamp-2">{question}</span>
              </Button>
            ))}
          </CardContent>
        </Card>
      )}

      {/* Main Configuration Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="variants" className="text-xs">
            <Package className="h-3 w-3 mr-1" />
            Variants
            {selectedVariants.length > 0 && (
              <Badge variant="secondary" className="ml-2 px-1 h-4 text-xs">
                {selectedVariants.length}
              </Badge>
            )}
          </TabsTrigger>
          <TabsTrigger value="modifiers" className="text-xs">
            <DollarSign className="h-3 w-3 mr-1" />
            Modifiers
            {selectedModifiers.length > 0 && (
              <Badge variant="secondary" className="ml-2 px-1 h-4 text-xs">
                {selectedModifiers.length}
              </Badge>
            )}
          </TabsTrigger>
          <TabsTrigger value="dietary" className="text-xs">
            <AlertCircle className="h-3 w-3 mr-1" />
            Dietary
          </TabsTrigger>
        </TabsList>

        <TabsContent value="variants" className="space-y-4">
          {parsedData.variants.length > 0 ? (
            <div className="space-y-3">
              {parsedData.variants.map((variant, idx) => (
                <Card
                  key={idx}
                  className="cursor-pointer hover:shadow-md transition-all"
                  onClick={() => {
                    setSelectedVariants(prev => {
                      const isSelected = prev.some(v => v.name === variant.name);
                      if (isSelected) {
                        return prev.filter(v => v.name !== variant.name);
                      }
                      return [...prev, variant];
                    });
                    onVariantSelect(variant);
                  }}
                >
                  <CardContent className="p-4">
                    <div className="flex items-center justify-between">
                      <div className="flex-1">
                        <h4 className="font-medium text-sm">{variant.name}</h4>
                        <p className="text-xs text-muted-foreground mt-1">{variant.description}</p>
                        <p className="text-sm font-semibold text-primary mt-2">{variant.price}</p>
                      </div>
                      <Button
                        size="sm"
                        variant={selectedVariants.some(v => v.name === variant.name) ? "default" : "outline"}
                      >
                        {selectedVariants.some(v => v.name === variant.name) ? (
                          <>
                            <Check className="h-3 w-3 mr-1" />
                            Selected
                          </>
                        ) : (
                          'Select'
                        )}
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="text-center py-6 text-sm text-muted-foreground">
                No variants detected. Continue the conversation to define sizes and portions.
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="modifiers" className="space-y-4">
          {parsedData.modifiers.length > 0 ? (
            parsedData.modifiers.map((group, idx) => (
              <Card key={idx}>
                <CardHeader className="pb-3">
                  <CardTitle className="text-sm">{group.group}</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="flex flex-wrap gap-2">
                    {group.items.map((item, itemIdx) => (
                      <Button
                        key={itemIdx}
                        variant="outline"
                        size="sm"
                        className="h-auto py-1.5 px-3"
                        onClick={() => {
                          const modifier = { ...item, category: group.group };
                          setSelectedModifiers(prev => {
                            const isSelected = prev.some(m => m.name === item.name);
                            if (isSelected) {
                              return prev.filter(m => m.name !== item.name);
                            }
                            return [...prev, modifier];
                          });
                          onModifierToggle(modifier);
                        }}
                      >
                        {item.name}
                        <Badge variant="secondary" className="ml-2 px-1">
                          {item.price}
                        </Badge>
                      </Button>
                    ))}
                  </div>
                </CardContent>
              </Card>
            ))
          ) : (
            <Card>
              <CardContent className="text-center py-6 text-sm text-muted-foreground">
                No modifiers detected. Continue the conversation to define toppings and add-ons.
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="dietary" className="space-y-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm">Allergens & Dietary Info</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div>
                <p className="text-xs font-medium text-muted-foreground mb-2">Common Allergens</p>
                <div className="flex flex-wrap gap-2">
                  {['Gluten', 'Dairy', 'Nuts', 'Soy', 'Eggs', 'Shellfish'].map(allergen => (
                    <Badge
                      key={allergen}
                      variant={Array.isArray(extractedData?.allergens) && extractedData.allergens.includes(allergen) ? "destructive" : "outline"}
                    >
                      {allergen}
                    </Badge>
                  ))}
                </div>
              </div>

              <div>
                <p className="text-xs font-medium text-muted-foreground mb-2">Dietary Options</p>
                <div className="flex flex-wrap gap-2">
                  {['Vegetarian', 'Vegan', 'Gluten-Free', 'Keto'].map(diet => (
                    <Badge
                      key={diet}
                      variant={Array.isArray(extractedData?.dietary) && extractedData.dietary.includes(diet) ? "default" : "outline"}
                    >
                      {diet}
                    </Badge>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Summary Card */}
      <Card className="bg-muted/50">
        <CardContent className="pt-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-xs text-muted-foreground">Selected</p>
              <p className="text-sm font-medium">
                {selectedVariants.length} variants, {selectedModifiers.length} modifiers
              </p>
            </div>
            <Button size="sm" variant="secondary">
              Apply Selections
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}