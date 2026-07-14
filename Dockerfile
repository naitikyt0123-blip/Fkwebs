# Use the official PHP image
FROM php:8.2-cli

# Copy application files to the /app directory
COPY . /app

# Set the working directory
WORKDIR /app

# Expose the port Railway provides via the PORT environment variable
# and start the built-in PHP web server
CMD php -S 0.0.0.0:$PORT
