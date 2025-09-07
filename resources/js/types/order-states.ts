// Server-provided state metadata
export interface StateMetadata {
  value: string;
  display_name: string;
  color: string;
  icon: string;
  action_label?: string;
  can_be_modified?: boolean;
  can_be_cancelled?: boolean;
}

export interface StateTransitionData {
  current_state: StateMetadata;
  next_states: StateMetadata[];
  can_cancel: boolean;
  is_final_state: boolean;
}

// Map server icon names to Lucide components
export const iconMap: Record<string, string> = {
  'file-text': 'FileText',
  'check-circle': 'CheckCircle',
  'play-circle': 'PlayCircle',
  'shopping-cart': 'ShoppingCart',
  'check-square': 'CheckSquare',
  'percent': 'Percent',
  'dollar-sign': 'DollarSign',
  'clock': 'Clock',
  'package': 'Package',
  'truck': 'Truck',
  'check-circle-2': 'CheckCircle2',
  'x-circle': 'XCircle',
  'rotate-ccw': 'RotateCcw',
};