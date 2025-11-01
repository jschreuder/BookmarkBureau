# Build Instructions

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

## Routing

- `/` → Angular application (index.html)
- `/dashboard` → Angular route
- `/admin` → Angular route
- `/api/*` → PHP backend (api.php)

The `.htaccess` file handles this routing automatically.

## Important Files

### frontend/src/api.php
PHP backend entry point. This file is copied during build to `web/api.php`.

### frontend/src/.htaccess
Apache configuration for routing. Copied to `web/.htaccess` during build.

## Build Process Details

The build process:
1. Angular CLI builds the application to `web/browser/`
2. Post-build script moves all files from `web/browser/` to `web/`
3. Post-build script removes the empty `browser/` directory

This is automated in the `package.json` build script.

## Cleaning

To clean the build output:
```bash
rm -rf web/*
```

Then rebuild with `npm run build` from the frontend directory.
