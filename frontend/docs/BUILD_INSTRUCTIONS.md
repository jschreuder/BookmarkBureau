# Build Instructions

## Prerequisites

Before building, you must set up your environment configuration.

### Required: Create environment.ts

The project requires an environment configuration file that is gitignored for local customization.

```bash
cd frontend/src/environments
cp environment.ts.dist environment.ts
```

Edit `environment.ts` to match your setup:
```typescript
export const environment = {
  production: false,
  apiBaseUrl: '/api'  // Adjust if needed
};
```

**Note:** This file is gitignored to allow individual developer configurations. See `ENVIRONMENT_SETUP.md` for more details.

---

## Quick Start

### Development
```bash
cd frontend
npm install
npm start
```
Access at http://localhost:4200

### Production Build
```bash
cd frontend
npm run build
```

This will:
1. Build the Angular application
2. Copy api.php and .htaccess from frontend/src
3. Flatten the output directory structure
4. Place everything in `web/` directory

### Running the Full Application
```bash
# After building
php -S localhost:8000 -t web
```

Access at http://localhost:8000

---

## Directory Structure After Build

```
web/
├── index.html              # Angular SPA entry point
├── api.php                 # PHP backend entry point
├── .htaccess               # Apache routing configuration
├── favicon.ico
├── *.js                    # JavaScript bundles
├── *.css                   # Stylesheets
└── 3rdpartylicenses.txt
```

---

## Routing

- `/` → Angular application (index.html)
- `/dashboard` → Angular route
- `/admin` → Angular route
- `/api/*` → PHP backend (api.php)

The `.htaccess` file handles this routing automatically.

---

## Important Files

### frontend/src/api.php
PHP backend entry point. This file is copied during build to `web/api.php`.

### frontend/src/.htaccess
Apache configuration for routing. Copied to `web/.htaccess` during build.

### frontend/src/environments/environment.ts
Local environment configuration. **Must be created from environment.ts.dist before building.**

---

## Build Process Details

The build process:
1. Angular CLI builds the application to `web/browser/`
2. Post-build script moves all files from `web/browser/` to `web/`
3. Post-build script removes the empty `browser/` directory

This is automated in the `package.json` build script.

---

## Testing

Run tests before building:
```bash
cd frontend
npm test              # Interactive mode with watch
npm run test:ci       # Single run with coverage
```

---

## Cleaning

To clean the build output:
```bash
rm -rf web/*
```

Then rebuild with `npm run build` from the frontend directory.

---

## Troubleshooting

### "Cannot find module 'environment'"
**Solution:** Create `environment.ts` from template:
```bash
cd frontend/src/environments
cp environment.ts.dist environment.ts
```

### Build fails with module not found errors
**Solution:** Reinstall dependencies:
```bash
cd frontend
rm -rf node_modules
npm install
```

### API calls return 404
**Solution:**
1. Verify backend PHP server is running
2. Check that `api.php` exists in `web/` directory
3. Verify `.htaccess` is present in `web/` directory
4. Ensure Apache `mod_rewrite` is enabled

### Tests fail after build
**Solution:** Clear Angular cache and rebuild:
```bash
cd frontend
rm -rf .angular
npm run build
```