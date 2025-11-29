# Stage 1: Build frontend
FROM node:22-alpine AS frontend-builder

WORKDIR /build

# Copy frontend package files
COPY frontend/package*.json ./

# Install dependencies (including devDependencies needed for build)
RUN npm ci --production=false

# Copy frontend source
COPY frontend/ ./

# Build Angular application in production mode
RUN npm run build -- --configuration production

# Stage 2: Install PHP dependencies
FROM composer:2 AS php-builder

WORKDIR /build

# Copy composer files
COPY composer.json composer.lock ./

# Install production dependencies only
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# Stage 3: Final runtime image
FROM php:8.4-fpm-alpine

# Install runtime dependencies
RUN apk add --no-cache \
    caddy \
    sqlite \
    && docker-php-ext-install pdo_sqlite

# Create application user (www-data already exists in php-fpm image)
WORKDIR /var/www

# Copy application code
COPY --chown=www-data:www-data . .

# Copy built frontend from stage 1
COPY --from=frontend-builder --chown=www-data:www-data /build/dist/frontend/browser ./web/

# Copy PHP dependencies from stage 2
COPY --from=php-builder --chown=www-data:www-data /build/vendor ./vendor

# Copy Caddy configuration
COPY --chown=www-data:www-data docker/Caddyfile /etc/caddy/Caddyfile

# Copy entrypoint script
COPY --chown=www-data:www-data docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create var directory for runtime data
RUN mkdir -p var/logs && chown -R www-data:www-data var

# Environment variables with sensible defaults
ENV APP_ENV=production
ENV DB_PATH=/var/www/var/bb.db
ENV RATELIMIT_DB_PATH=/var/www/var/ratelimit.db
ENV JWT_SECRET=change-me-in-production
ENV SITE_URL=http://localhost:8080

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:8080/ || exit 1

# Switch to non-root user
USER www-data

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
