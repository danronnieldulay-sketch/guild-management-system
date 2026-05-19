# Use official PHP image with Apache
FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed for the app
RUN docker-php-ext-install pdo pdo_mysql

# Copy composer.json and composer.lock
COPY composer.json composer.lock ./

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application code
COPY . .

# Create necessary directories
RUN mkdir -p logs backups && chmod -R 755 logs backups

# Expose port for Render
EXPOSE 3000

# Set environment to production
ENV APP_ENV=production

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:3000"]
