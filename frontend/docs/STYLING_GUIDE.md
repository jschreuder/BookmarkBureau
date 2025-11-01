# Styling Guide

## Overview

The Bookmark Bureau frontend uses **Angular Material 3** with a custom theme based on Azure (blue) and Violet palettes.

## Material Design Theme

### Theme Configuration

The theme is defined in [frontend/src/styles.scss](frontend/src/styles.scss) using Angular Material 3's simplified API:

```scss
@use '@angular/material' as mat;

$theme: mat.define-theme((
  color: (
    theme-type: light,
    primary: mat.$azure-palette,      // Blue tones
    tertiary: mat.$violet-palette,    // Purple/violet accents
  ),
  typography: (
    brand-family: 'Roboto, "Helvetica Neue", sans-serif',
    plain-family: 'Roboto, "Helvetica Neue", sans-serif',
  ),
  density: (
    scale: 0,
  )
));
```

### Color Palette

- **Primary (Azure)**: Blue tones used for toolbars, buttons, and key UI elements
- **Tertiary (Violet)**: Purple/violet used for accents and secondary elements
- **Theme Type**: Light mode

## UI Components

### Public Dashboard Area

**Features:**
- Material toolbar with app branding (bookmark icon + "Bookmark Bureau")
- Admin access button in top-right
- Card-based dashboard grid with hover effects
- Clean, modern typography
- Gradient header for dashboard details

**Key Components:**
- [dashboard-list.component.ts](frontend/src/app/dashboard/dashboard-list/dashboard-list.component.ts)
- [dashboard-view.component.ts](frontend/src/app/dashboard/dashboard-view/dashboard-view.component.ts)

### Admin Area

**Features:**
- Left sidebar navigation with gradient header
- Material icons for all menu items
- Active link highlighting with blue accent border
- Responsive layout with sticky toolbar
- Clean white content area

**Layout:**
- Width: 280px sidebar
- Gradient header (purple to blue)
- Light gray background (#f5f5f5)
- White content area with max-width 1200px

**Key Component:**
- [admin-layout.component.ts](frontend/src/app/admin/admin-layout/admin-layout.component.ts)

## Design Patterns

### Toolbars
- Primary color for public-facing pages
- White background with shadow for admin area
- Consistent height and padding
- Material elevation classes for depth

### Cards
- Hover effects: `translateY(-4px)` + enhanced shadow
- Transition: 0.2s for smooth interactions
- Rounded corners (8px for headers, 4px for items)
- Material elevation shadows

### Icons
- Material icons used throughout
- Consistent sizing (24px default, 32px for feature icons, 48px for headers)
- Icon + text combinations for buttons

### Typography
- Headings: Font-weight 400-500 (Material style)
- Body: Roboto font family
- Consistent color hierarchy using rgba opacity

## Responsive Design

- Container max-width: 1200px
- Auto margin centering
- Grid layouts with `repeat(auto-fill, minmax(...))`
- Flexible sidebar (can be toggled in future)

## Hover States

- Cards: Lift effect + shadow enhancement
- List items: Background color change (rgba(0,0,0,0.04))
- Buttons: Material ripple effect (built-in)
- Links: Underline on hover (default)

## Gradients

The app uses a consistent purple-to-blue gradient for branded elements:

```scss
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

Used in:
- Dashboard view header
- Admin sidebar header

## Customizing the Theme

To change the color scheme, edit [frontend/src/styles.scss](frontend/src/styles.scss):

```scss
$theme: mat.define-theme((
  color: (
    primary: mat.$indigo-palette,    // Change to any Material palette
    tertiary: mat.$pink-palette,     // Change accent colors
  ),
  // ...
));
```

Available Material palettes:
- `mat.$red-palette`
- `mat.$pink-palette`
- `mat.$purple-palette`
- `mat.$indigo-palette`
- `mat.$blue-palette`
- `mat.$cyan-palette`
- `mat.$teal-palette`
- `mat.$green-palette`
- `mat.$amber-palette`
- `mat.$orange-palette`
- And more...

## Material Components Used

- **Layout**: MatSidenavModule, MatToolbarModule, MatDividerModule
- **Buttons**: MatButtonModule, MatIconModule
- **Cards**: MatCardModule
- **Lists**: MatListModule
- **Chips**: MatChipsModule (for tags)
- **Navigation**: RouterModule

## Best Practices

1. **Use Material Components**: Prefer Material components over custom HTML/CSS
2. **Consistent Spacing**: Use 8px grid (8, 16, 24, 32, 40px)
3. **Icons**: Always use Material icons for consistency
4. **Elevation**: Use Material elevation classes for shadows
5. **Accessibility**: Include aria-labels for icon-only buttons
6. **Responsive**: Use flex/grid layouts, avoid fixed widths

## Adding New Styles

When adding new components:

1. Import required Material modules
2. Use component-scoped styles (in component file)
3. Follow existing spacing/color patterns
4. Test hover states and transitions
5. Ensure responsive behavior

## Performance

- Lazy-loaded routes reduce initial bundle size
- Material components are tree-shakeable
- SCSS compiled to optimized CSS
- Current bundle size: ~95KB (gzipped)
