# Use the official PHP-FPM image as the base
FROM public.ecr.aws/x2m1j4h8/payment-base:php-8.2-fpm

# Install necessary dependencies for PHP and Nginx
RUN apt-get update && apt-get install -y \
    nginx \
    zip \
    unzip \
    && docker-php-ext-install mysqli

# Set working directory to /var/www/html
WORKDIR /var/www/html/app

# Copy application files to the container
COPY public /var/www/html/app

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/app && chmod -R 755 /var/www/html/app

# Remove the default Nginx page (this prevents Nginx from serving the default page)
RUN rm /etc/nginx/sites-enabled/default

# Copy Nginx configuration file
COPY default.conf /etc/nginx/conf.d/default.conf

# Expose port 80 for the web server
EXPOSE 80

# Start PHP-FPM and Nginx services
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]