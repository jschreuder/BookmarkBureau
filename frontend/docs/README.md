# Frontend Documentation

This directory contains documentation for the Bookmark Bureau frontend.

## Documents

- **[BUILD_INSTRUCTIONS.md](BUILD_INSTRUCTIONS.md)** - Quick reference for building and running the application
- **[FRONTEND_SETUP.md](FRONTEND_SETUP.md)** - Complete architecture guide, directory structure, and development workflow
- **[STYLING_GUIDE.md](STYLING_GUIDE.md)** - Material Design theme configuration, UI components, and styling best practices

## Quick Links

### Getting Started
See [BUILD_INSTRUCTIONS.md](BUILD_INSTRUCTIONS.md) for:
- Development server setup
- Production build process
- Running the full application

### Architecture & Structure
See [FRONTEND_SETUP.md](FRONTEND_SETUP.md) for:
- Project structure overview
- Routing configuration
- API service documentation
- Technology stack details

### Styling & Theming
See [STYLING_GUIDE.md](STYLING_GUIDE.md) for:
- Material 3 theme configuration
- Custom color palettes
- Component styling patterns
- Design system guidelines

## Development

```bash
# Install dependencies
npm install

# Start development server
npm start

# Build for production
npm run build
```

## Tech Stack

- **Framework**: Angular 21+
- **UI Library**: Angular Material 3
- **Language**: TypeScript
- **Styling**: SCSS
- **Build Tool**: Angular CLI with esbuild
- **Icons**: Material Icons
- **Fonts**: Roboto (via Google Fonts)

## Project Structure

```
frontend/
├── src/
│   ├── app/
│   │   ├── admin/              # Admin area components
│   │   ├── dashboard/          # Public dashboard views
│   │   ├── core/               # Services and models
│   │   └── shared/             # Shared components
│   ├── styles.scss             # Global styles and Material theme
│   ├── index.html              # HTML entry point
│   └── main.ts                 # Application bootstrap
├── docs/                       # This documentation
├── angular.json                # Angular CLI configuration
└── package.json                # Dependencies and scripts
```

## Contributing

When adding new features:
1. Follow the existing component structure
2. Use Angular Material components where possible
3. Maintain consistent styling (see STYLING_GUIDE.md)
4. Update documentation as needed

## Support

For Angular questions: https://angular.dev
For Material Design: https://material.angular.io
