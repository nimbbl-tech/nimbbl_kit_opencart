# Dockerfile for OpenCart PHP-FPM application with Nginx reverse proxy
FROM public.ecr.aws/x2m1j4h8/payment-base:php-8.2-fpm

# Install mysqli extension if not already in base image
# Install nginx
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli && \
    apt-get update && apt-get install -y \
    nginx \
    procps net-tools \   # <-- ADDED: tools for debugging (ps, netstat)
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY upload/ /var/www/html/

# Set ownership and permissions for writable directories
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/system/storage && \
    chmod -R 775 /var/www/html/image

# Copy PHP configuration
COPY upload/php.ini /usr/local/etc/php/conf.d/opencart.ini

# Remove default nginx site
RUN rm -f /etc/nginx/sites-enabled/default

# Expose port 80 for Nginx
EXPOSE 80

# Start PHP-FPM in foreground and Nginx in foreground
# ----------------------------------------------------
# UPDATED: Use php-fpm -F (foreground) and run nginx in foreground
#           Previously php-fpm -D would daemonize and Nginx would fail to connect
CMD ["sh", "-c", "php-fpm -F & nginx -g 'daemon off;'"]