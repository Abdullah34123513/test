export const Colors = {
  primary: '#13ec5b',

  light: {
    background: '#f6f8f6',
    surface: '#ffffff',
    text: '#0f172a',
    textSecondary: '#64748b',
    textMuted: '#94a3b8',
    border: '#f1f5f9',
    borderLight: '#f8fafc',
  },

  dark: {
    background: '#102216',
    surface: '#102216',
    text: '#f1f5f9',
    textSecondary: '#94a3b8',
    textMuted: '#64748b',
    border: '#1e293b',
    borderLight: '#0f172a',
  },
};

export const Spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
};

export const BorderRadius = {
  sm: 8,
  md: 16,
  lg: 32,
  xl: 48,
  full: 9999,
};

export const FontSize = {
  xs: 10,
  sm: 12,
  base: 14,
  lg: 16,
  xl: 20,
  '2xl': 24,
};

export const FontWeight = {
  normal: '400' as const,
  medium: '500' as const,
  semibold: '600' as const,
  bold: '700' as const,
};
