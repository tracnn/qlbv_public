FROM nginx:alpine

# Copy nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

# Create necessary directories
RUN mkdir -p /var/www/public \
    && chown -R nginx:nginx /var/www

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"] 