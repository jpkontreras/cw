import { useCallback, useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const setCookie = (name: string, value: string, days = 365) => {
  if (typeof document === 'undefined') {
    return;
  }

  const maxAge = days * 24 * 60 * 60;
  document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = () => {
  // Always remove dark class to force light mode
  document.documentElement.classList.remove('dark');
};

export function initializeTheme() {
  // Always apply light theme
  applyTheme();

  // Clear any stored preferences and set to light
  localStorage.setItem('appearance', 'light');
  setCookie('appearance', 'light');
}

export function useAppearance() {
  // Always return light mode
  const [appearance] = useState<Appearance>('light');

  const updateAppearance = useCallback((mode: Appearance) => {
    // Force light mode regardless of input
    localStorage.setItem('appearance', 'light');
    setCookie('appearance', 'light');
    applyTheme();
  }, []);

  useEffect(() => {
    // Ensure light mode on mount
    updateAppearance('light');
  }, [updateAppearance]);

  return { appearance: 'light' as const, updateAppearance } as const;
}
