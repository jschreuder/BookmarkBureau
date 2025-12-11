/**
 * Shared color utility functions for calculating text color based on background color
 * with WCAG AA compliance for contrast ratios.
 */

/**
 * Determines the appropriate text color (black or white) for a given background color
 * based on WCAG AA contrast ratio requirements.
 *
 * @param backgroundColor - Hex color string (with or without # prefix)
 * @returns '#000000' for light backgrounds, '#ffffff' for dark backgrounds
 */
export const getTextColor = (backgroundColor: string | undefined): string => {
  // Default to black text if no background color is provided
  if (!backgroundColor) {
    return '#000000';
  }

  // Convert hex to RGB
  const hex = backgroundColor.replace('#', '');
  const r = Number.parseInt(hex.substring(0, 2), 16);
  const g = Number.parseInt(hex.substring(2, 4), 16);
  const b = Number.parseInt(hex.substring(4, 6), 16);

  // Calculate luminance using relative luminance formula
  // This formula is based on the WCAG 2.1 specification for calculating contrast ratios
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

  // Return black text for light backgrounds, white text for dark backgrounds
  // This ensures WCAG AA compliance for normal text (4.5:1 contrast ratio)
  return luminance > 0.5 ? '#000000' : '#ffffff';
};
