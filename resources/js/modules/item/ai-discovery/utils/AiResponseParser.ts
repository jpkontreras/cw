interface ParsedVariant {
  name: string;
  description: string;
  price: string;
}

interface ParsedModifier {
  name: string;
  price: string;
  category: string;
}

interface ParsedData {
  variants: ParsedVariant[];
  modifiers: ParsedModifier[];
  allergens: string[];
  dietaryInfo: string[];
  questions: string[];
  rawOptions: { [key: string]: string[] };
}

export class AiResponseParser {
  static parse(content: string): ParsedData {
    return {
      variants: this.extractVariants(content),
      modifiers: this.extractModifiers(content),
      allergens: this.extractAllergens(content),
      dietaryInfo: this.extractDietaryInfo(content),
      questions: this.extractQuestions(content),
      rawOptions: this.extractRawOptions(content)
    };
  }

  private static extractVariants(content: string): ParsedVariant[] {
    const variants: ParsedVariant[] = [];

    // Pattern 1: **Size:** Individual (CLP 7,000), Medium (CLP 12,000), Family (CLP 18,000)
    const sizePattern = /\*\*Size:\*\*\s*([^\n]+)/gi;
    const sizeMatch = sizePattern.exec(content);

    if (sizeMatch) {
      const sizesText = sizeMatch[1];
      // Extract each size option: Name (CLP price)
      const sizeOptions = sizesText.match(/([A-Za-z]+)\s*\(CLP\s*([\d,]+)\)/g);

      if (sizeOptions) {
        sizeOptions.forEach(option => {
          const match = option.match(/([A-Za-z]+)\s*\(CLP\s*([\d,]+)\)/);
          if (match) {
            variants.push({
              name: match[1],
              description: `${match[1]} size`,
              price: `CLP ${match[2]}`
            });
          }
        });
      }
    }

    // Pattern 2: Traditional variant listing with descriptions
    const traditionalPattern = /\*\*([^:*]+):\*\*[^-]*-\s*([^.]+)\.\s*CLP\s*([\d,]+)/gi;
    let match;

    while ((match = traditionalPattern.exec(content)) !== null) {
      variants.push({
        name: match[1].trim(),
        description: match[2].trim(),
        price: `CLP ${match[3]}`
      });
    }

    return variants;
  }

  private static extractModifiers(content: string): ParsedModifier[] {
    const modifiers: ParsedModifier[] = [];

    // Extract modifiers from sections like **Toppings:** or **Extra Cheese:**
    const sectionPattern = /\*\*([^:*]+):\*\*\s*([^\n]+)/gi;
    let sectionMatch;

    while ((sectionMatch = sectionPattern.exec(content)) !== null) {
      const category = sectionMatch[1].trim();
      const itemsText = sectionMatch[2];

      // Skip size sections as they're handled as variants
      if (category.toLowerCase() === 'size') continue;

      // Extract items with prices: Item (+CLP price)
      const itemPattern = /([A-Za-z\s]+)\s*\(\+?CLP\s*([\d,]+)\)/g;
      let itemMatch;

      while ((itemMatch = itemPattern.exec(itemsText)) !== null) {
        modifiers.push({
          name: itemMatch[1].trim(),
          price: `+CLP ${itemMatch[2]}`,
          category: category
        });
      }
    }

    return modifiers;
  }

  private static extractRawOptions(content: string): { [key: string]: string[] } {
    const options: { [key: string]: string[] } = {};

    // Extract sections that look like **Category:** option1, option2, option3
    const sectionPattern = /\*\*([^:*]+):\*\*\s*([^\n*]+)/gi;
    let match;

    while ((match = sectionPattern.exec(content)) !== null) {
      const category = match[1].trim();
      const itemsText = match[2].trim();

      // Clean up the text and split by commas
      const items = itemsText
        .replace(/\([^)]*\)/g, '') // Remove price tags
        .split(/[,;]/)
        .map(item => item.trim())
        .filter(item => item.length > 0 && !item.includes('CLP'));

      if (items.length > 0) {
        options[category] = items;
      }
    }

    return options;
  }

  private static extractAllergens(content: string): string[] {
    const allergens: string[] = [];
    const allergenKeywords = ['Gluten', 'Dairy', 'Soy', 'Nuts', 'Eggs', 'Shellfish', 'Fish'];

    allergenKeywords.forEach(allergen => {
      if (content.toLowerCase().includes(allergen.toLowerCase())) {
        allergens.push(allergen);
      }
    });

    return allergens;
  }

  private static extractDietaryInfo(content: string): string[] {
    const dietary: string[] = [];
    const dietaryKeywords = ['Vegetarian', 'Vegan', 'Gluten-Free', 'Keto-Friendly', 'Low-Calorie'];

    dietaryKeywords.forEach(diet => {
      if (content.toLowerCase().includes(diet.toLowerCase())) {
        dietary.push(diet);
      }
    });

    return dietary;
  }

  private static extractQuestions(content: string): string[] {
    const questions: string[] = [];

    // Extract questions that end with ?
    const questionPattern = /([^.!?\n]+\?)/g;
    let match;

    while ((match = questionPattern.exec(content)) !== null) {
      const question = match[1].trim();
      // Filter out inline questions within descriptions
      if (question.length > 15 && question.length < 200) {
        questions.push(question);
      }
    }

    return questions.slice(0, 3); // Limit to 3 most important questions
  }

  static extractActionableItems(content: string): {
    variants: { name: string; price: string; description: string }[];
    modifiers: { group: string; items: { name: string; price: string }[] }[];
    questions: string[];
    rawOptions: { [key: string]: string[] };
  } {
    const parsed = this.parse(content);

    // Group modifiers by category
    const groupedModifiers: { [key: string]: { name: string; price: string }[] } = {};

    parsed.modifiers.forEach(mod => {
      if (!groupedModifiers[mod.category]) {
        groupedModifiers[mod.category] = [];
      }
      groupedModifiers[mod.category].push({
        name: mod.name,
        price: mod.price
      });
    });

    return {
      variants: parsed.variants,
      modifiers: Object.entries(groupedModifiers).map(([group, items]) => ({
        group,
        items
      })),
      questions: parsed.questions,
      rawOptions: parsed.rawOptions
    };
  }

  static stripMarkdown(content: string): string {
    // Remove markdown formatting for display
    return content
      .replace(/\*\*([^*]+)\*\*/g, '$1') // Remove bold **text**
      .replace(/\*([^*]+)\*/g, '$1')     // Remove italic *text*
      .replace(/`([^`]+)`/g, '$1')       // Remove inline code `text`
      .replace(/^#+\s+/gm, '')           // Remove headers
      .trim();
  }
}