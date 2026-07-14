FROM php:8.2-cli

# cURL extension install (API call ke liye zaroori)
RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

# data folder writable banao
RUN mkdir -p /app/data && chmod -R 777 /app/data

# Railway PORT env variable use karega
CMD php -S 0.0.0.0:${PORT:-8080} -t /app
