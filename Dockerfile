# Use the official PHP image
FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql opcache

# Set working directory
WORKDIR /var/www/html

# Copy the application files
COPY . /var/www/html

# Create the SQLite database file and ensure proper permissions
RUN touch /var/www/html/database/database.sqlite && \
    chown -R www-data:www-data /var/www/html/database

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies via Composer
RUN composer install --optimize-autoloader --no-dev

# Set up storage permissions
RUN mkdir -p /var/www/html/storage/framework/{sessions,cache,views} && \
    chown -R www-data:www-data /var/www/html/storage

# Expose the port Laravel will serve on
EXPOSE 8000

# Start the Laravel development server
CMD ["bash", "-c", "php artisan migrate --force && php artisan serve --host 0.0.0.0 --port 8000"]
