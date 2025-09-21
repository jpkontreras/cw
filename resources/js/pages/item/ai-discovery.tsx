import React, { useState, useEffect, useRef, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  AiQuickActions,
  AiResponseParser
} from '@/modules/item/ai-discovery';
import {
  Sparkles,
  Send,
  User,
  Bot,
  ArrowLeft,
  Loader2,
  ChevronRight,
  Plus,
  Package,
  Calculator
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import axios from 'axios';

interface Message {
  id: string;
  role: 'user' | 'assistant' | 'system';
  content: string;
  timestamp: Date;
}

interface AiDiscoveryPageProps {
  item?: {
    name?: string;
    description?: string;
    id?: number;
  };
  session?: {
    session_uuid?: string;
    messages?: Message[];
  };
}

interface VariantOption {
  id?: string;
  name: string;
  price?: number;
  description?: string;
  selected?: boolean;
}

interface ModifierOption {
  id: string;
  name: string;
  price: number;
  category?: string;
  selected?: boolean;
}

interface ExtractedData {
  name?: string;
  description?: string;
  category?: string;
  base_price?: number;
  variants?: VariantOption[];
  modifiers?: ModifierOption[];
  dietary?: string[];
  allergens?: string[];
  selectedVariant?: string;
  selectedModifiers?: string[];
  [key: string]: unknown;
}

export default function AiDiscovery({ item, session }: AiDiscoveryPageProps) {
  const [searchQuery, setSearchQuery] = useState(item?.name || '');
  const [isLoading, setIsLoading] = useState(false);
  const [sessionId, setSessionId] = useState<string | null>(session?.session_uuid || null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [showChat, setShowChat] = useState(false);
  const [extractedData, setExtractedData] = useState<ExtractedData>({});
  const [showItemBuilder, setShowItemBuilder] = useState(false);
  const [currentAiResponse, setCurrentAiResponse] = useState('');
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const searchInputRef = useRef<HTMLInputElement>(null);

  // Parse AI response to extract structured data
  const parsedData = useMemo(() => {
    if (!currentAiResponse) return null;

    const parsed = AiResponseParser.extractActionableItems(currentAiResponse);

    // Convert parsed variants to our format with prices as numbers
    const variants: VariantOption[] = parsed.variants.map(v => ({
      id: v.name.toLowerCase().replace(/\s+/g, '-'),
      name: v.name,
      description: v.description,
      price: parsePrice(v.price),
      selected: extractedData.selectedVariant === v.name
    }));

    // Convert parsed modifiers to our format
    const modifiers: ModifierOption[] = [];
    parsed.modifiers.forEach(group => {
      group.items.forEach(item => {
        modifiers.push({
          id: `${group.group}-${item.name}`.toLowerCase().replace(/\s+/g, '-'),
          name: item.name,
          price: parsePrice(item.price),
          category: group.group,
          selected: extractedData.selectedModifiers?.includes(item.name)
        });
      });
    });

    // Extract raw options with prices
    const rawOptionsWithPrices: { [key: string]: Array<{ name: string; price?: number }> } = {};
    Object.entries(parsed.rawOptions).forEach(([category, items]) => {
      rawOptionsWithPrices[category] = items.map(item => {
        // Try to find price in the original response
        const priceMatch = currentAiResponse.match(new RegExp(`${item}[^\\n]*\\(\\+?CLP\\s*([\\d,]+)\\)`, 'i'));
        return {
          name: item,
          price: priceMatch ? parsePrice(`CLP ${priceMatch[1]}`) : undefined
        };
      });
    });

    return {
      variants,
      modifiers,
      questions: parsed.questions,
      rawOptions: rawOptionsWithPrices
    };
  }, [currentAiResponse, extractedData.selectedVariant, extractedData.selectedModifiers]);

  // Calculate total price
  const totalPrice = useMemo(() => {
    let total = 0;

    // Add base price or selected variant price
    if (parsedData?.variants && extractedData.selectedVariant) {
      const selectedVar = parsedData.variants.find(v => v.name === extractedData.selectedVariant);
      if (selectedVar?.price) {
        total += selectedVar.price;
      }
    } else if (extractedData.base_price) {
      total += extractedData.base_price;
    }

    // Add selected modifiers
    if (parsedData?.modifiers && extractedData.selectedModifiers) {
      extractedData.selectedModifiers.forEach(modName => {
        const mod = parsedData.modifiers.find(m => m.name === modName);
        if (mod?.price) {
          total += mod.price;
        }
      });
    }

    return total;
  }, [parsedData, extractedData]);

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  useEffect(() => {
    if (sessionId) {
      setShowChat(true);
    }
  }, [sessionId]);

  useEffect(() => {
    if (!showChat && searchInputRef.current) {
      searchInputRef.current.focus();
    }
  }, [showChat]);

  useEffect(() => {
    // Show item builder when we have meaningful data
    if (extractedData.name || extractedData.base_price || extractedData.category || (parsedData && parsedData.variants.length > 0)) {
      setShowItemBuilder(true);
    }
  }, [extractedData, parsedData]);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const parsePrice = (priceString: string): number => {
    // Extract number from strings like "CLP 7,000" or "+CLP 1,000"
    const match = priceString.match(/[\d,]+/);
    if (match) {
      return parseInt(match[0].replace(/,/g, ''), 10);
    }
    return 0;
  };

  const formatPrice = (price: number): string => {
    return `CLP ${price.toLocaleString()}`;
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (!showChat) {
        startAiSession();
      } else if (inputValue.trim()) {
        sendMessage();
      }
    }
  };

  const startAiSession = async () => {
    if (!searchQuery.trim()) return;

    setIsLoading(true);
    setShowChat(true);

    const initialMessage: Message = {
      id: Date.now().toString(),
      role: 'user',
      content: searchQuery,
      timestamp: new Date()
    };

    setMessages([initialMessage]);

    try {
      const response = await axios.post('/items/ai-discovery/start', {
        item_name: searchQuery,
        context: {
          cuisine_type: 'general',
          location: 'Chile',
          price_tier: 'medium',
          language: 'en'
        }
      });

      if (response.data) {
        const sessionUuid = response.data.session_id || response.data.sessionId || response.data.sessionUuid;

        if (sessionUuid) {
          setSessionId(sessionUuid);
        }

        if (response.data.message || response.data.initial_message) {
          const messageContent = response.data.message || response.data.initial_message;
          setCurrentAiResponse(messageContent);

          const assistantMessage: Message = {
            id: Date.now().toString(),
            role: 'assistant',
            content: messageContent,
            timestamp: new Date()
          };

          setMessages(prev => [...prev, assistantMessage]);
        }

        if (response.data.extracted_data) {
          setExtractedData(prev => ({
            ...prev,
            ...response.data.extracted_data
          }));
        }
      }
    } catch (error) {
      console.error('Failed to start AI session:', error);
      const errorMessage: Message = {
        id: Date.now().toString(),
        role: 'assistant',
        content: 'I apologize, but I encountered an error starting the discovery session. Please try again.',
        timestamp: new Date()
      };
      setMessages(prev => [...prev, errorMessage]);
    }

    setIsLoading(false);
    setTimeout(() => {
      inputRef.current?.focus();
    }, 100);
  };

  const sendMessage = async () => {
    if (!inputValue.trim() || !sessionId || isLoading) return;

    const userMessage: Message = {
      id: Date.now().toString(),
      role: 'user',
      content: inputValue,
      timestamp: new Date()
    };

    setMessages(prev => [...prev, userMessage]);
    setInputValue('');
    setIsLoading(true);

    try {
      const response = await axios.post('/items/ai-discovery/process', {
        session_id: sessionId,
        response: inputValue,
        extracted_data: extractedData
      });

      if (response.data.next_question || response.data.message) {
        const messageContent = response.data.next_question || response.data.message;
        setCurrentAiResponse(messageContent);

        const assistantMessage: Message = {
          id: Date.now().toString(),
          role: 'assistant',
          content: messageContent,
          timestamp: new Date()
        };
        setMessages(prev => [...prev, assistantMessage]);
      }

      if (response.data.extracted_data) {
        setExtractedData(prev => ({
          ...prev,
          ...response.data.extracted_data
        }));
      }

      if (response.data.ready_to_complete) {
        completeDiscovery();
      }
    } catch (error) {
      console.error('Failed to process message:', error);
      const errorMessage: Message = {
        id: Date.now().toString(),
        role: 'assistant',
        content: 'I apologize, but I encountered an error processing your response. Please try again.',
        timestamp: new Date()
      };
      setMessages(prev => [...prev, errorMessage]);
    }

    setIsLoading(false);
  };

  const completeDiscovery = async () => {
    if (!sessionId) return;

    try {
      const response = await axios.post('/items/ai-discovery/complete', {
        session_id: sessionId,
        name: extractedData.name || searchQuery,
        description: extractedData.description || '',
        base_price: totalPrice || extractedData.base_price || 0,
        type: 'product',
        category: extractedData.category || '',
        variants: parsedData?.variants || [],
        modifiers: parsedData?.modifiers || [],
        dietary: extractedData.dietary || [],
        allergens: extractedData.allergens || []
      });

      if (response.data.success && response.data.redirect) {
        router.visit(response.data.redirect);
      }
    } catch (error) {
      console.error('Failed to complete discovery:', error);
    }
  };

  const handleQuickAction = (label: string) => {
    setInputValue(label);
    inputRef.current?.focus();
  };

  const handleVariantSelect = (variant: VariantOption) => {
    setExtractedData(prev => ({
      ...prev,
      selectedVariant: prev.selectedVariant === variant.name ? undefined : variant.name,
      base_price: variant.price // Update base price with variant price
    }));
  };

  const handleModifierToggle = (modifier: ModifierOption) => {
    setExtractedData(prev => {
      const currentModifiers = prev.selectedModifiers || [];
      const isSelected = currentModifiers.includes(modifier.name);

      return {
        ...prev,
        selectedModifiers: isSelected
          ? currentModifiers.filter(m => m !== modifier.name)
          : [...currentModifiers, modifier.name]
      };
    });
  };


  const resetDiscovery = () => {
    setShowChat(false);
    setSessionId(null);
    setMessages([]);
    setSearchQuery('');
    setExtractedData({});
    setShowItemBuilder(false);
    setCurrentAiResponse('');
    setTimeout(() => {
      searchInputRef.current?.focus();
    }, 100);
  };

  const quickActions = [
    { label: "What sizes are available?", action: () => handleQuickAction("What sizes are available?"), type: 'secondary' as const },
    { label: "Add modifiers", action: () => handleQuickAction("What modifiers or add-ons should this have?"), type: 'secondary' as const },
    { label: "Set pricing", action: () => handleQuickAction("Help me set the pricing for this item"), type: 'secondary' as const },
    { label: "Dietary info", action: () => handleQuickAction("What are the dietary restrictions and allergens?"), type: 'secondary' as const }
  ];

  if (!showChat) {
    // Initial search screen
    return (
      <AppLayout>
        <Head title="AI Menu Discovery" />

        <Page>
          <Page.Header
            title="AI Menu Discovery"
            subtitle="Intelligently discover all variants and modifiers for your menu items"
          />

          <div className="flex items-center justify-center min-h-[60vh]">
            <div className="w-full max-w-2xl text-center">
              <div className="inline-flex p-4 rounded-full bg-orange-100 dark:bg-orange-900/30 mb-8">
                <Sparkles className="h-12 w-12 text-orange-500 dark:text-orange-400" />
              </div>

              <h1 className="text-3xl font-bold mb-3">
                What food item should we explore?
              </h1>
              <p className="text-muted-foreground mb-8">
                I'll intelligently discover all its variants and modifiers
              </p>

              <div className="space-y-4">
                <Input
                  ref={searchInputRef}
                  type="text"
                  placeholder="Type any food item... (hamburger, completo, sopaipilla, pizza, etc.)"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyDown={handleKeyDown}
                  className="h-14 text-base"
                  autoFocus
                />

                <Button
                  onClick={startAiSession}
                  disabled={!searchQuery.trim() || isLoading}
                  size="lg"
                  className="w-full h-12"
                >
                  {isLoading ? (
                    <>
                      <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                      Starting Discovery...
                    </>
                  ) : (
                    <>
                      <Sparkles className="mr-2 h-5 w-5" />
                      Start AI Discovery
                      <ChevronRight className="ml-1 h-5 w-5" />
                    </>
                  )}
                </Button>
              </div>
            </div>
          </div>
        </Page>
      </AppLayout>
    );
  }

  // Chat interface
  return (
    <AppLayout>
      <Head title="AI Discovery - Chat" />

      <Page>
        <Page.Header
          title={
            <div className="flex items-center gap-3">
              <Button
                variant="ghost"
                size="icon"
                onClick={resetDiscovery}
                className="mr-2"
              >
                <ArrowLeft className="h-5 w-5" />
              </Button>
              <Package className="h-6 w-6" />
              <span>Exploring: {extractedData.name || searchQuery}</span>
            </div>
          }
          subtitle="AI-powered menu item configuration"
          actions={
            showItemBuilder && (
              <Page.Actions>
                <Button
                  onClick={completeDiscovery}
                  disabled={!extractedData.name && !totalPrice}
                >
                  <Plus className="mr-2 h-4 w-4" />
                  Create Item
                </Button>
              </Page.Actions>
            )
          }
        />

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Chat Area */}
          <div className="lg:col-span-2">
            <Card className="h-[calc(100vh-14rem)]">
              <CardContent className="flex flex-col h-full p-0">
                {/* Messages */}
                <ScrollArea className="flex-1 p-6">
                  <div className="space-y-4">
                    {messages.map((message) => (
                      <div
                        key={message.id}
                        className={cn(
                          'flex gap-3',
                          message.role === 'user' ? 'justify-end' : 'justify-start'
                        )}
                      >
                        {message.role === 'assistant' && (
                          <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <Bot className="h-5 w-5 text-primary" />
                          </div>
                        )}

                        <div className={cn(
                          'max-w-[80%] rounded-lg px-4 py-2.5',
                          message.role === 'user'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted'
                        )}>
                          <p className={cn(
                            "text-sm whitespace-pre-wrap",
                            message.role === 'user' ? 'text-primary-foreground' : 'text-foreground'
                          )}>
                            {message.content}
                          </p>
                        </div>

                        {message.role === 'user' && (
                          <div className="w-9 h-9 rounded-full bg-muted flex items-center justify-center flex-shrink-0">
                            <User className="h-5 w-5" />
                          </div>
                        )}
                      </div>
                    ))}

                    {isLoading && (
                      <div className="flex gap-3">
                        <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center animate-pulse">
                          <Bot className="h-5 w-5 text-primary" />
                        </div>
                        <div className="bg-muted rounded-lg px-4 py-3">
                          <div className="flex items-center gap-2">
                            <div className="w-2 h-2 bg-muted-foreground/40 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
                            <div className="w-2 h-2 bg-muted-foreground/40 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
                            <div className="w-2 h-2 bg-muted-foreground/40 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
                          </div>
                        </div>
                      </div>
                    )}

                    <div ref={messagesEndRef} />
                  </div>
                </ScrollArea>

                {/* Quick Actions */}
                {messages.length > 0 && !isLoading && (
                  <div className="px-4 pb-2">
                    <AiQuickActions actions={quickActions} />
                  </div>
                )}

                {/* Input Area */}
                <div className="border-t p-4">
                  <div className="flex gap-2">
                    <Input
                      ref={inputRef}
                      placeholder="Ask about variants, modifiers, pricing, or dietary info..."
                      value={inputValue}
                      onChange={(e) => setInputValue(e.target.value)}
                      onKeyDown={handleKeyDown}
                      disabled={isLoading}
                      className="flex-1"
                    />
                    <Button
                      onClick={sendMessage}
                      disabled={isLoading || !inputValue.trim()}
                    >
                      {isLoading ? (
                        <Loader2 className="h-5 w-5 animate-spin" />
                      ) : (
                        <Send className="h-5 w-5" />
                      )}
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* AI Discovery Panel */}
          <div className="lg:col-span-1 space-y-4">
            {/* Price Calculator Card */}
            {totalPrice > 0 && (
              <Card className="bg-primary/5 border-primary/20">
                <CardHeader className="pb-3">
                  <CardTitle className="text-sm flex items-center gap-2">
                    <Calculator className="h-4 w-4" />
                    Price Calculator
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    {extractedData.selectedVariant && parsedData?.variants && (
                      <div className="flex justify-between text-sm">
                        <span>{extractedData.selectedVariant}</span>
                        <span className="font-medium">
                          {formatPrice(parsedData.variants.find(v => v.name === extractedData.selectedVariant)?.price || 0)}
                        </span>
                      </div>
                    )}
                    {extractedData.selectedModifiers?.map(modName => {
                      const mod = parsedData?.modifiers.find(m => m.name === modName);
                      if (!mod) return null;
                      return (
                        <div key={mod.id} className="flex justify-between text-sm">
                          <span className="text-muted-foreground">+ {mod.name}</span>
                          <span className="text-muted-foreground">+{formatPrice(mod.price)}</span>
                        </div>
                      );
                    })}
                    <div className="border-t pt-2 flex justify-between">
                      <span className="font-semibold">Total</span>
                      <span className="text-xl font-bold text-primary">{formatPrice(totalPrice)}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Parsed Options */}
            {parsedData && (
              <Card>
                <CardContent className="p-6 space-y-6">
                  {/* Variants/Sizes */}
                  {parsedData.variants.length > 0 && (
                    <div className="space-y-3">
                      <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                        Size Options
                      </h3>
                      <div className="space-y-2">
                        {parsedData.variants.map(variant => (
                          <button
                            key={variant.id}
                            onClick={() => handleVariantSelect(variant)}
                            className={cn(
                              "w-full text-left p-3 rounded-lg border-2 transition-all",
                              extractedData.selectedVariant === variant.name
                                ? "border-primary bg-primary/5"
                                : "border-border hover:border-primary/50"
                            )}
                          >
                            <div className="flex justify-between items-center">
                              <div>
                                <p className="font-medium">{variant.name}</p>
                                {variant.description && (
                                  <p className="text-xs text-muted-foreground">{variant.description}</p>
                                )}
                              </div>
                              <Badge variant={extractedData.selectedVariant === variant.name ? "default" : "secondary"}>
                                {formatPrice(variant.price || 0)}
                              </Badge>
                            </div>
                          </button>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Modifiers grouped by category */}
                  {parsedData.modifiers.length > 0 && (
                    <div className="space-y-4">
                      {Object.entries(
                        parsedData.modifiers.reduce((acc, mod) => {
                          const category = mod.category || 'Other';
                          if (!acc[category]) acc[category] = [];
                          acc[category].push(mod);
                          return acc;
                        }, {} as Record<string, ModifierOption[]>)
                      ).map(([category, mods]) => (
                        <div key={category} className="space-y-2">
                          <h4 className="text-xs font-medium text-muted-foreground">{category}</h4>
                          <div className="flex flex-wrap gap-2">
                            {mods.map(mod => (
                              <Button
                                key={mod.id}
                                variant={extractedData.selectedModifiers?.includes(mod.name) ? "default" : "outline"}
                                size="sm"
                                onClick={() => handleModifierToggle(mod)}
                                className="h-auto py-1.5 px-3"
                              >
                                {mod.name}
                                <Badge variant="secondary" className="ml-2 px-1">
                                  +{formatPrice(mod.price)}
                                </Badge>
                              </Button>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Raw Options */}
                  {parsedData.rawOptions && Object.keys(parsedData.rawOptions).length > 0 && (
                    <div className="space-y-3">
                      {Object.entries(parsedData.rawOptions).map(([category, options]) => (
                        <div key={category}>
                          <p className="text-xs font-medium text-muted-foreground mb-2">{category}:</p>
                          <div className="flex flex-wrap gap-2">
                            {options.map((option, idx) => (
                              <Badge
                                key={idx}
                                variant="secondary"
                                className="cursor-pointer hover:bg-primary hover:text-primary-foreground transition-colors"
                                onClick={() => {
                                  if (typeof option === 'object' && option.price) {
                                    // If it has a price, treat it as a modifier
                                    handleModifierToggle({
                                      id: `${category}-${option.name}`.toLowerCase().replace(/\s+/g, '-'),
                                      name: option.name,
                                      price: option.price,
                                      category
                                    });
                                  }
                                }}
                              >
                                {typeof option === 'object' ? option.name : option}
                                {typeof option === 'object' && option.price && (
                                  <span className="ml-1 font-semibold">+{formatPrice(option.price)}</span>
                                )}
                              </Badge>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Basic Info */}
                  {(extractedData.name || extractedData.category) && (
                    <div className="space-y-3 pt-3 border-t">
                      {extractedData.name && (
                        <div>
                          <label className="text-xs text-muted-foreground">Name</label>
                          <p className="font-medium">{extractedData.name}</p>
                        </div>
                      )}

                      {extractedData.category && (
                        <div>
                          <label className="text-xs text-muted-foreground">Category</label>
                          <Badge variant="secondary">{extractedData.category}</Badge>
                        </div>
                      )}
                    </div>
                  )}
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      </Page>
    </AppLayout>
  );
}